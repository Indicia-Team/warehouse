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
 * @package	Modules
 * @subpackage Summary_builder
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Helper class for custom_cache_tables functionality
 *
 * @package	Modules
 * @subpackage Custom_cache_tables
 */
class custom_cache_tables {

  public static function populate_tables($db, $last_run_date) {
    $lastDoneInfo = (array)variable::get('custom_cache_tables', array(), false);
    foreach (glob(MODPATH . "custom_cache_tables/definitions/*.php") as $filename) {
      require_once $filename;
      $defname = preg_replace('/\.php$/', '', basename($filename));
      if (!function_exists("get_{$defname}_query") || !function_exists("get_{$defname}_metadata") ) {
        kohana::log('error', "Skipping incomplete custom_cache_tables definition $filename");
        continue; // foreach
      }
      $metadata = call_user_func("get_{$defname}_metadata");
      if (empty($metadata['frequency'])) {
        kohana::log('error', "Definition $filename omits metadata frequency for custom_cache_tables");
        continue; // foreach
      }
      if (empty($lastDoneInfo[$defname]) || strtotime($lastDoneInfo[$defname]) < strtotime("-$metadata[frequency]")) {
        // for a new cache table, use now as the starting point to trigger population
	      if (empty($lastDoneInfo[$defname]))
		      $lastDoneInfo[$defname] = date(DATE_ISO8601);
        // Even if we are due an update, we might not have to do anything if there is a detect_changes_query
        // which returns nothing
        if (!empty($metadata['detect_changes_query'])) {
          $check = $db->query(str_replace('#date#', date('Y-m-d H:i:s', strtotime($lastDoneInfo[$defname])),
              $metadata['detect_changes_query']))->current();
          if (!$check->count) {
            kohana::log('debug', "Skipping $defname as no changes available to process");
            // reset the time to the next check
            $lastDoneInfo[$defname]=date(DATE_ISO8601);
            continue; // foreach
          }
        }
        // if the table already exists, delete it
        if (!empty($lastDoneInfo[$defname]))
          $db->query("DROP TABLE custom_cache_tables.$defname");
        echo "building cache table $defname<br/>";
        self::build_table($db, $defname);
        $lastDoneInfo[$defname]=date(DATE_ISO8601);
      }
    }
    variable::set('custom_cache_tables', $lastDoneInfo);
  }

  private static function build_table($db, $defname) {
    if (function_exists("get_{$defname}_query")) {
      $qry = call_user_func("get_{$defname}_query");
      $db->query($qry);
    }
  }



}