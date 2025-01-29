<?php

/**
 * @file
 * Queue worker to update cache_*_functional.location_ids on sample changes.
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
 * Queue worker to update cache_*_functional.location_ids on sample changes.
 */
class task_spatial_index_builder_sample {

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
    $linkedLocationAttrIds = spatial_index_builder::getLinkedLocationAttrIds($db);
    $procIdEsc = pg_escape_literal($db->getLink(), $procId);
    $qry = <<<SQL
-- Delete entries which no longer require processing - normally a result of a
-- deletion since the queue entry created.
DELETE FROM work_queue q
USING samples s
WHERE s.id=q.record_id
AND q.claimed_by=$procIdEsc
AND q.entity='sample'
AND q.task='task_spatial_index_builder_sample'
AND s.deleted=true;

DROP TABLE IF EXISTS smplist;
DROP TABLE IF EXISTS changed_samples;
SELECT record_id INTO temporary smplist
FROM work_queue
WHERE claimed_by=$procIdEsc
AND entity='sample'
AND task='task_spatial_index_builder_sample';

WITH ltree AS (
  -- Find locations where the user has selected a specific location on the
  -- input form (e.g. a Vice County).
  SELECT DISTINCT s.id as sample_id, l.id
  FROM smplist sl
  JOIN cache_samples_functional s on s.id=sl.record_id
  JOIN sample_attribute_values v on v.sample_id=s.id AND v.deleted=false AND v.sample_attribute_id IN ($linkedLocationAttrIds)
  JOIN locations l on l.id=v.int_value
  AND l.deleted=false
  AND l.location_type_id IN ($locationTypeFilters[allLocationTypeIds])
  $locationTypeFilters[surveyFilters]
  UNION ALL
  -- Add locations which are from an indexed layer and which spatially
  -- intersect the sample, unless the user has made a choice for that location
  -- type as per above.
  SELECT s.id as sample_id, l.id
  FROM smplist sl
  JOIN cache_samples_functional s on s.id=sl.record_id
  LEFT JOIN locations l on l.boundary_geom && s.public_geom AND st_intersects(l.boundary_geom, s.public_geom)
    AND (st_geometrytype(s.public_geom)='ST_Point' or not st_touches(l.boundary_geom, s.public_geom))
    AND l.deleted=false
    AND l.location_type_id IN ($locationTypeFilters[allLocationTypeIds])
    $locationTypeFilters[surveyFilters]
  LEFT JOIN sample_attribute_values v on v.sample_id=s.id AND v.deleted=false AND v.sample_attribute_id IN ($linkedLocationAttrIds)
  LEFT JOIN locations lfixed on lfixed.id=v.int_value AND lfixed.deleted=false
	WHERE coalesce(l.location_type_id,-1)<>COALESCE(lfixed.location_type_id,-2)
)
SELECT sample_id, array_agg(DISTINCT id) as location_ids
INTO TEMPORARY changed_samples
FROM ltree
GROUP BY sample_id;

-- Samples - for updated samples, copy over the changes if there are any
UPDATE cache_samples_functional u
  SET location_ids=cs.location_ids
FROM changed_samples cs
WHERE cs.sample_id=u.id
AND (
  ((u.location_ids IS NULL)<>(cs.location_ids IS NULL))
  OR u.location_ids <@ cs.location_ids = false OR u.location_ids @> cs.location_ids = false
);

UPDATE cache_occurrences_functional o
SET location_ids = s.location_ids
FROM cache_samples_functional s
JOIN changed_samples cs on cs.sample_id=s.id
WHERE o.sample_id=s.id
AND (o.location_ids <> s.location_ids OR (o.location_ids IS NULL)<>(s.location_ids IS NULL));

-- Garbage collection, taking care to only remove samples that got indexed
-- properly. We can clear do the occurrence tasks for the samples that are
-- done to save extra work.
DELETE FROM work_queue q
USING changed_samples s
WHERE s.sample_id=q.record_id
AND q.entity='sample'
AND q.task='task_spatial_index_builder_sample';

DELETE FROM work_queue q
USING changed_samples s, cache_occurrences_functional o
WHERE s.sample_id=o.sample_id
AND o.id=q.record_id
AND q.entity='occurrence'
AND q.task='task_spatial_index_builder_occurrence';
SQL;
    $db->query($qry);
  }

}
