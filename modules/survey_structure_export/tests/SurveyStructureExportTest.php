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

    // Load base database fixture which does not change.
    self::setupFixture('base_fixture.php');
  }

  /**
   * Set up function called before each tests.
   */
  protected function setUp(): void {
  }

  /**
   * Set up database fixture defined in file.
   */
  private static function setupFixture($filename) {
    $filename = "modules/survey_structure_export/tests/fixtures/$filename";
    require_once $filename;

    // Drop content in fixture tables.
    $tables = array_keys($fixture);
    $table_list = implode(',', $tables);
    pg_query(self::$conn, "TRUNCATE TABLE $table_list CASCADE");
    // And reset primary key sequences.
    foreach ($tables as $table) {
      if (substr($table, 0, 6) !== 'cache_') {
        // cache tables don't have their own sequences.
        $seq = $table . '_id_seq';
        pg_query(self::$conn, "SELECT setval('$seq', 1, false)");
      }
    }

    // Set up fixture.
    foreach ($fixture as $table => $records) {
      foreach ($records as $record) {
        if (!pg_insert(self::$conn, 'indicia.' . $table, $record)) {
          throw new Exception("Failed inserting $r into $table");
        }
      }
    }

    if (isset($expected)) {
      return $expected;
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

  /**
   * Test survey export with text attributes.
   */
  public function testExportTextAttributes() {
    $expected = self::setupFixture('text_attribute_fixture.php');
    $controller = new Survey_structure_export_Controller();
    $export = $controller->getSurveyAttributes(1, 1);

    $this->assertEqualsCanonicalizing($expected, $export);
  }

  /**
   * Test survey export with termlist attributes.
   */
  public function testExportTermlistAttributes() {
    $expected = self::setupFixture('termlist_attribute_fixture.php');
    $controller = new Survey_structure_export_Controller();
    $export = $controller->getSurveyAttributes(1, 1);

    $this->assertEqualsCanonicalizing($expected, $export);
  }

}
