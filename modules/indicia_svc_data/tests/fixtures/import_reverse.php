<?php

/**
 * Connection-local data for import reversal tests.
 */
class url {
  public static function base(): string {
    return '/';
  }
}

class Import_Reverse_Database {

  public static $calls = 0;
  public static $link;
  public static $blocksUnrelatedWrites;

  public function __construct() {
    global $scenario;
    self::$calls++;
    $dsn = getenv('INDICIA_TEST_PG_DSN');
    if (!$dsn) {
      throw new RuntimeException('Database reached');
    }
    self::$link = pg_connect(str_replace(';', ' ', preg_replace('/^pgsql:/', '', $dsn)), PGSQL_CONNECT_FORCE_NEW);
    pg_query(self::$link, <<<'SQL'
      CREATE TEMP TABLE imports (
        id int, website_id int, created_by_id int, import_guid text,
        entity text, reversible boolean, deleted boolean, inserted int, updated int,
        global_values json NOT NULL DEFAULT '{}', mappings json NOT NULL DEFAULT '{}'
      );
      CREATE TEMP TABLE occurrences (
        id int, sample_id int, import_guid text, deleted boolean,
        created_on timestamp, updated_on timestamp, updated_by_id int,
        determiner_id int, confidential boolean, created_by_id int, website_id int,
        external_key text, comment text, taxa_taxon_list_id int, record_status text,
        verified_by_id int, verified_on timestamp, zero_abundance boolean,
        last_verification_check_date timestamp, training boolean, sensitivity_precision int,
        release_status text, record_substatus text, record_decision_source text
      );
      CREATE TEMP TABLE samples (
        id int, survey_id int, location_id int, date_start date, date_end date, date_type text,
        entered_sref text, entered_sref_system text, location_name text, created_on timestamp,
        created_by_id int, comment text, external_key text, sample_method_id int, recorder_names text,
        parent_id int, input_form text, group_id int, privacy_precision int, record_status text,
        verified_by_id int, verified_on timestamp, licence_id int, training boolean,
        import_guid text, deleted boolean, updated_on timestamp, updated_by_id int
      );
      CREATE TEMP TABLE cache_occurrences_functional (id int);
      CREATE TEMP TABLE cache_occurrences_nonfunctional (id int);
      CREATE TEMP TABLE cache_samples_functional (id int);
      CREATE TEMP TABLE cache_samples_nonfunctional (id int);
      SQL);
    $import = array_replace([1, 1, 2, 'AAAAAAAA-AAAA-4AAA-AAAA-AAAAAAAAAAAA', 'occurrence', 't', 'f', 1, 0, '{}', '{}'], $scenario['import'] ?? []);
    pg_query_params(self::$link, 'INSERT INTO imports VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)', $import);
    if (!empty($scenario['reversal_records'])) {
      mkdir($scenario['directory'] . '/upload');
      $_SERVER['DOCUMENT_ROOT'] = $scenario['directory'];
      pg_query_params(self::$link, <<<'SQL'
        INSERT INTO samples (id, import_guid, parent_id, deleted) VALUES
          (1, $1, NULL, false), (2, NULL, 1, false), (3, $1, NULL, false);
        SQL, [$import[3]]);
      pg_query_params(self::$link, <<<'SQL'
        INSERT INTO occurrences (id, sample_id, import_guid, deleted) VALUES
          (1, 3, $1, false), (2, 2, 'LATER-IMPORT', false);
        SQL, [$import[3]]);
      pg_query(self::$link, <<<'SQL'
        INSERT INTO cache_occurrences_functional SELECT id FROM occurrences;
        INSERT INTO cache_samples_functional SELECT id FROM samples;
        SQL);
    }
  }

  public function getLink() {
    return self::$link;
  }

  public function query($sql, array $params = []) {
    if ($sql === 'COMMIT') {
      self::$blocksUnrelatedWrites = pg_fetch_result(pg_query(self::$link, <<<'SQL'
        SELECT EXISTS (
          SELECT 1 FROM pg_locks
          WHERE pid = pg_backend_pid()
            AND granted
            AND relation IN ('samples'::regclass, 'occurrences'::regclass)
            AND mode IN ('ShareLock', 'ShareRowExclusiveLock', 'ExclusiveLock', 'AccessExclusiveLock')
        )
        SQL), 0, 0) === 't';
    }
    // The controller uses Kohana's question-mark bindings.
    $index = 0;
    $sql = preg_replace_callback('/\?/', static function () use (&$index) {
      return '$' . ++$index;
    }, $sql);
    $result = pg_query_params(self::$link, $sql, $params);
    return new class($result) {
      public function __construct(private $result) {}

      public function result_array(): array {
        return array_map(static fn(array $row) => (object) $row, pg_fetch_all($this->result));
      }
    };
  }

}
