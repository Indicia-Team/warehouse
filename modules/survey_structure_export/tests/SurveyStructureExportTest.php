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
    require $filename;

    // Remove any pre-existing data in fixture tables.
    self::deleteFixture($fixture);

    // Set up fixture.
    foreach ($fixture as $table => $records) {
      foreach ($records as $record) {
        if (!pg_insert(self::$conn, 'indicia.' . $table, $record)) {
          throw new Exception("Failed inserting $r into $table");
        }
      }
    }

    // Fixture files may also contain the expected form of the survey export
    // for the configuration established by the fixture.
    if (isset($export)) {
      return $export;
    }
  }

  /**
   * Delete database fixture.
   */
  private static function deleteFixture($fixture) {
    // Drop content in fixture tables.
    $tables = array_keys($fixture);
    $table_list = implode(',', $tables);
    pg_query(self::$conn, "TRUNCATE TABLE $table_list CASCADE");
    // And reset primary key sequences.
    foreach ($tables as $table) {
      if (substr($table, 0, 6) !== 'cache_') {
        // Cache tables don't have their own sequences.
        $seq = $table . '_id_seq';
        pg_query(self::$conn, "SELECT setval('$seq', 1, false)");
      }
    }

    // Clear the cache to ensure tests use new database contents.
    $cache = Cache::instance();
    $cache->delete_all();

  }

  /**
   * Remove white space from a human-readable json string.
   */
  private static function trimJson($string) {
    $json = preg_replace('/\n\s*/', '', $string);
    $json = preg_replace('/":\s+/', '":', $json);
    return $json;
  }

  /**
   * Test survey export with no attributes.
   */
  public function testExportNoAttributes() {
    $controller = new Survey_structure_export_Controller();
    $controller->uri::$current_uri = 'survey_structure_export/1';
    $controller->uri::setup();
    $controller->index();
    $export = $controller->view->export;
    $expected = self::trimJson('{
      "srvAttrs": [],
      "smpAttrs": [],
      "occAttrs": []
    }');

    $this->assertEquals($expected, $export);
  }

  /**
   * Test survey export with text attributes.
   */
  public function testExportTextAttributes() {
    $expected = self::setupFixture('text_attribute_fixture.php');
    $expected = self::trimJson($expected);

    $controller = new Survey_structure_export_Controller();
    $controller->uri::$current_uri = 'survey_structure_export/1';
    $controller->uri::setup();
    $controller->index();
    $export = $controller->view->export;

    $this->assertEquals($expected, $export);
  }

  /**
   * Test survey export with termlist attributes.
   */
  public function testExportTermlistAttributes() {
    $expected = self::setupFixture('termlist_attribute_fixture.php');
    $expected = self::trimJson($expected);

    $controller = new Survey_structure_export_Controller();
    $controller->uri::$current_uri = 'survey_structure_export/1';
    $controller->uri::setup();
    $controller->index();
    $export = $controller->view->export;

    $this->assertEquals($expected, $export);
  }

  /**
   * Test survey import with various fixtures.
   */
  public function testImportSurvey() {
    $files = [
      'text_attribute_fixture.php',
      'termlist_attribute_fixture.php',
    ];

    foreach ($files as $file) {
      print("Testing fixture $file\n");
      $filepath = "modules/survey_structure_export/tests/fixtures/$file";
      require $filepath;

      // Reset the tables we are going to import into.
      self::deleteFixture($fixture);

      // Do the import.
      $controller = new Survey_structure_export_Controller();
      $_POST['survey_id'] = 1;
      $_POST['import_survey_structure'] = $export;
      $controller->save($export);

      // Test the database matches the fixture.
      foreach ($fixture as $table => $records) {
        if (substr($table, 0, 6) !== 'cache_') {
          // Import does not create cache tables.
          print("..Testing table $table\n");
          foreach ($records as $i => $record) {
            unset($record['created_on'], $record['updated_on']);
            $fields = array_keys($record);
            $field_list = implode(',', $fields);
            $query = "SELECT $field_list FROM $table WHERE id = " . ($i + 1);
            $result = pg_query(self::$conn, $query);
            if ($result === FALSE) {
              print("Failed query: $query");
            }
            $actual = pg_fetch_array($result, NULL, PGSQL_ASSOC);
            if ($actual === FALSE) {
              print("No results to query: $query");
            }
            // pg_fetch_array returns each value as a string.
            $expected = array_map('strval', $record);

            $this->assertEqualsCanonicalizing($expected, $actual);
          }
        }
      }
    }
  }

}
