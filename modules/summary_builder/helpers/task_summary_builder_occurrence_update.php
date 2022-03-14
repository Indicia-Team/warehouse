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

require_once(DOCROOT.'modules/summary_builder/config/summary_builder.php');

class task_summary_builder_occurrence_update {

  const BATCH_SIZE = 1000;

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
      $queries = kohana::config('summary_builder');
      // This query gets all the occurrences to be processed that are on a survey which does summarisation
      $query = str_replace(array('#procId#', '#task#'), array($procId, 'task_summary_builder_occurrence_update'),
                $queries['get_occurrences_to_process']);
      $result = $db->query($query)->result_array(false);
      foreach($result as $row){
          summary_builder::populate_summary_table_for_occurrence_modify($db, $row['occurrence_id'], $row['definition_id']);
      }
  }

}
