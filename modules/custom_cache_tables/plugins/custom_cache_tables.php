<?php

/**
 * @file
 * Warehouse plugin code for the custom cache table module.
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
 * Integrate the custom_cache_tables module with Indicia's scheduled tasks.
 *
 * This module allows custom tables to be periodically rebuilt which cache data used in reports.
 *
 * @param string $last_run_date
 *   Timestamp of last run of plugin scheduled task.
 * @param obj $db
 *   Database object.
 */
function custom_cache_tables_scheduled_task($last_run_date, $db) {
  try {
    custom_cache_tables::populateTables($db);
  }
  catch (Exception $e) {
    echo $e->getMessage();
  }
}
