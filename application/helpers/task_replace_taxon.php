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
 * Queue worker to replace a deleted taxon with another.
 *
 * Updates the occurrences for the deleted taxon to point to the replacement.
 */
class task_replace_taxon {

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

    // Find the user ID from several possible places, in order of precedence.
    if (isset($_SESSION['auth_user'])) {
      // User logged into warehouse.
      $userId = $_SESSION['auth_user']->id;
    }
    elseif (isset($remoteUserId)) {
      // User ID from request parameter.
      $userId = $remoteUserId;
    }
    else {
      // User ID from a global default.
      $defaultUserId = Kohana::config('indicia.defaultPersonId');
      $userId = ($defaultUserId ? $defaultUserId : 1);
    }

    /*
     * Performs the following tasks:
     * * Creates queue entries for the occurrence taxonomy field updates
     *   required.
     * * Inserts an explanation comment.
     * * Update occurrences data to point to the replacement taxon.
     */
    $procId = pg_escape_literal($db->getLink(), $procId);
    $userId = (int) $userId;
    $sql = <<<SQL
INSERT INTO work_queue(task, entity, record_id, params, cost_estimate, priority, created_on)
SELECT 'task_cache_builder_taxonomy_occurrence', 'taxa_taxon_list', record_id, null, 100, 2, now()
FROM work_queue
WHERE entity='taxa_taxon_list'
AND task='task_replace_taxon'
AND claimed_by=$procId;

INSERT INTO occurrence_comments(occurrence_id, comment, created_by_id, created_on, updated_by_id, updated_on)
SELECT o.id, 'The taxon associated with this occurrence (' || told.taxon || ', ID ' || ttlold.id::text || ') ' ||
  'was deleted and replaced with ' || cttlnew.taxon || ', ID ' || cttlnew.id::text || '.', $userId, now(), $userId, now()
FROM occurrences o
JOIN taxa_taxon_lists ttl ON ttl.id=o.taxa_taxon_list_id
JOIN taxa_taxon_lists ttlany ON ttlany.taxon_meaning_id=ttl.taxon_meaning_id
JOIN work_queue q ON (q.params->>'old_taxa_taxon_list_id')::integer=ttlany.id
JOIN taxa_taxon_lists ttlold ON ttlold.id=o.taxa_taxon_list_id
JOIN taxa told ON told.id=ttlold.taxon_id
JOIN cache_taxa_taxon_lists cttlnew ON cttlnew.id=q.record_id
WHERE q.entity='taxa_taxon_list'
AND q.task='task_replace_taxon'
AND q.claimed_by=$procId;

UPDATE occurrences o
SET taxa_taxon_list_id=q.record_id
FROM taxa_taxon_lists ttl
JOIN taxa_taxon_lists ttlany ON ttlany.taxon_meaning_id=ttl.taxon_meaning_id
JOIN work_queue q ON (q.params->>'old_taxa_taxon_list_id')::integer=ttlany.id
WHERE ttl.id=o.taxa_taxon_list_id
AND q.entity='taxa_taxon_list'
AND q.task='task_replace_taxon'
AND q.claimed_by=$procId;
SQL;
    $db->query($sql);
  }

}
