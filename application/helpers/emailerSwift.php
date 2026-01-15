<?php

/**
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
 * Email helper for using Kohana's built in Swift library to send emails.
 */
class emailerSwift {

  private static $swift;


  /**
   * Swift initialisation.
   *
   * @return void
   */
  public static function init() {
    self::$swift = email::connect();
  }

  /**
   * Send an email.
   *
   * @param string $subject
   *   Email subject.
   * @param string $body
   *   Email message body.
   * @param array $recipientList
   *   List of email recipients. Each entry is an array holding the email
   *   address and optional recipient name.
   * @param array $ccList
   *   List of email copy recipients. Each entry is an array holding the email
   *   address and optional recipient name.
   * @param string $from
   *   The email address that the email should be sent from.
   * @param ?string $fromName
   *   The optional name associated with the from email address.
   * @param ?int $priority
   *   Priority from 1 (very high) to 5 (very low). Default 3.
   * @param ?array $attachmentInfo
   *   Attachment to add. Can contain filename, mime type and data keys.
   */
  public static function send(
      $subject,
      $body,
      array $recipientList,
      array $ccList,
      $from,
      $fromName = NULL,
      $priority = 3,
      ?array $attachmentInfo = NULL
      ) {
    $message = new Swift_Message($subject);
    // Create the HTML part explicitly
    $htmlPart = new Swift_Message_Part(
      $body,
      'text/html',
      '8bit',
      'utf-8'
    );
    $message->attach($htmlPart);
    // Create the plain text part explicitly
    $textPart = new Swift_Message_Part(
      strip_tags($body),
      'text/plain',
      '8bit',
      'utf-8'
    );
    $message->attach($textPart);
    if ($priority !== 3) {
      $message->setPriority($priority);
    }
    $swiftRecipients = new Swift_RecipientList();
    foreach ($recipientList as $recipient) {
      $swiftRecipients->addTo($recipient[0], $recipient[1] ?? NULL);
    }
    foreach ($ccList as $cc) {
      $swiftRecipients->addCc($recipient[0], $recipient[1] ?? NULL);
    }
    $swiftFrom = $fromName ? new Swift_Address($from, $fromName) : new Swift_Address($from);
    if ($attachmentInfo) {
      $attachment = new Swift_Message_Attachment(
        $attachmentInfo['data'],
        $attachmentInfo['filename'],
        $attachmentInfo['mimeType']
      );
      $message->attach($attachment);
    }
    self::$swift->send($message, $swiftRecipients, $swiftFrom);
  }

}
