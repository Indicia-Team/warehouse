<?php

/**
 * @file
 * Library class to provide task queue processing functions.
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

 // For PHP 5, map throwable to exception so it doesn't cause errors.
 // Can be removed once PHP 5 support dropped.
 if (!interface_exists('Throwable')) {
   class Throwable extends Exception {}
 }

/**
 * Library class to provide task queue processing functions.
 */
class WorkQueue {

  /**
   * Database connection object.
   *
   * @var object
   */
  private $db;

  /**
   * Queue a task for later processing.
   *
   * Inserts a task into the work_queue table.
   *
   * @param object $db
   *   Database connection object.
   * @param array $fields
   *   Associative array of field values to insert:
   *   * task - task name which should match the name of the helper class which
   *     performs the task.
   *   * entity - database entity name if the task operates on a database
   *     table.
   *   * record_id - ID of record in the table identified by entity, if the
   *     task operates on a single record.
   *   * cost_estimate - value from 1 (low cost/fast) to 100 (high cost/slow)
   *     for the estimated cost of performing the task. Used to facilitate
   *     prioritisation based on current server load.
   *   * priority - value from 1 (high priority) to 3 (low priority).
   */
  public function enqueue($db, array $fields) {
    // Set the metadata.
    $fields['created_on'] = date("Ymd H:i:s");
    // Slightly convoluted build of the INSERT query so we can do a NOT EXISTS
    // to avoid duplicates in the queue.
    $setValues = [];
    $existsCheckSql =
      'task=' . pg_escape_literal($fields['task']) .
      'AND entity' . (empty($fields['entity']) ? ' IS NULL' : '=' . pg_escape_literal($fields['entity'])) .
      'AND record_id' . (empty($fields['record_id']) ? ' IS NULL' : '=' . pg_escape_literal($fields['record_id'])) .
      'AND params' . (empty($fields['params']) ? ' IS NULL' : '=' . pg_escape_literal($fields['params']));
    foreach ($fields as $value) {
      $setValues[] = pg_escape_literal($value);
    }
    $setFieldList = implode(', ', array_keys($fields));
    $setValueList = implode(', ', $setValues);
    $sql = <<<SQL
INSERT INTO work_queue ($setFieldList)
SELECT $setValueList
WHERE NOT EXISTS(
  SELECT 1 FROM work_queue WHERE $existsCheckSql
)
SQL;
    // Avoid any kohana overhead when running the query and prevent out query
    // result 'damaging' things as this is in postSubmit.
    $db->justRunQuery($sql);
  }

  /**
   * Processes a batch of tasks.
   *
   * @param object $db
   *   Database connection object.
   */
  public function process($db) {
    $this->db = $db;
    // Get a unique ID for this process run, so we can tag tasks we are doing
    // in the work_queue table and know they are ours.
    $procId = uniqid();
    // Use the current server CPU load to roughly guess the top cost estimate
    // to allow for tasks of each priority.
    $maxCostByPriority = $this->findMaxCostPriority();
    $taskTypesToDo = $this->getTaskTypesToDo($maxCostByPriority);

    foreach ($taskTypesToDo as $taskType) {
      $helper = $taskType->task;
      $doneCount = 0;
      $errorCount = 0;
      // Loop to claim batches of tasks for this task type. Only actually
      // iterate more than once for priority 1 tasks.
      do {
        try {
          // Claim an appropriate number of records to do in a batch, depending on
          // the helper class.
          if (!class_exists($helper)) {
            $this->failClassMissing($taskType);
            $errorCount++;
          }
          else {
            $claimedCount = $this->claim($taskType, $helper::BATCH_SIZE, $procId);
            if ($claimedCount === 0) {
              break;
            }
            call_user_func("$helper::process", $db, $taskType, $procId);
            $this->expire($taskType, $procId);
            $doneCount += $claimedCount;
          }
        }
        catch (Throwable $e) {
          $this->fail($taskType, $procId, $e);
          $errorCount++;
        }
      } while ($taskType->priority === 1 && $doneCount < $taskType->count);
      $errors = $errorCount === 0 ? '' : " with $errorCount batch failure(s).";
      echo "Work queue - $taskType->task ($taskType->entity): $doneCount done$errors<br/>";
    }
  }

  /**
   * Calculate maximum costs allowed per task priority.
   *
   * Use the current server CPU load to roughly guess the top cost estimate to
   * allow for tasks of each priority.
   *
   * @return array
   *   Array of recommended maximum cost estimates keyed by priority (1-3).
   */
  private function findMaxCostPriority() {
    $load = $this->getServerLoad();
    $maxCostByPriority = [
      1 => max(0, min(100, (integer) (160 - $load))),
      2 => max(0, min(100, (integer) (145 - $load * 2))),
      3 => max(0, min(100, (integer) (130 - $load * 3))),
    ];
    // Allow URL parameters to limit the maximum cost.
    if (!empty($_GET['max-cost'])) {
      // Check value 1 to 100.
      if (!preg_match('/^[1-9][0-9]?$|^100$/', $_GET['max-cost'])) {
        throw new exception('Invalid max-cost parameter - integer from 1 to 100 expected.');
      }
      foreach ($maxCostByPriority as $priority => &$maxCost) {
        $maxCost = min($maxCost, $_GET['max-cost']);
      }
    }
    // Allow URL parameters to limit the maximum priority.
    if (!empty($_GET['max-priority'])) {
      if (!preg_match('/^[1-3]$/', $_GET['max-priority'])) {
        throw new exception('Invalid max-priority parameter - value from 1 to 3 expected.');
      }
      if ($_GET['max-priority'] < 3) {
        unset($maxCostByPriority[3]);
      }
      if ($_GET['max-priority'] < 2) {
        unset($maxCostByPriority[2]);
      }
    }
    return $maxCostByPriority;
  }

  /**
   * Return average CPU usage across available cores.
   *
   * Used to help calculate how much additional load (in terms of task cost
   * estimate) to allow.
   *
   * @return float
   *   CPU usage as a percentage.
   */
  private function getServerLoad() {
    $load = [];
    if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
      $wmi = new COM("Winmgmts://");
      $server = $wmi->execquery("SELECT LoadPercentage FROM Win32_Processor");
      $cpu_num = 0;
      $load_total = 0;
      foreach ($server as $cpu) {
        $cpu_num++;
        $load_total += $cpu->loadpercentage;
      }
      $load[] = round($load_total / $cpu_num);
    }
    else {
      $load = sys_getloadavg();
    }
    return array_sum($load) / count($load);
  }

  /**
   * Retrieve the task types from the queue that we would like to process.
   *
   * Bases this on the max costs by priority calculated from the CPU load.
   *
   * @param array $maxCostByPriority
   *   Array of recommended maximum cost estimates keyed by priority (1-3).
   *
   * @return object
   *   Database result object from the work queue query.
   */
  private function getTaskTypesToDo(array $maxCostByPriority) {
    $sql = <<<SQL
SELECT DISTINCT task, entity, min(priority) as priority, min(created_on) as created_on, count(id)
FROM work_queue
WHERE ((priority=1 AND cost_estimate<=$maxCostByPriority[1])
OR (priority=2 AND cost_estimate<=$maxCostByPriority[2])
OR (priority=3 AND cost_estimate<=$maxCostByPriority[3]))
AND claimed_by IS NULL
AND error_detail IS NULL
GROUP BY task, entity
ORDER BY min(priority), min(created_on), task, entity
SQL;
    return $this->db->query($sql)->result($sql);
  }

  /**
   * Claim a batch of tasks from the queue.
   *
   * Ensures that 2 PHP processes can't claim the same tasks. Sets the
   * claimed_by to the value in $procId and also updates the claimed_on field
   * for the claimed tasks.
   *
   * @param object $taskType
   *   Task type database row object, defining the task and entity to process.
   * @param int $batchSize
   *   Maximum number of tasks to claim.
   * @param string $procId
   *   Unique ID of this worker process.
   *
   * @return int
   *   Number of records claimed.
   */
  private function claim($taskType, $batchSize, $procId) {
    // Use an atomic query to ensure we only claim tasks where they are not
    // already claimed.
    $sql = <<<SQL
UPDATE work_queue
SET claimed_by='$procId', claimed_on=now()
WHERE id IN (
  SELECT id FROM work_queue
  WHERE claimed_by IS NULL
  AND error_detail IS NULL
  AND task='$taskType->task'
  AND COALESCE(entity, '')='$taskType->entity'
  LIMIT $batchSize
);
SQL;
    $this->db->query($sql);
    // Now return the count we actually claimed.
    $sql = <<<SQL
SELECT COUNT(id) FROM work_queue
WHERE claimed_by='$procId'
AND task='$taskType->task'
AND COALESCE(entity, '')='$taskType->entity'
SQL;
    return $this->db->query($sql)->current()->count;
  }

  /**
   * Expires a batch of claimed tasks that are now done.
   *
   * @param object $taskType
   *   Task type database row object, defining the task and entity to process.
   * @param string $procId
   *   Unique ID of this worker process.
   */
  private function expire($taskType, $procId) {
    $this->db->delete('work_queue', [
      'claimed_by' => $procId,
      'task' => $taskType->task,
      'entity' => $taskType->entity,
    ]);
  }

  /**
   * If an exception detected during task processing, records the error.
   *
   * @param object $taskType
   *   Task type database row object, defining the task and entity to process.
   * @param string $procId
   *   Unique ID of this worker process.
   */
  private function fail($taskType, $procId, $e) {
    $this->db->update('work_queue', [
      'error_detail' => $e->__toString(),
    ], [
      'claimed_by' => $procId,
      'task' => $taskType->task,
      'entity' => $taskType->entity,
    ]);
    error_logger::log_error("Failure in work queue task batch claimed by $procId", $e);
  }

  /**
   * If the helper class does not exist, set the error on the work queue.
   *
   * @param object $taskType
   *   Task type database row object, defining the task and entity to process.
   */
  private function failClassMissing($taskType) {
    $this->db->update('work_queue', [
      'error_detail' => "Worker class $taskType->task missing.",
    ], [
      'task' => $taskType->task,
    ]);
    error_logger::log_error("Failure in work queue task batch because $taskType->task missing", $e);
  }

}