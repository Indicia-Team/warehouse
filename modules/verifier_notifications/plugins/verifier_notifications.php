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
 * @package	Verifier notifications
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
global $notificationSourceType;
global $notificationSource;
global $notificationComment;
global $sharingFilter;
global $sharingFilterFullName;
global $noNotificationsCreatedMessage;
global $oneNotificationCreatedMessage;
global $multipleNotificationsCreatedMessage;

//Module sends out VT (verifier tasks) and PT (Pending Record Tasks) notifications which alert users when they have records to verify or
//records at release_status "P" (pending) to check. Example use - 
//If a mentor needs to check the records of a student then that student's records can be set to "P" release status.
//This module can then pick up these records and automatically send the notifications to the mentor.

/*
 * Scheduled task that is called to create a new VT/PT notification for every user who doesn't already have
 * an outstanding notification of that type and who is associated with a filter that has returned some new occurrences
 */
function verifier_notifications_scheduled_task($last_run_date, $db) {
  $ptVariables['notificationSourceType'] = 'PT';
  $ptVariables['notificationSource'] = 'pending_record_check_notifications';
  $ptVariables['notificationComment'] = 'You have pending records to check.';
  $ptVariables['sharingFilter'] = 'M';
  $ptVariables['sharingFilterFullName'] = 'moderation';
  $ptVariables['noNotificationsCreatedMessage'] = 'No new pending record check notifications have been created.';
  $ptVariables['oneNotificationCreatedMessage'] = 'new pending record check notification has been created.';
  $ptVariables['multipleNotificationsCreatedMessage'] = 'new pending record check notifications have been created.';
  //Get all filters where the user for the filter does not already have an unacknowledged PT notification 
  $filters = get_filters_without_existing_notification($db, $ptVariables);
  loop_through_filters_and_create_notifications($db, $filters, $ptVariables);
  $vtVariables['notificationSourceType'] = 'VT';
  $vtVariables['notificationSource'] = 'verifier_notifications';
  $vtVariables['notificationComment'] = 'You have records to verify.';
  $vtVariables['sharingFilter'] = 'V';
  $vtVariables['sharingFilterFullName'] = 'verification';
  $vtVariables['noNotificationsCreatedMessage'] = 'No new verification notifications have been created.';
  $vtVariables['oneNotificationCreatedMessage'] = 'new verification notification has been created.';
  $vtVariables['multipleNotificationsCreatedMessage'] = 'new verification notifications have been created.';
  //Get all filters where the user for the filter does not already have an unacknowledged VT notification 
  $filters = get_filters_without_existing_notification($db, $vtVariables);
  loop_through_filters_and_create_notifications($db, $filters, $vtVariables);
}

/*
 * Get all filters where the user for the filter does not already have an unacknowledged VT/PT notification 
 */
function get_filters_without_existing_notification($db, $variables) {
  $filters = $db
    ->select('f.id,f.definition,fu.user_id,u.username')
    ->from('filters f')
    ->join('filters_users as fu','fu.filter_id','f.id')
    ->join('users as u','u.id','fu.user_id')
    ->join('notifications as n', "(n.user_id=fu.user_id and n.source_type='".$variables['notificationSourceType']."' and n.acknowledged=false)", '', 'LEFT')
    ->where(array('f.sharing'=>$variables['sharingFilter'], 'f.defines_permissions'=>'t','n.id'=>null,
                  'f.deleted'=>'f','fu.deleted'=>'f','u.deleted'=>'f'))
    ->get()->result_array(FALSE);
  return $filters;
}

/*
 * For each filter that returns at least one new occurrence and doesn't already have an outstanding VT/PT notification for a user,
 * create a new notification
 */
function loop_through_filters_and_create_notifications($db, $filters, $variables) {
  $report = 'library/occdelta/filterable_occdelta_count';
  //Supply a config of which websites to take into account.
  try {
    $website_ids=kohana::config('verifier_notifications.website_ids');
    $from=kohana::config('verifier_notifications.from');
  //Handle config file not present
  } catch (Exception $e) {
    $website_ids=array();
    $from='system';
  }
  //handle if config file present but option is not supplied
  if (empty($website_ids))
    $website_ids=array();
  //Supply 1 as the user id to give the code maximum privileges. Also force the main database connection 
  //to allow access to the temporary occdelta table.
  $reportEngine = new ReportEngine($website_ids, 1, $db);
  //When creating notifications keep a track of user's we have created notifications for, this allows us to 
  //avoid creating multiple notifications per user without having to check the database.
  $alreadyCreatedNotification=array();
  //Go through each filter for users who don't have an outstanding VT/PT notification.
  $notificationCounter=0;
  foreach ($filters as $filterIdx=>$filter) {  
    $extraParams = array('sharing'=>$variables['sharingFilterFullName']);
    if ($variables['notificationSourceType']==='VT')
      //Only look for completed record_status, we don't want to pick up V for instance, as these records are already verified
      $extraParams = array_merge($extraParams,array('record_status'=>'C'));
    else
    //If we are only interested in detecting Pending records then provide a release_status P parameter, this will
    //override the release_status R parameter that automatically appears in the report.
      $extraParams = array_merge($extraParams,array('release_status'=>'P'));   
    $params = json_decode($filter['definition'],true) + $extraParams;
    try {
      // don't run the filter unless we we haven't already created a notification for that user 
      if (!in_array($filter['user_id'],$alreadyCreatedNotification)) {
        //Get the report data for all new occurrences that match the filter.
        //Use the filter as the params
        $output=$reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
        //If records are returned then continue
        if ($output['content']['records'][0]['count'] > 0) {
          //Save the new notification
          $notificationObj = ORM::factory('notification');
          $notificationObj->source=$variables['notificationSource'];
          $notificationObj->acknowledged='false';
          $notificationObj->triggered_on=date("Ymd H:i:s");
          $notificationObj->user_id=$filter['user_id'];
          //Use VT "Verifier Task" or PT "Pending Record Task" notification type as we are informing the verifier that they need to perform a task.
          $notificationObj->source_type=$variables['notificationSourceType']; 
          $notificationObj->data=json_encode(
            array('username'=>$from,
                  'comment'=>$variables['notificationComment'],
                  'auto_generated'=>'t'));
          $notificationObj->save();
          $notificationCounter++;
          $alreadyCreatedNotification[]=$filter['user_id'];
        }
      }
    } catch (Exception $e) {
      echo $e->getMessage();
      error::log_error('Error occurred when creating notifications based on new occurrences and user\'s filters.', $e);
    }
  }
  //Display message to show how many notifications were created.
  if ($notificationCounter==0)
    echo $variables['noNotificationsCreatedMessage'].'</br>';
  elseif ($notificationCounter==1)
    echo $notificationCounter.' '.$variables['oneNotificationCreatedMessage'].'</br>';
  else 
    echo $notificationCounter.' '.$variables['multipleNotificationsCreatedMessage'].'</br>';
}

/*
 * Request that we need the system to create the occdelta database table when we run the scheduled task. This table allows
 * us to see which occurrences are new
 *  
 */
function verifier_notifications_metadata() {
  return array(
    'requires_occurrences_delta'=>TRUE
  );
}
?> 
 