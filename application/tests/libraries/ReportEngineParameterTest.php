<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../libraries/ReportEngine.php';

/**
 * Check SQL assembly without executing report queries against a database.
 */
class ReportEngineParameterTest extends TestCase {

  public function testPreprocessedWhereKeepsBothFormsOfTheParameter() {
    $paramDef = [
      'datatype' => 'text',
      'preprocess' => "SELECT keys FROM workflow WHERE code = '#group_code#'",
      'wheres' => [['sql' => 'external_key IN (#group_code#)']],
    ];
    $processedList = <<<'SQL'
    'one', 'O''Brien', E'key\\1'
    SQL;
    foreach ([[[$processedList]], [[NULL]], []] as $rows) {
      $db = new class($rows) {

        public $sql;
        private $rows;

        public function __construct($rows) {
          $this->rows = $rows;
        }

        public function escape_str($value) {
          return str_replace("'", "''", $value);
        }

        public function query($sql) {
          $this->sql = $sql;
          return $this;
        }

        public function result_array($object = FALSE) {
          return $this->rows;
        }

      };
      $sql = $this->mergeQuery(
        "SELECT * FROM records WHERE group_code = '#group_code-unprocessed#' #filters#",
        ['group_code' => "a'b"],
        ['group_code' => $paramDef],
        $db
      );
      $this->assertSame("SELECT keys FROM workflow WHERE code = 'a''b'", $db->sql);
      $this->assertStringContainsString("group_code = 'a''b'", $sql);
      $expected = $rows[0][0] ?? "'-999999'";
      $this->assertStringContainsString("AND (external_key IN ($expected))", $sql);
    }
  }

  public function testDatesAreNormalisedInBothWhereAndMainQuery() {
    $sql = $this->mergeQuery(
      "SELECT '#from#' AS filter_date FROM records WHERE TRUE #filters#",
      ['from' => '04/05/2026'],
      ['from' => ['datatype' => 'date', 'wheres' => [['sql' => "record_date >= '#from#'::date"]]]]
    );
    $this->assertStringContainsString("'2026-05-04' AS filter_date", $sql);
    $this->assertStringContainsString("(record_date >= '2026-05-04'::date)", $sql);
  }

  public function testConditionalNumericWhereUsesEmptyValue() {
    $sql = $this->mergeQuery(
      'SELECT * FROM records WHERE TRUE #filters#',
      ['id' => ''],
      ['id' => [
        'datatype' => 'integer',
        'emptyvalue' => '-1',
        'wheres' => [['value' => '', 'sql' => 'record_id = #id#']],
      ]]
    );
    $this->assertStringContainsString('AND (record_id = -1)', $sql);
  }

  public function testMultiselectSubstitutesEachSelectedValue() {
    $sql = $this->mergeQuery(
      'SELECT * FROM records WHERE TRUE #filters#',
      ['ids' => '12, 34'],
      ['ids' => [
        'datatype' => 'integer[]',
        'multiselect' => TRUE,
        'wheres' => [['sql' => 'record_id = #ids#']],
      ]]
    );
    $this->assertMatchesRegularExpression('/AND \(\(record_id = 12\) OR \(record_id =\s+34\)\)/', $sql);
  }

  public function testIdListsInWhereAndWholeClauseTokens() {
    $sql = $this->mergeQuery(
      'SELECT * FROM records WHERE TRUE #excluded# #filters#',
      ['ids' => '12, 34', 'excluded' => '56,78'],
      [
        'ids' => ['datatype' => 'idlist', 'wheres' => [['sql' => 'record_id IN (#ids#)']]],
        'excluded' => ['datatype' => 'excludeidlist', 'fieldname' => 'record_id'],
      ]
    );
    $this->assertStringContainsString('AND (record_id IN (12, 34))', $sql);
    $this->assertStringContainsString('AND record_id NOT IN (56,78)', $sql);
  }

  public function testMalformedIdListIsRejected() {
    $this->expectExceptionMessage('Invalid ID list for report parameter ids');
    $this->mergeQuery(
      'SELECT * FROM records WHERE TRUE #filters#',
      ['ids' => '12, not-an-id'],
      ['ids' => ['datatype' => 'idlist', 'wheres' => [['sql' => 'record_id IN (#ids#)']]]]
    );
  }

  private function mergeQuery($sql, array $params, array $definitions, $db = NULL) {
    $reader = new class($definitions) {

      public $loadStandardParamsSet = FALSE;
      public $loadLegacyStandardParamsSet = FALSE;
      private $params;

      public function __construct(array $params) {
        $this->params = $params;
      }

      public function getParams() {
        return $this->params;
      }

    };
    $class = new ReflectionClass(ReportEngine::class);
    $engine = $class->newInstanceWithoutConstructor();
    foreach (['providedParams' => $params, 'reportReader' => $reader, 'reportDb' => $db] as $name => $value) {
      $property = $class->getProperty($name);
      $property->setAccessible(TRUE);
      $property->setValue($engine, $value);
    }
    $method = $class->getMethod('mergeQueryWithParams');
    $method->setAccessible(TRUE);
    return $method->invoke($engine, $sql, FALSE);
  }

}
