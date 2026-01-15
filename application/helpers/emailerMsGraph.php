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

require __DIR__ . '/../../vendor/autoload.php';

use GuzzleHttp\Client;

/**
 * Email helper for using Microsoft Graph to send emails.
 *
 * Requires the following in the email config file:
 * * msgraph_tenant_id
 * * msgraph_client_id
 * * msgraph_client_secret
 */
class emailerMsGraph {

  private static Client $http;

  private static $token;

  /**
   * MS Graph initialisation - fetch a token.
   */
  public static function init() {
    self::$http = new Client();
    $config = Kohana::config('email');
    if (empty($config['msgraph_tenant_id']) || empty($config['msgraph_client_id']) || empty($config['msgraph_client_secret'])) {
      throw new exception('Cannot use Microsoft Graph emails as not configured correctly.');
    }
    $response = self::$http->post("https://login.microsoftonline.com/$config[msgraph_tenant_id]/oauth2/v2.0/token", [
      'form_params' => [
        'client_id' => $config['msgraph_client_id'],
        'client_secret' => $config['msgraph_client_secret'],
        'scope' => 'https://graph.microsoft.com/.default',
        'grant_type' => 'client_credentials',
      ],
    ]);
    $tokenData = json_decode($response->getBody(), true);
    if (!isset($tokenData['access_token'])) {
      throw new Exception("Failed to acquire access token: " . json_encode($tokenData));
    }
    self::$token = $tokenData['access_token'];
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
   */
  public static function send(
      $subject,
      $body,
      array $recipientList,
      array $ccList,
      $from,
      $fromName = NULL,
      $priority = 3
      ) {
    // Add spacer before footer.
    for ($i = 0; $i < $config = Kohana::config('email.msgraph_footer_spacer_rows', FALSE, FALSE) ?? 0; $i++) {
      $body .= '<br>';
    }
    $mail = [
      'message' => [
        'subject' => $subject,
        'body' => [
          'contentType' => 'HTML',
          'content' => $body
        ],
        'toRecipients' => [],
      ],
      'saveToSentItems' => TRUE,
    ];
    foreach ($recipientList as $to) {
      // Email recipient can be with or without name.
      $email = $to[1] ? ['address' => $to[0], 'name' => $to[1]] : ['address' => $to[0]];
      $mail['message']['toRecipients'][] = [
        'emailAddress' => $email,
      ];
    }
    if (count($ccList) > 0) {
      $mail['message']['ccRecipients'] = [];
      foreach ($ccList as $cc) {
        // Email recipient can be with or without name.
        $email = $cc[1] ? ['address' => $cc[0], 'name' => $cc[1]] : ['address' => $cc[0]];
        $mail['message']['ccRecipients'][] = [
          'emailAddress' => $email,
        ];
      }
    }
    if ($priority !== 3) {
      $mail['message']['importance'] = $priority < 3 ? 'high' : 'low';
    }
    $response = self::$http->post("https://graph.microsoft.com/v1.0/users/$from/sendMail", [
      'headers' => [
        'Authorization' => 'Bearer ' . self::$token,
        'Content-Type' => 'application/json',
      ],
      'json' => $mail,
    ]);

    if ($response->getStatusCode() !== 202) {
      throw new Exception('Microsoft Graph email send failure. Unexpected response status: ' . $response->getStatusCode());
    }
  }

}
