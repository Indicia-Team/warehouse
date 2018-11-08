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
