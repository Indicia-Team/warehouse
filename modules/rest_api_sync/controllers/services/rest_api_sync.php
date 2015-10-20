<?php

/**
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
 * @package Services
 * @subpackage REST API Sync
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link    http://code.google.com/p/indicia/
 */

/**
 *
 */
class Rest_Api_Sync_Controller extends Controller {

  private $db;

  private $fromDateTime;

  private $nextFromDateTime;

  /**
   * Main controller method for the rest_api_sync module. Initiates a synchronisation.
   */
  public function index() {
    $this->db = Database::instance();
    rest_api_sync::$client_user_id = Kohana::config('rest_api_sync.user_id');
    $servers = Kohana::config('rest_api_sync.servers');
    $this->fromDateTime = variable::get('rest_api_sync_last_run', '2015-01-01');
    $this->nextFromDateTime = date("Y-m-d\TH:i:s");
    echo "<h1>REST API Sync</h1>";
    foreach ($servers as $serverId => $server) {
      echo "<h2>$serverId</h2>";
      $nextPageOfProjectsUrl = rest_api_sync::get_server_projects_url($server['url']);
      while ($nextPageOfProjectsUrl) {
        $data = rest_api_sync::get_server_projects($nextPageOfProjectsUrl, $serverId);
        if (!isset($data['data'])) {
          echo 'Invalid response<br/>';
          echo "URL: $nextPageOfProjectsUrl<br/>";
          var_export($data);
          throw new exception('Response did not include data element');
        }
        $projects = $data['data'];
        foreach ($projects as $project) {
          $survey_id = $this->get_survey_id($server, $project);
          $this->sync_from_project($server, $serverId, $project, $survey_id);
        }
        $nextPageOfProjectsUrl = isset($data['paging']['next']) ? $data['paging']['next'] : false;
      }
      /*
       // TEMPORARY TEST CODE
      $project = array('id'=>'BT', 'Title'=>'Sync from BTO', 'Description'=>'Data synced from BTO');
      $survey_id = $this->get_survey_id($server, $project);
      $this->sync_from_project($server, $serverId, $project, $survey_id);
      */


    }
    variable::set('rest_api_sync_last_run', $this->nextFromDateTime);
  }

  private function sync_from_project($server, $serverId, $project, $survey_id) {
    echo "<h3>$project[id]</h3>";
    if (!isset($server['resources']) || in_array('taxon-observations', $server['resources']))
      self::sync_taxon_observations($server, $serverId, $project, $survey_id);
    if (!isset($server['resources']) || in_array('annotations', $server['resources']))
      self::sync_annotations($server, $serverId, $project, $survey_id);
  }
  
  private function sync_taxon_observations($server, $serverId, $project, $survey_id) {
    $datasetNameAttrId = Kohana::config('rest_api_sync.dataset_name_attr_id');
    $userId = Kohana::config('rest_api_sync.user_id');
    // @todo Proper handling of the last sync date
    $taxon_list_id = Kohana::config('rest_api_sync.taxon_list_id');
    $nextPageOfTaxonObservationsUrl = rest_api_sync::get_server_taxon_observations_url(
        $server['url'], $project['id'], $this->fromDateTime);
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
    while ($nextPageOfTaxonObservationsUrl) {
      $data = rest_api_sync::get_server_taxon_observations($nextPageOfTaxonObservationsUrl, $serverId);
      $observations = $data['data'];
      echo count($observations) . ' records found<br/>';
      foreach ($observations as $observation) {
        $taxa = $this->db->select('id')
          ->from('cache_taxa_taxon_lists')
          ->where(array(
            'taxon_list_id'=>$taxon_list_id,
            'external_key' => $observation['TaxonVersionKey'],
            'preferred' => 't'
          ))->get()->result_array(false);
        if (count($taxa)!==1) {
          // @todo Error handling
          kohana::log('debug', "REST API Sync could not find taxon for $observation[taxonVersionKey]");
          echo "Could not find taxon for $observation[taxonVersionKey]<br/>";
        } 
        else {
          // Find if the record originated here or elsewhere
          $recordOriginHere = substr($observation['id'], 0, strlen($userId)) === $userId;
          $existing = $this->find_existing_observation($observation['id'], $survey_id);
          // @todo Reuse the last sample if it matches
          $values = array(
            'website_id' => $server['website_id'],
            'sample:date_start'     => $observation['startDate'],
            'sample:date_end'       => $observation['endDate'],
            'sample:date_type'      => $observation['dateType'],
            'sample:recorder_names' => isset($observation['recorder'])
                ? $observation['recorder'] : 'Unknown',
            'occurrence:taxa_taxon_list_id' => $taxa[0]['id'],
            'occurrence:external_key' => $observation['id'],
            'occurrence:zero_abundance' => isset($observation['zeroAbundance'])
                ? strtolower($observation['zeroAbundance']) : 'f',
            'occurrence:sensitivity_precision' => isset($observation['sensitive'])
                && strtolower($observation['sensitive'])==='t' ? 10000 : null,
            'occurrence:record_status' => 'C'
          );
          if (count($existing)) {
            $values['occurrence:id'] = $existing[0]['id'];
            $values['sample:id'] = $existing[0]['sample_id'];
          } else {
            //
            $values['sample:survey_id'] = $survey_id;
          }
          // If the record was originated from a different system, the specified dataset name
          // needs to be stored
          if ($datasetNameAttrId && !$recordOriginHere)
            $values["smpAttr:$datasetNameAttrId"] = $observation['datasetName'];

          // Set the spatial reference depending on the projection information supplied.
          $this->set_sref_data($values, $observation, 'sample:entered_sref');
          // @todo Should the precision field be stored in a custom attribute?
          // Site handling. If a known site with a SiteKey, we can create a record in locations, otherwise use the 
          // free text location_name field.
          if (!empty($observation['SiteKey'])) {
            $values['sample:location_id'] = $this->get_location_id($server, $observation);
          }
          elseif (!empty($observation['siteName'])) {
            $values['sample:location_name'] = $observation['siteName'];
          }
          $obs = ORM::factory('occurrence');
          $obs->set_submission_data($values);
          $obs->submit();
          if (count($obs->getAllErrors())!==0) {
            echo "Error occurred submitting an occurrence<br/>";
            kohana::log('debug', "REST API Sync error occurred submitting an occurrence");
            kohana::log('debug', kohana::debug($obs->getAllErrors()));
            $tracker['errors']++;
          } else {
            if (count($existing))
              $tracker['updates']++;
            else
              $tracker['inserts']++;
          }
        }
      }
      $nextPageOfTaxonObservationsUrl = isset($data['paging']['next']) ? $data['paging']['next'] : false;
      echo $nextPageOfTaxonObservationsUrl.'--'; throw new exception;
    }
    echo "<strong>Observations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]<br/>";
  }
  
  private function sync_annotations($server, $serverId, $project, $survey_id) {
    $nextPageOfAnnotationsUrl = rest_api_sync::get_server_annotations_url(
        $server['url'], $project['id'], $this->fromDateTime);
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
    while ($nextPageOfAnnotationsUrl) {
      $data = rest_api_sync::get_server_annotations($nextPageOfAnnotationsUrl, $serverId);
      $annotations = $data['data'];
      foreach ($annotations as $annotation) {
        $this->map_record_status($annotation);
        // set up a values array for the annotation post
        $values = array(
          'occurrence_comment:comment' => $annotation['comment'],
          'occurrence_comment:email_address' => $this->valueOrNull($annotation, 'emailAddress'),
          'occurrence_comment:record_status' => $this->valueOrNull($annotation, 'record_status'),
          'occurrence_comment:record_substatus' => $this->valueOrNull($annotation, 'record_substatus'),
          'occurrence_comment:query' => $annotation['question'],
          'occurrence_comment:person_name' => $annotation['authorName'],
          'occurrence_comment:external_key' => $annotation['id']

          // @todo Other fields

        );

        // link to the existing observation
        $existingObs = $this->find_existing_observation($annotation['taxonObservation']['id'], $survey_id);
        if (!count($existingObs)) {
          // @todo Proper error handling as annotation can't be imported. Perhaps should obtain
          // and import the observation via the API?
          echo "Attempt to import annotation $annotation[id] but taxon observation not found<br/>";
        } else {
          $values['occurrence_comment:occurrence_id'] = $existingObs[0]['id'];
          // link to existing annotation if appropriate
          $existing = $this->find_existing_annotation($annotation['id'], $existingObs[0]['id']);
          if ($existing) {
            $values['occurrence_comment:id'] = $existing[0]['id'];
          }
          $annotationObj = ORM::factory('occurrence_comment');
          $annotationObj->set_submission_data($values);
          $annotationObj->submit();
          if (count($annotationObj->getAllErrors())!==0) {
            echo "Error occurred submitting an occurrence<br/>";
            kohana::log('debug', "REST API Sync error occurred submitting an occurrence");
            kohana::log('debug', kohana::debug($annotationObj->getAllErrors()));
            $tracker['errors']++;
          } else {
            if (count($existing))
              $tracker['updates']++;
            else
              $tracker['inserts']++;
          }
          $this->update_observation_with_annotation_details($existingObs[0]['id'], $annotation);
        }

      }
      $nextPageOfAnnotationsUrl = isset($data['paging']['next']) ? $data['paging']['next'] : false;
    }
    echo "<strong>Annotations</strong><br/><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]<br/>";
  }

  /**
   * If an annotation provides a newer record status or identification than that already
   * associated with an observation, updates the observation.
   *
   * @param integer $occurrence_id ID of the associated occurrence record in the database.
   * @param array $annotation Annotation object loaded from the REST API.
   */
  function update_observation_with_annotation_details($occurrence_id, $annotation) {
    // Find the original record to compare against
    $oldRecords = $this->db
      ->select('record_status, record_substatus, taxa_taxon_list_id')
      ->from('cache_occurrences')
      ->where('id', $occurrence_id)
      ->get()->result_array(false);
    if (!count($oldRecords))
      throw new exception('Could not find cache_occurrences record associated with a comment.');

    // Find the taxon information supplied with the comment's TVK
    $newTaxa = $this->db
      ->select('id, taxonomic_sort_order, taxon, authority, preferred_taxon, default_common_name, search_name, ' .
          'external_key, taxon_meaning_id, taxon_group_id, taxon_group')
      ->from('cache_taxa_taxon_lists')
      ->where('taxon_list_id', 1) /***********************/
      ->where(array('preferred'=>'t', 'external_key'=>$annotation['taxonVersionKey']))
      ->limit(1)
      ->get()->result_array(false);
    f (!count($newTaxa))
      throw new exception('Could not find cache_taxa_taxon_lists record associated with an update from a comment.');

    $oldRecord = $oldRecords[0];
    $newTaxon = $newTaxa[0];

    $new_status = $annotation['record_status']===$oldRecord['record_status']
        ? false : $annotation['record_status'];
    $new_substatus = $annotation['record_substatus']===$oldRecord['record_substatus']
        ? false : $annotation['record_substatus'];
    $new_ttlid = $newTaxon['id']===$oldRecord['taxa_taxon_list_id']
        ? false : $newTaxon['id'];

    // Does the comment imply an allowable change to the occurrence's attributes?
    if ($new_status || $new_substatus || $new_ttlid) {
      $oupdate = array('updated_on' => date("Ymd H:i:s"));
      $coupdate = array('cache_updated_on' => date("Ymd H:i:s"));
      if ($new_status || $new_substatus) {
        $oupdate['verified_on'] = date("Ymd H:i:s");
        // @todo Verified_by_id needs to be mapped to a proper user account.
        $oupdate['verified_by_id'] = 1;
        $coupdate['verified_on'] = date("Ymd H:i:s");
        // @todo Verified_by_id needs to be mapped to a proper user account.
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
      $this->db->update('occurrences',
        $oupdate,
        array('id' => $occurrence_id)
      );
      $this->db->update('cache_occurrences',
        $coupdate,
        array('id' => $occurrence_id)
      );
      // @todo create a determination if this is not automatic
    }
  }

  /**
   * Converts the record status codes in an annotation into Indicia codes.
   * @param $annotation
   */
  private function map_record_status(&$annotation) {
    if (empty($annotation['statusCode1']))
      $annotation['record_status'] = null;
    else {
      switch ($annotation['statusCode1']) {
        case 'A': // accepted = verified
          $annotation['record_status'] = 'V';
          break;
        case 'N': // not accepted = rejected
          $annotation['record_status'] = 'R';
          break;
        default:
          $annotation['record_status'] = 'C';
      }
    }
    if (empty($annotation['statusCode2']))
      $annotation['record_substatus'] = null;
    else
      $annotation['record_substatus'] = $annotation['statusCode2'];
  }

  /**
   * Retrieve existing observation details from the database for an ID supplied by
   * a call to the REST API.
   * @param string $id The taxon-observation's ID as returned by a call to the REST api.
   * @param integer $survey_id The database survey ID value to lookup within.
   * @return array Array containing occurrence and sample ID for any existing matching records.
   */
  private function find_existing_observation($id, $survey_id) {
    $thisSystemUserId = Kohana::config('rest_api_sync.user_id');
    $recordOriginHere = substr($id, 0, strlen($thisSystemUserId)) === $thisSystemUserId;
    // Look for an existing record to overwrite
    $filter = array(
      'o.deleted' => 'f',
      's.deleted' => 'f'
    );
    // @todo Do we want to overwrite existing records which originated here?
    // @todo What happens if origin here but record missing?
    if ($recordOriginHere)
      $filter['o.id'] = substr($id, strlen($thisSystemUserId));
    else {
      $filter['o.external_key'] = $id;
      $filter['s.survey_id'] = $survey_id;
    }
    $existing = $this->db->select('o.id, o.sample_id')
      ->from('occurrences o')
      ->join('samples as s', 'o.sample_id', 's.id')
      ->where($filter)->get()->result_array(false);
    return $existing;
  }

  /**
   * Retrieve existing comment details from the database for an annotation ID supplied by
   * a call to the REST API.
   * @param string $id The taxon-observation's ID as returned by a call to the REST api.
   * @param integer $occ_id The database observation ID value to lookup within.
   * @return array Array containing occurrence comment ID for any existing matching records.
   */
  function find_existing_annotation($id, $occ_id) {
    // @todo Add external key to comments table? OR do we use the timestamp?
    $userId = Kohana::config('rest_api_sync.user_id');
    $recordOriginHere = substr($id, 0, strlen($userId)) === $userId;
    // Look for an existing record to overwrite
    $filter = array(
      'oc.deleted' => 'f',
      'occurrence_id' => $occ_id
    );
    // @todo What happens if origin here but record missing?
    if ($recordOriginHere)
      $filter['oc.id'] = substr($id, strlen($userId)-1);
    else
      $filter['oc.external_key'] = $id;
    $existing = $this->db->select('oc.id')
      ->from('occurrence_comments oc')
      ->join('cache_occurrences as o', 'o.id', 'oc.occurrence_id')
      ->where($filter)->get()->result_array(false);
    return $existing;
  }

  /**
   * Returns a formatted decimal latitude and longitude string
   * @param float $lat
   * @param float $long
   * @return string Formatted lat long.
   */
  private function format_lat_long($lat, $long) {
    $ns = $lat >= 0 ? 'N' : 'S';
    $ew = $long >= 0 ? 'E' : 'W';
    $lat = abs($lat);
    $long = abs($long);
    return "$lat$ns $long$ew";
  }
  
  /**
   * Returns a formatted decimal east and north string
   * @param $east
   * @param $north
   * @return string
   */
  private function format_east_north($east, $north) {
    return "$east, $north";
  }
  
  /**
   * Retrieves the location_id for the locations records associated with an incoming observation. 
   * The observation must have a SiteKey specified which will be used to lookup a location linked
   * to the server's website ID. If it does not exist, then it will be created using the observation's
   * spatial reference as a centroid.
   * @param array $server The server configuration array which must contain it's website_id.
   * @param array $observation The observation data array.
   * @return integer The ID of the location record in the database.
   */
  function get_location_id($server, $observation) {
    $existing = $this->db->select('l.id')
      ->from('locations as l')
      ->join('locations_websites as lw', 'lw.location_id', 'l.id')
      ->where(array(
        'l.deleted' => 'f',
        'lw.deleted' => 'f',
        'lw.website_id' => $server['website_id'],
        'l.code' => $observation['SiteKey']
      ))->get()->result_array(false);
    if (count($existing)) 
      return $existing[0]['id'];
    else {
      return $this->create_location($server, $observation);
    }
  }
  
  /**
   * Creates a location in the database from the information supplied in an observation. The 
   * observation should have a SiteKey specified so that future observations for the same SiteKey
   * can be linked to the same location.
   * @param array $server The server configuration array which must contain it's website_id.
   * @param array $observation The observation data array.
   * @return integer The ID of the location record created in the database.
   * @todo Join the location to the server's associated website
   */
  function create_location($server, $observation) {
    $location = ORM::factory('location');
    $values = array(
      'location:code' => $observation['siteKey'],
      'location:name' => $observation['siteName']
    );
    $this->set_sref_data($values, $observation, 'location:centroid_sref');
    $location->set_submission_data($values);
    $location->submit();
    // @todo Error handling on submission.
    return $location->id;
  }
  
  /**
   * Uses the data in an observation to set the spatial reference information in a values array
   * before it can be submitted via ORM to the database.
   * @param array $values The values array to add the spatial reference information to.
   * @param array $observation The observation data array.
   * @param string $fieldname The name of the spatial reference field to be set in the values array, e.g. sample:entered_sref.
   */
  function set_sref_data(&$values, $observation, $fieldname) {
    if ($observation['Projection']==='OSGB' || $observation['Projection']==='OSI') {
      $values[$fieldname] = strtoupper(str_replace(' ', '', $observation['gridReference']));
      $values[$fieldname . '_system'] = $observation['Projection']==='OSGB' ? 'OSGB' : 'OSIE';
    }
    elseif ($observation['Projection']==='WGS84') {
      $values[$fieldname] = $this->format_lat_long($observation['north'], $observation['east']);
      $values[$fieldname . '_system'] = 4326;
    }
    elseif ($observation['Projection']==='OSGB36') {
      $values[$fieldname] = $this->format_east_north($observation['east'], $observation['north']);
      $values[$fieldname . '_system'] = 27700;
    }
  }

  private function get_survey_id($server, $project) {
    var_export($project);
    $projects = $this->db->select('id')
      ->from('surveys')
      ->where(array(
        'website_id' => $server['website_id'],
        'title' => "$project[id]:$project[title]",
        'deleted' => 'f'
      ))->get()->result_array(false);
    if (count($projects))
      return $projects[0]['id'];
    else {
      // Survey dataset does not exist yet so create it
      $values = array(
        'survey:title' => "$project[id]:$project[title]",
        'survey:description' => "$project[id]:$project[description]",
        'survey:website_id' => $server['website_id']
      );
      $survey = ORM::factory('survey');
      $survey->set_submission_data($values);
      $survey->submit();
      // @todo Error handling on submission.
      return $survey->id;
    }
  }

  /**
   * Simple utility function to return a value from an array, or null if not present.
   * @param $array
   * @param $key
   * @return mixed
   */
  function valueOrNull($array, $key) {
    return isset($array[$key]) ? $array[$key] : null;
  }

}