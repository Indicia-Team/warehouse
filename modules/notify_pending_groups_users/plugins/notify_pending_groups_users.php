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
 * Hook into the task scheduler. Runs a query to find all pending members for recording groups that need to be
 * notified back to the group's administrator(s).
 * @param string $last_run_date Date & time that this module was last run.
 */
function notify_pending_groups_users_scheduled_task($last_run_date) {
  if (!$last_run_date)
    // first run, so get all records changed in last day. Query will automatically gradually pick up the rest.
    $last_run_date=date('Y-m-d', time()-60*60*24*50);
  $db = new Database();
  $notifications = postgreSQL::selectPendingGroupsUsersNotifications($last_run_date, $db);
  echo "<br/>" . $db->last_query() . '<br/>';
  foreach ($notifications as $notification) {
    $person = $notification->surname . (empty($notification->first_name) ? '' : ', ' . $notification->first_name);
    $comment = "There is a pending request to join $notification->group_title";
    $theNotificationToInsert = array(
      'source' => 'Pending groups users',
      'source_type' => 'GU',
      'data' => json_encode(array(
        'comment' => $comment,
        'group_id' => $notification->group_id,
        'username' => $person
      )),
      'linked_id' => $notification->groups_user_id,
      'user_id' => $notification->notify_user_id
    );
    $db->insert('notifications', $theNotificationToInsert);
  }
  echo count($notifications) . ' notifications generated<br/>';
}