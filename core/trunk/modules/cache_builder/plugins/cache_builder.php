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
 * @package	Verification Check
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Hook into the task scheduler. This uses the queries defined in the cache_builder.php
 * file to create and populate cache tables. The tables are not always up to date as they
 * are only updated when the scheduler runs, but they have the advantage of simplifying
 * the data model for reporting as well as reducing the need to join in queries, therefore
 * significantly improving report performance.
 * @param string $last_run_date Date last run, or null if never run
 * 
 */
function cache_builder_scheduled_task($last_run_date) {
  if ($last_run_date===null)
    // first run, so get all records. This should be early enough!
    $last_run_date='1000-01-01';
  $db = new Database();
  try {
    foreach (kohana::config('cache_builder') as $table=>$queries) {
      cache_builder_get_changelist($db, $table, $queries['get_changelist_query'], $last_run_date);
      try {
        cache_builder_do_delete($db, $table);
        cache_builder_run_statement($db, $queries['update'], 'update');
        cache_builder_run_statement($db, $queries['insert'], 'insert');
        echo '<br/>';
        $db->query("drop table needs_update_$table");
      } catch (Exception $e) {
        $db->query("drop table needs_update_$table");
        echo $e->getMessage();
      }
    }
  } catch (Exception $e) {
    echo $e->getMessage();
  }
}

/**
 * Build a temporary table with the list of IDs of records we need to update.
 * The table has a deleted flag to indicate newly deleted records.
 * @param objcet $db Database connection.
 * @param string $table Name of the table being cached, e.g. occurrences.
 * @param string $query A query which selects a list of IDs for all new, updated or
 * deleted records (including looking for updates or deletions caused by related 
 * records).
 * @param string $last_run_date Date/time of the last time the cache builder was 
 * run, used to filter records to only the recent changes. Supplied as a string
 * suitable for injection into an SQL query.
 */
function cache_builder_get_changelist($db, $table, $query, $last_run_date) {
  $query = str_replace('#date#', $last_run_date, $query);
  $db->query("create temporary table needs_update_$table as $query");
  $db->query("ALTER TABLE needs_update_$table ADD CONSTRAINT ix_nu_$table PRIMARY KEY (id)");
  $r = $db->query("select count(*) as count from needs_update_$table")->result_array(false);
  
  echo "Updating $table";
}

/**
 * Deletes all records from the cache table which are in the table of records to update and 
 * where the deleted flag is true.
 * @param object $db Database connection.
 * @param string $table Name of the table being cached.
 */
function cache_builder_do_delete($db, $table) {
  $query = "delete from cache_$table where id in (select id from needs_update_$table where deleted=true)";
  $count = $db->query($query)->count();
  echo ", $count delete(s)";
}

/**
 * Runs an insert or update statemnet to update one of the cache tables. 
 * @param object $db Database connection.
 * @param string $query Query used to perform the update or insert
 * @param string $action Term describing the action, used for feedback only.
 */
function cache_builder_run_statement($db, $query, $action) {
  $count = $db->query($query)->count();
  echo ", $count $action(s)";
}

?>