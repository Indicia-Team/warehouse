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

  public static $client_system_id;

  public static function get_server_projects_url($server_url) {
    return $server_url . '/projects?' . http_build_query(array(
      'system_id' => self::$client_system_id
    ));
  }

  public static function get_server_taxon_observations_url($server_url, $projectId, $edited_date_from) {
    return $server_url . '/taxon_observations?' . http_build_query(array(
      'system_id' => self::$client_system_id,
      'proj_id' => $projectId,
      'edited_date_from' => $edited_date_from
    ));
  }

  public static function get_server_projects($url) {
    return self::get_data_from_rest_url($url);
  }

  public static function get_server_taxon_observations($url) {
    return self::get_data_from_rest_url($url);
  }


  private static function get_data_from_rest_url($url) {
    $session = curl_init();
    // Set the POST options.
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // Do the POST and then close the session
    $response = curl_exec($session);
    $data = json_decode($response, true);
    return $data;
  }

}
