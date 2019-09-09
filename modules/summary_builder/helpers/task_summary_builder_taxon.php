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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

class task_summary_builder_taxon {

  const BATCH_SIZE = 2;

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
UPDATE summary_occurrences so
SET so.taxon_list_id = dttl.taxon_list_id,
---  so.preferred_taxa_taxon_list_id INTEGER NOT NULL,
  so.taxonomic_sort_order = dttl.taxonomic_sort_order,
  so.taxon = dttl.taxon,
  so.preferred_taxon = dttl.preferred,
  so.default_common_name = dttl.common,
  so.taxon_meaning_id = dttl.taxon_meaning_id
FROM detail_taxa_taxon_lists dttl
WHERE 
  so.taxa_taxon_list_id = dttl.id
    AND so.taxa_taxon_list_id in (SELECT record_id
        FROM work_queue
        WHERE claimed_by='$procId'
        AND entity='taxon'
        AND task='task_summary_builder_taxon');
SQL;
  }
}
