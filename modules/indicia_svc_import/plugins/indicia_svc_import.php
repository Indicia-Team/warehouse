<?php

/**
 * @file
 * Classes for the importer_2 web-services.
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

defined('SYSPATH') or die('No direct script access.');

/**
 * Hook into the task scheduler to tidy old imports.
 *
 * If imports are aborted and don't finish, temporary tables and import files
 * are left which need to be tidied.
 */
function indicia_svc_import_scheduled_task($timestamp, $db, $endtime) {
  // Keep tables and files that relate to existing background import work queue
  // items.
  $toKeep = _indicia_svc_import_get_stuff_to_keep($db);
  // Query selects tables in the import_temp schema where the date in the
  // name indicates > 1 day old (format is DHH for last 3 digits, hence 100 =
  // 1 day).
  $sql = <<<SQL
    SELECT
      table_name
    FROM information_schema.tables
    WHERE table_schema= 'import_temp'
    AND to_char(now(), 'YYYYMMDDHH24')::integer - ('0' || substring(regexp_replace(table_name, '[^0-9]', '', 'g') for 10))::integer > 100
    ORDER BY table_name ASC
    LIMIT 5;
  SQL;
  $tables = $db->query($sql);
  foreach ($tables as $table) {
    if (!in_array($table->table_name, $toKeep['tables'])) {
      $tableNameEsc = pg_escape_identifier($db->getLink(), $table->table_name);
      $db->query("DROP TABLE import_temp.$tableNameEsc");
    }
  }
  // Purge files older than 1 day.
  warehouse::purgeOldFiles('import/', 60 * 60 * 24, $toKeep['files']);
}

/**
 * Declare optional plugins which extend the import functionality.
 *
 * Plugins need to be enabled in the import_2 prebuilt form configuration.
 *
 * @param string $entity
 *   Entity being imported, e.g. occurrence.
 *
 * @return array
 *   List of plugins, keyed by plugin name with the value being a description.
 */
function indicia_svc_import_import_plugins($entity) {
  if ($entity === 'occurrence') {
    return [
      'OccurrenceLinkedLocationCodeField' => 'A plugin which allows a linked location ID sample attribute to be populated from a location code, e.g. Vice County number. Requires the sample attribute ID and location type ID as parameters.',
    ];
  }
  return [];
}

/**
 * Find a list of background import files and tables to keep.
 *
 * Any existing work queue items for background imports mean that we should
 * leave the respective import temporary tables, config files and data files in
 * situ and not purge them.
 *
 * @param Database $db
 *   Database connection.
 */
function _indicia_svc_import_get_stuff_to_keep($db) {
  $r = [
    'files' => [],
    'tables' => [],
  ];
  $queuedImportConfigIds = $db->query(<<<SQL
    SELECT params->>'config-id' AS config_id
    FROM work_queue
    WHERE task='task_import_step';
  SQL);
  foreach ($queuedImportConfigIds as $q) {
    $configFile = DOCROOT . "import/$q->config_id.json";
    if (file_exists($configFile)) {
      $f = fopen($configFile, "r");
      $config = fgets($f);
      fclose($f);
      $config = json_decode($config, TRUE);
      // Ensure the import database table and config file are preserved.
      $r['tables'][] = $config['tableName'];
      $r['files'][] = "$q->config_id.json";
      // Also preserve the import data files.
      foreach (array_keys($config['files']) ?? [] as $dataFile) {
        $r['files'][] = $dataFile;
      }
    }
  }
  return $r;
}