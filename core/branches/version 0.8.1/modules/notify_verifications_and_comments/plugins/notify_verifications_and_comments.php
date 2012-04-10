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
 * @package	Verification Check
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Hook into the task scheduler. Runs a query to find all comments and verification status updates that need
 * to be notified back to the recorder of a record. 
 */
function notify_verifications_and_comments_scheduled_task($last_run_date) {  
  if (!$last_run_date)
    // first run, so get all records changed in last day. Query will automatically gradually pick up the rest.
    $last_run_date=date('Y-m-d', time()-60*60*24*50);  
  try {
    $db = new Database();
    $notifications = postgreSQL::selectVerificationAndCommentNotifications($last_run_date, $db);
    foreach ($notifications as $notification) {
      $vd = array($notification->date_start, $notification->date_end, $notification->date_type);
      $date = vague_date::vague_date_to_string($vd);
      $db->insert('notifications', array(
                'source' => 'Verifications and comments',
                'source_type' => $notification->source_type,
                'data' => json_encode(array(
                    'username'=>$notification->username,'occurrence_id'=>$notification->id,'comment'=>$notification->comment,
                    'taxon'=>$notification->taxon,'date'=>$date,'entered_sref'=>$notification->public_entered_sref,
                    'auto_generated'=>$notification->auto_generated, 'record_status'=>$notification->record_status, 'updated_on'=>$notification->updated_on
                )),
                'user_id' => $notification->created_by_id,
                // use digest mode the user selected for this notification, or their default if not specific
                'digest_mode' => 'N'
              ));
    }
    echo count($notifications) . ' notifications generated<br/>';
  } catch (Exception $e) {
    echo $e->getMessage();
  }
}

?>