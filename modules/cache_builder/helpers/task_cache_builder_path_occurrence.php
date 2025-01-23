<?php

/**
 * @file
 * Queue worker to update cache_occurrences_functional.taxon_path.
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
 * Queue worker to update cache_occurrences_functional.taxon_path.
 *
 * Class called when taxonomy is updated requiring a path update. Since the
 * hierarchical path data may affect many records, the update is batched and
 * queued.
 */
class task_cache_builder_path_occurrence {

  /**
   * Fairly fast, so processing large batches is OK.
   */
  public const BATCH_SIZE = 50000;

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
    $masterListId = warehouse::getMasterTaxonListId();
    $sql = <<<SQL
      UPDATE cache_occurrences_functional o
      SET taxon_path=ctp.path
      FROM work_queue q, cache_taxa_taxon_lists cttl
      LEFT JOIN cache_taxon_paths ctp
        ON ctp.taxon_meaning_id=cttl.taxon_meaning_id
        AND ctp.taxon_list_id=$masterListId
      WHERE cttl.external_key=o.taxa_taxon_list_external_key
      AND cttl.taxon_list_id=$masterListId
      AND o.id=q.record_id
      AND q.entity='occurrence'
      AND q.task='task_cache_builder_path_occurrence'
      AND q.claimed_by=?;
    SQL;
    $db->query($sql, [$procId]);
  }
}