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

class task_summary_builder_sample {

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
    $queries = kohana::config('summary_builder');
    self::cleanupUnrelatedTasks($db);
    // This query gets all the samples to be processed that are on a survey which does summarisation
    $query = str_replace(['#procId#', '#task#'], [$procId, 'task_summary_builder_sample'],
        $queries['get_samples_to_process']);
    $result = $db->query($query)->result_array(false);
    foreach($result as $row){
       summary_builder::populate_summary_table_for_sample($db, $row['sample_id'], $row['definition_id']);
    }
  }

  /**
   * Cleanup tasks from unrelated surveys.
   *
   * The summary builder is only involved in surveys defined in the
   * summary_definitions table, but tasks will be generated for all samples.
   * It's quicker to just throw out any tasks that we don't need so we only end
   * up processing the relevant ones.
   *
   * @param Database $db
   *   Database connection.
   */
  private static function cleanupUnrelatedTasks($db) {
    $qry = <<<SQL
      DELETE FROM work_queue q
      USING cache_samples_functional s
      LEFT JOIN summariser_definitions sd on sd.survey_id=s.survey_id
      WHERE s.id=q.record_id
      AND q.task='task_summary_builder_sample'
      AND sd.id IS NULL;
    SQL;
    $db->query($qry);
  }

}
