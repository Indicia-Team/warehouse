<?php

/**
 * @file
 * Plugin methods for the species alerts module.
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

/*
 * Scheduled task for generating species alerts, scans occdelta for any new occurrences matching species alert records and then
 * generates a notification for each match
 */
function species_alerts_scheduled_task($last_run_date, $db) {
  // Additional time allowed for the spatial indexing to catch up. If just
  // based on last_run_date, we'd miss records that get scanned before spatial
  // indexing.
  $extraTimeScanned = '2 days';
  // Get all new occurrences from the database that are either new occurrences
  // or verified and match them with species_alert records and return the
  // matches.
  $qry = <<<SQL
SELECT DISTINCT
  delta.id as occurrence_id,
  cttl.taxon as taxon,
  delta.record_status as record_status,
  snf.public_entered_sref as entered_sref,
  delta.record_status as record_status,
  delta.created_on,
  delta.updated_on,
  sa.user_id as alerted_user_id,
  u.username as username,
  MAX(CASE WHEN sa.alert_on_entry='t' THEN 1 ELSE 0 END) as notify_entry,
  MAX(CASE WHEN sa.alert_on_verify='t' AND delta.record_status='V' THEN 1 ELSE 0 END) as notify_verify
FROM cache_occurrences_functional delta
JOIN cache_samples_nonfunctional snf on snf.id=delta.sample_id
JOIN cache_taxa_taxon_lists cttl on cttl.id=delta.taxa_taxon_list_id
LEFT JOIN cache_taxa_taxon_lists cttlall on cttlall.taxon_meaning_id=delta.taxon_meaning_id
    OR (cttlall.external_key IS NOT NULL AND delta.taxa_taxon_list_external_key IS NOT NULL AND cttlall.external_key=delta.taxa_taxon_list_external_key)
JOIN index_websites_website_agreements iwwa on iwwa.to_website_id=delta.website_id and iwwa.receive_for_reporting=true
JOIN species_alerts sa ON
  (sa.location_id IS NULL OR delta.location_ids @> ARRAY[sa.location_id])
  AND (sa.survey_id IS NULL OR delta.survey_id = sa.survey_id)
  AND
    (sa.taxon_meaning_id = delta.taxon_meaning_id
    OR
    sa.external_key = delta.taxa_taxon_list_external_key
    OR
    sa.taxon_list_id = cttlall.taxon_list_id)
  AND
    ((sa.alert_on_entry='t' AND delta.record_status='C')
    OR
    (sa.alert_on_verify='t' AND delta.record_status='V'))
  AND
    sa.website_id=iwwa.from_website_id
  AND
    sa.deleted='f'
JOIN users u ON
  u.id=sa.user_id AND u.deleted='f'
-- Use left joins to exclude notifications that have already been generated.
LEFT JOIN notifications n_create ON n_create.user_id=sa.user_id AND n_create.linked_id=delta.id AND n_create.source='species alerts'
  AND n_create.data LIKE '%"record_status":"C"%' and n_create.data like '%"taxon":"' || cttl.taxon || '"%'
LEFT JOIN notifications n_verify ON n_verify.user_id=sa.user_id AND n_verify.linked_id=delta.id AND n_verify.source='species alerts'
  AND n_verify.data LIKE '%"record_status":"V"%' and n_verify.data like '%"taxon":"' || cttl.taxon || '"%'
WHERE delta.training='f' AND delta.confidential='f'
AND (
  (n_create.id IS NULL AND sa.alert_on_entry='t' AND delta.created_on> TO_TIMESTAMP('$last_run_date', 'YYYY-MM-DD HH24:MI:SS') - '$extraTimeScanned'::interval)
  OR (n_verify.id IS NULL AND sa.alert_on_verify='t' AND delta.record_status='V' AND delta.verified_on > TO_TIMESTAMP('$last_run_date', 'YYYY-MM-DD HH24:MI:SS') - '$extraTimeScanned'::interval)
)
-- Following just to allow index to be used.
AND delta.updated_on> TO_TIMESTAMP('$last_run_date', 'YYYY-MM-DD HH24:MI:SS') - '$extraTimeScanned'::interval
GROUP BY delta.id,
  cttl.taxon,
  delta.record_status,
  snf.public_entered_sref,
  delta.record_status,
  delta.created_on,
  delta.updated_on,
  sa.user_id,
  u.username;
SQL;
  $newOccDataForSpeciesAlert = $db->query($qry)->result_array(FALSE);
  if (!empty($newOccDataForSpeciesAlert)) {
    species_alerts_create_notifications($newOccDataForSpeciesAlert);
  }
  else {
    echo 'No Species Alerts have been created because there are no created/updated occurrences matching any species alert records.</br>';
  }
}

/*
 * Create a notification for each new/verified occurrence that matches an item in the species_alerts table
 */
function species_alerts_create_notifications($newOccDataForSpeciesAlert) {
  $notificationCounter = 0;
  // For any new occurrence record which has a matching species alert record,
  // we need to generate a notification for the user.
  foreach ($newOccDataForSpeciesAlert as $speciesAlertOccurrenceData) {
    if ($speciesAlertOccurrenceData['notify_entry'] === '1') {
      species_alerts_create_notification($speciesAlertOccurrenceData, 'entered');
      $notificationCounter++;
    }
    if ($speciesAlertOccurrenceData['notify_verify'] === '1') {
      species_alerts_create_notification($speciesAlertOccurrenceData, 'verified');
      $notificationCounter++;
    }
  }
  if ($notificationCounter === 0) {
    echo 'No new Species Alert notifications have been created.</br>';
  }
  elseif ($notificationCounter === 1) {
    echo '1 new Species Alert notification has been created.</br>';
  }
  else {
    echo "$notificationCounter new Species Alert notifications have been created.</br>";
  }
}

/**
 * Creates a single notification
 */
function species_alerts_create_notification($speciesAlertOccurrenceData, $action) {
  $sref = $speciesAlertOccurrenceData['entered_sref'];
  if (empty($sref)) {
    $sref = '*sensitive*';
  }
  $commentText = "A record of $speciesAlertOccurrenceData[taxon] at $sref on " .
    date("Y\/m\/d", strtotime($speciesAlertOccurrenceData['created_on'])) . " has been $action.<br\/>";
  try {
    $from = kohana::config('species_alerts.from');
  } catch (Exception $e) {
    $from = 'system';
  }
  $notificationObj = ORM::factory('notification');
  $notificationObj->source = 'species alerts';
  $notificationObj->acknowledged = 'false';
  $notificationObj->triggered_on = date("Ymd H:i:s");
  $notificationObj->user_id = $speciesAlertOccurrenceData['alerted_user_id'];
  $notificationObj->source_type = 'S';
  $notificationObj->linked_id = $speciesAlertOccurrenceData['occurrence_id'];
  $notificationObj->data =
    json_encode(
      array(
        'username' => $from,
        'occurrence_id' => $speciesAlertOccurrenceData['occurrence_id'],
        'comment' => $commentText,
        'taxon' => $speciesAlertOccurrenceData['taxon'],
        'date' => date("Y\/m\/d", strtotime($speciesAlertOccurrenceData['created_on'])),
        'entered_sref' => $speciesAlertOccurrenceData['entered_sref'],
        'auto_generated' => 't',
        'record_status' => $speciesAlertOccurrenceData['record_status'],
        'updated on' => date("Y-m-d H:i:s", strtotime($speciesAlertOccurrenceData['updated_on']))
      )
    );
  $notificationObj->save();
}

/*
 * Tell the system that we need the occdelta table to find out which occurrences have been created/changed recently.
 */
/*function species_alerts_metadata() {
  return array(
    'requires_occurrences_delta'=>TRUE
  );
}*/

function species_alerts_extend_data_services() {
  return array(
    'species_alerts'=>array()
  );
}