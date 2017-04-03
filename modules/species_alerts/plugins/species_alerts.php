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
 * @subpackage Species alerts
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/*
 * Scheduled task for generating species alerts, scans occdelta for any new occurrences matching species alert records and then
 * generates a notification for each match
 */
function species_alerts_scheduled_task($last_run_date, $db) {
  //Get all new occurrences from the database that are either new occurrences or verified and match them with species_alert records
  //and return the matches
  $newOccDataForSpeciesAlert = $db->query("
    SELECT DISTINCT
      od.id as occurrence_id,
      cttl.taxon as taxon,
      od.record_status as record_status,
      snf.public_entered_sref as entered_sref,
      od.cud as cud,
      od.record_status as record_status,
      od.created_on,
      od.updated_on,
      sa.user_id as alerted_user_id,
      u.username as username,
      MAX(CASE WHEN sa.alert_on_entry='t' AND od.cud='C' THEN 1 ELSE 0 END) as notify_entry,
      MAX(CASE WHEN sa.alert_on_verify='t' AND od.record_status='V' AND od.cud<>'D' THEN 1 ELSE 0 END) as notify_verify
    FROM occdelta od
      JOIN cache_samples_nonfunctional snf on snf.id=od.sample_id
      JOIN cache_taxa_taxon_lists cttl on cttl.id=od.taxa_taxon_list_id
      LEFT JOIN index_locations_samples ils on ils.sample_id=od.sample_id
      -- join to find any taxon list that this taxon occurs on
      LEFT JOIN cache_taxa_taxon_lists cttlall on cttlall.taxon_meaning_id=od.taxon_meaning_id 
          OR (cttlall.external_key IS NOT NULL AND od.taxa_taxon_list_external_key IS NOT NULL AND cttlall.external_key=od.taxa_taxon_list_external_key)
      JOIN index_websites_website_agreements iwwa on iwwa.to_website_id=od.website_id and iwwa.receive_for_reporting=true
      JOIN species_alerts sa ON 
        (sa.location_id IS NULL OR sa.location_id=ils.location_id)
        AND 
          (sa.taxon_meaning_id = od.taxon_meaning_id
          OR
          sa.external_key = od.taxa_taxon_list_external_key
          OR
          sa.taxon_list_id = cttlall.taxon_list_id)
        AND
          (sa.alert_on_entry='t' AND od.cud='C'
          OR
          (sa.alert_on_verify='t' AND od.record_status='V' AND od.cud<>'D'))
        AND
          sa.website_id=iwwa.from_website_id
        AND 
          sa.deleted='f'
      JOIN users u ON 
        u.id=sa.user_id AND u.deleted='f'
     where od.training='f'
     GROUP BY od.id,
      cttl.taxon,
      od.record_status,
      snf.public_entered_sref,
      od.cud,
      od.record_status,
      od.created_on,
      od.updated_on,
      sa.user_id,
      u.username")->result_array(false);
  if (!empty($newOccDataForSpeciesAlert))
    species_alerts_create_notifications($newOccDataForSpeciesAlert);
  else
    echo 'No Species Alerts have been created because there are no created/updated occurrences matching any species alert records.</br>';
}

/*
 * Create a notification for each new/verified occurrence that matches an item in the species_alerts table
 */
function species_alerts_create_notifications($newOccDataForSpeciesAlert) {
  $notificationCounter=0;
  //For any new occurrence record which has a matching species alert record, we need to generate a notification for the user
  foreach ($newOccDataForSpeciesAlert as $speciesAlertOccurrenceData) {
    if ($speciesAlertOccurrenceData['notify_entry']==='1') {
      species_alerts_create_notification($speciesAlertOccurrenceData, 'entered');
      $notificationCounter++;
    }
    if ($speciesAlertOccurrenceData['notify_verify']==='1') {
      species_alerts_create_notification($speciesAlertOccurrenceData, 'verified');
      $notificationCounter++;
    }
  }
  if ($notificationCounter==0)
    echo 'No new Species Alert notifications have been created.</br>';
  elseif ($notificationCounter==1)
    echo '1 new Species Alert notification has been created.</br>';
  else
    echo $notificationCounter.' new Species Alert notifications have been created.</br>';
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
function species_alerts_metadata() {
  return array(
    'requires_occurrences_delta'=>TRUE
  );
}

function species_alerts_extend_data_services() {
  return array(
    'species_alerts'=>array()
  );
}