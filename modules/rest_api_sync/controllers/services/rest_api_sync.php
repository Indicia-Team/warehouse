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

  /**
   * Main controller method for the rest_api_sync module. Initiates a synchronisation.
   */
  public function index() {
    $this->db = Database::instance();
    rest_api_sync::$client_user_id = Kohana::config('rest_api_sync.user_id');
    $servers = Kohana::config('rest_api_sync.servers');
    echo "<h1>REST API Sync</h1>";
    foreach ($servers as $serverId => $server) {
      echo "<h2>$serverId</h2>";
      $nextPageOfProjectsUrl = rest_api_sync::get_server_projects_url($server['url']);
      echo "$nextPageOfProjectsUrl<br/>";
      while ($nextPageOfProjectsUrl) {
        $data = rest_api_sync::get_server_projects($nextPageOfProjectsUrl, $serverId);
        $projects = $data['data'];
        foreach ($projects as $project) {
          $survey_id = $this->get_survey_id($server, $project);
          $this->sync_from_project($server, $serverId, $project, $survey_id);
        }
        $nextPageOfProjectsUrl = isset($data['paging']['next']) ? $data['paging']['next'] : false;
      }
      /*
       // TEMPORARY TEST CODE
      $project = array('id'=>'BT', 'Title'=>'Sync from BTO');
      $survey_id = $this->get_survey_id($server, $project);
      $this->sync_from_project($server, $serverId, $project, $survey_id);
      */

    }
  }

  private function sync_from_project($server, $serverId, $project, $survey_id) {
    // @todo Last Sync date handling
    echo "<h3>$project[id]</h3>";
    self::sync_taxon_observations($server, $serverId, $project, $survey_id);
    self::sync_annotations($server, $serverId, $project, $survey_id);
  }
  
  private function sync_taxon_observations($server, $serverId, $project, $survey_id) {
    $datasetNameAttrId = Kohana::config('rest_api_sync.dataset_name_attr_id');
    $userId = Kohana::config('rest_api_sync.user_id');
    // @todo Proper handling of the last sync date
    $fromDate = new DateTime('2 months ago');
    $taxon_list_id = Kohana::config('rest_api_sync.taxon_list_id');
    $nextPageOfTaxonObservationsUrl = rest_api_sync::get_server_taxon_observations_url(
        $server['url'], $project['id'], $fromDate->format('Y-m-d'));
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
    while ($nextPageOfTaxonObservationsUrl) {
      $data = rest_api_sync::get_server_taxon_observations($nextPageOfTaxonObservationsUrl, $serverId);
      $observations = $data['data'];
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
          kohana::log('debug', "REST API Sync could not find taxon for $observation[TaxonVersionKey]");
          echo "Could not find taxon for $observation[TaxonVersionKey]<br/>";
        } 
        else {
          // Find if the record originated here or elsewhere
          $recordOriginHere = substr($observation['id'], 0, strlen($userId)) === $userId;
          // @todo Reuse the last sample if it matches
          $values = array(
            'website_id' => $server['website_id'],
            'sample:survey_id' => $survey_id,
            'sample:date_start'     => $observation['StartDate'],
            'sample:date_end'       => $observation['EndDate'],
            'sample:date_type'      => $observation['DateType'],
            'sample:recorder_names' => isset($observation['Recorder']) ? $observation['Recorder'] : 'Unknown',
            'occurrence:taxa_taxon_list_id' => $taxa[0]['id'],
            'occurrence:external_key' => $observation['id'],
            'occurrence:zero_abundance' => $observation['ZeroAbundance'],
            'occurrence:sensitivity_precision' => $observation['Sensitive']==='T' ? 10000 : null,
            'occurrence:record_status' => 'C'
          );
          // If the record was originated from a different system, the specified dataset name
          // needs to be stored
          if ($datasetNameAttrId && !$recordOriginHere)
            $values["smpAttr:$datasetNameAttrId"] = $observation['DatasetName'];

          // Set the spatial reference depending on the projection information supplied.
          $this->set_sref_data($values, $observation, 'sample:entered_sref');
          // @todo Should the precision field be stored in a custom attribute?
          // Site handling. If a known site with a SiteKey, we can create a record in locations, otherwise use the 
          // free text location_name field.
          if (!empty($observation['SiteKey'])) {
            $values['sample:location_id'] = $this->get_location_id($server, $observation);
          }
          elseif (!empty($observation['SiteName'])) {
            $values['sample:location_name'] = $observation['SiteName'];
          }
          $existing = $this->find_existing_observation($observation['id'], $survey_id);
          if (count($existing)) {
            $values['occurrence:id'] = $existing[0]['id'];
            $values['sample:id'] = $existing[0]['sample_id'];
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
    }
    echo "<strong>Observations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]<br/>";
  }
  
  private function sync_annotations($server, $serverId, $project, $survey_id) {
    $fromDate = new DateTime('2 months ago');
    $nextPageOfAnnotationsUrl = rest_api_sync::get_server_annotations_url(
        $server['url'], $project['id'], $fromDate->format('Y-m-d'));
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
    while ($nextPageOfAnnotationsUrl) {
      $data = rest_api_sync::get_server_annotations($nextPageOfAnnotationsUrl, $serverId);
      $annotations = $data['data'];
      foreach ($annotations as $annotation) {
        $this->map_record_status($annotation);
        // set up a values array for the annotation post
        $values = array(
          'occurrence_comment:comment' => $annotation['Comment'],
          'occurrence_comment:record_status' => $annotation['StatusCode1'],
          'occurrence_comment:record_substatus' => $annotation['StatusCode2'],
          'occurrence_comment:email_address' => $annotation['EmailAddress'],
          // @todo Other fields

        );
        // @todo What to do about TaxonVersionKey? Create a determination record for audit trail?
        // link to the existing observation
        $existingObs = $this->find_existing_observation($annotation['TaxonObservation']['id'], $survey_id);
        if (count($existingObs)) {
          $values['taxon_occurrence_id'] = $existingObs[0]['id'];
        } else {
          // link to existing annotation if appropriate
          $existing = $this->find_existing_annotation($annotation['id'], $survey_id);
          if ($existing)
            $values['occurrence_comment:id'] = $existing[0]['id'];

          // submit the annotation
          // if the most recent annotation for a record, update it's verification status
          // @todo Proper error handling as annotation can't be imported. Perhaps should obtain
          // and import the observation via the API?
          echo "Attempt to import annotation $annotation[id] but taxon observation not found";
        }
      }
      $nextPageOfAnnotationsUrl = isset($data['paging']['next']) ? $data['paging']['next'] : false;
    }
    echo "<strong>Annotations</strong><br/><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]<br/>";
  }

  /**
   * Converts the record status codes in an annotation into Indicia codes.
   * @param $annotation
   */
  private function map_record_status(&$annotation) {
    if (empty($annotation['StatusCode1']))
      $annotation['record_status'] = null;
    else {
      switch ($annotation['StatusCode1']) {
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
    if (empty($annotation['StatusCode2']))
      $annotation['record_substatus'] = null;
    else
      $annotation['record_substatus'] = $annotation['StatusCode2'];
  }

  /**
   * Retrieve existing observation details from the database for an ID supplied by
   * a call to the REST API.
   * @param string $id The taxon-observation's ID as returned by a call to the REST api.
   * @param integer $survey_id The database survey ID value to lookup within.
   * @return array Array containing occurrence and sample ID for any existing matching records.
   */
  private function find_existing_observation($id, $survey_id) {
    $userId = Kohana::config('rest_api_sync.user_id');
    $recordOriginHere = substr($id, 0, strlen($userId)) === $userId;
    // Look for an existing record to overwrite
    $filter = array(
      'o.deleted' => 'f',
      's.deleted' => 'f',
      's.survey_id' => $survey_id
    );
    // @todo Do we want to overwrite existing records which originated here?
    // @todo What happens if origin here but record missing?
    if ($recordOriginHere)
      $filter['o.id'] = substr($id, strlen($userId)-1);
    else
      $filter['o.external_key'] = $id;
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
   * @param integer $survey_id The database survey ID value to lookup within.
   * @return array Array containing occurrence comment ID for any existing matching records.
   */
  function find_existing_annotation($id, $survey_id) {
    // @todo Add external key to comments table? OR do we use the timestamp?
    $userId = Kohana::config('rest_api_sync.user_id');
    $recordOriginHere = substr($id, 0, strlen($userId)) === $userId;
    // Look for an existing record to overwrite
    $filter = array(
      'oc.deleted' => 'f',
      'o.survey_id' => $survey_id
    );
    // @todo What happens if origin here but record missing?
    if ($recordOriginHere)
      $filter['oc.id'] = substr($id, strlen($userId)-1);
    else
      $filter['oc.external_key'] = $id;
    $existing = $this->db->select('oc.id')
      ->from('occurrences oc')
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
   */
  function create_location($server, $observation) {
    $location = ORM::factory('location');
    $values = array(
      'location:code' => $observation['SiteKey'],
      'location:name' => $observation['SiteName']
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
      $values[$fieldname] = strtoupper(str_replace(' ', '', $observation['GridReference']));
      $values[$fieldname . '_system'] = $observation['Projection']==='OSGB' ? 'OSGB' : 'OSIE';
    }
    elseif ($observation['Projection']==='WGS84') {
      $values[$fieldname] = $this->format_lat_long($observation['North'], $observation['East']);
      $values[$fieldname . '_system'] = 4326;
    }
    elseif ($observation['Projection']==='OSGB36') {
      $values[$fieldname] = $this->format_east_north($observation['East'], $observation['North']);
      $values[$fieldname . '_system'] = 27700;
    }
  }

  private function get_survey_id($server, $project) {
    $projects = $this->db->select('id')
      ->from('surveys')
      ->where(array(
        'website_id' => $server['website_id'],
        'title' => "$project[id]:$project[Title]",
        'deleted' => 'f'
      ))->get()->result_array(false);
    if (count($projects))
      return $projects[0]['id'];
    else {
      // Survey dataset does not exist yet so create it
      $values = array(
        'survey:title' => "$project[id]:$project[Title]",
        'survey:description' => "$project[id]:$project[Description]",
        'survey:website_id' => $server['website_id']
      );
      $survey = ORM::factory('survey');
      $survey->set_submission_data($values);
      $survey->submit();
      // @todo Error handling on submission.
      return $survey->id;
    }
  }

}