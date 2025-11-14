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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/warehouse
 */

/**
 * Helper class for cache_builder functionality.
 */
class cache_builder {

  /**
   * Allow cache table updates to be delegated to work_queue.
   *
   * Useful when processing large numbers of records where immediate visibility
   * of the results is not required.
   *
   * @var bool
   */
  public static $delayCacheUpdates = FALSE;

  /**
   * Performs the actual task of table population.
   */
  public static function populate_cache_table($db, $table, $last_run_date) {
    $queries = kohana::config("cache_builder.$table");
    $needsUpdateTable = pg_escape_identifier($db->getLink(), "needs_update_$table");
    try {
      echo "<h3>$table</h3>";
      $count = cache_builder::getChangeList($db, $table, $queries, $last_run_date);
      if ($count > 0) {
        cache_builder::makeChangesWithOutput($db, $table, $count);
      }
      else {
        echo "<p>No changes</p>";
      }
      $db->query("DROP TABLE IF EXISTS $needsUpdateTable");
    }
    catch (Exception $e) {
      $db->query("DROP TABLE IF EXISTS $needsUpdateTable");
      throw $e;
    }
  }

  /**
   * Apply changes to cache tables, with HTML table summary.
   *
   * See makeChanges().
   */
  public static function makeChangesWithOutput($db, $table, $count) {
    echo <<<HTML
<table>
  <thead>
    <tr><th></th><th># records affected</th></tr>
  </thead>
  <tbody>
    <tr><th scope="row">Total</th><td>$count</td>
    </tr>

HTML;
    cache_builder::makeChanges($db, $table);
    echo <<<HTML
  </tbody>
</table>
HTML;
  }

  /**
   * Apply required database changes to the cache tables.
   *
   * When the needs_update_* table already populated, apply the actual cache
   * update changes to the cached entity.
   *
   * @param object $db
   *   Database connection.
   * @param string $table
   *   Entity name to update (e.g. sample, occurrence, taxa_taxon_list).
   */
  public static function makeChanges($db, $table) {
    $queries = kohana::config("cache_builder.$table");
    cache_builder::do_delete($db, $table, $queries);
    // Preprocess some of the tags in the queries.
    if (is_array($queries['update'])) {
      foreach ($queries['update'] as &$sql) {
        $sql = str_replace('#join_needs_update#', $queries['join_needs_update'], $sql);
      }
    }
    else {
      $queries['update'] = str_replace('#join_needs_update#', $queries['join_needs_update'], $queries['update']);
    }
    cache_builder::run_statement($db, $table, $queries['update'], 'update');
    // Preprocess some of the tags in the queries.
    if (is_array($queries['insert'])) {
      foreach ($queries['insert'] as &$sql) {
        $sql = str_replace('#join_needs_update#', $queries['join_needs_update'] . ' and (nu.deleted=false or nu.deleted is null)', $sql);
      }
    }
    else {
      $queries['insert'] = str_replace('#join_needs_update#', $queries['join_needs_update'] . ' and (nu.deleted=false or nu.deleted is null)', $queries['insert']);
    }
    cache_builder::run_statement($db, $table, $queries['insert'], 'insert');
    if (isset($queries['extra_multi_record_updates'])) {
      cache_builder::run_statement($db, $table, $queries['extra_multi_record_updates'], 'final update');
    }
    if (!variable::get("populated-$table")) {
      $tableEsc = pg_escape_identifier($db->getLink(), $table);
      $cacheTableEsc = pg_escape_identifier($db->getLink(), "cache_$table");
      $cacheQuery = $db->query("select count(*) from $cacheTableEsc")->result_array(FALSE);
      if (isset($queries['count'])) {
        $totalQuery = $db->query($queries['count'])->result_array(FALSE);
      }
      else {
        $totalQuery = $db->query("select count(*) from $tableEsc where deleted='f'")->result_array(FALSE);
      }
      $percent = round($cacheQuery[0]['count'] * 100 / $totalQuery[0]['count']);
      echo "<p>Initial population of $table progress $percent%.</p>";
    }
  }

  /**
   * Inserts a single record into the cache, e.g. could be used as soon as a record is submitted.
   *
   * @param object $db
   *   Database object.
   * @param string $table
   *   Plural form of the table name.
   * @param array $ids
   *   Record IDs to insert in the cache.
   */
  public static function insert($db, $table, array $ids) {
    if (count($ids) > 0) {
      $idList = implode(',', $ids);
      warehouse::validateIntCsvListParam($idList);
      if (self::$delayCacheUpdates && in_array($table, ['occurrences', 'samples'])) {
        kohana::log('debug', "Delayed inserts for $table ($idList)");
        self::delayChangesViaWorkQueue($db, $table, $idList);
      }
      else {
        $master_list_id = warehouse::getMasterTaxonListId();
        $queries = kohana::config("cache_builder.$table");
        if (!isset($queries['key_field'])) {
          throw new exception("Cannot do a specific record insert into cache as the key_field configuration not defined in cache_builder configuration for $table");
        }
        if (!is_array($queries['insert'])) {
          $queries['insert'] = [$queries['insert']];
        }
        foreach ($queries['insert'] as $query) {
          $insertSql = str_replace(
            ['#join_needs_update#', '#master_list_id#'],
            ['', $master_list_id],
            $query
          );
          $insertSql .= ' and ' . $queries['key_field'] . " in ($idList)";
          $db->query($insertSql);
        }
      }
      self::final_queries($db, $table, $ids);
    }
  }

  /**
   * Updates a single record in the cache.
   *
   * E.g. could be used as soon as a record is edited.
   *
   * @param object $db
   *   Database object.
   * @param string $table
   *   Plural form of the table name.
   * @param array $ids
   *   Record IDs to insert in the cache.
   */
  public static function update($db, $table, array $ids) {
    if (count($ids) > 0) {
      $idList = implode(',', $ids);
      warehouse::validateIntCsvListParam($idList);
      if (self::$delayCacheUpdates && in_array($table, ['occurrences', 'samples'])) {
        kohana::log('debug', "Delayed updates for $table ($idList)");
        self::delayChangesViaWorkQueue($db, $table, $idList);
      }
      else {
        $master_list_id = warehouse::getMasterTaxonListId();
        $queries = kohana::config("cache_builder.$table");
        if (!isset($queries['key_field'])) {
          throw new exception('Cannot do a specific record update into cache as the key_field configuration not defined in cache_builder configuration');
        }
        if (!is_array($queries['update'])) {
          $queries['update'] = [$queries['update']];
        }
        foreach ($queries['update'] as $query) {
          $updateSql = str_replace(
            ['#join_needs_update#', '#master_list_id#'],
            ['', $master_list_id],
            $query
          );
          $updateSql .= ' and ' . $queries['key_field'] . " in ($idList)";
          $db->query($updateSql);
        }
        self::final_queries($db, $table, $ids);
      }
    }
  }

  /**
   * Deletes a single record from the cache.
   *
   * E.g. could be used as soon as a record is deleted.
   *
   * @param object $db
   *   Database object.
   * @param string $table
   *   Plural form of the table name.
   * @param array $ids
   *   Record IDs to delete from the cache.
   */
  public static function delete($db, $table, array $ids) {
    if (self::$delayCacheUpdates && in_array($table, ['occurrences', 'samples'])) {
      self::delayChangesViaWorkQueue($db, $table, implode(',', $ids));
    }
    else {
      foreach ($ids as $id) {
        if ($table === 'occurrences' || $table === 'samples') {
          $db->delete("cache_{$table}_functional", ['id' => $id]);
          $db->delete("cache_{$table}_nonfunctional", ['id' => $id]);
          if ($table === 'samples') {
            // Slightly more complex delete query to ensure indexes used.
            $sql = <<<SQL
DELETE FROM cache_occurrences_functional o
USING samples s
JOIN surveys su on su.id=s.survey_id
WHERE s.id=$id
AND o.sample_id=s.id
AND o.survey_id=su.id
AND o.website_id=su.website_id
SQL;
            $db->query($sql);
            $db->query('delete from cache_occurrences_nonfunctional where id in (select id from occurrences where sample_id=?)', [$id]);
          }
        }
        else {
          $db->delete("cache_$table", ['id' => $id]);
        }
      }
    }
  }

  /**
   * If submitting occurrence changes without a sample, update sample tracking.
   *
   * This is so that any sample data feeds receive an updated copy of the
   * sample, as the occurrence statistics will have changed.
   *
   * @param object $db
   *   Database object.
   * @param array $ids
   *   List of occurrences affected by a submission.
   */
  public static function updateSampleTrackingForOccurrences($db, array $ids) {
    $idList = implode(',', $ids);
    warehouse::validateIntCsvListParam($idList);
    $sql = <<<SQL
UPDATE cache_samples_functional s
SET website_id=s.website_id
FROM cache_occurrences_functional o
WHERE o.id IN ($idList)
AND (s.id=o.sample_id OR s.id=o.parent_sample_id);
SQL;
    $db->query($sql);
  }

  /**
   * During an import, add tasks to work queue rather than do immediate update.
   *
   * Allows performance improvement during import.
   *
   * @param object $db
   *   Database object.
   * @param string $table
   *   Plural form of the table name.
   * @param string $idCsv
   *   Record IDs to delete from the cache (comma separated string).
   */
  private static function delayChangesViaWorkQueue($db, $table, $idCsv) {
    warehouse::validateIntCsvListParam($idCsv);
    $entity = inflector::singular($table);
    // Priority 1 work_queue tasks so it precedes things like spatial indexing
    // or attributes population.
    $sql = <<<SQL
-- Comment necessary to prevent Kohana calling LASTVAL().
INSERT INTO work_queue(task, entity, record_id, params, cost_estimate, priority, created_on)
SELECT 'task_cache_builder_update', '$entity', t.id, null, 100, 1, now()
FROM $table t
LEFT JOIN work_queue w ON w.task='task_cache_builder_update' AND w.entity='$entity' AND w.record_id=t.id
WHERE t.id IN ($idCsv)
AND w.id IS NULL;
SQL;
    $db->query($sql);
  }

  public static function final_queries($db, $table, $ids) {
    $queries = kohana::config("cache_builder.$table");
    $doneCount = 0;
    if (isset($queries['extra_single_record_updates'])) {
      $idList = implode(',', $ids);
      warehouse::validateIntCsvListParam($idList);
      if (is_array($queries['extra_single_record_updates'])) {
        foreach ($queries['extra_single_record_updates'] as $key => &$sql) {
          $result = $db->query(str_replace('#ids#', $idList, $sql));
          $doneCount += $result->count();
          if ($doneCount >= count($ids)) {
            // We've updated all, so can drop out.
            break;
          }
        }
      }
      else {
        $db->query(str_replace('#ids#', $idList, $queries['extra_single_record_updates']));
      }
    }
  }

  /**
   * Build a temporary table with the list of IDs of records we need to update.
   *
   * The table has a deleted flag to indicate newly deleted records.
   *
   * @param object $db
   *   Database connection.
   * @param string $table
   *   Name of the table being cached, e.g. occurrences.
   * @param string $queries
   *   List of configured queries for this table.
   * @param string $last_run_date
   *   Date/time of the last time the cache builder was run, used to filter
   *   records to only the recent changes. Supplied as a string suitable for
   *   injection into an SQL query.
   */
  private static function getChangeList($db, $table, $queries, $last_run_date) {
    $query = str_replace('#date#', $last_run_date, $queries['get_changed_items_query']);
    $needsUpdateTable = pg_escape_identifier($db->getLink(), "needs_update_$table");
    $db->query("create temporary table $needsUpdateTable as $query");
    if (!variable::get("populated-$table")) {
      // As well as the changed records, pick up max 5000 previous records,
      // which is important for initial population. 5000 is an arbitrary number
      // to compromise between performance and cache population.
      $query = $queries['get_missing_items_query'] . ' limit 5000';
      $result = $db->query("insert into $needsUpdateTable $query");
      if ($result->count() === 0) {
        // Flag that we don't need to do any more previously existing records
        // as they are all done.
        // Future cache updates can just pick up changes from now on.
        variable::set("populated-$table", TRUE);
        echo "<p>Initial population of $table completed</p>";
      }
    }
    $constraintName = pg_escape_identifier($db->getLink(), "ix_nu_$table");
    $db->query("ALTER TABLE $needsUpdateTable ADD CONSTRAINT $constraintName PRIMARY KEY (id)");
    $r = $db->query("select count(*) as count from $needsUpdateTable")->result_array(FALSE);
    $row = $r[0];
    return $row['count'];
  }

  /**
   * Deletes all records from the cache table which are in the table of
   * records to update and where the deleted flag is true.
   *
   * @param object $db
   *   Database connection.
   * @param string $table
   *   Name of the table being cached.
   * @param array $queries
   *   List of configured queries for this table, which might include non-default delete queries.
   */
  private static function do_delete($db, $table, $queries) {
    // Set up a default delete query if none are specified.
    if (!isset($queries['delete_query'])) {
      $needsUpdateTable = pg_escape_identifier($db->getLink(), "needs_update_$table");
      $cacheTable = pg_escape_identifier($db->getLink(), "cache_$table");
      $queries['delete_query'] = ["delete from $cacheTable where id in (select id from $needsUpdateTable where deleted=true)"];
    }
    $count = 0;
    foreach ($queries['delete_query'] as $query) {
      $count += $db->query($query)->count();
    }
    if (variable::get("populated-$table")) {
      echo "    <tr><th scope=\"row\">Delete(s)</th><td>$count</td></tr>\n";
    }
  }

  /**
   * Runs an insert or update statemnet to update one of the cache tables.
   *
   * @param object $db
   *   Database connection.
   * @param string $query
   *   Query used to perform the update or insert. Can be a string, or an
   *   associative array of SQL strings if multiple required to do the task.
   * @param string $action
   *   Term describing the action, used for feedback only.
   */
  private static function run_statement($db, $table, $query, $action) {
    $master_list_id = warehouse::getMasterTaxonListId();
    if (is_array($query)) {
      foreach ($query as $title => $sql) {
        $sql = str_replace('#master_list_id#', $master_list_id, $sql);
        $count = $db->query($sql)->count();
        if (variable::get("populated-$table")) {
          echo "    <tr><th scope=\"row\">$action(s) for $title</th><td>$count</td></tr>\n";
        }
      }
    }
    else {
      $sql = str_replace('#master_list_id#', $master_list_id, $query);
      $count = $db->query($query)->count();
      if (variable::get("populated-$table")) {
        echo "    <tr><th scope=\"row\">$action(s)</th><td>$count</td></tr>\n";
      }
    }
  }
}