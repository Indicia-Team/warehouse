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

define('MAX_RECORDS_TO_PROCESS', 2000);

/**
 * Controller class to provide an endpoint for initiating the synchronisation of
 * two warehouses via the REST API.
 */
class Rest_Api_Sync_Controller extends Controller {

  /**
   * @var Database_Core Kohana database object
   */
  private $db;

  /**
   * @var string ISO datetime that the sync was last run. Used to filter requests for
   * records to only get changes.
   */
  private $from_date_time;

  /**
   * @var string When a sync run only manages to do part of the job (too many records to process)
   * this defines the limit of the completely processed edit date range.
   */
  private $processing_date_limit;

  /**
   * @var string Current processing state, used to track initial setup. Can be 'T'
   * for initial loading of taxon observations
   */
  private $state;

  /**
   * Main controller method for the rest_api_sync module. Initiates a synchronisation.
   */
  public function index() {
    kohana::log('debug', 'Initiating REST API Sync');
    $this->db = Database::instance();
    rest_api_sync::$client_user_id = Kohana::config('rest_api_sync.user_id');
    $servers = Kohana::config('rest_api_sync.servers');
    echo "<h1>REST API Sync</h1>";
    foreach ($servers as $server_id => $server) {
      echo "<h2>$server_id</h2>";
      $next_page_of_projects_url = rest_api_sync::get_server_projects_url($server['url']);
      while ($next_page_of_projects_url) {
        $response = rest_api_sync::get_server_projects($next_page_of_projects_url, $server_id);
        if (!isset($response['data'])) {
          $this->log('error', "Invalid response\nURL: $next_page_of_projects_url\nResponse did not include data element.");
          var_export($response);
          continue;
        }
        $projects = $response['data'];
        foreach ($projects as $project) {
          $this->from_date_time = variable::get("rest_api_sync_$project[id]_last_run", '1600-01-01', false);
          // add a second on, since we processed all the records from the last second
          $this->from_date_time = date("Y-m-d\TH:i:s", strtotime($this->from_date_time . ' +1 second'));
          $this->processing_date_limit = date("Y-m-d\TH:i:s");
          $survey_id = $this->get_survey_id($server, $project);
          $this->sync_from_project($server, $server_id, $project, $survey_id);
          // If we only managed a partial run, set the date limit of the record range we processed for next
          // time. Otherwise set the time we started processing so nothing is missed.
          variable::set("rest_api_sync_$project[id]_last_run", $this->processing_date_limit);
        }
        $next_page_of_projects_url = isset($response['paging']['next']) ? $response['paging']['next'] : false;
      }
    }
  }

  /**
   * Synchronises the taxon-observation and annotation data.
   *
   * @param array $server Configuration data for the server being called.
   * @param $server_id string Unique identifier for the server.
   * @param array $project Project resource obtained from the server's REST API.
   * @param integer $survey_id Database ID of the survey being imported into.
   */
  private function sync_from_project($server, $server_id, $project, $survey_id) {
    echo "<h3>$project[id]</h3>";
    $state = variable::get("rest_api_sync_$project[id]_state", 'load-taxon-observations');
    // unless the config forces a specific resource to load, we must initially load observations
    // before annotations otherwise we get annotations where the obs has not yet loaded.
    if ($state!=='load-annotations' || (isset($server['resources']) && count($server['resources'])===1)) {
      $done = self::sync_taxon_observations($server, $server_id, $project, $survey_id, $state==='load-done');
      if ($state === 'load-taxon-observations' && $done) {
        // progress to initial load of annotations
        $state = 'load-annotations';
        $this->processing_date_limit = '1600-01-01';
      }
    }
    if ($state!=='load-taxon-observations' || (isset($server['resources']) && count($server['resources'])===1)) {
      $done = self::sync_annotations($server, $server_id, $project, $survey_id, $state==='load-done');
      if ($state === 'load-annotations' && $done) {
        // initial loading done
        $state = 'load-done';
        // @todo -1 week is arbitrary. Should really use the time that we switched out of the
        // loading-taxon-observations state.
        $this->processing_date_limit = date("Y-m-d\TH:i:s -1 week");
      }

    }
    variable::set("rest_api_sync_$project[id]_state", $state);
  }

  /**
   * Synchronises the taxon-observation data.
   *
   * @param array $server Configuration data for the server being called.
   * @param $server_id string Unique identifier for the server.
   * @param array $project Project resource obtained from the server's REST API.
   * @param integer $survey_id Database ID of the survey being imported into.
   * @param boolean $load_all True if all data should be loaded, false if being throttled
   * during initial sync.
   * @return boolean True if completed all observations, false if didn't finish
   */
  private function sync_taxon_observations($server, $server_id, $project, $survey_id, $load_all) {
    // abort if not allowed to access this resource by config
    if (isset($server['resources']) && !in_array('taxon-observations', $server['resources']))
      return;
    $dataset_name_attr_id = Kohana::config('rest_api_sync.dataset_name_attr_id');
    $user_id = Kohana::config('rest_api_sync.user_id');
    $taxon_list_id = Kohana::config('rest_api_sync.taxon_list_id');
    $next_page_of_taxon_observations_url = rest_api_sync::get_server_taxon_observations_url(
        $server['url'], $project['id'], $this->from_date_time, $this->processing_date_limit);
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
    $processedCount = 0;
    $last_completely_processed_date = null;
    $last_record_date = null;
    while ($next_page_of_taxon_observations_url && ($load_all || $processedCount < MAX_RECORDS_TO_PROCESS)) {
      $data = rest_api_sync::get_server_taxon_observations($next_page_of_taxon_observations_url, $server_id);
      $observations = $data['data'];
      $this->log('debug', count($observations) . ' records found');
      foreach ($observations as $observation) {
        // If the record was originated from a different system, the specified dataset name
        // needs to be stored
        if ($dataset_name_attr_id && substr($observation['id'], 0, strlen($user_id)) !== $user_id) {
          $observation["smpAttr:$dataset_name_attr_id"] = $observation['datasetName'];
          unset($observation['datasetName']);
        }
        try {
          $is_new = api_persist::taxon_observation($this->db, $observation, $server['website_id'], $survey_id, $taxon_list_id);
          $tracker[$is_new ? 'inserts' : 'updates']++;
        }
        catch (exception $e) {
          $this->log('error', "Error occurred submitting an occurrence\n" . $e->getMessage() . "\n" .
              json_encode($observation), $tracker);
        }
        if ($last_record_date && $last_record_date <> $observation['lastEditDate'])
          $last_completely_processed_date = $last_record_date;
        $last_record_date = $observation['lastEditDate'];
        $processedCount++;
      }
      $next_page_of_taxon_observations_url = isset($data['paging']['next']) ? $data['paging']['next'] : false;
    }
    echo "<strong>Observations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]<br/>";
    if (!$load_all && $processedCount >= MAX_RECORDS_TO_PROCESS)
      $this->processing_date_limit = $last_completely_processed_date;
    return $load_all || $processedCount < MAX_RECORDS_TO_PROCESS;
  }

  /**
   * Synchronises the annotation data.
   *
   * @param array $server Configuration data for the server being called.
   * @param $server_id string Unique identifier for the server.
   * @param array $project Project resource obtained from the server's REST API.
   * @param integer $survey_id Database ID of the survey being imported into.
   * @param boolean $load_all True if all data should be loaded, false if being throttled
   * during initial sync.
   * @return boolean True if completed all observations, false if didn't finish
   */
  private function sync_annotations($server, $server_id, $project, $survey_id, $load_all) {
    // abort if not allowed to access this resource by config
    if (isset($server['resources']) && !in_array('annotations', $server['resources']))
      return;
    $nextPageOfAnnotationsUrl = rest_api_sync::get_server_annotations_url(
        $server['url'], $project['id'], $this->from_date_time, $this->processing_date_limit);
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
    $processedCount = 0;
    $last_completely_processed_date = null;
    $last_record_date = null;
    while ($nextPageOfAnnotationsUrl && ($load_all || $processedCount < MAX_RECORDS_TO_PROCESS)) {
      $data = rest_api_sync::get_server_annotations($nextPageOfAnnotationsUrl, $server_id);
      $annotations = $data['data'];
      $this->log('debug', count($annotations) . ' records found');
      foreach ($annotations as $annotation) {
        try {
          $is_new = api_persist::annotation($this->db, $annotation, $survey_id);
          $tracker[$is_new ? 'inserts' : 'updates']++;
        }
        catch (exception $e) {
          $this->log('error', "Error occurred submitting an annotation\n" . $e->getMessage() . "\n" .
            json_encode($annotation), $tracker);
        }
        if ($last_record_date && $last_record_date <> $annotation['lastEditDate'])
          $last_completely_processed_date = $last_record_date;
        $last_record_date = $annotation['lastEditDate'];
        $processedCount++;
      }
      $nextPageOfAnnotationsUrl = isset($data['paging']['next']) ? $data['paging']['next'] : false;
    }
    echo "<strong>Annotations</strong><br/><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]<br/>";
    if (!$load_all && $processedCount >= MAX_RECORDS_TO_PROCESS)
      $this->processing_date_limit = $last_completely_processed_date;
    return $load_all || $processedCount < MAX_RECORDS_TO_PROCESS;
  }

  /**
   * Retrieves the database survey_id to use when storing the data obtained for a
   * given project resource. The survey record is looked up using the project's ID
   * and title and if not found, is created automatically.
   *
   * @param array $server The server configuration array
   * @param array $project A project resource obtained from a REST API.
   * @return integer Survey_id.
   */
  private function get_survey_id($server, $project) {
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
   * Logs a message.
   *
   * The message is displayed on the screen and to the Kohana error log using the
   * supplied status as the error level. If a tracker array is supplied and the
   * status indicates an error, $tracker['errors'] is incremented.
   * @param string $status Message status, either error or debug.
   * @param string $msg Message to log.
   * @param array $tracker Array tracking count of inserts, updates and errors.
   */
  private function log($status, $msg, &$tracker=null) {
    kohana::log($status, "REST API Sync: $msg");
    if ($status==='error') {
      $msg = "ERROR: $msg";
      if ($tracker)
        $tracker['errors']++;
    }
    echo str_replace("\n", '<br/>', $msg) . '<br/>';
  }

}