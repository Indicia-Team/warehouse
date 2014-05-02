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
 * @package	Verifier notification emails
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
 
/*
 * Scheduled task that is called to create a new 'you have new records to verify' notification for every user who doesn't already have
 * an outstanding notification and who is associated with a filter that has returned some new occurrences
 */
function verifier_notification_emails_scheduled_task($last_run_date, $db) {
  //Get all filters where the user for the filter does not already have an unacknowledged 'you have new records to verify' notification 
  $filters = get_filters_without_existing_notification($db);
  //Collect all usernames from the users table, this is used in the notifications data column. We don't collect the username from the 
  //table at the time we need it because the sql would be in the middle of a large loop so it would be slow to keep collecting from database,
  //quicker to collect from array.
  $userDataWithIdKey = get_usernames_from_users_table($db);
  loop_through_filters_and_create_notifications($filters,$userDataWithIdKey);
}

/*
 * Get all filters where the user for the filter does not already have an unacknowledged 'you have new records to verify' notification 
 */
function get_filters_without_existing_notification($db) {
  $filters = $db
    ->select('f.id,f.definition,fu.user_id')
    ->from('filters f')
    ->join('filters_users as fu','fu.filter_id','f.id')
    ->join('notifications as n', "(n.user_id=fu.user_id and n.source_type='VT' and n.acknowledged=false)", '', 'LEFT')
    ->where(array('f.sharing'=>'V', 'f.defines_permissions'=>'t','n.id'=>null,
                  'f.deleted'=>'f','fu.deleted'=>'f'))
    ->get()->result_array(FALSE);
  return $filters;
}

/*
 * For efficiency we collect all the usernames we need before looping through the filters as we don't want to run the
 * SQL for each loop (there could be in the region of 500 filters)
 */
function get_usernames_from_users_table($db) {
  $userData = $db
    ->select('u.id,u.username')
    ->from('users u')
    ->where(array('u.deleted'=>'f'))
    ->get()->result_array(FALSE);
  //Setup the data so the user id is the key and the username is the data item, this allows us to immediately return the username for any
  //given ID.
  foreach($userData as $userDataItem) {
    $userDataWithIdKey[$userDataItem['id']]=$userDataItem['username'];         
  }
  return $userDataWithIdKey;
}

/*
 * For each filter that returns at least one new occurrence and doesn't already have an outstanding verification notification for a user,
 * create a new notification
 */
function loop_through_filters_and_create_notifications($filters,$userDataWithIdKey) {
  $report = 'library/occdelta/filterable_occdelta_count';
  //Supply a config of which websites to take into account. Supply 1 as the user
  //id to give the code maximum privileges
  if (kohana::config('verifier_notification_emails.website_ids'))
    $website_ids=kohana::config('verifier_notification_emails.website_ids');
  else 
    $website_ids=array();
  $reportEngine = new ReportEngine(kohana::config('verifier_notification_emails.website_ids'), 1);
  //When creating notifications keep a track of user's we have created notifications for, this allows us to 
  //avoid creating multiple notifications per user without having to check the database.
  $alreadyCreatedNotification=array();
  //Go through each filter for users who don't have an outstanding verification notification.
  $notificationCounter=0;
  foreach ($filters as $filterIdx=>$filter) {  
    $params = json_decode($filter['definition'],true);
    try {
      //Get the report data for all new occurrences that match the filter.
      //Use the filter as the params
      $data[$filterIdx]=$reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
        //If records are returned and we haven't already created a notification for that user then continue
        if ($data[$filterIdx]['content']['records'][0]['count'] > 0  && !in_array($filter['user_id'],$alreadyCreatedNotification)) {
          //Save the new notification
          $notificationObj = ORM::factory('notification');
          $notificationObj->source='you have new records to verify';
          $notificationObj->acknowledged='false';
          $notificationObj->triggered_on=date("Ymd H:i:s");
          $notificationObj->user_id=$filter['user_id'];
          //Use VT "Verifier Task" notification typeas we are informing the verifier that they need to perform a task.
          $notificationObj->source_type='VT'; 
          $notificationObj->data=json_encode(
            array('username'=>$userDataWithIdKey[$filter['user_id']],
                  'comment'=>'You have records to verify.',
                  'auto_generated'=>'t'));
          $notificationObj->save();
          $notificationCounter++;
          $alreadyCreatedNotification[]=$filter['user_id'];
        }
      
    } catch (Exception $e) {
      echo $e->getMessage();
      error::log_error('Error occurred when creating verification notifications based on new occurrences and user\'s filters.', $e);
    }
  }
  if ($notificationCounter==0)
    echo 'No new verification notifications have been created.</br>';
  elseif ($notificationCounter==1)
    echo $notificationCounter.' new verification notification has been created.</br>';
  else 
    echo $notificationCounter.' new verification notifications have been created.</br>';
}

/*
 * Request that we need the system to create the occdelta database table when we run the scheduled task. This table allows
 * us to see which occurrences are new
 *  
 */
function verifier_notification_emails_metadata() {
  return array(
    'requires_occurrences_delta'=>TRUE
  );
}
?> 
 