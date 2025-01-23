<?php

/**
 * @file
 * Queue worker to remove deleted locations from cache_*_functional.location_ids.
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
 * Queue worker to remove deleted locations from cache_*_functional.location_ids.
 */
class task_spatial_index_builder_location_delete {

  public const BATCH_SIZE = 100;

  /**
   * Work_queue class will automatically expire the completed tasks.
   *
   * @const bool
   */
  public const SELF_CLEANUP = FALSE;

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
    $qry = <<<SQL
DROP TABLE IF EXISTS loclist;
SELECT record_id INTO temporary loclist FROM work_queue WHERE claimed_by=? AND entity='location';

UPDATE cache_occurrences_functional u
SET location_ids=array_remove(u.location_ids, l.record_id)
FROM loclist l
WHERE u.location_ids @> ARRAY[l.record_id];

UPDATE cache_samples_functional u
SET location_ids=array_remove(u.location_ids, l.record_id)
FROM loclist l
WHERE u.location_ids @> ARRAY[l.record_id];

UPDATE locations u
SET higher_location_ids=array_remove(u.higher_location_ids, l.record_id)
FROM loclist l
WHERE u.higher_location_ids @> ARRAY[l.record_id];

SQL;
    $db->query($qry, [$procId]);
  }
}