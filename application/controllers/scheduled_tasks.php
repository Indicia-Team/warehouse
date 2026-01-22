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

  /**
   * Array containing metadata for the currently processing plugin.
   *
   * Key/value pairs.
   *
   * @var array
   */
  private $pluginMetadata;


  /**
   * Database connection.
   *
   * @var Database
   */
  private Database $db;

  /**
   * Main entry point for scheduled tasks.
   *
   * The index method is the default method called when you access this
   * controller, so we can use this to run the scheduled tasks. Takes an
   * optional URL or command-line parameter "tasks", which is a comma separated
   * list of the module names to schedule, plus can contain "notifications" to
   * fire the built in notifications system or "all_modules" to fire every
   * module that declares a scheduled task plugin. If tasks are not specified
   * then everything is run.
   */
  public function index() {
    warehouse::lockProcess('scheduled-tasks');
    try {
      $tm = microtime(TRUE);
      $this->db = new Database();
      $system = new System_Model();
      $allNonPluginTasks = ['notifications', 'work_queue'];
      // Allow tasks to be specified on command line or URL parameter.
      global $argv;
      $args = [];
      if ($argv) {
        parse_str(implode('&', array_slice($argv, 1)), $args);
      }
      $tasks = $_GET['tasks'] ?? $args['tasks'] ?? NULL;
      if ($tasks !== NULL) {
        $requestedTasks = explode(',', $tasks);
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
        if (class_exists('request_logging')) {
          request_logging::log('a', 'scheduled_tasks', NULL, 'triggers_notifications', 0, 0, $tm, $this->db);
        }
      }
      if ($scheduledPlugins) {
        $this->runScheduledPlugins($system, $scheduledPlugins);
      }
      if (in_array('notifications', $nonPluginTasks)) {
        $email_config = Kohana::config('email');
        if (array_key_exists('do_not_send', $email_config) and $email_config['do_not_send']) {
          kohana::log('info', "Email configured for do_not_send: ignoring notifications from scheduled tasks");
        }
        else {
          $emailer = new Emailer();
          $this->doRecordOwnerNotifications($emailer);
          $this->doNotificationDigestEmailsForTriggers($emailer);
        }
        // The value of the last_scheduled_task_check on the Indicia system entry
        // is used to mark the last time notifications were handled, so we can
        // process new notification info next time notifications are handled.
        $this->db->update('system', ['last_scheduled_task_check' => "'" . date('c', $currentTime) . "'"], ['id' => 1]);
      }
      if (in_array('work_queue', $nonPluginTasks)) {
        $timeAtStart = microtime(TRUE);
        $queue = new WorkQueue();
        $queue->process($this->db);
        $timeTaken = microtime(TRUE) - $timeAtStart;
        if ($timeTaken > 10) {
          self::msg("Work queue processing took $timeTaken seconds.", 'alert');
        }
        if (class_exists('request_logging')) {
          request_logging::log('a', 'scheduled_tasks', NULL, 'work_queue', 0, 0, $timeAtStart, $this->db);
        }
      }
      self::msg("Ok!");
      $tm = microtime(TRUE) - $tm;
      if ($tm > 30) {
        self::msg(
          "Scheduled tasks for " . implode(', ', array_merge($nonPluginTasks, $scheduledPlugins)) . " took $tm seconds.",
          'alert'
        );
      }
    }
    finally {
      warehouse::unlockProcess('scheduled-tasks');
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
      $reportEngine = new ReportEngine();
      // Get parameter for last run specific to this trigger.
      $params['date'] = variable::get("trigger_last_run-$trigger->id", $this->lastRunDate, FALSE);
      $currentTime = time();
      try {
        $data = $reportEngine->requestReport(
          $trigger->trigger_template_file . '.xml',
          'local',
          'xml',
          $params,
          TRUE,
          ReportReader::REPORT_DESCRIPTION_FULL
        );
      }
      catch (Exception $e) {
        self::msg($trigger->name . ": " . $e, 'error');
        continue;
      }

      if (!isset($data['content']['records'])) {
        kohana::log('error', 'Error in trigger file ' . $trigger->trigger_template_file . '.xml');
        continue;
      }
      if (count($data['content']['records']) > 0) {
        $parsedData = $this->parseData($data);
        self::msg($trigger->name . ": " . count($data['content']['records']) . " records found");
        // Note escaping disabled in where clause to permit use of CAST
        // expression.
        $actions = $this->db
          ->select('trigger_actions.type, trigger_actions.param1, trigger_actions.param2, trigger_actions.param3, users.default_digest_mode, people.email_address, users.core_role_id')
          ->from('trigger_actions, users')
          ->join('people', 'people.id', 'users.person_id')
          ->where([
            'trigger_id' => $trigger->id,
            'type' => "'E'",
            'users.id' => 'CAST(param1 AS INT)',
            'trigger_actions.deleted' => "'f'",
            'users.deleted' => "'f'",
            'people.deleted' => "'f'",
          ], NULL, FALSE)
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
            $allowedData = [];
            foreach ($userWebsites as $allowedWebsite) {
              if (isset($parsedData['websiteRecordData'][$allowedWebsite->website_id])) {
                $allowedData[$allowedWebsite->website_id] = $parsedData['websiteRecordData'][$allowedWebsite->website_id];
              }
            }
          }
          // Use digest mode the user selected for this notification, or
          // their default if not specific.
          $digestMode = $action->param2 ?? $action->default_digest_mode;
          if (count($allowedData) > 0) {
            $this->db->insert('notifications', [
              'source' => $trigger->name,
              'source_type' => 'T',
              'data' => json_encode([
                'headings' => $parsedData['headingData'],
                'data' => $allowedData,
              ]),
              'user_id' => $action->param1,
              'digest_mode' => $digestMode,
              'cc' => $action->param3,
            ]);
          }
        }
        $this->doTriggerLogComments(
          $trigger->name,
          [
            'headings' => $parsedData['headingData'],
            'data' => $parsedData['websiteRecordData'],
          ]
        );
        $this->doDirectTriggerNotifications(
          $trigger->name,
          [
            'headings' => $parsedData['headingData'],
            'data' => $parsedData['websiteRecordData'],
          ]
        );
        if (!empty($data['description']['attachment'])) {
          // Apply parameters to query used to build attachment data. This
          // automatically includes the date parameter.
          foreach ($params as $key => $value) {
            $data['description']['attachment']['query'] = str_replace("#$key#", $value, $data['description']['attachment']['query']);
          }
        }
        $this->doTriggerImmediateEmails(
          $trigger->name,
          [
            'headings' => $parsedData['headingData'],
            'data' => $parsedData['websiteRecordData'],
          ],
          $data['description']
        );
      }
      // Remember when this specific trigger last ran.
      variable::set("trigger_last_run-$trigger->id", date('c', $currentTime));
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
          $this->db->insert('occurrence_comments', [
            'comment' => $record[$logCommentCol],
            'created_by_id' => 1,
            'created_on' => date('Y-m-d H:i:s'),
            'updated_by_id' => 1,
            'updated_on' => date('Y-m-d H:i:s'),
            'occurrence_id' => $record[$occIDCol],
            'generated_by' => 'notifications',
          ]);
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
   */
  private function doDirectTriggerNotifications($triggerName, array $data) {
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
      $this->db->insert('notifications', [
        'source' => $triggerName,
        'source_type' => 'T',
        'data' => json_encode([
          'headings' => $data['headings'],
          'data' => $userData,
        ]),
        'user_id' => str_replace('user:', '', $user),
        // Users specified in notify_user_ids should be notified as soon as
        // possible.
        'digest_mode' => 'I',
      ]);
    }
  }

  /**
   * Send emails for trigger reports that directly define an email to send.
   *
   * These emails can be sent directly, bypassing the notifications system and
   * therefore can be sent to anyone not just registered users. E.g. a thank
   * you email to anonymous recorders.
   *
   * @param string $triggerName
   *   Name of the trigger which fired.
   * @param array $data
   *   Info regarding the trigger report columns and associated retrieved data.
   * @param array $reportDescription
   *   Description of the report as returned by the report engine. Includes
   *   file attachment metadata.
   */
  private function doTriggerImmediateEmails($triggerName, array $data, array $reportDescription) {
    if (count($data['data']) === 0 || !in_array('email_to', $data['headings'])) {
      return;
    }
    $emailConfig = Kohana::config('email');
    if (!isset($emailConfig['address'])) {
      self::msg('Email address not provided in email configuration', 'error');
      return;
    }
    $colIndexes = [];
    $sysCols = ['email_to', 'email_subject', 'email_body', 'email_name'];
    foreach ($sysCols as $col) {
      if (($colIdx = array_search($col, $data['headings'])) !== FALSE) {
        $colIndexes[$col] = $colIdx;
      }
    }
    $defaultSubject = empty(kohana::config('email.notification_subject'))
      ? kohana::lang('misc.notification_subject')
      : kohana::config('email.notification_subject');
    $emails = [];
    foreach ($data['data'] as $records) {
      foreach ($records as $record) {
        if (!empty($record[$colIndexes['email_to']])) {
          $to = $record[$colIndexes['email_to']];
          $name = empty($colIndexes['email_name']) || empty($record[$colIndexes['email_name']])
            ? $to
            : $record[$colIndexes['email_name']];
          if (!isset($emails["$to $name"])) {
            $emails["$to $name"] = [];
          }

          $subject = empty($colIndexes['email_subject']) || empty($record[$colIndexes['email_subject']])
            ? $defaultSubject
            : $record[$colIndexes['email_subject']];
          if (empty($colIndexes['email_body']) || empty($record[$colIndexes['email_body']])) {
            // Email body not provided, so construct it from the other columns.
            $body = '';
            foreach ($data['headings'] as $idx => $colTitle) {
              // Skip the functional email columns.
              if (!in_array($colTitle, $sysCols)) {
                $body[] = '<h2>' . htmlspecialchars($colTitle) . '</h2>';
                $body[] = '<p>' . htmlspecialchars($record[$idx]) . '</p>';
              }
            }
          }
          else {
            $body = $record[$colIndexes['email_body']];
          }
          // Aggregate emails per email address, so we don't send multiple.
          $emails["$to $name"][] = [
            'to' => $to,
            'name' => $name,
            'subject' => $subject,
            'body' => $body,
          ];
        }
      }
    }
    $emailer = new Emailer();
    // Now send the emails as a digest so each recipient only gets one email.
    foreach ($emails as $infoList) {
      // If a single email for this recipient we can use the subject, otherwise
      // we use a generic subject and put each email subject in as a subtitle.
      $subject = count($infoList) === 1 ? $infoList[0]['subject'] : $defaultSubject;
      $emailContent = '';
      foreach ($infoList as $infoItem) {
        if (count($infoList) > 1) {
          $emailContent .= '<h1>' . htmlspecialchars($infoItem['subject']) . '</h1>';
        }
        $emailContent .= '<div>' . $infoItem['body'] . '</div>';
      }
      $emailer->addRecipient($infoList[0]['to'], $infoList[0]['name']);
      $emailer->setFrom($emailConfig['address']);
      // Add any attachment defined in the report description.
      if (!empty($reportDescription['attachment']) && !empty($reportDescription['attachment']['query']) && !empty($reportDescription['attachment']['filename'])) {
        $emailer->addAttachmentFromQuery(
          $reportDescription['attachment']['query'],
          $reportDescription['attachment']['filename'],
          $this->db
        );
      }
      $emailer->send($subject, "<html>$emailContent</html>", 'triggerImmediateEmailTo', $triggerName);
    }
  }

  /**
   * Create email digests for trigger notifications.
   *
   * Takes any notifications stored in the database which specify their own
   * digest_mode and builds emails to send for any that are now due.
   * Notifications that specify a digest_mode are always from the warehouse's
   * triggers & notifications section.
   */
  private function doNotificationDigestEmailsForTriggers(emailer $emailer) {
    self::msg("Checking notifications");
    // First, build a list of the notifications we are going to do.
    $digestTypes = ['I'];
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
      ->orderby(['notifications.user_id' => 'ASC', 'notifications.cc' => 'ASC'])
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
    $notificationIds = [];
    foreach ($notifications as $notification) {
      $notificationIds[] = $notification->id;
      if (($currentUserId != $notification->user_id) || ($currentCc != $notification->cc)) {
        if ($currentUserId) {
          // Send current email data.
          $this->sendEmail($notificationIds, $emailer, $currentUserId, $emailContent, $currentCc);
          $notificationIds = [];
        }
        $currentUserId = $notification->user_id;
        $currentCc = $notification->cc;
        $intro = empty(kohana::config('email.notification_intro')) ?
          kohana::lang('misc.notification_intro') : kohana::config('email.notification_intro');
        $emailContent = sprintf($intro, kohana::config('email.server_name')) . '<br/><br/>';
      }
      $emailContent .= self::unparseData($notification->data);
    }
    // Make sure we send the email to the last person in the list.
    if ($currentUserId !== NULL) {
      // Send current email data.
      $this->sendEmail($notificationIds, $emailer, $currentUserId, $emailContent, $currentCc);
    }
  }

  private function sendEmail($notificationIds, $emailer, $userId, $emailContent, $cc) {
    // Use a transaction to allow us to prevent the email sending and marking
    // of notification as done getting out of step.
    $this->db->begin();
    try {
      $this->db
        ->set([
          'acknowledged' => 't',
          'email_sent' => 't',
          ])
        ->from('notifications')
        ->in('id', $notificationIds)
        ->update();
      $email_config = Kohana::config('email');
      $userResults = $this->db
        ->select('people.email_address, people.first_name, people.surname')
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
        $subject = empty(kohana::config('email.notification_subject')) ?
          kohana::lang('misc.notification_subject') : kohana::config('email.notification_subject');
        $emailer->addRecipient($user->email_address,"$user->first_name $user->surname");
        $cc = isset($cc) ? explode(',', $cc) : [];
        foreach ($cc as $ccEmail) {
          $emailer->addCc(trim($ccEmail));
        }
        // Send the email.
        $sent = $emailer->send(sprintf($subject, kohana::config('email.server_name')), "<html>$emailContent</html>", 'triggerActions');
        kohana::log('info', "$sent email notification(s) sent to $user->email_address");
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
   * Return a query to get the list of triggers.
   *
   * Uses the query builder as gives us good performance without making
   * additional work should we go database agnostic.
   */
  private function getTriggerQuery() {
    // Additional join to trigger_actions just prevents us from processing
    // triggers with nothing to do.
    return $this->db
      ->select('DISTINCT triggers.id, triggers.name, triggers.trigger_template_file, triggers.params_json')
      ->from('triggers')
      ->join('trigger_actions', 'trigger_actions.trigger_id', 'triggers.id')
      ->where([
        'enabled' => 'true',
        'triggers.deleted' => 'false',
        'trigger_actions.deleted' => 'false',
      ])
      ->get();
  }

  /**
   * Parses the output of a report.
   *
   * Parses it to return an associative array containing the following
   * information:
   * * 'headingData' => Array of column headings
   * * 'websiteRecordData' => Array of records, each containing an array of
   *   values.
   *
   * Website records and record data are split into an array keyed by website
   * ID, so that it is easier to provide data back to the notified users
   * appropriate to their website rights.
   */
  private function parseData($data) {
    // Build the column headers. Get the HTML (for immediate use) as well as
    // the array data (for storing the notifications).
    $headingData = [];
    foreach ($data['content']['columns'] as $column => $cfg) {
      if ($cfg['visible'] !== 'false') {
        $headingData[] = empty($cfg['display']) ? $column : $cfg['display'];
      }
    }
    // Build the blocks of data, one per website, so we can tailor the output
    // table to each recipient.
    $websiteRecordData = [];
    foreach ($data['content']['records'] as $idx => $record) {
      $recordAsArray = [];
      foreach ($data['content']['columns'] as $column => $cfg) {
        if ($cfg['visible'] !== 'false') {
          // Allow for an incorrect column def in the report, as a broken
          // report can block the scheduled tasks otherwise.
          $recordAsArray[] = empty($record[$column]) ? '' : $record[$column];
        }
      }
      $websiteRecordData[$record['website_id']][] = $recordAsArray;
    }
    return [
      'headingData' => $headingData,
      'websiteRecordData' => $websiteRecordData,
    ];
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
   * Notify owners of posted records where requested.
   *
   * Look for records posted by recorders who have given their email address
   * and want to receive a summary of the record they are posting.
   */
  private function doRecordOwnerNotifications(emailer $emailer) {
    // Workflow module can dictate that communications should be logged for
    // some species.
    $modules = kohana::config('config.modules');
    $useWorkflowModule = in_array(MODPATH . 'workflow', $modules);
    // Get parameter for last run specific to record owner notifications.
    $lastRunDate = variable::get('record-owner-notifications', $this->lastRunDate);
    $currentTime = time();
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
      ->where([
        'sa1.caption' => 'Email me a copy of the record',
        'sa2.caption' => 'Email',
        'samples.created_on>=' => $lastRunDate,
      ])
      ->where('sav1.int_value<>0')
      ->get();
    if (count($emailsRequired) === 0) {
      self::msg("No record owner notifications to send");
      return;
    }
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
      ->select('o.id, ttl.taxon, s.date_start, s.date_end, s.date_type, s.entered_sref as spatial_reference, ' .
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
    $occurrenceArray = [];
    foreach ($occurrences as $occurrence) {
      $occurrenceArray[$occurrence->id] = $occurrence;
    }
    $attrArray = [];
    // Get the sample attributes.
    $occurrenceIds = implode(',', $recordsToFetch);
    $attrValues = $this->db->query(<<<SQL
      SELECT o.id, a.caption, CASE a.data_type
            WHEN 'T'::bpchar THEN av.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN av.int_value::text ||
            CASE
                WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                ELSE ''::text
            END
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN av.text_value
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS value
      FROM occurrences o
      JOIN sample_attribute_values av ON av.sample_id=o.sample_id AND av.deleted='f'
      JOIN sample_attributes a ON a.id=av.sample_attribute_id AND a.deleted='f'
      LEFT JOIN cache_termlists_terms t ON t.id=av.int_value
      WHERE o.id IN ($occurrenceIds)
    SQL)->result();
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
      $emailer->addRecipient($email->email_address);
      $emailer->setFrom($email_config['address']);
      $emailer->send(kohana::lang('misc.notification_subject', kohana::config('email.server_name')), "<html>$emailContent</html>", 'recordOwnerNotification');
    }
    if ($useWorkflowModule) {
      foreach ($occurrences as $occurrence) {
        if ($occurrence->log_all_communications === 't') {
          $this->db->insert('occurrence_comments', [
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
          ]);
        }
      }
    }
    variable::set('record-owner-notifications', date('c', $currentTime));
  }

  /**
   * Builds HTML table for notification emails.
   *
   * Takes the content of an array keyed by occurrence ID, looks up the item
   * for the required occurrence, and puts the content of this item into a set
   * of rows (one row per name/value pair) with one table cell for the key, and
   * one for the value.
   */
  private function addArrayToEmailTable($occurrenceId, $array, &$emailContent) {
    $excludedFields = [
      'date_end',
      'date_type',
      'Email me a copy of the record',
      'CMS Username',
      'CMS User ID',
      'Email',
      'Happy for Contact?',
    ];
    foreach ($array[$occurrenceId] as $field => $value) {
      if ($field === 'date_start') {
        $value = vague_date::vague_date_to_string([
          $array[$occurrenceId]->date_start,
          $array[$occurrenceId]->date_end,
          $array[$occurrenceId]->date_type,
        ]);
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
    // Take 1 second off current time to use as the end of the scanned time
    // period. Avoids possibilities of records being lost half way through the
    // current second.
    $t = time() - 1;
    $maxTime = date("Y-m-d H:i:s", $t);
    $latestUnprocessed = $this->db
      ->select("min(created_on) - '1 second'::interval as maxtime")
      ->from('work_queue')
      ->in('task', [
        // Don't process data which is not spatially indexed, or queued for an
        // update in cache builder.
        'task_spatial_index_builder_sample',
        'task_spatial_index_builder_occurrence',
        'task_cache_builder_update'
      ])
      ->where('claimed_by', NULL)
      ->get()->current();
    if ($latestUnprocessed->maxtime !== NULL) {
      $maxTime = $latestUnprocessed->maxtime;
    }
    $allScheduledPlugins = $this->getScheduledPlugins();
    // Load the plugins and last run date info from the system table. Any not
    // run before will start from the current timepoint. We need this to be
    // sorted, so we can process the list of changed records for each group of
    // plugins with the same timestamp together.
    $pluginsFromDb = $this->db
      ->select('name, last_scheduled_task_check')
      ->from('system')
      ->in('name', $allScheduledPlugins)
      ->get();
    $pluginList = [];
    foreach ($pluginsFromDb as $plugin) {
      $pluginList[$plugin->name] = [
        'lastRunTimestamp' => $plugin->last_scheduled_task_check ?? $maxTime
      ];
    }
    foreach ($allScheduledPlugins as $plugin) {
      // Any new plugins not run before should also be included in the list.
      if (!isset($pluginList[$plugin])) {
        $pluginList[$plugin] = [
          'lastRunTimestamp' => $maxTime,
        ];
      }
      elseif (!isset($pluginList[$plugin]['lastRunTimestamp'])) {
        $pluginList[$plugin]['lastRunTimestamp'] = $maxTime;
      }
      require_once MODPATH . "$plugin/plugins/$plugin.php";
      $pluginList[$plugin] = array_merge(
        $pluginList[$plugin],
        $this->loadPluginMetadata($plugin)
      );
    }
    uasort($pluginList, function ($a, $b) {
      $weightDiff = $a['weight'] - $b['weight'];
      if ($weightDiff !== 0) {
        return $weightDiff;
      }
      return strtotime($a['lastRunTimestamp']) - strtotime($b['lastRunTimestamp']);
    });
    // Now go through plugins to run them.
    foreach ($pluginList as $plugin => $pluginMetadata) {

      // Allow the list of scheduled plugins we are running to be controlled
      // from the URL parameters.
      if (in_array('all_modules', $scheduledPlugins) || in_array($plugin, $scheduledPlugins)) {
        kohana::log('debug', "Processing scheduled task $plugin");
        kohana::log_save();
        if (!empty($pluginMetadata['requires_occurrences_delta'])) {
          $this->loadOccurrencesDeltaIfRequired($pluginMetadata, $maxTime);
        }
        // Call the plugin, only if there are records to process, or it doesn't
        // care.
        if (!$pluginMetadata['requires_occurrences_delta']
            || $this->occdeltaCount > 0
            || $pluginMetadata['always_run']) {
          kohana::log('debug', "Running scheduled task $plugin");
          kohana::log_save();
          echo "<h2>Running $plugin</h2>";
          echo "<p>Last run at $pluginMetadata[lastRunTimestamp]</p>";
          $tm = microtime(TRUE);
          try {
            call_user_func($plugin . '_scheduled_task', $pluginMetadata['lastRunTimestamp'], $this->db, $maxTime);
          }
          catch (Exception $e) {
            error_logger::log_error("Error in scheduled task $plugin", $e);
            throw $e;
          }
          // Log plugins which take more than 5 seconds.
          $took = microtime(TRUE) - $tm;
          if ($took > 5) {
            self::msg("Scheduled plugin $plugin took $took seconds", 'alert');
          }
          if (class_exists('request_logging')) {
            request_logging::log('a', 'scheduled_tasks', NULL, $plugin, NULL, NULL, $tm, $this->db);
          }
          kohana::log('debug', "Finished scheduled task $plugin");
          kohana::log_save();
        }
        else {
          echo "<strong>Skipping $plugin as nothing to do</strong> - last run at $pluginMetadata[lastRunTimestamp]<br/>";
          kohana::log('debug', "Skipped scheduled task $plugin as nothing to do.");
          kohana::log_save();
        }
        // Mark the time of the last scheduled task check so we can get the
        // correct list of updates next time.
        $timestamp = $pluginMetadata['requires_occurrences_delta'] ? $this->occdeltaEndTimestamp : $maxTime;
        if (!$this->db->update('system', ['last_scheduled_task_check' => $timestamp], ['name' => $plugin])->count()) {
          $this->db->insert('system', [
            'version' => '0.1.0',
            'name' => $plugin,
            'repository' => 'Not specified',
            'release_date' => date('Y-m-d', $t),
            'last_scheduled_task_check' => $timestamp,
            'last_run_script' => NULL,
          ]);
        }
      }
    }
  }

  /**
   * Retrieve a list of the modules that are scheduled plugins.
   *
   * @return array
   *   List of machine names of plugins which implement <plugin>_scheduled_task
   *   and should be called during each scheduled task run.
   */
  private function getScheduledPlugins() {
    $cacheId = 'scheduled-plugin-names';
    $cache = Cache::instance();
    // Get list of plugins which integrate with scheduled tasks. Use cache so
    // we avoid loading all module files unnecessarily.
    if (!($plugins = $cache->get($cacheId))) {
      $plugins = [];
      foreach (Kohana::config('config.modules') as $path) {
        $plugin = basename($path);
        if (file_exists("$path/plugins/$plugin.php")) {
          require_once "$path/plugins/$plugin.php";
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
   * @param array $pluginMetadata
   *   Metadata for the current plugin.
   * @param string $currentTime
   *   Timepoint of the scheduled task run, so we can be absolutely clear about
   *   not including records added which overlap the scheduled task.
   *
   * @link http://indicia-docs.readthedocs.io/en/latest/developing/warehouse/plugins.html#scheduled-task-hook
   */
  private function loadOccurrencesDeltaIfRequired(array $pluginMetadata, $currentTime) {
    if ($pluginMetadata['requires_occurrences_delta']) {
      if ($this->occdeltaStartTimestamp !== $pluginMetadata['lastRunTimestamp']) {
        // This scheduled plugin wants to know about the changed occurrences,
        // and the current occdelta table does not contain the records since
        // the correct change point.
        $this->db->query('DROP TABLE IF EXISTS occdelta;');
        // Set date in far past if empty to avoid error.
        $lastRunTimestamp = !empty($pluginMetadata['lastRunTimestamp']) ? $pluginMetadata['lastRunTimestamp'] : '2000-01-01 01:00:00';
        // This query uses a 2 stage process as it is faster than joining
        // occurrences to cache_occurrences.
        $ts = pg_escape_literal($this->db->getLink(), $lastRunTimestamp);
        $ct = pg_escape_literal($this->db->getLink(), $currentTime);
        $query = <<<SQL
          select distinct o.id
          into temporary occlist
          from occurrences o
          where o.updated_on>$ts and o.updated_on<=$ct
          union
          select o.id from occurrences o
          join samples s on s.id=o.sample_id and s.deleted=false
          where s.updated_on>$ts and s.updated_on<=$ct
          union
          select o.id from occurrences o
          join samples s on s.id=o.sample_id and s.deleted=false
          join samples sp on sp.id=s.parent_id and sp.deleted=false
          where sp.updated_on>$ts and sp.updated_on<=$ct
          order by id;

          select co.*,
            case when o.created_on>$ts then 'C' when o.deleted=true then 'D' else 'U' end as CUD,
            case
              when o.created_on>$ts then o.created_on
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

          drop table occlist;
        SQL;
        $this->db->query($query);
        $this->occdeltaStartTimestamp = $pluginMetadata['lastRunTimestamp'];
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
      $qry = $this->db->query("select timestamp from occdelta order by timestamp asc limit 1 offset $max")->result();
      foreach ($qry as $t) {
        $delayed = $this->db->query("delete from occdelta where timestamp>?", [$t->timestamp])->count();
        self::msg("Scheduled tasks are delaying processing of $delayed records as too many to process", 'alert');
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
   *
   * @return array
   *   Metadata for this plugin.
   */
  private function loadPluginMetadata($plugin) {
    $pluginMetadata = function_exists($plugin . '_metadata') ? call_user_func($plugin . '_metadata') : [];
    $pluginMetadata = array_merge([
      'requires_occurrences_delta' => FALSE,
      'always_run' => FALSE,
      // Default is to run after other modules that set a higher priority via a
      // lower weight.
      'weight' => 1000,
    ], $pluginMetadata);
    return $pluginMetadata;
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
