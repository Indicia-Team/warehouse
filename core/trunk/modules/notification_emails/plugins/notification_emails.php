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
 * @package	Notification emails
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */


/**
 * Hook to ORM enable the relationship between users and notification settings.
 */
function notification_emails_extend_orm() {
  return array('user'=>array(
    'has_many'=>array('user_email_notification_settings')
  ));
}

function notification_emails_extend_data_services() {
  return array(
    'user_email_notification_settings'=>array()
  );
}

/*
 * When the scheduled tasks are run, send out emails to users with details of their notifications based on their subscription settings (e.g. they could be setup to receive
 * emails about species alerts on a weekly basis)
 */
function notification_emails_scheduled_task($last_run_date, $db) {
  //We need to first determine which jobs to run, for instance, if it is less than a week since the last weekly job was run, then the weekly job doesn't need running yet.
  $frequenciesToRun = get_frequencies_to_run_now($db);
  //Don't do anything if there are no jobs to run
  if (!empty($frequenciesToRun[0])) {
    runEmailNotificationJobs($db,$frequenciesToRun); 
  } else {
    echo 'There are no email notification jobs to run at the moment.</br>';
  }
}

/*
 * Collect the notification frequency jobs that needs to be run now. For instance if it is less than a week
 * since the weekly notification frequency job was last run, then we don't need to run it now. As soon as
 * we detect that it has been longer than a week then we know that is one of the jobs we need to run now.
 */
function get_frequencies_to_run_now($db) {
  $frequenciesToRun = $db->query("
    SELECT notification_frequency
    FROM user_email_notification_frequency_last_runs
    WHERE 
    (notification_frequency='IH' AND now()>=(last_run_date + interval '1 hour'))
    OR
    (notification_frequency='D' AND now()>=(last_run_date + interval '1 day'))
    OR
    (notification_frequency='W' AND now()>=(last_run_date + interval '1 week'))
    OR 
    last_run_date IS NULL
  ")->result_array(false);
  return $frequenciesToRun;
}

/*
 * Send out the notification emails
 */
function runEmailNotificationJobs($db, $frequenciesToRun) {
  //Get the URL of the page that will appear as a link in the email to the subscription page. This is provided in the config by the user
  $noSubscriptionSettingsLinkMessage='The subscription link url has not been provided in the configuration file. The link will not appear in notification emails that are sent out.</br>';
  try {
    $subscriptionSettingsPageUrl=kohana::config('notification_emails.subscription_settings_url');
    //Handle config file not present
  } catch (Exception $e) {
    echo $noSubscriptionSettingsLinkMessage;
  }
  //Handle if config file present but option is not
  if (empty($subscriptionSettingsPageUrl))
    echo $noSubscriptionSettingsLinkMessage;
  
  $frequencyToRunString='';
  //Gather all the notification frequency jobs we need to run into a set ready to pass into sql
  foreach ($frequenciesToRun as $frequencyToRunArray) {
    $frequencyToRunString .= "'".$frequencyToRunArray['notification_frequency']."'".',';
  }
  //Chop comma off end of set
  $frequencyToRunString = substr($frequencyToRunString, 0, -1);
  //Get all the notifications where the source_type is listed as needing running today and the notification id is later than the last notification that was sent by that job
  $notificationsToSendEmailsFor = $db->query("
    SELECT n.id,n.user_id, n.source_type, n.source, n.data, u.username
    FROM notifications n
      JOIN user_email_notification_settings unf ON unf.notification_source_type=n.source_type AND unf.user_id = n.user_id AND unf.notification_frequency in (".$frequencyToRunString.") AND unf.deleted='f'
      JOIN user_email_notification_frequency_last_runs unflr ON unf.notification_frequency=unflr.notification_frequency AND (n.id>unflr.last_max_notification_id OR unflr.last_max_notification_id IS NULL)
      JOIN users u ON u.id = n.user_id
    WHERE n.acknowledged = 'f'
    ORDER BY n.user_id, u.username, n.id
  ")->result_array(false);
  if (empty($notificationsToSendEmailsFor)) {
    echo 'There are no email notification jobs to run at the moment.</br>';
  } else {
    $emailSentCounter=0;
    //All the notifications that need to be sent in an email are grouped by user, as we cycle through the notifications then we can track who the user was for the previous notification.
    //When this user id then changes, we know we need to start building an new email to a new user.
    $previousUserId=0;
    $notificationIds=array();
    $emailContent=start_building_new_email($notificationsToSendEmailsFor[0]);
    foreach ($notificationsToSendEmailsFor as $notificationToSendEmailsFor) {
      //This user is not the first user but we have detected that it is not the same user we added a notification to the email for last time,
      //this means we need to send out the previous user's email and start building a new email
      if ($notificationToSendEmailsFor['user_id']!=$previousUserId && $previousUserId!=0) {      
        if (!empty($subscriptionSettingsPageUrl))
          $emailContent.='<a href="'.$subscriptionSettingsPageUrl.'?user_id='.$previousUserId.'">Click here to update your subscription settings.</a></br></br>';
        send_out_user_email($db,$emailContent,$previousUserId,$notificationIds);
        $emailSentCounter++;
        //As we just sent out a an email, we can start building a new one.
        $emailContent=start_building_new_email($notificationToSendEmailsFor);
      } 
      //For every notification, we must add it to the email
      $emailContent.= "ID: ".$notificationToSendEmailsFor['id']."</br>";
      $emailContent.= "Notification Source Type: ".$notificationToSendEmailsFor['source_type']."</br>";
      if (!empty($notificationToSendEmailsFor['source']))
        $emailContent.= "Notification Source: ".$notificationToSendEmailsFor['source']."</br>";
      if (!empty($notificationToSendEmailsFor['data'])) {
        $emailContent.= "</br>Data</br>";
        $emailContent.= unparseData($notificationToSendEmailsFor['data'])."</br></br>";
      }
      //Log the notification id so we know that this will have to be set to acknowledged (emailed notifications are considered acknowledged)
      $notificationIds[]=$notificationToSendEmailsFor['id'];
      //Update the user_id tracker as we cycle through the notifications
      $previousUserId=$notificationToSendEmailsFor['user_id'];
    }
    //if we have run out of notifications to send we will have finished going around the loop, so we just need to send out the last email whatever happens
    if (!empty($subscriptionSettingsPageUrl))
      $emailContent.='<a href="'.$subscriptionSettingsPageUrl.'?user_id='.$previousUserId.'">Click here to update your subscription settings.</a></br></br>';
    send_out_user_email($db,$emailContent,$previousUserId,$notificationIds);
    $emailSentCounter++;
    //Save the maximum notification id against the jobs we are going to run now, so we know that we have done the notifications up to that id and next time the jobs are run
    //they only need to work with notifications later than that id.
    //Also set the date/time the job was run
    update_last_run_metadata($db, $frequenciesToRun);
    //All notifications that have been sent out in an email are considered to be acknowledged
    $db
      ->set('acknowledged', 't')
      ->from('notifications')
      ->in('id', $notificationIds)
      ->update();
    if ($emailSentCounter==0)
      echo 'No new notification emails have been sent.</br>';
    elseif ($emailSentCounter==1)
      echo '1 new notification email has been sent.</br>';
    else 
      echo $emailSentCounter.' new notification emails have been sent.</br>';
  }
}

/*
 * Create the first part of the email to send (i.e. the part before the list of notifications itself)
 */
function start_building_new_email($notificationToSendEmailsFor) {
  //How do we address the user at the start of the email e.g. Dear user, To user, How is your day going user?
  //Get this from config if available
  $defaultUserAddress='Dear';
  try {
    $emailContent=kohana::config('notification_emails.how_to_address_username');
    //Handle config file not present
  } catch (Exception $e) {
    $emailContent=$defaultUserAddress;
  }
  //Handle if config file present but option is not
  if (empty($emailContent)) {
    $emailContent=$defaultUserAddress;
  }
  $emailContent .= ' '.$notificationToSendEmailsFor['username'].', </br></br>';
  //Some description before the list of notifications
  $defaultTopOfEmailBody='You have the following new notifications.';
  try {
    $topOfEmailBody=kohana::config('notification_emails.top_of_email_body');
  //Handle config file not present
  } catch (Exception $e) {
    $topOfEmailBody=$defaultTopOfEmailBody;
  }
  //Handle if config file present but option is not
  if (empty($topOfEmailBody))
    $topOfEmailBody=$defaultTopOfEmailBody;
  
  $emailContent.=$topOfEmailBody.'</br></br>';
  return $emailContent;
}

/*
 * Save the maximum notification id against the jobs we are running now, so we know that we have done the notifications up to that id and next time the jobs are run
 * they only need to work with notifications later than that id.
 * Also set the date/time the job was run.
 */
function update_last_run_metadata($db, $frequenciesToUpdate) {
  $newMaxIdData = $db->query("
    SELECT max(n.id) as new_max_notification_id
    FROM notifications n
  ")->result_array(false);
  //Cycle through the frequency jobs we are running this time as only these need updating.
  foreach ($frequenciesToUpdate as $frequencyToUpdate) {
    $updateData = $db->query("
      UPDATE user_email_notification_frequency_last_runs
      SET last_run_date=now(),last_max_notification_id=".$newMaxIdData[0]['new_max_notification_id']."
      WHERE notification_frequency='".$frequencyToUpdate['notification_frequency']."'
    ")->result_array(false);
  }
 }

/*
 * Actually send the email to the uer
 */
function send_out_user_email($db,$emailContent,$userId,$notificationIds) {
  $cc=null;
  $swift = email::connect();
  // Use a transaction to allow us to prevent the email sending and marking of notification as done
  // getting out of step
  try {
    $email_config = Kohana::config('email');
    if (!isset($email_config['address'])) {
      echo 'Email address not provided in email configuration', 'error';
      return;
    }
    //Get the user's email address from the people table
    $userResults = $db->
        select('people.email_address')
        ->from('people')
        ->join('users', 'users.person_id', 'people.id')
        ->where('users.id', $userId)
        ->limit(1)
        ->get();
      
      $defaultEmailSubject='You have new notifications.';
      try {
        $emailSubject=kohana::config('notification_emails.email_subject');
        //Handle config file not present
      } catch (Exception $e) {
        $emailSubject=$defaultEmailSubject;
      }
      if (empty($emailSubject))
        $emailSubject=$defaultEmailSubject;
      
      $message = new Swift_Message($emailSubject, $emailContent,
                                   'text/html');
      $recipients = new Swift_RecipientList();
      $recipients->addTo($userResults[0]->email_address);
      // send the email
      $swift->send($message, $recipients, $email_config['address']);
      kohana::log('info', 'Email notification sent to '. $userResults[0]->email_address);        
  } catch (Exception $e) {
    // Email not sent, so undo marking of notification as complete.
    $db->rollback();
    throw $e;
  }
}

/**
 * Converts data stored in the notifications table into an HTML grid.
 */
function unparseData($data) {
  $struct = json_decode($data, true);
  $r = "<table><tbody>\n";
  foreach ($struct as $title=>$record) {
    $r .= '<tr><td>';
    $r .= $title.': '.$record;
    $r .= "</td></tr>\n";
  }
  $r .= "</tbody>\n</table>\n";
  return $r;
}
?>