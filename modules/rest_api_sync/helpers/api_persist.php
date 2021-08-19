<?php

/**
 * @file
 * Helper class for persisting records to the database.
 *
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Helper class for data persistence operations for the API.
 *
 * User by the REST API as well as REST API Sync modules for data persitance
 * (create, update & deletes) .
 *
 * @package Services
 * @subpackage REST API
 *
 * @todo Test classes
 * @todo Exceptions (and endpoint) need to return an error code.
 */
class api_persist {

  private static $licences = NULL;

  private static $mediaTypes = [];

  /**
   * Capture information about Darwin Core linked attributes in the survey.
   *
   * @var array
   */
  private static $dwcAttributes = [];

  /**
   * Finds attributes in the survey which have a DwC term name.
   *
   * Allows the code to post data into these attributes without continually
   * looking them up.
   */
  public static function initDwcAttributes($db, $surveyId) {
    self::$dwcAttributes = [
      'occAttrs' => self::fetchDwcAttrs($db, 'occurrence', $surveyId),
      'smpAttrs' => self::fetchDwcAttrs($db, 'sample', $surveyId),
    ];
  }

  private static function fetchDwcAttrs($db, $type, $surveyId) {
    // List of DwC terms that we might process values for.
    $attrs = $db->select('a.id, a.term_name')
      ->from("{$type}_attributes as a")
      ->join("{$type}_attributes_websites as aw", "aw.{$type}_attribute_id", 'a.id')
      ->where([
        'aw.restrict_to_survey_id' => $surveyId,
        'a.deleted' => 'f',
        'aw.deleted' => 'f',
      ])
      ->where('a.term_name IS NOT NULL')
      ->get();
    $r = [];
    foreach ($attrs as $attr) {
      $r[$attr->term_name] = ($type === 'sample' ? 'smp' : 'occ') . "Attr:$attr->id";
    }
    return $r;
  }

  /**
   * Persists a taxon-observation resource.
   *
   * @param object $db
   *   Database connection object.
   * @param array $observation
   *   Observation data.
   * @param int $website_id
   *   Website ID being saved into.
   * @param int $survey_id
   *   Survey dataset ID being saved into.
   * @param int $taxon_list_id
   *   Taxon list used for species name lookups.
   * @param bool $allowUpdateWhenVerified
   *   Should existing verified records be overwritten?
   *
   * @return bool
   *   True if a new record was creates, false if an existing one was updated,
   *   NULL if no action (e.g. existing verified record which was skipped due
   *   to $allowUpdateWhenVerified setting).
   *
   * @throws \exception
   */
  public static function taxonObservation($db, array $observation, $website_id, $survey_id, $taxon_list_id, $allowUpdateWhenVerified) {
    if (!empty($observation['organismKey'])) {
      $lookup = ['organism_key' => $observation['organismKey']];
    }
    elseif (!empty($observation['taxonVersionKey'])) {
      $lookup = ['search_code' => $observation['taxonVersionKey']];
    }
    elseif (!empty($observation['taxonName'])) {
      $lookup = ['original' => $observation['taxonName']];
    }
    $ttl_id = self::findTaxon($db, $taxon_list_id, $lookup);
    if (!$ttl_id) {
      throw new exception("Could not find taxon for $observation[taxonVersionKey]");
    }
    $values = self::getTaxonObservationValues($db, $website_id, $observation, $ttl_id);

    self::checkMandatoryFields($observation, 'taxon-observation');
    $existing = self::findExistingObservation($db, $observation['id'], $survey_id);
    if (count($existing)) {
      if ($existing[0]['record_status'] === 'V' && $allowUpdateWhenVerified === FALSE) {
        // Skip overwrite of a verified record.
        return NULL;
      }
      $values['occurrence:id'] = $existing[0]['id'];
      $values['sample:id'] = $existing[0]['sample_id'];
      self::applyExistingImageIds($db, $values);
    }
    else {
      $values['sample:survey_id'] = $survey_id;
    }

    // Set the spatial reference depending on the projection information
    // supplied.
    self::setSrefData($values, $observation, 'sample:entered_sref');

    // Site handling. If a known site with a SiteKey, we can create a record in
    // locations, otherwise use the free text location_name field.
    if (!empty($observation['SiteKey'])) {
      $values['sample:location_id'] = self::getLocationId($db, $website_id, $observation);
    }
    elseif (!empty($observation['siteName'])) {
      $values['sample:location_name'] = $observation['siteName'];
    }
    $obs = ORM::factory('occurrence');
    $obs->set_submission_data($values);
    $obs->submit();
    if (count($obs->getAllErrors()) !== 0) {
      throw new exception("Error occurred submitting an occurrence\n" . kohana::debug($obs->getAllErrors()));
    }
    return count($existing) === 0;
  }

  /**
   * Copies a "standard" Dwc attribute from the observation to the values.
   *
   * The DwC attribute will be linked to a custom attribute where the term_name
   * identifies the DwC term.
   *
   * @param array $observation
   *   Provided observation values.
   * @param array $values
   *   Submission values which will be updated with the value.
   * @param string $type
   *   Attribute type, smp or occ.
   * @param string $term
   *   The DwC term.
   */
  private static function copyDwcAttribute(array $observation, array &$values, $type, $term) {
    if (!empty($observation[$term]) && !empty(self::$dwcAttributes["{$type}Attrs"][$term])) {
      $values[self::$dwcAttributes["{$type}Attrs"][$term]] = $observation[$term];
    }
  }

  /**
   * Persists an annotation resource.
   *
   * @param object $db
   *   Database connection object.
   * @param array $annotation
   *   Annotation data.
   * @param int $survey_id
   *   Survey dataset ID being saved into.
   *
   * @return bool
   *   True if new annotation inserted, false if updated.
   */
  public static function annotation($db, array $annotation, $survey_id) {
    self::mapRecordStatus($annotation);
    // Set up a values array for the annotation post.
    $values = self::getAnnotationValues($db, $annotation);
    // Link to the existing observation.
    $existingObs = self::findExistingObservation($db, $annotation['taxonObservation']['id'], $survey_id);
    if (!count($existingObs)) {
      // @todo Proper error handling as annotation can't be imported. Perhaps should obtain
      // and import the observation via the API?
      throw new exception("Attempt to import annotation $annotation[id] but taxon observation not found.");
    }
    $values['occurrence_comment:occurrence_id'] = $existingObs[0]['id'];
    // Link to existing annotation if appropriate.
    $existing = self::findExistingAnnotation($db, $annotation['id'], $existingObs[0]['id']);
    if ($existing) {
      $values['occurrence_comment:id'] = $existing[0]['id'];
    }
    $annotationObj = ORM::factory('occurrence_comment');
    $annotationObj->set_submission_data($values);
    $annotationObj->submit();
    self::updateObservationWithAnnotationDetails($db, $existingObs[0]['id'], $annotation);
    if (count($annotationObj->getAllErrors()) !== 0) {
      throw new exception("Error occurred submitting an annotation\n" . kohana::debug($annotationObj->getAllErrors()));
    }
    return count($existing) === 0;
  }

  /**
   * Retrieve taxon observation values.
   *
   * Builds the values array required to post a taxon-observation resource to
   * the local database.
   *
   * @param object $db
   *   Database connection object.
   * @param int $website_id
   *   Website ID.
   * @param array $observation
   *   Taxon-observation resource data.
   * @param int $ttl_id
   *   ID of the taxa taxon list the observation points to.
   *
   * @return array
   *   Values array to use for submission building.
   *
   * @todo Reuse the last sample if it matches
   */
  private static function getTaxonObservationValues($db, $website_id, array $observation, $ttl_id) {
    $sensitive = isset($observation['sensitive']) && strtolower($observation['sensitive']) === 't';
    $values = [
      'website_id' => $website_id,
      'sample:date_start'     => $observation['startDate'],
      'sample:date_end'       => $observation['endDate'],
      'sample:date_type'      => $observation['dateType'],
      'sample:recorder_names' => isset($observation['recordedBy']) ? $observation['recordedBy'] : 'Unknown',
      'occurrence:taxa_taxon_list_id' => $ttl_id,
      'occurrence:external_key' => $observation['id'],
      'occurrence:zero_abundance' => isset($observation['zeroAbundance']) ? strtolower($observation['zeroAbundance']) : 'f',
      'occurrence:sensitivity_precision' => $sensitive ? 10000 : NULL,
    ];
    if (!empty($observation['licenceCode'])) {
      $values['sample:licence_id'] = self::getLicenceIdFromCode($db, $observation['licenceCode']);
    }
    if (!empty($observation['occurrenceRemarks'])) {
      $values['occurrence:comment'] = $observation['occurrenceRemarks'];
    }
    if (!empty($observation['media'])) {
      foreach ($observation['media'] as $idx => $medium) {
        $values["occurrence_medium:path:$idx"] = $medium['path'];
        $values["occurrence_medium:caption:$idx"] = $medium['caption'];
        $values["occurrence_medium:media_type_id:$idx"] = self::getMediaTypeId($db, $medium['mediaType']);
        if (!empty($medium['licenceCode'])) {
          $values["occurrence_medium:licence_id:$idx"] = self::getLicenceIdFromCode($db, $medium['licenceCode']);
        }
      }
    }
    if (isset($observation['occAttrs'])) {
      foreach ($observation['occAttrs'] as $id => $value) {
        self::mapOccAttrValueToTermId($db, $id, $value);
        $values["occAttr:$id"] = $value;
      }
    }
    if (!empty($observation['eventId'])) {
      $values['sample:external_key'] = $observation['eventId'];
    }
    if (!empty($observation['eventRemarks'])) {
      $values['sample:comment'] = $observation['eventRemarks'];
    }
    if (!empty($observation['identificationVerificationStatus'])) {
      self::applyIdentificationVerificationStatus($observation['identificationVerificationStatus'], $values);
    }
    self::copyDwcAttribute($observation, $values, 'smp', 'coordinateUncertaintyInMeters');
    self::copyDwcAttribute($observation, $values, 'smp', 'collectionCode');
    self::copyDwcAttribute($observation, $values, 'occ', 'individualCount');
    self::copyDwcAttribute($observation, $values, 'occ', 'lifeStage');
    self::copyDwcAttribute($observation, $values, 'occ', 'reproductiveCondition');
    self::copyDwcAttribute($observation, $values, 'occ', 'sex');
    self::copyDwcAttribute($observation, $values, 'occ', 'identifiedBy');
    self::copyDwcAttribute($observation, $values, 'occ', 'identificationRemarks');
    return $values;
  }

  private static function applyIdentificationVerificationStatus($identificationVerificationStatus, array &$values) {
    $mappings = [
      'accepted' => ['V', NULL],
      'accepted - correct' => ['V', 1],
      'accepted - considered correct' => ['V', 2],
      'unconfirmed' => ['C', NULL],
      'unconfirmed - plausible' => ['C', 3],
      'unconfirmed - not reviewed' => ['C', NULL],
      'not accepted' => ['R', NULL],
      'not accepted - unable to verify' => ['R', 4],
      'not accepted - incorrect' => ['R', 5],
    ];
    if (isset($mappings[strtolower($identificationVerificationStatus)])) {
      $statuses = $mappings[strtolower($identificationVerificationStatus)];
      kohana::log('debug', strtolower($identificationVerificationStatus) . ': ' . var_export($statuses, TRUE));
      $values['occurrence:record_status'] = $statuses[0];
      $values['occurrence:record_substatus'] = $statuses[1];
    }
    else {
      throw new exception("Invalid identificationVerificationStatus value: $identificationVerificationStatus");
    }
  }

  private static function mapOccAttrValueToTermId($db, $occAttrId, &$value) {
    $cacheId = "occAttrIsLookup-$occAttrId";
    $cache = Cache::instance();
    $attrInfo = $cache->get($cacheId);
    if ($attrInfo === NULL) {
      $qry = <<<SQL
SELECT data_type, termlist_id
FROM occurrence_attributes
WHERE id=$occAttrId AND deleted=false
SQL;
      $attrRecord = $db->query($qry)->current();
      $attrInfo = [
        'data_type' => $attrRecord->data_type,
        'termlist_id' => $attrRecord->termlist_id,
      ];
      $cache->set($cacheId, $attrInfo);
    }
    // Don't bother if not a lookup attribute.
    if ($attrInfo['data_type'] === 'L') {
      if (is_array($value)) {
        foreach ($value as &$item) {
          $qry = "SELECT id FROM cache_termlists_terms t WHERE termlist_id=$attrInfo[termlist_id] AND term='$item'";
          $termInfo = $db->query($qry)->current();
          if ($termInfo) {
            $item = $termInfo->id;
          }
        }
      }
      else {
        $qry = "SELECT id FROM cache_termlists_terms t WHERE termlist_id=$attrInfo[termlist_id] AND term='$value'";
        $termInfo = $db->query($qry)->current();
        if ($termInfo) {
          $value = $termInfo->id;
        }
      }
    }
  }

  /**
   * Converts a media type name into the termlists_term_id to store in the db.
   *
   * @param object $db
   *   Database connection object.
   * @param string $mediaType
   *   Media type name, e.g. Image:Local.
   *
   * @return int
   *   Termlists_terms.id value.
   *
   * @throws Exception
   */
  private static function getMediaTypeId($db, $mediaType) {
    if (empty(self::$mediaTypes[$mediaType])) {
      $data = $db->select('t.id')
        ->from('cache_termlists_terms as t')
        ->join('termlists as tl', 'tl.id', 't.termlist_id')
        ->where([
          't.term' => $mediaType,
          'tl.external_key' => 'indicia:media_types',
          'tl.deleted' => 'false',
        ])
        ->get()->result_array(FALSE);
      if (count($data) === 1) {
        self::$mediaTypes[$mediaType] = $data[0]['id'];
      }
      else {
        throw new Exception("Could not find unique match for media type $mediaType in the media types termlist.");
      }
    }
    return self::$mediaTypes[$mediaType];
  }

  /**
   * Builds the values array required to post an annotation resource to the db.
   *
   * @param object $db
   *   Database connection object.
   * @param array $annotation
   *   Annotation resource data.
   *
   * @return array
   *   Values array to use for submission building.
   */
  private static function getAnnotationValues($db, array $annotation) {
    return array(
      'occurrence_comment:comment' => $annotation['comment'],
      'occurrence_comment:email_address' => self::valueOrNull($annotation, 'emailAddress'),
      'occurrence_comment:record_status' => self::valueOrNull($annotation, 'record_status'),
      'occurrence_comment:record_substatus' => self::valueOrNull($annotation, 'record_substatus'),
      'occurrence_comment:query' => $annotation['question'],
      'occurrence_comment:person_name' => $annotation['authorName'],
      'occurrence_comment:external_key' => $annotation['id'],
    );
  }

  /**
   * Convert a licence code to a licence's record ID.
   *
   * @param object $db
   *   Database connection object.
   * @param string $licenceCode
   *   Licence code to retrieve the ID for.
   *
   * @return int
   *   Licence ID.
   */
  private static function getLicenceIdFromCode($db, $licenceCode) {
    if (self::$licences === NULL) {
      $qry = <<<SQL
SELECT id, code
FROM licences;
SQL;
      $licenceData = $db->query($qry)->result_array(FALSE);
      self::$licences = [];
      foreach ($licenceData as $licence) {
        self::$licences[strtolower(str_replace($licence['code'], ' ', '-'))] = $licence['id'];
      }
    }
    return self::$licences[strtolower(str_replace($licenceCode, ' ', '-'))];
  }

  /**
   * Looks up a taxon's ID from the database.
   *
   * @param object $db
   *   Database instance.
   * @param int $taxon_list_id
   *   Taxon list to lookup against.
   * @param array $lookup
   *   Array of field/value pairs look up (e.g
   *   ['external_key' => '<Taxon version key>'])
   *
   * @return int
   *   Taxa_taxon_list_id of the found record.
   *
   * @throws \exception
   */
  private static function findTaxon($db, $taxon_list_id, array $lookup) {
    $filter = '';
    if (!empty($lookup['original'])) {
      // This facilitates use of an index on searchterm. Only search on first 2
      // words in case different way of annotation subsp.
      $words = explode(' ', $lookup['original']);
      $searchTerm = $words[0] . (count($words) > 1 ? " $words[1]" : '');
      $filter = "AND searchterm like '" . pg_escape_string($searchTerm) . "%'\n";
      // Now build a custom exact match filter that looks for alternative ssp.
      // annotations.
      $exactMatches = ["'" . pg_escape_string($lookup['original']) . "'"];
      if (count($words) === 3) {
        $exactMatches[] = "'" . pg_escape_string("$words[0] $words[1] subsp. $words[2]") . "'";
      }
      $filter .= 'AND original in (' . implode(',', $exactMatches) . ")\n";
      $qry = <<<SQL
SELECT taxon_meaning_id, taxa_taxon_list_id
FROM cache_taxon_searchterms
WHERE taxon_list_id=$taxon_list_id
AND simplified='f'
$filter
ORDER BY preferred DESC
SQL;
    }
    else {
      // Add in the exact match filter for other search methods.
      foreach ($lookup as $key => $value) {
        $filter .= "AND t.$key='$value'\n";
      }
      $qry = <<<SQL
SELECT ttl.taxon_meaning_id, ttl.id as taxa_taxon_list_id
FROM taxa_taxon_lists ttl
JOIN taxa t ON t.id=ttl.taxon_id AND t.deleted=false
WHERE ttl.taxon_list_id=$taxon_list_id
AND ttl.deleted=false
$filter
ORDER BY ttl.preferred DESC
SQL;
    }

    $taxa = $db->query($qry)->result_array(FALSE);
    // Need to know if the search found a single unique taxon concept so count
    // the taxon meanings.
    $uniqueConcepts = [];
    foreach ($taxa as $taxon) {
      $uniqueConcepts[$taxon['taxon_meaning_id']] = $taxon['taxon_meaning_id'];
    }
    if (count($uniqueConcepts) === 1) {
      // If we found 1 concept, then the first match will be fine.
      return $taxa[0]['taxa_taxon_list_id'];
    }
    else {
      // If ambiguous about the concept then the search has failed.
      throw new exception('Could not find a unique preferred taxon for lookup ' . json_encode($lookup));
    }
  }

  /**
   * Mandatory field check.
   *
   * Checks that all the mandatory fields for a given resource type are
   * populated. Returns an array of missing field names, empty if the record
   * is complete.
   *
   * @param array $array
   *   List of parameters.
   * @param string $resourceName
   *   Name of the resource being checked.
   *
   * @throws \exception
   */
  private static function checkMandatoryFields(array $array, $resourceName) {
    $required = [];
    // Deletions have no other mandatory fields except the id to delete.
    if (!empty($resource['delete']) && $resource['delete'] === 'T') {
      $array[] = 'id';
    }
    else {
      switch ($resourceName) {
        case 'taxon-observation':
          $required = [
            'id',
            'startDate',
            'endDate',
            'dateType',
            'projection',
            'coordinateUncertaintyInMeters',
            'recordedBy',
          ];
          // Conditionally required fields.
          if (empty($array['gridReference'])) {
            $required[] = 'east';
            $required[] = 'north';
            if (empty($array['east']) || empty($array['north'])) {
              $required[] = 'gridReference';
            }
          }
          // One of taxonVersionKey, organismKey or taxonName required.
          if (empty($array['taxonVersionKey']) && empty($array['organismKey'])) {
            $required[] = 'taxonName';
          }
          break;

        case 'annotation':
          // @todo Mandatory fields for an annotation.
          break;
      }
    }
    $missing = array_diff($required, array_keys($array));
    if (!empty($missing)) {
      throw new exception("$resourceName has missing mandatory field values: " . implode(', ', $missing));
    }
  }

  /**
   * Finds an existing observation.
   *
   * Retrieve existing observation details from the database for an ID supplied
   * by a call to the REST API.
   *
   * @param object $db
   *   Database instance.
   * @param string $id
   *   The taxon-observation's ID as returned by a call to the REST api.
   * @param int $survey_id
   *   The database survey ID value to lookup within.
   *
   * @return array
   *   Array containing occurrence and sample ID plus record_status for any
   *   existing matching records.
   */
  private static function findExistingObservation($db, $id, $survey_id) {
    $thisSystemUserId = Kohana::config('rest_api_sync.user_id');
    $recordOriginHere = substr($id, 0, strlen($thisSystemUserId)) === $thisSystemUserId;
    // Look for an existing record to overwrite.
    $filter = array(
      'o.deleted' => 'f',
      's.deleted' => 'f',
    );
    // @todo Do we want to overwrite existing records which originated here?
    // @todo What happens if origin here but record missing?
    if ($recordOriginHere) {
      $filter['o.id'] = substr($id, strlen($thisSystemUserId));
    }
    else {
      $filter['o.external_key'] = (string) $id;
      $filter['s.survey_id'] = $survey_id;
    }
    $existing = $db->select('o.id, o.sample_id, o.record_status')
      ->from('occurrences o')
      ->join('samples as s', 'o.sample_id', 's.id')
      ->where($filter)
      ->get()->result_array(FALSE);
    return $existing;
  }

  /**
   * Match incoming images to the existing media IDs in the database.
   *
   * For an existing record submission that contains images, ensure that the
   * existing images are overwritten rather than duplicated.
   *
   * @param object $db
   *   Database connection.
   * @param array $values
   *   Submitted values which will be updated with existing image IDs.
   */
  private static function applyExistingImageIds($db, array &$values) {
    // Find the images with paths in the incoming data.
    $pathValueKeys = preg_grep('/^occurrence_medium:path:\d+$/', array_keys($values));
    if (count($pathValueKeys) === 0) {
      // No incoming images so nothing to do.
      return;
    }
    // Get the portion of the submitted values that are image path fields.
    $pathValues = array_intersect_key($values, array_combine($pathValueKeys, $pathValueKeys));
    // Find the existing database records.
    $existing = $db->select('id, path')
      ->from('occurrence_media')
      ->where([
        'occurrence_id' => $values['occurrence:id'],
        'deleted' => 'f',
      ])
      ->get()->result_array(FALSE);
    // Match the database records to the incoming values using the path.
    foreach ($existing as $dbImage) {
      $key = array_search($dbImage['path'], $pathValues);
      if ($key !== FALSE) {
        // Add the image ID to the submitted values to cause an update.
        $values[str_replace(':path:', ':id:', $key)] = $dbImage['id'];
      }
    }
  }

  /**
   * Find an existing annotation.
   *
   * Retrieve existing comment details from the database for an annotation ID
   * supplied by a call to the REST API.
   *
   * @param object $db
   *   Database instance.
   * @param string $id
   *   The taxon-observation's ID as returned by a call to the REST api.
   * @param int $occ_id
   *   The database observation ID value to lookup within.
   *
   * @return array
   *   Array containing occurrence comment ID for any existing matching records.
   */
  private static function findExistingAnnotation($db, $id, $occ_id) {
    // @todo Add external key to comments table? OR do we use the timestamp?
    $userId = Kohana::config('rest_api_sync.user_id');
    $recordOriginHere = substr($id, 0, strlen($userId)) === $userId;
    // Look for an existing record to overwrite.
    $filter = array(
      'oc.deleted' => 'f',
      'occurrence_id' => $occ_id,
    );
    // @todo What happens if origin here but record missing?
    if ($recordOriginHere) {
      $filter['oc.id'] = substr($id, strlen($userId) - 1);
    }
    else {
      $filter['oc.external_key'] = $id;
    }
    $existing = $db->select('oc.id')
      ->from('occurrence_comments oc')
      ->join('cache_occurrences as o', 'o.id', 'oc.occurrence_id')
      ->where($filter)->get()->result_array(FALSE);
    return $existing;
  }

  /**
   * Sets spatial reference data for an observation.
   *
   * Uses the data in an observation to set the spatial reference information
   * in a values array before it can be submitted via ORM to the database.
   *
   * @param array $values
   *   The values array to add the spatial reference information to.
   * @param array $observation
   *   The observation data array.
   * @param string $fieldname
   *   The name of the spatial reference field to be set in the values array,
   *   e.g. sample:entered_sref.
   */
  private static function setSrefData(array &$values, array $observation, $fieldname) {
    if ($observation['projection'] === 'OSGB' || $observation['projection'] === 'OSI') {
      $values[$fieldname] = strtoupper(str_replace(' ', '', $observation['gridReference']));
      $values[$fieldname . '_system'] = $observation['projection'] === 'OSGB' ? 'OSGB' : 'OSIE';
    }
    elseif ($observation['projection'] === 'WGS84') {
      $values[$fieldname] = self::formatLatLong($observation['north'], $observation['east']);
      $values[$fieldname . '_system'] = 4326;
    }
    elseif ($observation['projection'] === 'OSGB36') {
      $values[$fieldname] = self::formatEastNorth($observation['east'], $observation['north']);
      $values[$fieldname . '_system'] = 27700;
    }
  }

  /**
   * Returns a formatted decimal latitude and longitude string.
   *
   * @param float $lat
   *   Latitude.
   * @param float $long
   *   Longitude.
   *
   * @return string
   *   Formatted lat long.
   */
  private static function formatLatLong($lat, $long) {
    $ns = $lat >= 0 ? 'N' : 'S';
    $ew = $long >= 0 ? 'E' : 'W';
    // Variant of abs() function using preg_replace avoids changing float to
    // scientific notation.
    $lat = preg_replace('/^-/', '', $lat);
    $long = preg_replace('/^-/', '', $long);
    return "$lat$ns $long$ew";
  }

  /**
   * Returns a formatted decimal east and north string.
   *
   * @param $east
   *   Easting.
   * @param $north
   *   Northing.
   * @return string
   *   Formatted easting/northing.
   */
  private static function formatEastNorth($east, $north) {
    return "$east, $north";
  }

  /**
   * Retrieves the location_id for the locations records associated with an incoming observation.
   * The observation must have a SiteKey specified which will be used to lookup a location linked
   * to the server's website ID. If it does not exist, then it will be created using the observation's
   * spatial reference as a centroid.
   *
   * @param object $db
   *   Database instance.
   * @param int $website_id
   *   ID of the website registration the location should be looked up from.
   * @param array $observation
   *   The observation data array.
   *
   * @return int
   *   The ID of the location record in the database.
   */
  private static function getLocationId($db, $website_id, $observation) {
    $existing = $db->select('l.id')
      ->from('locations as l')
      ->join('locations_websites as lw', 'lw.location_id', 'l.id')
      ->where(array(
        'l.deleted' => 'f',
        'lw.deleted' => 'f',
        'lw.website_id' => $website_id,
        'l.code' => $observation['SiteKey'],
      ))->get()->result_array(FALSE);
    if (count($existing)) {
      return $existing[0]['id'];
    }
    else {
      return self::createLocation($website_id, $observation);
    }
  }

  /**
   * Creates a location in the database from the information supplied in an observation. The
   * observation should have a SiteKey specified so that future observations for the same SiteKey
   * can be linked to the same location.
   *
   * @param int $website_id
   *   ID of the database registration to add the location to.
   * @param array $observation
   *   The observation data array.
   *
   * @return int
   *   The ID of the location record created in the database.
   *
   * @todo Join the location to the server's associated website
   */
  private static function createLocation($website_id, array $observation) {
    $location = ORM::factory('location');
    $values = array(
      'location:code' => $observation['siteKey'],
      'location:name' => $observation['siteName']
    );
    self::setSrefData($values, $observation, 'location:centroid_sref');
    $location->set_submission_data($values);
    $location->submit();
    // @todo Error handling on submission.
    // @todo Link the location to the website we are importing into?
    return $location->id;
  }

  /**
   * Converts the record status codes in an annotation into Indicia codes.
   *
   * @param array $annotation
   *   Annotation data.
   */
  private static function mapRecordStatus(array &$annotation) {
    if (empty($annotation['statusCode1'])) {
      $annotation['record_status'] = NULL;
    }
    else {
      switch ($annotation['statusCode1']) {
        case 'A':
          // Accepted = verified.
          $annotation['record_status'] = 'V';
          break;

        case 'N':
          // Not accepted = rejected.
          $annotation['record_status'] = 'R';
          break;

        default:
          $annotation['record_status'] = 'C';
      }
    }
    if (empty($annotation['statusCode2'])) {
      $annotation['record_substatus'] = NULL;
    }
    else {
      $annotation['record_substatus'] = $annotation['statusCode2'];
    }
  }

  /**
   * If an annotation provides a newer record status or identification than that already
   * associated with an observation, updates the observation.
   *
   * @param int $occurrence_id
   *   ID of the associated occurrence record in the database.
   * @param array $annotation
   *   Annotation object loaded from the REST API.
   *
   * @throws exception
   */
  private static function updateObservationWithAnnotationDetails($db, $occurrence_id, array $annotation) {
    // Find the original record to compare against.
    $oldRecords = $db
      ->select('record_status, record_substatus, taxa_taxon_list_id')
      ->from('cache_occurrences')
      ->where('id', $occurrence_id)
      ->get()->result_array(FALSE);
    if (!count($oldRecords)) {
      throw new exception('Could not find cache_occurrences record associated with a comment.');
    }

    // Find the taxon information supplied with the comment's TVK.
    $newTaxa = $db
      ->select('id, taxonomic_sort_order, taxon, authority, preferred_taxon, default_common_name, search_name, ' .
        'external_key, taxon_meaning_id, taxon_group_id, taxon_group')
      ->from('cache_taxa_taxon_lists')
      ->where([
        'preferred' => 't',
        'external_key' => $annotation['taxonVersionKey'],
        'taxon_list_id' => kohana::config('rest_api_sync.taxon_list_id'),
      ])
      ->limit(1)
      ->get()->result_array(FALSE);
    if (!count($newTaxa)) {
      throw new exception('Could not find cache_taxa_taxon_lists record associated with an update from a comment.');
    }

    $oldRecord = $oldRecords[0];
    $newTaxon = $newTaxa[0];

    $new_status = $annotation['record_status'] === $oldRecord['record_status']
      ? FALSE : $annotation['record_status'];
    $new_substatus = $annotation['record_substatus'] === $oldRecord['record_substatus']
      ? FALSE : $annotation['record_substatus'];
    $new_ttlid = $newTaxon['id'] === $oldRecord['taxa_taxon_list_id']
      ? FALSE : $newTaxon['id'];

    // Does the comment imply an allowable change to the occurrence's attributes?
    if ($new_status || $new_substatus || $new_ttlid) {
      $oupdate = array('updated_on' => date("Ymd H:i:s"));
      $coupdate = array('cache_updated_on' => date("Ymd H:i:s"));
      if ($new_status || $new_substatus) {
        $oupdate['verified_on'] = date("Ymd H:i:s");
        // @todo Verified_by_id needs to be mapped to a proper user account.
        $oupdate['verified_by_id'] = 1;
        $coupdate['verified_on'] = date("Ymd H:i:s");
        $coupdate['verifier'] = $annotation['authorName'];
      }
      if ($new_status) {
        $oupdate['record_status'] = $new_status;
        $coupdate['record_status'] = $new_status;
      }
      if ($new_substatus) {
        $oupdate['record_substatus'] = $new_substatus;
        $coupdate['record_substatus'] = $new_substatus;
      }
      if ($new_ttlid) {
        $oupdate['taxa_taxon_list_id'] = $new_ttlid;
        $coupdate['taxa_taxon_list_id'] = $new_ttlid;
        $coupdate['taxonomic_sort_order'] = $newTaxon['taxonomic_sort_order'];
        $coupdate['taxon'] = $newTaxon['taxon'];
        $coupdate['preferred_taxon'] = $newTaxon['preferred_taxon'];
        $coupdate['authority'] = $newTaxon['authority'];
        $coupdate['default_common_name'] = $newTaxon['default_common_name'];
        $coupdate['search_name'] = $newTaxon['search_name'];
        $coupdate['taxa_taxon_list_external_key'] = $newTaxon['external_key'];
        $coupdate['taxon_meaning_id'] = $newTaxon['taxon_meaning_id'];
        $coupdate['taxon_group_id'] = $newTaxon['taxon_group_id'];
        $coupdate['taxon_group'] = $newTaxon['taxon_group'];
      }
      $db->update('occurrences',
        $oupdate,
        array('id' => $occurrence_id)
      );
      $db->update('cache_occurrences',
        $coupdate,
        array('id' => $occurrence_id)
      );
      // @todo create a determination if this is not automatic
    }
  }

  /**
   * Simple utility function to return a value from an array, or null if not present.
   *
   * @param $array
   * @param $key
   *
   * @return mixed
   */
  private static function valueOrNull($array, $key) {
    return isset($array[$key]) ? $array[$key] : NULL;
  }

}