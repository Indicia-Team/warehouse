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
 * @todo Should output_sref be the blurred or precise version in the submitted records?
 * @todo Discard rule violations if there are similar records already verified.
 * @todo Do ES identification difficulty rule filters work?
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
      $batch = array_slice($ids, $start, 100);
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
      SELECT o.id, o.taxa_taxon_list_external_key AS tvk,
        o.date_start, o.date_end, o.date_type,
        onf.attr_stage AS stage, onf.output_sref,
        o.created_on
      FROM cache_occurrences_functional o
      JOIN cache_occurrences_nonfunctional onf ON onf.id=o.id
      WHERE o.id IN ($ids)
    SQL;

    $records = $this->db->query($query, [$batch])->result();
    $recordsArray = [];
    foreach ($records as $record) {
      $recordsArray[] = [
        'id' => $record->id,
        'tvk' => $record->tvk,
        'date' =>   $this->getFormattedDate($record),
        'sref' => [
          'srid' => 27700,
          'gridref' => $record->output_sref,
        ],
        'stage' => $record->stage,
      ];
    }
    $response = $this->curlRequest('/verify', [], json_encode(['records' => $recordsArray]));
    foreach ($response->records as $record) {
      $this->saveRecordResponse($record);
    }
  }

  /**
   * Formats the date of a record for Record Cleaner.
   *
   * If the record's date is exact, it returns the date in 'yyyy-mm-dd' format.
   * Otherwise, it returns a date range in the format 'start_date - end_date'.
   * If the end date is not specified, it uses the record's submission date.
   *
   * @param object $record
   *   The record object containing date information.
   *
   * @return string
   *   The formatted date or date range.
   */
  private function getFormattedDate($record): string {
    if ($record->date_type === 'U') {
      // Date unknown, so use the date range up to the submission date.
      return date('Y-m-d', strtotime($record->created_on));
    }
    if ($record->date_type === 'D' || $record->date_start === $record->date_end) {
      // Already in yyyy-mm-dd format.
      return $record->date_start;
    }
    // Return a date range, but note that the end date cannot be indeterminate
    // so use the submission date if not specified.
    $startDate = $record->date_start ?? '';
    $endDate = $record->date_end ?? date('Y-m-d', strtotime($record->created_on));
    return trim("$startDate - $endDate");
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
    foreach ($record->messages as $message) {
      if (preg_match('/:(difficulty|period|phenology|tenkm):/', $message)) {
        $tokens = explode(':', $message);
        $sourceCode = $tokens[1];
        $ruleType = $tokens[2];
        $comment = trim($tokens[count($tokens) - 1]);
        $subType = $ruleType === 'difficulty' ? $tokens[3] : NULL;
        if ($ruleType !== 'difficulty') {
          // @todo Explore if we can get a list of taxa with rules from the API,
          // so we can do a single update of the entire list, rather than based
          // on individual records.
          $mappedRuleType = $this->mapRuleType($ruleType);
          // Update the applicable rules in the taxon cache tables.
          $query = <<<SQL
            UPDATE cache_taxa_taxon_lists cttl
            SET applicable_verification_rule_types = array_append(cttl.applicable_verification_rule_types, ?)
            FROM cache_occurrences_functional o
            WHERE o.id=?
            AND cttl.taxon_meaning_id=o.taxon_meaning_id
            AND (NOT ? = ANY(cttl.applicable_verification_rule_types) OR cttl.applicable_verification_rule_types IS NULL);
          SQL;
          $this->db->query($query, [
            $mappedRuleType,
            $record->id,
            $mappedRuleType,
          ]);
          // Update the occurrence's applied rules to reflect the record
          // cleaner rule combined with the data cleaner rules.
          $query = <<<SQL
            UPDATE cache_occurrences_functional o
            SET applied_verification_rule_types = cttl.applicable_verification_rule_types
            FROM cache_taxa_taxon_lists cttl
            WHERE o.id=?
            AND cttl.preferred_taxa_taxon_list_id=o.preferred_taxa_taxon_list_id;
          SQL;
          $this->db->query($query, [
            $record->id,
          ]);
        }
        else {
          // Difficulty rules are stored differently.
          $query = <<<SQL
            UPDATE cache_taxon_searchterms cts
            SET identification_difficulty = ?
            FROM cache_occurrences_functional o
            WHERE cts.taxon_meaning_id=o.taxon_meaning_id
            AND o.id=?;
            UPDATE cache_occurrences_functional
            SET identification_difficulty = ?
            WHERE id = ?;
          SQL;
          $this->db->query($query, [
            $subType,
            $record->id,
            $subType,
            $record->id,
          ]);
          if ((int) $subType < 2) {
            continue;
          }
        }
        $query = <<<SQL
          INSERT INTO occurrence_comments (occurrence_id, comment, auto_generated, generated_by, generated_by_subtype, implies_manual_check_required, created_by_id, created_on, updated_by_id, updated_on)
          VALUES (?, ?, true, ?, ?, true, 1, now(), 1, now())
        SQL;
        $this->db->query($query, [
          $record->id,
          "$comment ($sourceCode)",
          "record_cleaner_$ruleType",
          $subType,
        ]);
      }
    }
  }

  /**
   * Maps Record Cleaner rule types to Indicia rule types.
   *
   * @param string $ruleType
   *   The rule type from the Record Cleaner.
   *
   * @return string
   *   The corresponding Indicia rule type.
   */
  private function mapRuleType($ruleType) {
    $mapping = [
      'difficulty' => 'identification_difficulty',
      'period' => 'period',
      'phenology' => 'period_within_year',
      'tenkm' => 'without_polygon',
    ];
    return $mapping[$ruleType] ?? $ruleType;
  }



  private function updateCacheTables(array $batch) {
    $ids = implode(',', $batch);
    $query = <<<SQL
      SELECT o.id,
      CASE WHEN o.last_verification_check_date IS NULL THEN NULL ELSE
        COALESCE(string_agg(distinct '[' || oc.generated_by || ']{' || oc.comment || '}', ' '), 'pass')
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
        AND ocdiff.generated_by like 'record_cleaner:%:difficulty'
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
