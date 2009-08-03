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