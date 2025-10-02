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
   *
   * @return bool
   *   TRUE if the email sent successfully, FALSE otherwise.
   */
  public function send($subject, $message) {
    if (!$this->from) {
      $config = kohana::config('email');
      $this->from = $config['address'];
      $this->fromName = $config['server_name'];
    }
    kohana::log('debug', "Sending email to " . json_encode($this->recipients) . " with subject $subject and message\n$message");
    $emailLibrary = kohana::config('email.library', FALSE, FALSE) ?? 'Swift';
    $emailHelper = "emailer$emailLibrary";
    $emailHelper::send(
      $subject,
      $message,
      $this->recipients,
      $this->cc,
      $this->from,
      $this->fromName
    );
    // Now reset the emailer for next time.
    $this->reset();
    return TRUE;
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

}
