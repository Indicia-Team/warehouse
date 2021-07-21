<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit test class for survey_structure_export module.
 */
class SurveyStructureExportTest extends TestCase {

  /**
   * One database connection shared by all tests.
   *
   * @var conn
   */
  public static $conn = NULL;

  /**
   * One database fixture shared by all tests.
   *
   * @var conn
   */
  public static $fixture;

  /**
   * Set up function called once before first test.
   */
  public static function setUpBeforeClass(): void {
    $host = 'host = 127.0.0.1';
    $dbname = 'dbname = indicia';
    $user = 'user = indicia_user';
    $pass = 'password = indicia_user_pass';
    self::$conn = pg_connect("$host $dbname $user $pass");
  }

  /**
   * Set up function called before each tests.
   */
  protected function setUp(): void {
    // Load local fixture file.
    $this->setupFixture('modules/survey_structure_export/tests/base_fixture.php');
  }

  /**
   * Set up database fixture defined in file.
   */
  private function setupFixture($filename) {
    require_once $filename;

    // Drop content in fixture tables.
    $tables = array_keys($fixture);
    $table_list = implode(',', $tables);
    pg_query(self::$conn, "TRUNCATE TABLE $table_list CASCADE");
    // And reset sequences.
    foreach ($tables as $table) {
      $seq = $table . '_id_seq';
      pg_query(self::$conn, "SELECT setval('$seq', 1, false)");
    }

    // Set up fixture.
    foreach ($fixture as $table => $records) {
      foreach ($records as $record) {
        if (!pg_insert(self::$conn, 'indicia.' . $table, $record)) {
          throw new Exception("Failed inserting $r into $table");
        }
      }
    }
  }

  /**
   * Test survey export with no attributes.
   */
  public function testExportNoAttributes() {
    $controller = new Survey_structure_export_Controller();
    $export = $controller->getSurveyAttributes(1, 1);
    $expected = [
      'srvAttrs' => [],
      'smpAttrs' => [],
      'occAttrs' => [],
    ];

    $this->assertEqualsCanonicalizing($expected, $export);
  }

}
