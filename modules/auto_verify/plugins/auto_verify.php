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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/warehouse
 */

/**
 * Returns plugin metadata.
 *
 * Identifies that the plugin uses the occdelta table to identify changes.
 *
 * @return array
 *   Metadata.
 */
function auto_verify_metadata() {
  return array(
    'requires_occurrences_delta' => TRUE,
    'always_run' => TRUE,
  );
}

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
  if (empty($autoVerifyNullIdDiff)) {
    echo "Unable to automatically verify occurrences when the auto_accept_occurrences_with_null_id_difficulty entry is empty.<br>";
    kohana::log('error', 'Unable to automatically verify occurrences when the auto_accept_occurrences_with_null_id_difficulty configuration entry is empty.');
    return FALSE;
  }

  $oldestRecordCreatedDateToProcess = kohana::config('auto_verify.oldest_record_created_date_to_process', FALSE, FALSE);
  if (empty($oldestRecordCreatedDateToProcess)) {
    echo "Unable to automatically verify occurrences when the oldest_record_created_date_to_process entry is empty.<br>";
    kohana::log('error', 'Unable to automatically verify occurrences when the oldest_record_created_date_to_process configuration entry is empty.');
    return FALSE;
  }

  $maxRecordsNumber = kohana::config('auto_verify.max_num_records_to_process_at_once', FALSE, FALSE);
  $maxRecordsNumber = $maxRecordsNumber ? $maxRecordsNumber : 1000;

  $mode = variable::get('auto_verify_mode', 'initial');
  if ($mode === 'initial') {
    // Use ID from last scan or config setting if starting from scratch.
    $startId = variable::get(
      'auto_verify_start_at_id',
      kohana::config('auto_verify.oldest_occurrence_id_to_process', FALSE, FALSE)
    );
    // Default lastId to 0 if not configured and first time.
    $startId = $startId ? $startId : 1;
    $qryEnd = <<<SQL
AND delta.id >= $startId
AND delta.created_on >= TO_TIMESTAMP('$oldestRecordCreatedDateToProcess', 'DD/MM/YYYY')
AND delta.updated_on >= TO_TIMESTAMP('$oldestRecordCreatedDateToProcess', 'DD/MM/YYYY')
ORDER BY delta.id
LIMIT $maxRecordsNumber
SQL;
  }
  else {
    // Use last run date to find lowest tracking ID since the last run. If
    // based just on updated_on we would miss other changes (e.g. data cleaner
    // result being set to true)
    $minTracking = $db
      ->query("select min(tracking) as min_tracking from cache_occurrences_functional where updated_on>='$last_run_date'")
      ->current()
      ->min_tracking;
    $qryEnd = "AND delta.tracking>=$minTracking";
  }
  $verificationTime = gmdate("Y\/m\/d H:i:s");
  $query = <<<SQL
DROP TABLE IF EXISTS records_to_auto_verify;

SELECT DISTINCT delta.id
INTO TEMPORARY records_to_auto_verify
FROM cache_occurrences_functional delta
JOIN surveys s ON s.id = delta.survey_id AND s.auto_accept=true AND s.deleted=false
WHERE delta.data_cleaner_result=true
AND delta.record_status='C' AND delta.record_substatus IS NULL
AND (($autoVerifyNullIdDiff=false AND delta.identification_difficulty IS NOT NULL AND delta.identification_difficulty<=s.auto_accept_max_difficulty)
OR ($autoVerifyNullIdDiff=true AND (delta.identification_difficulty IS NULL OR delta.identification_difficulty<=s.auto_accept_max_difficulty)))
AND (s.auto_accept_taxa_filters IS NULL OR (s.auto_accept_taxa_filters && delta.taxon_path))
$qryEnd;

INSERT INTO occurrence_comments (comment, generated_by, occurrence_id, record_status, record_substatus, created_by_id, updated_by_id, created_on, updated_on, auto_generated)
SELECT 'Accepted based on automatic checks', 'system', o.id, 'V', '2', 1, 1, '$verificationTime', '$verificationTime', true
FROM occurrences o
JOIN records_to_auto_verify rav ON rav.id=o.id;

UPDATE occurrences o
SET
  record_status='V',
  record_substatus='2',
  release_status='R',
  verified_by_id=1,
  verified_on='$verificationTime',
  record_decision_source='M',
  updated_on=now(),
  updated_by_id=1
FROM records_to_auto_verify rav
WHERE rav.id=o.id;

UPDATE cache_occurrences_functional o
SET
  record_status='V',
  record_substatus='2',
  release_status='R',
  verified_on='$verificationTime'
FROM records_to_auto_verify rav
WHERE rav.id=o.id;

UPDATE cache_occurrences_nonfunctional o
SET verifier='admin, core'
FROM records_to_auto_verify rav
WHERE rav.id=o.id;

SQL;
  $db->query($query);
  // Grab stats about the records processed.
  $query = "SELECT count(*), max(id) FROM records_to_auto_verify;";
  $stats = $db->query($query)->current();
  echo "$stats->count occurrence record(s) has been automatically verified.</br>";
  if ($mode === 'initial') {
    if ($stats->count < $maxRecordsNumber) {
      // Done, so switch mode.
      variable::set('auto_verify_mode', 'updates');
      variable::delete('auto_verify_start_at_id');
    }
    else {
      variable::set('auto_verify_start_at_id', $stats->max + 1);
    }
  }
}
