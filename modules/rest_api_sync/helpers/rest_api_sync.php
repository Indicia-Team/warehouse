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
 * @link http://code.google.com/p/indicia/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class for syncing to RESTful APIs.
 */
class rest_api_sync {

  public static $client_user_id;

  public static function getDataFromRestUrl($url, $serverId) {
    // @todo is this the most optimal place to retrieve config?
    $servers = Kohana::config('rest_api_sync.servers');
    $shared_secret = $servers[$serverId]['shared_secret'];
    $userId = self::$client_user_id;
    $session = curl_init();
    // Set the POST options.
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $hmac = hash_hmac("sha1", $url, $shared_secret, $raw_output=FALSE);
    curl_setopt($session, CURLOPT_HTTPHEADER, array("Authorization: USER:$userId:HMAC:$hmac"));
    // Do the request
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($session);
    // Check for an error, or check if the http response was not OK.
    if ($curlErrno || $httpCode != 200) {
      echo "Error occurred accessing $url<br/>";
      echo $response;
      kohana::log('error', "Rest API Sync error $httpCode");
      kohana::log('error', 'cUrl POST request failed.');
      if ($curlErrno) {
        kohana::log('error', 'Error number: ' . $curlErrno);
        kohana::log('error', 'Error message: ' . curl_error($session));
      }
      echo 'Request failed<br/>';
      echo "$url<br/>";
      throw new exception('Request to server failed');
    }
    $data = json_decode($response, true);
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
    echo str_replace("\n", '<br/>', $msg) . '<br/>';
  }

}
