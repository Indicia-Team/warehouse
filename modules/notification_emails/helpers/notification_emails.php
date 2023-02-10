<?php

/**
 * @file
 * Helper functions for the notification emails module.
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
 * @package Modules
 * @subpackage Notification emails
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Notification email helper class.
 */
class notification_emails {

  /**
   * Returns a list of notification types with names and optional descriptions.
   *
   * @return array
   *   Associative array of types keyed by type code.
   */
  public static function getNotificationTypes() {
    $config = kohana::config('notification_emails.notification_types', FALSE, FALSE);
    $defaultTypeNames = array(
      'S' => 'Species alerts',
      'C' => 'Comments on your records',
      'Q' => 'Queries on your records',
      'RD' => 'Redeterminations of your records',
      'V' => 'Verification of your records',
      'A' => 'Record Cleaner results for your records',
      'VT' => 'Incoming records for you to verify',
      'M' => 'Milestones and achievements you\'ve attained',
      'PT' => 'Incoming pending records for you to check',
      'GU' => 'Pending users in groups you administer',
    );
    $r = [];
    foreach ($defaultTypeNames as $code => $defaultTitle) {
      $typeData = array(
        'title' => $defaultTitle,
      );
      if ($config && isset($config[$code])) {
        $typeData = array_merge($typeData, $config[$code]);
      }
      $r[$code] = $typeData;
    }
    return $r;
  }

  /**
   * Returns a list of all known record status codes along with the display text associated with each.
   *
   * @return array
   *   Associative array of record status + substatus codes with their descriptions.
   */
  public static function getRecordStatuses() {
    return array(
      'T' => 'Test',
      'I' => 'Data entry in progress',
      'V' => 'Accepted',
      'V1' => 'Accepted as correct',
      'V2' => 'Accepted as considered correct',
      'C' => 'Awaiting review',
      'C3' => 'Plausible',
      'D' => 'Queried',
      'R' => 'Not accepted',
      'R4' => 'Not accepted as unable to verify',
      'R5' => 'Not accepted as incorrect',
    );
  }

  /**
   * Retrieve the frequencies we are going to run this time round (e.g. hourly, or daily etc).
   *
   * Collect the notification frequency jobs that needs to be run now. For instance if it is less than a week
   * since the weekly notification frequency job was last run, then we don't need to run it now. As soon as
   * we detect that it has been longer than a week then we know that is one of the jobs we need to run now.
   *
   * @param object $db
   *   Database connection object.
   *
   * @return array
   *   List of frequencies we need to process.
   */
  public static function getFrequenciesToRunNow($db) {
    $frequenciesToRun = $db->query("
      SELECT notification_frequency
      FROM user_email_notification_frequency_last_runs
      WHERE
      (notification_frequency='IH' AND now()>=(last_run_date + interval '1 hour'))
      OR
      (notification_frequency='D' AND now()>=(last_run_date + interval '1 day'))
      OR
      (notification_frequency='W' AND now()>=(last_run_date + interval '1 week'))
      OR
      last_run_date IS NULL
    ")->result_array(FALSE);
    return $frequenciesToRun;
  }

}
