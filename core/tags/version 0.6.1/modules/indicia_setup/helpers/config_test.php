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

  /**
   * Check the system configuration.
   * @param boolean $problems_only If true then only reports on problems, not successful checks. Defaults to false.
   * @param boolean $force If true then forces a check even if the system configuration has been completed.
   */
  public static function check_config($problems_only=false, $force=false) {    
    $result = array();    
    // If the Indicia config is present, then everything has passed, so we can skip the tests unless it is being forced.
    if ($force || kohana::config_load('indicia', false)==null) {
      self::check_php_version($result, $problems_only);
      self::check_postgres($result, $problems_only);
      self::check_curl($result, $problems_only);
      self::check_gd2($result, $problems_only);
      self::check_dBase($result, $problems_only);
      self::check_zip($result, $problems_only);
      self::check_dir_permissions($result, $problems_only);
      self::check_helper($result, $problems_only);
      self::check_email($result, $problems_only);
      // Check db must be the last one
      self::check_db($result, $problems_only);
    }
    return $result;
  }


  /**
   * Ensure that the PHP version running on the server is at least 5.2, which supports
   * JSON properly.
   *
   * @param array $messages List of messages that any information should be appended to.
   * @param boolean $problems_only Set to true to report only the problems, not the successful
   * checks. False reports both failures and successes.
   */
  public static function check_db(&$messages, $problems_only) {
    // The Indicia config file is only created after a successful db creation.
    $config=kohana::config_load('indicia', false);
    if (!$config) {
      $problem = array(
        'title' => 'Database configuration',
        'description' => '<p>Database configuration options need to be set allowing the Indicia Warehouse to access your ' .
            'database. Indicia will then install the required database tables for you.</p>',
        'success' => false
      );
      $other_problems=false;
      for ($i=0; $i<count($messages); $i++) {
        $other_problems = $other_problems || ($messages[$i]['success']==false);
      }
      if (!$other_problems) {
        // No other problems, so can proceed to install the database.
        $problem['action'] = array('title'=>'Configure database', 'link'=>'config_db');
      }	else {
        $problem['description'] .= '<p>Fix the other issues listed on this page before proceeding to configure and install the database.</p>';
      }
      array_push($messages, $problem);
    } elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'Database configuration',
        'description' => '<p>The Indicia Warehouse database has been configured and installed.</p>',
        'success' => true
      ));
    }
  }

  /**
   * Ensure that the email configuration file has been setup.
   *
   * @param array $messages List of messages that any information should be appended to.
   * @param boolean $problems_only Set to true to report only the problems, not the successful
   * checks. False reports both failures and successes.
   */
  private static function check_email(&$messages, $problems_only) {
    if (array_key_exists('skip_email', $_SESSION)) {
      if (!$problems_only) {
        array_push($messages, array(
          'title' => 'Email configuration',
          'description' => '<p>Email configuration has been skipped so the server may not be able to send ' .
              'forgotten password reminder emails.</p>',
          'success' => true
        ));
      }
    } else {
      $email_config = kohana::config('email');
      if (!array_key_exists('forgotten_passwd_title', $email_config)) {
        array_push($messages, array(
          'title' => 'Email configuration',
          'description' => '<p>Email configuration options need to be set up to allow the Indicia Warehouse to send emails to users ' .
              'who forget their passwords.</p>',
          'success' => false,
          'action' => array('title'=>'Configure email', 'link'=>'config_email')
        ));
      }
      else if (!array_key_exists('test_result', $email_config) ||
          $email_config['test_result'] != 'pass') {
        array_push($messages, array(
          'title' => 'Email configuration',
          'description' => '<p>Email configuration has not been tested. The Indicia Warehouse might not be able to send emails to users ' .
              'who forget their passwords.</p>',
          'success' => false,
          'action' => array('title'=>'Configure email', 'link'=>'config_email')
        ));
      }
      elseif (!$problems_only) {
        array_push($messages, array(
          'title' => 'Email configuration',
          'description' => '<p>Configuration of server side emails completed.</p>',
          'success' => true
        ));
      }
    }
  }

  /**
   * Ensure that the PHP version running on the server is at least 5.2, which supports
   * JSON properly.
   *
   * @param array $messages List of messages that any information should be appended to.
   * @param boolean $problems_only Set to true to report only the problems, not the successful
   * checks. False reports both failures and successes.
   */
  private static function check_php_version(&$messages, $problems_only) {
    // PHP_VERSION_ID is available as of PHP 5.2.7, if our
    // version is lower than that, then emulate it
    if(!defined('PHP_VERSION_ID'))
    {
        $version = PHP_VERSION;
        define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
    }
    if (PHP_VERSION_ID<50200) {
      array_push($messages, array(
        'title' => 'PHP Version',
        'description' => '<p>Your PHP version is '.phpversion().' which does not support JSON communication with the online recording websites. '.
            'Please upgrade the PHP installation on this web server to at least version 5.2.</p>',
        'success' => false
      ));
    } elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'PHP Version',
        'description' => '<p>PHP version is '.phpversion().'.</p>',
        'success' => true
      ));
    }
  }

  /**
   * Ensure that the PHP PostgreSQL extensions are available.
   *
   * @param array $messages List of messages that any information should be appended to.
   * @param boolean $problems_only Set to true to report only the problems, not the successful
   * checks. False reports both failures and successes.
   */
  private static function check_postgres(&$messages, $problems_only) {
    if(!function_exists('pg_version')) {
      array_push($messages, array(
        'title' => 'PostgreSQL PHP Extensions',
        'description' => 'The PostgreSQL extensions are not available on this installation of PHP. To fix this, find your php.ini file in the PHP installation folder and ' .
            'find the line <strong>;extension=php_pgsql.dll</strong>. Remove the semi-colon from the start of the line and save the file, then restart your ' .
            'webserver. Please pass this information to the administrator of your webserver if you are not sure how to do this.',
        'success' => false
      ));
    } elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'PostgreSQL PHP Extensions',
        'description' => 'The PostgreSQL extensions for PHP are available on this PHP installation.',
        'success' => true
      ));
    }

  }

  /**
   * Ensure that the cUrl library is installed.
   *
   * @param array $messages List of messages that any information should be appended to.
   * @param boolean $problems_only Set to true to report only the problems, not the successful
   * checks. False reports both failures and successes.
   */
  private static function check_curl(&$messages, $problems_only) {
    if (!function_exists('curl_exec')) {
      array_push($messages, array(
        'title' => 'cUrl Library',
        'description' => '<p>The cUrl library is not installed on this web server. To fix this, find your php.ini file in the PHP installation folder and ' .
            'find the line <strong>;extension=php_curl.dll</strong>. Remove the semi-colon from the start of the line and save the file, then restart your ' .
            'webserver. Please pass this information to the administrator of your webserver if you are not sure how to do this.</p>',
        'success' => false
      ));
    } elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'cUrl Library',
        'description' => '<p>The cUrl library is installed.</p>',
        'success' => true
      ));
    }
  }
  
/**
   * Ensure that the gd2 graphics library is installed.
   *
   * @param array $messages List of messages that any information should be appended to.
   * @param boolean $problems_only Set to true to report only the problems, not the successful
   * checks. False reports both failures and successes.
   */
  private static function check_gd2(&$messages, $problems_only) {
    if (!function_exists('gd_info')) {
      array_push($messages, array(
        'title' => 'gd2 Library',
        'description' => '<p>The gd2 library is not installed on this web server. To fix this, find your php.ini file in the PHP installation folder and ' .
            'find the line <strong>;extension=php_gd2.dll</strong>. Remove the semi-colon from the start of the line and save the file, then restart your ' .
            'webserver. Please pass this information to the administrator of your webserver if you are not sure how to do this.</p>',
        'success' => false
      ));
    } elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'gd2 Library',
        'description' => '<p>The gd2 library is installed.</p>',
        'success' => true
      ));
    }
  }

/**
   * Ensure that the dBase library is installed.
   *
   * @param array $messages List of messages that any information should be appended to.
   * @param boolean $problems_only Set to true to report only the problems, not the successful
   * checks. False reports both failures and successes.
   */
  private static function check_dBase(&$messages, $problems_only) {
    if (!function_exists('dbase_open')) {
      // Note that this does NOT prevent installation, only give a warning.
      array_push($messages, array(
        'title' => 'dBase Library',
        'description' => '<p>The dBase library is not installed on this web server. This is required to enable upload of SHP files containing location boundaries, but '.
            'does not stop Indicia working. To fix this for servers running PHP 5.2, find your php.ini file in the PHP installation folder and ' .
            'find the line <strong>;extension=php_dbase.dll</strong>. Remove the semi-colon from the start of the line and save the file, then restart your ' .
            'webserver process. If you compiled PHP yourself, you must compile PHP with dbase support by using the --enable-dbase configuration option.</p>',
        'success' => true,
        'warning' => true
      ));
    } elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'dBase Library',
        'description' => '<p>The dBase library is installed.</p>',
        'success' => true
      ));
    }
  }

/**
   * Ensure that the zip library is installed.
   *
   * @param array $messages List of messages that any information should be appended to.
   * @param boolean $problems_only Set to true to report only the problems, not the successful
   * checks. False reports both failures and successes.
   */
  private static function check_zip(&$messages, $problems_only) {
    if (!function_exists('zip_open')) {
      array_push($messages, array(
        'title' => 'Zip Library',
        'description' => '<p>The zip library is not installed on this web server. To fix this for Windows servers, find your php.ini file in the PHP installation folder and ' .
            'find the line <strong>;extension=php_zip.dll</strong>. Remove the semi-colon from the start of the line and save the file, then restart your ' .
            'webserver. For Linux systems, you must compile PHP with zip support by using the --enable-zip configuration option. Please pass this information to the administrator of your webserver if you are not sure how to do this.</p>',
        'success' => false
      ));
    } elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'Zip Library',
        'description' => '<p>The Zip library is installed.</p>',
        'success' => true
      ));
    }
  }

  /**
   * Ensure that the various directories required by the installation have the correct
   * permissions. Public so that it can be accessed individually by the ack_permissions
   * page.
   *
   * @param array $messages List of messages that any information should be appended to.
   * @param boolean $problems_only Set to true to report only the problems, not the successful
   * checks. False reports both failures and successes.
   */
  public static function check_dir_permissions(&$messages, $problems_only) {
    // list of messages about each directory we need access to.
    $good_dirs=array();
    $bad_dirs=array();
    $readonly=true;
    $writeable=false;
    if (array_key_exists('ack_permissions', $_SESSION)) {
      if (!$problems_only) {
        array_push($messages, array(
          'title' => 'Directory Access',
          'description' => '<p>Problems with the directory access permissions have been acknowledged by you.</p>',
          'success' => true
        ));
      }
    } else {
      self::check_dir_permission($writeable,  $good_dirs, $bad_dirs, 'image upload',
          dirname(dirname(dirname(dirname(__file__ )))) . '/upload',
          'images can be uploaded',
          'images cannot be uploaded');
      self::check_dir_permission($writeable,  $good_dirs, $bad_dirs, 'configuration',
          dirname(dirname(dirname(dirname(__file__ )))) . '/application/config',
          'the installation settings to be stored correctly',
          'the installation settings cannot be stored');
      self::check_dir_permission($writeable,  $good_dirs, $bad_dirs, 'database update folders',
          dirname(dirname(dirname(dirname(__file__ )))) . '/modules/indicia_setup/db',
          'the database upgrades can be tracked',
          'the database upgrades cannot be tracked');
      self::check_dir_permission($writeable,  $good_dirs, $bad_dirs, 'configuration',
          dirname(dirname(dirname(dirname(__file__ )))) . '/client_helpers',
          'the settings for the data entry helper classes to be stored',
          'the settings for the data entry helper classes cannot be stored');
      self::check_dir_permission($writeable,  $good_dirs, $bad_dirs, 'demo',
          dirname(dirname(dirname(dirname(__file__ )))) . '/modules/demo',
          'the settings for the demo configuration to be stored',
          'the settings for the demo configuration classes cannot be stored');

      if (count($good_dirs)>0 && !$problems_only) {
        array_push($messages, array(
          'title' => 'Correct Directory Access',
          'description' => '<p>'.implode('</p><p>', $good_dirs).'</p>',
          'success' => true
        ));
      }
      if (count($bad_dirs)>0) {
        array_push($messages, array(
          'title' => 'Directory Access',
          'description' => '<p>'.implode('</p><p>', $bad_dirs).'</p>',
          'success' => false,
          'action' => array('title'=>'Acknowledge', 'link'=>'ack_permissions')
        ));
      }
    }
  }

  /**
   * Test the access rights to a specific directory. Appends pass and fail messages to the $good_dirs and
   * $bad_dirs arrays.
   *
   * @param boolean $readonly Set to true to check that a directory is readable but not writeable. Set to false to
   * check a directory can be written.
   * @param array $good_dirs The array which pass messages will be appended to.
   * @param array $bad_dirs The array which fail messages will be appended to.
   * @param string $folder_name The natural language description of the tested folder.
   * @param string $pass A natural language description of the pass state.
   * @param string $fail A natural language description of the fail state.
   */
  private static function check_dir_permission($readonly, &$good_dirs, &$bad_dirs, $folder_name, $dir, $pass, $fail) {
    $access_str=$readonly ? 'readable' : 'writeable';
    $dir = realpath($dir);
    if (($readonly && is_readable($dir)) || (!$readonly && is_writeable($dir))) {
      if ($readonly && is_writeable($dir)) {
        array_push($bad_dirs, "The $folder_name directory at $dir is writeable. It should be readonly otherwise " .
            "it presents an unnecessary security risk.");
      } else {
        array_push($good_dirs, "The $folder_name directory is $access_str to allow $pass.");
      }
    } else {
      array_push($bad_dirs,
          "The $folder_name directory at $dir isn't writeable by PHP scripts. This means that $fail.");
    }
  }

  /**
   * Ensure that the client_helpers/helper_config file has been setup.
   *
   * @param array $messages List of messages that any information should be appended to.
   * @param boolean $problems_only Set to true to report only the problems, not the successful
   * checks. False reports both failures and successes.
   */
  private static function check_helper(&$messages, $problems_only) {
    if (!file_exists(dirname(dirname(dirname(dirname(__file__ )))) . '/client_helpers/helper_config.php')) {
      array_push($messages, array(
        'title' => 'Demo configuration',
        'description' => '<p>Configuration options need to be set to allow the demonstration pages provided with this ' .
            'Warehouse installation to work.</p>',
        'success' => false,
        'action' => array('title'=>'Configure Demo Pages', 'link'=>'config_demo')
      ));
    } elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'Demo configuration',
        'description' => '<p>The demonstration pages have been configured.</p>',
        'success' => true
      ));
    }
  }

 }

 ?>