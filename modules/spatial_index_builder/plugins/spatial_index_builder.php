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
    $recordCount = spatial_index_builder_get_sample_list($last_run_date, $db);
    $locCount = spatial_index_builder_get_location_list($last_run_date, $db);
    if ($recordCount + $locCount > 0) 
      spatial_index_builder_populate($db);
    spatial_index_builder_cleanup($db);
  } catch (Exception $e) {
    echo $e->getMessage();
  }
  
}

/**
 * Build a temporary table with the list of samples we will process, so that we have
 * consistency if changes are happening concurrently. 
 * @param type $db 
 */
function spatial_index_builder_get_sample_list($last_run_date, $db) {
  $query = "select s.id, now() as timepoint into temporary smplist 
from samples s 
where s.deleted=false 
and s.updated_on>'$last_run_date'";
  $db->query($query);
  $r = $db->query('select count(*) as count from smplist')->result_array(false);
  echo "Building spatial index for ".$r[0]['count']." sample(s).<br/>";
  return $r[0]['count'];
}

/**
 * Build a temporary table with the list of new and changed locations we will process, so that we have
 * consistency if changes are happening concurrently. 
 * @param type $db 
 */
function spatial_index_builder_get_location_list($last_run_date, $db) {
  $filter=spatial_index_builder_get_type_filter();
  list($join, $where)=$filter;
  $query = "select l.id, now() as timepoint into temporary loclist 
from locations l
$join
where l.deleted=false 
and l.updated_on>'$last_run_date'
$where";
  $db->query($query);
  $r = $db->query('select count(*) as count from loclist')->result_array(false);
  echo "Building spatial index for ".$r[0]['count']." locations(s).<br/>";
  return $r[0]['count'];
}

/** 
 * Reads the config file, if any, and returns details of the join and where clause that must be added
 * to the indexing query to respect the location type filter in the config file.
 * @return array Array containing the join SQL in the first entry and where SQL in the second.
 */
function spatial_index_builder_get_type_filter() {
  $config=kohana::config_load('spatial_index_builder', false);
  if (array_key_exists('location_types', $config)) {
    $join='join cache_termlists_terms t on t.id=l.location_type_id';
    $where="and t.preferred_term in ('".implode("','", $config['location_types'])."')";
  } else {
    $join='';
    $where='';
  }
  return array($join, $where);
}

/** 
 * Performs the actual population of ths index.
 * @param object $db Database object
 */
function spatial_index_builder_populate($db) {
  // First task - cleanup any existing records for the samples and locations we are about to rescan.
  $query = "delete from index_locations_samples where location_id in (
      select id from loclist union select id from locations where deleted=false
    ) or sample_id in (
      select id from smplist union select id from samples where deleted=false
    );";
  $db->query($query);
  // are we filtering by location type?
  $filter=spatial_index_builder_get_type_filter();
  list($join, $where)=$filter;
  // Now the actual population
  $query = "insert into index_locations_samples (location_id, sample_id, contains)
    select l.id, s.id, st_contains(l.boundary_geom, s.geom)
    from locations l
    $join
    join samples s on s.deleted=false and st_intersects(l.boundary_geom, s.geom)
    where l.deleted=false
    and (l.id in (select id from loclist)
    or s.id in (select id from smplist))
    $where";
  echo $db->query($query)->count().' index entries created.<br/>';
}

function spatial_index_builder_cleanup($db) {
  $db->query('drop table smplist');
  $db->query('drop table loclist');
}

?>