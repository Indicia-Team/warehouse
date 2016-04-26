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
 * @package	REST Api Sync
 * @subpackage Helpers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 *
 */
class rest_api_sync {

  public static $client_user_id;

  public static function get_server_projects_url($server_url) {
    return $server_url . '/projects';
  }

  public static function get_server_taxon_observations_url($server_url, $projectId,
      $edited_date_from, $edited_date_to) {
    return $server_url . '/taxon-observations?' . http_build_query(array(
      'proj_id' => $projectId,
      'edited_date_from' => $edited_date_from,
      'edited_date_to' => $edited_date_to,
      'page_size' => 500
    ));
  }
  
  public static function get_server_annotations_url($server_url, $projectId,
      $edited_date_from, $edited_date_to) {
    return $server_url . '/annotations?' . http_build_query(array(
      'proj_id' => $projectId,
      'edited_date_from' => $edited_date_from,
      'edited_date_to' => $edited_date_to,
      'page_size' => 500
    ));
  }

  public static function get_server_projects($url, $serverId) {
    return self::get_data_from_rest_url($url, $serverId);
  }

  public static function get_server_taxon_observations($url, $serverId) {
    return self::get_data_from_rest_url($url, $serverId);
  }
  
  public static function get_server_annotations($url, $serverId) {
    return self::get_data_from_rest_url($url, $serverId);
  }


  private static function get_data_from_rest_url($url, $serverId) {
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
      kohana::log('error', "Rest API Sync error $httpCode");
      kohana::log('error', 'cUrl POST request failed.');
      if ($curlErrno) {
        kohana::log('error', 'Error number: '.$curlErrno);
        kohana::log('error', 'Error message: '.curl_error($session));
      }
      echo 'Request failed<br/>';
      echo "$url<br/>";
      throw new exception('Request to server failed');
    }
    $data = json_decode($response, true);
    return $data;
  }

}
