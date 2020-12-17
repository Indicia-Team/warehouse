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

/**
 * Helper class for testing the system configuration.
 */
class config_test {

  /**
   * Check the system configuration.
   *
   * @param bool $problems_only
   *   If true then only reports on problems, not successful checks. Defaults to false.
   *
   * @param bool $force
   *   If true then forces a check even if the system configuration has been completed.
   */
  public static function check_config($problems_only = FALSE, $force = FALSE) {
    $result = array();
    $config = kohana::config_load('indicia', FALSE);
    // If the Indicia config is present, then everything has passed, so we can
    // skip the tests unless it is being forced.
    if ($force || empty($config)) {
      self::check_php_version($result, $problems_only);
      self::check_postgres($result, $problems_only);
      self::check_curl($result, $problems_only);
      self::check_gd2($result, $problems_only);
      self::check_zip($result, $problems_only);
      self::check_dir_permissions($result, $problems_only);
      self::check_email($result, $problems_only);
      // Check db must be the last one.
      self::check_db($result, $problems_only);
    }
    return $result;
  }

  /**
   * Ensure that the PHP version running on the server is at least 5.2, which supports
   * JSON properly.
   *
   * @param array $messages
   *   List of messages that any information should be appended to.
   * @param bool $problems_only
   *   Set to true to report only the problems, not the successful checks.
   *   False reports both failures and successes.
   */
  public static function check_db(&$messages, $problems_only) {
    // The Indicia config file is only created after a successful db creation.
    $config = kohana::config_load('indicia', FALSE);
    if (!$config) {
      $problem = array(
        'title' => 'Database configuration',
        'description' => 'Database configuration options need to be set allowing the Indicia Warehouse to access your ' .
            'database. Indicia will then install the required database tables for you.',
        'success' => FALSE,
      );
      $other_problems = FALSE;
      for ($i = 0; $i < count($messages); $i++) {
        $other_problems = $other_problems || ($messages[$i]['success'] == FALSE);
      }
      if (!$other_problems) {
        // No other problems, so can proceed to install the database.
        $problem['action'] = array(
          'title' => 'Configure database',
          'link' => 'config_db',
        );
      }
      else {
        $problem['description'] .= ' Fix the other issues listed on this page before proceeding to configure and ' .
          'install the database.';
      }
      array_push($messages, $problem);
    }
    elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'Database configuration',
        'description' => 'The Indicia Warehouse database has been configured and installed.',
        'success' => TRUE,
      ));
    }
  }

  /**
   * Ensure that the email configuration file has been setup.
   *
   * @param array $messages
   *   List of messages that any information should be appended to.
   * @param bool $problems_only
   *   Set to true to report only the problems, not the successful checks. False reports both failures and successes.
   */
  private static function check_email(&$messages, $problems_only) {
    if (array_key_exists('skip_email', $_SESSION)) {
      if (!$problems_only) {
        array_push($messages, array(
          'title' => 'Email configuration',
          'description' => 'Email configuration has been skipped so the server may not be able to send ' .
              'forgotten password reminder emails.',
          'success' => TRUE,
        ));
      }
    } else {
      $email_config = kohana::config('email');
      if (!array_key_exists('forgotten_passwd_title', $email_config)) {
        array_push($messages, array(
          'title' => 'Email configuration',
          'description' => 'Email configuration options need to be set up to allow the Indicia Warehouse to send emails to users ' .
              'who forget their passwords.',
          'success' => FALSE,
          'action' => array(
            'title' => 'Configure email',
            'link' => 'config_email'
          ),
        ));
      }
      else if (!array_key_exists('test_result', $email_config) ||
          $email_config['test_result'] != 'pass') {
        array_push($messages, array(
          'title' => 'Email configuration',
          'description' => 'Email configuration has not been tested. The Indicia Warehouse might not be able to send emails to users ' .
              'who forget their passwords.',
          'success' => FALSE,
          'action' => array(
            'title' => 'Configure email',
            'link' => 'config_email',
          )
        ));
      }
      elseif (!$problems_only) {
        array_push($messages, array(
          'title' => 'Email configuration',
          'description' => 'Configuration of server side emails completed.',
          'success' => TRUE,
        ));
      }
    }
  }

  /**
   * Ensure that the PHP version running on the server is at least 5.2, which supports
   * JSON properly.
   *
   * @param array $messages
   *   List of messages that any information should be appended to.
   * @param bool $problems_only
   *   Set to true to report only the problems, not the successful checks.
   *   False reports both failures and successes.
   */
  private static function check_php_version(&$messages, $problems_only) {
    if (PHP_VERSION_ID < 50600) {
      array_push($messages, array(
        'title' => 'PHP Version',
        'description' => 'Your PHP version is ' . phpversion() . ' which is unsupported. Please upgrade the PHP ' .
          'installation on this web server to version 5.6 or higher.',
        'success' => FALSE,
      ));
    }
    elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'PHP Version',
        'description' => 'PHP version is ' . phpversion(),
        'success' => TRUE,
      ));
    }
  }

  /**
   * Ensure that the PHP PostgreSQL extensions are available.
   *
   * @param array $messages
   *   List of messages that any information should be appended to.
   * @param bool $problems_only
   *   Set to true to report only the problems, not the successful checks.
   *   False reports both failures and successes.
   */
  private static function check_postgres(&$messages, $problems_only) {
    if (!function_exists('pg_version')) {
      array_push($messages, array(
        'title' => 'PostgreSQL PHP Extensions',
        'description' => 'The PostgreSQL extensions are not available on this installation of PHP. To fix this, find your php.ini file in the PHP installation folder and ' .
            'find the line <strong>;extension=php_pgsql.dll</strong> or <strong>;extension=pgsql</strong>. Remove the semi-colon from the start of the line and save the file, then restart your ' .
            'webserver. Please pass this information to the administrator of your webserver if you are not sure how to do this.',
        'success' => FALSE,
      ));
    }
    elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'PostgreSQL PHP Extensions',
        'description' => 'The PostgreSQL extensions for PHP are available on this PHP installation.',
        'success' => TRUE,
      ));
    }

  }

  /**
   * Ensure that the cUrl library is installed.
   *
   * @param array $messages
   *   List of messages that any information should be appended to.
   * @param boolean $problems_only
   *   Set to true to report only the problems, not the successful checks.
   *   False reports both failures and successes.
   */
  private static function check_curl(&$messages, $problems_only) {
    if (!function_exists('curl_exec')) {
      array_push($messages, array(
        'title' => 'cUrl Library',
        'description' => 'The cUrl library is not installed on this web server. To fix this, find your php.ini file in the PHP installation folder and ' .
            'find the line <strong>;extension=php_curl.dll</strong>. Remove the semi-colon from the start of the line and save the file, then restart your ' .
            'webserver. Please pass this information to the administrator of your webserver if you are not sure how to do this.',
        'success' => FALSE,
      ));
    }
    elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'cUrl Library',
        'description' => 'The cUrl library is installed.',
        'success' => TRUE,
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
        'description' => 'The gd2 library is not installed on this web server. To fix this, find your php.ini file in the PHP installation folder and ' .
            'find the line <strong>;extension=php_gd2.dll</strong>. Remove the semi-colon from the start of the line and save the file, then restart your ' .
            'webserver. Please pass this information to the administrator of your webserver if you are not sure how to do this.',
        'success' => FALSE,
      ));
    }
    elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'gd2 Library',
        'description' => 'The gd2 library is installed.',
        'success' => TRUE,
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
      $description = <<<MSG
The zip library is not installed on this web server. This is required to enable upload of SHP files containing location
boundaries and download of Darwin Core Archive format files, but otherwise does not stop Indicia working. To fix this
for Windows servers, find your php.ini file in the PHP installation folder and find the line
<strong>;extension=php_zip.dll</strong>. Remove the semi-colon from the start of the line and save the file, then
restart your webserver. For Linux systems, you must compile PHP with zip support by using the --enable-zip configuration
option. Please pass this information to the administrator of your webserver if you are not sure how to do this.
MSG;
      array_push($messages, array(
        'title' => 'Zip Library',
        'description' => "<p>$description</p>",
        'success' => TRUE,
        'warning' => TRUE,
      ));
    }
    elseif (!$problems_only) {
      array_push($messages, array(
        'title' => 'Zip Library',
        'description' => '<p>The Zip library is installed.</p>',
        'success' => TRUE,
      ));
    }
  }

  /**
   * Ensure that the various directories required by the installation have the correct
   * permissions. Public so that it can be accessed individually by the ack_permissions
   * page.
   *
   * @param array $messages
   *   List of messages that any information should be appended to.
   * @param bool $problems_only
   *   Set to true to report only the problems, not the successful checks.
   *   False reports both failures and successes.
   */
  public static function check_dir_permissions(&$messages, $problems_only) {
    // list of messages about each directory we need access to.
    $good_dirs = array();
    $bad_dirs = array();
    $readonly = TRUE;
    $writeable = FALSE;
    self::check_dir_permission($writeable, $good_dirs, $bad_dirs, 'configuration',
          dirname(dirname(dirname(dirname(__file__ )))) . '/application/config',
          'the installation settings to be stored correctly',
          'the installation settings cannot be stored');
    self::check_dir_permission($writeable, $good_dirs, $bad_dirs, 'cache',
          dirname(dirname(dirname(dirname(__file__ )))) . '/application/cache',
          'the warehouse to cache information to improve performance',
          'the warehouse cannot cache information to improve performance');
    if (array_key_exists('ack_permissions', $_SESSION)) {
      if (!$problems_only) {
        array_push($messages, array(
          'title' => 'Directory Access',
          'description' => 'Non-essential problems with the directory access permissions have been acknowledged by you.',
          'success' => TRUE,
        ));
      }
    }
    else {
      self::check_dir_permission($writeable, $good_dirs, $bad_dirs, 'image upload',
          dirname(dirname(dirname(dirname(__file__)))) . '/upload',
          'images to be uploaded',
          'images cannot be uploaded');
      self::check_dir_permission($writeable, $good_dirs, $bad_dirs, 'image upload queue',
          dirname(dirname(dirname(dirname(__file__)))) . '/upload-queue',
          'queued images to be uploaded',
          'queued images cannot be uploaded');
      self::check_dir_permission($writeable, $good_dirs, $bad_dirs, 'configuration',
          dirname(dirname(dirname(dirname(__file__)))) . '/client_helpers',
          'the settings for the data entry helper classes to be stored',
          'the settings for the data entry helper classes cannot be stored');
      self::check_dir_permission($writeable, $good_dirs, $bad_dirs, 'data upload',
          dirname(dirname(dirname(dirname(__file__)))) . '/client_helpers/upload',
          'data to be uploaded',
          'data cannot be uploaded');
      self::check_dir_permission($readonly, $good_dirs, $bad_dirs, 'reports',
          dirname(dirname(dirname(dirname(__file__)))) . '/reports',
          'the report templates to be accessed',
          'the reports templates cannot be aceessed');
      self::check_dir_permission($readonly, $good_dirs, $bad_dirs, 'trigger templates',
          dirname(dirname(dirname(dirname(__file__)))) . '/reports/trigger_templates',
          'the trigger and notification templates to be accessed',
          'the trigger and notification templates cannot be accessed');
      self::check_dir_permission($readonly, $good_dirs, $bad_dirs, 'database update folders',
          dirname(dirname(dirname(dirname(__file__)))) . '/modules/indicia_setup/db',
          'the database upgrades to be accessed',
          'the database upgrades cannot be accessed');

      if (count($good_dirs) > 0 && !$problems_only) {
        array_push($messages, array(
          'title' => 'Correct Directory Access',
          'description' => implode('<br/>', $good_dirs),
          'success' => TRUE,
        ));
      }
      if (count($bad_dirs) > 0) {
        array_push($messages, array(
          'title' => 'Directory Access',
          'description' => '<ul><li>' . implode('</li><li>', $bad_dirs) . '</li></ul>',
          'success' => FALSE,
          'action' => array('title' => 'Acknowledge', 'link' => 'ack_permissions'),
        ));
      }
    }
  }

  /**
   * Test the access rights to a specific directory. Appends pass and fail messages to the $good_dirs and
   * $bad_dirs arrays.
   *
   * @param bool $readonly
   *   Set to true to check that a directory is readable but not writeable. Set to false to
   *   check a directory can be written.
   * @param array $good_dirs
   *   The array which pass messages will be appended to.
   * @param array $bad_dirs
   *   The array which fail messages will be appended to.
   * @param string $folder_name
   *   The natural language description of the tested folder.
   * @param string $pass
   *   A natural language description of the pass state.
   * @param string $fail
   *   A natural language description of the fail state.
   */
  private static function check_dir_permission($readonly, &$good_dirs, &$bad_dirs, $folder_name, $dir, $pass, $fail) {
    $access_str = $readonly ? 'readable' : 'writeable';
    $dir = realpath($dir);
    if (!is_readable($dir)) {
      array_push($bad_dirs,
          "The $folder_name directory at $dir isn't readable by PHP scripts. This means that $fail.");
    }
    elseif ($readonly && is_writeable($dir)) {
      array_push($bad_dirs, "The $folder_name directory at $dir is writeable. It should be readonly otherwise " .
            "it presents an unnecessary security risk.");
    }
    elseif (!$readonly && !is_writeable($dir)) {
      array_push($bad_dirs,
          "The $folder_name directory at $dir isn't writeable by PHP scripts. This means that $fail.");
    }
    else {
      array_push($good_dirs, "The $folder_name directory is $access_str to allow $pass.");
    }
  }

}
