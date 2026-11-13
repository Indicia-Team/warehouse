<?php

/**
 * Exercise real import recovery endpoints without a warehouse connection.
 */
$scenario = json_decode(stream_get_contents(STDIN), TRUE, 512, JSON_THROW_ON_ERROR);
$root = dirname(__DIR__, 4);
chdir($root);
define('SYSPATH', $root . '/system/');
define('DOCROOT', $scenario['directory'] . '/');

if (!function_exists('pg_escape_identifier')) {
  function pg_escape_identifier($link, $value) {
    return '"' . str_replace('"', '""', $value) . '"';
  }
}

class Kohana {
  public static array $logs = [];

  public static function config(...$args) {
    return [];
  }

  public static function log($level, $message) {
    self::$logs[] = [$level, $message];
  }
}

class inflector {
  public static function plural($value) {
    return $value . 's';
  }
}

class Service_Base_Controller {}

class ImportRecoveryRecord extends stdClass {
  public function set_metadata() {}
  public function save() {}
  public function getAllErrors() {
    return [];
  }
}

class ORM {
  public static function factory(...$args) {
    return new ImportRecoveryRecord();
  }
}

class ImportRecoveryResult implements IteratorAggregate {
  public function __construct(private array $rows = []) {}

  public function result(...$args) {
    return $this;
  }

  public function current() {
    return $this->rows[0];
  }

  public function getIterator(): Traversable {
    yield from $this->rows;
  }
}

class Database {
  public static array $benchmarks = [];
  public static array $queries = [];
  public static array $counts;

  public function getLink() {
    return NULL;
  }

  public function query($sql) {
    self::$queries[] = $sql;
    if (str_contains($sql, 'COUNT(*)')) {
      return new ImportRecoveryResult([(object) self::$counts]);
    }
    if (str_contains($sql, 'DROP TABLE')) {
      return new ImportRecoveryResult();
    }
    $rows = [
      ['saved', 2, NULL, 'yes'],
      ['pending', 3, NULL, 'no'],
      ['invalid', 4, '{"comment":"invalid"}', 'no'],
    ];
    if (str_contains($sql, 'WHERE errors IS NOT NULL')) {
      $includePending = str_contains($sql, 'OR imported=false');
      $rows = array_values(array_filter($rows, static function ($row) use ($includePending) {
        return $row[2] !== NULL || ($includePending && $row[3] === 'no');
      }));
    }
    if (!str_contains($sql, 'END as imported')) {
      $rows = array_map(static fn ($row) => array_slice($row, 0, 3), $rows);
    }
    return new ImportRecoveryResult($rows);
  }
}

require_once 'modules/indicia_svc_import/helpers/import2ChunkHandler.php';
require_once 'modules/indicia_svc_import/controllers/services/import_2.php';

$config = [
  'importGuid' => 'recovery-test',
  'tableName' => 'recovery_test',
  'entity' => 'occurrence',
  'parentEntity' => 'sample',
  'global-values' => ['website_id' => 1],
  'columns' => ['Comment' => ['tempDbField' => 'comment']],
  'isExcel' => FALSE,
  'totalRows' => 249,
  'rowsProcessed' => $scenario['processed'] ?? 200,
  'rowsInserted' => $scenario['processed'] ?? 200,
  'rowsUpdated' => 0,
  'errorsCount' => 0,
  'files' => [],
  'activeParent' => ['id' => 42, 'data' => ['comment' => 'not-for-logs']],
];
import2ChunkHandler::saveConfig('recovery', $config);

if ($scenario['action'] === 'export') {
  $_GET = ['config-id' => 'recovery'] + ($scenario['options'] ?? []);
  ob_start();
  (new Import_2_Controller())->get_errors_file();
  $csv = ob_get_clean();
  $stream = fopen('php://temp', 'w+');
  fwrite($stream, $csv);
  rewind($stream);
  $response = [];
  while (($row = fgetcsv($stream, escape: '\\')) !== FALSE) {
    $response[] = $row;
  }
  fclose($stream);
}
else {
  Database::$counts = $scenario['counts'] ?? [
    'total_rows' => 249,
    'imported_rows' => 200,
    'checked_rows' => 249,
    'error_rows' => 0,
    'pending_import_rows' => 49,
    'pending_check_rows' => 0,
  ];
  $context = [
    'requestId' => 'test-request',
    'configId' => 'recovery',
    'importGuid' => $config['importGuid'],
    'rowsProcessedAtStart' => $scenario['start'] ?? 100,
    'activeParentBefore' => 42,
  ];
  $method = new ReflectionMethod(import2ChunkHandler::class, 'finishChunk');
  $response = $method->invokeArgs(NULL, [
    new Database(), 'recovery', &$config, !empty($scenario['precheck']),
    $scenario['parents'] ?? [], 0, microtime(TRUE), $context,
  ]);
}

echo json_encode([
  'response' => $response,
  'queries' => Database::$queries,
  'logs' => Kohana::$logs,
  'configRetained' => file_exists(DOCROOT . 'import/recovery.json'),
], JSON_THROW_ON_ERROR);
