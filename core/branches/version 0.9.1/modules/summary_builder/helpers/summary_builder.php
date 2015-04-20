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
	
  public static function populate_summary_table($db, $last_run_date, $rebuild) {
  	$queries = kohana::config('summary_builder');
  	$r = $db->query($queries['select_definitions'])->result_array(false);
  	if(count($r)){
  		foreach($r as $row){
  			if($rebuild === true || $rebuild == $row['survey_id']) {
  				echo 'Rebuilding summary data for survey ID '.$row['survey_id'].'<br/>';
  				$query = "delete from summary_occurrences where ".
  						"survey_id = ".$row['survey_id'];
	  			$count = $db->query($query)->count();
  				echo 'Removed '.$count.' records. Rebuild will commence on next invocation of scheduled tasks.<br/>';
	  		} else {
  				echo 'Processing summariser_definition ID '.$row['id'].' for survey ID '.$row['survey_id'].'<br/>';
  				self::populate_summary_table_for_survey($db, $last_run_date, $row);
  			}
  		}
  	} else {
  		echo 'No summariser_definitions to be processed.';
  	}
  }

  private static function populate_summary_table_for_survey($db, $last_run_date, $definition) {
  	$YearTaxonLocationUser = array();
  	$YearTaxonLocation = array();
  	$YearTaxonUser = array();
  	$YearTaxon = array();
    try {
      $count=summary_builder::get_changelist($db, $last_run_date, $definition, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon);
	  if($count>0)
	  	summary_builder::do_summary($db, $definition, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon);
    } catch (Exception $e) {
      error::log_error('Building summary', $e);
      echo $e->getMessage();
    }
   
  	return;
  }
  
  private static function get_changelist($db, $last_run_date, $definition, &$YearTaxonLocationUser, &$YearTaxonLocation, &$YearTaxonUser, &$YearTaxon) {
  	$queries = kohana::config('summary_builder');
  	$limit = 1000;
  	$query = str_replace(array('#date#','#survey_id#','#limit#'),
  						 array($last_run_date,$definition['survey_id'],$limit),
  						 $queries['get_changed_items_query']);
  	 
  	$r = $db->query($query)->result_array(false);
  	$count = count($r);
  	if($count){
  		echo $count.' new/altered occurrences to be processed.<br/>';
  		foreach($r as $row){
 			$year = substr($row['date_start'], 0, 4);
  			// create list of year/taxon  
 			if(!isset($YearTaxon[$year])) $YearTaxon[$year]=array();
 			if(!in_array($row['taxa_taxon_list_id'], $YearTaxon[$year])) $YearTaxon[$year][] = $row['taxa_taxon_list_id'];
 			// create list of year/taxon/user: TBC user is determined by the creator of the parent sample.
 			if(!isset($YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']])) $YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']] = array();
 			if(!in_array($row['created_by_id'], $YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']])) $YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']][] = $row['created_by_id'];
  			// create list of year/taxon/location
 			if(!isset($YearTaxonLocation[$year.':'.$row['taxa_taxon_list_id']])) $YearTaxonLocation[$year.':'.$row['taxa_taxon_list_id']] = array();
 			if(!in_array($row['location_id'], $YearTaxonLocation[$year.':'.$row['taxa_taxon_list_id']])) $YearTaxonLocation[$year.':'.$row['taxa_taxon_list_id']][] = $row['location_id'];
  			// create list of year/taxon/location/user
 			if(!isset($YearTaxonLocationUser[$year.':'.$row['taxa_taxon_list_id'].':'.$row['location_id']])) $YearTaxonLocationUser[$year.':'.$row['taxa_taxon_list_id'].':'.$row['location_id']] = array();
 			if(!in_array($row['created_by_id'], $YearTaxonLocationUser[$year.':'.$row['taxa_taxon_list_id'].':'.$row['location_id']])) $YearTaxonLocationUser[$year.':'.$row['taxa_taxon_list_id'].':'.$row['location_id']][] = $row['created_by_id'];
  		}
  		$limit = $limit-$count;
   	} else {
  		echo 'No new/altered occurrences to be processed.<br/>';
  	}
  	// Now check for any missed data.
  	$query = str_replace(array('#date#','#survey_id#','#limit#'),
  						 array($last_run_date,$definition['survey_id'],$limit),
  						 $queries['get_missed_items_query']);
  	$r = $db->query($query)->result_array(false);
  	if(count($r)){
  		echo count($r).' missed occurrences to be processed.<br/>';
  		foreach($r as $row){
 			$year = substr($row['date_start'], 0, 4);
  			// create list of year/taxon  
 			if(!isset($YearTaxon[$year])) $YearTaxon[$year]=array();
 			if(!in_array($row['taxa_taxon_list_id'], $YearTaxon[$year])) $YearTaxon[$year][] = $row['taxa_taxon_list_id'];
 			// create list of year/taxon/user: TBC user is determined by the creator of the parent sample.
 			if(!isset($YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']])) $YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']] = array();
 			if(!in_array($row['created_by_id'], $YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']])) $YearTaxonUser[$year.':'.$row['taxa_taxon_list_id']][] = $row['created_by_id'];
  			// create list of year/taxon/location
 			if(!isset($YearTaxonLocation[$year.':'.$row['taxa_taxon_list_id']])) $YearTaxonLocation[$year.':'.$row['taxa_taxon_list_id']] = array();
 			if(!in_array($row['location_id'], $YearTaxonLocation[$year.':'.$row['taxa_taxon_list_id']])) $YearTaxonLocation[$year.':'.$row['taxa_taxon_list_id']][] = $row['location_id'];
  			// create list of year/taxon/location/user
 			if(!isset($YearTaxonLocationUser[$year.':'.$row['taxa_taxon_list_id'].':'.$row['location_id']])) $YearTaxonLocationUser[$year.':'.$row['taxa_taxon_list_id'].':'.$row['location_id']] = array();
 			if(!in_array($row['created_by_id'], $YearTaxonLocationUser[$year.':'.$row['taxa_taxon_list_id'].':'.$row['location_id']])) $YearTaxonLocationUser[$year.':'.$row['taxa_taxon_list_id'].':'.$row['location_id']][] = $row['created_by_id'];
  		}
  		echo 'Last missed occurrence processed has id '.$r[count($r)-1]['id'].'<br/>';
  	} else
  		echo 'No missed occurrences to be processed.<br/>';
  	if(count($YearTaxon)>0){
  		ksort($YearTaxon);
  		ksort($YearTaxonUser);
  		ksort($YearTaxonLocation);
  	  	ksort($YearTaxonLocationUser);
 	}
  	return count($YearTaxon);
  }

  private static function do_summary($db, $definition, $YearTaxonLocationUser, $YearTaxonLocation, $YearTaxonUser, $YearTaxon) {
  	$queries = kohana::config('summary_builder');
	foreach($YearTaxon as $year=>$taxonList) {
	  echo "Processing data for ".$year.".<br />";
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
	  		echo "Weekstart month-day combination unrecognised {".$weekstart[1]."}<br />";
	  		return;
	  	  }
	  	  $weekstart[1]=$weekstart_date->format('N'); // ISO Day of week - Mon=1, Sun=7
	  	}
	  	if(intval($weekstart[1])!=$weekstart[1] || $weekstart[1]<1 || $weekstart[1]>7) {
	  	  echo "Weekstart unrecognised or out of range {".$weekstart[1]."}<br />";
	  	  return;
	  	}
	  	$consider_date = new DateTime($year.'-01-01');
	  	$weekNoOffset=0;
	  	while($consider_date->format('N')!=$weekstart[1]) $consider_date->modify('-1 day');
	  	$weekOne_date = date_create($year.'-'.$definition['period_one_contains']);
	  	if(!$weekOne_date){
	      echo "Week one month-day combination unrecognised {".$definition['period_one_contains']."}<br />";
	      return;
	  	}
	  	while($weekOne_date->format('N')!=$weekstart[1]) $weekOne_date->modify('-1 day'); // scan back to start of week
	  	while($weekOne_date > $consider_date){
	  	  $weekOne_date->modify('-7 days');
	  	  $weekNoOffset++;
	  	}
	  	$anchors=explode(',',$definition['season_limits']);
	  	$definition['anchors'] = array((count($anchors) && $anchors[0]!='') ? $anchors[0] : false,
	  									(count($anchors)>1 && $anchors[1]!='') ? $anchors[1] : false);
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
	  	echo "period_type unrecognised {".$definition['period_type']."}<br />";
	  	return;
	  }
	  foreach($taxonList as $taxonID) {
 	  		$taxon = $db->query("SELECT * FROM cache_taxa_taxon_lists WHERE id = $taxonID")->result_array(false);
	  		if(count($taxon)!=1) {
	  			echo "Error : Taxon search for id = $taxonID returned wrong number of rows: ".count($taxon)." - expected one row. <br/>";
	  			continue;
	  		}
	  	foreach($YearTaxonLocation[$year.':'.$taxonID] as $locationID) {
	  	  foreach($YearTaxonLocationUser[$year.':'.$taxonID.':'.$locationID] as $userID){
  		  	$query = str_replace(array('#year#', '#survey_id#', '#taxon_id#', '#location_id#', '#user_id#', '#attr_id#'),
  		  						 array($year, $definition['survey_id'], $taxonID, $locationID, $userID, $definition['occurrence_attribute_id']),
  		  						 $definition['occurrence_attribute_id'] != '' ? $queries['get_YearTaxonLocationUser_Attr_query'] : $queries['get_YearTaxonLocationUser_query']);
            $data = array();
  		  	summary_builder::do_delete($db, $definition, $year, $taxonID, $locationID, $userID);
            summary_builder::load_data($db, $data, $periods, $periodMapping, $query);
  			summary_builder::apply_data_combining($definition, $data);
  		  	if($definition['calculate_estimates'])
  		  		summary_builder::apply_estimates($db, $definition, $data);
  		  	//summary_builder::dump_data($data);
  		  	summary_builder::do_insert($db, $definition, $year, $taxonID, $locationID, $userID, $data, $periods, $taxon[0]);
  		  }
  		  $query = str_replace(array('#year#', '#survey_id#', '#taxon_id#', '#location_id#', '#attr_id#'),
  		  						 array($year, $definition['survey_id'], $taxonID, $locationID, $definition['occurrence_attribute_id']),
  		  						 $definition['occurrence_attribute_id'] != '' ? $queries['get_YearTaxonLocation_Attr_query'] : $queries['get_YearTaxonLocation_query']);
          $data = array();
  		  summary_builder::do_delete($db, $definition, $year, $taxonID, $locationID, false);
          summary_builder::load_data($db, $data, $periods, $periodMapping, $query);
  		  summary_builder::apply_data_combining($definition, $data);
  		  if($definition['calculate_estimates'])
  		  		summary_builder::apply_estimates($db, $definition, $data);
  		  //summary_builder::dump_data($data);
  		  summary_builder::do_insert($db, $definition, $year, $taxonID, $locationID, false, $data, $periods, $taxon[0]);
	  	}
	  	foreach($YearTaxonUser[$year.':'.$taxonID] as $userID){
	  		$query = str_replace(array('#year#', '#survey_id#', '#taxon_id#', '#user_id#'),
	  				array($year, $definition['survey_id'], $taxonID, $userID), $queries['get_YearTaxonUser_query']);
	  		$data = array();
	  		summary_builder::do_delete($db, $definition, $year, $taxonID, false, $userID);
	  		summary_builder::load_summary_data($db, $data, $periods, $periodMapping, $query);
	  		//summary_builder::dump_data($data);
	  		summary_builder::do_insert($db, $definition, $year, $taxonID, false, $userID, $data, $periods, $taxon[0]);
	  	}
	  	$query = str_replace(array('#year#', '#survey_id#', '#taxon_id#'),
	  				array($year, $definition['survey_id'], $taxonID), $queries['get_YearTaxon_query']);
	  	$data = array();
	  	summary_builder::do_delete($db, $definition, $year, $taxonID, false, false);
	  	summary_builder::load_summary_data($db, $data, $periods, $periodMapping, $query);
	  	//summary_builder::dump_data($data);
	  	summary_builder::do_insert($db, $definition, $year, $taxonID, false, false, $data, $periods, $taxon[0]);
	  }
	}
  }
  
  private static function load_data($db, &$data, &$periods, &$periodMapping, $query) {
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
    }
  }
  
  private static function load_summary_data($db, &$data, &$periods, &$periodMapping, $query) {
   	$r = $db->query($query)->result_array(false);
  	foreach($periods as $periodNo=>$defn)
  		$data[$periodNo] = array('summary'=>0, 'hasData'=>false, 'estimate'=>0, 'hasEstimate'=>false,'samples'=>array());
  	foreach($r as $row) {
  		$datetime1 = new DateTime($row['date_start']);
  		$offset = $datetime1->format('z');
  		$period = $periodMapping[$offset];
  		if($row['count']!= null) {
  			$data[$period]['summary'] += $row['count'];
  			$data[$period]['hasData'] = true;
  		}
  		if($row['estimate']!= null) {
  			$data[$period]['estimate'] += $row['estimate'];
  			$data[$period]['hasEstimate'] = true;
  		}
  	}
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
  				$data[$period]['summary'] = summary_builder::apply_data_rounding($definition, $val);
  				break;
  			default :
  			case 'A':
  				foreach($detail['samples'] as $sampleID=>$value)
  					$data[$period]['summary'] += $value;
  				break;
  		}
  	}
  }

  private static function apply_estimates($db, $definition, &$data) {
  	$firstAnchor = $definition['anchors'][0];
  	$lastAnchor = $definition['anchors'][1];
  	$thisLocation=false;
  	$lastDataPeriod=false;
  	foreach($data as $period=>$detail) {
  		if($detail['hasData']) {
  			$data[$period]['estimate'] = $detail['summary'];
  			$data[$period]['hasEstimate'] = true;
  			if($lastDataPeriod===false && ($firstAnchor===false || $period-1>$firstAnchor) && ($lastAnchor===false || $period-1<$lastAnchor) && $definition['first_value']=='H') {
  				$lastDataPeriod = $period-2;
  				$lastDataPeriodValue = 0;
  			}
  			if($lastDataPeriod!==false && ($period-$lastDataPeriod > 1)){
  			  for($j=1; $j < ($period-$lastDataPeriod); $j++){ // fill in periods between data points
  			  	$estimate = $data[$lastDataPeriod]['summary']+(($j.".0")*($data[$period]['summary']-$lastDataPeriodValue))/($period-$lastDataPeriod);
  			  	$data[$lastDataPeriod+$j]['estimate'] = summary_builder::apply_data_rounding($definition, $estimate);
  			  	$data[$lastDataPeriod+$j]['hasEstimate'] = true;
  			  }
  			}
  			$lastDataPeriod=$period;
  			$lastDataPeriodValue=$data[$lastDataPeriod]['summary'];
  		}
  	}
  	if($lastDataPeriod && ($firstAnchor===false || $lastDataPeriod>=$firstAnchor) && ($lastAnchor===false || $lastDataPeriod-1<$lastAnchor) && $lastDataPeriod<count($data) && $definition['last_value']=='H') {
  		$data[$lastDataPeriod+1]['estimate'] = summary_builder::apply_data_rounding($definition, $data[$lastDataPeriod]['summary']/2.0);
  		$data[$lastDataPeriod+1]['hasEstimate'] = true;
  	} 
  }
  
  private static function apply_data_rounding($definition, $val) {
  	if($val>0 && $val<1) $val=1;
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
  
  private static function do_delete($db, $definition, $year, $taxonID, $locationID, $userID) {
    // set up a default delete query if none are specified
    $query = "delete from summary_occurrences where year = '".$year."' AND ".
             "survey_id = ".$definition['survey_id']." AND ".
			 "taxa_taxon_list_id = ".$taxonID." AND ".
			 "location_id ".($locationID ? "= ".$locationID : "IS NULL")." AND ".
			 "user_id ".($userID ? "= ".$userID : "IS NULL");
    $count = $db->query($query)->count();
  }

  private static function do_insert($db, $definition, $year, $taxonID, $locationID, $userID, &$data, &$periods, $taxon) {
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