<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

/**
 * Integration tests for notification escalation ordering.
 */
class Models_Notification_Priority_Test extends Indicia_DatabaseTestCase {

  /**
   * Database connection.
   *
   * @var Database
   */
  private Database $db;

  public function getDataSet() {
    return new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  }

  public function setUp(): void {
    parent::setUp();
    $this->db = new Database();
  }

  public function testEscalatePriorityOrderingWithNullLast() {
    $source = 'phpunit-priority-order';

    $this->insertNotification($source, 1, NULL);
    $this->insertNotification($source, 1, 2);
    $this->insertNotification($source, 1, 1);

    $rows = $this->db->query(
      "SELECT escalate_email_priority
      FROM notifications
      WHERE source='$source'
      ORDER BY escalate_email_priority DESC NULLS LAST, user_id, source_type, id"
    )->result_array(FALSE);

    $orderedPriorities = array_map(function ($row) {
      return $row['escalate_email_priority'];
    }, $rows);

    $this->assertEquals([2, 1, NULL], $orderedPriorities);
  }

  /**
   * Inserts a notification row for ordering tests.
   *
   * @param string $source
   *   Source label.
   * @param int $userId
   *   User ID.
   * @param int|null $escalateEmailPriority
   *   Escalation priority.
   */
  private function insertNotification($source, $userId, $escalateEmailPriority) {
    $this->db->insert('notifications', [
      'source' => $source,
      'source_type' => 'Q',
      'data' => '{"message":"test"}',
      'user_id' => $userId,
      'linked_id' => 1,
      'digest_mode' => 'I',
      'acknowledged' => 'f',
      'email_sent' => 'f',
      'triggered_on' => date('Y-m-d H:i:s'),
      'escalate_email_priority' => $escalateEmailPriority,
    ]);
  }

}
