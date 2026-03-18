<?php

/**
 * @file
 * Email sending library class.
 *
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Email sending library.
 *
 * Allows a single set of function calls to send emails using different email
 * backends depending on configuration.
 */
class Emailer {

  private static $db;

  /**
   * Queued email status: pending send.
   */
  private const QUEUE_STATUS_QUEUED = 'Q';

  /**
   * Queued email status: permanently failed.
   */
  private const QUEUE_STATUS_FAILED = 'F';

  /**
   * Name of the email helper class, e.g. for Swift or MS Graph connections.
   *
   * @var string
   */
  private $emailHelper;

  /**
   * Any attachment to add to the email.
   *
   * Contains the data for the attachment, the filename and mime type.
   *
   * @var array|null
   */
  private ?array $attachmentInfo = NULL;

  /**
   * List of recipients, each containing an email address and optional name.
   *
   * @var array
   */
  private array $recipients = [];

  /**
   * List of copied recipients each containing an email and optional name.
   *
   * @var array
   */
  private array $cc = [];

  /**
   * Email sent from address.
   *
   * @var string
   */
  private $from = NULL;

  /**
   * Email sent from name.
   *
   * @var string
   */
  private $fromName = NULL;

  /**
   * Priority from 1 (very high) to 5 (very low).
   *
   * @var int
   */
  private $priority = 3;


  /**
   * Constructor - initialise the library we are going to use.
   */
  public function __construct() {
    $emailLibrary = kohana::config('email.library', FALSE, FALSE) ?? 'Swift';
    $this->emailHelper = "emailer$emailLibrary";
    $this->emailHelper::init();
  }

  /**
   * Reset any details set for the next email.
   */
  public function reset() {
    $this->recipients = [];
    $this->cc = [];
    $this->from = NULL;
    $this->fromName = NULL;
    $this->attachmentInfo = NULL;
  }

  /**
   * Send an email with the supplied subject and message.
   *
   * The email recipients should have already been set, as well as optionally
   * the reply to, cc, and from emails.
   *
   * @param string $subject
   *   Email subject.
   * @param string $message
   *   Email message.
   * @param string $emailType
   *   System component that caused the email, e.g. notifications.
   * @param string $emailSubtype
   *   Optional additional info to identify the source of the email, e.g. the
   *   notification type.
   *
   * @return int
   *   The number of recipients who have been sent emails - 0 if an error
   *   occurred.
   */
  public function send($subject, $message, $emailType, $emailSubtype = NULL) {
    $config = kohana::config('email');
    $recipientCount = count($this->recipients);
    $succeeded = FALSE;
    $deferred = FALSE;
    $errorMessage = NULL;
    if (!$this->from) {
      $this->from = $config['address'];
      $this->fromName = $config['server_name'];
    }
    if ($config['do_not_send'] ?? FALSE) {
      // Email disabled on this server, this classes as a success.
      return count($this->recipients);
    }
    $emailLibrary = $config['library'] ?? 'Swift';
    $emailHelper = "emailer$emailLibrary";
    try {
      if (empty($this->recipients) || empty($message)) {
        throw new Exception('Email incomplete - missing recipient or message');
      }
      if ($this->shouldQueueEmail($emailType)) {
        $this->queueEmail($subject, $message, $emailType, $emailSubtype);
        $errorMessage = 'Deferred due to hourly email limit';
        $deferred = TRUE;
      }
      else {
        $emailHelper::send(
          $subject,
          $message,
          $this->recipients,
          $this->cc,
          $this->from,
          $this->fromName,
          $this->priority,
          $this->attachmentInfo
        );
        $succeeded = TRUE;
        if ($config['enable_send_rate_limit'] ?? FALSE) {
          self::incrementSentThisHour();
        }
      }
    }
    catch (Exception $e) {
      error_logger::log_error('Error in email helper', $e);
      $errorMessage = $e->getMessage();
    }
    finally {
      if (($config['log_emails'] ?? FALSE) && !$deferred) {
        $this->logEmail($subject, $message, $emailType, $emailSubtype, $errorMessage);
      }
      // Now reset the emailer for next time.
      $this->reset();
    }
    return $succeeded ? $recipientCount : 0;
  }

  /**
   * Process queued emails, e.g. when scheduled tasks runs.
   *
   * @return array
   *   Keys sent and attempted.
   */
  public static function processQueue() {
    $config = kohana::config('email');
    if (!($config['enable_send_rate_limit'] ?? FALSE)) {
      return ['attempted' => 0, 'sent' => 0];
    }
    self::initDb();
    $hourlyLimit = $config['hourly_send_limit'] ?? 250;
    $criticalReserve = max(0, $config['hourly_critical_reserve'] ?? 20);
    $nonCriticalThreshold = self::getNonCriticalThreshold($hourlyLimit, $criticalReserve);
    $batchSize = $config['queue_replay_batch_size'] ?? 250;
    $alreadySent = self::countSentThisHour();
    if ($alreadySent >= $hourlyLimit) {
      return ['attempted' => 0, 'sent' => 0];
    }
    $safeBatchSize = (int) $batchSize;
    $queueItems = self::$db->query(
      "SELECT *
      FROM email_send_queue
      WHERE status='" . self::QUEUE_STATUS_QUEUED . "'
      ORDER BY COALESCE(escalate_email_priority, 0) DESC, queued_on ASC
      LIMIT $safeBatchSize"
    );
    $attempted = 0;
    $sent = 0;
    $emailLibrary = $config['library'] ?? 'Swift';
    $emailHelper = "emailer$emailLibrary";
    $emailHelper::init();
    foreach ($queueItems as $item) {
      if ($alreadySent >= $hourlyLimit) {
        break;
      }
      $isCritical = (int) $item->escalate_email_priority > 0;
      if (!$isCritical && $alreadySent >= $nonCriticalThreshold) {
        continue;
      }
      $attempted++;
      try {
        $sendPriority = ((int) $item->escalate_email_priority === 2) ? 2 : 3;
        $emailHelper::send(
          $item->subject,
          $item->body,
          json_decode($item->recipients, TRUE) ?: [],
          json_decode($item->cc, TRUE) ?: [],
          $item->from_email,
          $item->from_name,
          $sendPriority,
          empty($item->attachment_info) ? NULL : json_decode($item->attachment_info, TRUE)
        );
        self::$db
          ->set([
            'status' => 'S',
            'sent_on' => date('Y-m-d H:i:s'),
            'error_message' => NULL,
          ])
          ->from('email_send_queue')
          ->where('id', $item->id)
          ->update();
        self::insertEmailLog(
          $item->subject,
          $item->body,
          $item->email_type,
          $item->email_subtype,
          json_decode($item->recipients, TRUE) ?: [],
          json_decode($item->cc, TRUE) ?: [],
          $item->from_email,
          $item->from_name,
          NULL
        );
        $sent++;
        $alreadySent++;
        self::incrementSentThisHour();
      }
      catch (Exception $e) {
        $attempts = (int) $item->attempts + 1;
        $status = $attempts >= 10 ? self::QUEUE_STATUS_FAILED : self::QUEUE_STATUS_QUEUED;
        self::$db
          ->set([
            'attempts' => $attempts,
            'status' => $status,
            'error_message' => $e->getMessage(),
          ])
          ->from('email_send_queue')
          ->where('id', $item->id)
          ->update();
        error_logger::log_error('Failed sending queued email', $e);
      }
    }
    return ['attempted' => $attempted, 'sent' => $sent];
  }

  /**
   * Decide if this email should be queued due to throttling.
   *
   * @param string $emailType
   *   System component that caused the email.
   *
   * @return bool
   *   TRUE if email should be deferred to the queue.
   */
  private function shouldQueueEmail($emailType) {
    $config = kohana::config('email');
    if (!($config['enable_send_rate_limit'] ?? FALSE)) {
      return FALSE;
    }
    self::initDb();
    $hourlyLimit = $config['hourly_send_limit'] ?? 250;
    $criticalReserve = max(0, $config['hourly_critical_reserve'] ?? 20);
    $nonCriticalThreshold = self::getNonCriticalThreshold($hourlyLimit, $criticalReserve);
    $sentCount = self::countSentThisHour();
    $escalatePriority = self::deriveEscalateEmailPriority($emailType, $this->priority);
    $isCritical = $escalatePriority !== NULL;
    if ($isCritical) {
      return $sentCount >= $hourlyLimit;
    }
    return $sentCount >= $nonCriticalThreshold;
  }

  /**
   * Compute threshold where non-critical traffic starts queueing.
   *
   * Guards against reserve values that would consume all non-critical
   * capacity by ensuring at least one non-critical slot remains when
   * hourly limit is above zero.
   *
   * @param int $hourlyLimit
   *   Maximum number of emails that can be sent in the hour.
   * @param int $criticalReserve
   *   Reserved email slots for critical traffic.
   *
   * @return int
   *   Number of sent emails at which non-critical emails should queue.
   */
  private static function getNonCriticalThreshold($hourlyLimit, $criticalReserve) {
    if ($hourlyLimit > 0) {
      $criticalReserve = min($criticalReserve, $hourlyLimit - 1);
    }
    return max(0, $hourlyLimit - $criticalReserve);
  }

  /**
   * Queue an email payload for replay.
   *
   * @param string $subject
   *   Email subject.
   * @param string $message
   *   Email body.
   * @param string $emailType
   *   System component that caused the email.
   * @param string $emailSubtype
   *   Optional email subtype.
   */
  private function queueEmail($subject, $message, $emailType, $emailSubtype) {
    self::initDb();
    $escalatePriority = self::deriveEscalateEmailPriority($emailType, $this->priority);
    $groupKey = $this->getQueueMergeKey($subject, $emailType, $emailSubtype, $escalatePriority);
    $existing = self::$db
      ->select('id, body')
      ->from('email_send_queue')
      ->where([
        'status' => self::QUEUE_STATUS_QUEUED,
        'group_key' => $groupKey,
      ])
      ->orderby('queued_on', 'DESC')
      ->limit(1)
      ->get()
      ->current();
    if ($existing) {
      self::$db
        ->set('body', $existing->body . '<hr/>' . $message)
        ->from('email_send_queue')
        ->where('id', $existing->id)
        ->update();
      return;
    }
    self::$db->insert('email_send_queue', [
      'status' => self::QUEUE_STATUS_QUEUED,
      'queued_on' => date('Y-m-d H:i:s'),
      'recipients' => json_encode($this->recipients),
      'cc' => json_encode($this->cc),
      'subject' => $subject,
      'body' => $message,
      'from_email' => $this->from,
      'from_name' => $this->fromName,
      'escalate_email_priority' => $escalatePriority,
      'attachment_info' => empty($this->attachmentInfo) ? NULL : json_encode($this->attachmentInfo),
      'email_type' => $emailType,
      'email_subtype' => $emailSubtype,
      'group_key' => $groupKey,
    ]);
  }

  /**
   * Build a merge key for queue coalescing.
   *
   * @param string $subject
   *   Email subject.
   * @param string $emailType
   *   System component that caused the email.
   * @param string $emailSubtype
   *   Optional email subtype.
   * @param int|null $escalatePriority
   *   Escalation priority value.
   *
   * @return string
   *   Grouping key for queued rows.
   */
  private function getQueueMergeKey($subject, $emailType, $emailSubtype, $escalatePriority) {
    // Only notification emails are merged.
    if ($emailType !== 'notification_emails') {
      return uniqid('', TRUE);
    }
    return sha1(json_encode([
      $subject,
      $emailType,
      $emailSubtype,
      $this->from,
      $this->fromName,
      $this->recipients,
      $this->cc,
      $escalatePriority,
      empty($this->attachmentInfo),
    ]));
  }

  /**
   * Count successfully sent emails in the current hour.
    *
    * @return int
    *   Count of emails sent this hour.
   */
  private static function countSentThisHour() {
    $counterData = variable::get('email-send-hourly-counter', NULL, FALSE);
    if (empty($counterData)) {
      return 0;
    }
    $parts = explode(':', $counterData);
    $hour = $parts[0] ?? '';
    $count = $parts[1] ?? 0;
    if ($hour !== date('YmdH')) {
      return 0;
    }
    return (int) $count;
  }

  /**
   * Increment hourly send counter.
   */
  private static function incrementSentThisHour() {
    $currentHour = date('YmdH');
    $counterData = variable::get('email-send-hourly-counter', NULL, FALSE);
    if (empty($counterData)) {
      variable::set('email-send-hourly-counter', "$currentHour:1");
      return;
    }
    $parts = explode(':', $counterData);
    $hour = $parts[0] ?? '';
    $count = (int) ($parts[1] ?? 0);
    if ($hour !== $currentHour) {
      variable::set('email-send-hourly-counter', "$currentHour:1");
    }
    else {
      variable::set('email-send-hourly-counter', "$currentHour:" . ($count + 1));
    }
  }

  /**
   * Derive escalation priority from email metadata.
   *
   * @param string $emailType
   *   System component that caused the email.
   * @param int $priority
   *   Email helper priority from 1 (high) to 5 (low).
   *
   * @return int|null
   *   NULL for normal emails, 1 for urgent send, 2 for urgent + high priority.
   */
  private static function deriveEscalateEmailPriority($emailType, $priority) {
    if ((int) $priority <= 2) {
      return 2;
    }
    if ($emailType === 'forgottenPassword') {
      return 1;
    }
    return NULL;
  }

  /**
   * Ensure static DB connection is available.
   */
  private static function initDb() {
    if (!self::$db) {
      self::$db = new Database();
    }
  }

  /**
   * Stores the output of a query ready to attach as a CSV to the sent email.
   *
   * @param string $query
   *   SQL statement to run to get the data.
   * @param string $filename
   *   Name of the file to save the CSV as.
   * @param Database $db
   *   Database connection to use.
   */
  public function addAttachmentFromQuery($query, $filename, Database $db) {
    $result = $db->query($query)->result();
    $data = '';

    // Add header row with column names
    if (!empty($result)) {
      $headers = array_map(function($field) {
        return '"' . str_replace('"', '""', $field) . '"';
      }, array_keys((array) $result[0]));
      $data .= implode(',', $headers) . "\n";
    }

    foreach ($result as $row) {
      $fields = array_map(function($field) {
        return empty($field) ? '' : '"' . str_replace('"', '""', $field) . '"';
      }, (array) $row);
      $data .= implode(',', $fields) . "\n";
    }

    $this->attachmentInfo = [
      'filename' => $filename,
      'mimeType' => 'text/csv',
      'data' => $data,
    ];
  }

  /**
   * Add an email recipient.
   *
   * @param string $email
   *   Recipient email address.
   * @param mixed $name
   *   Recipient name.
   */
  public function addRecipient($email, $name = null) {
    $this->recipients[] = [$email, $name];
  }

  /**
   * Add an email copy recipient.
   *
   * @param string $email
   *   Copied to email address.
   * @param mixed $name
   *   Copied to recipient name.
   */
  public function addCc($email, $name = null) {
    $this->cc[] = [$email, $name];
  }

  /**
   * Add an email from address.
   *
   * @param string $email
   *   Set from email address.
   * @param ?string $name
   *   Optional name of the email sender.
   */
  public function setFrom($email, $name = NULL) {
    $this->from = $email;
    if ($name) {
      $this->fromName = $name;
    }
  }

  /**
   * Add an email from address.
   *
   * @param int $priority
   *   Priority from 1 (very high) to 5 (very low).
   */
  public function setPriority($priority) {
    $this->priority = $priority;
  }

  /**
   * Log an email to the database.
   *
   * Called if email config has 'log_emails' set to TRUE, normally for
   * debugging purposes.
   *
   * @param string $subject
   *   Email subject.
   * @param mixed $message
   *   Email body.
   * @param string $emailType
   *   System component that caused the email, e.g. notifications.
   * @param string $emailSubtype
   *   Optional additional info to identify the source of the email, e.g. the
   *   notification type.
   * @param string $errorMessage
   *   If the email failed to send and an exception caught, the message from
   *   the exception.
   */
  private function logEmail($subject, $message, $emailType, $emailSubtype = NULL, $errorMessage = NULL) {
    self::insertEmailLog(
      $subject,
      $message,
      $emailType,
      $emailSubtype,
      $this->recipients,
      $this->cc,
      $this->from,
      $this->fromName,
      $errorMessage
    );
  }

  /**
   * Store an email log entry.
    *
    * @param string $subject
    *   Email subject.
    * @param string $message
    *   Email body.
    * @param string $emailType
    *   System component that caused the email.
    * @param string|null $emailSubtype
    *   Optional subtype for the email source.
    * @param array $recipients
    *   Recipient list.
    * @param array $cc
    *   CC recipient list.
    * @param string $from
    *   Sender email address.
    * @param string|null $fromName
    *   Sender name.
    * @param string|null $errorMessage
    *   Error text if sending failed.
   */
  private static function insertEmailLog(
      $subject,
      $message,
      $emailType,
      $emailSubtype,
      array $recipients,
      array $cc,
      $from,
      $fromName,
      $errorMessage) {
    self::initDb();
    try {
      self::$db->insert('email_log_entries', [
        'from_email' => $from,
        'from_name' => $fromName,
        'recipients' => json_encode($recipients),
        'cc' => json_encode($cc),
        'subject' => $subject,
        'body' => $message,
        'email_type' => $emailType,
        'email_subtype' => $emailSubtype,
        'sent_on' => date('Y-m-d H:i:s'),
        'error_message' => $errorMessage,
      ]);
    }
    catch (Exception $e) {
      error_logger::log_error('Failed to log email', $e);
    }
  }

}
