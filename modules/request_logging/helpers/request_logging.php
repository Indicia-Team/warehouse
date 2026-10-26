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
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class to provide methods for logging web service requests.
 */
class request_logging {

  /**
   * Non-credential fields excluded to keep request logs concise.
   */
  private const SKIPPED_PARAMETERS = ['paramsFormExcludes', 'callback', 'reportSource', 'mode'];

  /**
   * Log a web service request.
   *
   * @param string $io
   *   Pass one of the following:
   *   * 'i' for inputs (sending data into the warehouse)
   *   * 'o' for outputs (pulling data out)
   *   * 'a' for other actions.
   * @param string $service
   *   Web service class that was requested.
   * @param string $subtype
   *   Subtype of the resource if additional information available (e.g. report
   *   count).
   * @param string $resource
   *   Resource that was accessed if appropriate, e.g. a table or report name.
   * @param int|null $website_id
   *   ID of the client website, or null if not known.
   * @param int|null $user_id
   *   ID of the user making the request, or null if not known.
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
  public static function log(
      $io,
      $service,
      $subtype,
      $resource,
      $website_id,
      $user_id,
      $startTime,
      $db = NULL,
      $exceptionMsg = NULL,
      $overrideStoredPost = NULL) {
    // Check if this type of request is logged.
    $logged = Kohana::config('request_logging.logged_requests');
    if (in_array("$io.$service", $logged)) {
      // Request is to be logged.
      $db = $db === NULL ? new Database() : $db;
      $db->query('START TRANSACTION READ WRITE;');
      $get = empty($_GET) ? NULL : json_encode(self::stripUnloggedParams($_GET));
      if ($overrideStoredPost) {
        $post = $overrideStoredPost;
      }
      elseif (!empty($_POST)) {
        // PHP has already parsed form and multipart bodies; never log file data.
        $post = $_POST;
      }
      else {
        $post = file_get_contents('php://input');
        if (empty($post)) {
          $post = NULL;
        }
      }
      if ($post !== NULL) {
        $post = self::preparePostForLogging($post, $overrideStoredPost ? '' : ($_SERVER['CONTENT_TYPE'] ?? ''));
      }
      $db->insert('request_log_entries', [
        'io' => $io,
        'service' => $service,
        'resource' => $resource,
        'subtype' => $subtype,
        'request_parameters_get' => $get,
        'request_parameters_post' => $post,
        'website_id' => $website_id,
        'user_id' => $user_id,
        'start_timestamp' => $startTime,
        'duration' => microtime(TRUE) - $startTime,
        'exception_msg' => $exceptionMsg,
        'response_size' => ob_get_length(),
      ]);
      $db->query('COMMIT');
    }
  }

  /**
   * Tidy up parameters we don't want to log.
   *
   * @param array $array
   *   Parameters list.
   *
   * @return array
   *   Tidied array.
   */
  private static function stripUnloggedParams(array $array) {
    return logRedaction::parameters($array, self::SKIPPED_PARAMETERS);
  }

  /**
   * Redact POST data before encoding it for the JSON log column.
   *
   * Raw bodies remain JSON strings for compatibility with existing log readers.
   *
   * @param array|object|string $post
   *   Parsed parameters, a raw body, or replacement data supplied by the caller.
   * @param string $contentType
   *   Content type used to interpret a raw request body.
   *
   * @return string
   *   JSON-encoded, sanitised data.
   */
  private static function preparePostForLogging($post, string $contentType = ''): string {
    if (is_array($post)) {
      $post = self::stripUnloggedParams($post);
    }
    elseif ($post instanceof stdClass) {
      $post = (object) self::stripUnloggedParams(get_object_vars($post));
    }
    elseif (is_string($post)) {
      $post = logRedaction::body($post, self::SKIPPED_PARAMETERS, $contentType);
    }
    return json_encode($post, JSON_THROW_ON_ERROR);
  }

}
