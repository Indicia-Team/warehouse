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
 * @package	Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Controller that implements any scheduled tasks, such as checking triggers against the recent
 * records to look for notifications. This controller does not have a user interface, it is intended 
 * to be automated on a schedule.  
 *
 * @package Core
 * @subpackage Controllers
 */
class Scheduled_Tasks_Controller extends Controller {
  private $last_run_date;  
  
  public function __construct()
	{
		parent::__construct();
	}
  
  /** 
   * The index method is the default method called when you access this controller, so we can use this 
   * to run the scheduled tasks.
   */
  public function index() {
    $this->checkTriggers();
    echo "Ok!";
  }
  
  /** 
  * Compares any recently entered or edited records with the notifications registered on the system, looking
  * for matches. If found, then the notification's actions are fired.
  */
  protected function checkTriggers() {
    echo "Checking triggers<br/>";
    // @todo Need to get the timestamp of last run
    $this->last_run_date = ORM::Factory('system', 1)->getLastScheduledTaskCheck();    
    // grab the time before we start, so there is no chance of a record coming in while we run that is missed.
    $currentTime = time();
    // Override escaping in the database config, since we don't want it for our queries.
    $dbConfig = Kohana::config('database.default');
    $dbConfig['escape'] = false;    
    $this->db = new Database($dbConfig);    
    // Get a list of all the triggers that have at least one action
    $result = $this->getTriggerQuery();
    $reportEngine = new ReportEngine();
    // For each trigger, we need to get the output of the report file which defines the trigger
    foreach ($result as $trigger)
    {       
      $params = json_decode($trigger->params_json, true);      
      $params['date'] = $this->last_run_date;
      $data=$reportEngine->requestReport($trigger->trigger_template_file.'.xml', 'local', 'xml', $params);      
      
      if (count($data['content']['data']>0)) {        
        $parsedData = $this->parseData($data);               
        echo count($data['content']['data']). " records found<br/>";
        $actions = $this->db
            ->select('trigger_actions.type, trigger_actions.param1, trigger_actions.param2, users.default_digest_mode, people.email_address, users.core_role_id')
            ->from('trigger_actions, users')            
            ->join('people', 'people.id', 'users.person_id')
            ->where(array(
                'trigger_id' => $trigger->id,
                'type' => "'E'",
                'users.id' => 'CAST(param1 AS INT)',
                'trigger_actions.deleted' => "'f'",
                'users.deleted' => "'f'",
                'people.deleted' => "'f'"
            ))
            ->get();        
        foreach ($actions as $action) {          
          if ($action->core_role_id!==1) {
            // if not a core admin, we will need to do a filter on websites the user has access to.
            $userWebsites = $this->db
                  ->select('website_id')
                  ->from('users_websites')
                  ->where('user_id', $action->param1)
                  ->get();
          }
          
          // Insert data in notifications table, either for the user to manually acknowledge, or for a digest mail to be built.
          // First build a list of data for the user's websites
          if ($action->core_role_id==1) {
            // core admin can see any data            
            $allowedData = $parsedData['websiteRecordData'];
          } else {              
            $allowedData = array();
            foreach ($userWebsites as $allowedWebsite) {              
              if (isset($parsedData['websiteRecordData'][$allowedWebsite->website_id]))
                $allowedData[$allowedWebsite->website_id] = $parsedData['websiteRecordData'][$allowedWebsite->website_id];
            }              
          }
          if (count($allowedData)>0) {
            $this->db->insert('notifications', array(
              'source' => "'$trigger->name'",
              'source_type' => "'T'",
              'data' => "'".json_encode(array('headings'=>$parsedData['headingData'], 'data' => $allowedData))."'",
              'user_id' => $action->param1,
              // use digest mode the user selected for this notification, or their default if not specific
              'digest_mode' => "'" . ($action->param2===null ? $action->default_digest_mode : $action->param2) . "'"
            ));
          }
        }        
      }      
    }
    $this->doDigestNotifications();
    $this->db
        ->update('system', array('last_scheduled_task_check'=>"'" . date('c', $currentTime) . "'"), array('id' => 1));
  }
  
  /**
  * Takes any notifications stored in the database and builds emails to send for any that are now due.
  */
  private function doDigestNotifications() {
    $swift = email::connect();
    // First, build a list of the notifications we are going to do    
    $digestTypes = array('I');
    $date = getdate();    
    $lastdate = getdate(strtotime($this->last_run_date));
    if ($date['yday']!=$lastdate['yday'] || $date['year']!=$lastdate['year'])
      $digestTypes[] = 'D';
    if ($date['yday']-$lastdate['yday']>=7 || $date['wday']<$lastdate['wday'])
      $digestTypes[] = 'W';
    
    // Get a list of the notifications to send, ordered by user so we can construct each email
    $notifications = $this->db
      ->select('id, source, source_type, data, user_id')
      ->from('notifications')      
      ->where('acknowledged',"'f'")
      ->in('notifications.digest_mode', $digestTypes)
      ->orderby('notifications.user_id', 'ASC')
      ->get();
    $currentUserId = null;
    $emailContent='';
    $notificationIds = array();
    foreach ($notifications as $notification) {
      $notificationIds[] = $notification->id;     
      if ($currentUserId!=$notification->user_id) {        
        if ($currentUserId) {
          // send current email data
          $this->sendEmail($notificationIds, $swift, $currentUserId, $emailContent);
          $notificationIds = array();
        }
        $currentUserId = $notification->user_id;
        $emailContent = kohana::lang('misc.notification_intro', kohana::config('email.server_name')) . '<br/><br/>';
      }      
      $emailContent .= self::unparseData($notification->data);
    }
    if ($currentUserId!==null) {
      // send current email data      
      $this->sendEmail($notificationIds, $swift, $currentUserId, $emailContent);
    }    
  }
  
  private function sendEmail($notificationIds, $swift, $userId, $emailContent) {
    // Use a transaction to allow us to prevent the email sending and marking of notification as done
    // getting out of step
    $this->db->begin();
    try {      
      $this->db
          ->set('acknowledged', "'t'")
          ->from('notifications')
          ->in('id', $notificationIds)
          ->update();
      $email_config = Kohana::config('email');
      $userResults = $this->db->
          select('people.email_address')
          ->from('people')
          ->join('users', 'users.person_id', 'people.id')
          ->where('users.id', $userId)
          ->limit(1)
          ->get(); 
      if (!isset($email_config['address'])) {
        kohana::log('error', 'Address not provided in email configuration');
        echo "Email not sent";
        return;
      }      
      foreach($userResults as $user) {
        $message = new Swift_Message(kohana::lang('misc.notification_subject', kohana::config('email.server_name')), $emailContent,
                                     'text/html');
        $recipients = new Swift_RecipientList();            
        $recipients->addTo($user->email_address);            
        // send the email            
        $swift->send($message, $recipients, $email_config['address']);
      }
    } catch (Exception $e) {
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
    // Additional join to trigger_actions just prevents us from processing triggers with nothing to do.
    return $this->db
        ->select('DISTINCT triggers.id, triggers.name, triggers.trigger_template_file, triggers.params_json')
        ->from('triggers')
        ->join('trigger_actions', 'trigger_actions.trigger_id', 'triggers.id')
        ->where(array('enabled'=>'true','triggers.deleted'=>'false','trigger_actions.deleted'=>'false'))
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
    // build the column headers. Get the HTML (for immediate use) as well as the array data (for storing the notifications).
    $headingData = array();
    foreach ($data['content']['columns'] as $column=>$cfg) {
      if ($cfg['visible']!=='false') {        
        $headingData[] = empty($cfg['display']) ? $column : $cfg['display'];
      }
    }
    // build the blocks of data, one per website, so we can tailor the output table to each recipient.
    $websiteRecordData = array();              
    foreach ($data['content']['data'] as $idx => $record) {
      $recordAsArray = array(); 
      foreach ($data['content']['columns'] as $column=>$cfg) {
        if ($cfg['visible']!=='false') {
          $recordAsArray[] = $record[$column];          
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
    $struct = json_decode($data, true);
    $r = "<table><thead>\n<tr><th>";
    $r .= implode('</th><th>', $struct['headings']);
    $r .= "</th></tr>\n</thead>\n<tbody>\n";
    // The sructure has an entry per allowed website for the notified user, containing a list of records.
    foreach ($struct['data'] as $website=>$records) {
      foreach($records as $record) {
        $r .= '<tr><td>';
        $r .= implode('</td><td>', $record);
        $r .= "</td></tr>\n";
      }
    }
    $r .= "</tbody>\n</table>\n";
    return $r;
  }
}