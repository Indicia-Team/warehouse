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
    'user_email_notification_settings'=>array('allow_full_access'=>true)
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
    echo 'There are no email notification jobs to run at the moment.<br/>';
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
  $subscriptionSettingsPageUrl = url::base() . 'subscription_settings.php';
  $frequencyToRunString='';
  //Gather all the notification frequency jobs we need to run into a set ready to pass into sql
  foreach ($frequenciesToRun as $frequencyToRunArray) {
    $frequencyToRunString .= "'".$frequencyToRunArray['notification_frequency']."'".',';
  }
  //Chop comma off end of set
  $frequencyToRunString = substr($frequencyToRunString, 0, -1);
  //Get all the notifications where the source_type is listed as needing running today and the notification id is later than the last notification that was sent by that job
  $notificationsToSendEmailsFor = $db->query("
    SELECT distinct n.id,n.user_id, n.source_type, n.source, n.data, u.username, coalesce(p.first_name, u.username) as name_to_use
    FROM notifications n
      JOIN user_email_notification_settings unf ON unf.notification_source_type=n.source_type AND unf.user_id = n.user_id AND unf.notification_frequency in (".$frequencyToRunString.") AND unf.deleted='f'
      JOIN user_email_notification_frequency_last_runs unflr ON unf.notification_frequency=unflr.notification_frequency AND (n.id>unflr.last_max_notification_id OR unflr.last_max_notification_id IS NULL)
      JOIN users u ON u.id = n.user_id AND u.deleted=false
      JOIN people p ON p.id = u.person_id AND p.deleted=false
    WHERE n.email_sent = 'f' AND n.source_type<>'T' AND n.acknowledged = 'f'
    ORDER BY n.user_id, u.username, n.source_type, n.id
  ")->result_array(false);
  if (empty($notificationsToSendEmailsFor)) {
    echo 'There are no email notification jobs to run at the moment.<br/>';
  } else {
    //Get address to send emails from.
    $email_config=array();
    //Try and get from configuration file if possible
    try {
      $email_config['address']=kohana::config('notification_emails.email_sender_address');
      $systemName = kohana::config('notification_emails.system_name');
      //Handle config file not present
    } catch (Exception $e) {
      $email_config = Kohana::config('email');
      $systemName = 'System generated';
    }
    //Handle also if config file present but option is not
    if (!isset($email_config['address'])) {
      echo 'Email address not provided in email configuration or email_sender_address configuration option not provided. I cannot send any emails without a sender address.<br/>';
      return false;;
    }
    $emailSentCounter=0;
    //All the notifications that need to be sent in an email are grouped by user, as we cycle through the notifications then we can track who the user was for the previous notification.
    //When this user id then changes, we know we need to start building an new email to a new user.
    $previousUserId=0;
    $notificationIds=array();
    $emailContent=start_building_new_email($notificationsToSendEmailsFor[0]);
    $currentType = '';
    $sourceTypes=array('S'=>'Species alerts','C'=>'Comments on your records','V'=>'Verification of your records','A'=>'Record Cleaner results for your records',
        'VT'=>'Incoming records for you to verify','M'=>'Milestones and achievements you\'ve attained');
    $recordStatus = array('T' => 'Test', 'I' => 'Data entry in progress', 'C' => 'Pending verification', 'R' => 'Rejected', 'D' => 'Queried', 'V' => 'Verified', 'S' => 'Awaiting response');
    $dataFieldsToOutput = array('username'=>'From', 'occurrence_id'=>'Record ID', 'comment'=>'Message', 'record_status'=>'Record status');
    foreach ($notificationsToSendEmailsFor as $notificationToSendEmailsFor) {
      //This user is not the first user but we have detected that it is not the same user we added a notification to the email for last time,
      //this means we need to send out the previous user's email and start building a new email
      if ($notificationToSendEmailsFor['user_id']!=$previousUserId && $previousUserId!=0) {      
        $emailContent.='<a href="'.$subscriptionSettingsPageUrl.'?user_id='.$previousUserId.'&warehouse_url='.url::base().'">Click here to update your subscription settings.</a><br/><br/>';
        send_out_user_email($db,$emailContent,$previousUserId,$notificationIds,$email_config);
        //Used to mark the notifications in an email if an email send is successful, once email send attempt has been made we can reset the list ready for the next email.
        $notificationIds=array();
        $emailSentCounter++;
        //As we just sent out a an email, we can start building a new one.
        $emailContent=start_building_new_email($notificationToSendEmailsFor);
        $currentType = '';
      } 
      if (!empty($notificationToSendEmailsFor['data'])) {
        $record = json_decode($notificationToSendEmailsFor['data'], true);
        // Output a header for the group of notifications of the same type
        if ($currentType!==$notificationToSendEmailsFor['source_type']) {
          if ($currentType!=='')
            $emailContent .= '</tbody></table>';
          $currentType=$notificationToSendEmailsFor['source_type'];
          $emailContent .= '<h2>'.$sourceTypes[$currentType].'</h2>';
          $emailContent .= '<table><thead>';
          foreach ($dataFieldsToOutput as $field=>$caption) {
            if (isset($record[$field])) 
              $emailContent .= "<th>$caption</th>";
          }
          $emailContent .= '</thead><tbody>';
        }
        $emailContent .= '<tr>';
        foreach ($dataFieldsToOutput as $field=>$caption) {
          if (isset($record[$field])) { 
            if ($field==='username' && ($record[$field]==='admin' || $record[$field]==='system'))
              $record[$field] = $systemName;
            elseif ($field==='occurrence_id')
              $record[$field] = 'Record ID ' . $record[$field];
            elseif ($field==='record_status') 
              $record[$field] = $recordStatus[$record[$field]];
            $emailContent .= '<td style="padding-right: 1em;">'.$record[$field].'</td>';
          }
        }
        $emailContent .= '</tr>';
      }
      //Log the notification id so we know that this will have to be set to email_sent in the database for the notification
      $notificationIds[]=$notificationToSendEmailsFor['id'];
      //Update the user_id tracker as we cycle through the notifications
      $previousUserId=$notificationToSendEmailsFor['user_id'];
    }
    if ($currentType!=='')
      $emailContent .= '</tbody></table>';
    //if we have run out of notifications to send we will have finished going around the loop, so we just need to send out the last email whatever happens
    $emailContent.='<a href="'.$subscriptionSettingsPageUrl.'?user_id='.$previousUserId.'&warehouse_url='.url::base().'">Click here to update your subscription settings.</a><br/><br/>';
    send_out_user_email($db,$emailContent,$previousUserId,$notificationIds,$email_config);
    $emailSentCounter++;
    //Save the maximum notification id against the jobs we are going to run now, so we know that we have done the notifications up to that id and next time the jobs are run
    //they only need to work with notifications later than that id.
    //Also set the date/time the job was run
    update_last_run_metadata($db, $frequenciesToRun);
    if ($emailSentCounter==0)
      echo 'No new notification emails have been sent.<br/>';
    elseif ($emailSentCounter==1)
      echo '1 new notification email has been sent.<br/>';
    else 
      echo $emailSentCounter.' new notification emails have been sent.<br/>';
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
    $emailContent='<p>' . $defaultUserAddress;
  }
  //Handle if config file present but option is not
  if (empty($emailContent)) {
    $emailContent=$defaultUserAddress;
  }
  $emailContent .= ' '.$notificationToSendEmailsFor['name_to_use'].', </p>';
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
  
  $emailContent.="<p>$topOfEmailBody.</p>";
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
function send_out_user_email($db,$emailContent,$userId,$notificationIds,$email_config) {
  $cc=null;
  $swift = email::connect();
  // Use a transaction to allow us to prevent the email sending and marking of notification as done
  // getting out of step
  try {
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
    //When configured, add a link on the email to the notifications page
    try {
      $notificationsLinkUrl = kohana::config('notification_emails.notifications_page_url');
    }
    //If there is a problem getting the link configuration, then do nothing at all, we can just ignore the link.
    catch (exception $e) {
    }
    if (!empty($notificationsLinkUrl)) {
      try {
        $notificationsLinkText=kohana::config('notification_emails.notifications_page_url_text');
      }
      //Leave variable as empty if exception, then the next "if" statement can pick up empty variable.
      //This works better as it still works if the variable is empty but no exception has been generated (e.g an empty option has been provided by user).
      catch (exception $e) {
      }
      if (empty($notificationsLinkText))
        $notificationsLinkText='Click here to go your notifications page.';
      $emailContent .= '<a href="'.$notificationsLinkUrl.'">'.$notificationsLinkText.'</a></br>';
    }   
    $message = new Swift_Message($emailSubject, $emailContent,
                                 'text/html');
    $recipients = new Swift_RecipientList();
    $recipients->addTo($userResults[0]->email_address);
    // send the email
    $swift->send($message, $recipients, $email_config['address']);
    kohana::log('info', 'Email notification sent to '. $userResults[0]->email_address); 
    //All notifications that have been sent out in an email are marked so we don't resend them
    $db
      ->set('email_sent', 'true')
      ->from('notifications')
      ->in('id', $notificationIds)
      ->update();
    //As Verifier Tasks need to be actioned, we don't auto acknowledge them
    $db
      ->set('acknowledged', 't')
      ->from('notifications')
      ->where("source_type != 'VT'")
      ->in('id', $notificationIds)
      ->update();
  } catch (Exception $e) {
    $db->rollback();
    throw $e;
  }
}
?>