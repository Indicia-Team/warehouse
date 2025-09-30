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
   * Email sent from, array containing email and optional name.
   *
   * @var array
   */
  private array $from;

  /**
   * Email reply to, array containing email and optional name.
   *
   * @var array
   */
  private array $replyTo;

  /**
   * Reset any details set for the next email.
   */
  public function reset() {
    $this->recipients = [];
    $this->cc = [];
    $this->from = null;
    $this->replyTo = null;
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
    if (!$this->from || !$this->replyTo) {
      $config = kohana::config('email');
      if (!$this->from) {
        $this->from = [$config['address'], $config['server_name']];
      }
      if (!$this->replyTo) {
        $this->replyTo = [$config['address'], $config['server_name']];
      }
    }
    // Send email logic here
    kohana::log('debug', "Sending email to " . json_encode($this->recipients) . " with subject $subject and message\n$message");
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
   * @param mixed $name
   *   Set from name.
   */
  public function setFrom($email, $name = null) {
    $this->from = [$email, $name];
  }

  /**
   * Add an email reply to address.
   *
   * @param string $email
   *   Set reply to email address.
   * @param mixed $name
   *   Set reply to name.
   */
  public function setReplyTo($email, $name = null) {
    $this->replyTo = [$email, $name];
  }

}
