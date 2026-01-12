<?php

/**
 * @file
 * Queue worker to move records to a different survey/website combination.
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
 * Queue worker to update cache after moving an occurrence between surveys.
 *
 * E.g. used by the data_utils/bulk_move service to do lazy cache table updates.
 */
class task_cache_builder_post_move {

  /**
   * Not a massively fast operation.
   */
  public const BATCH_SIZE = 500;

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
    // Now process taxonomy where the cache update is already done.
    $procIdEsc = pg_escape_literal($db->getLink(), $procId);
    $sql = <<<SQL
      DROP TABLE IF EXISTS moving_records;

      CREATE TEMPORARY TABLE moving_records AS (
        SELECT o.id, o.sample_id, s.survey_id, su.title as survey_title, su.website_id, w.title as website_title
        FROM occurrences o
        JOIN samples s ON s.id=o.sample_id
        JOIN surveys su ON su.id=s.survey_id
        JOIN websites w ON w.id=su.website_id
        JOIN work_queue q ON q.record_id=o.id
        WHERE q.entity='occurrence'
        AND q.task='task_cache_builder_post_move'
        AND q.claimed_by=$procIdEsc
      );
    SQL;
    $db->query($sql);
    $sql = <<<SQL
      UPDATE cache_occurrences_functional u
      SET website_id=mr.website_id,
        survey_id=mr.survey_id
      FROM moving_records mr
      WHERE mr.id=u.id;
    SQL;
    $db->query($sql);
    $sql = <<<SQL
      UPDATE cache_samples_functional u
      SET website_id=mr.website_id,
        survey_id=mr.survey_id
      FROM moving_records mr
      WHERE mr.sample_id=u.id;
    SQL;
    $db->query($sql);
    $sql = <<<SQL
      UPDATE cache_samples_nonfunctional u
      SET website_title=mr.website_title,
        survey_title=mr.survey_title
      FROM moving_records mr
      WHERE mr.sample_id=u.id;
    SQL;
    $db->query($sql);
    $sql = <<<SQL
      DELETE FROM work_queue q
      USING moving_records mr
      WHERE q.entity='taxa_taxon_list'
      AND q.task='task_cache_builder_post_move'
      AND q.claimed_by=$procIdEsc
      AND q.record_id=mr.id;

      DROP TABLE moving_records;
    SQL;
    $db->query($sql);
  }

}
