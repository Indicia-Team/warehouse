<?php

/**
 * @file
 * Scheduled tasks controller.
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Scheduled tasks controller.
 *
 * Controller that implements any scheduled tasks, such as checking triggers
 * against the recent records to look for notifications. This controller does
 * not have a user interface, it is intended to be automated on a schedule.
 */
class Scheduled_Tasks_Controller extends Controller {
  private $lastRunDate;
  private $occdeltaStartTimestamp = '';
  private $occdeltaEndTimestamp = '';
  private $occdeltaCount = 0;
  private $pluginMetadata;

  /**
   * Main entry point for scheduled tasks.
   *
   * The index method is the default method called when you access this
   * controller, so we can use this to run the scheduled tasks. Takes an
   * optional URL parameter "tasks", which is a comma separated list of
   * the module names to schedule, plus can contain "notifications" to fire the
   * built in notifications system or "all_modules" to fire every module that
   * declares a scheduled task plugin. If tasks are not specified then
   * everything is run.
   */
  public function index() {
    $tm = microtime(TRUE);
    $this->db = new Database();
    $system = new System_Model();
    $allNonPluginTasks = ['notifications', 'work_queue'];
    if (isset($_GET['tasks'])) {
      $requestedTasks = explode(',', $_GET['tasks']);
      $scheduledPlugins = array_diff($requestedTasks, $allNonPluginTasks);
      $nonPluginTasks = array_intersect($allNonPluginTasks, $requestedTasks);
    }
    else {
      $nonPluginTasks = $allNonPluginTasks;
      $scheduledPlugins = ['all_modules'];
    }
    // Grab the time before we start, so there is no chance of a record coming
    // in while we run that is missed.
    $currentTime = time();
    if (in_array('notifications', $nonPluginTasks)) {
      $this->lastRunDate = $system->getLastScheduledTaskCheck();
      $this->checkTriggers();
      $tmtask = microtime(TRUE) - $tm;
      if ($tmtask > 5) {
        self::msg("Triggers & notifications scheduled task took $tmtask seconds.", 'alert');
      }
    }
    if ($scheduledPlugins) {
      $this->runScheduledPlugins($system, $scheduledPlugins);
    }
    if (in_array('notifications', $nonPluginTasks)) {
      $swift = email::connect();
      $this->doRecordOwnerNotifications($swift);
      $this->doDigestNotifications($swift);
    }
    if (in_array('work_queue', $nonPluginTasks)) {
      $qtm = microtime(TRUE);
      $queue = new WorkQueue();
      $queue->process($this->db);
      $qtm = microtime(TRUE) - $qtm;
      if ($qtm > 10) {
        self::msg("Work queue processing took $qtm seconds.", 'alert');
      }
    }
    // Mark the time of the last scheduled task check, so we can get diffs
    // next time.
    $this->db->update('system', array('last_scheduled_task_check' => "'" . date('c', $currentTime) . "'"), array('id' => 1));
    self::msg("Ok!");
    $tm = microtime(TRUE) - $tm;
    if ($tm > 30) {
      self::msg(
        "Scheduled tasks for " . implode(', ', array_merge($nonPluginTasks, $scheduledPlugins)) . " took $tm seconds.",
        'alert'
      );
    }
  }

  /**
   * Check triggers fired when data changes.
   *
   * Compares any recently entered or edited records with the notifications
   * registered on the system, looking for matches. If found, then the
   * notification's actions are fired.
   */
  protected function checkTriggers() {
    self::msg("Checking triggers");
    kohana::log('info', "Checking triggers");
    // Get a distinct list of all the triggers that have at least one action.
    $result = $this->getTriggerQuery();
    // For each trigger, we need to get the output of the report file which
    // defines the trigger.
    foreach ($result as $trigger) {
      $params = json_decode($trigger->params_json, TRUE);
      $params['date'] = $this->lastRunDate;
      $reportEngine = new ReportEngine();
      try {
        $data = $reportEngine->requestReport($trigger->trigger_template_file . '.xml', 'local', 'xml', $params);
      }
      catch (Exception $e) {
        self::msg($trigger->name . ": " . $e, 'error');
        continue;
      }
      if (!isset($data['content']['records'])) {
        kohana::log('error', 'Error in trigger file ' . $trigger->trigger_template_file . '.xml');
        continue;
      }
      if (count($data['content']['records'] > 0)) {
        $parsedData = $this->parseData($data);
        self::msg($trigger->name . ": " . count($data['content']['records']) . " records found");
        // Note escaping disabled in where clause to permit use of CAST
        // expression.
        $actions = $this->db
          ->select('trigger_actions.type, trigger_actions.param1, trigger_actions.param2, trigger_actions.param3, users.default_digest_mode, people.email_address, users.core_role_id')
          ->from('trigger_actions, users')
          ->join('people', 'people.id', 'users.person_id')
          ->where(array(
            'trigger_id' => $trigger->id,
            'type' => "'E'",
            'users.id' => 'CAST(param1 AS INT)',
            'trigger_actions.deleted' => "'f'",
            'users.deleted' => "'f'",
            'people.deleted' => "'f'",
          ), NULL, FALSE)
          ->get();
        foreach ($actions as $action) {
          if ($action->core_role_id !== 1) {
            // If not a core admin, we will need to do a filter on websites
            // the user has access to.
            $userWebsites = $this->db
              ->select('website_id')
              ->from('users_websites')
              ->where('user_id', $action->param1)
              ->get();
          }

          // Insert data in notifications table, either for the user to
          // manually acknowledge, or for a digest mail to be built.
          // First build a list of data for the user's websites.
          if ($action->core_role_id == 1) {
            // Core admin can see any data.
            $allowedData = $parsedData['websiteRecordData'];
          }
          else {
            $allowedData = array();
            foreach ($userWebsites as $allowedWebsite) {
              if (isset($parsedData['websiteRecordData'][$allowedWebsite->website_id])) {
                $allowedData[$allowedWebsite->website_id] = $parsedData['websiteRecordData'][$allowedWebsite->website_id];
              }
            }
          }
          $digestMode = ($action->param2 === NULL ? $action->default_digest_mode : $action->param2);
          if (count($allowedData) > 0) {
            $this->db->insert('notifications', array(
              'source' => $trigger->name,
              'source_type' => 'T',
              'data' => json_encode(array('headings' => $parsedData['headingData'], 'data' => $allowedData)),
              'user_id' => $action->param1,
              // Use digest mode the user selected for this notification, or
              // their default if not specific.
              'digest_mode' => $digestMode,
              'cc' => $action->param3,
            ));
          }
        }
        $this->doTriggerLogComments(
          $trigger->name,
          ['headings' => $parsedData['headingData'], 'data' => $parsedData['websiteRecordData']]
        );
        $this->doDirectTriggerNotifications(
          $trigger->name,
          ['headings' => $parsedData['headingData'], 'data' => $parsedData['websiteRecordData']],
          $digestMode
        );
      }
    }
  }

  /**
   * Allow trigger reports to log occurrence comments.
   *
   * Where the output of a trigger report includes data in a log_comment
   * column, add this info to the occurrenec_comments table.
   *
   * @param string $triggerName
   *   Name of the trigger which fired.
   * @param array $data
   *   Info to store in the notification including the record from the trigger
   *   report.
   */
  private function doTriggerLogComments($triggerName, array $data) {
    // This only applies if a log_comment and occurrence_id columns in report
    // output.
    if (count($data['data']) === 0 || !in_array('log_comment', $data['headings']) ||
        !in_array('occurrence_id', $data['headings'])) {
      return;
    }
    $logCommentCol = array_search('log_comment', $data['headings']);
    $occIDCol = array_search('occurrence_id', $data['headings']);
    // For each record, check if log_comment is populated, if so, save the
    // comment.
    foreach ($data['data'] as $websiteId => $records) {
      foreach ($records as $record) {
        if (!empty($record[$logCommentCol])) {
          $this->db->insert('occurrence_comments', array(
            'comment' => $record[$logCommentCol],
            'created_by_id' => 1,
            'created_on' => date('Y-m-d H:i:s'),
            'updated_by_id' => 1,
            'updated_on' => date('Y-m-d H:i:s'),
            'occurrence_id' => $record[$occIDCol],
            'generated_by' => 'notifications',
          ));
        }
        else {
          echo 'Skipping as no comment to insert: ' . $record[$occIDCol] . '<br/>';
        }
      }
    }
  }

  /**
   * Direct trigger notifications.
   *
   * If a trigger query specifies notify_user_ids as a column, then this gives
   * a list of user IDs which must be told about the trigger firing.
   *
   * @param string $triggerName
   *   Name of the trigger which fired.
   * @param array $data
   *   Info to store in the notification including the record from the trigger
   *   report.
   * @param string $digestMode
   *   Digest frequency code.
   */
  private function doDirectTriggerNotifications($triggerName, array $data, $digestMode) {
    // This only applies if a notify_user_ids column in report output.
    if (count($data['data']) === 0 || !in_array('notify_user_ids', $data['headings'])) {
      return;
    }
    // Don't want to include the system functionality columns in the
    // notifications themselves.
    $sysCols = ['notify_user_ids', 'log_comment', 'occurrence_id'];
    $colsToRemove = [];
    foreach ($sysCols as $col) {
      if (($colIdx = array_search($col, $data['headings'])) !== FALSE) {
        $colsToRemove[] = $colIdx;
        if ($col === 'notify_user_ids') {
          $notifyUserIdsCol = $colIdx;
        }
      }
    }
    $data['headings'] = array_diff_key($data['headings'], array_flip($colsToRemove));
    // Keep a list of users to notify.
    $userNotifications = [];
    // For each record, attach it to any user that needs to be notified.
    foreach ($data['data'] as $websiteId => &$records) {
      foreach ($records as &$record) {
        $userIds = explode(',', $record[$notifyUserIdsCol]);
        // Clean out system functionality columns.
        $record = array_diff_key($record, array_flip($colsToRemove));
        foreach ($userIds as $userId) {
          if (!isset($userNotifications["user:$userId"])) {
            $userNotifications["user:$userId"] = [];
          }
          if (!isset($userNotifications["user:$userId"]["$websiteId"])) {
            $userNotifications["user:$userId"]["$websiteId"] = [];
          }
          $userNotifications["user:$userId"]["$websiteId"][] = $record;
        }
      }
    }
    // Now for each user, add their notifications.
    foreach ($userNotifications as $user => $userData) {
      $this->db->insert('notifications', array(
        'source' => $triggerName,
        'source_type' => 'T',
        'data' => json_encode(['headings' => $data['headings'], 'data' => $userData]),
        'user_id' => str_replace('user:', '', $user),
        'digest_mode' => $digestMode,
      ));
    }
  }

  /**
  * Takes any notifications stored in the database and builds emails to send for any that are now due.
  */
  private function doDigestNotifications($swift) {
    self::msg("Checking notifications");
    // First, build a list of the notifications we are going to do.
    $digestTypes = array('I');
    $date = getdate();
    $lastdate = getdate(strtotime($this->lastRunDate));
    if ($date['yday'] != $lastdate['yday'] || $date['year'] != $lastdate['year']) {
      $digestTypes[] = 'D';
    }
    if ($date['yday'] - $lastdate['yday'] >= 7 || $date['wday'] < $lastdate['wday']) {
      $digestTypes[] = 'W';
    }

    // Get a list of the notifications to send, ordered by user so we can
    // construct each email.
    $notifications = $this->db
      ->select('id, source, source_type, data, user_id, cc')
      ->from('notifications')
      ->where('acknowledged', 'f')
      ->in('notifications.digest_mode', $digestTypes)
      ->orderby(array('notifications.user_id' => 'ASC', 'notifications.cc' => 'ASC'))
      ->get();
    $nrNotifications = count($notifications);
    if ($nrNotifications > 0) {
      self::msg("Found $nrNotifications notifications");
    }
    else {
      self::msg("No notifications found");
    }

    $currentUserId = NULL;
    $currentCc = NULL;
    $emailContent = '';
    $notificationIds = array();
    foreach ($notifications as $notification) {
      $notificationIds[] = $notification->id;
      if (($currentUserId != $notification->user_id) || ($currentCc != $notification->cc)) {
        if ($currentUserId) {
          // Send current email data.
          $this->sendEmail($notificationIds, $swift, $currentUserId, $emailContent, $currentCc);
          $notificationIds = array();
        }
        $currentUserId = $notification->user_id;
        $currentCc = $notification->cc;
        $emailContent = kohana::lang('misc.notification_intro', kohana::config('email.server_name')) . '<br/><br/>';
      }
      $emailContent .= self::unparseData($notification->data);
    }
    // Make sure we send the email to the last person in the list.
    if ($currentUserId !== NULL) {
      // Send current email data.
      $this->sendEmail($notificationIds, $swift, $currentUserId, $emailContent, $currentCc);
    }
  }

  private function sendEmail($notificationIds, $swift, $userId, $emailContent, $cc) {
    // Use a transaction to allow us to prevent the email sending and marking
    // of notification as done getting out of step.
    $this->db->begin();
    try {
      $this->db
        ->set('acknowledged', 't')
        ->from('notifications')
        ->in('id', $notificationIds)
        ->update();
      $email_config = Kohana::config('email');
      $userResults = $this->db
        ->select('people.email_address')
        ->from('people')
        ->join('users', 'users.person_id', 'people.id')
        ->where('users.id', $userId)
        ->limit(1)
        ->get();
      if (!isset($email_config['address'])) {
        self::msg('Email address not provided in email configuration', 'error');
        return;
      }
      foreach ($userResults as $user) {
        $message = new Swift_Message(kohana::lang('misc.notification_subject', kohana::config('email.server_name')), $emailContent,
                                     'text/html');
        $recipients = new Swift_RecipientList();
        $recipients->addTo($user->email_address);
        $cc = explode(',', $cc);
        foreach ($cc as $ccEmail) {
          $recipients->addCc(trim($ccEmail));
        }
        // Send the email.
        $swift->send($message, $recipients, $email_config['address']);
        kohana::log('info', 'Email notification sent to ' . $user->email_address);
      }
    }
    catch (Exception $e) {
      // Email not sent, so undo marking of notification as complete.
      $this->db->rollback();
      throw $e;
    }
    $this->db->commit();
  }

  /**
   * Return a query to get the list of triggers. Uses the query builder as gives us good performance without
   * making additional work should we go database agnostic.
   */
  private function getTriggerQuery() {
    // Additional join to trigger_actions just prevents us from processing
    // triggers with nothing to do.
    return $this->db
      ->select('DISTINCT triggers.id, triggers.name, triggers.trigger_template_file, triggers.params_json')
      ->from('triggers')
      ->join('trigger_actions', 'trigger_actions.trigger_id', 'triggers.id')
      ->where(array(
        'enabled' => 'true',
        'triggers.deleted' => 'false',
        'trigger_actions.deleted' => 'false',
      ))
      ->get();
  }

  /**
   * Takes the output of a report. Parses it to return an associative array containing the following information:
   *   'headingData' => Array of column headings
   *   'websiteRecordData' => Array of records, each containing an array of values.
   * Website records and record data are split into an array keyed by website ID, so that it is easier to provide
   * data back to the notified users appropriate to their website rights.
   */
  private function parseData($data) {
    // Build the column headers. Get the HTML (for immediate use) as well as
    // the array data (for storing the notifications).
    $headingData = array();
    foreach ($data['content']['columns'] as $column => $cfg) {
      if ($cfg['visible'] !== 'false') {
        $headingData[] = empty($cfg['display']) ? $column : $cfg['display'];
      }
    }
    // Build the blocks of data, one per website, so we can tailor the output
    // table to each recipient.
    $websiteRecordData = array();
    foreach ($data['content']['records'] as $idx => $record) {
      $recordAsArray = array();
      foreach ($data['content']['columns'] as $column => $cfg) {
        if ($cfg['visible'] !== 'false') {
          // Allow for an incorrect column def in the report, as a broken
          // report can block the scheduled tasks otherwise.
          $recordAsArray[] = empty($record[$column]) ? '' : $record[$column];
        }
      }
      $websiteRecordData[$record['website_id']][] = $recordAsArray;
    }
    return array(
      'headingData' => $headingData,
      'websiteRecordData' => $websiteRecordData
    );
  }

  /**
   * Converts data stored in the notifications table into an HTML grid.
   */
  private function unparseData($data) {
    $struct = json_decode($data, TRUE);
    $r = "<table>\n";
    if (!empty($struct['headings'])) {
      $r .= "  <thead>\n    <tr>      <th>";
      $r .= implode('</th>      <th>', $struct['headings']);
      $r .= "</th>\n    </tr>\n  </thead>\n";
    }
    $r .= "  <tbody>\n";
    // The sructure has an entry per allowed website for the notified user,
    // containing a list of records.
    foreach ($struct['data'] as $website => $records) {
      foreach ($records as $record) {
        $r .= '    <tr>      <td>';
        $r .= implode('</td>      <td>', $record);
        $r .= "</td>\n    </tr>\n";
      }
    }
    $r .= "  </tbody>\n</table>\n";
    return $r;
  }

  /**
   * Look for records posted by recorders who have given their email address and want to receive a summary of the record they are posting.
   */
  private function doRecordOwnerNotifications($swift) {
    // Workflow module can dictate that communications should be logged for
    // some species.
    $modules = kohana::config('config.modules');
    $useWorkflowModule = in_array(MODPATH . 'workflow', $modules);
    // Get a list of the records which contributors want to get a summary back
    // for.
    $emailsRequired = $this->db
      ->select('DISTINCT occurrences.id as occurrence_id, sav2.text_value as email_address, surveys.title as survey')
      ->from('occurrences')
      ->join('samples', 'samples.id', 'occurrences.sample_id')
      ->join('surveys', 'surveys.id', 'samples.survey_id')
      ->join('sample_attribute_values as sav1', 'sav1.sample_id', 'samples.id')
      ->join('sample_attributes as sa1', 'sa1.id', 'sav1.sample_attribute_id')
      ->join('sample_attribute_values as sav2', 'sav2.sample_id', 'samples.id')
      ->join('sample_attributes as sa2', 'sa2.id', 'sav2.sample_attribute_id')
      ->where(array(
        'sa1.caption' => 'Email me a copy of the record',
        'sa2.caption' => 'Email',
        'samples.created_on>=' => $this->lastRunDate,
      ))
      ->where('sav1.int_value<>0')
      ->get();
    // Get a list of the records we need details of, so we can hit the db more
    // efficiently, plus a list of occurrence IDs and associated email
    // addresses.
    $recordsToFetch = [];
    $occurrenceEmails = [];
    foreach ($emailsRequired as $email) {
      $recordsToFetch[] = $email->occurrence_id;
      $occurrenceEmails[$email->occurrence_id] = $email->email_address;
    }
    $qry = $this->db
      ->select('o.id, ttl.taxon, s.date_start, s.date_end, s.date_type, s.entered_sref as spatial_reference, '.
          's.location_name, o.comment as sample_comment, o.comment as occurrence_comment')
      ->from('samples as s')
      ->join('occurrences as o', 'o.sample_id', 's.id')
      ->join('cache_taxa_taxon_lists as ttl', 'ttl.id', 'o.taxa_taxon_list_id')
      ->in('o.id', $recordsToFetch);
    if ($useWorkflowModule) {
      // Extra query info to determine if comms need to be logged for this
      // species.
      $qry
        ->select('wm.log_all_communications')
        ->join('workflow_metadata as wm', 'wm.key_value', 'ttl.external_key', 'LEFT')
        ->in('wm.entity', ['occurrence', NULL])
        ->in('wm.key', ['taxa_taxon_list_external_key', NULL]);
    }
    $occurrences = $qry->get();
    // Copy the occurrences to an array so we can build a structured list of
    // data, keyed by ID.
    $occurrenceArray = array();
    foreach ($occurrences as $occurrence) {
      $occurrenceArray[$occurrence->id] = $occurrence;
    }
    $attrArray = array();
    // Get the sample attributes.
    $attrValues = $this->db
      ->select('o.id, av.caption, av.value')
      ->from('list_sample_attribute_values as av')
      ->join('samples as s', 's.id', 'av.sample_id')
      ->join('occurrences as o', 'o.sample_id', 's.id')
      ->in('o.id', $recordsToFetch)
      ->get();
    foreach ($attrValues as $attrValue) {
      $attrArray[$attrValue->id][$attrValue->caption] = $attrValue->value;
    }
    // Get the occurrence attributes.
    $attrValues = $this->db
      ->select('av.occurrence_id, av.caption, av.value')
      ->from('list_occurrence_attribute_values av')
      ->in('av.occurrence_id', $recordsToFetch)
      ->get();
    foreach ($attrValues as $attrValue) {
      $attrArray[$attrValue->occurrence_id][$attrValue->caption] = $attrValue->value;
    }
    $email_config = Kohana::config('email');
    foreach ($emailsRequired as $email) {
      $emailContent = "Thank you for sending your record to $email->survey. Here are the details of your contribution for your records.<br/><table>";
      $this->addArrayToEmailTable($email->occurrence_id, $occurrenceArray, $emailContent);
      $this->addArrayToEmailTable($email->occurrence_id, $attrArray, $emailContent);
      $emailContent .= "</table>";

      $message = new Swift_Message(kohana::lang('misc.notification_subject', kohana::config('email.server_name')), $emailContent,
                                     'text/html');
      $recipients = new Swift_RecipientList();
      $recipients->addTo($email->email_address);
      // Send the email.
      $swift->send($message, $recipients, $email_config['address']);
    }
    if ($useWorkflowModule) {
      foreach ($occurrences as $occurrence) {
        if ($occurrence->log_all_communications === 't') {
          $this->db->insert('occurrence_comments', array(
            'occurrence_id' => $occurrence->id,
            'comment' => "An acknowledgement email was sent to the record contributor.",
            'correspondence_data' => json_encode([
              'email' => [
                [
                  'from' => $email_config['address'],
                  'to' => $occurrenceEmails[$occurrence->id],
                  'subject' => kohana::lang('misc.notification_subject', kohana::config('email.server_name')),
                  'body' => 'Details of the record and attributes sent as part of a digest email.',
                ],
              ],
            ]),
            'created_by_id' => 1,
            'created_on' => date('Y-m-d H:i:s'),
            'updated_by_id' => 1,
            'updated_on' => date('Y-m-d H:i:s'),
          ));
        }
      }
    }
  }

  /*
   * Takes the content of an array keyed bby occurrence ID, looks up the item for the required
   * occurrence, and puts the content of this item into a set of rows (one row per name/value pair)
   * with one table cell for the key, and one for the value
   */
  private function addArrayToEmailTable($occurrenceId, $array, &$emailContent) {
    $excludedFields = array(
      'date_end',
      'date_type',
      'Email me a copy of the record',
      'CMS Username',
      'CMS User ID',
      'Email',
      'Happy for Contact?',
    );
    foreach ($array[$occurrenceId] as $field => $value) {
      if ($field === 'date_start') {
        $value = vague_date::vague_date_to_string(array(
          $array[$occurrenceId]->date_start,
          $array[$occurrenceId]->date_end,
          $array[$occurrenceId]->date_type,
        ));
        $field = 'date';
      }
      if (!empty($value) && !in_array($field, $excludedFields)) {
        $emailContent .= "<tr><td>$field</td><td>$value</td></tr>";
      }
    }
  }

  /**
   * Loop through any plugin modules which declare scheduled tasks and run them.
   *
   * @param object $system
   *   System model instance.
   * @param array $scheduledPlugins
   *   Array of plugin names to run, or array containing "all_modules" to run
   *   them all.
   */
  private function runScheduledPlugins($system, array $scheduledPlugins) {
    // take 1 second off current time to use as the end of the scanned time period. Avoids possibilities of records
    // being lost half way through the current second.
    $t = time() - 1;
    $currentTime = date("Y-m-d H:i:s", $t);
    $plugins = $this->getScheduledPlugins();
    // Load the plugins and last run date info from the system table. Any not run before will start from the current timepoint.
    // We need this to be sorted, so we can process the list of changed records for each group of plugins with the same timestamp together.
    $pluginsFromDb = $this->db
      ->select('name, last_scheduled_task_check')
      ->from('system')
      ->in('name', $plugins)
      ->orderby('last_scheduled_task_check', 'ASC')
      ->get();
    $sortedPlugins = array();
    foreach ($pluginsFromDb as $plugin) {
      $sortedPlugins[$plugin->name] = $plugin->last_scheduled_task_check === NULL ? $currentTime : $plugin->last_scheduled_task_check;
    }
    // Any new plugins not run before should also be included in the list
    foreach ($plugins as $plugin) {
      if (!isset($sortedPlugins[$plugin])) {
        $sortedPlugins[$plugin] = $currentTime;
      }
    }
    // Make sure data_cleaner runs before auto_verify module.
    if (array_key_exists('data_cleaner', $sortedPlugins)) {
      $sortedPlugins = array('data_cleaner' => $sortedPlugins['data_cleaner']) + $sortedPlugins;
    }
    // Make sure the cache_builder and spatial_index_builders run first as some
    // other modules depend on the cache_occurrences_* tables.
    if (array_key_exists('spatial_index_builder', $sortedPlugins)) {
      $sortedPlugins = array('spatial_index_builder' => $sortedPlugins['spatial_index_builder']) + $sortedPlugins;
    }
    if (array_key_exists('cache_builder', $sortedPlugins)) {
      $sortedPlugins = array('cache_builder' => $sortedPlugins['cache_builder']) + $sortedPlugins;
    }
    // Make sure the verifier notification emails run last as the emails are
    // sent out based on the results of other modules such as notifications
    // generated.
    if (array_key_exists('verifier_notification_emails', $sortedPlugins)) {
      $temp = $sortedPlugins['verifier_notification_emails'];
      unset($sortedPlugins['verifier_notification_emails']);
      $sortedPlugins['verifier_notification_emails'] = $temp;
    }
    // Now go through timestamps in order of time since they were run.
    foreach ($sortedPlugins as $plugin => $timestamp) {
      // allow the list of scheduled plugins we are running to be controlled from the URL parameters.
      if (in_array('all_modules', $scheduledPlugins) || in_array($plugin, $scheduledPlugins)) {
        require_once MODPATH . "$plugin/plugins/$plugin.php";
        $this->loadPluginMetadata($plugin);
        $this->loadOccurrencesDelta($plugin, $timestamp, $currentTime);
        // Call the plugin, only if there are records to process, or it doesn't care.
        if (!$this->pluginMetadata['requires_occurrences_delta']
            || $this->occdeltaCount > 0
            || $this->pluginMetadata['always_run']) {
          echo "<strong>Running $plugin</strong> - last run at $timestamp <br/>";
          $tm = microtime(TRUE);
          call_user_func($plugin . '_scheduled_task', $timestamp, $this->db, $currentTime);
          // log plugins which take more than 5 seconds
          $took = microtime(TRUE) - $tm;
          if ($took > 5) {
            self::msg("Scheduled plugin $plugin took $took seconds", 'alert');
          }
        }
        else {
          echo "<strong>Skipping $plugin as nothing to do</strong> - last run at $timestamp <br/>";
        }
        // Mark the time of the last scheduled task check so we can get the
        // correct list of updates next time.
        $timestamp = $this->pluginMetadata['requires_occurrences_delta'] ? $this->occdeltaEndTimestamp : $currentTime;
        if (!$this->db->update('system', array('last_scheduled_task_check' => $timestamp), array('name' => $plugin))->count())
          $this->db->insert('system', array(
            'version' => '0.1.0',
            'name' => $plugin,
            'repository' => 'Not specified',
            'release_date' => date('Y-m-d', $t),
            'last_scheduled_task_check' => $timestamp,
            'last_run_script' => NULL,
          ));
      }
    }
  }

  private function getScheduledPlugins() {
    $cacheId = 'scheduled-plugin-names';
    $cache = Cache::instance();
    // get list of plugins which integrate with scheduled tasks. Use cache so we avoid loading all module files unnecessarily.
    if (!($plugins = $cache->get($cacheId))) {
      $plugins = array();
      foreach (Kohana::config('config.modules') as $path) {
        $plugin = basename($path);
        if (file_exists("$path/plugins/$plugin.php")) {
          require_once("$path/plugins/$plugin.php");
          if (function_exists($plugin . '_scheduled_task')) {
            $plugins[] = $plugin;
          }
        }
      }
      $cache->set($cacheId, $plugins);
    }
    return $plugins;
  }

  /**
   * Creates the occdelta table.
   *
   * If a plugin needs a different occurrences delta table to the one we've got
   * currently prepared, then build it.
   *
   * @param type $plugin
   * @param type $timestamp
   * @param string $currentTime
   *   Timepoint of the scheduled task run, so we can be absolutely clear about
   *   not including records added which overlap the scheduled task.
   *
   * @link http://indicia-docs.readthedocs.io/en/latest/developing/warehouse/plugins.html#scheduled-task-hook
   */
  private function loadOccurrencesDelta($plugin, $timestamp, $currentTime) {
    if ($this->pluginMetadata['requires_occurrences_delta']) {
      if ($this->occdeltaStartTimestamp !== $timestamp) {
        // This scheduled plugin wants to know about the changed occurrences,
        // and the current occdelta table does not contain the records since
        // the correct change point.
        $this->db->query('DROP TABLE IF EXISTS occdelta;');
        // This query uses a 2 stage process as it is faster than joining occurrences to cache_occurrences.
        $query = "
select distinct o.id
into temporary occlist
from occurrences o
where o.updated_on>'$timestamp' and o.updated_on<='$currentTime'
union
select o.id from occurrences o
join samples s on s.id=o.sample_id and s.deleted=false
where s.updated_on>'$timestamp' and s.updated_on<='$currentTime'
union
select o.id from occurrences o
join samples s on s.id=o.sample_id and s.deleted=false
join samples sp on sp.id=s.parent_id and sp.deleted=false
where sp.updated_on>'$timestamp' and sp.updated_on<='$currentTime'
order by id;

select co.*,
  case when o.created_on>'$timestamp' then 'C' when o.deleted=true then 'D' else 'U' end as CUD,
  case
    when o.created_on>'$timestamp' then o.created_on
    else greatest(o.updated_on, s.updated_on, sp.updated_on)
  end as timestamp,
  lower(coalesce(onf.attr_stage, onf.attr_sex_stage)) as stage
into temporary occdelta
from occlist ol
join occurrences o on o.id=ol.id
join cache_occurrences_functional co on co.id=o.id
join cache_occurrences_nonfunctional onf on onf.id=co.id
join samples s on s.id=o.sample_id and s.deleted=false
left join samples sp on sp.id=s.parent_id and sp.deleted=false;

create index ix_occdelta_taxa_taxon_list_id on occdelta(taxa_taxon_list_id);
create index ix_occdelta_taxa_taxon_list_external_key on occdelta(taxa_taxon_list_external_key);

drop table occlist;";
        $this->db->query($query);
        $this->occdeltaStartTimestamp = $timestamp;
        $this->occdeltaEndTimestamp = $currentTime;
        // If processing more than a few thousand records at a time, things
        // will slow down. So we'll cut off the delta at the second which the
        // 5000th record fell on.
        $this->limitDeltaSize();
      }
    }
  }

  /**
   * If processing too many records, clear out the excess.
   */
  private function limitDeltaSize() {
    $this->occdeltaCount = $this->db->count_records('occdelta');
    $max = 5000;
    if ($this->occdeltaCount > $max) {
      $qry = $this->db->query("select timestamp from occdelta order by timestamp asc limit 1 offset $max");
      foreach ($qry as $t) {
        self::msg("Scheduled tasks are delaying processing of " . $this->db->query("delete from occdelta where timestamp>'$t->timestamp'")->count()." records as too many to process", 'alert');
        // Remember where we are up to.
        $this->occdeltaEndTimestamp = $t->timestamp;
      }
      $this->occdeltaCount = $this->db->count_records('occdelta');
    }
  }

  /**
   * Loads the metadata for the given plugin name.
   *
   * @param string $plugin
   *   Name of the plugin.
   */
  private function loadPluginMetadata($plugin) {
    $this->pluginMetadata = function_exists($plugin . '_metadata') ? call_user_func($plugin . '_metadata') : array();
    $this->pluginMetadata = array_merge(array(
      'requires_occurrences_delta' => FALSE,
      'always_run' => FALSE,
    ), $this->pluginMetadata);
  }

  /**
   * Echoes out a message and adds to the kohana log.
   *
   * @param string $msg
   *   Message text.
   * @param string $status
   *   Kohana log message status.
   */
  private function msg($msg, $status = 'debug') {
    echo "$msg<br/>";
    if ($status === 'error') {
      kohana::log('error', 'Error occurred whilst running scheduled tasks.');
    }
    kohana::log($status, $msg);
  }

}
