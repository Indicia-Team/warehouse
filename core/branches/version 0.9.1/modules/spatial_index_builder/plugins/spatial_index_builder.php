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
 * @todo Filter to a config file defined list of location types
 */

function spatial_index_builder_metadata() {
  return array(
    'requires_occurrences_delta'=>TRUE,
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
    $recordCount = $db->count_records('occdelta');
    $locCount = spatial_index_builder_get_location_list($last_run_date, $db);
    if ($recordCount + $locCount > 0) 
      spatial_index_builder_populate($db);
    spatial_index_builder_cleanup($db);
  } catch (Exception $e) {
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
  $message = "Building spatial index for ".$r[0]['count']." locations(s)";
  echo "$message<br/>";
  Kohana::log('debug', $message);
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
      select sample_id from occdelta union select id from samples where deleted=true
    );";
  $db->query($query);
  Kohana::log('debug', "Cleaned up index_locations_samples before populating new values.");
  // are we filtering by location type?
  $filter=spatial_index_builder_get_type_filter();
  list($join, $where, $surveyRestriction)=$filter;
  // Now the actual population
  $query = "insert into index_locations_samples (location_id, sample_id, contains)
    select l.id, s.id, st_contains(l.boundary_geom, s.geom)
    from locations l
    $join
    join samples s on s.deleted=false
      and (st_intersects(l.boundary_geom, s.geom) and not st_touches(l.boundary_geom, s.geom))
    where l.deleted=false
    and (l.id in (select id from loclist)
    or s.id in (select sample_id from occdelta))
    $where
    $surveyRestriction";
  $message = $db->query($query)->count().' index_locations_samples entries created.';
  echo "$message<br/>";
  Kohana::log('debug', $message);
}

function spatial_index_builder_cleanup($db) {
  $db->query('drop table loclist');
}