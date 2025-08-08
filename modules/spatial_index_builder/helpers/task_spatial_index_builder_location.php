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
   *
   * @todo Dynamic sample attribute IDs (both for samples and locations).
   */
  public static function process($db, $taskType, $procId) {
    self::processLocationsAgainstSampleData($db, $procId);
    self::processLocationsAgainstHigherLocations($db, $procId);
  }

  /**
   * Find the intersection between updated location boundaries and sample data.
   *
   * @param object $db
   *   Database connection object.
   * @param string $procId
   *   Unique identifier of this work queue processing run. Allows filtering
   *   against the work_queue table's claimed_by field to determine which
   *   tasks to perform.
   */
  private static function processLocationsAgainstSampleData($db, $procId) {
    $locationTypeFilters = spatial_index_builder::getLocationTypeFilters($db);
    $linkedLocationAttrIds = spatial_index_builder::getLinkedLocationAttrIds($db);

    $surveyFilters = '';
    $gridRefSizeFilters = '';
    $gridRefSizeFiltersSensitive = '';
    if (!empty($locationTypeFilters['surveyFilters'])) {
      foreach ($locationTypeFilters['surveyFilters'] as $locationTypeId => $surveyIds) {
        $surveyIds = implode(',', $surveyIds);
        $surveyFilters .= "AND (s.survey_id IN ($surveyIds) OR l.location_type_id<>$locationTypeId)";
      }
    }
    if (isset($locationTypeFilters['maxGridRefAreas'])) {
      foreach ($locationTypeFilters['maxGridRefAreas'] as $locationTypeId => $gridRefMaxArea) {
        $gridRefSizeFilters .= "AND (l.location_type_id<>$locationTypeId OR st_area(s.public_geom) <= $gridRefMaxArea)";
        $gridRefSizeFiltersSensitive .= "AND (l.location_type_id<>$locationTypeId OR st_area(s.geom) <= $gridRefMaxArea)";
      }
    }
    $locationTypeIds = implode(',', $locationTypeFilters['locationTypeIds']);

    $qry = <<<SQL
      DROP TABLE IF EXISTS loclist;
      DROP TABLE IF EXISTS user_decisions;
      DROP TABLE IF EXISTS changed_location_hits;
      DROP TABLE IF EXISTS changed_location_hits_sensitive;
      DROP TABLE IF EXISTS changed_location_hits_occs;
      DROP TABLE IF EXISTS smp_locations_deleted;
      DROP TABLE IF EXISTS occ_locations_deleted;
      DROP TABLE IF EXISTS smp_locations_deleted_sensitive;

      -- Prepare temporary tables that make things faster when we update cache
      -- tables.
      SELECT DISTINCT w.record_id
      INTO TEMPORARY loclist
      FROM work_queue w
      JOIN locations l ON l.id = w.record_id
        AND l.location_type_id IN ($locationTypeIds)
      WHERE w.claimed_by='$procId'
      AND w.entity='location'
      AND w.task='task_spatial_index_builder_location';

      CREATE TEMPORARY TABLE user_decisions (
        id integer,
        location_type_id integer,
        sample_id integer
      );

      -- Build a list of location/sample links where the original recorder or a
      -- verifier have forced the spatial indexer to use specific locations.
      INSERT INTO user_decisions
      SELECT l.id, l.location_type_id, s.id as sample_id
        FROM loclist ll
        JOIN locations l
          ON l.id=ll.record_id
          AND l.deleted=false
        JOIN samples s ON s.deleted=false AND s.forced_spatial_indexer_location_ids @@ ('$.*[*] == ' || ll.record_id)::jsonpath
        UNION ALL
        SELECT l.id, l.location_type_id, v.sample_id as sample_id
        FROM loclist ll
        JOIN locations l
          ON l.id=ll.record_id
          AND l.deleted=false
        JOIN sample_attribute_values v ON v.int_value=l.id AND v.sample_attribute_id IN ($linkedLocationAttrIds) AND v.deleted=false
        JOIN samples smp ON smp.id=v.sample_id
          AND smp.deleted=false
          AND smp.forced_spatial_indexer_location_ids IS NULL;

      WITH ltree AS (
        SELECT id, location_type_id, sample_id
        FROM user_decisions
        UNION ALL
        SELECT l.id, l.location_type_id, s.id as sample_id
        FROM loclist ll
        JOIN locations l
          ON l.id=ll.record_id
          AND l.deleted=false
          AND l.location_type_id IN ($locationTypeIds)
        JOIN cache_samples_functional s
          ON st_intersects(l.boundary_geom, s.public_geom)
          AND (st_geometrytype(s.public_geom)='ST_Point' OR NOT st_touches(l.boundary_geom, s.public_geom))
          $surveyFilters
          $gridRefSizeFilters
        LEFT JOIN sample_attribute_values v ON v.sample_id=s.id AND v.deleted=false AND v.sample_attribute_id IN ($linkedLocationAttrIds)
        JOIN samples smp ON smp.id=s.id
          AND smp.deleted=false
          AND smp.forced_spatial_indexer_location_ids IS NULL
        LEFT JOIN locations lfixed on lfixed.id=v.int_value AND lfixed.deleted=false
        WHERE COALESCE(l.location_type_id,-1)<>COALESCE(lfixed.location_type_id,-2)
      )
      SELECT sample_id, array_agg(distinct id) as location_ids
      INTO TEMPORARY changed_location_hits
      FROM ltree
      GROUP BY sample_id;

      WITH ltree AS (
        SELECT id, location_type_id, sample_id
        FROM user_decisions
        UNION ALL
        SELECT l.id, l.location_type_id, s.id as sample_id
        FROM loclist ll
        JOIN locations l
          ON l.id=ll.record_id
          AND l.deleted=false
          AND l.location_type_id IN ($locationTypeIds)
        JOIN samples s ON s.id=s.id
          AND s.deleted=false
          AND s.forced_spatial_indexer_location_ids IS NULL
          AND st_intersects(l.boundary_geom, s.geom)
          AND (st_geometrytype(s.geom)='ST_Point' OR NOT st_touches(l.boundary_geom, s.geom))
          $surveyFilters
          $gridRefSizeFiltersSensitive
        LEFT JOIN sample_attribute_values v ON v.sample_id=s.id AND v.deleted=false AND v.sample_attribute_id IN ($linkedLocationAttrIds)
        LEFT JOIN locations lfixed on lfixed.id=v.int_value AND lfixed.deleted=false
        WHERE COALESCE(l.location_type_id,-1)<>COALESCE(lfixed.location_type_id,-2)
      )
      SELECT sample_id, array_agg(distinct id) as location_ids
      INTO TEMPORARY changed_location_hits_sensitive
      FROM ltree
      GROUP BY sample_id;

      -- Find samples currently indexed against locations where they no longer
      -- intersect.
      SELECT s.id, array_remove(s.location_ids, ll.record_id) as location_ids
      INTO TEMPORARY smp_locations_deleted
      FROM cache_samples_functional s
      JOIN loclist ll ON s.location_ids @> ARRAY[ll.record_id]
      LEFT JOIN (changed_location_hits clh
        JOIN loclist lhit ON clh.location_ids @> ARRAY[lhit.record_id]
      ) ON clh.sample_id=s.id
      WHERE clh.sample_id IS NULL;

      -- Remove the locations from the samples which no longer intersect.
      UPDATE cache_samples_functional u
      SET location_ids=ld.location_ids
      FROM smp_locations_deleted ld
      WHERE u.id=ld.id;

      -- Add missing hits to samples which are now indexed against locations
      -- but weren't before.
      UPDATE cache_samples_functional u
        SET location_ids=CASE
          WHEN u.location_ids IS NULL THEN clh.location_ids
          ELSE ARRAY(select distinct unnest(array_cat(clh.location_ids, u.location_ids)))
        END
      FROM changed_location_hits clh
      WHERE u.id=clh.sample_id
      AND (u.location_ids IS NULL OR NOT u.location_ids @> clh.location_ids);

      -- Find occurrences currently indexed against locations where they no
      -- longer intersect.
      SELECT o.id, array_remove(o.location_ids, ll.record_id) as location_ids
      INTO TEMPORARY occ_locations_deleted
      FROM cache_occurrences_functional o
      JOIN loclist ll ON o.location_ids @> ARRAY[ll.record_id]
      LEFT JOIN (changed_location_hits clh
        JOIN loclist lhit ON clh.location_ids @> ARRAY[lhit.record_id]
      ) ON clh.sample_id=o.sample_id
      WHERE clh.sample_id IS NULL;

      -- Remove the locations from the occurrences which no longer intersect.
      UPDATE cache_occurrences_functional u
      SET location_ids=ld.location_ids
      FROM occ_locations_deleted ld
      WHERE u.id=ld.id;

      -- Add missing hits to occurrences which are now indexed against
      -- locations but weren't before.
      UPDATE cache_occurrences_functional u
        SET location_ids=CASE
          WHEN u.location_ids IS NULL THEN clh.location_ids
          ELSE ARRAY(select distinct unnest(array_cat(clh.location_ids, u.location_ids)))
        END
      FROM changed_location_hits clh
      WHERE u.sample_id=clh.sample_id
      AND (u.location_ids IS NULL OR NOT u.location_ids @> clh.location_ids);

      -- Find samples currently indexed against locations where they no longer
      -- intersect - for the full-precision version for sensitive or private
      -- data.
      SELECT s.id, array_remove(s.location_ids, ll.record_id) as location_ids
      INTO TEMPORARY smp_locations_deleted_sensitive
      FROM cache_samples_sensitive s
      JOIN loclist ll ON s.location_ids @> ARRAY[ll.record_id]
      LEFT JOIN (changed_location_hits_sensitive clh
        JOIN loclist lhit ON clh.location_ids @> ARRAY[lhit.record_id]
      ) ON clh.sample_id=s.id
      WHERE clh.sample_id IS NULL;

      -- Remove the locations from the samples which no longer intersect - for
      -- the full-precision version for sensitive or private data.
      UPDATE cache_samples_sensitive u
      SET location_ids=ld.location_ids
      FROM smp_locations_deleted_sensitive ld
      WHERE u.id=ld.id;

      -- Add missing hits to samples which are now indexed against locations
      -- but weren't before - for the full-precision version for sensitive or
      -- private data.
      UPDATE cache_samples_sensitive u
        SET location_ids=CASE
          WHEN u.location_ids IS NULL THEN clh.location_ids
          ELSE ARRAY(select distinct unnest(array_cat(clh.location_ids, u.location_ids)))
        END
      FROM changed_location_hits_sensitive clh
      WHERE u.id=clh.sample_id
      AND (u.location_ids IS NULL OR NOT u.location_ids @> clh.location_ids);

    SQL;
    $db->query($qry);
  }

  /**
   * Find the intersection between updated locations and higher locations.
   *
   * E.g a site may be indexed against vice counties or countries, depending on
   * the setup of layers in the config file.
   *
   * @param object $db
   *   Database connection object.
   * @param string $procId
   *   Unique identifier of this work queue processing run. Allows filtering
   *   against the work_queue table's claimed_by field to determine which
   *   tasks to perform.
   */
  private static function processLocationsAgainstHigherLocations($db, $procId) {
    $locationIndexing = kohana::config('spatial_index_builder.location_indexing', FALSE, FALSE);
    if (!$locationIndexing) {
      // Not configured - nothing to do.
      return;
    }
    $qry = <<<SQL
      -- Table for changed locations joined to their higher locations.
      DROP TABLE IF EXISTS contained_locations_higher_locations;
      CREATE TEMPORARY TABLE contained_locations_higher_locations (
        location_id integer,
        higher_location_id integer
      );

      -- Table for changed higher locations joined to their contained locations.
      DROP TABLE IF EXISTS higher_locations_contained_locations;
      CREATE TEMPORARY TABLE higher_locations_contained_locations (
        higher_location_id integer,
        location_id integer
      );

    SQL;
    $db->query($qry);
    // Loop through the pairs of location types that are indexed against
    // another (higher) location type.
    foreach ($locationIndexing as $config) {
      $type = pg_escape_literal($db->getLink(), $config['location_type']);
      $higherType = pg_escape_literal($db->getLink(), $config['higher_location_type']);
      $typeId = $db->query("select id from cache_termlists_terms where preferred_term=$type and termlist_title ilike 'location types'")->current()->id;
      $higherTypeId = $db->query("select id from cache_termlists_terms where preferred_term=$higherType and termlist_title ilike 'location types'")->current()->id;
      // For each changed location of the right type & website, collect the
      // higher locations its supposed to be within.
      $qry = <<<SQL
        INSERT INTO contained_locations_higher_locations
        SELECT l.id, hl.id
        FROM locations l
        JOIN work_queue w
          ON w.claimed_by='$procId'
          AND w.entity='location'
          AND w.task='task_spatial_index_builder_location'
          AND w.record_id=l.id
        JOIN locations_websites lw
          ON lw.location_id=l.id AND lw.website_id=$config[website_id]
        JOIN locations hl
          ON hl.deleted=false
          AND hl.location_type_id=$higherTypeId
          AND st_intersects(l.boundary_geom, hl.boundary_geom)
          AND (st_geometrytype(l.boundary_geom)='ST_Point' OR NOT st_touches(l.boundary_geom, hl.boundary_geom))
        LEFT JOIN locations_websites hw
          ON hw.location_id=hl.id
          AND hw.website_id=lw.website_id
        WHERE l.deleted=false
        -- Higher location must be in the same website, or public.
        AND (hw.id IS NOT NULL OR hl.public=true)
        AND l.location_type_id=$typeId;
      SQL;
      $db->query($qry);
      // For each changed location that's in the higher location type given in
      // the configuration, rescan the contained locations and collect in a
      // temp table.
      $qry = <<<SQL
        INSERT INTO higher_locations_contained_locations
        SELECT hl.id, l.id
        FROM locations hl
        JOIN work_queue w
          ON w.claimed_by=?
          AND w.entity='location'
          AND w.task='task_spatial_index_builder_location'
          AND w.record_id=hl.id
        LEFT JOIN locations_websites hw
          ON hw.location_id=hl.id
        JOIN locations l
          ON l.deleted=false
          AND l.location_type_id=$typeId
          AND st_intersects(l.boundary_geom, hl.boundary_geom)
          AND (st_geometrytype(l.boundary_geom)='ST_Point' OR NOT st_touches(l.boundary_geom, hl.boundary_geom))
        JOIN locations_websites lw
          ON lw.location_id=l.id
          AND lw.website_id=?
        WHERE hl.deleted=false
        -- Higher location must be in the same website, or public.
        AND (lw.website_id=hw.website_id OR hl.public=true)
        AND hl.location_type_id=?;
      SQL;
      $db->query($qry, [$procId, $config['website_id'], $higherTypeId]);
    }
    // Now, aggregate the higher locations for all changed locations and apply
    // the updated list to the records.
    $qry = <<<SQL
      DROP TABLE IF EXISTS location_updates;

      SELECT w.record_id as location_id, array_remove(array_agg(DISTINCT clhl.higher_location_id), NULL) as higher_location_ids
      INTO TEMPORARY location_updates
      FROM work_queue w
      LEFT JOIN contained_locations_higher_locations clhl ON clhl.location_id=w.record_id
      WHERE w.claimed_by=?
      AND w.entity='location'
      AND w.task='task_spatial_index_builder_location'
      GROUP BY w.record_id;

      UPDATE locations u
      SET higher_location_ids=lu.higher_location_ids
      FROM location_updates lu
      WHERE lu.location_id=u.id;
    SQL;
    $db->query($qry, [$procId]);
    // For all the changed locations in the higher type layer, remove the ID
    // from any child locations that are no longer within the higher location.
    $qry = <<<SQL
      UPDATE locations u
      SET higher_location_ids = array_remove(u.higher_location_ids, w.record_id)
      FROM work_queue w
      WHERE w.claimed_by=?
      AND w.entity='location'
      AND w.task='task_spatial_index_builder_location'
      AND u.higher_location_ids @> ARRAY[w.record_id]
      AND u.id NOT IN (SELECT location_id FROM higher_locations_contained_locations WHERE higher_location_id=w.record_id);
    SQL;
    $db->query($qry, [$procId]);
    // Finally, for all the changed locations in the higher type layer, add the
    // child location IDs that are missing.
    $qry = <<<SQL
      UPDATE locations u
      SET higher_location_ids = array_append(u.higher_location_ids, w.record_id)
      FROM work_queue w
      WHERE w.claimed_by=?
      AND w.entity='location'
      AND w.task='task_spatial_index_builder_location'
      AND NOT (u.higher_location_ids @> ARRAY[w.record_id])
      AND u.id IN (SELECT location_id FROM higher_locations_contained_locations WHERE higher_location_id=w.record_id);
    SQL;
    $db->query($qry, [$procId]);
  }

}
