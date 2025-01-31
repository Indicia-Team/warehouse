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

/*
 * @todo Handle Identification difficulty changes as no longer using verification rules locally. E.g. how to store in cache_taxon_searchterms?
 * @todo The cache table update query needs to alter applicable_verification_rule_types to include the rules run by the Record Cleaner.
 * @todo Should output_sref be the blurred or precise version in the submitted records?
 * @todo Correct date format submitted in the records?
 * @todo Check icons generated in PG reports. Are the standard icons even being generated?
 * @todo Check icons generated in ES reports
 * @todo Check filtering in PG reports
 * @todo Check filtering in ES reports
 *
 */

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

  /**
   * Initialise the object instance.
   *
   * @param Database $db
   *   Database connection.
   */
  public function __construct($db) {
    $this->db = $db;
    $this->config = kohana::config('record_cleaner');
  }

  /**
   * Main entry point which processes all outstanding records.
   *
   * @param string $endtime
   */
  public function processRecords($endtime) {
    // Process records here.
    if (!$this->authenticate()) {
      // Abort if authentication fails.
      return;
    }
    $this->processAll($endtime);
  }

  /**
   * Obtain an API authentication token.
   *
   * @return bool
   *   True if authentication successful, false otherwise.
   */
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

  private function processAll($endtime) {
    $query = <<<SQL
      SELECT id
      FROM occdelta
      WHERE record_status not in ('I','V','R','D')
      AND verification_checks_enabled=true
      ORDER BY id
    SQL;
    $result = $this->db->query($query)->result_array(TRUE);
    $ids = array_column($result, 'id');
    $pages = ceil(count($ids) / BATCH_SIZE);
    for ($page = 0; $page < $pages; $page++) {
      $start = $page * BATCH_SIZE;
      $end = min(($page + 1) * BATCH_SIZE, count($ids));
      $batch = array_slice($ids, $start, $end);
      $this->cleanoutOldMessages($batch);
      $this->processBatch($batch);
      $this->updateOccurrenceMetadata($batch, $endtime);
      $this->updateCacheTables($batch);
    }
  }


  /**
   * Cleans out old messages for the given batch of occurrence IDs.
   *
   * @param array $batch
   *   Array of occurrence IDs to clean messages for.
   */
  private function cleanoutOldMessages(array $batch) {
    $ids = implode(',', $batch);
    $query = <<<SQL
      UPDATE occurrence_comments
      SET deleted=true, updated_on=now()
      WHERE occurrence_id IN ($ids)
      AND (
        generated_by IN (
          'data_cleaner_identification_difficulty',
          'data_cleaner_period',
          'data_cleaner_period_within_year',
          'data_cleaner_without_polygon'
        )
        OR generated_by LIKE 'record_cleaner:%'
      );
    SQL;
    $this->db->query($query);
  }

  private function processBatch(array $batch) {
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
    $response = $this->curlRequest('/verify', [], json_encode(['records' => $recordsArray]));
    echo '<pre>'; var_export($response); echo '</pre>';
    $batchWithRulesRun = [];
    foreach ($response->records as $record) {
      $this->saveRecordResponse($record);
    }
  }

  /**
   * Saves verification responses for a record.
   *
   * This function processes the messages in the provided record array. If a message
   * matches the pattern `:(difficulty|period|phenology|10km):`, it extracts the relevant
   * tokens and inserts a comment into the `occurrence_comments` table.
   *
   * @param object $record The record object containing messages and other details.
   *   - 'id' (int): The ID of the occurrence record.
   *   - 'messages' (array): An array of messages to be processed.
   *
   * The function expects the messages to be in a specific format, where the message
   * contains tokens separated by colons. The expected format is:
   * `:<sourceCode>:<ruleType>:<comment>` or `:<sourceCode>:<ruleType>:<subType>:<comment>`
   */
  private function saveRecordResponse($record) {
    echo "Processing ID $record->id: " . var_export($record->messages, TRUE) . '<br/>';
    foreach ($record->messages as $message) {
      if (preg_match('/:(difficulty|period|phenology|tenkm):/', $message)) {
        $tokens = explode(':', $message);
        $sourceCode = $tokens[1];
        $ruleType = $tokens[2];
        $comment = trim($tokens[count($tokens) - 1]);
        $subType = $ruleType === 'difficulty' ? $tokens[3] : NULL;
        if ($ruleType === 'difficulty' && (int) $subType < 2) {
          continue;
        }
        $query = <<<SQL
          INSERT INTO occurrence_comments (occurrence_id, comment, auto_generated, generated_by, generated_by_subtype, implies_manual_check_required, created_by_id, created_on, updated_by_id, updated_on)
          VALUES (?, ?, true, ?, ?, true, 1, now(), 1, now())
        SQL;
        $this->db->query($query, [
          $record->id,
          $comment,
          "record_cleaner:$ruleType:$sourceCode",
          $subType,
        ]);
      }
    }
  }

  private function updateCacheTables(array $batch) {
    $ids = implode(',', $batch);
    $query = <<<SQL
      SELECT o.id,
      CASE WHEN o.last_verification_check_date IS NULL THEN NULL ELSE
        COALESCE(string_agg(distinct '[' || replace(oc.generated_by, ':', ' ') || ']{' || oc.comment || '}', ' '), 'pass')
      END AS data_cleaner_info,
      CASE WHEN o.last_verification_check_date IS NULL THEN NULL ELSE COUNT(oc.id)=0 END AS data_cleaner_result,
      ocdiff.generated_by_subtype::integer as difficulty
      INTO TEMPORARY data_cleaner_results
      FROM occurrences o
      LEFT JOIN occurrence_comments oc ON oc.occurrence_id=o.id
        AND oc.implies_manual_check_required=true
        AND oc.deleted=false
      LEFT JOIN occurrence_comments ocdiff ON ocdiff.occurrence_id=o.id
        AND ocdiff.implies_manual_check_required=true
        AND ocdiff.deleted=false
        AND ocdiff.generated_by like 'record_cleaner:difficulty:%'
      WHERE o.id in ($ids)
      GROUP BY o.id, o.last_verification_check_date, ocdiff.generated_by_subtype;

      UPDATE cache_occurrences_functional o
      SET data_cleaner_result = dcr.data_cleaner_result,
        applied_verification_rule_types=cttl.applicable_verification_rule_types,
        identification_difficulty = dcr.difficulty
      FROM data_cleaner_results dcr, cache_taxa_taxon_lists cttl
      WHERE dcr.id=o.id
      AND cttl.external_key = o.taxa_taxon_list_external_key
      AND cttl.preferred=true;

      UPDATE cache_occurrences_nonfunctional o
      SET data_cleaner_info = dcr.data_cleaner_info
      FROM data_cleaner_results dcr
      WHERE dcr.id=o.id;

      DROP TABLE data_cleaner_results;
    SQL;
    $this->db->query($query);
  }

  /**
   * Update the metadata associated occurrences so we know rules have been run.
   *
   * @param object $db
   *   Kohana database instance.
   * @param string $endtime
   *   Timestamp to save as the last verification check date.
   */
  function updateOccurrenceMetadata(array $batch, $endtime) {
    $ids = implode(',', $batch);
    // Note we use the information from the point when we started the process,
    // in caseany changes have happened in the meanwhile which might otherwise be
    // missed.
    $query = <<<SQL
      update occurrences o
      set last_verification_check_date='$endtime'
      where o.id in ($ids)
    SQL;
    $this->db->query($query);
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

}
