<?php

/**
 * @file
 * Queue worker to update cache_* tables.
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
 * Queue worker to update cache_* tables.
 *
 * Class called when a task_cache_builder_update task encountered in the work
 * queue. Updates appropriate cache tables.
 */
class task_cache_builder_update {

  /**
   * Update limit to 1000 so not too resource hungry.
   */
  const BATCH_SIZE = 1000;

  /**
   * Perform the processing for a task batch found in the queue.
   *
   * @param object $db
   *   Database connection object.
   * @param object $taskType
   *   Object read from the database for the task batch. Contains the task
   *   name, entity, priority, created_on of the first record in the batch
   *   count (total number of queued tasks of this type).
   * @param string $procId
   *   Unique identifier of this work queue processing run. Allows filtering
   *   against the work_queue table's claimed_by field to determine which
   *   tasks to perform.
   */
  public static function process($db, $taskType, $procId) {
    $table = inflector::plural($taskType->entity);
    $sql = <<<SQL
CREATE TEMPORARY TABLE needs_update_$table AS
SELECT record_id AS id, COALESCE(params->>'deleted' = 'true', false) AS deleted
FROM work_queue
WHERE entity='$taskType->entity' AND claimed_by='$procId'
SQL;
    $db->query($sql);
    $db->query("ALTER TABLE needs_update_$table ADD CONSTRAINT ix_nu_$table PRIMARY KEY (id)");
    cache_builder::makeChanges($db, $table);
    $ids = [];
    $sql = <<<SQL
SELECT DISTINCT id FROM needs_update_$table;
SQL;
    $rows = $db->query($sql)->result();
    foreach($rows as $row) {
      $ids[] = $row->id;
    }
    if ($table === 'samples') {
      postgreSQL::insertMapSquaresForSamples($ids, $db);
    }
    elseif ($table === 'occurrences') {
      postgreSQL::insertMapSquaresForOccurrences($ids, $db);
    }
  }

}
