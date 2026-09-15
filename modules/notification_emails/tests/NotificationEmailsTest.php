<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for notification email formatting helpers.
 */
class Notification_Emails_Test extends TestCase {

  public static function setUpBeforeClass(): void {
    require_once DOCROOT . 'modules/notification_emails/plugins/notification_emails.php';
  }

  public function testSafeEscapePreservesAllowedElementsAndWhitespaceVariants() {
    $comment = '<EM>Species</EM><br />Reviewed < p >today</P><strong>Thanks</strong>';

    $result = notification_emails_safe_escape($comment);

    $this->assertSame(
      '<em>Species</em><br/>Reviewed <p>today</p><strong>Thanks</strong>',
      $result
    );
  }

  public function testSafeEscapeEscapesDisallowedElementsAndAttributes() {
    $comment = '<a href="https://example.com">link</a><em class="unsafe">text</em><script>alert(1)</script>';

    $result = notification_emails_safe_escape($comment);

    $this->assertSame(
      '&lt;a href=&quot;https://example.com&quot;&gt;link&lt;/a&gt;'
        . '&lt;em class=&quot;unsafe&quot;&gt;text&lt;/em&gt;'
        . '&lt;script&gt;alert(1)&lt;/script&gt;',
      $result
    );
  }

  public function testSafeEscapeLeavesPlainTextUnchanged() {
    $comment = 'Your record was examined by an expert.';

    $this->assertSame($comment, notification_emails_safe_escape($comment));
  }

  public function testSafeEscapePreservesConfiguredLink() {
    $comment = '<a target="_blank" href="https://example.com/verification">Review records</a>';

    $result = notification_emails_safe_escape($comment, ['https://example.com/verification']);

    $this->assertSame(
      '<a href="https://example.com/verification" target="_blank" rel="noopener noreferrer">Review records</a>',
      $result
    );
  }

  public function testSafeEscapePreservesConfiguredLinkPrefix() {
    $comment = '<a href="https://example.com/record-details?id=123">View record</a>';

    $result = notification_emails_safe_escape($comment, ['https://example.com/record-details*']);

    $this->assertSame('<a href="https://example.com/record-details?id=123">View record</a>', $result);
  }

  public function testSafeEscapeEscapesUnconfiguredAndUnsafeLinks() {
    $allowedUrls = ['https://example.com/verification'];

    $this->assertSame(
      '&lt;a href=&quot;https://example.com/verification-extra&quot;&gt;Wrong link&lt;/a&gt;',
      notification_emails_safe_escape(
        '<a href="https://example.com/verification-extra">Wrong link</a>',
        $allowedUrls
      )
    );
    $this->assertSame(
      '&lt;a href=&quot;javascript:alert(1)&quot;&gt;Unsafe link&lt;/a&gt;',
      notification_emails_safe_escape('<a href="javascript:alert(1)">Unsafe link</a>', ['javascript:*'])
    );
  }

  public function testNotificationTypesIncludeGroupUserNotifications() {
    $types = notification_emails::getNotificationTypes();

    $this->assertSame('Pending users in groups you administer', $types['GU']['title']);
  }

  public function testRecordStatusesIncludeSubstatuses() {
    $statuses = notification_emails::getRecordStatuses();

    $this->assertSame('Accepted as correct', $statuses['V1']);
    $this->assertSame('Not accepted as incorrect', $statuses['R5']);
  }

}
