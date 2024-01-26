<?php

/**
 * @file
 * Queue worker to apply filters which limit the scope of a workflow event.
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
 * Queue worker to apply filters which limit the scope of a workflow event.
 *
 * Workflow events can have limited scope, e.g. to a particular custom
 * attribute value or geographic region. The event's record changes must be
 * applied at the time the record is saved, but this is too early for the
 * filter criteria to be checked. So a work queue entry is created to undo the
 * event's outcome if the filter is not met.
 *
 * @todo ensure this runs after spatial indexing.
 */
class task_workflow_event_check_filters {

  public const BATCH_SIZE = 100;

  /**
   * Work_queue class will automatically expire the completed tasks.
   *
   * @const bool
   */
  public const SELF_CLEANUP = FALSE;

  public static function process($db, $taskType, $procId) {
    // Retrieve work queue tasks for this procId, where the task links to an
    // event with a filter, but the record does not comply with the filter.
    $qry = <<<SQL
SELECT q.record_id
FROM work_queue q
JOIN occurrences o ON o.id=q.record_id
JOIN workflow_events e on e.id::text=q.params->>'workflow_events.id'
LEFT JOIN samples s ON s.id=o.sample_id AND e.location_ids_filter is not null
LEFT JOIN locations l ON l.id = ANY(e.location_ids_filter) AND st_intersects(l.boundary_geom, s.geom)
LEFT JOIN (occurrence_attribute_values v
  JOIN cache_termlists_terms t on t.id=v.int_value
  JOIN occurrence_attributes a ON a.id=v.occurrence_attribute_id
) ON v.occurrence_id=o.id AND e.attrs_filter_term IS NOT NULL
  -- case insensitive array check.
  AND lower(t.term)=ANY(lower(e.attrs_filter_values::text)::text[])
  AND lower(a.term_name)=lower(e.attrs_filter_term)
WHERE q.entity='occurrence' AND q.task='task_workflow_event_check_filters' AND claimed_by='$procId'
-- Need to either fail on the locations filter, or attribute values filter.
AND ((e.location_ids_filter IS NOT NULL AND l.id IS NULL)
OR (e.attrs_filter_term IS NOT NULL AND v.id IS NULL));
SQL;
    $tasks = $db->query($qry);
    $occurrenceIds = [];
    foreach ($tasks as $task) {
      $occurrenceIds[] = $task->record_id;
    }
    // For the records outside the workflow_event's filter we can rewind them.
    $rewinds = workflow::getRewindChangesForRecords($db, 'occurrence', $occurrenceIds, ['S', 'V', 'R']);
    foreach ($rewinds as $key => $rewind) {
      list($entity, $id) = explode('.', $key);
      $obj = ORM::factory($entity, $id);
      foreach ($rewind as $field => $value) {
        $obj->$field = $value;
      }
      $obj->save();
    }
  }

}
