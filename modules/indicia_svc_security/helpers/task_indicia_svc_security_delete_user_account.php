<?php

/**
 * @file
 * Queue worker to update cache_occurrences_functional.taxon_path.
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
 * Test framework for handling when a user cancels their account.
 *
 * Currently sends the user cancellation email to a test account defined by
 * the deletion_user_test_id variable.
 */
class task_indicia_svc_security_delete_user_account {

  const BATCH_SIZE = 10;

  /**
   * Perform the processing for a task batch found in the queue.
   *
   * @param object $db
   *   Database connection object.
   * @param object $taskType
   *   Object read from the database for the task batch. Contains the task
   *   name, entity, priority, created_on of the first record in the batch
   *   count (total number of queued tasks of this type).
   * @param string $procId
   *   Unique identifier of this work queue processing run. Allows filtering
   *   against the work_queue table's claimed_by field to determine which
   *   tasks to perform.
   */
  public static function process($db, $taskType, $procId) {
    $anonymousUserId = self::getAnonymousUserId($db, $procId);
    $jobs = $db
      ->select("record_id as user_id, (params::json->>'website_id')::integer as website_id")
      ->from('work_queue')
      ->where([
        'entity' => 'user',
        'task' => 'task_indicia_svc_security_delete_user_account',
        'claimed_by' => $procId,
      ])
      ->get()->result();
    foreach ($jobs as $job) {
      // Call function replaceUserIdWithAnonId($job->userId, $job->website_id, $anonymousUserId);
      self::sendWebsitesListEmail($db, $procId, $job->userId, $job->website_id);
    }
  }

  /**
   * Get the user ID of the special anonymous user.
   *
   * @param object $db
   *   Database connection object.
   *
   * @return integer
   *   User ID of the special anonymous user.
   */
  public static function getAnonymousUserId($db) {
    $anonymousUserId = $db
      ->select("users.id as anonymous_user_id")
      ->from('users')
      ->join('people', 'people.id', 'users.person_id')
      ->where([
        'users.deleted' => 'false',
        'people.external_key' => 'indicia:anonymous',
        'people.deleted' => 'false',

      ])
      ->limit(1)
      ->get()->result_array();
    return $anonymousUserId[0]->anonymous_user_id;
  }

  /**
   * Send test email to tes user account.
   *
   * Send email to user containing details of the websites they are still a
   * member of (after they have left their current website).
   * Email currently only sent to test account defined by
   * the deletion_user_test_id variable.
   *
   * @param object $db
   *   Database connection object.
   * @param int $userId
   *   User ID being deleted.
   * @param int $websiteId
   *   Website ID they are being deleted from.
   */
  private static function sendWebsitesListEmail($db, $userId, $websiteId) {
    if (kohana::config('indicia_svc_security.deletion_user_test_id') != 0) {
      $deletionUserTestId = kohana::config('indicia_svc_security.deletion_user_test_id');
    }
    else {
      $deletionUserTestId = NULL;
    }
    if (!empty($deletionUserTestId)) {
      if (!empty($deletionUserTestId)) {
        $peopleResults = $db
          ->select('people.email_address')
          ->from('people')
          ->join('users', 'users.person_id', 'people.id')
          ->where('users.id', $deletionUserTestId)
          ->limit(1)
          ->get()->result_array();
      }

      $swift = email::connect();
      $emailSenderAddress = self::setupEmailSenderAddress();
      $emailSubject = self::setupEmailSubject();
      $websiteListUserIsStillMemberOf = self::getUserWebsitesList($db, $deletionUserTestId);
      $emailContent = self::setupEmailContent($db, $websiteListUserIsStillMemberOf);
      $recipients = self::setupEmailRecipients($peopleResults[0]->email_address);
      $message = new Swift_Message($emailSubject, "<html>$emailContent</html>", 'text/html');
      $swift->send($message, $recipients, $emailSenderAddress);
      kohana::log('info', 'Website membership email sent to ' . $peopleResults[0]->email_address);
    }
  }

  /**
   * Collect address of email sender from configuration.
   *
   * @return string
   *   String containing the sender address.
   */
  private static function setupEmailSenderAddress() {
    // Try and get from configuration file if possible.
    try {
      $emailSenderAddress = kohana::config('indicia_svc_security.email_sender_address');
    }
    catch (Exception $e) {
      throw new Exception('Could not send the website membership information email, because the email sender address configuration was not specified.');
    }
    return $emailSenderAddress;
  }

  /**
   * Collect the subject line from configuration.
   *
   * @return string
   *   String containing the subject line.
   */
  private static function setupEmailSubject() {
    try {
      $emailSubject = kohana::config('indicia_svc_security.email_subject');
    }
    // Handle config file not present.
    catch (Exception $e) {
      throw new Exception('Could not send the website membership information email, because the email subject configuration was not specified.');
    }
    return $emailSubject;
  }

  /**
   * Collect the email content.
   *
   * @param object $db
   *   Database connection object.
   * @param array $websiteListUserIsStillMemberOf
   *   A list of website names the user is still a member of.
   *
   * @return string
   *   String containing the email's content.
   */
  private static function setupEmailContent($db, array $websiteListUserIsStillMemberOf) {

    try {
      $emailContent = kohana::config('indicia_svc_security.email_content');
      if (!empty($websiteListUserIsStillMemberOf)) {
        $emailContent .= "<div>" . implode("\n<br>", $websiteListUserIsStillMemberOf) . "</div>";
      }
    }
    catch (Exception $e) {
      throw new Exception('Could not send the website membership information email, because the email content configuration was not specified.');
    }
    return $emailContent;
  }

  /**
   * Collect the email content that contains the details of the user's websites.
   *
   * @return string
   *   String containing the email address of the recipient.
   */
  private static function setupEmailRecipients($emailAddress) {
    $recipients = new Swift_RecipientList();
    $recipients->addTo($emailAddress);
    return $recipients;
  }

  /**
   * Collect a list of websites user is still a member of to put in email.
   *
   * @param object $db
   *   Database connection object.
   * @param int $warehouseUserId
   *   Warehouse ID of user we are deleting.
   *
   * @return array
   *   Array of websites the user is still a member of.
   */
  private static function getUserWebsitesList($db, $warehouseUserId) {
    $usersWebsitesResults = $db
      ->select('websites.title')
      ->from('users_websites')
      ->join('websites', 'websites.id', 'users_websites.website_id')
      ->where(['users_websites.user_id' => $warehouseUserId])
      ->get()->result_array();
    // Convert result into a one dimensional array.
    $streamlinedUsersWebsitesResults = [];
    foreach ($usersWebsitesResults as $usersWebsitesResult) {
      $streamlinedUsersWebsitesResults[] = $usersWebsitesResult->title;
    }
    return $streamlinedUsersWebsitesResults;
  }

}
