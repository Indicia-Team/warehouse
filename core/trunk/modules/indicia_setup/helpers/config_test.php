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
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Helper class for testing the system configuration.
 *
 * @package	Core
 * @subpackage helpers
 */
class config_test {

  public static function check_config($problems_only=false) {
    $result = array();
    $email_config = kohana::config('email');
    // PHP_VERSION_ID is available as of PHP 5.2.7, if our
    // version is lower than that, then emulate it
    if(!defined('PHP_VERSION_ID'))
    {
        $version = PHP_VERSION;
        define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
    }
    if (PHP_VERSION_ID<50200) {
      array_push($result, array(
        'title' => 'PHP Version',
        'description' => 'Your PHP version is '.phpversion().' which does not support JSON communication with the online recording websites. '.
            'Please upgrade the PHP installation on this web server to at least version 5.2.',
        'success' => false
      ));
    } elseif (!$problems_only) {
      array_push($result, array(
        'title' => 'PHP Version',
        'description' => 'PHP version is '.phpversion().'.',
        'success' => true
      ));
    }
    if (!function_exists('curl_exec')) {
      array_push($result, array(
        'title' => 'cUrl Library',
        'description' => 'The cUrl library is installed not installed on this web server. To fix this, find your php.ini file in the PHP installation folder and ' .
            'find the line <strong>;extension=php_curl.dll</strong>. Remove the semi-colon from the start of the line and save the file, then restart your ' .
            'webserver. Please pass this information to the administrator of your webserver if you are not sure how to do this.',
        'success' => false
      ));
    } elseif (!$problems_only) {
      array_push($result, array(
        'title' => 'cUrl Library',
        'description' => 'The cUrl library is installed.',
        'success' => true
      ));
    }
    if (!array_key_exists('forgotten_passwd_title', $email_config)) {
      array_push($result, array(
        'title' => 'Email configuration',
        'description' => 'Email configuration options need to be set up to allow the Indicia Warehouse to send emails to users ' .
            'who forget their passwords.',
        'success' => false,
        'action' => 'config_email'
      ));
    }
    else if (!array_key_exists('test_result', $email_config) ||
        $email_config['test_result'] != 'pass') {
      array_push($result, array(
        'title' => 'Email configuration',
        'description' => 'Email configuration has not been tested. The Indicia Warehouse might not be able to send emails to users ' .
            'who forget their passwords.',
        'success' => false,
        'action' => 'config_email'
      ));
    }
    elseif (!$problems_only) {
      array_push($result, array(
        'title' => 'Email configuration',
        'description' => 'Configuration of server side emails completed.',
        'success' => true
      ));
    }
    return $result;

  }

 }

 ?>