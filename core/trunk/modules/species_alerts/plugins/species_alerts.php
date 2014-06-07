<?php

function species_alerts_extend_data_services() {
  return array(
    'species_alerts'=>array('allow_full_access'=>true)
  );
}

/*
 * Scheduled task for generating species alerts, scans occdelta for any new occurrences matching species alert records and then
 * generates a notification for each match
 */
function species_alerts_scheduled_task($last_run_date, $db) {
  //Get all new occurrences from the database that are either new occurrences or verified and match them with species_alert records
  //and return the matches
  $newOccDataForSpeciesAlert = $db->query("
    SELECT 
      od.id as occurrence_id,
      od.taxon as taxon,
      od.record_status as record_status,
      od.public_entered_sref as entered_sref,
      od.cud as cud,
      od.record_status as record_status,
      o.created_on as created_on,
      o.updated_on as updated_on,
      sa.user_id as alerted_user_id,
      u.username as username
    FROM occdelta od
      JOIN occurrences o ON o.id = od.id
      JOIN species_alerts sa ON 
        od.location_id=sa.location_id
        AND 
          (sa.taxon_meaning_id = od.taxon_meaning_id
          OR
          sa.external_key = od.taxa_taxon_list_external_key)
        AND
          (sa.alert_on_entry='t' AND od.cud='C'
          OR
          (sa.alert_on_verify='t' AND (od.record_status='V' AND od.cud='U')))
        AND
          sa.website_id=od.website_id
        AND 
          sa.deleted='f'
      JOIN users u ON 
        u.id=sa.user_id AND u.deleted='f'")->result_array(false);
  if (!empty($newOccDataForSpeciesAlert))
    create_notifications($newOccDataForSpeciesAlert);
  else
    echo 'No Species Alerts have been created because there are no created/updated occurrences matching any species alert records.</br>';
}

/*
 * Create a notification for each new/verified occurrence that matches an item in the species_alerts table
 */
function create_notifications($newOccDataForSpeciesAlert) {
  $notificationCounter=0;
  //For any new occurrence record which has a matching species alert record, we need to generate a notification for the user
  foreach ($newOccDataForSpeciesAlert as $speciesAlertOccurrenceData) {
    if ($speciesAlertOccurrenceData['record_status']=='V'&&$speciesAlertOccurrenceData['cud']=='U')
      $commentText = "Species Alert: The record ".$speciesAlertOccurrenceData['taxon']." at ".$speciesAlertOccurrenceData['entered_sref']." on ".date("Y\/m\/d",strtotime($speciesAlertOccurrenceData['created_on']))." has been verified.<br\/>";
    if ($speciesAlertOccurrenceData['cud']=='C') 
      $commentText = "Species Alert: The record ".$speciesAlertOccurrenceData['taxon']." at ".$speciesAlertOccurrenceData['entered_sref']." on ".date("Y\/m\/d",strtotime($speciesAlertOccurrenceData['created_on']))." has been created.<br\/>";
    if (!empty($commentText)) {
      $notificationObj = ORM::factory('notification');
      $notificationObj->source='species alerts';
      $notificationObj->acknowledged='false';
      $notificationObj->triggered_on=date("Ymd H:i:s");
      $notificationObj->user_id=$speciesAlertOccurrenceData['alerted_user_id'];
      $notificationObj->source_type='S';
      $notificationObj->linked_id=$speciesAlertOccurrenceData['occurrence_id'];
      $notificationObj->data=
      json_encode(
        array(
          'username'=>$speciesAlertOccurrenceData['username'],
          'occurrence_id'=>$speciesAlertOccurrenceData['occurrence_id'],         
          'comment'=>$commentText,
          'taxon'=>$speciesAlertOccurrenceData['taxon'],
          'date'=>date("Y\/m\/d",strtotime($speciesAlertOccurrenceData['created_on'])),
          'entered_sref'=>$speciesAlertOccurrenceData['entered_sref'],
          'auto_generated'=>'t',
          'record_status'=>$speciesAlertOccurrenceData['record_status'],
          'updated on'=>date("Y-m-d H:i:s",strtotime($speciesAlertOccurrenceData['updated_on']))
        )
      );
      $notificationObj->save();
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

/*
 * Tell the system that we need the occdelta table to find out which occurrences have been created/changed recently.
 */
function species_alerts_metadata() {
  return array(
    'requires_occurrences_delta'=>TRUE
  );
}
?> 
 