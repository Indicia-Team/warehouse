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
 * @subpackage Auto verify
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	https://github.com/Indicia-Team/warehouse
 */

/**
 * Hook into the task scheduler. When run, the system checks the cache_occurrences_functional table for records where the data cleaner has marked
 * the record as data_cleaner_info "pass", record_status="C", the system then sets the record to verified automatically.
 * @param string $last_run_date Date last run, or null if never run
 * @param object $db Database object.
 * @todo Config for $autoVerifyNullIdDiff should be a boolean, not a string
 * @todo Config for $processOldData should be a boolean, not a string
 */
function auto_verify_scheduled_task($last_run_date, $db) {
  $autoVerifyNullIdDiff=kohana::config('auto_verify.auto_accept_occurrences_with_null_id_difficulty');
  $processOldData=kohana::config('auto_verify.process_old_data');
  if (empty($autoVerifyNullIdDiff)) {
    echo "Unable to automatically verify occurrences when the auto_accept_occurrences_with_null_id_difficulty entry is empty.<br>";
    kohana::log('error', 'Unable to automatically verify occurrences when the auto_accept_occurrences_with_null_id_difficulty configuration entry is empty.');
    return false;
  }
  //Do we need to consider old data (probably as a one-off run) or just newly changed data.
  $subQuery="
    SELECT delta.id";
  if (!empty($processOldData)&&$processOldData==='true') { 
    $subQuery.="  
      FROM cache_occurrences_functional delta";
  } else {
    $subQuery.="  
      FROM occdelta delta";
  }
  $subQuery.="
    JOIN surveys s on s.id = delta.survey_id AND s.auto_accept=true AND s.deleted=false
    LEFT JOIN cache_taxon_searchterms cts on cts.taxon_meaning_id = delta.taxon_meaning_id
    WHERE delta.data_cleaner_result=true
    AND delta.record_status='C' AND delta.record_substatus IS NULL
        AND ((".$autoVerifyNullIdDiff."=false AND cts.identification_difficulty IS NOT NULL AND cts.identification_difficulty<=s.auto_accept_max_difficulty) 
        OR (".$autoVerifyNullIdDiff."=true AND (cts.identification_difficulty IS NULL OR cts.identification_difficulty<=s.auto_accept_max_difficulty)))";
  $verificationTime=gmdate("Y\/m\/d H:i:s");
  //Need to update cache_occurrences_*, as these tables have already been built at this point.
  $query = "
    INSERT INTO occurrence_comments (comment, generated_by, occurrence_id,record_status,record_substatus,created_by_id,updated_by_id,created_on,updated_on,auto_generated)
    SELECT 'Accepted based on automatic checks', 'system', id,'V','2',1,1,'".$verificationTime."','".$verificationTime."',true
    FROM occurrences
    WHERE id in
    (".$subQuery.");
      
    UPDATE occurrences
    SET 
    record_status='V',
    record_substatus='2',
    release_status='R',
    verified_by_id=1,
    verified_on='".$verificationTime."',
    record_decision_source='M'
    WHERE id in
    (".$subQuery.");
      
    UPDATE cache_occurrences_functional
    SET 
    record_status='V',
    record_substatus='2',
    release_status='R',
    verified_on='".$verificationTime."'
    WHERE id in
    (".$subQuery.");

    UPDATE cache_occurrences_nonfunctional
    SET verifier='admin, core'
    WHERE id in
    (".$subQuery.");";
  $results=$db->query($query)->result_array(false);
  //Query to return count of records, as I was unable to pursuade the above query to output the number of updated
  //records correctly.
  $query = "
    SELECT count(id)
    FROM cache_occurrences_functional co
    WHERE co.verified_on='".$verificationTime."';";
  $results=$db->query($query)->result_array(false);
  if (!empty($results[0]['count']) && $results[0]['count']>1)
    echo $results[0]['count'].' occurrence records have been automatically verified.</br>';
  elseif (!empty($results[0]['count']) && $results[0]['count']==="1")
    echo '1 occurrence record has been automatically verified.</br>';
  else
    echo 'No occurrence records have been auto-verified.</br>';
}

/*
 * Tell the system that we need the occdelta table to find out which occurrences have been created/changed recently.
 */
function auto_verify_metadata() {
  return array(
    'requires_occurrences_delta'=>TRUE
  );
}