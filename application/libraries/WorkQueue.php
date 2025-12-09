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
      'task=' . pg_escape_literal($db->getLink(), $fields['task']) .
      ' AND entity' . (empty($fields['entity']) ? ' IS NULL' : '=' . pg_escape_literal($db->getLink(), $fields['entity'])) .
      ' AND record_id' . (empty($fields['record_id']) ? ' IS NULL' : '=' . pg_escape_literal($db->getLink(), $fields['record_id'])) .
      // Use JSONB to compare as valid in pgSQL.
      ' AND params' . (empty($fields['params']) ? ' IS NULL' : ('::jsonb=' . pg_escape_literal($db->getLink(), $fields['params']) . '::jsonb'));
    foreach ($fields as $value) {
      $setValues[] = pg_escape_literal($db->getLink(), $value);
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
   * @param bool $force
   *   If true, process even if the server load is high. Default false. Set to
   *   true when running from unit tests to ensure tasks are processed.
   */
  public function process($db, $force = FALSE) {
    $this->db = $db;
    // Use the current server CPU load to roughly guess the top cost estimate
    // to allow for tasks of each priority.
    if ($force) {
      $maxCostByPriority = [
        1 => 100,
        2 => 100,
        3 => 100,
      ];
    }
    else {
      $maxCostByPriority = $this->findMaxCostPriority();
    }
    $taskTypesToDo = $this->getTaskTypesToDo($maxCostByPriority);
    foreach ($taskTypesToDo as $taskType) {
      $helper = $taskType->task;
      $doneCount = 0;
      $errorCount = 0;
      // Loop to claim batches of tasks for this task type. Only actually
      // iterate more than once for priority 1 tasks.
      do {
        // Get a unique ID for this process run, so we can tag tasks we are
        // doing in the work_queue table and know they are ours.
        $procId = uniqid('', TRUE);
        try {
          if (!class_exists($helper)) {
            $this->failClassMissing($taskType);
            $errorCount++;
          }
          // Claim an appropriate number of records to do in a batch, depending
          // on the helper class.
          else {
            $claimedCount = $this->claim($taskType, $helper::BATCH_SIZE, $procId);
            if ($claimedCount === 0) {
              break;
            }
            call_user_func("$helper::process", $db, $taskType, $procId);
            // Tasks can be responsible for their own task garbage collection,
            // or allow a generic cleanup of all claimed tasks.
            if ($helper::SELF_CLEANUP) {
              // Any remaining tasks haven't been self-cleaned by the task
              // class, so reset them.
              $this->reset($taskType, $procId);
            }
            else {
              $this->expire($taskType, $procId);
            }
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
      2 => max(0, min(100, (integer) (160 - $load * 2))),
      3 => max(0, min(100, (integer) (160 - $load * 3))),
    ];
    global $argv;
    if (isset($argv)) {
      parse_str(implode('&', array_slice($argv, 1)), $params);
    }
    else {
      $params = $_GET;
    }
    // Allow URL parameters to limit the maximum cost.
    if (!empty($params['max-cost'])) {
      // Check value 1 to 100.
      if (!preg_match('/^[1-9][0-9]?$|^100$/', $params['max-cost'])) {
        throw new exception('Invalid max-cost parameter - integer from 1 to 100 expected.');
      }
      foreach ($maxCostByPriority as $priority => &$maxCost) {
        $maxCost = min($maxCost, $params['max-cost']);
      }
    }
    // Allow URL parameters to limit the maximum priority.
    if (!empty($params['max-priority'])) {
      if (!preg_match('/^[1-3]$/', $params['max-priority'])) {
        throw new exception('Invalid max-priority parameter - value from 1 to 3 expected.');
      }
      if ($params['max-priority'] < 3) {
        $maxCostByPriority[3] = 0;
      }
      if ($params['max-priority'] < 2) {
        $maxCostByPriority[2] = 0;
      }
    }
    // Also, allow URL parameters to limit the minimum priority.
    if (!empty($params['min-priority'])) {
      if (!preg_match('/^[1-3]$/', $params['min-priority'])) {
        throw new exception('Invalid min-priority parameter - value from 1 to 3 expected.');
      }
      if ($params['min-priority'] > 1) {
        $maxCostByPriority[1] = 0;
      }
      if ($params['min-priority'] > 2) {
        $maxCostByPriority[2] = 0;
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
    switch (PHP_OS_FAMILY) {
      case 'Windows':
        $cmd = 'powershell -command "(Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average).Average"';
        $output = [];
        @exec($cmd, $output);
        // This value doesn't need to be adjusted by number of CPUs as it is
        // already an average.
        return ((float) ($output[0] ?? 0));

      case 'Darwin':
        // Fetch CPU count on MacOS.
        exec('getconf _NPROCESSORS_ONLN', $output);
        $ncpu = $output[0];
        break;

      default:
        // Fetch CPU count - assume Linux.
        $ncpu = substr_count((string)@file_get_contents('/proc/cpuinfo'),"\nprocessor") + 1;
    }
    $load = sys_getloadavg();
    // Load over 1, 5 and 15 minutes - take average of 1 and 5 minute load.
    $loadAvg = ($load[0] + $load[1]) / 2;
    return ($loadAvg / $ncpu) * 100;
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
      WHERE ((priority=1 AND cost_estimate<=?)
      OR (priority=2 AND cost_estimate<=?)
      OR (priority=3 AND cost_estimate<=?))
      AND claimed_by IS NULL
      AND error_detail IS NULL
      GROUP BY task, entity
      ORDER BY min(priority), min(created_on), task, entity
    SQL;
      return $this->db->query($sql, [
        $maxCostByPriority[1],
        $maxCostByPriority[2],
        $maxCostByPriority[3],
      ])->result();
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
  private function claim($taskType, int $batchSize, $procId) {
    // Use an atomic query to ensure we only claim tasks where they are not
    // already claimed.
    $sql = <<<SQL
WITH rows AS (
  UPDATE work_queue
  SET claimed_by=?, claimed_on=now()
  WHERE id IN (
    SELECT id FROM work_queue
    WHERE claimed_by IS NULL
    AND error_detail IS NULL
    AND task=?
    AND COALESCE(entity, '')=COALESCE(?, '')
    ORDER BY priority, cost_estimate, id
    LIMIT ?
  )
  AND claimed_by IS NULL
  RETURNING 1
)
SELECT count(*) FROM rows;
SQL;
    // Run query and return count claimed.
    return $this->db->query($sql, [$procId, $taskType->task, $taskType->entity, $batchSize])->current()->count;
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
      'error_detail' => NULL,
    ]);
  }

  /**
   *
   * Resets a batch of claimed tasks that were claimed but never done.
   *
   * @param object $taskType
   *   Task type database row object, defining the task and entity to process.
   * @param string $procId
   *   Unique ID of this worker process.
   */
  private function reset($taskType, $procId) {
    $this->db->update('work_queue', [
      'error_detail' => NULL,
      'claimed_by' => NULL,
      'claimed_on' => NULL,
    ], [
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
   * @param Throwable $e
   *   Exception object.
   */
  private function fail($taskType, $procId, Throwable $e) {
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
    kohana::log('error', "Failure in work queue task batch because $taskType->task missing");
    error_logger::log_trace(debug_backtrace());
  }

}
