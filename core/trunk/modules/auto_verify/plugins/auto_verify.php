<?php

function auto_verify_extend_data_services() {
  return array(
    'auto_verify'=>array('allow_full_access'=>true)
  );
}

/**
 * Hook into the task scheduler. When run, the system checks the cache_occurrences table for records where the data cleaner has marked
 * the record as data_cleaner_info "pass", record_status="C", the system then sets the record to verified automatically.
 * @param string $last_run_date Date last run, or null if never run
 * @param object $db Database object.
 */
function auto_verify_scheduled_task($last_run_date, $db) {
  $autoVerifyNullIdDiff=kohana::config('auto_verify.auto_accept_occurrences_with_null_id_difficulty');
  if (empty($autoVerifyNullIdDiff)) {
    print_r("Unable to automatically verify occurrences when the auto_accept_occurrences_with_null_id_difficulty entry is empty.<br>");
    kohana::log('error', 'Unable to automatically verify occurrences when the auto_accept_occurrences_with_null_id_difficulty configuration entry is empty.');
    return false;
  }
 
  //occDelta doesn't have the data_cleaner_info filled in, so join to cache_occurrences
  $subQuery="
    SELECT od.id
    FROM occdelta od
    JOIN cache_occurrences co on co.id=od.id
    JOIN surveys s on s.id = co.survey_id AND s.auto_accept=true AND s.deleted=false
    JOIN cache_taxon_searchterms cts on cts.taxa_taxon_list_id = co.taxa_taxon_list_id 
      AND ((".$autoVerifyNullIdDiff."=false AND cts.identification_difficulty IS NOT NULL AND cts.identification_difficulty<=s.auto_accept_max_difficulty) 
      OR (".$autoVerifyNullIdDiff."=true AND (cts.identification_difficulty IS NULL OR cts.identification_difficulty<=s.auto_accept_max_difficulty))) 
    WHERE co.data_cleaner_info='pass' AND co.record_status='C'";
  $verificationTime=gmdate("Y\/m\/d H:i:s");
  //Need to update cache_occurrences, as this table has already been built at this point.
  $query = "
    INSERT INTO occurrence_comments (comment, generated_by, occurrence_id,record_status,record_substatus,created_by_id,updated_by_id,created_on,updated_on,auto_generated)
    SELECT 'Automatically accepted', 'system', id,'V','2',1,1,'".$verificationTime."','".$verificationTime."',true
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
      
    UPDATE cache_occurrences
    SET 
    record_status='V',
    record_substatus='2',
    release_status='R',
    verified_on='".$verificationTime."',
    verifier='system'
    WHERE id in
    (".$subQuery.");";
  $results=$db->query($query)->result_array(false);
  //Query to return count of records, as I was unable to pursuade the above query to output the number of updated
  //records correctly.
  $query = "
    SELECT count(id)
    FROM cache_occurrences co
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
?> 
 