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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

 defined('SYSPATH') or die('No direct script access.');

 define('MAX_PAGES', 1);

/**
 * Helper class for syncing to RESTful APIs.
 */
class rest_api_sync_utils {

  /**
   * Client user ID for authentication.
   *
   * @var string
   */
  public static $clientUserId;

  /**
   * Keep track of logged messages so they can be reported back to a UI.
   *
   * @var array
   */
  public static $log = [];

  /**
   * Gets a page of data from another server's REST API.
   *
   * @param string $url
   *   URL of the service to access.
   * @param string $serverId
   *   Identifier of the service being called.
   *
   * @return array
   *   List of records.
   */
  public static function getDataFromRestUrl($url, $serverId) {
    // @todo is this the most optimal place to retrieve config?
    $servers = Kohana::config('rest_api_sync.servers');
    $session = curl_init();
    // Set the POST options.
    curl_setopt($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    if (empty($servers[$serverId]['serverType']) || $servers[$serverId]['serverType'] === 'Indicia') {
      $shared_secret = $servers[$serverId]['shared_secret'];
      $userId = self::$clientUserId;
      $hmac = hash_hmac("sha1", $url, $shared_secret, FALSE);
      curl_setopt($session, CURLOPT_HTTPHEADER, ["Authorization: USER:$userId:HMAC:$hmac"]);
    }
    elseif (!empty($servers[$serverId]['serverType']) && substr($servers[$serverId]['serverType'], 0, 5) === 'json_') {
      // All JSON servers use same HMAC authentication.
      $shared_secret = $servers[$serverId]['shared_secret'];
      $userId = self::$clientUserId;
      $time = round(microtime(TRUE) * 1000);
      $authData = "$userId$time";
      // Create the authentication HMAC.
      $hmac = hash_hmac("sha1", $authData, $shared_secret, FALSE);
      curl_setopt($session, CURLOPT_HTTPHEADER, ["Authorization: USER:$userId:TIME:$time:HMAC:$hmac"]);
    }
    // Do the request.
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($session);
    // Check fo r an error, or check if the http response was not OK.
    if ($curlErrno || $httpCode != 200) {
      self::log('error', "Rest API Sync error $httpCode calling $url");
      self::log('error', "cUrl POST request failed. Status $httpCode.");
      if ($curlErrno) {
        self::log('error', 'Error number: ' . $curlErrno);
        self::log('error', 'Error message: ' . curl_error($session));
      }
      throw new exception('Request to server failed');
    }
    $data = json_decode($response, TRUE);
    return $data;
  }

  /**
   * Logs a message.
   *
   * The message is displayed on the screen and to the Kohana error log using
   * the supplied status as the error level. If a tracker array is supplied and
   * the status indicates an error, $tracker['errors'] is incremented.
   *
   * @param string $status
   *   Message status, either error or debug.
   * @param string $msg
   *   Message to log.
   * @param array $tracker
   *   Array tracking count of inserts, updates and errors.
   */
  public static function log($status, $msg, array &$tracker = NULL) {
    kohana::log($status, "REST API Sync: $msg");
    if ($status === 'error') {
      $msg = "ERROR: $msg";
      if ($tracker) {
        $tracker['errors']++;
      }
    }
    self::$log[] = str_replace("\n", '<br/>', $msg);
  }

}
