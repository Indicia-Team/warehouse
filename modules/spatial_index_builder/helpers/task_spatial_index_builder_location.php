<?php

/**
 * @file
 * Queue worker to update cache_*_functional.location_ids on location changes.
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
 * Queue worker to update cache_*_functional.location_ids on location changes.
 */
class task_spatial_index_builder_location {

  public const BATCH_SIZE = 100;

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
    $locationTypeFilters = spatial_index_builder::getLocationTypeFilters($db);
    $qry = <<<SQL
DROP TABLE IF EXISTS loclist;
DROP TABLE IF EXISTS changed_location_hits;

SELECT record_id INTO temporary loclist
FROM work_queue
WHERE claimed_by='$procId'
AND entity='location'
AND task='task_spatial_index_builder_location';

SELECT s.id as sample_id, array_agg(l.id) as location_ids
INTO TEMPORARY changed_location_hits
FROM cache_samples_functional s
JOIN locations l ON st_intersects(l.boundary_geom, s.public_geom)
  AND NOT st_touches(l.boundary_geom, s.public_geom)
  AND l.deleted=false
  $locationTypeFilters
JOIN loclist ll on ll.record_id=l.id
GROUP BY s.id;

-- Samples - remove any old hits for locations that have changed.
UPDATE cache_samples_functional u
SET location_ids=array_remove(u.location_ids, ll.record_id)
FROM cache_samples_functional s
JOIN loclist ll ON s.location_ids @> ARRAY[ll.record_id]
LEFT JOIN (changed_location_hits clh
  JOIN loclist lhit ON clh.location_ids @> ARRAY[lhit.record_id]
) ON clh.sample_id=s.id
WHERE u.id=s.id
AND clh.sample_id IS NULL;

-- Samples - add any missing hits for locations that have changed.
UPDATE cache_samples_functional u
  SET location_ids=CASE WHEN u.location_ids IS NULL THEN clh.location_ids ELSE ARRAY(select distinct unnest(array_cat(clh.location_ids, u.location_ids))) END
FROM changed_location_hits clh
WHERE u.id=clh.sample_id;

-- Occurrences - remove any old hits for locations that have changed.
UPDATE cache_occurrences_functional u
SET location_ids=array_remove(u.location_ids, ll.record_id)
FROM cache_occurrences_functional o
JOIN loclist ll ON o.location_ids @> ARRAY[ll.record_id]
LEFT JOIN (changed_location_hits clh
  JOIN loclist lhit ON clh.location_ids @> ARRAY[lhit.record_id]
) ON clh.sample_id=o.sample_id
WHERE u.id=o.id
AND clh.sample_id IS NULL;

-- Samples - add any missing hits for locations that have changed.
UPDATE cache_occurrences_functional u
  SET location_ids=CASE WHEN u.location_ids IS NULL THEN clh.location_ids ELSE ARRAY(select distinct unnest(array_cat(clh.location_ids, u.location_ids))) END
FROM changed_location_hits clh
WHERE u.sample_id=clh.sample_id;

SQL;
    $db->query($qry);
  }

}