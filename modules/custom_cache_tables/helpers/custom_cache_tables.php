<?php

/**
 * @file
 * Custom cache tables module helper class.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Helper class for custom_cache_tables functionality.
 */
class custom_cache_tables {

  /**
   * Trigger the rebuild of all due custom cache tables.
   *
   * @param obj $db
   *   Database object.
   */
  public static function populateTables($db) {
    $lastDoneInfo = (array) variable::get('custom_cache_tables', array(), FALSE);
    foreach (glob(MODPATH . "custom_cache_tables/definitions/*.php") as $filename) {
      require_once $filename;
      $defname = preg_replace('/\.php$/', '', basename($filename));
      if (!function_exists("get_{$defname}_query") || !function_exists("get_{$defname}_metadata")) {
        kohana::log('error', "Skipping incomplete custom_cache_tables definition $filename");
        // Next foreach iteration.
        continue;
      }
      $metadata = call_user_func("get_{$defname}_metadata");
      if (empty($metadata['frequency'])) {
        kohana::log('error', "Definition $filename omits metadata frequency for custom_cache_tables");
        // Next foreach iteration.
        continue;
      }
      if (empty($lastDoneInfo[$defname]) || strtotime($lastDoneInfo[$defname]) < strtotime("-$metadata[frequency]")) {
        // For a new cache table, use now as the starting point to trigger population.
        if (empty($lastDoneInfo[$defname])) {
          $lastDoneInfo[$defname] = date(DATE_ISO8601);
        }
        // Even if we are due an update, we might not have to do anything if there is a detect_changes_query
        // which returns nothing.
        if (!empty($metadata['detect_changes_query'])) {
          $check = $db->query(str_replace('#date#', date('Y-m-d H:i:s', strtotime($lastDoneInfo[$defname])),
              $metadata['detect_changes_query']))->current();
          if (!$check->count) {
            kohana::log('debug', "Skipping $defname as no changes available to process");
            // Reset the time to the next check.
            $lastDoneInfo[$defname] = date(DATE_ISO8601);
            // Next foreach iteration.
            continue;
          }
        }
        // If the table already exists, delete it.
        if (!empty($lastDoneInfo[$defname]) && (!isset($metadata['autodrop']) || $metadata['autodrop'] === TRUE)) {
          $db->query("DROP TABLE IF EXISTS custom_cache_tables." . pg_escape_identifier($db->getLink(), $defname));
        }
        echo "Building cache table $defname<br/>";
        self::buildTable($db, $defname);
        $lastDoneInfo[$defname] = date(DATE_ISO8601);
      }
    }
    variable::set('custom_cache_tables', $lastDoneInfo);
  }

  /**
   * Runs the query required to build a single table.
   *
   * @param obj $db
   *   Database object.
   * @param string $defname
   *   Custom cache table definition name.
   */
  private static function buildTable($db, $defname) {
    if (function_exists("get_{$defname}_query")) {
      $qry = call_user_func("get_{$defname}_query");
      $db->query($qry);
    }
  }

}
