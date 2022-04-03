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
 * @author Indicia Team
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
    $db->query("DROP TABLE import_temp.$table->table_name");

  }
}
