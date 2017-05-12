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
 * @package	Modules
 * @subpackage Spatial index builder
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Plugin module which creates an index of the associations between locations and the overlapping
 * samples (allowing records to be easily found). 
 * @todo Initial population after installation
 */

function spatial_index_builder_metadata() {
  return array(
    'always_run'=>TRUE // don't skip this plugin as the scheduled tasks runner does not take into account changed locations
  );
}

/**
 * Hook into the task scheduler. Creates a table which identifies a list of records
 * which overlap with each site, allowing fast site based reporting.
 * @param string $last_run_date Date last run, or null if never run
 * @param object $db Database object.
 */
function spatial_index_builder_scheduled_task($last_run_date, $db) {  
  if (isset($_GET['force_index_rebuild']))
    $last_run_date=date('Y-m-d', time()-60*60*24*365*200);
  elseif ($last_run_date===null)
    // first run, so get all records changed in last day. Query will automatically gradually pick up the rest.
    $last_run_date=date('Y-m-d', time()-60*60*24);
  try {  
    $locCount = spatial_index_builder_get_location_list($last_run_date, $db);
    $sampleCount = spatial_index_builder_get_sample_list($last_run_date, $db);
    $message = "Building spatial index for $locCount locations and $sampleCount samples(s)<br/>";
    kohana::log('debug', $message);
    echo $message;
    if ($locCount + $sampleCount> 0) {
      spatial_index_builder_populate($db);
      spatial_index_builder_add_to_cache($db);
    }
    spatial_index_builder_cleanup($db);
  } catch (Exception $e) {
    error_logger::log_error('Spatial index builder scheduled task', $e);
    echo $e->getMessage();
  }
  
}

/**
 * Build a temporary table with the list of new and changed locations we will process, so that we have
 * consistency if changes are happening concurrently.
 * @param $last_run_date Timestamp when this was last run, used to get DB changed records
 * @param object $db Database object
 * @return integer Count of locations found
 */
function spatial_index_builder_get_location_list($last_run_date, $db) {
  $filter=spatial_index_builder_get_type_filter();
  list($join, $where, $surveyRestriction)=$filter;
  $query = "select l.id, now() as timepoint into temporary loclist 
from locations l
$join
where l.deleted=false 
and l.updated_on>'$last_run_date'
$where";
  $db->query($query);
  $r = $db->query('select count(*) as count from loclist')->result_array(false);  
  return $r[0]['count'];
}

/**
 * Build a temporary table with the list of new and changed samples we will process, so that we have
 * consistency if changes are happening concurrently.
 * @param $last_run_date Timestamp when this was last run, used to get DB changed records
 * @param object $db Database object
 * @return integer Count of samples found
 */
function spatial_index_builder_get_sample_list($last_run_date, $db) {
  $query = "select s.id, now() as timepoint into temporary smplist 
from samples s
where s.deleted=false 
and s.updated_on>'$last_run_date'
";
  $db->query($query);
  $r = $db->query('select count(*) as count from smplist')->result_array(false);
  return $r[0]['count'];
}


/** 
 * Reads the config file, if any, and returns details of the join and where clause that must be added
 * to the indexing query to respect the location type filter in the config file.
 * @return array Array containing the join SQL in the first entry and where SQL in the second.
 */
function spatial_index_builder_get_type_filter() {
  $config=kohana::config_load('spatial_index_builder', false);
  $surveyRestriction = '';
  if (array_key_exists('location_types', $config)) {
    $join='join cache_termlists_terms t on t.id=l.location_type_id';
    $where="and t.preferred_term in ('".implode("','", $config['location_types'])."')";
    if (array_key_exists('survey_restrictions', $config)) {
      foreach ($config['survey_restrictions'] as $type => $surveyIds) {
        $surveys = implode(', ', $surveyIds);
        $surveyRestriction .= "and (t.preferred_term<>'$type' or s.survey_id in ($surveys))\n";
      }
    }
  } else {
    $join='';
    $where='';
  }
  return array($join, $where, $surveyRestriction);
}

/**
 * Inserts missing index_locations_samples records for the identified list of either
 * locations or samples that have been updated.
 * @param $db
 * @param $filter
 * @param string $limit Sql to limit to the updated locations or samples
 */
function _spatial_index_builder_index_insert($db, $filter, $limit) {
  list($join, $where, $surveyRestriction)=$filter;
  // Now the actual population
  $query = "insert into index_locations_samples (location_id, sample_id, contains, location_type_id)
    select distinct 
      l.id, s.id, coalesce(linked.id, 0) = l.id or st_contains(l.boundary_geom, s.geom), l.location_type_id
    from locations l
    $join
    join samples s on s.deleted=false
      and (st_intersects(l.boundary_geom, s.geom) and not st_touches(l.boundary_geom, s.geom))
    $limit
    left join index_locations_samples ils on ils.location_id=l.id and ils.sample_id=s.id
    join cache_samples_nonfunctional snf on snf.id=s.id
    left join locations linked on linked.id=snf.attr_linked_location_id and linked.deleted=false
    where ils.id is null
    and l.deleted=false
    and (
      -- if a linked_location_id specified, then limit the indexing to the linked location for this location type.
      snf.attr_linked_location_id is null 
      or linked.id = l.id 
      or linked.location_type_id <> l.location_type_id 
      )
    $where
    $surveyRestriction";
  $message = $db->query($query)
      ->count() . ' index_locations_samples entries created.';
  echo "$message<br/>";
  Kohana::log('debug', $message);
}

/** 
 * Performs the actual population of ths index.
 * @param object $db Database object
 */
function spatial_index_builder_populate($db) {
  // First task - cleanup any existing records for the samples and locations we are about to rescan.
  $query = "delete from index_locations_samples where location_id in (
      select id from loclist union select id from locations where deleted=true
    );";
  $db->query($query);
  $query = "delete from index_locations_samples where sample_id in (
      select id from smplist union select id from samples where deleted=true
    );";
  $db->query($query);
  Kohana::log('debug', "Cleaned up index_locations_samples before populating new values.");
  // are we filtering by location type?
  $filter=spatial_index_builder_get_type_filter();
  _spatial_index_builder_index_insert($db, $filter, 'join smplist list on list.id=s.id');
  _spatial_index_builder_index_insert($db, $filter, 'join loclist list on list.id=l.id');
}

function spatial_index_builder_cleanup($db) {
  $db->query('drop table loclist');
  $db->query('drop table smplist');
}

/**
 * if cache_builder module installed, then we want to add spatial index info into the cache tables
 * for best performance.
 * @param $db
 */
function spatial_index_builder_add_to_cache($db) {
  $config=kohana::config_load('spatial_index_builder', false);
  if (!array_key_exists('location_types', $config) || !array_key_exists('unique', $config))
    return;
  $types = $db->select('id, term')->from('cache_termlists_terms')
    ->where('termlist_title', 'Location types')
    ->in('term', $config['location_types'])
    ->get()->result_array(false);
  if (!count($types))
    return;
  $s_sets = array();
  $o_sets = array();
  $overlapping_fix_queries = array();
  $joins = array();
  foreach ($types as $type) {
    // We can only do this type of indexing for boundary types that occur only once per sample
    if (!in_array($type['term'], $config['unique']))
      continue;
    // Script for handling updated samples can be constructed to do all location types
    // in one go.
    $column = 'location_id_' . preg_replace('/[^\da-z]/', '_', strtolower($type['term']));
    $s_sets[] = "$column = ils$type[id].location_id";
    $o_sets[] = "$column = s.$column";
    $joins[] = <<<JOIN
LEFT JOIN (index_locations_samples ils$type[id]
  JOIN locations l$type[id] 
      ON l$type[id].id=ils$type[id].location_id AND l$type[id].deleted=false AND 
      (l$type[id].code IS NULL OR l$type[id].code NOT LIKE '%+%')
) ON ils$type[id].sample_id=s.id AND ils$type[id].location_type_id=$type[id] AND ils$type[id].contains=true
JOIN;
    // Script for handling updated locations is a bit more complex so we have to run
    // once per location type
    $db->query(<<<QRY
UPDATE cache_samples_functional u
SET $column = null
FROM loclist l
WHERE l.id=u.$column;
QRY
    );
    $db->query(<<<QRY
UPDATE cache_occurrences_functional u
SET $column = null
FROM loclist l
WHERE l.id=u.$column;
QRY
    );
    $db->query(<<<QRY
UPDATE cache_samples_functional u
SET $column = ils$type[id].location_id
FROM locations l
LEFT JOIN index_locations_samples ils$type[id] on ils$type[id].location_id=l.id
    and ils$type[id].location_type_id=$type[id] and ils$type[id].contains=true
JOIN loclist list on list.id=l.id
WHERE u.id=ils$type[id].sample_id
AND (l.code IS NULL OR l.code NOT LIKE '%+%');
QRY
    );
    $db->query(<<<QRY
UPDATE cache_occurrences_functional u
SET $column = ils$type[id].location_id
FROM locations l
LEFT JOIN index_locations_samples ils$type[id] on ils$type[id].location_id=l.id
    and ils$type[id].location_type_id=$type[id] and ils$type[id].contains=true
JOIN loclist list on list.id=l.id
WHERE u.sample_id=ils$type[id].sample_id
AND (l.code IS NULL OR l.code NOT LIKE '%+%');
QRY
    );
    // The following stuff fixes any squares that lie on boundaries
    $overlapping_fix_queries[] = <<<QRY
DROP TABLE IF EXISTS overlapping_boundaries;
    
SELECT s.website_id, s.survey_id, s.id AS sample_id, ils.location_id, 0::float AS area
INTO TEMPORARY overlapping_boundaries
FROM cache_samples_functional s
JOIN index_locations_samples ils ON ils.sample_id=s.id AND ils.location_type_id=$type[id] AND ils.contains=false
JOIN locations l ON l.id=ils.location_id and l.deleted=false AND COALESCE(l.code, '') NOT LIKE '%+%'
JOIN smplist slist ON slist.id=ils.sample_id;

INSERT INTO overlapping_boundaries
SELECT s.website_id, s.survey_id, s.id AS sample_id, ils.location_id, 0::float AS area
FROM cache_samples_functional s
JOIN index_locations_samples ils ON ils.sample_id=s.id AND ils.location_type_id=$type[id] AND ils.contains=false
JOIN locations l ON l.id=ils.location_id and l.deleted=false AND COALESCE(l.code, '') NOT LIKE '%+%'
JOIN loclist llist ON llist.id=ils.location_id
LEFT JOIN overlapping_boundaries ob ON ob.location_id=ils.location_id AND ob.sample_id=s.id
WHERE ob.sample_id IS NULL;

UPDATE overlapping_boundaries ob
SET area=st_area(st_intersection(s.geom, l.boundary_geom))
FROM samples s, locations l
WHERE s.id=ob.sample_id AND l.id=ob.location_id
AND s.deleted=false
AND l.deleted=false;

DELETE FROM overlapping_boundaries WHERE area=0;

DELETE from overlapping_boundaries ob1
USING overlapping_boundaries ob2 
WHERE ob2.sample_id=ob1.sample_id
AND (ob2.area>ob1.area OR (ob2.area=ob1.area AND ob2.location_id<ob1.location_id));

UPDATE cache_occurrences_functional o
SET $column = ob.location_id
FROM overlapping_boundaries ob 
WHERE o.website_id=ob.website_id AND o.survey_id=ob.survey_id AND o.sample_id=ob.sample_id
AND COALESCE(o.$column, 0)<>ob.location_id;

UPDATE cache_samples_functional s
SET $column = ob.location_id
FROM overlapping_boundaries ob 
WHERE s.website_id=ob.website_id AND s.survey_id=ob.survey_id AND s.id=ob.sample_id
AND COALESCE(s.$column, 0)<>ob.location_id;

DROP TABLE overlapping_boundaries;
QRY;
  }
  if (count($s_sets)) {
    $s_sets = implode(",\n", $s_sets);
    $o_sets = implode(",\n", $o_sets);
    $joins = implode("\n", $joins);
    $db->query(<<<QRY
UPDATE cache_samples_functional u
SET $s_sets
FROM samples s
$joins
JOIN smplist list on list.id=s.id
WHERE u.id=s.id;
QRY
    );
    $db->query(<<<QRY
UPDATE cache_occurrences_functional u
SET $o_sets
FROM cache_samples_functional s
JOIN smplist list on list.id=s.id
WHERE s.id=u.sample_id;
QRY
    );
  }
  foreach ($overlapping_fix_queries as $qry) {
    $db->query($qry);
  }
}