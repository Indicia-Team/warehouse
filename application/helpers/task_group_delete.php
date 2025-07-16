<?php

/**
 * @file
 * Queue worker to remove a deleted group's info from the cache tables.
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
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Queue worker to remove a deleted group's info from the cache tables.
 */
class task_group_delete{

  public const BATCH_SIZE = 5;

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
    // Work out the SQL required clean up after group deletion. Note that it
    // does not remove the group ID from the samples table as this facilitates
    // an undo if required.
    $procIdEsc = pg_escape_literal($db->getLink(), $procId);
    $sql = <<<SQL

UPDATE cache_samples_nonfunctional snf
SET group_title=null
FROM work_queue wq
JOIN groups g ON g.id=wq.record_id
  AND wq.task='task_group_delete'
  AND wq.entity='group'
JOIN cache_samples_functional s ON s.group_id=g.id
WHERE snf.id=s.id
AND wq.claimed_by=$procIdEsc;

UPDATE cache_samples_functional u
SET group_id=null
FROM work_queue wq
WHERE u.group_id=wq.record_id
  AND wq.task='task_group_delete'
  AND wq.entity='group'
  AND wq.claimed_by=$procIdEsc;

UPDATE cache_occurrences_functional u
SET group_id=null
FROM work_queue wq
WHERE u.group_id=wq.record_id
  AND wq.task='task_group_delete'
  AND wq.entity='group'
  AND wq.claimed_by=$procIdEsc;

SQL;
    $db->query($sql);
  }

}
