<?php

/**
 * @file
 * Plugin for the notification emails warehouse module.
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
 * Metadata for the notification emails plugin.
 *
 * @return array
 *   Metadata.
 */
function notification_emails_metadata() {
  return [
    // Set to a high weight to ensure that the notification emails plugin runs
    // last.
    'weight' => 1001,
  ];
}

/**
 * Hook ORM to enable the relationship from users to notification settings.
 */
function notification_emails_extend_orm() {
  return [
    'user' => [
      'has_many' => ['user_email_notification_settings'],
    ],
    'website' => [
      'has_many' => ['website_email_notification_settings'],
    ],
  ];
}

function notification_emails_extend_ui() {
  return [
    [
      'view' => 'website/website_edit',
      'type' => 'tab',
      'controller' => 'website_email_notification_setting',
      'title' => 'Notification email defaults',
      'allowForNew' => FALSE,
    ],
  ];
}

/**
 * Implements the alter_menu hook.
 *
 * Adds an admin page for email queue monitoring when send rate limiting is
 * enabled.
 *
 * @param array $menu
 *   Existing menu structure.
 * @param Auth $auth
 *   Authorization object.
 *
 * @return array
 *   Altered menu structure.
 */
function notification_emails_alter_menu(array $menu, Auth $auth) {
  if (!($auth->logged_in('CoreAdmin'))) {
    return $menu;
  }
  if (!(kohana::config('email.enable_send_rate_limit') ?? FALSE)) {
    return $menu;
  }
  if (!isset($menu['Admin']) || !is_array($menu['Admin'])) {
    $menu['Admin'] = [];
  }
  $menu['Admin']['Email queue'] = 'email_queue';
  return $menu;
}

/**
 * Implements the extend_data_services hook.
 *
 * Determines the data entities which should be added to those available via
 * data services.
 *
 * @return array
 *   List of database entities exposed by this plugin with configuration.
 */
function notification_emails_extend_data_services() {
  // No need to expose website_email_notification_settings as it's only used
  // internally.
  return [
    'user_email_notification_settings' => [
      'allow_full_access' => TRUE,
    ],
  ];
}

/**
 * Implements the scheduled_task plugin hook.
 *
 * When the scheduled tasks are run, send out emails to users with details of
 * their notifications based on their subscription settings (e.g. they could
 * be setup to receive emails about species alerts on a weekly basis).
 *
 * @param string $last_run_date
 *   Timestamp when this scheduled task process was last initiated.
 * @param object $db
 *   Database access object.
 */
function notification_emails_scheduled_task($last_run_date, $db) {
  // We need to first determine which jobs to run, for instance, if it is less
  // than a week since the last weekly job was run, then the weekly job doesn't
  // need running yet.
  $frequenciesToRun = notification_emails::getFrequenciesToRunNow($db);
  // Don't do anything if there are no jobs to run.
  if (!empty($frequenciesToRun[0])) {
    run_email_notification_jobs($db, $frequenciesToRun);
  }
  else {
    echo 'There are no email notification jobs to run at the moment.<br/>';
  }
}

/**
 * Send out the notification emails.
 *
 * @param object $db
 *   Database connection object.
 * @param array $frequenciesToRun
 *   List of notification frequencies we are processing this time round.
 */
function run_email_notification_jobs($db, array $frequenciesToRun) {
  $subscriptionSettingsPageUrl = url::base() . 'subscription_settings.php';
  $maxNotificationsPerEmail = 100;
  $queryChunkSize = 500;
  $allowedFrequencies = ['IH', 'D', 'W'];
  $frequencyToRun = [];
  // Gather all the notification frequency jobs we need to run into a set
  // ready to pass into sql.
  foreach ($frequenciesToRun as $frequencyToRunArray) {
    if (!empty($frequencyToRunArray['notification_frequency'])
      && in_array($frequencyToRunArray['notification_frequency'], $allowedFrequencies, TRUE)) {
      $frequencyToRun[] = "'" . $frequencyToRunArray['notification_frequency'] . "'";
    }
  }
  if (empty($frequencyToRun)) {
    echo 'There are no email notification jobs to run at the moment.<br/>';
    return;
  }
  $frequencyToRunString = implode(',', $frequencyToRun);
  try {
    $modules = kohana::config('config.modules');
    $useWorkflowModule = in_array(MODPATH . 'workflow', $modules);
  }
  catch (Exception $ex) {
    $useWorkflowModule = FALSE;
  }
  // Get all the notifications that need sending.
  // These are either ones where a user has a notification setting that
  // matches the notification and frequency run we are about to do, or
  // alternatively if it a high priority species (e.g. red alert invasive)
  // defined by verifier_notifications_immediate=true then makes sure the
  // notification is sent as part of the immediate/hourly batch no matter what
  // the frequency is for on the verifier's notification setting for that
  // source type.
  $notificationsToSendEmailsForSql = <<<SQL
    SELECT distinct n.id, n.user_id, n.source_type, n.source, n.linked_id, n.data, n.escalate_email_priority, u.username,
          CASE WHEN n.escalate_email_priority IS NULL THEN -1 ELSE n.escalate_email_priority END AS sort_priority,
          CASE
            WHEN unf.notification_frequency = 'IH' THEN 3
            WHEN unf.notification_frequency = 'D' THEN 2
            WHEN unf.notification_frequency = 'W' THEN 1
            ELSE 0
          END AS sort_frequency_priority,
          coalesce(p.first_name, u.username) as name_to_use, cof.website_id, cof.query
    FROM notifications n
    JOIN users u ON u.id = n.user_id AND u.deleted=false
    JOIN people p ON p.id = u.person_id AND p.deleted=false
    LEFT JOIN cache_occurrences_functional cof on cof.id=n.linked_id
    --This part just deals with the normal situation where we include a notification email if the user has a setting that
    -- matches the current run or has its escalate_email_priority set (so we always send immediately).
    LEFT JOIN user_email_notification_settings unf ON unf.notification_source_type=n.source_type
      AND unf.user_id = n.user_id
      AND (unf.notification_frequency in ($frequencyToRunString) OR n.escalate_email_priority IS NOT NULL)
      AND unf.deleted='f'
    LEFT JOIN user_email_notification_frequency_last_runs unflr ON unf.notification_frequency=unflr.notification_frequency

  SQL;
  if ($useWorkflowModule === TRUE) {
    $notificationsToSendEmailsForSql .= <<<SQL
      -- If there is a species that needs sending immediately, make sure the task is a verification task and the user
      -- has a notification setting (although we don't care what the frequency is of the setting) and then
      -- if the current run is immediate/hourly then include the notification in the run
      LEFT JOIN workflow_metadata wm ON 'IH' IN ($frequencyToRunString)
        AND lower(wm.entity)='occurrence'
        AND lower(wm.key)='taxa_taxon_list_external_key'
        AND (wm.key_value=cof.taxa_taxon_list_external_key AND cof.taxa_taxon_list_external_key IS NOT NULL)
        AND wm.verifier_notifications_immediate=true
        AND wm.deleted=false
      LEFT JOIN user_email_notification_settings unfMetaDataLinked ON unfMetaDataLinked.notification_source_type=n.source_type
        AND n.source_type = 'VT'
        AND unfMetaDataLinked.user_id = n.user_id
        AND unfMetaDataLinked.deleted='f'
      LEFT JOIN user_email_notification_frequency_last_runs unflrMetaDataLinked ON unflrMetaDataLinked.notification_frequency='IH'

    SQL;
  }
  $idFilterQuery = $db->query(
    'SELECT MIN(last_max_notification_id) as last_id FROM user_email_notification_frequency_last_runs'
  )->result_array(FALSE);
  $newIdFilter = $idFilterQuery[0]['last_id'] === NULL ? '' : 'AND n.id>' . $idFilterQuery[0]['last_id'];
  $notificationsToSendEmailsForSql .= <<<SQL
    WHERE n.email_sent = 'f' AND n.source_type<>'T' AND n.acknowledged = 'f' $newIdFilter
    --Send a notification if the user has a notification setting and notification that matches the current run
    --and the notification hasn't already been set (or nothing has ever been sent)
    AND (
      (unf.id IS NOT NULL AND (
        n.id>unflr.last_max_notification_id OR unflr.last_max_notification_id IS NULL OR unflr.id IS NULL
      ))

  SQL;
  if ($useWorkflowModule === TRUE) {
    $notificationsToSendEmailsForSql .= <<<SQL
      -- Do same for high priority species verification tasks to be automatically included in the immediate hourly run
      OR (wm.id IS NOT NULL AND unfMetaDataLinked.id IS NOT NULL AND (
        n.id>unflrMetaDataLinked.last_max_notification_id OR unflrMetaDataLinked.last_max_notification_id IS NULL or unflrMetaDataLinked.id IS NULL
      ))

    SQL;
  }
  $notificationsToSendEmailsForSql .= <<<SQL
    )

  SQL;
  // Build and process notifications in chunks so we avoid keeping a large
  // DB result set in memory when there are many pending notifications.
  // Get address to send emails from.
  $email_config = [];
  // Try and get from configuration file if possible.
  try {
    $email_config['address'] = kohana::config('notification_emails.email_sender_address');
    $systemName = kohana::config('notification_emails.system_name');
    // Handle config file not present.
  }
  catch (Exception $e) {
    $email_config = Kohana::config('email');
    $systemName = 'System generated';
  }
  // Handle also if config file present but option is not.
  if (!isset($email_config['address'])) {
    echo 'Email address not provided in email configuration or email_sender_address configuration option not provided. I cannot send any emails without a sender address.<br/>';
    return;
  }
  $emailSentCounter = 0;
  $emailQueuedCounter = 0;
  // All the notifications that need to be sent in an email are grouped by
  // user, as we cycle through the notifications then we can track who the
  // user was for the previous notification. When this user id then changes,
  // we know we need to start building an new email to a new user.
  $previousUserId = 0;
  $notificationIds = [];
  $emailContent = '';
  $notificationsInCurrentEmail = 0;
  $currentType = '';
  $sourceTypes = notification_emails::getNotificationTypes();
  $recordStatuses = notification_emails::getRecordStatuses();
  $dataFieldsToOutput = array(
    'username' => 'From',
    'occurrence_id' => 'Record ID',
    'comment' => 'Message',
    'record_status' => 'Record status',
  );
  $emailHighPriority = FALSE;
  $lastSortPriority = NULL;
  $lastSortFrequencyPriority = NULL;
  $lastUserId = NULL;
  $lastSourceType = NULL;
  $lastId = NULL;
  $foundNotifications = FALSE;
  while (TRUE) {
    $chunkSql = $notificationsToSendEmailsForSql;
    if ($lastId !== NULL) {
      // Key based chunking, rather than offset as the latter can get thrown
      // off by the email_sent field being updated for notifications as we go
      // around the loop. We order by the same fields as the main query, so
      // we know that we won't miss any records or get duplicates as we go
      // around the loop.
      $chunkSql .= <<<SQL
        AND (
          (CASE WHEN n.escalate_email_priority IS NULL THEN -1 ELSE n.escalate_email_priority END) < $lastSortPriority
          OR (
            (CASE WHEN n.escalate_email_priority IS NULL THEN -1 ELSE n.escalate_email_priority END) = $lastSortPriority
            AND (
              (CASE
                WHEN unf.notification_frequency = 'IH' THEN 3
                WHEN unf.notification_frequency = 'D' THEN 2
                WHEN unf.notification_frequency = 'W' THEN 1
                ELSE 0
              END) < $lastSortFrequencyPriority
              OR (
                (CASE
                  WHEN unf.notification_frequency = 'IH' THEN 3
                  WHEN unf.notification_frequency = 'D' THEN 2
                  WHEN unf.notification_frequency = 'W' THEN 1
                  ELSE 0
                END) = $lastSortFrequencyPriority
                AND (
                  n.user_id > $lastUserId
                  OR (n.user_id = $lastUserId AND (
                    n.source_type > '$lastSourceType'
                    OR (n.source_type = '$lastSourceType' AND n.id > $lastId)
                  ))
                ))
            )
          )
        )

      SQL;
    }
    $chunkSql .= "ORDER BY CASE WHEN n.escalate_email_priority IS NULL THEN -1 ELSE n.escalate_email_priority END DESC, CASE WHEN unf.notification_frequency = 'IH' THEN 3 WHEN unf.notification_frequency = 'D' THEN 2 WHEN unf.notification_frequency = 'W' THEN 1 ELSE 0 END DESC, n.user_id, n.source_type, n.id\n";
    $chunkSql .= 'LIMIT ' . (int) $queryChunkSize;
    $notificationsToSendEmailsFor = $db->query($chunkSql)->result(FALSE);
    $rowsInChunk = 0;
    foreach ($notificationsToSendEmailsFor as $notificationToSendEmailsFor) {
      $rowsInChunk++;
      $foundNotifications = TRUE;
      if ($previousUserId === 0) {
        $emailContent = start_building_new_email($notificationToSendEmailsFor);
      }
      // If user changes (or we hit the per-email max for the same user), send
      // the email built so far and start a new one.
      $userChanged = ($notificationToSendEmailsFor['user_id'] != $previousUserId && $previousUserId !== 0);
      $emailFullForUser = ($notificationsInCurrentEmail >= $maxNotificationsPerEmail);
      if ($userChanged || $emailFullForUser) {
        if ($currentType !== '') {
          $emailContent .= "</tbody>\n</table>\n";
        }
        $sent = send_out_user_email(
          $db,
          $emailContent,
          $previousUserId,
          $notificationIds,
          $email_config['address'],
          $subscriptionSettingsPageUrl,
          $emailHighPriority
        );
        // Reset tracking ready for the next email (either next user, or next
        // chunk for the same user).
        $emailHighPriority = FALSE;
        $notificationIds = [];
        $notificationsInCurrentEmail = 0;
        if ($sent > 0) {
          $emailSentCounter++;
        }
        else {
          $emailQueuedCounter++;
        }
        $emailContent = start_building_new_email($notificationToSendEmailsFor);
        $currentType = '';
      }

      if ($notificationToSendEmailsFor['escalate_email_priority'] == 2) {
        $emailHighPriority = TRUE;
      }
      if (!empty($notificationToSendEmailsFor['data'])) {
        $record = json_decode($notificationToSendEmailsFor['data'], TRUE);
        // Output a header for the group of notifications of the same type.
        if ($currentType !== $notificationToSendEmailsFor['source_type']) {
          if ($currentType !== '') {
            $emailContent .= "</tbody>\n</table>\n";
          }
          $currentType = $notificationToSendEmailsFor['source_type'];
          $sourceTypeTitle = !empty($sourceTypes[$currentType]['title'])
            ? $sourceTypes[$currentType]['title']
            : $currentType;
          $emailContent .= '<h2>' . html::specialchars($sourceTypeTitle) . '</h2>';
          if (!empty($sourceTypes[$currentType]['description'])) {
            $emailContent .= '<p>' . html::specialchars($sourceTypes[$currentType]['description']) . '</p>';
          }
          $emailContent .= "<table>\n<thead><tr>";
          foreach ($dataFieldsToOutput as $field => $caption) {
            if (isset($record[$field]) || $field === 'query') {
              $emailContent .= "<th>$caption</th>";
            }
          }
          $emailContent .= "</tr>\n</thead>\n<tbody>";
        }
        $emailContent .= '<tr>';
        foreach ($dataFieldsToOutput as $field => $caption) {
          if (isset($record[$field]) || $field === 'query') {
            $value = isset($record[$field]) ? $record[$field] : '';
            // Set a default output, which will be overridden for certain fields.
            $htmlToDisplay = html::specialchars((string) $value);
            if ($field === 'username' && ($record[$field] === 'admin' || $record[$field] === 'system')) {
              $htmlToDisplay = html::specialchars($systemName);
            }
            elseif ($field === 'comment') {
              // Use the raw value as it can contain HTML.
              $htmlToDisplay = '<div style="padding: 4px; border: solid silver 1px; border-radius: 4px;">' .
                nl2br($value) . '</div>';
              // Add a reply link if relevant.
              if (!empty($record['occurrence_id']) && in_array($currentType, ['C', 'V', 'Q'])) {
                $link = notification_emails_hyperlink_id(
                  $notificationToSendEmailsFor['linked_id'],
                  $notificationToSendEmailsFor['website_id'],
                  'click here to add a comment',
                );
                // Only use if this converted to a link successfully (i.e. a
                // record details page available for this website).
                if ($link !== 'click here to add a comment') {
                  $htmlToDisplay .= "&#8617 To reply, $link.<br/><br/>";
                }
              }
            }
            elseif ($field === 'record_status') {
              $statusCode = $record['record_status'] .
                (empty($record['record_substatus']) ? '' : $record['record_substatus']);
              if (isset($recordStatuses[$statusCode])) {
                $htmlToDisplay = $recordStatuses[$statusCode];
                if ($record['record_status'] === 'V') {
                  $htmlToDisplay = '&#10003 ' . $htmlToDisplay;
                }
                elseif ($record['record_status'] === 'R') {
                  $htmlToDisplay = '&#10007 ' . $htmlToDisplay;
                }

              }
            }
            elseif ($field === 'occurrence_id') {
              $htmlToDisplay = notification_emails_hyperlink_id(
                  $record[$field],
                  $notificationToSendEmailsFor['website_id']
              );
            }
            if (empty($htmlToDisplay)) {
              $htmlToDisplay = '';
            }
            $emailContent .= '<td style="padding-right: 1em;">' . $htmlToDisplay . '</td>';
          }
        }
        $emailContent .= '</tr>';
      }
      // Log the notification id so we know that this will have to be set to
      // email_sent in the database for the notification.
      $notificationIds[] = $notificationToSendEmailsFor['id'];
      $notificationsInCurrentEmail++;
      // Update the user_id tracker as we cycle through the notifications.
      $previousUserId = $notificationToSendEmailsFor['user_id'];
      $lastSortPriority = (int) $notificationToSendEmailsFor['sort_priority'];
      $lastSortFrequencyPriority = (int) $notificationToSendEmailsFor['sort_frequency_priority'];
      $lastUserId = (int) $notificationToSendEmailsFor['user_id'];
      $lastSourceType = $db->escape_str($notificationToSendEmailsFor['source_type']);
      $lastId = (int) $notificationToSendEmailsFor['id'];
    }
    // Stop the outer chunk loop once we get a partial chunk.
    if ($rowsInChunk < $queryChunkSize) {
      break;
    }
  }
  if (!$foundNotifications) {
    echo 'There are no email notifications to send at the moment.<br/>';
    return;
  }
  if ($currentType !== '') {
    $emailContent .= "</tbody></table>\n";
  }
  // If we have run out of notifications to send we will have finished going
  // around the loop, so we just need to send out the last email whatever
  // happens.
  $sent = send_out_user_email($db, $emailContent, $previousUserId, $notificationIds, $email_config['address'],
    $subscriptionSettingsPageUrl, $emailHighPriority);
  $emailHighPriority = FALSE;
  if ($sent > 0) {
    $emailSentCounter++;
  }
  else {
    $emailQueuedCounter++;
  }
  // Save the maximum notification id against the jobs we are going to run
  // now, so we know that we have done the notifications up to that id and
  // next time the jobs are run they only need to work with notifications
  // later than that id.
  // Also set the date/time the job was run.
  update_last_run_metadata($db, $frequenciesToRun);
  if ($emailSentCounter == 0 && $emailQueuedCounter == 0) {
    echo 'No new notification emails have been sent.<br/>';
  }
  elseif ($emailSentCounter == 1) {
    echo '1 new notification email has been sent.<br/>';
  }
  elseif ($emailSentCounter > 1) {
    echo $emailSentCounter . ' new notification emails have been sent.<br/>';
  }
  if ($emailQueuedCounter == 1) {
    echo '1 notification email has been queued due to hourly send limit.<br/>';
  }
  elseif ($emailQueuedCounter > 1) {
    echo $emailQueuedCounter . ' notification emails have been queued due to hourly send limit.<br/>';
  }
}

/**
 * Converts a record ID into a hyperlink to the details page.
 *
 * Only does the conversion if a suitable link provided in the configuration.
 *
 * @param int $id
 *   Record ID.
 * @param int $websiteId
 *   Website the record came from.
 * @param string $caption
 *   Optional link caption. If not specified the ID is used.
 *
 * @return string
 *   Hyperlink HTML.
 */
function notification_emails_hyperlink_id($id, $websiteId, $caption = NULL) {
  try {
    $recordDetailsPages = kohana::config('notification_emails.record_details_page_urls');
    // Handle config file not present.
  }
  catch (Exception $e) {
    $recordDetailsPages = [];
  }
  if (!$caption) {
    $caption = $id;
  }
  $safeCaption = html::specialchars((string) $caption);
  $safeTitleId = html::specialchars((string) $id);
  // First look for record details pages from the website the record came from.
  foreach ($recordDetailsPages as $page) {
    if ($page['website_id'] == $websiteId) {
      $url = str_replace('#id#', $id, $page['url']);
      $safeUrl = html::specialchars($url);
      return "<a title=\"View details of record $safeTitleId\" href=\"$safeUrl\">$safeCaption</a>";
    }
  }
  // Record not from any configured record details page's website, but might be
  // from one that it can display records for.
  foreach ($recordDetailsPages as $page) {
    $ids = notification_emails_get_shared_website_list($page['website_id']);
    if (in_array($websiteId, $ids)) {
      $url = str_replace('#id#', $id, $page['url']);
      $safeUrl = html::specialchars($url);
      return "<a title=\"View details of record $safeTitleId\" href=\"$safeUrl\">$safeCaption</a>";
    }
  }
  // If no record details page found, just return the caption as a label.
  return $safeCaption;
}

function notification_emails_get_shared_website_list($websiteId) {
  $tag = "website-share-array-$websiteId";
  $cacheId = "$tag-reporting";
  $cache = Cache::instance();
  if ($cached = $cache->get($cacheId)) {
    return $cached;
  }
  $db = new Database();
  $qry = $db->select('to_website_id')
    ->from('index_websites_website_agreements')
    ->where("receive_for_reporting", 't')
    ->in('from_website_id', $websiteId)
    ->get()->result();
  $ids = [];
  foreach ($qry as $row) {
    $ids[] = $row->to_website_id;
  }
  // Tag all cache entries for this website so they can be cleared together
  // when changes are saved.
  $cache->set($cacheId, $ids, $tag);
  return $ids;
}

/**
 * Create the first part of the email to send.
 *
 * Creates the email part before the list of notifications itself.
 *
 * @param array $notificationToSendEmailsFor
 *   Notification data.
 */
function start_building_new_email(array $notificationToSendEmailsFor) {
  // How do we address the user at the start of the email e.g. Dear user,
  // To user, How is your day going user? Get this from config if available.
  $defaultUserAddress = 'Dear';
  try {
    $emailContent = kohana::config('notification_emails.how_to_address_username');
    // Handle config file not present.
  }
  catch (Exception $e) {
    $emailContent = '<p>' . $defaultUserAddress;
  }
  // Handle if config file present but option is not.
  if (empty($emailContent)) {
    $emailContent = $defaultUserAddress;
  }
  $nameToUse = html::specialchars((string) $notificationToSendEmailsFor['name_to_use']);
  $emailContent .= ' ' . $nameToUse . ', </p>';
  // Some description before the list of notifications.
  $defaultTopOfEmailBody = 'You have the following new notifications.';
  try {
    $topOfEmailBody = kohana::config('notification_emails.top_of_email_body');
    // Handle config file not present.
  }
  catch (Exception $e) {
    $topOfEmailBody = $defaultTopOfEmailBody;
  }
  // Handle if config file present but option is not.
  if (empty($topOfEmailBody)) {
    $topOfEmailBody = $defaultTopOfEmailBody;
  }

  $emailContent .= "<p>$topOfEmailBody</p>";
  return $emailContent;
}

/**
 * Updates metadata relating to the last run.
 *
 * Save the maximum notification id against the jobs we are running now, so we
 * know that we have done the notifications up to that id and next time the
 * jobs are run they only need to work with notifications later than that id.
 * Also set the date/time the job was run.
 */
function update_last_run_metadata($db, $frequenciesToUpdate) {
  $allowedFrequencies = ['IH', 'D', 'W'];
  $newMaxIdData = $db->query("
    SELECT max(n.id) as new_max_notification_id
    FROM notifications n
  ")->result_array(FALSE);
  // Cycle through the frequency jobs we are running this time as only these
  // need updating.
  foreach ($frequenciesToUpdate as $frequencyToUpdate) {
    if (empty($frequencyToUpdate['notification_frequency'])
      || !in_array($frequencyToUpdate['notification_frequency'], $allowedFrequencies, TRUE)) {
      continue;
    }
    $db->query("
      UPDATE user_email_notification_frequency_last_runs
      SET last_run_date=now(),last_max_notification_id=" . $newMaxIdData[0]['new_max_notification_id'] . "
      WHERE notification_frequency='" . $frequencyToUpdate['notification_frequency'] . "'
    ")->result_array(FALSE);
  }
}

/**
 * Actually send the email to the user.
 *
 * @param object $db
 *   Database object.
 * @param string $emailContent
 *   Content to send in the email body.
 * @param int $userId
 *   User's warehouse ID.
 * @param array $notificationIds
 *   List of notification IDs.
 * @param string $emailAddress
 *   Email address to send to.
 * @param string $subscriptionSettingsPageUrl
 *   URL of the subscription settings page to include a link to.
 * @param bool $highPriority
 *   Flag set if the email should be high priority.
 *
 * @return int
 *   1 when sent now, 0 when deferred/queued or not sent.
 */
function send_out_user_email(
    $db,
    $emailContent,
    $userId,
    array $notificationIds,
    $emailAddress,
    $subscriptionSettingsPageUrl,
    $highPriority) {

  $email_config = Kohana::config('email');
  if (array_key_exists('do_not_send', $email_config) && $email_config['do_not_send']) {
    kohana::log('info', "Email configured for do_not_send: ignoring send_out_user_email");
    return 0;
  }
  //AVB note: The warehouse_url param is now redundant and can be removed next time testing is carried out on this page.
  $emailContent .= '<br><a href="' . html::specialchars($subscriptionSettingsPageUrl) . '?user_id=' .
    (int) $userId . '&warehouse_url=' . html::specialchars(url::base()) .
    '">Click here to control which notifications you receive.</a><br/><br/>';
  // Use a transaction to allow us to prevent the email sending and marking of
  // notification as done getting out of step.
  $db->begin();
  try {
    // Get the user's email address from the people table.
    $userResults = $db
      ->select('people.email_address')
      ->from('people')
      ->join('users', 'users.person_id', 'people.id')
      ->where('users.id', $userId)
      ->limit(1)
      ->get();
    if (count($userResults) === 0 || empty($userResults[0]->email_address)) {
      kohana::log('error', 'No recipient email address found for notification user ' . $userId);
      $db->commit();
      return 0;
    }

    $defaultEmailSubject = 'You have new notifications.';
    try {
      $emailSubject = kohana::config('notification_emails.email_subject');
      // Handle config file not present.
    }
    catch (Exception $e) {
      $emailSubject = $defaultEmailSubject;
    }
    if (empty($emailSubject)) {
      $emailSubject = $defaultEmailSubject;
    }
    // When configured, add a link on the email to the notifications page.
    try {
      $notificationsLinkUrl = kohana::config('notification_emails.notifications_page_url');
    }
    // If there is a problem getting the link configuration, then do nothing
    // at all, we can just ignore the link.
    catch (Exception $e) {
    }
    if (!empty($notificationsLinkUrl)) {
      try {
        $notificationsLinkText = kohana::config('notification_emails.notifications_page_url_text');
      }
      // Leave variable as empty if exception, then the next "if" statement can
      // pick up empty variable. This works better as it still works if the
      // variable is empty but no exception has been generated (e.g an empty
      // option has been provided by user).
      catch (Exception $e) {
      }
      if (empty($notificationsLinkText)) {
        $notificationsLinkText = 'Click here to go your notifications page.';
      }
      $emailContent .= '<a href="' . html::specialchars($notificationsLinkUrl) . '">' .
        html::specialchars($notificationsLinkText) . '</a></br>';
    }
    $emailer = new Emailer();
    $emailer->addRecipient($userResults[0]->email_address);
    if ($highPriority === TRUE) {
      $emailer->setPriority(2);
    }
    $emailer->setFrom($emailAddress);
    // Send the email.
    try {
      $sent = $emailer->send($emailSubject, "<html>$emailContent</html>", 'notification_emails');
      if ($sent > 0) {
        kohana::log('info', 'Email notification sent to ' . $userResults[0]->email_address);
        // All notifications that have been sent out in an email are marked so we
        // don't resend them.
        $db
          ->set('email_sent', 't')
          ->from('notifications')
          ->in('id', $notificationIds)
          ->update();
        // As Verifier Tasks, Pending Record Tasks need to be actioned, we don't
        // auto acknowledge them.
        $db
          ->set('acknowledged', 't')
          ->from('notifications')
          ->where("source_type != 'VT' AND source_type != 'PT'")
          ->in('id', $notificationIds)
          ->update();
      }
      else {
        kohana::log('info', 'Email notification deferred for queue replay for user ' . $userId);
      }
      $db->commit();
      return $sent;
    }
    catch (Exception $e) {
      kohana::log('error', 'Failed to send email notification to ' . $userResults[0]->email_address);
      error_logger::log_error('Sending email from notification_emails', $e);
      $db->commit();
      return 0;
    }
  }
  catch (Exception $e) {
    $db->rollback();
    throw $e;
  }

  return 0;
}
