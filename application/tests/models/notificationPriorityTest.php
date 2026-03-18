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

  public function testQueuedEmailOrderingPrioritisesEscalatedRowsOverNull() {
    $db = $this->db;
    $db->query("DELETE FROM email_send_queue");

    $db->query("\
      INSERT INTO email_send_queue
        (status, queued_on, recipients, cc, subject, body, from_email, from_name, escalate_email_priority, email_type, group_key)
      VALUES
        ('Q', now() - interval '2 minutes', '[]', '[]', 'normal queued', 'body', 'noreply@example.com', 'System', NULL, 'notification_emails', 'phpunit-normal'),
        ('Q', now() - interval '1 minutes', '[]', '[]', 'urgent queued', 'body', 'noreply@example.com', 'System', 2, 'notification_emails', 'phpunit-urgent')
    ");

    $rows = $db->query("\
      SELECT subject
      FROM email_send_queue
      WHERE status='Q'
      ORDER BY COALESCE(escalate_email_priority, 0) DESC, queued_on ASC
    ")->result_array(FALSE);

    $subjects = array_map(function ($row) {
      return $row['subject'];
    }, $rows);

    $this->assertEquals(['urgent queued', 'normal queued'], $subjects);
  }

  public function testQueuedEmailOrderingUsesQueuedOnForSamePriority() {
    $db = $this->db;
    $db->query("DELETE FROM email_send_queue");

    $db->query("\
      INSERT INTO email_send_queue
        (status, queued_on, recipients, cc, subject, body, from_email, from_name, escalate_email_priority, email_type, group_key)
      VALUES
        ('Q', now() - interval '5 minutes', '[]', '[]', 'first urgent', 'body', 'noreply@example.com', 'System', 2, 'notification_emails', 'phpunit-urgent-first'),
        ('Q', now() - interval '1 minutes', '[]', '[]', 'second urgent', 'body', 'noreply@example.com', 'System', 2, 'notification_emails', 'phpunit-urgent-second')
    ");

    $rows = $db->query("\
      SELECT subject
      FROM email_send_queue
      WHERE status='Q'
      ORDER BY COALESCE(escalate_email_priority, 0) DESC, queued_on ASC
    ")->result_array(FALSE);

    $subjects = array_map(function ($row) {
      return $row['subject'];
    }, $rows);

    $this->assertEquals(['first urgent', 'second urgent'], $subjects);
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
