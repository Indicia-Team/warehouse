<?php

/**
 * @file
 * Queue worker to validate or import a chunk from an import file.
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
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Queue worker to validate or import a chunk from an import file.
 */
class task_import_step {

  public const BATCH_SIZE = 1;

  /**
   * Handle our own task expiry once import complete.
   *
   * @const bool
   */
  public const SELF_CLEANUP = TRUE;

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
    self::cleanupOldImportsWhichFailedValidation($db);
    // Get the task details.
    $task = $db->query("SELECT * FROM work_queue WHERE task = 'task_import_step' AND claimed_by = ? ORDER BY id LIMIT 1", [$procId])->current();
    if (!$task) {
      // Nothing to do.
      return;
    }
    $params = json_decode($task->params, TRUE);
    if (!isset($params['config-id'])) {
      // Invalid task, just delete it.
      kohana::log('error', 'Invalid task_import_step task with id ' . $task->id . ' - no config-id parameter');
      $db->query("DELETE FROM work_queue WHERE id = ?", [$task->id]);
      return;
    }
    // If doing the actual import, check we aren't creating huge processing
    // queues.
    if (empty($params['precheck']) && self::abortTaskIfBusy($db, $procId)) {
      return;
    }
    try {
      $r = import2ChunkHandler::importChunk($db, $params);
      // Reset the task so that it gets picked up again for the next chunk.
      unset($params['restart']);
      if ($r['status'] === 'done') {
        if ($r['errorsCount'] > 0) {
          // Precheck complete with errors, so do not proceed to real import.
          self::notifyUserOfValidationErrorsAndCleanup($db, $task, $params);
          return;
        }
        if (!empty($params['precheck'])) {
          // If precheck was done, remove the flag so that next time it does the
          // real import.
          unset($params['precheck']);
          // Also set restart so that it starts from the beginning of the file.
          $params['restart'] = TRUE;
        }
        else {
          // Import complete, so delete the task.
          kohana::log('debug', 'Import complete, removing task from queue.');
          self::notifyUserOfCompletionAndCleanup($db, $task, $params);
          return;
        }
      }

      $db->query("UPDATE work_queue SET claimed_by=null, claimed_on=null, params=? WHERE id = ?", [json_encode($params), $task->id]);
    } catch (Exception $e) {
      self::notifyUserOfCrash($db, $task, $params, $e);
    }
  }

  /**
   * Background import cleanup.
   *
   * Removes queued imports which failed validation and have been waiting for
   * the user to download the errors file for over a week. This allows the
   * associated temporary tables and files to be cleaned up.
   *
   * @param Database $db
   *   Database connection.
   */
  private static function cleanupOldImportsWhichFailedValidation($db) {
    $db->query(<<<SQL
      DELETE FROM work_queue
      WHERE task='task_import_step'
      AND error_detail = 'Failed validation, awaiting user to download errors file'
      AND claimed_on < now() - interval '1 week';
    SQL);
  }

  /**
   * Retrieve user information required to send an email.
   *
   * @param Database $db
   *   Database connection.
   * @param array $params
   *   Work queue entry parameters including the user ID.
   */
  private static function getUserInfo($db, array $params) {
    if (!isset($params['user_id'])) {
      return NULL;
    }
    return $db->query(<<<SQL
      SELECT p.first_name, p.surname, p.email_address
      FROM people p
      JOIN users u ON u.person_id=p.id AND u.deleted=false
      WHERE u.id = ? AND p.deleted=false
    SQL, [$params['user_id']])->current();
  }

  /**
   * On import completion, notify the user and cleanup the work queue task.
   *
   * @param Database $db
   *   Database connection.
   * @param object $task
   *   Work queue task.
   * @param array $params
   *   Work queue task parameters.
   */
  private static function notifyUserOfCompletionAndCleanup($db, $task, $params) {
    $personInfo = self::getUserInfo($db, $params);
    if ($personInfo) {
      $emailer = new Emailer();
      $emailer->addRecipient($personInfo->email_address, $personInfo->first_name . ' ' . $personInfo->surname);
      $emailer->send(
        'Import complete',
        "The import you requested has completed successfully.<br><br>Regards<br>Indicia Team",
        'importBackgroundProcessing'
      );
    }
    else {
      kohana::log('error', 'Could not find user with id ' . $params['user_id'] . ' to send import complete email.');
    }
    $db->query("DELETE FROM work_queue WHERE id = ?", [$task->id]);
  }

  /**
   * On crash during import, notify the user and cleanup the work queue task.
   *
   * @param Database $db
   *   Database connection.
   * @param object $task
   *   Work queue task.
   * @param array $params
   *   Work queue task parameters.
   * @param Exception $e
   *   Exception object.
   */
  private static function notifyUserOfCrash($db, $task, $params, $e) {
    $personInfo = self::getUserInfo($db, $params);
    if ($personInfo) {
      $emailer = new Emailer();
      $emailer->addRecipient($personInfo->email_address, $personInfo->first_name . ' ' . $personInfo->surname);
      $emailer->send(
        'Import failed',
        "The import you requested has failed due to a system error. Please contact your system administrator to investigate.<br><br>Regards<br>Indicia Team",
        'importBackgroundProcessing',
      );
    }
    else {
      kohana::log('error', 'Could not find user with id ' . $params['user_id'] . ' to send import failed email.');
    }
    $errorDetail = [
      'message' => $e->getMessage(),
      'trace' => error_logger::getTraceAsText($e->getTrace()),
    ];
    $db->query("UPDATE work_queue SET error_detail=?, claimed_by=null WHERE id = ?", [json_encode($errorDetail), $task->id]);
  }

  /**
   * On validation failure, notify the user and cleanup the work queue task.
   *
   * @param Database $db
   *   Database connection.
   * @param object $task
   *   Work queue task.
   * @param array $params
   *   Work queue task parameters.
   */
  private static function notifyUserOfValidationErrorsAndCleanup($db, $task, $params) {
    $personInfo = self::getUserInfo($db, $params);
    if ($personInfo) {
      $emailer = new Emailer();
      $emailer->addRecipient($personInfo->email_address, $personInfo->first_name . ' ' . $personInfo->surname);
      $link = url::site() . 'services/import_2/get_errors_file?config-id=' . $params['config-id'];
      $emailer->send(
        'Import failed validation',
        "The import you requested has failed validation.<br><a href=\"$link\">Download the rows that had errors</a><br><br>Regards<br>Indicia Team",
        'importBackgroundProcessing'
      );
    }
    else {
      kohana::log('error', 'Could not find user with id ' . $params['user_id'] . ' to send import failed validation email.');
    }
    // Don't delete the task yet, as we need it to remain so that the user can
    // download the errors file. Just set a specific error detail so that we
    // know why it is still there.
    $errorDetail = 'Failed validation, awaiting user to download errors file';
    $db->query("UPDATE work_queue SET error_detail=?, claimed_by=null WHERE id = ?", [$errorDetail, $task->id]);
  }

  private static function abortTaskIfBusy($db, $procId) {
    $queueLengthLimit = 10000;
    // Elasticsearch queue length check.
    // Cache builder update length check.
    // Spatial index builder queue length check.
    $qry = <<<SQL
      SELECT task, entity, count(*)
      FROM work_queue
      WHERE error_detail IS NULL
      AND task in ('task_spatial_index_builder_sample', 'task_cache_builder_update')
      GROUP BY task, entity HAVING count(*) > $queueLengthLimit
    SQL;
    $feeds = Kohana::config('indicia_svc_import.elasticsearch_feeds_to_monitor', FALSE, FALSE);
    if ($feeds) {
      array_walk($feeds, function(&$v, $k, $db) {
        $v = pg_escape_literal($db->getLink(), 'rest-autofeed-' . $v);
      }, $db);
      $feedCsv = implode(',', $feeds);
      $qry .= <<<SQL
        UNION ALL
        SELECT name, null, (SELECT max(tracking) FROM cache_occurrences_functional) - ((value::json->0)->>'last_tracking_id')::integer AS count
        FROM variables
        WHERE name IN ($feedCsv)
        AND (SELECT max(tracking) FROM cache_occurrences_functional) - ((value::json->0)->>'last_tracking_id')::integer > $queueLengthLimit
      SQL;
    }
    $busyTaskTypes = $db->query($qry)->result()->count();
    if ($busyTaskTypes > 0) {
      kohana::log('info', 'Aborting import step as there are ' . $busyTaskTypes . ' busy task types.');
    }
    return $busyTaskTypes > 0;
  }

}
