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
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

class Security extends security_Core {

  /**
   * Create an authorisation nonce.
   *
   * Method to create a nonce, either from a service call (when the caller type is a website) or from the Warehouse
   * (when the caller type is an Indicia user.
   */
  public static function create_nonce($type, $website_id) {
    $nonce = sha1(time() . ':' . rand() . $_SERVER['REMOTE_ADDR'] . ':' . kohana::config('indicia.private_key'));
    $cache = new Cache();
    $cache->set($nonce, $website_id, $type, Kohana::config('indicia.nonce_life'));
    return $nonce;
  }

  /**
   * Takes a value and ensures it is matches an expected pattern. Used to
   * check parameters passed to web services to inhibit SQL injection.
   *
   * @param mixed $value The value to check.
   * @param string $type The type of parameter, [int|str], for integer or
   * string.
   * @param string $regex The pattern for a valid string parameter to match.
   * @return string The value or false if the check fails
   */
  public static function checkParam($value, $type, $regex = NULL) {
    switch ($type) {
      case 'int':
        // Value can by of type integer
        if (is_int($value)) {
          return (string) $value;
        }
        // Or value can be of type string containing an integer.
        if (ctype_digit($value)) {
          return $value;
        }
        break;
      case 'str':
        // Value must match regexp.
        $value = trim($value);
        if (preg_match($regex, $value) === 1) {
          return $value;
        }
    }
    return FALSE;
  }

  /**
   * Returns the ID to use for the current logged in user.
   *
   * @return integer
   */
  public static function getUserId() {
    // @todo Refactor getUserId method into a helper somewhere.
    if (isset($_SESSION['auth_user'])) {
      $userId = $_SESSION['auth_user']->id;
    }
    else {
      global $remoteUserId;
      if (isset($remoteUserId)) {
        $userId = $remoteUserId;
      }
      else {
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        $userId = ($defaultUserId ? $defaultUserId : 1);
      }
    }
    return $userId;
  }

}