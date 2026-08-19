<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

class Scheduled_Tasks_Record_Owner_Test_Emailer extends Emailer {

  public array $sentMessages = [];

  public int $sendResult = 1;

  public function __construct() {
  }

  public function addRecipient($email, $name = NULL) {
    $this->sentMessages[] = [
      'recipient' => $email,
      'name' => $name,
    ];
  }

  public function send($subject, $message, $emailType, $emailSubtype = NULL) {
    $this->sentMessages[count($this->sentMessages) - 1]['subject'] = $subject;
    $this->sentMessages[count($this->sentMessages) - 1]['message'] = $message;
    return $this->sendResult;
  }
}

/**
 * Integration tests for record owner notification processing.
 */
class Controllers_Scheduled_Tasks_Record_Owner_Test extends Indicia_DatabaseTestCase {

  private Database $db;

  private ReflectionMethod $notificationMethod;

  private object $controller;

  public function getDataSet() {
    return new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  }

  public function setUp(): void {
    parent::setUp();
    $this->db = new Database();
    if (!class_exists('Scheduled_Tasks_Controller', FALSE)) {
      require_once 'application/controllers/scheduled_tasks.php';
    }
    $reflectionClass = new ReflectionClass('Scheduled_Tasks_Controller');
    $this->controller = $reflectionClass->newInstanceWithoutConstructor();
    $dbProperty = $reflectionClass->getProperty('db');
    $dbProperty->setAccessible(TRUE);
    $dbProperty->setValue($this->controller, $this->db);
    $lastRunProperty = $reflectionClass->getProperty('lastRunDate');
    $lastRunProperty->setAccessible(TRUE);
    $lastRunProperty->setValue($this->controller, '2000-01-01 00:00:00+00');
    $this->notificationMethod = $reflectionClass->getMethod('doRecordOwnerNotifications');
    $this->notificationMethod->setAccessible(TRUE);
  }

  public function testSendsOneEscapedEmailForDuplicateEmailAttributes() {
    $sampleId = 3;
    $this->db->update('samples', [
      'created_on' => date('Y-m-d H:i:s'),
    ], ['id' => $sampleId]);
    $this->addEmailAttributes($sampleId, [
      'first@example.com',
      'second@example.com',
    ], TRUE);
    $this->db->update('surveys', ['title' => 'Survey <unsafe>'], ['id' => 3]);
    $this->db->update('samples', ['comment' => 'Sample <unsafe>'], ['id' => $sampleId]);
    $this->db->update('occurrences', ['comment' => 'Occurrence <unsafe>'], ['id' => 3]);

    $emailer = new Scheduled_Tasks_Record_Owner_Test_Emailer();
    $this->invokeNotificationMethod($emailer);

    $this->assertCount(1, $emailer->sentMessages);
    $message = $emailer->sentMessages[0]['message'];
    $this->assertStringContainsString('Survey &lt;unsafe&gt;', $message);
    $this->assertStringContainsString('Sample &lt;unsafe&gt;', $message);
    $this->assertStringContainsString('Occurrence &lt;unsafe&gt;', $message);
    $this->assertStringNotContainsString('<unsafe>', $message);
  }

  public function testAdvancesCheckpointWhenNoEmailsAreFound() {
    $checkpoint = '2099-01-01T00:00:00+00:00';
    variable::set('record-owner-notifications', $checkpoint);

    $this->invokeNotificationMethod(new Scheduled_Tasks_Record_Owner_Test_Emailer());

    $this->assertNotSame($checkpoint, variable::get('record-owner-notifications'));
  }

  public function testMissingDetailRowsDoNotCauseEmailProcessing() {
    $sampleId = 3;
    $this->db->update('samples', [
      'created_on' => date('Y-m-d H:i:s'),
    ], ['id' => $sampleId]);
    $this->addEmailAttributes($sampleId, ['missing-detail@example.com'], TRUE);
    $this->db->update('occurrences', ['taxa_taxon_list_id' => 999999], ['id' => 3]);

    $emailer = new Scheduled_Tasks_Record_Owner_Test_Emailer();
    $this->invokeNotificationMethod($emailer);

    $this->assertCount(0, $emailer->sentMessages);
  }

  public function testFailedSendLogsFailureCommentAndRetainsCheckpoint() {
    $sampleId = 3;
    $this->db->update('samples', [
      'created_on' => date('Y-m-d H:i:s'),
    ], ['id' => $sampleId]);
    $this->addEmailAttributes($sampleId, ['failed@example.com'], TRUE);
    $this->enableWorkflowLogging();
    $checkpoint = '2000-01-01T00:00:00+00:00';
    variable::set('record-owner-notifications', $checkpoint);

    $emailer = new Scheduled_Tasks_Record_Owner_Test_Emailer();
    $emailer->sendResult = 0;
    $this->invokeNotificationMethod($emailer);

    $comment = $this->db->query(
      "SELECT comment FROM occurrence_comments WHERE occurrence_id=3 ORDER BY id DESC LIMIT 1"
    )->current();
    $this->assertSame(
      'Sending acknowledgement email to the record contributor was attempted but failed due to an email send failure',
      $comment->comment
    );
    $this->assertSame($checkpoint, variable::get('record-owner-notifications'));
  }

  private function invokeNotificationMethod($emailer) {
    $this->notificationMethod->invoke($this->controller, $emailer);
  }

  private function addEmailAttributes($sampleId, array $emails, $optIn) {
    $optInAttributeId = $this->addSampleAttribute('Email me a copy of the record');
    $emailAttributeId = $this->addSampleAttribute('Email');
    $this->db->insert('sample_attribute_values', [
      'sample_id' => $sampleId,
      'sample_attribute_id' => $optInAttributeId,
      'int_value' => $optIn ? 1 : 0,
      'created_on' => date('Y-m-d H:i:s'),
      'created_by_id' => 1,
      'updated_on' => date('Y-m-d H:i:s'),
      'updated_by_id' => 1,
    ]);
    foreach ($emails as $email) {
      $this->db->insert('sample_attribute_values', [
        'sample_id' => $sampleId,
        'sample_attribute_id' => $emailAttributeId,
        'text_value' => $email,
        'created_on' => date('Y-m-d H:i:s'),
        'created_by_id' => 1,
        'updated_on' => date('Y-m-d H:i:s'),
        'updated_by_id' => 1,
      ]);
    }
  }

  private function addSampleAttribute($caption) {
    $insertResult = $this->db->insert('sample_attributes', [
      'caption' => $caption,
      'data_type' => $caption === 'Email' ? 'T' : 'B',
      'public' => 'false',
      'created_on' => date('Y-m-d H:i:s'),
      'created_by_id' => 1,
      'updated_on' => date('Y-m-d H:i:s'),
      'updated_by_id' => 1,
    ]);
    return $insertResult->insert_id();
  }

  private function enableWorkflowLogging() {
    $this->db->insert('workflow_metadata', [
      'key_value' => 'TESTKEY',
      'entity' => 'occurrence',
      'key' => 'taxa_taxon_list_external_key',
      'log_all_communications' => 't',
      'created_on' => date('Y-m-d H:i:s'),
      'created_by_id' => 1,
      'updated_on' => date('Y-m-d H:i:s'),
      'updated_by_id' => 1,
    ]);
  }

}