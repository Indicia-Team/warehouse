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
   * Name of the email helper class, e.g. for Swift or MS Graph connections.
   *
   * @var string
   */
  private $emailHelper;

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
   * @return bool
   *   The number of recipients who have been sent emails - 0 if an error occurred..
   */
  public function send($subject, $message, $emailType, $emailSubtype = NULL) {
    $config = kohana::config('email');
    $succeeded = FALSE;
    $errorMessage = NULL;
    if (!$this->from) {
      $this->from = $config['address'];
      $this->fromName = $config['server_name'];
    }
    if ($config['do_not_send'] ?? FALSE) {
      // Email disabled on this server, this classes as a success.
      return TRUE;
    }
    $emailLibrary = $config['library'] ?? 'Swift';
    $emailHelper = "emailer$emailLibrary";
    try {
      if (empty($this->recipients || empty($this->message))) {
        throw new Exception('Email incomplete - missing recipient or message');
      }
      $emailHelper::send(
        $subject,
        $message,
        $this->recipients,
        $this->cc,
        $this->from,
        $this->fromName,
        $this->priority
      );
      $succeeded = TRUE;
    }
    catch (Exception $e) {
      error_logger::log_error('Error in email helper', $e);
      $errorMessage = $e->getMessage();
    }
    finally {
      if ($config['log_emails'] ?? FALSE) {
        $this->logEmail($subject, $message, $emailType, $emailSubtype, $errorMessage);
      }
      // Now reset the emailer for next time.
      $this->reset();
    }
    return $succeeded ? count($this->recipients) : 0;
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
   * @param string $email
   *   Set from email address.
   * @param ?string $name
   *   Optional name of the email sender.
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
    if (!self::$db) {
      self::$db = new Database();
    }
    try {
      self::$db->insert('email_log_entries', [
        'from_email' => $this->from,
        'from_name' => $this->fromName,
        'recipients' => json_encode($this->recipients),
        'cc' => json_encode($this->cc),
        'subject' => $subject,
        'body' => $message,
        'email_type' => $emailType,
        'email_subtype' => $emailSubtype,
        'sent_on' => date('Y-m-d H:i:s'),
      ]);
    }
    catch (Exception $e) {
      error_logger::log_error('Failed to log email', $e);
    }
  }

}
