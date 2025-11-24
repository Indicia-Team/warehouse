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
 * Email helper for development which just logs information.
 *
 * No email sent, but info put in debug logs so useful for development.
 */
class emailerDevLogger {

  /**
   * Initialisation.
   *
   * @return void
   */
  public static function init() {
  }

  /**
   * Send an email.
   *
   * @param string $subject
   *   Email subject.
   * @param string $message
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
   */
  public static function send(
      $subject,
      $message,
      array $recipientList,
      array $ccList,
      $from,
      $fromName = NULL,
      $priority = 3
      ) {
    Kohana::log('debug', 'Email information (emailerDevLogger): ' . var_export([
      'subject' => $subject,
      'message' => $message,
      'recipientList' => $recipientList,
      'cc' => $ccList,
      'from' => $from,
      'fromName' => $fromName,
      'priority' => $priority,
    ], TRUE));
  }

}
