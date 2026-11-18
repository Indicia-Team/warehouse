<?php

/**
 * Connection-local data for import reversal authorization tests.
 */
class Import_Reverse_Database {

  public static $calls = 0;
  public static $link;

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
        entity text, reversible boolean, deleted boolean, inserted int, updated int
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
    $import = array_replace([1, 1, 2, 'AAAAAAAA-AAAA-4AAA-AAAA-AAAAAAAAAAAA', 'occurrence', 't', 'f', 0, 0], $scenario['import'] ?? []);
    pg_query_params(self::$link, 'INSERT INTO imports VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)', $import);
  }

  public function getLink() {
    return self::$link;
  }

  public function query($sql, array $params = []) {
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
