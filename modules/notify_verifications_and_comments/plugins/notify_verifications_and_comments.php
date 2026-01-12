<?php

/**
 * @file
 * Plugin file for the Notify Verifications and Comments module.
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

// @todo Update to use occdelta

/**
 * Hook into the task scheduler.
 *
 * Runs a query to find all comments and verification status updates that need
 * to be notified back to the recorder of a record.
 *
 * @param string $lastRunDate
 *   Date & time that this module was last run.
 * @param object $db
 *   Database connection.
 * @param string $maxTime
 *   Date & time to select records up to for this processing batch.
 */
function notify_verifications_and_comments_scheduled_task($lastRunDate, $db, $maxTime) {
  if (!$lastRunDate) {
    // First run, so get all records changed in last day. Query will
    // automatically gradually pick up the rest.
    $lastRunDate = date('Y-m-d', time() - 60 * 60 * 24 * 50);
  }
  $notifications = postgreSQL::selectVerificationAndCommentNotifications($lastRunDate, $maxTime, $db);
  foreach ($notifications as $notification) {
    $vd = [
      $notification->date_start,
      $notification->date_end,
      $notification->date_type,
    ];
    $date = vague_date::vague_date_to_string($vd);
    $taxonLabel = notification_taxon_label($notification);
    if (empty($notification->comment)) {
      $action = warehouse::recordStatusCodeToTerm(
        $notification->record_status . (empty($notification->record_substatus) ? '' : $notification->record_substatus),
        'amended'
      );
      $comment = "The record of $taxonLabel at $notification->public_entered_sref on $date was $action.";
    }
    else {
      if ($notification->auto_generated === 't' && substr($notification->generated_by ?? '', 0, 12) === 'data_cleaner'
          && $notification->record_owner === 't') {
        $comment = <<<TXT
The following message was attached to your record of $taxonLabel at $notification->public_entered_sref on $date
when it was checked using the
<a target="_blank" href="http://www.nbn.org.uk/Tools-Resources/Recording-Resources/NBN-Record-Cleaner.aspx"> NBN Record
Cleaner</a>. This does not mean the record is incorrect or is being disputed; the information below is merely a flag
against the record that might provide useful information for recording and verification purposes.
TXT;
      }
      elseif ($notification->verified_on > $lastRunDate && $notification->record_status !== 'I'
          && $notification->record_status !== 'T' && $notification->record_status !== 'C') {
        if ($notification->record_owner === 't') {
          $comment = "Your record of $taxonLabel at $notification->public_entered_sref on $date was examined by an expert.";
        }
        else {
          $comment = "A record of $taxonLabel at $notification->public_entered_sref on $date which you'd " .
            "previously commented on was examined by an expert.";
        }
      }
      elseif ($notification->record_owner === 't') {
        $comment = "A comment was added to your record of $taxonLabel at $notification->public_entered_sref on $date.";
      }
      else {
        $comment = "A reply was added to the record of $taxonLabel at $notification->public_entered_sref " .
          "on $date which you've previously commented on.";
      }
      $comment .= "<br/><em>$notification->comment</em>";
    }
    $theNotificationToInsert = [
      'source' => 'Verifications and comments',
      'source_type' => $notification->source_type,
      'data' => json_encode([
        'username' => $notification->username,
        'occurrence_id' => $notification->id,
        'comment' => $comment,
        'taxon' => $taxonLabel,
        'date' => $date,
        'entered_sref' => $notification->public_entered_sref,
        'auto_generated' => $notification->auto_generated,
        'record_status' => $notification->record_status,
        'record_substatus' => $notification->record_substatus,
        'updated_on' => $notification->updated_on,
      ]),
      'linked_id' => $notification->id,
      'user_id' => $notification->notify_user_id,
      'source_detail' => $notification->source_detail,
    ];
    $db->insert('notifications', $theNotificationToInsert);
  }
  echo count($notifications) . ' notifications generated<br/>';
}

/**
 * Builds an informative taxon label for a notification.
 *
 * @param object $notification
 *   Notification data read from the database, including taxon,
 *   preferred_taxon and default_common_name.
 */
function notification_taxon_label($notification) {
  $recordedName = $notification->language_iso === 'lat' ? "<em>$notification->taxon</em>" : $notification->taxon;
  $recordedNameIsDefaultCommonName = strcasecmp($notification->default_common_name ?: '', $notification->taxon) === 0;
  $recordedNameIsPreferredName = strcasecmp($notification->preferred_taxon, $notification->taxon) === 0;
  if (empty($notification->default_common_name)) {
    $r = "<em>$notification->preferred_taxon</em>";
    if (!$recordedNameIsPreferredName) {
      $r .= " (recorded as $recordedName)";
    }
  }
  else {
    $r = "$notification->default_common_name";
    if (!($recordedNameIsPreferredName || $recordedNameIsDefaultCommonName)) {
      $r .= " (<em>$notification->preferred_taxon</em>, recorded as $recordedName)";
    }
    else {
      $r .= " (<em>$notification->preferred_taxon</em>)";
    }
  }
  return $r;
}
