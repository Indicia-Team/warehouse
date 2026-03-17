<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for email priority escalation helper logic.
 */
class Helper_Emailer_Priority_Test extends TestCase {

  /**
   * Reflection class for Emailer.
   *
   * @var ReflectionClass
   */
  private ReflectionClass $reflectionClass;

  /**
   * Reflection object for Emailer instance methods.
   *
   * @var object
   */
  private object $emailerObject;

  public function setUp(): void {
    if (!class_exists('Emailer', FALSE)) {
      require_once 'application/libraries/Emailer.php';
    }
    $this->reflectionClass = new ReflectionClass('Emailer');
    // Avoid constructor dependencies on runtime config.
    $this->emailerObject = $this->reflectionClass->newInstanceWithoutConstructor();
  }

  public function testDeriveEscalatePriorityFromForgottenPassword() {
    $method = $this->reflectionClass->getMethod('deriveEscalateEmailPriority');
    $method->setAccessible(TRUE);
    $result = $method->invoke(NULL, 'forgottenPassword', 3);
    $this->assertSame(1, $result);
  }

  public function testDeriveEscalatePriorityFromHighSendPriority() {
    $method = $this->reflectionClass->getMethod('deriveEscalateEmailPriority');
    $method->setAccessible(TRUE);
    $result = $method->invoke(NULL, 'notification_emails', 2);
    $this->assertSame(2, $result);
  }

  public function testDeriveEscalatePriorityForNormalEmail() {
    $method = $this->reflectionClass->getMethod('deriveEscalateEmailPriority');
    $method->setAccessible(TRUE);
    $result = $method->invoke(NULL, 'notification_emails', 4);
    $this->assertNull($result);
  }

  public function testQueueMergeKeyStableForSamePayload() {
    $subject = 'Subject';
    $emailType = 'notification_emails';
    $emailSubtype = 'C';
    $escalatePriority = 2;

    $this->setEmailerProperties('sender@example.com', 'Sender', [['a@example.com', 'A']], [['c@example.com', 'C']], NULL);

    $method = $this->reflectionClass->getMethod('getQueueMergeKey');
    $method->setAccessible(TRUE);

    $firstKey = $method->invoke($this->emailerObject, $subject, $emailType, $emailSubtype, $escalatePriority);
    $secondKey = $method->invoke($this->emailerObject, $subject, $emailType, $emailSubtype, $escalatePriority);
    $this->assertSame($firstKey, $secondKey);
  }

  public function testQueueMergeKeyChangesWhenEscalationChanges() {
    $subject = 'Subject';
    $emailType = 'notification_emails';
    $emailSubtype = 'C';

    $this->setEmailerProperties('sender@example.com', 'Sender', [['a@example.com', 'A']], [['c@example.com', 'C']], NULL);

    $method = $this->reflectionClass->getMethod('getQueueMergeKey');
    $method->setAccessible(TRUE);

    $normalKey = $method->invoke($this->emailerObject, $subject, $emailType, $emailSubtype, NULL);
    $urgentKey = $method->invoke($this->emailerObject, $subject, $emailType, $emailSubtype, 2);
    $this->assertNotSame($normalKey, $urgentKey);
  }

  /**
   * Populate the Emailer object with fields used in queue merge key generation.
   *
   * @param string $from
   *   Sender email address.
   * @param string $fromName
   *   Sender name.
   * @param array $recipients
   *   List of recipients.
   * @param array $cc
   *   List of copied recipients.
   * @param array|null $attachmentInfo
   *   Attachment metadata.
   */
  private function setEmailerProperties($from, $fromName, array $recipients, array $cc, ?array $attachmentInfo) {
    $this->setPrivateProperty('from', $from);
    $this->setPrivateProperty('fromName', $fromName);
    $this->setPrivateProperty('recipients', $recipients);
    $this->setPrivateProperty('cc', $cc);
    $this->setPrivateProperty('attachmentInfo', $attachmentInfo);
  }

  /**
   * Sets a private property on the reflected Emailer object.
   *
   * @param string $property
   *   Property name.
   * @param mixed $value
   *   Value to assign.
   */
  private function setPrivateProperty($property, $value) {
    $reflectionProperty = $this->reflectionClass->getProperty($property);
    $reflectionProperty->setAccessible(TRUE);
    $reflectionProperty->setValue($this->emailerObject, $value);
  }

}
