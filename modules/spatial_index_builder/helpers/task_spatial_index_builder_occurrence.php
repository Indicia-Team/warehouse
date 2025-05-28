<?php

/**
 * @file
 * Queue worker to update cache_*_functional.location_ids on occ changes.
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
 * Queue worker to update cache_*_functional.location_ids on occ changes.
 */
class task_spatial_index_builder_occurrence {

  public const BATCH_SIZE = 5000;

  /**
   * This class will expire the completed tasks itself.
   *
   * @const bool
   */
  public const SELF_CLEANUP = TRUE;

  /**
   * Perform the processing for a task batch found in the queue.
   *
   * If an occurrence is inserted after the initial sample creation, we need to
   * copy over the sample's location_ids.
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
    // Delete entries which no longer require processing - normally a result of
    // a deletion since the queue entry created.
    $qry = <<<SQL
      DELETE FROM work_queue q
      USING occurrences o
      JOIN samples s ON s.id=o.sample_id
      WHERE o.id=q.record_id
      AND q.claimed_by=?
      AND q.entity='occurrence'
      AND q.task='task_spatial_index_builder_occurrence'
      AND (s.deleted=true OR o.deleted=true);
    SQL;
    $db->query($qry, [$procId]);
    $qry = <<<SQL
      DROP TABLE IF EXISTS occlist;

      SELECT q.id as work_queue_id, o.id, s.location_ids
      INTO TEMPORARY occlist
      FROM work_queue q
      JOIN cache_occurrences_functional o ON o.id=q.record_id
      -- s.location_ids will be null if the sample not yet indexed.
      JOIN cache_samples_functional s ON s.id=o.sample_id AND s.location_ids IS NOT NULL
      WHERE q.claimed_by=?
      AND q.entity='occurrence'
      AND q.task='task_spatial_index_builder_occurrence';

      UPDATE cache_occurrences_functional o
      SET location_ids = ol.location_ids
      FROM occlist ol
      WHERE ol.id=o.id
      AND (o.location_ids <> ol.location_ids OR (o.location_ids IS NULL)<>(ol.location_ids IS NULL));

      -- Delete processed.
      DELETE FROM work_queue q
      USING occlist ol
      WHERE ol.work_queue_id=q.id;
    SQL;
    $db->query($qry, [$procId]);
    // Occurrence tasks will be skipped if the parent sample is not indexed.
    // Add work queue tasks for any samples that have not been done for any
    // reason. The occurrences should then be picked up next time around.
    $qry = <<<SQL
      INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
      SELECT DISTINCT 'task_spatial_index_builder_sample', 'sample', o.sample_id, 2, 30, now()
      FROM cache_occurrences_functional o
      JOIN cache_samples_functional s ON s.id=o.sample_id
        AND s.location_ids IS NULL
      JOIN work_queue q ON q.record_id=o.id
        AND q.claimed_by=?
        AND q.entity='occurrence'
        AND q.task='task_spatial_index_builder_occurrence';
    SQL;
    $db->query($qry, [$procId]);
  }

}
