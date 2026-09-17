<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

/**
 * Tests pagination of skipped iNaturalist records during a redo run.
 */
class RestApiSyncRemoteInaturalistTest extends Indicia_DatabaseTestCase {

  /**
   * Database connection used to arrange skipped-record test data.
   *
   * @var Database
   */
  private static $db;

  /**
   * Creates the database connection used by the test class.
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    self::$db = new Database();
  }

  /**
   * Loads the core fixture required by the database test framework.
   *
   * @return PHPUnit\DbUnit\DataSet\YamlDataSet
   *   The database fixture dataset.
   */
  public function getDataSet() {
    return new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  }

  /**
   * Removes skipped records before each test.
   */
  public function setUp(): void {
    parent::setUp();
    self::$db->query('DELETE FROM rest_api_sync_skipped_records');
  }

  /**
   * Removes skipped records after each test.
   */
  public function tearDown(): void {
    self::$db->query('DELETE FROM rest_api_sync_skipped_records');
    parent::tearDown();
  }

  /**
   * Checks that a short page advances the skipped-record cursor.
   */
  public function testSkippedRecordPageAdvancesCursorOnShortPage() {
    $this->insertSkippedRecord('INAT', '101');
    $lastId = $this->insertSkippedRecord('INAT', '102');

    $sourceIds = $this->getNextPageOfSkippedRecords('INAT', 0);

    $this->assertSame(['101', '102'], $sourceIds);
    $this->assertSame((int) $lastId, $this->getLastSkippedRecordId());
  }

  /**
   * Checks that a full page advances the skipped-record cursor.
   */
  public function testSkippedRecordPageAdvancesCursorOnFullPage() {
    for ($sourceId = 1; $sourceId <= INAT_PAGE_SIZE; $sourceId++) {
      $lastId = $this->insertSkippedRecord('INAT', (string) $sourceId);
    }

    $sourceIds = $this->getNextPageOfSkippedRecords('INAT', 0);

    $this->assertCount(INAT_PAGE_SIZE, $sourceIds);
    $this->assertSame((int) $lastId, $this->getLastSkippedRecordId());
  }

  /**
   * Checks that pagination excludes rows at or before the supplied cursor.
   */
  public function testSkippedRecordPageStartsAfterSuppliedCursor() {
    $firstId = $this->insertSkippedRecord('INAT', '101');
    $this->insertSkippedRecord('INAT', '102');
    $lastId = $this->insertSkippedRecord('INAT', '103');

    $sourceIds = $this->getNextPageOfSkippedRecords('INAT', $firstId);

    $this->assertSame(['102', '103'], $sourceIds);
    $this->assertSame((int) $lastId, $this->getLastSkippedRecordId());
  }

  /**
   * Checks that duplicate source IDs are returned once while the row cursor
   * still advances to the final skipped-record row.
   */
  public function testSkippedRecordPageDeduplicatesSourceIdsButKeepsLastRowCursor() {
    $this->insertSkippedRecord('INAT', '101');
    $this->insertSkippedRecord('INAT', '101');
    $lastId = $this->insertSkippedRecord('INAT', '102');

    $sourceIds = $this->getNextPageOfSkippedRecords('INAT', 0);

    $this->assertSame(['101', '102'], $sourceIds);
    $this->assertSame((int) $lastId, $this->getLastSkippedRecordId());
  }

  /**
   * Checks that another retry failure updates rather than re-queues the row.
   */
  public function testRepeatedFailureUpdatesCurrentSkippedRecord() {
    $skippedRecordId = $this->insertSkippedRecord('INAT', '101');

    $this->updatePreviousErrors('INAT', 101, 'Latest error');

    $rows = self::$db->query(
      "SELECT id, error_message, current FROM rest_api_sync_skipped_records WHERE server_id='INAT' AND source_id='101'"
    )->result_array(FALSE);
    $this->assertCount(1, $rows);
    $this->assertSame((int) $skippedRecordId, (int) $rows[0]['id']);
    $this->assertSame('Latest error', $rows[0]['error_message']);
    $this->assertSame('t', $rows[0]['current']);
  }

  /**
   * Inserts a current skipped occurrence for a test server.
   *
   * @param string $serverId
   *   Server identifier used by the redo configuration.
   * @param string $sourceId
   *   Source record identifier.
   *
   * @return int
   *   The inserted skipped-record ID.
   */
  private function insertSkippedRecord($serverId, $sourceId) {
    $result = self::$db->query(<<<SQL
      INSERT INTO rest_api_sync_skipped_records (
        server_id,
        source_id,
        dest_table,
        error_message,
        current,
        created_on,
        created_by_id,
        updated_on,
        updated_by_id
      ) VALUES (?, ?, 'occurrences', 'Test error', true, now(), 1, now(), 1)
    SQL, [$serverId, $sourceId]);
    return $result->insert_id();
  }

  /**
   * Invokes the private helper that loads the next skipped-record page.
   *
   * @param string $serverId
   *   Server identifier used by the redo configuration.
   * @param int $fromId
   *   Last skipped-record ID already processed.
   *
   * @return array
   *   Unique source IDs from the next page.
   */
  private function getNextPageOfSkippedRecords($serverId, $fromId) {
    $method = new ReflectionMethod(
      'rest_api_sync_remote_inaturalist',
      'getNextPageOfSkippedRecords'
    );
    return $method->invoke(null, self::$db, ['redoServer' => $serverId], $fromId);
  }

  /**
   * Reads the private helper cursor after loading a page.
   *
   * @return int|null
   *   The last skipped-record ID recorded by the helper.
   */
  private function getLastSkippedRecordId() {
    $property = new ReflectionProperty(
      'rest_api_sync_remote_inaturalist',
      'lastSkippedRecordId'
    );
    return $property->getValue();
  }

  /**
   * Invokes the private helper that updates a repeated retry failure.
   */
  private function updatePreviousErrors($serverId, $sourceId, $errorMessage) {
    $method = new ReflectionMethod(
      'rest_api_sync_remote_inaturalist',
      'updatePreviousErrors'
    );
    $method->invoke(null, self::$db, $sourceId, ['redoServer' => $serverId], $errorMessage, 1);
  }

}