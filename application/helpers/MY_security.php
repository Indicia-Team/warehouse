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
 * @package	Core
 * @subpackage Helpers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct script access.');

class Security extends security_Core {

  /**
   * Method to create a nonce, either from a service call (when the caller type is a website) or from the Warehouse
   * (when the caller type is an Indicia user.
   */
  public static function create_nonce($type, $website_id) {
    $nonce = sha1(time().':'.rand().$_SERVER['REMOTE_ADDR'].':'.kohana::config('indicia.private_key'));
    $cache = new Cache();
    $cache->set($nonce, $website_id, $type, Kohana::config('indicia.nonce_life'));
    return $nonce;
  }

  /**
   * Takes a value and ensures it is matches an expected pattern. Used to 
   * check parameters passed to web services to inhibit SQL injection.
   * 
   * @param string $value The value to check.
   * @param string $type The type of parameter, int|str (integer or string)
   * @param string $regex The pattern for a valid string parameter to match.
   * @return string The value or false if the check fails
   */
  public static function checkParam($value, $type, $regex = NULL) {
    switch ($type) {
      case 'int':
        // Value of param must be an integer (though stored in a string).
        if (ctype_digit($value)) {
          return $value;
        }
        break;
      case 'str':
        // Value of param must match regexp.
        $value = trim($value);
        if (preg_match($regex, $value) === 1) {
          return $value;
        }
    }
    return FALSE;
  }
  
}
 ?>