<?php

/**
 * @file
 * Data cleaner plugin functions.
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
 * Returns plugin metadata.
 *
 * Identifies that the plugin uses the occdelta table to identify changes.
 *
 * @return array
 *   Metadata.
 */
function record_cleaner_metadata() {
  return [
    'requires_occurrences_delta' => TRUE,
    // Should be run after the cache_builder and data_cleaner plugins.
    'weight' => 3,
  ];
}

/**
 * Hook into the task scheduler to run the rules against new records.
 */
function record_cleaner_scheduled_task($timestamp, $db, $endtime) {
  $api = new RecordCleanerApi($db);
  $api->processRecords($endtime);
}
