<?php

/**
 * @file
 * Helper class for workflow code.
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
 * Helper class for verifier notification functionality.
 */
class verifier_notifications {

  /**
   * Finds overdue occurrence verifications to notify.
   *
   * If the workflow module identifies a timescale before which verification is
   * required, raises a notification to relevant verifiers where that timescale
   * is passed.
   *
   * @param object $db
   *   Database connection object.
   * @param string $workflowGroup
   *   Name of the configured workflow group.
   * @param string $lastRunDate
   *   Timestamp of the last scheduled task run.
   */
  public static function processOverdueVerifications($db, $workflowGroup, $lastRunDate) {
    $urls = verifier_notification_urls_for_task_type('verification');
    $occurrencesDoneForUser = [];
    // Loop through the known moderation/verification pages on each website.
    foreach ($urls as $url) {
      $reportEngine = new ReportEngine([$url['website_id']], 1, $db);
      $filters = self::getAllVerificationFiltersForWebsite($db, $url['website_id']);
      $overdueRecords = self::getAllOverdueRecordsForWebsite($reportEngine, $workflowGroup, $lastRunDate);
      if (!empty($overdueRecords)) {
        echo 'There are ' . count($overdueRecords) . ' overdue records.<br/>';
        $occurrenceIds = [];
        foreach ($overdueRecords as $record) {
          $occurrenceIds[] = (int) $record['occurrence_id'];
        }
        $occurrenceIdList = implode(', ', $occurrenceIds);
        // Create a temp table.
        $sql = <<<SQL
DROP TABLE IF EXISTS occdelta_overdue;
SELECT co.*
INTO TEMPORARY occdelta_overdue
FROM cache_occurrences_functional co
WHERE co.id IN ($occurrenceIdList)
AND co.training=false;
-- Ensure the precise geometry is used for the spatial filter.
UPDATE occdelta_overdue o
SET public_geom=s.geom
FROM samples s
WHERE s.id=o.sample_id;
SQL;
        $db->query($sql);
        // Run all filters against occdelta_overdue and generate notifications
        // for the output.
        foreach ($filters as $filter) {
          $reportParams = json_decode($filter['definition'], TRUE);
          $reportOutput = $reportEngine->requestReport(
            "library/occdelta_overdue/filterable_explore_list.xml", 'local', 'xml', $reportParams);
          foreach ($reportOutput['content']['records'] as $overdueRecordToNotify) {
            if (in_array("$overdueRecordToNotify[occurrence_id]:$filter[user_id]", $occurrencesDoneForUser)) {
              // Can skip this notification - the user probably has overlapping
              // verification filters.
              continue;
            }
            else {
              // Remember we've done this one.
              $occurrencesDoneForUser[] = "$overdueRecordToNotify[occurrence_id]:$filter[user_id]";
            }
            // Save the new notification.
            $notificationObj = ORM::factory('notification');
            $notificationObj->source = 'verification_overdue_notifications';
            $notificationObj->linked_id = $overdueRecordToNotify['occurrence_id'];
            $notificationObj->acknowledged = 'false';
            $notificationObj->triggered_on = date("Ymd H:i:s");
            $notificationObj->user_id = $filter['user_id'];
            $notificationObj->source_type = 'VT';
            $info = "Record $overdueRecordToNotify[occurrence_id] of $overdueRecordToNotify[taxon] " .
              "at $overdueRecordToNotify[output_sref] on $overdueRecordToNotify[date] is overdue for verification.";
            $notificationObj->data = json_encode([
              'username' => $url['title'],
              'comment' => "<a href=\"$url[url]\">$info</a>",
              'auto_generated' => 't',
            ]);
            $notificationObj->escalate_email_priority = 2;
            $notificationObj->save();
          }
        }
      }
    }
  }

  /**
   * Retrieves all verification filters for a website ID.
   *
   * @param object $db
   *   Database connection object.
   * @param int $websiteId
   *   ID of a website that has a verification page.
   *
   * @return array
   *   Filter records loaded frmo the database.
   */
  private static function getAllVerificationFiltersForWebsite($db, $websiteId) {
    return $db
      ->select('DISTINCT f.id,f.definition,fu.user_id,u.username')
      ->from('filters f')
      ->join('filters_users as fu', 'fu.filter_id', 'f.id')
      ->join('users as u', 'u.id', 'fu.user_id')
      ->join('users_websites as uw', 'uw.user_id', 'u.id')
      ->where(array(
        'f.sharing' => 'V',
        'f.defines_permissions' => 't',
        'uw.website_id' => $websiteId,
        'f.deleted' => 'f',
        'fu.deleted' => 'f',
        'u.deleted' => 'f',
      ))
      ->get()->result_array(FALSE);
  }

  /**
   * Retrieves overdue records.
   *
   * @param object $reportEngine
   *   Report engine object.
   * @param string $workflowGroup
   *   Name of the configured workflow group.
   * @param string $lastRunDate
   *   Timestamp of the last scheduled task run.
   *
   * @return array
   *   Overdue occurrence records loaded frmo the database.
   */
  private static function getAllOverdueRecordsForWebsite($reportEngine, $workflowGroup, $lastRunDate) {
    // Get all new overdue records for website_id (+shared) into occdelta that
    // aren't already notified.
    $reportParams = [
      'workflow_overdue' => '1',
      'workflow_overdue_notification' => 'no',
      'workflow_group_code' => $workflowGroup,
      'edited_date_from' => $lastRunDate,
      'confidential' => 'all',
      'release_status' => 'A',
    ];
    $reportOutput = $reportEngine->requestReport(
      "library/occurrences/filterable_explore_list_workflow.xml", 'local', 'xml', $reportParams);
    return $reportOutput['content']['records'];
  }

}
