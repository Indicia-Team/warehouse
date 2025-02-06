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
 * @todo Check date format in records returned by the API.
 */

class SkipRecordException extends Exception {}

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
        OR generated_by LIKE 'record_cleaner_%'
      );
    SQL;
    $this->db->query($query);
  }

  /**
   * Process a batch of records.
   *
   * @param array $batch
   *   Record IDs in the batch to process.
   */
  private function processBatch(array $batch) {
    $ids = implode(',', $batch);
    $query = <<<SQL
      SELECT o.id, o.taxa_taxon_list_external_key AS tvk,
        o.date_start, o.date_end, o.date_type,
        onf.attr_stage AS stage,
        s.entered_sref, s.entered_sref_system, o.map_sq_10km_id, COALESCE(snf.attr_sref_precision, 50) as accuracy,
        st_x(st_transform(st_centroid(s.geom), 4326)) as lon, st_y(st_transform(st_centroid(s.geom), 4326)) as lat,
        o.created_on
      FROM cache_occurrences_functional o
      JOIN cache_occurrences_nonfunctional onf ON onf.id=o.id
      JOIN samples s on s.id=o.sample_id AND s.deleted=false
      JOIN cache_samples_nonfunctional snf on snf.id=o.sample_id
      WHERE o.id IN ($ids)
    SQL;

    $records = $this->db->query($query, [$batch])->result();
    $recordsArray = [];
    // We need to know 10km square later when checking for existing verified
    // records.
    $tenKmSquareIds = [];
    foreach ($records as $record) {
      try {
        $recordsArray[] = [
          'id' => $record->id,
          'tvk' => $record->tvk,
          'date' =>   $this->getFormattedDate($record),
          'sref' => $this->getFormattedSref($record),
          'stage' => $record->stage,
        ];
        $tenKmSquareIds[$record->id] = $record->map_sq_10km_id;
      }
      catch (SkipRecordException $e) {
        // Aborted for some reason, so add a note.
        $query = <<<SQL
          INSERT INTO occurrence_comments (occurrence_id, comment, auto_generated, generated_by, generated_by_subtype, implies_manual_check_required, created_by_id, created_on, updated_by_id, updated_on)
          VALUES (?, ?, true, 'record_cleaner', null, true, 1, now(), 1, now())
        SQL;
        $this->db->query($query, [
          $record->id,
          'This record was not checked by Record Cleaner: ' . $e->getMessage(),
        ]);
      }
    }
    $response = $this->curlRequest('/verify', [], json_encode(['records' => $recordsArray]));
    if (!isset($response->records)) {
      kohana::log('error', 'Unexpected response from Record Cleaner: ' . var_export($response, TRUE));
      throw new Exception('Unexpected response from Record Cleaner.');
    }
    foreach ($response->records as $record) {
      $this->saveRecordResponse($record, $tenKmSquareIds[$record->id]);
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
   * Return the required structure to send to the Record Cleaner sref field.
   *
   * @param mixed $record
   *   Record data read from the database.
   *
   * @return array
   *   Sref array structure.
   */
  private function getFormattedSref($record): array {
    $sref = strtoupper(trim($record->entered_sref));
    switch (strtoupper($record->entered_sref_system)) {
      case 'osgb':
      case 'osie':
      case 'utm30ed50':
        return [
          'srid' => $this->gridSystemCodeToSrid($record->entered_sref_system),
          'gridref' => $record['entered_sref'],
        ];

      case '23030': // Channel Islands Easting Northing
      case '27770': // OSGB Easting Northing
        if (!preg_match('/^(?<x>\d+),\s*(?<y>\d+)$/', $sref, $matches)) {
          throw new SkipRecordException('Incorrectly formatted coordinates.');
        }
        return [
          'srid' => $record->entered_sref_system,
          'easting' => $matches['x'],
          'northing' => $matches['y'],
          'accuracy' => $this->getFormattedAccuracy($record->accuracy),
        ];

      default:
        // Use lat long coords.
        return [
          'srid' => '4326',
          'longitude' => $record->lon,
          'latitude' => $record->lat,
          'accuracy' => $this->getFormattedAccuracy($record->accuracy),
        ];
    }
  }

  /**
   * Converts record coordinate precision to an accuracy from the expected set.
   *
   * @param int $accuracy
   *   The accuracy value to be converted.
   *
   * @return int
   *   The next highest number from the set [10000, 2000, 1000, 100, 10, 1].
   */
  private function getFormattedAccuracy($accuracy) {
    $thresholds = [10000, 2000, 1000, 100, 10, 1];
    foreach ($thresholds as $threshold) {
      if ($accuracy <= $threshold) {
        return $threshold;
      }
    }
    // Default to the highest threshold if none matched.
    return 10000;
  }

  /**
   * Maps grid system code to SRID.
   *
   * @param string $gridSystemCode
   *   The grid system code (e.g., 'osgb', 'osie', 'utm30ed50').
   *
   * @return int
   *   The corresponding SRID.
   */
  private function gridSystemCodeToSrid($gridSystemCode) {
    $mapping = [
      'osgb' => 27700,
      'osie' => 29903,
      'utm30ed50' => 23030,
    ];
    if (!isset($mapping[$gridSystemCode])) {
      throw new SkipRecordException("Unsupported grid system code: $gridSystemCode");
    }
    return $mapping[$gridSystemCode];
  }



  /**
   * Saves verification responses for a record.
   *
   * This function processes the messages in the provided record array. If a
   * message matches the pattern `:(difficulty|period|phenology|10km):`, it
   * extracts the relevant tokens and inserts a comment into the
   * `occurrence_comments` table.
   *
   * The function expects the messages to be in a specific format, where the
   * message contains tokens separated by colons. The expected format is:
   * `:<sourceCode>:<ruleType>:<comment>` or `:<sourceCode>:<ruleType>:<subType>:<comment>`.
   *
   * @param object $record
   *   The record object containing messages and other details.
   *   - 'id' (int): The ID of the occurrence record.
   *   - 'messages' (array): An array of messages to be processed.
   * @param string $tenKmSqId
   *   The ID of the 10km square, required when checking for other nearby
   *   verified records.
   */
  private function saveRecordResponse($record, $tenKmSqId) {
    foreach ($record->messages as $message) {
      if (preg_match('/^Rules run: (?<rules>.+)/', $message, $matches)) {
        $ruleList = explode(', ', $matches['rules']);
        $query = <<<SQL
          UPDATE cache_taxa_taxon_lists cttl
          SET applicable_verification_rule_types = array(
            SELECT DISTINCT unnest(array_append(cttl.applicable_verification_rule_types, ?))
          )
          FROM cache_occurrences_functional o
          WHERE o.id=?
          AND cttl.taxon_meaning_id=o.taxon_meaning_id
          AND cttl.preferred=true;
        SQL;
        foreach ($ruleList as $rule) {
          $this->db->query($query, [
            $rule,
            $record->id,
          ]);
        }
      }
      elseif (preg_match('/:(difficulty|period|phenology|tenkm):/', $message)) {
        $tokens = explode(':', $message);
        $sourceCode = $tokens[1];
        $ruleType = $tokens[2];
        $comment = trim($tokens[count($tokens) - 1]);
        $subType = $ruleType === 'difficulty' ? $tokens[3] : NULL;
        if ($this->shouldDiscardFlagDueToExistingVerifiedRecords($record, $ruleType, $tenKmSqId)) {
          // Discard this message due to similar verified records.
          continue;
        }
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
        }
        else {
          // Difficulty rules are stored differently.
          $query = <<<SQL
            UPDATE cache_taxon_searchterms cts
            SET identification_difficulty = ?
            FROM cache_occurrences_functional o
            WHERE cts.taxon_meaning_id=o.taxon_meaning_id
            AND o.id=?;
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
   * Determines whether a flag should be discarded due to the existence of verified records.
   *
   * This function checks if there are existing verified records that match certain criteria
   * based on the rule type provided.
   *
   * @param object $record
   *   The record being evaluated.
   * @param string $ruleType
   *   The type of rule being applied. Can be 'phenology', 'tenkm', other rule
   *   types are not checked.
   * @param string $tenKmSqId
   *   The ID of the 10km square, required when checking for other nearby
   *   verified records.
   *
   * @return bool
   *   Returns TRUE if the flag should be discarded, FALSE otherwise.
   */
  private function shouldDiscardFlagDueToExistingVerifiedRecords($record, $ruleType, $tenKmSqId) {
    $query = <<<SQL
      SELECT id
      FROM cache_occurrences_functional o
      WHERE o.taxa_taxon_list_external_key=?
      AND o.record_status='V'
      AND o.map_sq_10km_id=?
    SQL;
    $params = [
      $record->tvk,
      $tenKmSqId,
    ];
    switch ($ruleType) {
      case 'phenology':
        // Only check exact dates without a hyphen separating the range.
        if (preg_match('/^( -|- )$/', $record->date)) {
          return FALSE;
        }
        $query .= <<<SQL
          AND ABS(EXTRACT(doy from o.date_start) - EXTRACT(doy FROM ?::date)) < 7
        SQL;
        // Record Cleaner API currently returns UK format d/m/Y.
        list($d, $m, $y) = explode('/', $record->date);
        $params[] = "$y-$m-$d";
        break;

      case 'tenkm':
        break;

      default:
        // Don't do this check for other rule types.
        return FALSE;
    }
    return $this->db->query("$query LIMIT 3", $params)->count() >= 3;
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



  /**
   * Update results into occurrence cache tables.
   *
   * @param array $batch
   *   List of record IDs to update.
   */
  private function updateCacheTables(array $batch) {
    // Ensure IDs are integers to prevent SQL injection.
    $ids = implode(',', array_map('intval', $batch));
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
        AND ocdiff.generated_by='record_cleaner_difficulty'
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
