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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Queue worker to update cache_occurrences_functional taxonomy related fields.
 *
 * E.g. for a rename taxon UKSI operation, occurrences get a new external key,
 * so need to update cache_occurrences_functional.
 */
class task_cache_builder_taxonomy_occurrence {

  /**
   * Not a fast operation.
   */
  public const BATCH_SIZE = 10;

  /**
   * Work_queue class will automatically expire the completed tasks.
   *
   * @const bool
   */
  public const SELF_CLEANUP = FALSE;

  /**
   * Perform the processing for a task batch found in the queue.
   *
   * Note that this task updates occurrences, but the stored record in the
   * work_queue task should be a taxa_taxon_list. Otherwise huge numbers of
   * work queue tasks are required.
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

    // Unclaim any work queue tasks for taxa that are pending a cache update.
    $sql = <<<SQL
UPDATE work_queue q
SET claimed_by=null, claimed_on=null
FROM taxa_taxon_lists ttl
JOIN taxa t ON t.id=ttl.taxon_id
LEFT JOIN cache_taxa_taxon_lists cttl ON cttl.id=ttl.id
WHERE q.claimed_by=?
AND q.entity='taxa_taxon_list'
AND q.task='task_cache_builder_taxonomy_occurrence'
AND q.record_id=ttl.id
AND (cttl.id IS NULL OR cttl.cache_updated_on<GREATEST(ttl.updated_on, t.updated_on))
SQL;
    $db->query($sql, [$procId]);

    // Now process taxonomy where the cache update is already done.
    $sql = <<<SQL
WITH target_occurrences AS MATERIALIZED (
  SELECT DISTINCT o.id
  FROM cache_occurrences_functional o
  JOIN occurrences occ ON occ.id=o.id
  JOIN cache_taxa_taxon_lists cttl ON cttl.id=occ.taxa_taxon_list_id
  JOIN cache_taxa_taxon_lists cttlm ON cttlm.taxon_meaning_id=cttl.taxon_meaning_id
  JOIN work_queue q ON q.record_id=cttlm.id
  WHERE q.entity='taxa_taxon_list'
  AND q.task='task_cache_builder_taxonomy_occurrence'
  AND q.claimed_by=?
), locked_occurrences AS MATERIALIZED (
  SELECT o.id
  FROM cache_occurrences_functional o
  JOIN target_occurrences t ON t.id=o.id
  ORDER BY o.id
  FOR UPDATE OF o
)
UPDATE cache_occurrences_functional o
SET taxon_path=ctp.path,
  preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id,
  taxa_taxon_list_external_key=cttl.external_key,
  taxon_meaning_id=cttl.taxon_meaning_id,
  family_taxa_taxon_list_id=cttl.family_taxa_taxon_list_id,
  taxon_group_id=cttl.taxon_group_id,
  taxon_rank_sort_order=cttl.taxon_rank_sort_order,
  marine_flag=cttl.marine_flag,
  freshwater_flag=cttl.freshwater_flag,
  terrestrial_flag=cttl.terrestrial_flag,
  non_native_flag=cttl.non_native_flag
FROM occurrences occ
JOIN cache_taxa_taxon_lists cttl ON cttl.id=occ.taxa_taxon_list_id
LEFT JOIN cache_taxon_paths ctp ON ctp.external_key=cttl.external_key
  AND ctp.taxon_list_id=COALESCE(?, cttl.taxon_list_id)
JOIN locked_occurrences l ON l.id=o.id
WHERE o.id=occ.id;
SQL;
  $db->query($sql, [$procId, $masterListId]);
  }

}
