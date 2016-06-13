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
 * @subpackage Summary_builder
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Helper class for summary_builder functionality
 *
 * @package	Modules
 * @subpackage Summary_builder
 */
class summary_builder {

  /**
   * Performs the actual task of table population.
   */
	
  private static $verbose;

  public static function force_summary_truncate(&$db, $last_run_date, $verbose) {
  	 
  	self::$verbose = $verbose;
  	 
  	$queries = kohana::config('summary_builder');
  	echo date(DATE_ATOM).' Truncating full data set.<br/>';
  	$r = $db->query($queries['summary_truncate']);
  }
  
  public static function populate_summary_table(&$db, $last_run_date, $verbose, $rebuild, $clear, $missing_check) {
  	
  	self::$verbose = $verbose;
  	
  	$queries = kohana::config('summary_builder');
  	$r = $db->query($queries['select_definitions'])->result_array(false);
  	if(count($r)){
  		foreach($r as $row){
  			if($clear == $row['survey_id']) {
  				echo date(DATE_ATOM).' Clearing of summary data for survey ID '.$row['survey_id'].' requested.<br/>';
  				if(isset($_GET['location_id']) && $_GET['location_id'] != '')
	  				$query = str_replace(array('#survey_id#','#location_id#'), array($row['survey_id'], $_GET['location_id']), $queries['clear_survey_location']);
  				else
	  				$query = str_replace(array('#survey_id#'), array($row['survey_id']), $queries['clear_survey']);
	  			$count = $db->query($query)->count();
  				if(self::$verbose) {
  					echo date(DATE_ATOM).' Removed '.$count.' records. Rebuild will commence on next invocation of scheduled tasks.<br/>';
	  				if (!isset($row['check_for_missing']) || $row['check_for_missing'] == 'f') return;
  						echo ' Missing check not currently enabled in the definition.<br/>';
  				}
  			} else if($rebuild === true || $rebuild == $row['survey_id']) {
  				echo date(DATE_ATOM).' Rebuild of summary data for survey ID '.$row['survey_id'].' requested.<br/>';
  				$query = str_replace(array('#survey_id#'), array($row['survey_id']), $queries['first_sample_creation_date']);
  				$sample_date = $db->query($query)->result_array(false);
  				if(count($sample_date)){
  					$query = str_replace(array('#survey_id#','#date#'),
	  						array($row['survey_id'], $sample_date[0]['first_date']),
	  						$queries['rebuild_survey']);
	  				$count = $db->query($query)->count();
  					if(self::$verbose){
  						echo date(DATE_ATOM).' Date altered on '.$count.' records. Rebuild will commence on next invocation of scheduled tasks.<br/>';
  					  	if (!isset($row['check_for_missing']) || $row['check_for_missing'] == 'f') return;
  							echo ' Missing check not currently enabled in the definition.<br/>';
	  				}
  				} else {
  					$query = str_replace(array('#survey_id#'), array($row['survey_id']), $queries['clear_survey']);
	  				$count = $db->query($query)->count();
  					if(self::$verbose) echo date(DATE_ATOM).' No samples detected for Survey '.$row['survey_id'].'. Precautionary clearance of summary data attempted: '.$count.' summary records removed.<br/>';
  				}
	  		} else {
  				echo date(DATE_ATOM).' Processing summariser_definition ID '.$row['id'].' for survey ID '.$row['survey_id'].'<br/>';
  				self::populate_summary_table_for_survey($db, $last_run_date, $row, ($missing_check === true || $missing_check == $row['survey_id']));
  			}
  		}
  	} else {
  		echo date(DATE_ATOM).' No summariser_definitions to be processed.<br/>';
  	}
  }

  /*
   * Bit of an oddball situation. Our table does not have a sequence. When inserting, The database driver sets
   * the insert_id for the pg_result from the lastval function - with no sequence this returns an error, and the error
   * is ignored from a PHP point of view. However from Postgres POV, this causes a transaction abort/rollback,
   * so we can't wrap a transaction around it. We need a transaction though, so this is a hack to populate
   * the lastval. Use a temporary sequence, as lastval gives an error if the sequence has been dropped.
   */
  private static function init_lastval(&$db) {
  	$db->query("CREATE TEMPORARY SEQUENCE summary_builder_dummy_sequence;"); // dropped automatically at end of process
  	$db->query("SELECT NEXTVAL('summary_builder_dummy_sequence');");
  }
  
  private static function populate_summary_table_for_survey(&$db, $last_run_date, $definition, $missing_check) {
  	$YearTaxonLocationUser = array();
  	$YearTaxonLocation = array();
  	$YearTaxonUser = array();
  	$YearTaxon = array(); // list of taxa in a given year.
    try {
      $count=summary_builder::get_changelist($db, $last_run_date, $definition, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon, $missing_check);
	  if($count>0)
	  	summary_builder::do_summary($db, $definition, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon);
    } catch (Exception $e) {
			error_logger::log_error('Building summary', $e);
      echo $e->getMessage();
    }
   
  	return;
  }
  
  private static function check_deleted_locations(&$db, $last_run_date, $definition, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon, $missing_check)
  {
  	// Can't pick up setting of locations_websites deleted since last_run as it doesn't have an updated_on field.
  	//   Means can't really do a "location has been deleted since last run": just do a sweep up.
  	//
  	// Pick up if a deleted location has any entries in the summary_occurrences (sweeps up all).
  	//   if yes, the link to the sample will have been broken by the cascade trigger, which also doesn't set the updated_on date...
  	// Retrieve all the users (ulist) which have entries in the cache for this location.
  	// Need to delete all the entries which have a location_id set to the deleted location (user and non user specific).
  	// Need to rebuild all the entries which have a user_id in ulist.
  	// Need to rebuild all the top level global entries.
  	// Can't put a limit on the numbers returned, as need all from each location processed at the same time.
  	
  	// TODO only do this if the first run in the day

  	if (!$missing_check && (!isset($definition['check_for_missing']) || $definition['check_for_missing'] == 'f')) return;
  		 
  	$queries = kohana::config('summary_builder');
  	$query = str_replace(array('#survey_id#','#website_id#'),
  						 array($definition['survey_id'], $definition['website_id']),
  						 $queries['get_deleted_locations_query']);
  	
  	$r = $db->query($query)->result_array(false); // returns location_id, user_id, taxa_taxon_list_id, year
  	
  	$count = count($r);
  	if($count){
  		if(self::$verbose) echo date(DATE_ATOM).' '.$count.' deleted locations records to be processed.<br/>';
  		foreach($r as $row){
 			$year = $row['year'];
  			// Flag the rebuild of a top level year/taxon combination.
 			if(!isset($YearTaxon[$year])) $YearTaxon[$year]=array();
 			if(!in_array($row['taxa_taxon_list_id'], $YearTaxon[$year])) $YearTaxon[$year][] = $row['taxa_taxon_list_id'];
  			// Flag the rebuild of a top level year/taxon/user combination.
 			if(!isset($YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']])) $YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']] = array();
 			if(!in_array($row['user_id'], $YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']])) $YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']][] = $row['user_id'];
  			// no rebuild for location entries, as the location is deleted, so just remove them immediately.
  		  	summary_builder::do_delete($db, $definition, $year, $row['taxa_taxon_list_id'], $row['location_id'], $row['user_id']);
 			summary_builder::do_delete($db, $definition, $year, $row['taxa_taxon_list_id'], $row['location_id'], false);
  		}
  		$limit = $limit-$count;
   	} else {
  		if(self::$verbose) echo date(DATE_ATOM).' No deleted locations records processed.<br/>';
  	}
  }

  	private static $locationYearUser; // cache
  	
  private static function check_samples(&$db, $last_run_date, $definition, &$limit, &$YearTaxonLocationUser, &$YearTaxonLocation, &$YearTaxonUser, &$YearTaxon, $missing_check)
  {
  	// 2 modes: since last run and sweep.

  	self::$locationYearUser = array();

	// Pick up if a top level sample or subsample has been deleted -> user_id and location_id.
	//   May or may not have occurrence records, but still affects calculations.
	// Need to rebuild all the entries which have a location_id set to the location (user and non user specific) for all taxa that have been visited at that location that year..
	// Need to rebuild all the entries which have the user_id and no location set for all taxa that have been visited at that location that year..
	// Need to rebuild all the top level global entries (there are no new taxa).
  	  	 
  	$queries = kohana::config('summary_builder');
 
  	if ($missing_check || (isset($definition['check_for_missing']) && $definition['check_for_missing'] != 'f')) {
  		if(self::$verbose) echo date(DATE_ATOM).' Running missed deleted sample check for survey ID '.$definition['survey_id'].'<br/>';
  		$query = $queries['get_missed_deleted_samples_query'];
  	} else {
  		if(self::$verbose) echo date(DATE_ATOM).' Running deleted sample check for survey ID '.$definition['survey_id'].'<br/>';
  		$query = $queries['get_deleted_samples_query'];
  	}
  	self::run_samples_query($db, $query, $last_run_date, $definition, $limit, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon);
  	if($limit <= 0 ) return;
  		 
  	if ($missing_check || (isset($definition['check_for_missing']) && $definition['check_for_missing'] != 'f')) {
  		if(self::$verbose) echo date(DATE_ATOM).' Running missed created sample check for survey ID '.$definition['survey_id'].'<br/>';
  		$query = $queries['get_missed_created_samples_query'];
  	} else {
  		if(self::$verbose) echo date(DATE_ATOM).' Running created sample check for survey ID '.$definition['survey_id'].'<br/>';
  		$query = $queries['get_created_samples_query'];
  	}
  	self::run_samples_query($db, $query, $last_run_date, $definition, $limit, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon);  	
  }

  private static function run_samples_query(&$db, $query, $last_run_date, $definition, &$limit, &$YearTaxonLocationUser, &$YearTaxonLocation, &$YearTaxonUser, &$YearTaxon)
  {
  	$query = str_replace(array('#survey_id#', '#date#', '#limit#'),
  							array($definition['survey_id'], $last_run_date, $limit),
  							$query);
  	$r = $db->query($query)->result_array(false); // returns date_start, created_by_id, location_id
  	
  	$count = count($r);
  	$actual = 0;
  	if($count){
  		if(self::$verbose) echo date(DATE_ATOM).' Maximum of '.$count.' sample records to be processed (limit = '.$limit.').<br/>';
  		foreach($r as $row) {
			$c = summary_builder::flag_all_taxa ($db, $definition['survey_id'], $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon, substr($row['date_start'], 0, 4), $row['location_id'], $row['user_id']);
  			$limit = $limit-$c;
  			$actual++;
  			if($limit <= 0 ) {
  				if(self::$verbose) echo date(DATE_ATOM).' '.$actual.' sample records processed, record count limit reached.<br/>';
  				if(self::$verbose) echo date(DATE_ATOM).' Date on sample last processed : '.$row['date_start'].'<br/>';
  				$limit = 0;
  				return;
  			}
  		}
  		if(self::$verbose) echo date(DATE_ATOM).' Record count limit '.$limit.' after sample records processing.<br/>';
  	} else {
  		if(self::$verbose) echo date(DATE_ATOM).' No sample records processed.<br/>';
  	}
  }

  private static function flag_one_taxa (&$YearTaxonLocationUser, &$YearTaxonLocation, &$YearTaxonUser, &$YearTaxon, $year, $taxon, $location_id, $user_id)
  {
 	if(!isset($YearTaxon[$year])) $YearTaxon[$year]=array();
 	if(!in_array($taxon, $YearTaxon[$year])) $YearTaxon[$year][] = $taxon;
 	// create list of year/taxon/user
 	if(!isset($YearTaxonUser[$year.':'.$taxon])) $YearTaxonUser[$year.':'.$taxon] = array();
 	if(!in_array($user_id, $YearTaxonUser[$year.':'.$taxon])) $YearTaxonUser[$year.':'.$taxon][] = $user_id;
  	// create list of year/taxon/location
 	if(!isset($YearTaxonLocation[$year.':'.$taxon])) $YearTaxonLocation[$year.':'.$taxon] = array();
 	if(!in_array($location_id, $YearTaxonLocation[$year.':'.$taxon])) $YearTaxonLocation[$year.':'.$taxon][] = $location_id;
  	// create list of year/taxon/location/user
 	if(!isset($YearTaxonLocationUser[$year.':'.$taxon.':'.$location_id])) $YearTaxonLocationUser[$year.':'.$taxon.':'.$location_id] = array();
 	if(!in_array($user_id, $YearTaxonLocationUser[$year.':'.$taxon.':'.$location_id])) $YearTaxonLocationUser[$year.':'.$taxon.':'.$location_id][] = $user_id;
  }
  
  private static function flag_all_taxa (&$db, $survey_id, &$YearTaxonLocationUser, &$YearTaxonLocation, &$YearTaxonUser, &$YearTaxon, $year, $location_id, $user_id)
  {
  	if(isset(self::$locationYearUser[$year.':'.$location_id.':'.$user_id]))
  		return 0; // previously processed a sample for this year/location/user, so no need to do again

  	$queries = kohana::config('summary_builder');
  	$query = str_replace(array('#survey_id#', '#location_id#', '#year#'),
  							array($survey_id, $location_id, $year),
  							$queries['get_taxa_query']);
  	$r = $db->query($query)->result_array(false); // returns all taxa_taxon_list_id for this year/location combination
  	self::$locationYearUser[$year.':'.$location_id.':'.$user_id] = true;
    
  	$count = count($r);
  	if($count){
  		foreach($r as $row){
  			// create list of year/taxon
  			$taxon = $row['taxa_taxon_list_id'];
  			summary_builder::flag_one_taxa ($YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon, $year, $taxon, $location_id, $user_id);
  		}
  	}
  	return $count;
  }
  
  private static function get_changelist(&$db, $last_run_date, $definition, &$YearTaxonLocationUser, &$YearTaxonLocation, &$YearTaxonUser, &$YearTaxon, $missing_check) {
  	$queries = kohana::config('summary_builder');
  	$limit = (isset($definition['max_records_per_cycle']) ? $definition['max_records_per_cycle'] : 1000);
  	if(self::$verbose) echo date(DATE_ATOM).' Record count limit: '.$limit.'<br/>';
  	
  	// the location deletion does not lead to any new calculations, just re-summarising existing data, so no impact on limit.
  	if(!isset($_GET['only']) || $_GET['only'] == 'locations')
  		summary_builder::check_deleted_locations($db, $last_run_date, $definition, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon, $missing_check);

  	if(!isset($_GET['only']) || $_GET['only'] == 'samples')
  		summary_builder::check_samples($db, $last_run_date, $definition, $limit, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon, $missing_check);
  	
  	// Now check occurrences
  	if($limit > 0 && (!isset($_GET['only']) || $_GET['only'] == 'occurrences')) {
  		if ($missing_check || (isset($definition['check_for_missing']) && $definition['check_for_missing'] != 'f')) {
  			if(self::$verbose) echo date(DATE_ATOM).' Start of missing occurrence query for survey ID '.$definition['survey_id'].'<br/>';
  			$query = str_replace(array('#date#','#survey_id#','#limit#'),
  					array($last_run_date,$definition['survey_id'],$limit),
  					$queries['get_missed_changed_occurrences_query']);
  		} else {
  			if(self::$verbose) echo date(DATE_ATOM).' Start of occurrence query for survey ID '.$definition['survey_id'].'<br/>';
  			$query = str_replace(array('#date#','#survey_id#','#limit#'),
  					array($last_run_date,$definition['survey_id'],$limit),
  					$queries['get_changed_occurrences_query']);
  		}
		$r = $db->query($query)->result_array(false);
	  	$count = count($r);
  		if($count){
  			if(self::$verbose) echo date(DATE_ATOM).' '.$count.' occurrences to be processed.<br/>';
  			foreach($r as $row)
  				summary_builder::flag_one_taxa ($YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon, substr($row['date_start'], 0, 4), $row['taxa_taxon_list_id'], $row['location_id'], $row['created_by_id']);
	  		$limit = $limit-$count;
	   	} else if(self::$verbose) echo date(DATE_ATOM).' No occurrences to be processed.<br/>';

  	  	if($limit > 0 && (!isset($_GET['only']) || $_GET['only'] == 'occurrences') && ($missing_check || (isset($definition['check_for_missing']) && $definition['check_for_missing'] != 'f'))) {
  			if(self::$verbose) echo date(DATE_ATOM).' Start of missing deleted occurrence query for survey ID '.$definition['survey_id'].'<br/>';
  			$query = str_replace(array('#date#','#survey_id#','#limit#'),
  					array($last_run_date,$definition['survey_id'],$limit),
  					$queries['get_missed_deleted_occurrences_query']);
			$r = $db->query($query)->result_array(false);
	  		$count = count($r);
 			if($count){
 				if(self::$verbose) echo date(DATE_ATOM).' '.$count.' occurrences to be processed.<br/>';
  				foreach($r as $row)
  					summary_builder::flag_one_taxa ($YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon, substr($row['date_start'], 0, 4), $row['taxa_taxon_list_id'], $row['location_id'], $row['created_by_id']);
	   		} else if(self::$verbose) echo date(DATE_ATOM).' No occurrences to be processed.<br/>';
	  	}
  	}
  	
  	if(count($YearTaxon)>0){
  		ksort($YearTaxon);
  		ksort($YearTaxonUser);
  		ksort($YearTaxonLocation);
  	  	ksort($YearTaxonLocationUser);
 	}
  	return count($YearTaxon);
  }

  private static function do_summary(&$db, $definition, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon) {
  	$queries = kohana::config('summary_builder');
  	self::init_lastval($db);
  	foreach($YearTaxon as $year=>$taxonList) {
	  $db->begin();
	  echo date(DATE_ATOM).' Processing data for '.$year.'<br />';
	  $yearStart = new DateTime($year.'-01-01');
	  $yearEnd = new DateTime($year.'-01-01');
	  // calculate date to period conversions
	  if($definition['period_type']=='W'){
	  	// work out week numbers, and period mapping.
	  	// Week 1 = the week with date_from in
	  	$weekstart=explode('=',$definition['period_start']);
	  	if($weekstart[0]=='date'){
	  	  $weekstart_date = date_create($year."-".$weekstart[1]);
	  	  if(!$weekstart_date){
	  		echo date(DATE_ATOM).' ERROR : Weekstart month-day combination unrecognised {'.$weekstart[1].'}<br />';
	  		return;
	  	  }
	  	  $weekstart[1]=$weekstart_date->format('N'); // ISO Day of week - Mon=1, Sun=7
	  	}
	  	if(intval($weekstart[1])!=$weekstart[1] || $weekstart[1]<1 || $weekstart[1]>7) {
	  	  echo date(DATE_ATOM).' ERROR : Weekstart unrecognised or out of range {'.$weekstart[1].'}<br />';
	  	  return;
	  	}
	  	$consider_date = new DateTime($year.'-01-01');
	  	$weekNoOffset=0;
	  	while($consider_date->format('N')!=$weekstart[1]) $consider_date->modify('-1 day');
	  	$weekOne_date = date_create($year.'-'.$definition['period_one_contains']);
	  	if(!$weekOne_date){
	      echo date(DATE_ATOM).' ERROR : Week one month-day combination unrecognised {'.$definition['period_one_contains'].'}<br />';
	      return;
	  	}
	  	while($weekOne_date->format('N')!=$weekstart[1]) $weekOne_date->modify('-1 day'); // scan back to start of week
	  	while($weekOne_date > $consider_date){
	  	  $weekOne_date->modify('-7 days');
	  	  $weekNoOffset++;
	  	}
	  	
	  	// the season limits define the start and end of the recording season. The generation of estimates
	  	// is restricted to these weeks.
	  	// if no value is provided for either the start or the end, then the relevant value is assume to be either the start of the year
	  	// or the end of the year.
	  	// If a value is provided, it is inclusive. UKBMS is nominally 1,26: this differs from the old report_calendar_summary
	  	// page, where it was exclusive, and the limits where defined as 0,27.
	  	$season_limits=explode(',',$definition['season_limits']);
	  	$definition['season_limits_array'] = array('start' => ((count($season_limits) && $season_limits[0]!='') ? $season_limits[0] : false),
	  									'end' => ((count($season_limits)>1 && $season_limits[1]!='') ? $season_limits[1] : false));
	  	$periods=array();
	  	$periodMapping=array();
	  	// Build day number to period mapping. first period = 1, days 1st Jan = 0
	  	$dayIterator = new DateTime($year.'-01-01');
	  	$periodNo = 1-$weekNoOffset;
	  	while($dayIterator->format('Y')==$year){
	  		$periodMapping[$dayIterator->format('z')]=$periodNo;
	  		$dayIterator->modify('+1 day');
	  		if($dayIterator->format('N') == $weekstart[1]) $periodNo++;
	  	}
	  	// Build period definition. first period = 1, days 1st Jan = 0
	  	$dayIterator = clone $weekOne_date;
	  	$periodNo = 1-$weekNoOffset;
	  	$periodLength = date_interval_create_from_date_string('6 days');
	  	while($dayIterator->format('Y')<=$year){
	  		$endDate = clone $dayIterator;
	  		$endDate->modify('+6 day');
	  		$periods[$periodNo]=array('date_start'=>$dayIterator->format('Y-m-d'), 'date_end'=>$endDate->format('Y-m-d'));
	  		$dayIterator->modify('+7 days');
	  		$periodNo++;
	  	}
	  } else {
	  	echo date(DATE_ATOM).' ERROR : period_type unrecognised {'.$definition['period_type'].'}<br />';
	  	return;
	  }
	  foreach($taxonList as $taxonID) {
 	  		$taxon = $db->query("SELECT * FROM cache_taxa_taxon_lists WHERE id = $taxonID")->result_array(false);
	  		if(count($taxon)!=1) {
	  			echo date(DATE_ATOM)." ERROR : Taxon search for id = $taxonID returned wrong number of rows: ".count($taxon)." - expected one row. <br/>";
	  			continue;
	  		}
	  		 
	  	foreach($YearTaxonLocation[$year.':'.$taxonID] as $locationID) {
	  	  foreach($YearTaxonLocationUser[$year.':'.$taxonID.':'.$locationID] as $userID){
  		  	$query = str_replace(array('#year#', '#survey_id#', '#taxon_id#', '#location_id#', '#user_id#', '#attr_id#'),
  		  						 array($year, $definition['survey_id'], $taxonID, $locationID, $userID, $definition['occurrence_attribute_id']),
  		  						 $definition['occurrence_attribute_id'] != '' ? $queries['get_YearTaxonLocationUser_Attr_query'] : $queries['get_YearTaxonLocationUser_query']);
            $data = array();
	  		if(self::$verbose) echo date(DATE_ATOM).' Processing data for Y'.$year.' T'.$taxonID.' L'.$locationID.' U'.$userID.'<br />';
	  		summary_builder::do_delete($db, $definition, $year, $taxonID, $locationID, $userID);
	  		if(!summary_builder::load_data($db, $data, $periods, $periodMapping, $query)) {
	  			if(self::$verbose) echo date(DATE_ATOM).' Data cleared, none inserted<br />';
	  			continue;
	  		}
	  		summary_builder::apply_data_combining($definition, $data);
  		  	if($definition['calculate_estimates'] != 'f')
  		  		summary_builder::apply_estimates($db, $definition, $data);
//  		  	summary_builder::dump_data($data);
  		  	summary_builder::do_insert($db, $definition, $year, $taxonID, $locationID, $userID, $data, $periods, $taxon[0]);
	  	  }
	      if(self::$verbose) echo date(DATE_ATOM).' Processing data for Y'.$year.' T'.$taxonID.' L'.$locationID.'<br />';
  		  $query = str_replace(array('#year#', '#survey_id#', '#taxon_id#', '#location_id#', '#attr_id#'),
  		  						 array($year, $definition['survey_id'], $taxonID, $locationID, $definition['occurrence_attribute_id']),
  		  						 $definition['occurrence_attribute_id'] != '' ? $queries['get_YearTaxonLocation_Attr_query'] : $queries['get_YearTaxonLocation_query']);
          $data = array();
          summary_builder::do_delete($db, $definition, $year, $taxonID, $locationID, false);
          if(!summary_builder::load_data($db, $data, $periods, $periodMapping, $query)) {
	  		if(self::$verbose) echo date(DATE_ATOM).' Data cleared, none inserted<br />';
	  		continue;
	  	  }
          summary_builder::apply_data_combining($definition, $data);
  		  if($definition['calculate_estimates'] != 'f')
  		  		summary_builder::apply_estimates($db, $definition, $data);
  		  //summary_builder::dump_data($data);
  		  summary_builder::do_insert($db, $definition, $year, $taxonID, $locationID, false, $data, $periods, $taxon[0]);
	  	}
	  	foreach($YearTaxonUser[$year.':'.$taxonID] as $userID){
	  		if(self::$verbose) echo date(DATE_ATOM).' Processing data for Y'.$year.' T'.$taxonID.' U'.$userID.'<br />';
	  		$query = str_replace(array('#year#', '#survey_id#', '#taxon_id#', '#user_id#'),
	  				array($year, $definition['survey_id'], $taxonID, $userID), $queries['get_YearTaxonUser_query']);
	  		$data = array();
	  		summary_builder::do_delete($db, $definition, $year, $taxonID, false, $userID);
	  		if(!summary_builder::load_summary_data($db, $data, $periods, $periodMapping, $query)) {
	  			if(self::$verbose) echo date(DATE_ATOM).' Data cleared, none inserted<br />';
	  			continue;
	  		}
	  		//summary_builder::dump_data($data);
		  	summary_builder::do_insert($db, $definition, $year, $taxonID, false, $userID, $data, $periods, $taxon[0]);
	  	}
	  	if(self::$verbose) echo date(DATE_ATOM).' Processing data for Y'.$year.' T'.$taxonID.'<br />';
	  	$query = str_replace(array('#year#', '#survey_id#', '#taxon_id#'),
	  				array($year, $definition['survey_id'], $taxonID), $queries['get_YearTaxon_query']);
	  	$data = array();
	  	summary_builder::do_delete($db, $definition, $year, $taxonID, false, false);
	  	if(summary_builder::load_summary_data($db, $data, $periods, $periodMapping, $query)) {
	  	  //summary_builder::dump_data($data);
	  	  summary_builder::do_insert($db, $definition, $year, $taxonID, false, false, $data, $periods, $taxon[0]);
	  	} else  {
	  		if(self::$verbose) echo date(DATE_ATOM).' Data cleared, none inserted<br />';
	  	}
	  }
	  $db->commit();
	}
  	if(self::$verbose) echo date(DATE_ATOM).' End of summarisation for survey ID '.$definition['survey_id'].'<br/>';
  }
  
  private static function load_data(&$db, &$data, &$periods, &$periodMapping, $query) {
  	$present = false;
   	$r = $db->query($query)->result_array(false);
    foreach($periods as $periodNo=>$defn)
    	$data[$periodNo] = array('summary'=>0, 'hasData'=>false, 'hasEstimate'=>false,'samples'=>array());
    foreach($r as $row) {
  	  $datetime1 = new DateTime($row['date_start']);
  	  $offset = $datetime1->format('z');
  	  $period = $periodMapping[$offset];
  	  $data[$period]['hasData'] = true;
  	  if(isset($data[$period]['samples'][$row['sample_id']])) $data[$period]['samples'][$row['sample_id']] += $row['count'];
  	  else $data[$period]['samples'][$row['sample_id']] = $row['count'];
  	  if(!isset($row['present']) || $row['present']=='t')
  	  	$present = true;
    }
    return $present;
  }
  
  private static function load_summary_data(&$db, &$data, &$periods, &$periodMapping, $query) {
  	$r = $db->query($query)->result_array(false);
  	$present = false;
  	foreach($periods as $periodNo=>$defn)
  		$data[$periodNo] = array('summary'=>0, 'hasData'=>false, 'estimate'=>0, 'hasEstimate'=>false,'samples'=>array());
  	foreach($r as $row) {
  		$datetime1 = new DateTime($row['date_start']);
  		$offset = $datetime1->format('z');
  		$period = $periodMapping[$offset];
  		if($row['count']!= null) {
  			$data[$period]['summary'] += $row['count'];
  			$data[$period]['hasData'] = true;
  			$present = true;
  		}
  		if($row['estimate']!= null) {
  			$data[$period]['estimate'] += $row['estimate'];
  			$data[$period]['hasEstimate'] = true;
			$present = true;
 		}
  	}
    return $present;
  }
  private static function dump_data(&$data) {
  	echo "<br/><br/><table><tr>";
  	foreach($data as $period=>$detail) {
  		echo '<td>'.$period.'</td>';
  	}
  	echo '</tr><tr>';
  	foreach($data as $period=>$detail) {
  		echo '<td>'.($detail["hasData"] ? $detail["summary"] : '').'</td>';
  	}
  	echo '</tr><tr>';
  	foreach($data as $period=>$detail) {
  		echo '<td>'.($detail["hasEstimate"] ? $detail["estimate"] : '').'</td>';
  	}
  	echo '</tr></table><br/>';
  }

  private static function apply_data_combining($definition, &$data) {
  	foreach($data as $period=>$detail){
  		switch($definition['data_combination_method']){
  			case 'M':
  				foreach($detail['samples'] as $sampleID=>$value)
  					$data[$period]['summary'] = max($data[$period]['summary'], $value);
  					break;
  					// Not doing an 'S' samples with occurrences
  			case 'L':
  				$val=0;
  				foreach($detail['samples'] as $sampleID=>$value) $val += $value;
  				$cnt = count($detail['samples']);
  				$val = $cnt ? ($val.".0")/$cnt : 0;
  				if($val>0 && $val<1) $val=1;
  				// data rounding only occurs in this option
  				$data[$period]['summary'] = summary_builder::apply_data_rounding($definition, $val, true);
  				break;
  			default :
  			case 'A':
  				foreach($detail['samples'] as $sampleID=>$value)
  					$data[$period]['summary'] += $value;
  				break;
  		}
  	}
  }

  private static function apply_estimates(&$db, $definition, &$data) {
  	$season_start = $definition['season_limits_array']['start'];
  	$season_end = $definition['season_limits_array']['end'];
  	$thisLocation=false;
  	$lastDataPeriod=false;
  	$minPeriod = min(array_keys($data));
  	foreach($data as $period=>$detail) { // we assume this comes out in period order
  		if($detail['hasData']) {
  			$data[$period]['estimate'] = $detail['summary'];
  			$data[$period]['hasEstimate'] = true;
  			// Half value estimate setup if this is the first count. Previous period (which is where the estimate will be) must be within season limits
  			if($lastDataPeriod===false && $definition['first_value']=='H') {
  				$lastDataPeriod = $period-2;
  				$lastDataPeriodValue = 0;
  			}
  			if($lastDataPeriod!==false && ($period-$lastDataPeriod > 1)){
  			  for($j=1; $j < ($period-$lastDataPeriod); $j++){ // fill in periods between data points
  			  	// only consider estimate generation within the season limits.
  			  	if(($season_start===false || ($lastDataPeriod+$j)>=$season_start) && ($season_end===false || ($lastDataPeriod+$j)<=$season_end)) {
  			  	  $estimate = $lastDataPeriodValue+(($j.".0")*($data[$period]['summary']-$lastDataPeriodValue))/($period-$lastDataPeriod);
  			  	  $data[$lastDataPeriod+$j]['estimate'] = summary_builder::apply_data_rounding($definition, $estimate, false);
  			  	  $data[$lastDataPeriod+$j]['hasEstimate'] = true;
  			  	}
  			  }
  			}
  			$lastDataPeriod=$period;
  			$lastDataPeriodValue=$data[$lastDataPeriod]['summary'];
  		}
  	}
  	// Have reached end of data, so do half value estimate setup. Next period (which is where the estimate will be) must be within season limits
  	if($lastDataPeriod && ($season_start===false || $lastDataPeriod+1>=$season_start) && ($season_end===false || $lastDataPeriod+1<=$season_end) && $lastDataPeriod<max(array_keys($data)) && $definition['last_value']=='H') {
  		$data[$lastDataPeriod+1]['estimate'] = summary_builder::apply_data_rounding($definition, $lastDataPeriodValue/2.0, false);
  		$data[$lastDataPeriod+1]['hasEstimate'] = true;
  	} 
  }
  
  private static function apply_data_rounding($definition, $val, $special) {
  	if($special && $val>0 && $val<1) return 1;
  	switch($definition['data_rounding_method']){
  		case 'N':
  			return (int)round($val);
  		case 'U':
  			return (int)ceil($val);
  		case 'D':
  			return (int)floor($val);
  		case 'X':
  		default :
  			break;
  	}
  	return $val;
  }
  
  private static function do_delete(&$db, $definition, $year, $taxonID, $locationID, $userID) {
    // set up a default delete query if none are specified
    $query = "delete from summary_occurrences where year = '".$year."' AND ".
             "survey_id = ".$definition['survey_id']." AND ".
			 "taxa_taxon_list_id = ".$taxonID." AND ".
			 "location_id ".($locationID ? "= ".$locationID : "IS NULL")." AND ".
			 "user_id ".($userID ? "= ".$userID : "IS NULL");
    $count = $db->query($query)->count();
  }

  private static function do_insert(&$db, $definition, $year, $taxonID, $locationID, $userID, &$data, &$periods, $taxon) {
  	// set up a default delete query if none are specified
  	$rows = array();
  	foreach($data as $period=>$details){
  		if($details['hasData']||$details['hasEstimate']){
  			$rows[] = "(".implode(',',array($definition['website_id'], /* website_id integer */
  				$definition['survey_id'], // survey_id integer,
  				$year,
  				$locationID ? $locationID : "NULL", //location_id integer,
  				$userID ? $userID : "NULL", // user_id integer,
  				$period,
				"'".$periods[$period]['date_start']."'", //  date_start date,
  				"'".$periods[$period]['date_end']."'", //  date_end date,
  				"'DD'", // date_type character varying(2),
  				"'".$definition['period_type']."'", // type character varying,
  				$taxonID, // taxa_taxon_list_id integer,
  				$taxon["preferred_taxa_taxon_list_id"], // preferred_taxa_taxon_list_id integer,
  				$taxon["taxonomic_sort_order"]=="" || $taxon["taxonomic_sort_order"]==null ? "NULL" : $taxon["taxonomic_sort_order"], // taxonomic_sort_order bigint,
				"'".str_replace("'","''",$taxon["taxon"])."'", //  taxon character varying,
				"'".str_replace("'","''",$taxon["preferred_taxon"])."'", // preferred_taxon character varying,
				"'".str_replace("'","''",$taxon["default_common_name"])."'", // default_common_name character varying,
				$taxon["taxon_meaning_id"], // taxon_meaning_id integer,
				$taxon["taxon_list_id"], // taxon_list_id integer,
  				$details['hasData'] ? $details['summary'] : "NULL", //  count double precision,
				$details['hasEstimate'] ? $details['estimate'] : "NULL", //  estimate double precision,
  				1, // created_by_id integer,
  				"now()" // summary_created_on timestamp without time zone NOT NULL,
  				)).")";
  		}
  	}
  	if(!count($rows)) return;
  	$query = "insert into summary_occurrences (website_id, 
  				survey_id,
  				year,
  				location_id,
  				user_id,
  				period_number,
				date_start,
  				date_end,
  				date_type,
  				type,
  				taxa_taxon_list_id,
  				preferred_taxa_taxon_list_id,
  				taxonomic_sort_order,
				taxon,
				preferred_taxon,
				default_common_name,
				taxon_meaning_id,
	  			taxon_list_id,
				count,
				estimate,
  				created_by_id,
  				summary_created_on) 
  	         VALUES ".implode(',',$rows);
  	$db->query($query)->count();
  }

}