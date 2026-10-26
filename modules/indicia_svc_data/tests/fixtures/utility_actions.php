<?php

/**
 * Bounded service doubles for utility nonce lifecycle tests.
 *
 * Controllers, authentication, permissions, and nonce file operations are real.
 * Database/workflow/spreadsheet doubles supply successful operations without
 * warehouse fixtures; unexpected queries fail rather than silently succeeding.
 */
require_once $root . '/application/helpers/warehouse.php';

/**
 * Return controller-compatible rows without loading the framework database.
 */
class Utility_Action_Result extends ArrayObject {
  public function current() {
    return $this->count() ? (object) $this[0] : NULL;
  }

  public function result($objects = TRUE) {
    return $objects ? array_map(static function ($row) {
      return (object) $row;
    }, $this->getArrayCopy()) : $this->getArrayCopy();
  }

  public function result_array($objects = FALSE) {
    return $this->result($objects);
  }
}

/**
 * Model only queries used by the selected utility action; count mutations.
 */
class Utility_Action_Database {
  public static $calls = 0;
  public static $link;
  public static $effects = 0;

  public function __construct() {
    self::$calls++;
    if (!empty($GLOBALS['scenario']['fail_operation'])) {
      throw new RuntimeException('Test utility operation failed.');
    }
  }

  public function getLink() {
    if (!self::$link) {
      $dsn = getenv('INDICIA_TEST_PG_DSN');
      if (!$dsn) {
        throw new RuntimeException('Set INDICIA_TEST_PG_DSN for PostgreSQL escaping tests.');
      }
      self::$link = pg_connect(str_replace(';', ' ', preg_replace('/^pgsql:/', '', $dsn)), PGSQL_CONNECT_FORCE_NEW);
    }
    return self::$link;
  }

  public function query($sql, array $parameters = []) {
    $normalised = preg_replace('/\s+/', ' ', trim($sql));
    if (str_starts_with($normalised, 'SELECT COUNT(*) AS count FROM')) {
      return new Utility_Action_Result([['count' => !empty($GLOBALS['scenario']['deny_access']) ? 0 : 1]]);
    }
    if (str_starts_with($normalised, 'SELECT external_key FROM cache_taxa_taxon_lists')) {
      return new Utility_Action_Result([['external_key' => 'TEST-TAXON']]);
    }
    if (str_starts_with($normalised, 'SELECT count(*) FROM')
        || str_starts_with($normalised, 'SELECT COUNT(DISTINCT o.id)')) {
      return new Utility_Action_Result([['count' => '0']]);
    }
    if (str_starts_with($normalised, 'SELECT DISTINCT o.sample_id')) {
      return new Utility_Action_Result([['sample_id' => 1, 'forced_spatial_indexer_location_ids' => '{}']]);
    }
    if (str_starts_with($normalised, 'SELECT SUM(CASE')) {
      return new Utility_Action_Result([[
        'invalid_survey_count' => 0, 'confidential_count' => 0,
        'other_user_count' => 0, 'occurrence_count' => 1, 'sample_count' => 1,
      ]]);
    }
    if (str_starts_with($normalised, 'SELECT o2.id as excluded_id')) {
      return new Utility_Action_Result([]);
    }
    if (str_starts_with($normalised, 'SELECT DISTINCT website_id FROM occurrences')) {
      return new Utility_Action_Result([['website_id' => 1]]);
    }
    if (str_starts_with($normalised, 'SELECT string_agg(distinct sample_id::text')) {
      return new Utility_Action_Result([['string_agg' => '1']]);
    }
    if (in_array($normalised, ['START TRANSACTION;', 'COMMIT;', 'ROLLBACK;'], TRUE)) {
      return new Utility_Action_Result([]);
    }
    if (str_starts_with($normalised, 'UPDATE ')
        || str_starts_with($normalised, 'INSERT INTO ')
        || str_starts_with($normalised, 'SELECT s.id INTO TEMPORARY changing_samples')
        || str_starts_with($normalised, 'SELECT f_handle_determination(')
        || $normalised === 'select "test_action"();') {
      self::$effects++;
      return new Utility_Action_Result([['result' => 'OK']]);
    }
    throw new LogicException("Unexpected utility query: $normalised");
  }

  public function from($table) {
    if (!in_array($table, ['samples', 'cache_samples_functional'], TRUE)) {
      throw new LogicException("Unexpected update table: $table");
    }
    return $this;
  }

  public function set(array $updates) {
    return $this;
  }

  public function where($field, $value) {
    return $this;
  }

  public function in($field, array $values) {
    return $this;
  }

  public function update() {
    self::$effects++;
  }

  public function insert($table, array $values) {
    if (!in_array($table, ['occurrence_comments', 'sample_comments'], TRUE)) {
      throw new LogicException("Unexpected insert table: $table");
    }
    self::$effects++;
  }
}

/**
 * Supply one unverified occurrence/sample for real bulk verification loops.
 */
class ReportEngine {
  public function __construct($websites, $userId) {
  }

  public function requestReport($report, $source, $format, array $parameters) {
    return ['content' => ['records' => [[
      'occurrence_id' => 1, 'sample_id' => 1, 'record_status' => 'C',
      'record_substatus' => NULL, 'pass' => TRUE,
    ]]]];
  }
}

/**
 * Count occurrence updates while leaving workflow internals out of these tests.
 */
class data_utils {
  public static function getOccurrenceTableVerificationUpdateValues(...$parameters) {
    return ['record_status' => 'V'];
  }

  public static function getOccurrenceTableRedetUpdateValues(...$parameters) {
    return ['taxa_taxon_list_id' => 1];
  }

  public static function applyWorkflowToOccurrenceVerificationUpdates(...$parameters) {
    Utility_Action_Database::$effects++;
  }
}

/**
 * Record queue writes without requiring a shared work queue.
 */
class WorkQueue {
  public function enqueue($db, array $entry) {
    Utility_Action_Database::$effects++;
  }
}

/**
 * Provide one valid species-recorded rule without real uploads or spreadsheets.
 */
class ImportTools {
  public function uploadFile() {
    Utility_Action_Database::$effects++;
    return '/test/rules.csv';
  }

  public function loadColumnTitlesFromFile($file, $normalise) {
    return ['taxon id', 'rule type'];
  }

  public function openSpreadsheet($file, array &$config) {
    return [new class {
      public function isEmpty() {
        return FALSE;
      }
    }];
  }

  public function rowToArray($row, array $columns) {
    return ['TEST-TAXON', 'species recorded'];
  }
}
