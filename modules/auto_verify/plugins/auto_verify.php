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
 * Hook into the task scheduler. When run, the system checks the
 * cache_occurrences_functional table for records where the data cleaner has
 * marked the record as data_cleaner_result to true, record_status="C", the system
 * then sets the record to verified automatically (subject to taxon restriction
 * tests descrbed below).
 * If the survey associated with the record has a value in its 
 * auto_accept_taxa_filters field, then the taxon_meaning_id associated with 
 * the record has to be the same as, or a decendent of, one of the
 * taxa stored in the auto_accept_taxa_filters to qualify for auto verification.
 *
 * @param string $last_run_date
 *   Date last run, or null if never run.
 * @param object $db
 *   Database object.
 *
 * @todo Config for $autoVerifyNullIdDiff should be a boolean, not a string
 */
function auto_verify_scheduled_task($last_run_date, $db) {
  $autoVerifyNullIdDiff = kohana::config('auto_verify.auto_accept_occurrences_with_null_id_difficulty', FALSE, FALSE);

  $oldestRecordCreatedDateToProcess = kohana::config('auto_verify.oldest_record_created_date_to_process', FALSE, FALSE);
  $oldestOccurrenceIdToProcess = kohana::config('auto_verify.oldest_occurrence_id_to_process', FALSE, FALSE);
  $maxRecordsNumber = kohana::config('auto_verify.max_num_records_to_process_at_once', FALSE, FALSE);

  if (empty($autoVerifyNullIdDiff)) {
    echo "Unable to automatically verify occurrences when the auto_accept_occurrences_with_null_id_difficulty entry is empty.<br>";
    kohana::log('error', 'Unable to automatically verify occurrences when the auto_accept_occurrences_with_null_id_difficulty configuration entry is empty.');
    return FALSE;
  }

  if (empty($oldestRecordCreatedDateToProcess)) {
    echo "Unable to automatically verify occurrences when the oldest_record_created_date_to_process entry is empty.<br>";
    kohana::log('error', 'Unable to automatically verify occurrences when the oldest_record_created_date_to_process configuration entry is empty.');
    return FALSE;
  }

  if (empty($oldestOccurrenceIdToProcess)) {
    echo "Unable to automatically verify occurrences when the oldest_occurrence_id_to_process entry is empty.<br>";
    kohana::log('error', 'Unable to automatically verify occurrences when the oldest_occurrence_id_to_process configuration entry is empty.');
    return FALSE;
  }

  if (empty($maxRecordsNumber)) {
    echo "Unable to automatically verify occurrences when the max_num_records_to_process_at_once entry is empty.<br>";
    kohana::log('error', 'Unable to automatically verify occurrences when the max_num_records_to_process_at_once configuration entry is empty.');
    return FALSE;
  }
  
  $subQuery = "
    SELECT distinct delta.id
    FROM cache_occurrences_functional delta
    JOIN surveys s on s.id = delta.survey_id AND s.auto_accept=true AND s.deleted=false
    LEFT JOIN cache_taxon_searchterms cts on cts.taxon_meaning_id = delta.taxon_meaning_id
    WHERE delta.data_cleaner_result=true
    AND delta.record_status='C' AND delta.record_substatus IS NULL
        AND delta.created_on >= TO_TIMESTAMP('$oldestRecordCreatedDateToProcess', 'DD/MM/YYYY')
        AND (($autoVerifyNullIdDiff=false AND cts.identification_difficulty IS NOT NULL AND cts.identification_difficulty<=s.auto_accept_max_difficulty)
        OR ($autoVerifyNullIdDiff=true AND (cts.identification_difficulty IS NULL OR cts.identification_difficulty<=s.auto_accept_max_difficulty)))
    AND (s.auto_accept_taxa_filters is null
      OR delta.taxon_meaning_id = ANY (s.auto_accept_taxa_filters)
      OR (s.auto_accept_taxa_filters && delta.taxon_path))";

  if (isset($oldestOccurrenceIdToProcess) && $oldestOccurrenceIdToProcess > -1) {
    $subQuery .= "
      AND delta.id >= $oldestOccurrenceIdToProcess";
  }

  if (isset($maxRecordsNumber) && $maxRecordsNumber > -1) {
    $subQuery .= "
      order by delta.id desc limit $maxRecordsNumber";
  }

  $verificationTime = gmdate("Y\/m\/d H:i:s");
  //Need to update cache_occurrences_*, as these tables have already been built at this point.
  $query = "
    INSERT INTO occurrence_comments (comment, generated_by, occurrence_id,record_status,record_substatus,created_by_id,updated_by_id,created_on,updated_on,auto_generated)
    SELECT 'Accepted based on automatic checks', 'system', id,'V','2',1,1,'$verificationTime','$verificationTime',true
    FROM occurrences
    WHERE id in
    ($subQuery);

    UPDATE occurrences
    SET
    record_status='V',
    record_substatus='2',
    release_status='R',
    verified_by_id=1,
    verified_on='$verificationTime',
    record_decision_source='M',
    updated_on = now(),
    updated_by_id = 1
    WHERE id in
    ($subQuery);

    UPDATE cache_occurrences_functional
    SET
    record_status='V',
    record_substatus='2',
    release_status='R',
    verified_on='$verificationTime'
    WHERE id in
    ($subQuery);

    UPDATE cache_occurrences_nonfunctional
    SET verifier='admin, core'
    WHERE id in
    ($subQuery);";
  $results=$db->query($query)->result_array(FALSE);
  // Query to return count of records, as I was unable to pursuade the above
  // query to output the number of updated records correctly.
  $query = "
    SELECT count(id)
    FROM cache_occurrences_functional co
    WHERE co.verified_on='$verificationTime';";
  $results = $db->query($query)->result_array(FALSE);
  if (!empty($results[0]['count']) && $results[0]['count'] > 1) {
    echo $results[0]['count'] . ' occurrence records have been automatically verified.</br>';
  }
  elseif (!empty($results[0]['count']) && $results[0]['count'] === "1")
    echo '1 occurrence record has been automatically verified.</br>';
  else
    echo 'No occurrence records have been auto-verified.</br>';
}
