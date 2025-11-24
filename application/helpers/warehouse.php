<?php

/**
 * @file
 * Helper class to provide generally useful Indicia warehouse functions.
 *
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

/**
 * Stubs for hostsite_* functions that are needed by client_helper code.
 */

/**
 * Set a non-essential cookie.
 *
 * Respects settings in EU Cookie Compliance module.
 *
 * @param string $cookie
 *   Cookie name.
 * @param string $value
 *   Cookie value.
 * @param int $expire
 *   Optional expiry value.
 */
function hostsite_set_cookie($cookie, $value, $expire = NULL) {
  // Respect the remembered_fields_optin control.
  if (isset($_POST['cookie_optin']) && $_POST['cookie_optin'] === '0') {
    return;
  }
  setcookie($cookie, $value, $expire);
  // Cookies are only set when the page is loaded. So, fudge the cookie array.
  $_COOKIE[$cookie] = $value;
}

/**
 * Limited warehouse support for hostsite_get_user_field().
 */
function hostsite_get_user_field($field) {
  if ($field === 'language') {
    return 'en';
  }
  elseif ($field === 'indicia_user_id') {
    // PostedUserId is to support tests.
    global $postedUserId;
    return isset($_SESSION) ? $_SESSION['auth_user']->id : ($postedUserId ?? 0);
  }
  elseif ($field === 'training') {
    return FALSE;
  }
  elseif ($field === 'taxon_groups' || $field === 'location' || $field === 'location_expertise' || $field === 'location_collation') {
    // Unsupported client website fields.
    return FALSE;
  }
  else {
    throw new exception("Unsupported hostsite_get_user_field call on warehouse for field $field");
  }
}

/**
 * Helper class to provide generally useful Indicia warehouse functions.
 */
class warehouse {

  /**
   * Mappings from sharing codes to associated terms for website agreements.
   *
   * @var array
   */
  private static $sharingMappings = [
    'R' => 'reporting',
    'V' => 'verification',
    'P' => 'peer_review',
    'D' => 'data_flow',
    'M' => 'moderation',
    'E' => 'editing',
  ];

  /**
   * Mappings from record status codes to associated phrases.
   *
   * @var array
   */
  private static $recordStatusMappings = [
    'V' => 'accepted',
    'V1' => 'accepted as correct',
    'V2' => 'accepted as considered correct',
    'C3' => 'marked as plausible',
    'Q' => 'queried',
    'R' => 'not accepted',
    'R4' => 'not accepted as unable to verify',
    'R5' => 'not accepted as incorrect',
    // Legacy.
    'D' => 'queried',
  ];

  /**
   * Flock file created if this process can only run one at a time.
   *
   * @var resource
   */
  private static $lock;

  /**
   * Loads any of the client helper libraries.
   *
   * Also ensures that the correct resources are loaded when the libraries are
   * used within the context of the warehouse.
   *
   * @param array $helpers
   *   Array of helper file names without the php extension.
   */
  public static function loadHelpers(array $helpers) {
    foreach ($helpers as $helper) {
      require_once DOCROOT . "client_helpers/$helper.php";
    }
    require_once DOCROOT . 'client_helpers/templates.bootstrap-3.php';
    // No need to re-link to jQuery as included in tempalate.
    helper_base::$dumped_resources[] = 'jquery';
    helper_base::$dumped_resources[] = 'jquery_ui';
    helper_base::$dumped_resources[] = 'fancybox';
    // Ensure correct protocol, in case both http and https supported.
    $protocol = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';
    helper_base::$base_url = preg_replace('/^https?/', $protocol, helper_base::$base_url);
  }

  /**
   * Clears files older than a certain age in a folder.
   *
   * @param string $path
   *   Folder path relative to the DOCROOT, without leading or trailing slash.
   * @param int $age
   *   Age in seconds. Files older than this are deleted.
   * @param array $keep
   *   Optional list of file names to keep.
   */
  public static function purgeOldFiles($path, $age, array $keep = []) {
    $base = rtrim(DOCROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $fullDir = $base . trim($path, DIRECTORY_SEPARATOR);
    if (!is_dir($fullDir)) {
      return;
    }
    // First, get an array of files sorted by date.
    $files = [];
    $dir = opendir($fullDir);
    // Skip certain file names.
    $exclude = array_merge($keep, [
      '.',
      '..',
      '.htaccess',
      'web.config',
      '.gitignore',
    ]);
    if ($dir) {
      while ($filename = readdir($dir)) {
        $fullPath = $fullDir . DIRECTORY_SEPARATOR . $filename;
        if (in_array($filename, $exclude)) {
          continue;
        }
        if (is_dir($fullPath)) {
          // Recurse into sub-folders.
          self::purgeOldFiles($path . DIRECTORY_SEPARATOR . $filename, $age, $keep);
          // Check if empty and remove if so.
          $remaining = array_diff(scandir($fullPath), ['.', '..']);
          if (empty($remaining)) {
            @rmdir($fullPath);
          }
        }
        else {
          // File node change time used for comparison - the time the file was
          // added to the folder.
          $fileTimestamp = filectime($fullPath);
          $files[] = [$fullPath, $fileTimestamp];
        }
      }
    }
    // Sort the file array by date, oldest first.
    usort($files, fn($a, $b) => $a[1] <=> $b[1]);
    // Iterate files.
    foreach ($files as $file) {
      // If we have reached a file that is not old enough to expire, don't
      // go any further. Expiry set to 1 hour.
      if ($file[1] > (time() - $age)) {
        break;
      }
      // Clear out the old file.
      if (is_file($file[0])) {
        // Ignore errors, will try again later if not deleted.
        @unlink($file[0]);
      }
    }
  }

  /**
   * Find the master taxon list's ID from config.
   *
   * This identifies the taxon list which provides an overall taxonomic
   * hierarchy. Zero returned if not set to prevent errors in SQL.
   *
   * @return int
   *   Taxon list ID or zero.
   */
  public static function getMasterTaxonListId() {
    // Preferred location in indicia config file.
    $masterTaxonListId = kohana::config('indicia.master_list_id', FALSE, FALSE);
    // Legacy support - in v1.x the setting was in the cache builder module.
    if (!$masterTaxonListId) {
      $masterTaxonListId = kohana::config('cache_builder_variables.master_list_id', FALSE, FALSE);
    }
    // If not set, default to zero for safety.
    return empty($masterTaxonListId) ? 0 : (int) $masterTaxonListId;
  }

  /**
   * A cached lookup of the websites that are available for a sharing mode.
   *
   * @param array $websiteIds
   *   ID of the website that is receiving the shared data.
   * @param object $db
   *   Database connection.
   * @param string $scope
   *   Sharing mode.
   *
   * @return array
   *   List of website IDs that will share their data.
   */
  public static function getSharedWebsiteList(array $websiteIds, $db, $scope = 'reporting') {
    if (count($websiteIds) === 1) {
      $tag = 'website-share-array-' . implode('', $websiteIds);
      $cacheId = "$tag-$scope";
      $cache = Cache::instance();
      if ($cached = $cache->get($cacheId)) {
        return $cached;
      }
    }
    $qry = $db->select('to_website_id')
      ->from('index_websites_website_agreements')
      ->where("receive_for_$scope", 't')
      ->in('from_website_id', $websiteIds)
      ->get()->result();
    $ids = [];
    foreach ($qry as $row) {
      $ids[] = $row->to_website_id;
    }
    if (count($websiteIds) === 1) {
      // Tag all cache entries for this website so they can be cleared together
      // when changes are saved.
      $cache->set($cacheId, $ids, $tag);
    }
    return $ids;
  }

  /**
   * Expand a single character sharing mode code to the full term.
   *
   * @param string $code
   *   Sharing mode code to expand.
   *
   * @return string
   *   Expanded term.
   */
  public static function sharingCodeToTerm($code) {
    return array_key_exists($code, self::$sharingMappings) ? self::$sharingMappings[$code] : $code;
  }

  /**
   * Converts a sharing term to a single character sharing mode code.
   *
   * @param string $term
   *   Sharing mode term.
   *
   * @return string
   *   Sharing mode code.
   */
  public static function sharingTermToCode($term) {
    $mappings = array_flip(self::$sharingMappings);
    return array_key_exists($term, $mappings) ? $mappings[$term] : $term;
  }

  /**
   * Expand a record status code to the full term.
   *
   * @param string $code
   *   Record status code to expand.
   * @param string $default
   *   Default to return if code not recognised. If not set, then the original
   *   code is returned.
   *
   * @return string
   *   Expanded term.
   */
  public static function recordStatusCodeToTerm($code, $default = NULL) {
    $default = $default ?? $code;
    return array_key_exists($code, self::$recordStatusMappings) ? self::$recordStatusMappings[$code] : $default;
  }

  /**
   * Find the command-line or query string parameters we are locking for.
   *
   * @return array
   *   Parameters as an associative array.
   */
  private static function getLockParameters() {
    global $argv;
    if (isset($argv)) {
      parse_str(implode('&', array_slice($argv, 1)), $params);
    }
    else {
      $params = $_GET;
    }
    return $params;
  }

  /**
   * Build a suitable filename for the lock file.
   *
   * The file name is unique for the type and parameters supplied via the
   * command-line or query string.
   *
   * @param string $type
   *   Process type name, e.g. scheduled-tasks or rest-autofeed.
   * @param array $params
   *   Associative array of parameters to define the unique lock.
   *
   * @return string
   *   A filename which includes a hash of the parameters, so that it is
   *   unique to this configuration of the scheduled tasks.
   */
  private static function getLockFilename($type) {
    $uid = md5(http_build_query(self::getLockParameters()));
    return DOCROOT . "application/cache/$type.lock-$uid.lock";
  }

  /**
   * Grab a file lock if possible.
   *
   * Will fail if another process is already running with the same type and
   * command-line or query string parameters.
   *
   * @param string $type
   *   Process type name, e.g. scheduled-tasks or rest-autofeed.
   */
  public static function lockProcess($type) {
    self::$lock = fopen(self::getLockFilename($type), 'w+');
    if (!flock(self::$lock, LOCK_EX | LOCK_NB)) {
      kohana::log('alert', "Process $type attempt aborted as already running.");
      die("\nProcess $type attempt aborted as already running.\n");
    }
    fwrite(self::$lock, 'Got a lock: ' . var_export(self::getLockParameters(), TRUE));
  }

  /**
   * Release and clean up the lock file.
   *
   * @param string $type
   *   Process type name, e.g. scheduled-tasks or rest-autofeed.
   */
  public static function unlockProcess($type) {
    fclose(self::$lock);
    unlink(self::getLockFilename($type));
  }

  /**
   * Check format of a comma-separated list of integers.
   *
   * @param string $list
   *   Comma-separated list of integers.
   * @param mixed $msg
   *   Message to throw as an exception if the format is incorrect.
   */
  public static function validateIntCsvListParam($list, $msg = 'Parameter format incorrect') {
    if (!preg_match('/^\d+(,\d+)*$/', str_replace(' ', '', $list))) {
      throw new exception($msg);
    }
  }

  /**
   * Validates that all elements in the given array are integers.
   *
   * @param array $array
   *   The array to validate.
   * @param string $msg
   *   The exception message to throw if validation fails. Default is 'Array
   *   format incorrect'.
   *
   * @throws Exception If any element in the array is not a valid integer.
   */
  public static function validateIntArray(array $array, string $msg = 'Array format incorrect') {
    foreach ($array as $value) {
      if (!filter_var($value, FILTER_VALIDATE_INT)) {
        throw new Exception($msg);
      }
    }
  }

  /**
   * Convert array of strings to a SQL IN list.
   *
   * @param mixed $db
   *   Database connection object.
   * @param array $strings
   *   Array of strings.
   *
   * @return string
   *   Strings escaped and comma-separated, ready for use in SQL In clause.
   */
  public static function stringArrayToSqlInList($db, array $strings) {
    return implode(', ', array_map(function ($s) use ($db) {
      return pg_escape_literal($db->getLink(), $s);
    }, $strings));
  }

  /**
   * Custom sort function for date comparison of files.
   *
   * @param int $a
   *   Date value 1 as Unix timestamp.
   * @param int $b
   *   Date value 2 as Unix timestamp.
   */
  private static function dateCmp($a, $b) {
    if ($a[1] < $b[1]) {
      $r = -1;
    }
    elseif ($a[1] > $b[1]) {
      $r = 1;
    }
    else {
      $r = 0;
    }
    return $r;
  }

}
