<?php

use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that interact with the database.
 *
 * Created as a replacement for Indicia_DatabaseTestCase which depends upon
 * the now obsolete DbUnit. Allows the set up of fixtures from arrays
 * containing data for records in tables.
 */
class SimpleDatabaseTestCase extends TestCase {
  /**
   * One database connection shared by all tests.
   *
   * @var conn
   */
  public static $conn = NULL;

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
   * Set up function called before every test.
   */
  protected function setUp(): void {
    // Load base database fixture which does not change.
    $fixture = $this->getDataSet();
    self::setupFixture($fixture);
  }

  /**
   * Set up database fixture defined in file.
   */
  private static function setupFixture($fixture) {

    // Remove any pre-existing data in fixture tables.
    self::deleteFixture($fixture);

    // Set up fixture.
    foreach ($fixture as $table => $records) {
      foreach ($records as $record) {
        if (!pg_insert(self::$conn, 'indicia.' . $table, $record)) {
          throw new Exception("Failed inserting $record into $table");
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

}
