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
global $noNotificationsCreatedMessage;
global $oneNotificationCreatedMessage;
global $multipleNotificationsCreatedMessage;

//Module sends out VT (verifier tasks) by default, but can also be setup to send PT (Pending Record Tasks)
//notifications which alert users when they have records at release_status "P" (pending) to check. Example use - 
//If a mentor needs to check the records of a student then that student's records can be set to "P" release status.
//This module can then pick up these records and automatically send the notifications to the mentor.
try {
  $ptModeConfig = kohana::config('verifier_notifications.use_pending_check_mode');
} catch (Exception $e) {
}
if (isset($ptModeConfig) && $ptModeConfig===true) 
  $mode = 'PT';
else 
  $mode = 'VT';

if ($mode==='PT') {
  $notificationSourceType = kohana::config('verifier_notifications.use_pending_check_mode');
  $notificationSourceType = 'PT';
  $notificationSource = 'pending_record_check_notifications';
  $notificationComment='You have pending records to check.';
  $sharingFilter='M';
  $noNotificationsCreatedMessage='No new pending record check notifications have been created.';
  $oneNotificationCreatedMessage='new pending record check notification has been created.';
  $multipleNotificationsCreatedMessage='new pending record check notifications have been created.';
} else {
  $notificationSourceType = 'VT';
  $notificationSource = 'verifier_notifications';
  $notificationComment= 'You have records to verify.';
  $sharingFilter='V';
  $noNotificationsCreatedMessage='No new verification notifications have been created.';
  $oneNotificationCreatedMessage='new verification notification has been created.';
  $multipleNotificationsCreatedMessage='new verification notifications have been created.';
}

/*
 * Scheduled task that is called to create a new VT/PT notification for every user who doesn't already have
 * an outstanding notification of that type and who is associated with a filter that has returned some new occurrences
 */
function verifier_notifications_scheduled_task($last_run_date, $db) {
  //Get all filters where the user for the filter does not already have an unacknowledged VT/PT notification 
  $filters = get_filters_without_existing_notification($db);
  loop_through_filters_and_create_notifications($db, $filters);
}

/*
 * Get all filters where the user for the filter does not already have an unacknowledged VT/PT notification 
 */
function get_filters_without_existing_notification($db) {
  global $notificationSourceType;
  global $sharingFilter;
  $filters = $db
    ->select('f.id,f.definition,fu.user_id,u.username')
    ->from('filters f')
    ->join('filters_users as fu','fu.filter_id','f.id')
    ->join('users as u','u.id','fu.user_id')
    ->join('notifications as n', "(n.user_id=fu.user_id and n.source_type='".$notificationSourceType."' and n.acknowledged=false)", '', 'LEFT')
    ->where(array('f.sharing'=>$sharingFilter, 'f.defines_permissions'=>'t','n.id'=>null,
                  'f.deleted'=>'f','fu.deleted'=>'f','u.deleted'=>'f'))
    ->get()->result_array(FALSE);
  return $filters;
}

/*
 * For each filter that returns at least one new occurrence and doesn't already have an outstanding verification notification for a user,
 * create a new notification
 */
function loop_through_filters_and_create_notifications($db, $filters) {
  global $noNotificationsCreatedMessage;
  global $oneNotificationCreatedMessage;
  global $multipleNotificationsCreatedMessage;
  global $notificationSourceType;
  global $notificationSource;
  global $notificationComment;

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
  //Go through each filter for users who don't have an outstanding verification notification.
  $notificationCounter=0;
  foreach ($filters as $filterIdx=>$filter) {  
    $params = json_decode($filter['definition'],true) + array('sharing'=>'verification');
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
          $notificationObj->source=$notificationSource;
          $notificationObj->acknowledged='false';
          $notificationObj->triggered_on=date("Ymd H:i:s");
          $notificationObj->user_id=$filter['user_id'];
          //Use VT "Verifier Task" or PT "Pending Record Task" notification type as we are informing the verifier that they need to perform a task.
          $notificationObj->source_type=$notificationSourceType; 
          $notificationObj->data=json_encode(
            array('username'=>$from,
                  'comment'=>$notificationComment,
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
    echo $noNotificationsCreatedMessage.'</br>';
  elseif ($notificationCounter==1)
    echo $notificationCounter.' '.$oneNotificationCreatedMessage.'</br>';
  else 
    echo $notificationCounter.' '.$multipleNotificationsCreatedMessage.'</br>';
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
 