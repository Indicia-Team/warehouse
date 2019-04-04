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
 * @link 	https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class to provide methods for logging web service requests.
 */
class request_logging {

  /**
   * Log a web service request.
   * @param string $io
   *   Pass one of the following:
   *   * 'i' for inputs (sending data into the warehouse)
   *   * 'o' for outputs (pulling data out)
   *   * 'a' for other actions.
   * @param string $service
   *   Web service class that was requested.
   * @param string $resource
   *   Resource that was accessed if appropriate, e.g. a table or report name.
   * @param integer $website_id
   *   ID of the client website, or null if not known
   * @param integer $user_id
   *   ID of the user making the request, or null if not known
   * @param float $startTime
   *   Unix timestamp of the request start, i.e. the value of microtime(true).
   * @param Database $db
   *   Kohana database object, or null if none available.
   * @param string $exceptionMsg
   *   Optional message if an exception occurred.
   * @param string $overrideStoredPost
   *   Where POST data are large or to complex to log usefully, it may be
   *   replaced in the log by specifying an object to store here.
   */
  public static function log($io, $service, $resource, $website_id, $user_id, $startTime, $db = NULL,
      $exceptionMsg = NULL, $overrideStoredPost = NULL) {
    // Check if this type of request is logged.
    $logged = Kohana::config('request_logging.logged_requests');
    if (in_array("$io.$service", $logged)) {
      // Request is to be logged.
      $db = new Database();
      $db->query('START TRANSACTION READ WRITE;');
      $get = empty($_GET) ? NULL : json_encode($_GET);
      if ($overrideStoredPost) {
        $post = json_encode($overrideStoredPost);
      }
      else {
        $post = file_get_contents('php://input');
        if (empty($post)) {
          $post = empty($_POST) ? NULL : json_encode($_POST);
        }
      }
      $db->insert('request_log_entries', array(
        'io' => $io,
        'service' => $service,
        'resource' => $resource,
        'request_parameters_get' => $get,
        'request_parameters_post' => $post,
        'website_id' => $website_id,
        'user_id' => $user_id,
        'start_timestamp' => $startTime,
        'duration' => microtime(TRUE) - $startTime,
        'exception_msg' => $exceptionMsg,
        'response_size' => ob_get_length(),
      ));
      $db->query('COMMIT');
    }
  }
}