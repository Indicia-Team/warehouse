<?php

/**
 * @file
 * Plugin functions for the verifier notifications module.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Return plugin metadata for the verifier notificaitons module.
 */
function verifier_notifications_metadata() {
  return [
    'requires_occurrences_delta' => TRUE,
    'always_run' => verifier_notifications_use_workflow_module(),
  ];
}

/**
 * Scheduled task hook.
 *
 * Module sends out VT (verifier tasks) and PT (Pending Record Tasks)
 * notifications which alert users when they have records to verify or records
 * at release_status "P" (pending) to check. If the Workflow module is enabled
 * then we also send out notifications if a record has not been verified yet
 * and that verification check is overdue.
 * Example use -
 * If a mentor needs to check the records of a student then that student's
 * records can be set to "P" release status.
 * This module can then pick up these records and automatically send the
 * notifications to the mentor.
 */
function verifier_notifications_scheduled_task($last_run_date, $db) {
  // Create a copy of the occdelta table but with the sample geometry precise.
  $qry = <<<SQL
    DROP TABLE IF EXISTS occdelta_precise;
    CREATE TEMPORARY TABLE occdelta_precise AS
    SELECT * FROM occdelta;
    UPDATE occdelta_precise o
    SET public_geom=s.geom
    FROM samples s
    WHERE s.id=o.sample_id;

    CREATE INDEX ix_occdelta_precise_taxa_taxon_list_id on occdelta_precise(taxa_taxon_list_id);
    CREATE INDEX ix_occdelta_precise_taxa_taxon_list_external_key on occdelta_precise(taxa_taxon_list_external_key);
    CREATE INDEX ix_occdelta_precise_public_geom on occdelta_precise USING gist(public_geom);
  SQL;
  $params = [
    'notificationSourceType' => 'PT',
    'notificationSource' => 'pending_record_check_notifications',
    'linkText' => 'You have pending records to check for {1}.',
    'sharingFilter' => 'M',
    'sharingFilterFullName' => 'moderation',
    'noNotificationsCreatedMessage' => 'No new pending record check notifications have been created.',
    'oneNotificationCreatedMessage' => 'new pending record check notification has been created.',
    'multipleNotificationsCreatedMessage' => 'new pending record check notifications have been created.',
  ];
  verifier_notifications_process_task_type('moderation', $params, $db);
  $params = [
    'notificationSourceType' => 'VT',
    'notificationSource' => 'verifier_notifications',
    'linkText' => 'You have new or updated records to verify for {1}.',
    'sharingFilter' => 'V',
    'sharingFilterFullName' => 'verification',
    'noNotificationsCreatedMessage' => 'No new verification notifications have been created.',
    'oneNotificationCreatedMessage' => 'new verification notification has been created.',
    'multipleNotificationsCreatedMessage' => 'new verification notifications have been created.',
  ];
  verifier_notifications_process_task_type('verification', $params, $db);
  if (verifier_notifications_use_workflow_module()) {
    $config = kohana::config('workflow_groups', FALSE, FALSE);
    if ($config) {
      foreach (array_keys($config['groups']) as $group) {
        verifier_notifications::processOverdueVerifications($db, $group, $last_run_date);
      }
    }
  }
}

/**
 * Check if the workflow module enabled.
 *
 * @return bool
 *   Returns true if the workflow module enabled.
 */
function verifier_notifications_use_workflow_module() {
  try {
    $modules = kohana::config('config.modules');
    $useWorkflowModule = in_array(MODPATH . 'workflow', $modules);
  }
  catch (Exception $ex) {
    $useWorkflowModule = FALSE;
  }
  return $useWorkflowModule;
}

/**
 * Retrieves the configured list of URLs pointing to pages for a task type.
 *
 * @param string $type
 *   Task type, e.g. verification.
 *
 * @return array
 *   List of configured URLs.
 */
function verifier_notification_urls_for_task_type($type) {
  try {
    $urls = kohana::config("verifier_notifications.{$type}_urls");
    if (!$urls) {
      $urls = [];
    }
  }
  catch (Exception $e) {
    // Config file not present.
    $urls = [];
  }
  return $urls;
}

/**
 * Process the notifications required for a task type.
 *
 * @param string $type
 *   Type of task, either moderation or verification.
 * @param array $params
 *   Parameters array defining the type name and messages.
 * @param object $db
 *   Database connection object.
 */
function verifier_notifications_process_task_type($type, array $params, $db) {
  $urls = verifier_notification_urls_for_task_type($type);
  // Loop through the known moderation/verification pages on each website.
  foreach ($urls as $url) {
    $urlParams = array_merge($params, $url);
    acknowledge_stale_notifications($db, $urlParams);
    // Get all filters where the user for the filter does not already have an
    // unacknowledged notification of that type and the user is associated
    // with the website of the moderation page.
    $filters = get_filters_without_existing_notification($db, $urlParams);
    // Fire the notifications for records matching these filters.
    loop_through_workflows_and_filters_and_create_notifications($db, $filters, $urlParams);
  }
}

/**
 * Acknowledge stale verifier notifications so they are re-sent.
 *
 * If a verifier receives a notification, then forgets about it and doesn't
 * acknowledge it, they may not realise their verification record pool is
 * growing. Avoid this by acknowledging these stale notifications so they are
 * sent out again.
 *
 * @param object $db
 *   Database connection.
 * @param array $params
 *   Parameters defining the notification type.
 */
function acknowledge_stale_notifications($db, array $params) {
  // Define a date after which we consider a notification still "fresh" - 1 day
  // ago.
  $date = new DateTime();
  $date->sub(new DateInterval('P1D'));
  $freshDate = $date->format('c');
  // Automatically refresh stale notifications so they don't build up.
  $sql = <<<SQL
    UPDATE notifications n SET acknowledged=true
    FROM filters f
    JOIN filters_users AS fu ON (fu.filter_id = f.id)
    JOIN users AS u ON (u.id = fu.user_id)
    JOIN users_websites AS uw ON (uw.user_id = u.id)
    WHERE f.sharing = ?
    AND f.defines_permissions = 't'
    AND uw.website_id = ?
    AND f.deleted = 'f'
    AND fu.deleted = 'f'
    AND u.deleted = 'f'
    AND n.user_id=fu.user_id
    AND n.source_type=?
    AND n.source=?
    AND n.acknowledged=false
    AND n.linked_id is null
    AND n.triggered_on<=?
SQL;
  $db->query($sql, [
    $params['sharingFilter'],
    $params['website_id'],
    $params['notificationSourceType'],
    $params['notificationSource'],
    $freshDate,
  ]);
}

/**
 * Retrieve filters that detect changes which have not already been notified.
 *
 * Get all filters where the user for the filter does not already have a
 * "fresh" notification of the type we are interested in (the source is
 * different on the overdue notification). A "fresh" notification is considered
 * as any triggered in the last 24 hours.
 *
 * @param object $db
 *   Database connection object.
 * @param array $params
 *   Parameters array defining the type name and messages.
 *
 * @return array
 *   List of filters
 */
function get_filters_without_existing_notification($db, array $params) {
  $filters = $db
    ->select('DISTINCT f.id, f.definition, fu.user_id, u.username')
    ->from('filters f')
    ->join('filters_users as fu', 'fu.filter_id', 'f.id')
    ->join('users as u', 'u.id', 'fu.user_id')
    ->join('users_websites as uw', 'uw.user_id', 'u.id')
    ->join('notifications as n', "(n.user_id=fu.user_id and n.source_type='$params[notificationSourceType]' " .
      "and n.source='$params[notificationSource]' and n.acknowledged=false and n.linked_id is null " .
      // Only include notifications that link to this verification page.
      'and n.data like \'%<a href=\\\\"' . str_replace('/', '\\\\/', $params['url']) . '\\\\"%\')', '', 'LEFT')
    ->where([
      'f.sharing' => $params['sharingFilter'],
      'f.defines_permissions' => 't',
      'n.id' => NULL,
      'uw.website_id' => $params['website_id'],
      'f.deleted' => 'f',
      'fu.deleted' => 'f',
      'u.deleted' => 'f',
    ])
    ->get()->result_array(FALSE);
  return $filters;
}

/**
 * Create notifications for each filter.
 *
 * Cycle each filter and check if there if there is a notification that needs
 * creating.
 */
function loop_through_workflows_and_filters_and_create_notifications($db, $filters, $params) {
  $forceHighPriorityEmail = FALSE;
  $notificationCounter = 0;
  // Supply 1 as the user id to give the code maximum privileges. Also force
  // the main database connection to allow access to the temporary occdelta
  // table.
  $reportEngine = new ReportEngine([$params['website_id']], 1, $db);
  // When creating notifications keep a track of users we have created
  // notifications for per verification page, this allows us to avoid creating
  // multiple notifications per user without having to check the database.
  $alreadyCreatedNotifications = [];
  // Go through each filter for users who don't have an outstanding
  // notification of the required type.
  foreach ($filters as $filter) {
    $extraParams = ['sharing' => $params['sharingFilterFullName']];
    if ($params['notificationSourceType'] === 'VT') {
      // Only look for completed record_status, we don't want to pick up V for
      // instance, as these records are already verified. Also include any
      // release status and any confidential status for verification purposes.
      $extraParams = array_merge($extraParams, [
        'record_status' => 'C',
        'release_status' => 'A',
        'confidential' => 'all',
      ]);
    }
    else {
      // If we are only interested in detecting Pending records then provide a
      // release_status P parameter, this will  override the release_status R
      // parameter that automatically appears in the report.
      $extraParams = array_merge($extraParams, ['release_status' => 'P']);
    }
    // Allow params override from URL configuration.
    if (isset($params['extraParams'])) {
      $extraParams = array_merge($extraParams, $params['extraParams']);
    }
    $reportParams = json_decode($filter['definition'], TRUE) + $extraParams;
    try {
      // Don't run the filter unless we we haven't already created a
      // notification for that user.
      if (!in_array($filter['user_id'], $alreadyCreatedNotifications)) {
        // Get the report data.
        // Use the filter as the params.
        $reportOutput = $reportEngine->requestReport("library/occdelta_precise/filterable_occdelta_count.xml", 'local', 'xml', $reportParams);
      }
      // If applicable records are returned then create notification.
      if (!empty($reportOutput) && $reportOutput['content']['records'][0]['count'] > 0) {
        // Save the new notification.
        save_notification($filter['user_id'], $params, $forceHighPriorityEmail);
        $notificationCounter++;
        $alreadyCreatedNotifications[] = $filter['user_id'];
      }
    }
    catch (Exception $e) {
      echo $e->getMessage();
      error_logger::log_error('Error occurred when creating ' . $params['notificationSource'], $e);
    }
  }
  // Display message to show how many notifications were created.
  if ($notificationCounter == 0) {
    echo $params['noNotificationsCreatedMessage'] . '</br>';
  }
  elseif ($notificationCounter == 1) {
    echo $notificationCounter . ' ' . $params['oneNotificationCreatedMessage'] . '</br>';
  }
  else {
    echo $notificationCounter . ' ' . $params['multipleNotificationsCreatedMessage'] . '</br>';
  }
}

/**
 * Saves a notification to the database.
 *
 * @param int $userId
 *   User ID.
 * @param array $params
 *   Parameters array defining the type name and messages.
 * @param bool $forceHighPriorityEmail
 *   True if the email associated with this notification needs to be sent high
 *   priority.
 */
function save_notification($userId, array $params, $forceHighPriorityEmail) {
  // Save the new notification.
  $notificationObj = ORM::factory('notification');
  // For overdue notifications the source field is different even though they
  // both use VT type.
  $notificationObj->source = $params['notificationSource'];
  $notificationObj->acknowledged = 'false';
  $notificationObj->triggered_on = date("Ymd H:i:s");
  $notificationObj->user_id = $userId;
  // Use VT "Verifier Task" or PT "Pending Record Task" notification type as we
  // are informing the verifier that they need to perform a task.
  $notificationObj->source_type = $params['notificationSourceType'];
  $linkText = str_replace('{1}', $params['title'], $params['linkText']);
  $notificationObj->data = json_encode([
    'username' => $params['title'],
    'comment' => "<a href=\"$params[url]\">$linkText</a>",
    'auto_generated' => 't',
  ]);
  if ($forceHighPriorityEmail === TRUE) {
    $notificationObj->escalate_email_priority = 2;
  }
  $notificationObj->save();
}
