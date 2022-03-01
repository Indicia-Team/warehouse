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
 * Limited warehouse support for hostsite_get_user_field().
 */
function hostsite_get_user_field($field) {
  if ($field === 'language') {
    return 'en';
  }
  elseif ($field === 'indicia_user_id') {
    return isset($_SESSION) ? $_SESSION['auth_user']->id : 0;
  }
  elseif ($field === 'training') {
    return FALSE;
  }
  else {
    throw new exception("Unsuppoered hostsite_get_user_field call on warehouse for field $field");
  }
}

/**
 * Helper class to provide generally useful Indicia warehouse functions.
 */
class warehouse {

  private static $sharingMappings = [
    'R' => 'reporting',
    'V' => 'verification',
    'P' => 'peer review',
    'D' => 'data flow',
    'M' => 'moderation',
    'E' => 'editing',
  ];

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
    return empty($masterTaxonListId) ? 0 : $masterTaxonListId;
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

}
