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
 * @package	Milestones
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	https://github.com/indicia-team/warehouse/
 */

/*
 * Tell the system that we need the occdelta table to find out which occurrences have been created/changed recently.
 */
function milestones_metadata() {
  return array(
    'always_run'=>TRUE, // sample checks may not have occurrence updates.
    'requires_occurrences_delta'=>TRUE
  );
}

/**
 * Add Milestones tab to the websites edit page.
 */
function milestones_extend_ui() {
  return array(array(
    'view'=>'website/website_edit',
    'type'=>'tab',
    'controller'=>'milestone/index',
    'title'=>'Milestones',
    'allowForNew' => false
  ));
}

/**
 * Hook to ORM enable the relationship between websites and milestones.
 */
function milestones_extend_orm() {
  return array('website'=>array(
    'has_many'=>array('milestones')
  ));
}

/**
 * Hook to data services to allow milestones to be exposed.
 */
function milestones_extend_data_services() {
  return array(
    'milestones'=>array()
  );
}

/**
 * Get a list of distinct user/website combinations and the occurrence/taxon count milestones that each combination will need testing for
 * (these are milestones associated with each website where the milestone has not been
 * awarded yet for that user)
 * Also collect details for the filter associated with the milestone (this is a 1 to 1 relationship)
 *
 * Only need to take into account user/website combinations for occurrences that have been created/verified since the last time the scheduled task was run
 * as only these will have passed a new milestone.
 */
function get_user_website_combinations_with_unawarded_milestones_for_changed_occurrences($db) {
  $usersWebsiteCombos = $db->query("
    SELECT DISTINCT co.created_by_id, co.website_id, u.username, f.id,f.definition, m.id as milestone_id, m.count,
        m.awarded_by, m.entity as milestone_entity, m.success_message, m.send_to_user, m.admin_emails, m.title,
        p.first_name||' '||p.surname as name
    FROM occdelta co
    JOIN milestones m ON m.website_id=co.website_id and m.deleted=false AND (m.entity = 'T' OR m.entity='O')
    LEFT JOIN milestone_awards ma ON ma.milestone_id = m.id AND ma.user_id=co.created_by_id  AND ma.deleted=false
    JOIN filters f ON f.id=m.filter_id
    JOIN users u ON u.id=co.created_by_id
    JOIN people p ON p.id=u.person_id
    LEFT JOIN groups_users gu ON gu.group_id=m.group_id AND gu.user_id=u.id AND gu.deleted=false
    WHERE ma.id IS NULL AND (m.group_id IS NULL OR gu.id IS NOT NULL)
    ")->result_array(false);
  return $usersWebsiteCombos;
}

/**
 * Get a list of distinct user/website combinations and the media count milestones that each combination will need testing for
 * (these are milestones associated with each website where the milestone has not been
 * awarded yet for that user)
 * Also collect details for the filter associated with the milestone (this is a 1 to 1 relationship)
 *
 * Only need to take into account user/website combinations for occurrence_media that have been created (or the occurrence itself verified) since the last time the scheduled task was run
 * as only these will have passed a new milestone.
 *
 *
 */
function get_user_website_combinations_with_unawarded_milestones_for_changed_occ_media($db) {
  $usersWebsiteCombos = $db->query("
    SELECT DISTINCT om.created_by_id, co.website_id, u.username, f.id, f.definition, m.id as milestone_id, m.count,
        m.awarded_by, m.entity as milestone_entity, m.success_message, m.send_to_user, m.admin_emails, m.title,
        p.first_name||' '||p.surname as name
    FROM cache_occurrences_functional co
    JOIN occurrence_media om on om.occurrence_id=co.id AND om.deleted=false
    JOIN system s on (om.created_on > s.last_scheduled_task_check or co.verified_on > s.last_scheduled_task_check) AND s.name = 'milestones'
    JOIN milestones m on m.website_id=co.website_id and m.deleted=false AND m.entity = 'M'
    LEFT JOIN milestone_awards ma on ma.milestone_id = m.id AND ma.user_id=om.created_by_id AND ma.deleted=false
    JOIN filters f on f.id=m.filter_id
    JOIN users u on u.id=co.created_by_id
    JOIN people p ON p.id=u.person_id
    LEFT JOIN groups_users gu on gu.group_id=m.group_id AND gu.user_id=u.id AND gu.deleted=false
    WHERE ma.id IS null AND (m.group_id is null OR gu.id is not null)
    ")->result_array(false);
  return $usersWebsiteCombos;
}

/**
 * Get a list of distinct user/website combinations and the sample count milestones that each combination will need
 * testing for (these are milestones associated with each website where the milestone has not been awarded yet for that
 * user)
 * Also collect details for the filter associated with the milestone (this is a 1 to 1 relationship)
 *
 * Only need to take into account user/website combinations for samples that have been created since the last time the
 * scheduled task was run as only these will have passed a new milestone.
 */
function get_user_website_combinations_with_unawarded_milestones_for_changed_samples($db) {
  // need the interval, as the times don't quite line up, and some data may be missed.
  $usersWebsiteCombos = $db->query("
    SELECT DISTINCT s.created_by_id, su.website_id, u.username, f.id, f.definition, m.id as milestone_id, m.count,
        m.awarded_by, m.entity as milestone_entity, m.success_message, m.send_to_user, m.admin_emails, m.title,
        p.first_name||' '||p.surname as name
    FROM samples s
    JOIN surveys su ON s.survey_id = su.id
    JOIN milestones m ON m.website_id=su.website_id and m.deleted=false AND m.entity = 'S'
    LEFT JOIN milestone_awards ma ON ma.milestone_id = m.id AND ma.user_id=s.created_by_id AND ma.deleted=false
    JOIN filters f ON f.id=m.filter_id
    JOIN users u ON u.id=s.created_by_id
    JOIN people p ON p.id=u.person_id
    LEFT JOIN groups_users gu ON gu.group_id=m.group_id AND gu.user_id=u.id AND gu.deleted=false
    JOIN system sys on (s.created_on > (sys.last_scheduled_task_check - '1 hour'::interval)) AND sys.name = 'milestones'
    WHERE ma.id IS NULL AND (m.group_id IS NULL OR gu.id IS NOT NULL)
      AND s.parent_id IS NULL AND s.deleted = FALSE
    ")->result_array(false);
  return $usersWebsiteCombos;
}


/**
 * When the scheduled task is run, we need to send a notification to all users who have passed a new milestone
 */
function milestones_scheduled_task($last_run_date, $db) {
  //Get a list of distinct user/website combinations and  milestones that each combination will need testing for,
  //these are milestones associated with each website the user is associated with, where the milestone has not been
  //awarded yet)
  $occurrenceMilestonesToCheck = get_user_website_combinations_with_unawarded_milestones_for_changed_occurrences($db);
  $mediaMilestonesToCheck = get_user_website_combinations_with_unawarded_milestones_for_changed_occ_media($db);
  $sampleMilestonesToCheck = get_user_website_combinations_with_unawarded_milestones_for_changed_samples($db);
  
  //Supply a config of which websites to take into account.
  try {
    $website_ids=kohana::config('milestones.website_ids');
  //Handle config file not present
  } catch (Exception $e) {
    $website_ids=array();
  }
  //handle if config file present but option is not supplied
  if (empty($website_ids))
    $website_ids=array();
  //Supply 1 as the user id to give the code maximum privileges
  $reportEngine = new ReportEngine($website_ids, 1);
  $notificationCount=0;
  //Cycle through all the occurrence media milestones that haven't been awarded yet and could potentially need to be awarded since last run.
  foreach ($mediaMilestonesToCheck as $milestoneToCheck) {
    $report = 'library/occurrences/filterable_occurrence_media_counts_per_user_website';

    $params = json_decode($milestoneToCheck['definition'],true);
    $params['user_id'] = $milestoneToCheck['created_by_id'];
    $params['website_id'] = $milestoneToCheck['website_id'];

    try {
      //Get the report data for all new occurrences that match the filter,user,website.
      $data=$reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
    } catch (Exception $e) {
      echo $e->getMessage();
      error_logger::log_error('Error occurred when creating verification notifications based on new occurrences and user\'s filters.', $e);
    }
    foreach($data['content']['records'] as $milestoneCountData) {
      if ($milestoneCountData['count']>=$milestoneToCheck['count']) {
        create_milestone_reached_notification($milestoneToCheck);
        $notificationCount++;
      }
    }
  }
  //Cycle through all the occurrence taxa/occurrence milestones that haven't been awarded yet and could potentially need to be awarded since the last run
  foreach ($occurrenceMilestonesToCheck as $milestoneToCheck) {
    if ($milestoneToCheck['milestone_entity']=='T')
      $report = 'library/occurrences/filterable_taxa_counts_per_user_website';
    else
      $report = 'library/occurrences/filterable_occurrence_counts_per_user_website';

    $params = json_decode($milestoneToCheck['definition'],true);
    $params['user_id'] = $milestoneToCheck['created_by_id'];
    $params['website_id'] = $milestoneToCheck['website_id'];

    try {
      //Get the report data for all new occurrences that match the filter/user/website
      $data=$reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
    } catch (Exception $e) {
      echo $e->getMessage();
      error_logger::log_error('Error occurred when creating verification notifications based on new occurrences and user\'s filters.', $e);
    }
    foreach($data['content']['records'] as $milestoneCountData) {
      if ($milestoneCountData['count']>=$milestoneToCheck['count']) {
        create_milestone_reached_notification($milestoneToCheck);
        $notificationCount++;
      }
    }
  }
  //Cycle through all the sample milestones that haven't been awarded yet and could potentially need to be awarded since the last run
  foreach ($sampleMilestonesToCheck as $milestoneToCheck) {
    $report = 'library/samples/filterable_sample_counts_per_user_website';
                  
    $params = json_decode($milestoneToCheck['definition'],true);
    $params['user_id'] = $milestoneToCheck['created_by_id'];
    $params['website_id'] = $milestoneToCheck['website_id'];

    try {
      //Get the report data for all new samples that match the filter/user/website
      $data=$reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
    } catch (Exception $e) {
       echo $e->getMessage();
       error_logger::log_error('Error occurred when creating verification notifications based on new samples and user\'s filters.', $e);
    }
    foreach($data['content']['records'] as $milestoneCountData) {
      if ($milestoneCountData['count']>=$milestoneToCheck['count']) {
        create_milestone_reached_notification($milestoneToCheck);
        $notificationCount++;
      }
    }
  }
  if ($notificationCount==0)
    echo 'No new milestone notifications have been created.</br>';
  elseif ($notificationCount==1)
    echo '1 new milestone notification has been created.</br>';
  else
    echo $notificationCount.' new milestone notifications have been created.</br>';
}

/**
 * Send the notification to say the award has been made.
 */
function create_milestone_reached_notification($milestoneToCheck) {
  if ($milestoneToCheck['send_to_user'] !== 'f') {
    $notificationObj = ORM::factory('notification');
    $notificationObj->source='milestones';
    $notificationObj->triggered_on=date("Ymd H:i:s");
    $notificationObj->user_id=$milestoneToCheck['created_by_id'];
    $notificationObj->source_type='M';
    $notificationObj->data=json_encode(
      array('comment'=>$milestoneToCheck['success_message'],
          'auto_generated'=>'t',
          'username'=>$milestoneToCheck['awarded_by']));
    $notificationObj->save();
  }

  if (!empty($milestoneToCheck['admin_emails'])) {
    $email_config = Kohana::config('email');
    if (array_key_exists ('do_not_send' , $email_config) and $email_config['do_not_send']) {
      kohana::log('info', 'Email configured for do_not_send: Milestone reached : '.$milestoneToCheck['title'].' : '.$milestoneToCheck['name']);
    } else {
      $swift = email::connect();
      try
      {
        $message = new Swift_Message(
            'Milestone reached : '.$milestoneToCheck['title'].' : '.$milestoneToCheck['name'],
            '<p>'.$milestoneToCheck['name'].' has reached the milestone &quot;'.$milestoneToCheck['title'].'&quot;</p>',
            'text/html'
            );
        $sendTo = explode(',', $milestoneToCheck['admin_emails']);
        $recipients = new Swift_RecipientList();
        foreach($sendTo as $adminRecipient) {
          $recipients->addTo($adminRecipient, $adminRecipient);
        }
        $swift->send($message, $recipients, $email_config['address']);
      }
      catch (Swift_Exception $e)
      {
        kohana::log('error', "Error sending milestone email: " . $e->getMessage());
        throw new Kohana_User_Exception('swift.general_error', $e->getMessage());
      }
    }
  }
  
  //Save that the Milestone has been awarded so users are not awarded more than once.
  $awardObj = ORM::factory('milestone_award');
  $awardObj->milestone_id=$milestoneToCheck['milestone_id'];
  $awardObj->user_id=$milestoneToCheck['created_by_id'];
  $awardObj->created_on=date("Ymd H:i:s");
  $awardObj->updated_on=date("Ymd H:i:s");
  $awardObj->created_by_id=$milestoneToCheck['created_by_id'];
  $awardObj->updated_by_id=$milestoneToCheck['created_by_id'];
  $awardObj->save();
}