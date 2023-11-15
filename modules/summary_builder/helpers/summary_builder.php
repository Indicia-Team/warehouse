<?php

/**
 * @file
 * Helper functions for the summary_builder module.
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
 * Helper functions for the summary_builder module.
 */
class summary_builder {

    /**
     * Performs the actual task of table population.
     */
    
    
    /**
     * It is only the presence/absence of a sample that affects the values/estimates, so this is invoked on insert or
     * deletion, not update.
     */
    /** OK **/
    public static function populate_summary_table_for_sample(&$db, $sampleId, $definitionId) {
        // This is the same for an existing sample update, or an insert
        // There is a possibility that if the date on a sample changes to a different year, the the old year values
        // will not be updated - similar if the location is moved. This is however a very remote possibility, and
        // is not possible from the UKBMS front end.
        $queries = kohana::config('summary_builder');
        // this has to be done only once
        self::init_lastval($db);

        $query = str_replace('#definition_id#', $definitionId, $queries['get_definition']);
        $definitionResult = $db->query($query)->result_array(false);
        
        // This returns all taxa on this sample, merged with all taxa on summary records for this location/year
        // Includes a check that the data for the location/year/user/taxon is either not present or was last updated
        // before the sample record update time: if not then has been run in another batch and is up to date
        list($year, $userId, $locationId, $taxaList) = self::get_changelist_sample($db, $sampleId);

        if (count($taxaList) > 0){
            self::do_summary($db, $definitionResult[0], $year, $userId, $locationId, $taxaList);
        }
    }

    /** OK **/
    private static function get_changelist_sample(&$db, $sampleId) {
        $queries = kohana::config('summary_builder');
        $taxa = [];

        $query = str_replace('#sample_id#', $sampleId, $queries['sample_detail_lookup']);
        $sampleResults = $db->query($query)->result_array(false); // returns survey_id, year, created_by_id, location_id
        
        $query = str_replace(array('#website_id#', '#survey_id#', '#sample_id#', '#year#', '#user_id#', '#location_id#'),
            array($sampleResults[0]['website_id'], $sampleResults[0]['survey_id'], $sampleId ,
                  $sampleResults[0]['year'], $sampleResults[0]['user_id'],  $sampleResults[0]['location_id']),
            $queries['sample_occurrence_lookup']);
        $occurrenceResults = $db->query($query)->result_array(false); // returns taxa_taxon_list_id
        foreach($occurrenceResults as $row){
            $taxa[] = $row['taxa_taxon_list_id'];
        }
        
        $query = str_replace(array('#website_id#', '#survey_id#', '#sample_id#', '#year#', '#user_id#', '#location_id#'),
            array($sampleResults[0]['website_id'], $sampleResults[0]['survey_id'], $sampleId ,
                $sampleResults[0]['year'], $sampleResults[0]['user_id'],  $sampleResults[0]['location_id']),
            $queries['sample_existing_taxa']);
        $existingResults = $db->query($query)->result_array(false);
        foreach($existingResults as $row){
            $taxa[] = $row['taxa_taxon_list_id'];
        }
        $taxa = array_unique($taxa);
        return array($sampleResults[0]['year'], $sampleResults[0]['user_id'], $sampleResults[0]['location_id'], $taxa);
    }

    /** OK **/
    public static function populate_summary_table_for_occurrence_insert_delete(&$db, $occurrenceId, $definitionId) {
        // This is the same for an occurrence insert or delete: only affects one taxa
        $queries = kohana::config('summary_builder');
        // this has to be done only once
        self::init_lastval($db);
        
        $query = str_replace('#definition_id#', $definitionId,
            $queries['get_definition']);
        $definitionResult = $db->query($query)->result_array(false);
        
        // This returns just this taxa, following same format as the other methods.
        // Includes a check that the data for the location/year/user/taxon is either not present or was last updated
        // before the sample record update time: if not then has been run in another batch and is up to date
        list($year, $userId, $locationId, $taxa) = self::get_changelist_occurrence_insert_delete($db, $occurrenceId);
        
        if (count($taxa) > 0){
            self::do_summary($db, $definitionResult[0], $year, $userId, $locationId, $taxa);
        }
    }

    /** OK **/
    /** this fills out the data according to a single occurrence insert/delete **/
    private static function get_changelist_occurrence_insert_delete(&$db, $occurrenceId) {
        $queries = kohana::config('summary_builder');
        $taxa = [];
        
        $query = str_replace('#occurrence_id#', $occurrenceId, $queries['occurrence_detail_lookup']);
        $occurrenceResults = $db->query($query)->result_array(false); // returns year, created_by_id, location_id, taxa_taxon_list_id
        if(count($occurrenceResults) === 0)
            return array(0, 0, 0, []);
        return array($occurrenceResults[0]['year'], $occurrenceResults[0]['user_id'],
            $occurrenceResults[0]['location_id'], [$occurrenceResults[0]['taxa_taxon_list_id']]);
    }
    
    
    /** OK **/
    public static function populate_summary_table_for_occurrence_modify(&$db, $occurrenceId, $definitionId) {
        // A modification to an existing taxa may include the changing of the taxa: have to recalculate all taxa
        // on that location/user/year. (e.g. on verification)
        $queries = kohana::config('summary_builder');
        // this has to be done only once
        self::init_lastval($db);
        
        $query = str_replace('#definition_id#', $definitionId,
            $queries['get_definition']);
        $definitionResult = $db->query($query)->result_array(false);
        
        // This returns this taxon, merged with all taxa on summary records for this location/year
        // Includes a check that the data for the location/year/user/taxon is either not present or was last updated
        // before the sample record update time: if not then has been run in another batch and is up to date
        list($year, $userId, $locationId, $taxa) = self::get_changelist_occurrence_modify($db, $occurrenceId);
        
        if (count($taxa) > 0){
            self::do_summary($db, $definitionResult[0], $year, $userId, $locationId, $taxa);
        }
    }

    /** this fills out the data according to a single occurrence modify **/
    private static function get_changelist_occurrence_modify(&$db, $occurrenceId) {
        $queries = kohana::config('summary_builder');
        $taxa = [];
        
        $query = str_replace('#occurrence_id#', $occurrenceId, $queries['sample_detail_lookup_occurrence']);
        $sampleResults = $db->query($query)->result_array(false); // returns survey_id, year, created_by_id, location_id
        if(count($sampleResults) === 0)
            return array(0, 0, 0, []);

        $query = str_replace('#occurrence_id#', $occurrenceId, $queries['occurrence_detail_lookup']);
        $occurrenceResults = $db->query($query)->result_array(false); // returns taxa_taxon_list_id
        foreach($occurrenceResults as $row){
            $taxa[] = $row['taxa_taxon_list_id'];
        }
        
        $query = str_replace(array('#website_id#', '#survey_id#', '#sample_id#', '#year#', '#user_id#', '#location_id#'),
            array($sampleResults[0]['website_id'], $sampleResults[0]['survey_id'], $sampleResults[0]['sample_id'] ,
                $sampleResults[0]['year'], $sampleResults[0]['user_id'],  $sampleResults[0]['location_id']),
            $queries['sample_existing_taxa']);
        $existingResults = $db->query($query)->result_array(false);
        foreach($existingResults as $row){
            $taxa[] = $row['taxa_taxon_list_id'];
        }
        $taxa = array_unique($taxa);
        return array($sampleResults[0]['year'], $sampleResults[0]['user_id'], $sampleResults[0]['location_id'], $taxa);
    }
    

    public static function populate_summary_table_for_location_delete(&$db, $locationId) {
        $queries = kohana::config('summary_builder');
        // this has to be done only once
        self::init_lastval($db);

        $query = str_replace('#location_id#', $locationId, $queries['location_existing_data']);
        $existingResults = $db->query($query)->result_array(false);
        $definitionResult = $db->query($queries['get_all_definitions'])->result_array(false);
        $query = str_replace('#location_id#', $locationId, $queries['delete_location_data']);
        $db->query($query);
        foreach($existingResults as $row){
            foreach($definitionResult as $definition) {
                if($definition['survey_id'] == $row['survey_id']) {
                    self::do_summary($db, $definition, $row['year'], $row['user_id'], $locationId,
                        [$row['taxa_taxon_list_id']]);
                }
            }
        }
    }

    /*
     * Bit of an oddball situation. Our table does not have a sequence. When inserting, The database driver sets
     * the insert_id for the pg_result from the lastval function - with no sequence this returns an error, and the error
     * is ignored from a PHP point of view. However from Postgres POV, this causes a transaction abort/rollback,
     * so we can't wrap a transaction around it. We need a transaction though, so this is a hack to populate
     * the lastval. Use a temporary sequence, as lastval gives an error if the sequence has been dropped.
     */
    /** OK **/
    private static function init_lastval(&$db) {
        $db->query("DROP SEQUENCE IF EXISTS summary_builder_dummy_sequence;");
        $db->query("CREATE TEMPORARY SEQUENCE summary_builder_dummy_sequence;"); // dropped automatically at end of process
        $db->query("SELECT NEXTVAL('summary_builder_dummy_sequence');");
    }

    /** OK **/
    private static function do_summary(&$db, $definition, $year, $userId, $locationId, $taxa) {
        $queries = kohana::config('summary_builder');
        $db->begin();
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
        foreach($taxa as $taxonId) {
            $taxon = $db->query("SELECT * FROM cache_taxa_taxon_lists WHERE id = $taxonId")->result_array(false);
            if(count($taxon)!=1) {
                echo date(DATE_ATOM)." ERROR : Taxon search for id = $taxonId returned wrong number of rows: ".count($taxon)." - expected one row. <br/>";
                continue;
            }
            summary_builder::do_delete($db, $definition, $year, $taxonId, $locationId, $userId);
            summary_builder::do_delete($db, $definition, $year, $taxonId, $locationId, 0);
            summary_builder::do_delete($db, $definition, $year, $taxonId, 0, $userId);
            summary_builder::do_delete($db, $definition, $year, $taxonId, 0, 0);
            
            // This updates the data for the user/taxa/location/year combination.
            $query = str_replace(array('#year#', '#survey_id#', '#taxon_id#', '#location_id#', '#user_id#', '#attr_id#'),
                array($year, $definition['survey_id'], $taxonId, $locationId, $userId, $definition['occurrence_attribute_id']),
                            $definition['occurrence_attribute_id'] != '' ? $queries['get_YearTaxonLocationUser_Attr_query'] : $queries['get_YearTaxonLocationUser_query']);
            $data = array();
            if(summary_builder::load_data($db, $data, $periods, $periodMapping, $query)) {
                summary_builder::apply_data_combining($definition, $data);
                if($definition['calculate_estimates'] != 'f') {
                    summary_builder::apply_estimates($db, $definition, $data);
                }
                summary_builder::do_insert($db, $definition, $year, $taxonId, $locationId, $userId, $data, $periods, $taxon[0]);
            }

            // This updates the data for the allusers/taxa/location/year combination.
            $query = str_replace(array('#year#', '#survey_id#', '#taxon_id#', '#location_id#', '#attr_id#'),
                array($year, $definition['survey_id'], $taxonId, $locationId, $definition['occurrence_attribute_id']),
                        $definition['occurrence_attribute_id'] != '' ? $queries['get_YearTaxonLocation_Attr_query'] : $queries['get_YearTaxonLocation_query']);
            $data = array();
            if(summary_builder::load_data($db, $data, $periods, $periodMapping, $query)) {
                summary_builder::apply_data_combining($definition, $data);
                if($definition['calculate_estimates'] != 'f')
                    summary_builder::apply_estimates($db, $definition, $data);
                    summary_builder::do_insert($db, $definition, $year, $taxonId, $locationId, 0, $data, $periods, $taxon[0]);
            }

            // This run updates the data for the user/taxa/location/year combination.
            // Then ads this all together to find the value for the user/taxa/alllocations/year combination
            // This run updates the data for the allusers/taxa/location/year combination.
            // Then ads this all together to find the value for the allusers/taxa/alllocations/year combination
            // In this cycle the year is fixed, the taxa is either one or an array, the location is fixed, the user is fixed.
            $query = str_replace(array('#year#', '#survey_id#', '#taxon_id#', '#user_id#'),
                array($year, $definition['survey_id'], $taxonId, $userId), $queries['get_YearTaxonUser_query']);
            $data = array();
            if(summary_builder::load_summary_data($db, $data, $periods, $periodMapping, $query)) {
                summary_builder::do_insert($db, $definition, $year, $taxonId, 0, $userId, $data, $periods, $taxon[0]);
            }
            // This run updates the data for the user/taxa/location/year combination.
            // Then ads this all together to find the value for the user/taxa/alllocations/year combination
            // This run updates the data for the allusers/taxa/location/year combination.
            // Then ads this all together to find the value for the allusers/taxa/alllocations/year combination
            // In this cycle the year is fixed, the taxa is either one or an array, the location is fixed, the user is fixed.
            
            $query = str_replace(array('#year#', '#survey_id#', '#taxon_id#'),
                        array($year, $definition['survey_id'], $taxonId), $queries['get_YearTaxon_query']);
            $data = array();
            if(summary_builder::load_summary_data($db, $data, $periods, $periodMapping, $query)) {
                summary_builder::do_insert($db, $definition, $year, $taxonId, 0, 0, $data, $periods, $taxon[0]);
            }
        }
        $db->commit();
    }
    
    /** OK **/
    private static function load_data(&$db, &$data, &$periods, &$periodMapping, $query) {
        $present = false;
        $r = $db->query($query)->result_array(false);
        foreach($periods as $periodNo=>$defn) {
            $data[$periodNo] = array('summary'=>0, 'hasData'=>false, 'hasEstimate'=>false,'samples'=>array());
        }
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
    
    /** OK **/
    private static function load_summary_data(&$db, &$data, &$periods, &$periodMapping, $query) {
        $results = $db->query($query)->result_array(false);
        $present = false;
        foreach($periods as $periodNo=>$defn) {
            $data[$periodNo] = array('summary'=>0, 'hasData'=>false, 'estimate'=>0, 'hasEstimate'=>false,'samples'=>array());
        }
        foreach($results as $row) {
            $summary=json_decode($row['summarised_data'], true);
            foreach($summary as $period) {
              if($period['summary'] !== null && $period['summary'] !== 'NULL') {
                  $data[$period['period']]['summary'] += $period['summary'];
                  $data[$period['period']]['hasData'] = true;
                  $present = true;
              }
              if($period['estimate'] !== null && $period['estimate'] !== 'NULL') {
                  $data[$period['period']]['estimate'] += $period['estimate'];
                  $data[$period['period']]['hasEstimate'] = true;
                  $present = true;
              }
            }
        }
        return $present;
    }
    
    /** OK **/
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
    
    /** OK **/
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
    
    /** OK **/
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
    
    /** OK **/
    private static function do_delete(&$db, $definition, $year, $taxonId, $locationId, $userId) {
        // set up a default delete query if none are specified
        $query = "delete from summary_occurrences where year = '".$year."' AND ".
            "survey_id = ".$definition['survey_id']." AND ".
            "taxa_taxon_list_id = ".$taxonId." AND ".
            "location_id = ".$locationId." AND ".
            "user_id = ".$userId.";";
        $count = $db->query($query)->count();
    }
    
    /** OK **/
    private static function do_insert(&$db, $definition, $year, $taxonId, $locationId, $userId, &$data, &$periods, $taxon) {
        $summary = array();
        foreach($data as $period=>$details){
            if($details['hasData']||$details['hasEstimate']){
                $summary[] = array(
                    'period' => $period,
                    'date_start' => $periods[$period]['date_start'],
                    'date_end' => $periods[$period]['date_end'],
                    'summary' => $details['hasData'] ? $details['summary'] : "NULL", 
                    'estimate' => $details['hasEstimate'] ? $details['estimate'] : "NULL",
                    );
            }
        }
        if(!count($summary)) return;
        
        $query = "insert into summary_occurrences (
          website_id,
          survey_id,
          year,
          location_id,
          user_id,
          type,
          taxa_taxon_list_id,
          preferred_taxa_taxon_list_id,
          taxonomic_sort_order,
          taxon,
          preferred_taxon,
          default_common_name,
          taxon_meaning_id,
          taxon_list_id,
          summarised_data,
          created_by_id,
          summary_created_on)
         VALUES (
          " . $definition['website_id'] . ",
          " . $definition['survey_id'] . ",
          " . $year . ",
          " . $locationId . ",
          " . $userId . ",
          '" . $definition['period_type'] . "',
          " . $taxonId . ",
          " . $taxon["preferred_taxa_taxon_list_id"] . ",
          " . ($taxon["taxonomic_sort_order"] ?? "NULL") . ",
          '" . str_replace("'", "''", ($taxon["taxon"] ?? '')) . "',
          '" . str_replace("'", "''", ($taxon["preferred_taxon"] ?? '')) . "',
          '" . str_replace("'", "''", ($taxon["default_common_name"] ?? '')) . "',
          " . $taxon["taxon_meaning_id"] . ",
          " . $taxon["taxon_list_id"] . ",
          '" . json_encode($summary) . "'::json,
          1,
          now()
        );";
        $db->query($query);
    }

//            throw new exception('Configured survey restriction incorrect in spatial index builder');

    
    
  /**
   * A utility function used by the work queue task helpers.
   *
   * Returns the filter SQL to limit indexed locations to the correct types as
   * declared in the configuration, with survey limits where appropriate.
   *
   * @param object $db
   *   Database connection object.
   *
   * @return string
   *   SQL filter clause.
   */

}
