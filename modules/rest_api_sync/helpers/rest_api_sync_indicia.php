<?php

/**
 * @file
 * Helper class for synchronising records from an Indicia server.
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

 defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class for syncing to the RESTful API on an Indicia warehouses.
 */
class rest_api_sync_indicia {

  /**
   * ISO datetime that the sync was last run.
   *
   * Used to filter requests for records to only get changes.
   *
   * @var string
   */
  private static $fromDateTime;

  /**
   * Processing state.
   *
   * Current processing state, used to track initial setup.
   *
   * @var string
   */
  private $state;

  /**
   * Date up to which processing has been performed.
   *
   * When a sync run only manages to do part of the job (too many records to
   * process) this defines the limit of the completely processed edit date
   * range.
   *
   * @var string
   */
  private static $processingDateLimit;

  private static $db;

  /**
   * Synchronise a set of data loaded from the Indicia warehouse server.
   *
   * @param string $serverId
   *   ID of the server as defined in the configuration.
   * @param array $server
   *   Server configuration.
   */
  public static function syncServer($serverId, $server) {
    self::$db = Database::instance();
    $next_page_of_projects_url = self::getServerProjectsUrl($server['url']);
    while ($next_page_of_projects_url) {
      $response = self::getServerProjects($next_page_of_projects_url, $serverId);
      if (!isset($response['data'])) {
        rest_api_sync::log('error', "Invalid response\nURL: $next_page_of_projects_url\nResponse did not include data element.");
        var_export($response);
        continue;
      }
      $projects = $response['data'];
      foreach ($projects as $project) {
        self::$fromDateTime = variable::get("rest_api_sync_$project[id]_last_run", '1600-01-01', FALSE);
        // Add a second on, since we processed all the records from the last
        // second.
        self::$fromDateTime = date("Y-m-d\TH:i:s", strtotime(self::$fromDateTime . ' +1 second'));
        self::$processingDateLimit = date("Y-m-d\TH:i:s");
        $survey_id = self::getSurveyId($server, $project);
        self::syncFromProject($server, $serverId, $project, $survey_id);
        // If we only managed a partial run, set the date limit of the record
        // range we processed for next time. Otherwise set the time we
        // started processing so nothing is missed.
        variable::set("rest_api_sync_$project[id]_last_run", self::$processingDateLimit);
      }
      $next_page_of_projects_url = isset($response['paging']['next']) ? $response['paging']['next'] : FALSE;
    }
  }

  /**
   * Currently just a stub function to treat the whole Indicia sync as a page.
   *
   * Enables use from the UI.
   */
  public static function syncPage($serverId, $server) {
    self::syncServer($serverId, $server);
    return [
      'moreToDo' => FALSE,
      'pageCount' => 1,
      'recordCount' => -1,
    ];
  }

  /**
   * Retrieve the database survey_id to use for synced records.
   *
   * Retrieves the database survey_id to use when storing the data obtained for
   * a given project resource. The survey record is looked up using the
   * project's ID and title and if not found, is created automatically.
   *
   * @param array $server
   *   The server configuration array.
   * @param array $project
   *   A project resource obtained from a REST API.
   *
   * @return int
   *   The survey_id.
   */
  private static function getSurveyId(array $server, array $project) {
    $projects = self::$db->select('id')
      ->from('surveys')
      ->where(array(
        'website_id' => $server['website_id'],
        'title' => "$project[id]:$project[title]",
        'deleted' => 'f',
      ))->get()->result_array(FALSE);
    if (count($projects)) {
      return $projects[0]['id'];
    }
    else {
      // Survey dataset does not exist yet so create it.
      $values = array(
        'survey:title' => "$project[id]:$project[title]",
        'survey:description' => "$project[id]:$project[description]",
        'survey:website_id' => $server['website_id'],
      );
      $survey = ORM::factory('survey');
      $survey->set_submission_data($values);
      $survey->submit();
      // @todo Error handling on submission.
      return $survey->id;
    }
  }

  /**
   * Synchronises the taxon-observation and annotation data.
   *
   * @param array $server
   *   Configuration data for the server being called.
   * @param string $serverId
   *   Unique identifier for the server.
   * @param array $project
   *   Project resource obtained from the server's REST API.
   * @param int $survey_id
   *   Database ID of the survey being imported into.
   */
  private static function syncFromProject(array $server, $serverId, array $project, $survey_id) {
    $state = variable::get("rest_api_sync_$project[id]_state", 'load-taxon-observations');
    // Unless the config forces a specific resource to load, we must
    // initially load observations before annotations otherwise we get
    // annotations where the obs has not yet loaded.
    if ($state !== 'load-annotations' || (isset($server['resources']) && count($server['resources']) === 1)) {
      $done = self::syncTaxonObservations($server, $serverId, $project, $survey_id, $state === 'load-done');
      if ($state === 'load-taxon-observations' && $done) {
        // Progress to initial load of annotations.
        $state = 'load-annotations';
        self::$processingDateLimit = '1600-01-01';
      }
    }
    if ($state !== 'load-taxon-observations' || (isset($server['resources']) && count($server['resources']) === 1)) {
      $done = self::syncAnnotations($server, $serverId, $project, $survey_id, $state === 'load-done');
      if ($state === 'load-annotations' && $done) {
        // Initial loading done.
        $state = 'load-done';
        // @todo -1 week is arbitrary. Should really use the time that we switched out of the
        // loading-taxon-observations state.
        self::$processingDateLimit = date("Y-m-d\TH:i:s -1 week");
      }
    }
    variable::set("rest_api_sync_$project[id]_state", $state);
  }

  /**
   * Synchronises the taxon-observation data.
   *
   * @param array $server
   *   Configuration data for the server being called.
   * @param string $serverId
   *   Unique identifier for the server.
   * @param array $project
   *   Project resource obtained from the server's REST API.
   * @param int $survey_id
   *   Database ID of the survey being imported into.
   * @param bool $load_all
   *   True if all data should be loaded, FALSE if being throttled during
   *   initial sync.
   *
   * @return bool
   *   True if completed all observations, FALSE if didn't finish
   */
  private static function syncTaxonObservations(array $server, $serverId, array $project, $survey_id, $load_all) {
    // Abort if not allowed to access this resource by config.
    if (isset($server['resources']) && !in_array('taxon-observations', $server['resources'])) {
      return TRUE;
    }
    $dataset_name_attr_id = Kohana::config('rest_api_sync.dataset_name_attr_id');
    $user_id = Kohana::config('rest_api_sync.user_id');
    $taxon_list_id = Kohana::config('rest_api_sync.taxon_list_id');
    $next_page_of_taxon_observations_url = self::getServerUrl(
      'taxon-observations',
      $server,
      $project['id'],
      self::$fromDateTime,
      self::$processingDateLimit
    );
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
    $processedCount = 0;
    $last_completely_processed_date = NULL;
    $last_record_date = NULL;
    while ($next_page_of_taxon_observations_url && ($load_all || $processedCount < MAX_RECORDS_TO_PROCESS)) {
      $data = self::getServerTaxonObservations($next_page_of_taxon_observations_url, $serverId);
      $observations = $data['data'];
      rest_api_sync::log('debug', count($observations) . ' records found');
      foreach ($observations as $observation) {
        // If the record was originated from a different system, the specified
        // dataset name needs to be stored.
        if ($dataset_name_attr_id && substr($observation['id'], 0, strlen($user_id)) !== $user_id) {
          $observation["smpAttr:$dataset_name_attr_id"] = $observation['datasetName'];
          unset($observation['datasetName']);
        }
        try {
          $is_new = api_persist::taxonObservation(self::$db, $observation, $server['website_id'], $survey_id, $taxon_list_id);
          $tracker[$is_new ? 'inserts' : 'updates']++;
        }
        catch (exception $e) {
          rest_api_sync::log('error', "Error occurred submitting an occurrence\n" . $e->getMessage() . "\n" .
              json_encode($observation), $tracker);
        }
        if ($last_record_date && $last_record_date <> $observation['lastEditDate']) {
          $last_completely_processed_date = $last_record_date;
        }
        $last_record_date = $observation['lastEditDate'];
        $processedCount++;
      }
      $next_page_of_taxon_observations_url = isset($data['paging']['next']) ? $data['paging']['next'] : FALSE;
    }
    if (!$load_all && $processedCount >= MAX_RECORDS_TO_PROCESS) {
      self::$processingDateLimit = $last_completely_processed_date;
    }
    return $load_all || $processedCount < MAX_RECORDS_TO_PROCESS;
  }

  /**
   * Synchronises the annotation data.
   *
   * @param array $server
   *   Configuration data for the server being called.
   * @param string $serverId
   *   Unique identifier for the server.
   * @param array $project
   *   Project resource obtained from the server's REST API.
   * @param int $survey_id
   *   Database ID of the survey being imported into.
   * @param bool $load_all
   *   True if all data should be loaded, FALSE if being throttled
   *   during initial sync.
   *
   * @return bool
   *   True if completed all observations, FALSE if didn't finish
   */
  private static function syncAnnotations(array $server, $serverId, array $project, $survey_id, $load_all) {
    // Abort if not allowed to access this resource by config.
    if (isset($server['resources']) && !in_array('annotations', $server['resources'])) {
      return TRUE;
    }
    $nextPageOfAnnotationsUrl = self::getServerUrl(
      'annotations',
      $server,
      $project['id'],
      self::$fromDateTime,
      self::$processingDateLimit
    );
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
    $processedCount = 0;
    $last_completely_processed_date = NULL;
    $last_record_date = NULL;
    while ($nextPageOfAnnotationsUrl && ($load_all || $processedCount < MAX_RECORDS_TO_PROCESS)) {
      $data = self::getServerAnnotations($nextPageOfAnnotationsUrl, $serverId);
      $annotations = $data['data'];
      rest_api_sync::log('debug', count($annotations) . ' records found');
      foreach ($annotations as $annotation) {
        try {
          $is_new = api_persist::annotation(self::$db, $annotation, $survey_id);
          $tracker[$is_new ? 'inserts' : 'updates']++;
        }
        catch (exception $e) {
          rest_api_sync::log('error', "Error occurred submitting an annotation\n" . $e->getMessage() . "\n" .
            json_encode($annotation), $tracker);
        }
        if ($last_record_date && $last_record_date <> $annotation['lastEditDate']) {
          $last_completely_processed_date = $last_record_date;
        }
        $last_record_date = $annotation['lastEditDate'];
        $processedCount++;
      }
      $nextPageOfAnnotationsUrl = isset($data['paging']['next']) ? $data['paging']['next'] : FALSE;
    }
    if (!$load_all && $processedCount >= MAX_RECORDS_TO_PROCESS) {
      self::$processingDateLimit = $last_completely_processed_date;
    }
    return $load_all || $processedCount < MAX_RECORDS_TO_PROCESS;
  }

  private static function getServerUrl(
      $endPoint,
      array $server,
      $projectId,
      $edited_date_from,
      $edited_date_to
      ) {
    $params = [
      'proj_id' => $projectId,
      'edited_date_from' => $edited_date_from,
      'edited_date_to' => $edited_date_to,
      'page_size' => 500,
    ];
    if (!empty($server['parameters'])) {
      $params = array_merge($params, $server['parameters']);
    }
    return "$server[url]/$endPoint?" . http_build_query($params);
  }

  private static function getServerProjectsUrl($server_url) {
    return $server_url . '/projects';
  }

  public static function getServerProjects($url, $serverId) {
    return rest_api_sync::getDataFromRestUrl($url, $serverId);
  }

  public static function getServerTaxonObservations($url, $serverId) {
    return rest_api_sync::getDataFromRestUrl($url, $serverId);
  }

  public static function getServerAnnotations($url, $serverId) {
    return rest_api_sync::getDataFromRestUrl($url, $serverId);
  }

}
