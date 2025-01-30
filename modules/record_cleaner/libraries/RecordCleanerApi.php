<?php

/**
 * @file
 * Helper class for interactions with the Record Cleaner API.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

const BATCH_SIZE = 100;
const PAGE_LIMIT = 10;

/**
 * Functionality for the Record Cleaner API interactions.
 */
class RecordCleanerApi {

  /**
   * Database connection.
   *
   * @var Database
   */
  private $db;

  /**
   * Configuration settings.
   *
   * @var array
   */
  private $config;

  /**
   * Authentication token.
   *
   * @var string
   */
  private $authToken;

  public function __construct($db) {
    $this->db = $db;
    $this->config = kohana::config('record_cleaner');
  }

  public function processRecords($endtime) {
    // Process records here.
    if (!$this->authenticate()) {
      // Abort if authentication fails.
      return;
    }
    $this->processAll();
    $this->updateOccurrenceMetadata($this->db, $endtime);
  }

  private function authenticate() {
    $response = $this->curlRequest('/token', [], [
      'grant_type' => 'password',
      'username' => $this->config['api']['username'],
      'password' => $this->config['api']['password'],
      'scope' => '',
      'client_id' => '',
      'client_secret' => '',
    ]);
    if (empty($response->access_token)) {
      kohana::log('error', 'Authentication on Record Cleaner API failed');
      return FALSE;
    }
    $this->authToken = $response->access_token;
    return TRUE;
  }

  private function processAll() {
    $query = <<<SQL
      select id
      from occdelta
      where record_status not in ('I','V','R','D')
      order by id
    SQL;
    $result = $this->db->query($query)->result_array(TRUE);
    $ids = array_column($result, 'id');
    $pages = ceil(count($ids) / BATCH_SIZE);
    for ($page = 0; $page < $pages; $page++) {
      $start = $page * BATCH_SIZE;
      $end = min(($page + 1) * BATCH_SIZE, count($ids));
      $batch = array_slice($ids, $start, $end);
      $this->processBatch($batch);
    }
  }

  private function processBatch(array $batch) {
    // @todo Should output_sref be the blurred or precise version?
    // @todo Correct date format?
    $ids = implode(',', $batch);
    $query = <<<SQL
      select o.id, o.taxa_taxon_list_external_key as tvk, o.date_start, o.date_end, o.date_type,
        onf.attr_stage as stage, onf.output_sref, o.date_start
      from cache_occurrences_functional o
      join cache_occurrences_nonfunctional onf on onf.id=o.id
      where o.id in ($ids)
    SQL;

    $records = $this->db->query($query, [$batch])->result();
    $recordsArray = [];
    foreach ($records as $record) {
      $recordsArray[] = [
        'id' => $record->id,
        'tvk' => $record->tvk,
        'date' => $record->date_start,
        'sref' => [
          'srid' => 27700,
          'gridref' => $record->output_sref,
        ],
        'stage' => $record->stage
      ];
    }
    echo '<pre>';
    var_export($this->curlRequest('/verify', [], json_encode(['records' => $recordsArray])));
    echo '</pre>';
  }


  private function curlRequest($endpoint, array $query = [], mixed $post = []) {
    $url = $this->config['api']['url'] . $endpoint;
    if (!empty($query)) {
      $url .= '?' . http_build_query($query);
    }
    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    if (!empty($post)) {
      curl_setopt ($session, CURLOPT_POST, TRUE);
      echo '<pre>' . var_export($post); echo '</pre>';
      curl_setopt ($session, CURLOPT_POSTFIELDS, $post);
    }
    // @todo Why does attaching content-type on token request break it?

    // Attach authentication token.
    if (isset($this->authToken)) {
      curl_setopt($session, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $this->authToken,
        'Content-Type: application/json',
      ]);
    }

    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
      kohana::log('error', 'Record Cleaner API error: ' . $response);
    }
    curl_close($session);
    return json_decode($response);
  }

  /**
   * Update the metadata associated occurrences so we know rules have been run.
   *
   * @param object $db
   *   Kohana database instance.
   * @param string $endtime
   *   Timestamp to save as the last verification check date.
   */
  function updateOccurrenceMetadata($db, $endtime) {
    // Note we use the information from the point when we started the process,
    // in caseany changes have happened in the meanwhile which might otherwise be
    // missed.
    $query = <<<SQL
      update occurrences o
      set last_verification_check_date='$endtime'
      from occdelta
      where occdelta.id=o.id and occdelta.record_status not in ('I','V','R','D')
    SQL;
    $db->query($query);
  }

}
