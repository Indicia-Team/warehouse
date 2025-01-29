<?php

/**
 * @file
 * Queue worker to update cache table blocked_sharing_tasks field.
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
 * Queue worker to update cache table blocked_sharing_tasks field.
 *
 * Class called when a task_cache_builder_user task encountered in the work
 * queue. Updates cache_*_functional.blocked_sharing_tasks to reflect any
 * changes to a user's record privacy settings.
 */
class task_cache_builder_user_privacy {

  /**
   * Low batch size as could be lots to update.
   */
  const BATCH_SIZE = 5;

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
    $procIdEsc = pg_escape_literal($db->getLink(), $procId);
    $sql = <<<SQL
UPDATE cache_occurrences_functional o
  SET blocked_sharing_tasks=
    CASE WHEN u.allow_share_for_reporting
      AND u.allow_share_for_peer_review AND u.allow_share_for_verification
      AND u.allow_share_for_data_flow AND u.allow_share_for_moderation
      AND u.allow_share_for_editing
    THEN null
    ELSE
      ARRAY_REMOVE(ARRAY[
        CASE WHEN u.allow_share_for_reporting=false THEN 'R' ELSE NULL END,
        CASE WHEN u.allow_share_for_peer_review=false THEN 'P' ELSE NULL END,
        CASE WHEN u.allow_share_for_verification=false THEN 'V' ELSE NULL END,
        CASE WHEN u.allow_share_for_data_flow=false THEN 'D' ELSE NULL END,
        CASE WHEN u.allow_share_for_moderation=false THEN 'M' ELSE NULL END,
        CASE WHEN u.allow_share_for_editing=false THEN 'E' ELSE NULL END
      ], NULL)
    END
FROM users u
JOIN work_queue q
  ON q.record_id=u.id
  AND q.task='task_cache_builder_user_privacy'
  AND q.entity='user'
  AND q.claimed_by=$procIdEsc
WHERE u.id=o.created_by_id;

UPDATE cache_samples_functional s
  SET blocked_sharing_tasks=
    CASE WHEN u.allow_share_for_reporting
      AND u.allow_share_for_peer_review AND u.allow_share_for_verification
      AND u.allow_share_for_data_flow AND u.allow_share_for_moderation
      AND u.allow_share_for_editing
    THEN null
    ELSE
      ARRAY_REMOVE(ARRAY[
        CASE WHEN u.allow_share_for_reporting=false THEN 'R' ELSE NULL END,
        CASE WHEN u.allow_share_for_peer_review=false THEN 'P' ELSE NULL END,
        CASE WHEN u.allow_share_for_verification=false THEN 'V' ELSE NULL END,
        CASE WHEN u.allow_share_for_data_flow=false THEN 'D' ELSE NULL END,
        CASE WHEN u.allow_share_for_moderation=false THEN 'M' ELSE NULL END,
        CASE WHEN u.allow_share_for_editing=false THEN 'E' ELSE NULL END
      ], NULL)
    END
FROM users u
JOIN work_queue q
  ON q.record_id=u.id
  AND q.task='task_cache_builder_user_privacy'
  AND q.entity='user'
  AND q.claimed_by=$procIdEsc
WHERE u.id=s.created_by_id

SQL;
    $db->query($sql);
  }

}
