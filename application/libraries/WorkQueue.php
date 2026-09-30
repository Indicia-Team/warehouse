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

  private const FULL_WORKLOAD_THRESHOLD = 50;
  private const SATURATED_WORKLOAD_THRESHOLD = 95;

  private static $shutdownFunctionRegistered = FALSE;

  /**
   * Database connection object.
   *
   * @var object
   */
  private $db;

  /**
   * Track procIds claimed in this process run for shutdown cleanup.
   *
   * @var array
   */
  private $claimedProcIds = [];

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
    $this->registerShutdownFunction();
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
            $claimedCount = $this->claim($taskType, $helper::BATCH_SIZE, $procId, $maxCostByPriority);
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
    $utilisation = $this->getServerLoad() + 40;
    $maxCostByPriority = $this->calculateMaxCostByPriority($utilisation);
    global $argv;
    if (isset($argv)) {
      parse_str(implode('&', array_slice($argv, 1)), $params);
    }
    else {
      $params = $_GET;
    }
    $maxCostByPriority = $this->applyRequestLimits($maxCostByPriority, $params);
    kohana::log('debug', sprintf(
      'Work queue CPU utilisation %.1f%%; maximum costs P1=%d, P2=%d, P3=%d.',
      $utilisation,
      $maxCostByPriority[1],
      $maxCostByPriority[2],
      $maxCostByPriority[3]
    ));
    return $maxCostByPriority;
  }

  /**
   * Apply request limits to the load-based cost limits.
   *
   * @param array $maxCostByPriority
   *   Load-based maximum costs keyed by priority.
   * @param array $params
   *   Request or command-line parameters.
   *
   * @return array
   *   Restricted maximum costs keyed by priority.
   */
  private function applyRequestLimits(array $maxCostByPriority, array $params) {
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
   * Calculate task cost limits from CPU utilisation.
   *
   * Leave the queue unrestricted until the server is half busy, then reduce
   * the permitted cost progressively. Lower priority tasks are reduced more
   * aggressively so that cheap high-priority work can still make progress.
   *
   * @param float $utilisation
   *   CPU utilisation as a percentage from 0 to 100.
   *
   * @return array
   *   Maximum task costs keyed by priority.
   */
  private function calculateMaxCostByPriority($utilisation) {
    $utilisation = max(0, min(100, (float) $utilisation));
    if ($utilisation <= self::FULL_WORKLOAD_THRESHOLD) {
      $headroom = 1;
    }
    elseif ($utilisation >= self::SATURATED_WORKLOAD_THRESHOLD) {
      $headroom = 0;
    }
    else {
      $headroom = (self::SATURATED_WORKLOAD_THRESHOLD - $utilisation) /
        (self::SATURATED_WORKLOAD_THRESHOLD - self::FULL_WORKLOAD_THRESHOLD);
    }
    return [
      1 => (int) round(10 + 90 * $headroom),
      2 => (int) round(100 * $headroom ** 1.5),
      3 => (int) round(100 * $headroom ** 2),
    ];
  }

  /**
  * Return CPU utilisation as a percentage.
    *
   * @return float
   *   CPU usage as a percentage.
   */
  private function getServerLoad() {
    switch (PHP_OS_FAMILY) {
      case 'Windows':
        $cmd = 'powershell -command "(Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average).Average"';
        $output = [];
        $exitCode = 1;
        if (function_exists('exec')) {
          @exec($cmd, $output, $exitCode);
        }
        return $this->parseWindowsCpuLoad($output, $exitCode);

      case 'Darwin':
        return $this->getMacCpuUtilisation();

      default:
        return $this->getLinuxCpuUtilisation();
    }
  }

  /**
   * Read CPU utilisation from the macOS top command.
   *
   * @return float
   *   CPU utilisation percentage, or 0 if it cannot be read.
   */
  private function getMacCpuUtilisation() {
    if (!function_exists('exec')) {
      return 0;
    }
    $output = [];
    $exitCode = 1;
    @exec("LC_ALL=C top -l 2 -n 0 -s 1 | grep 'CPU usage' | tail -1", $output, $exitCode);
    if ($exitCode !== 0 || empty($output)) {
      return 0;
    }
    $idle = $this->parseMacCpuIdle($output);
    if ($idle === NULL) {
      return 0;
    }
    return $this->normaliseServerLoad(100 - $idle);
  }

  /**
   * Read CPU utilisation from Linux /proc/stat over a short interval.
   *
   * @return float
   *   CPU utilisation percentage, or 0 if it cannot be read.
   */
  private function getLinuxCpuUtilisation() {
    $cgroupUtilisation = $this->getLinuxCgroupCpuUtilisation();
    if ($cgroupUtilisation !== NULL) {
      return $cgroupUtilisation;
    }
    $first = $this->readLinuxCpuCounters();
    if ($first === NULL) {
      return 0;
    }
    usleep(100000);
    $second = $this->readLinuxCpuCounters();
    if ($second === NULL) {
      return 0;
    }
    $totalDelta = $second['total'] - $first['total'];
    $idleDelta = $second['idle'] - $first['idle'];
    if ($totalDelta <= 0) {
      return 0;
    }
    return $this->normaliseServerLoad(($totalDelta - $idleDelta) / $totalDelta * 100);
  }

  /**
   * Read CPU utilisation relative to a Linux cgroup CPU quota, when present.
   *
   * @return float|null
   *   CPU utilisation percentage, or NULL when no usable quota is available.
   */
  private function getLinuxCgroupCpuUtilisation() {
    $usagePath = $this->getLinuxCgroupFile('', 'cpu.stat', '/sys/fs/cgroup/cpu.stat');
    $limitPath = $this->getLinuxCgroupFile('', 'cpu.max', '/sys/fs/cgroup/cpu.max');
    $cpusetCpus = NULL;
    $usageUnit = 1;
    if (!is_readable($usagePath) || !is_readable($limitPath)) {
      $usagePath = $this->getLinuxCgroupFile('cpuacct', 'cpuacct.usage', '/sys/fs/cgroup/cpuacct/cpuacct.usage');
      $quotaPath = $this->getLinuxCgroupFile('cpu', 'cpu.cfs_quota_us', '/sys/fs/cgroup/cpu/cpu.cfs_quota_us');
      $periodPath = $this->getLinuxCgroupFile('cpu', 'cpu.cfs_period_us', '/sys/fs/cgroup/cpu/cpu.cfs_period_us');
      $cpusetPath = $this->getLinuxCgroupFile('cpuset', 'cpuset.cpus', '/sys/fs/cgroup/cpuset/cpuset.cpus');
      if (!is_readable($usagePath)) {
        return NULL;
      }
      $quota = is_readable($quotaPath) ? trim((string) @file_get_contents($quotaPath)) : NULL;
      $period = is_readable($periodPath) ? trim((string) @file_get_contents($periodPath)) : NULL;
      if (is_numeric($quota) && is_numeric($period) && (float) $quota > 0 && (float) $period > 0) {
        $limit = [$quota, $period];
        if (is_readable($cpusetPath)) {
          $cpusetCpus = $this->parseCpuSetCount(@file_get_contents($cpusetPath));
          if ($cpusetCpus === NULL) {
            return NULL;
          }
        }
      }
      else {
        $cpusetCpus = is_readable($cpusetPath)
          ? $this->parseCpuSetCount(@file_get_contents($cpusetPath))
          : NULL;
        if ($cpusetCpus === NULL) {
          return NULL;
        }
        $limit = [$cpusetCpus, 1];
      }
      $usageKey = NULL;
      $usageUnit = 0.001;
    }
    else {
      $limit = preg_split('/\s+/', trim((string) @file_get_contents($limitPath)));
      $usageKey = 'usage_usec';
      $cpusetPath = $this->getLinuxCgroupFile('', 'cpuset.cpus.effective', '/sys/fs/cgroup/cpuset.cpus.effective');
      if (is_readable($cpusetPath)) {
        $cpusetCpus = $this->parseCpuSetCount(@file_get_contents($cpusetPath));
        if ($cpusetCpus === NULL) {
          return NULL;
        }
      }
      if (isset($limit[0]) && $limit[0] === 'max') {
        if ($cpusetCpus === NULL) {
          return NULL;
        }
        $limit = [$cpusetCpus, 1];
      }
    }
    if (count($limit) < 2 || $limit[0] === 'max' || !is_numeric($limit[0]) || (float) $limit[1] <= 0) {
      return NULL;
    }
    $first = $this->readLinuxCgroupUsage($usagePath, $usageUnit, $usageKey);
    if ($first === NULL) {
      return NULL;
    }
    $start = microtime(TRUE);
    usleep(100000);
    $second = $this->readLinuxCgroupUsage($usagePath, $usageUnit, $usageKey);
    $elapsed = microtime(TRUE) - $start;
    if ($second === NULL || $elapsed <= 0) {
      return NULL;
    }
    $quotaCpus = (float) $limit[0] / (float) $limit[1];
    return $this->calculateCgroupCpuUtilisation($first, $second, $elapsed, $quotaCpus, $cpusetCpus);
  }

  /**
   * Resolve a cgroup file for the current process.
   *
   * @param string $controller
   *   cgroup v1 controller, or an empty string for cgroup v2.
   * @param string $file
   *   File name within the process cgroup.
   * @param string $fallback
   *   Legacy path to use when membership cannot be read.
   *
   * @return string
   *   Resolved cgroup file path.
   */
  private function getLinuxCgroupFile($controller, $file, $fallback) {
    $membership = @file_get_contents('/proc/self/cgroup');
    if ($membership === FALSE) {
      return $fallback;
    }
    foreach (preg_split('/\r?\n/', trim($membership)) as $line) {
      $parts = explode(':', $line, 3);
      if (count($parts) !== 3) {
        continue;
      }
      $controllers = $parts[1] === '' ? [] : explode(',', $parts[1]);
      if (($controller === '' && $parts[0] === '0' && empty($controllers))
        || ($controller !== '' && in_array($controller, $controllers, TRUE))) {
        $relativePath = trim($parts[2], '/');
        return '/sys/fs/cgroup' . ($relativePath === '' ? '' : '/' . $relativePath) . '/' . $file;
      }
    }
    return $fallback;
  }

  /**
   * Calculate CPU utilisation from two cgroup usage readings.
   *
   * @param float $first
   *   Initial usage in microseconds.
   * @param float $second
   *   Final usage in microseconds.
   * @param float $elapsed
   *   Wall-clock interval in seconds.
   * @param float $quotaCpus
   *   Effective CPU entitlement.
   *
  * @param int|null $cpusetCpus
  *   Optional cpuset CPU entitlement.
  *
  * @return float|null
   *   CPU utilisation percentage, or NULL for invalid measurements.
   */
  private function calculateCgroupCpuUtilisation($first, $second, $elapsed, $quotaCpus, $cpusetCpus = NULL) {
    if ($cpusetCpus !== NULL) {
      $quotaCpus = min($quotaCpus, $cpusetCpus);
    }
    if ($second <= $first || $elapsed <= 0 || $quotaCpus <= 0) {
      return NULL;
    }
    $usageSeconds = ($second - $first) / 1000000;
    return $this->normaliseServerLoad($usageSeconds / ($elapsed * $quotaCpus) * 100);
  }

  /**
  * Read cgroup CPU usage in microseconds.
    *
   * @param string $usagePath
   *   Path to the cgroup cpu.stat file.
  * @param float $usageUnit
   *   Conversion factor from the file unit to microseconds.
  * @param string|null $usageKey
  *   cgroup v2 usage key, or NULL for cgroup v1's raw usage file.
  *
   * @return float|null
   *   CPU usage, or NULL when unavailable.
   */
  private function readLinuxCgroupUsage($usagePath, $usageUnit, $usageKey = 'usage_usec') {
    $usage = @file_get_contents($usagePath);
    if ($usage === FALSE) {
      return NULL;
    }
    if ($usageKey === NULL) {
      if (!is_numeric(trim($usage))) {
        return NULL;
      }
      return (float) trim($usage) * $usageUnit;
    }
    if (preg_match('/(?:^|\s)' . preg_quote($usageKey, '/') . '\s+(\d+)/', $usage, $matches) !== 1) {
      return NULL;
    }
    return (float) $matches[1] * $usageUnit;
  }

  /**
   * Count CPUs in a Linux cpuset expression such as 0-3,6.
   *
   * @param string|false $cpuset
   *   Contents of a cpuset file.
   *
   * @return int|null
   *   Number of CPUs, or NULL for malformed input.
   */
  private function parseCpuSetCount($cpuset) {
    if (!is_string($cpuset) || trim($cpuset) === '') {
      return NULL;
    }
    $count = 0;
    foreach (explode(',', trim($cpuset)) as $range) {
      if (preg_match('/^(\d+)(?:-(\d+))?$/', trim($range), $matches) !== 1) {
        return NULL;
      }
      $end = isset($matches[2]) ? (int) $matches[2] : (int) $matches[1];
      $start = (int) $matches[1];
      if ($end < $start) {
        return NULL;
      }
      $count += $end - $start + 1;
    }
    return $count > 0 ? $count : NULL;
  }

  /**
   * Read aggregate Linux CPU counters.
   *
   * @return array|null
   *   Total and idle jiffies, or NULL when unavailable.
   */
  private function readLinuxCpuCounters() {
    $stat = @file_get_contents('/proc/stat');
    if ($stat === FALSE || preg_match('/^cpu\s+(.+)$/m', $stat, $matches) !== 1) {
      return NULL;
    }
    return $this->parseLinuxCpuCounters($matches[0]);
  }

  /**
   * Parse aggregate Linux CPU counters.
   *
   * @param string $stat
   *   Contents of /proc/stat.
   *
   * @return array|null
   *   Total and idle jiffies, or NULL for malformed input.
   */
  private function parseLinuxCpuCounters($stat) {
    if (preg_match('/^cpu\s+(.+)$/m', $stat, $matches) !== 1) {
      return NULL;
    }
    $values = preg_split('/\s+/', trim($matches[1]));
    if (count($values) < 4 || count(array_filter($values, 'is_numeric')) !== count($values)) {
      return NULL;
    }
    return [
      'total' => array_sum($values),
      'idle' => (float) $values[3] + (float) ($values[4] ?? 0),
    ];
  }

  /**
   * Clamp and validate a server utilisation value.
   *
   * @param mixed $value
   *   Candidate percentage.
   *
   * @return float
   *   A percentage from 0 to 100.
   */
  private function normaliseServerLoad($value) {
    return is_numeric($value) && is_finite((float) $value)
      ? max(0, min(100, (float) $value))
      : 0;
  }

  /**
   * Parse a macOS top output line and return its idle percentage.
   *
   * @param array $output
   *   Command output lines.
   *
   * @return float|null
   *   Idle percentage, or NULL when not found.
   */
  private function parseMacCpuIdle(array $output) {
    $output = implode(' ', $output);
    return preg_match('/([0-9.]+)% idle/', $output, $matches) === 1
      ? $this->normaliseServerLoad($matches[1])
      : NULL;
  }

  /**
   * Parse validated Windows processor output.
   *
   * @param array $output
   *   Command output lines.
   * @param int $exitCode
   *   Command exit code.
   *
   * @return float
   *   CPU utilisation percentage, or 0 when unavailable.
   */
  private function parseWindowsCpuLoad(array $output, $exitCode) {
    return $exitCode === 0 && isset($output[0])
      ? $this->normaliseServerLoad($output[0])
      : 0;
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
  * @param array $maxCostByPriority
  *   Maximum task costs keyed by priority.
   *
   * @return int
   *   Number of records claimed.
   */
  private function claim($taskType, int $batchSize, $procId, array $maxCostByPriority) {
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
          AND ((priority=1 AND cost_estimate<=?)
            OR (priority=2 AND cost_estimate<=?)
            OR (priority=3 AND cost_estimate<=?))
          ORDER BY priority, cost_estimate, id
          LIMIT ?
        )
        AND claimed_by IS NULL
        RETURNING 1
      )
      SELECT count(*) FROM rows;
    SQL;
    // Run query and return count claimed.
    $claimedCount = $this->db->query($sql, [
      $procId,
      $taskType->task,
      $taskType->entity,
      $maxCostByPriority[1],
      $maxCostByPriority[2],
      $maxCostByPriority[3],
      $batchSize,
    ])->current()->count;
    // Track this procId for shutdown handler.
    if ($claimedCount > 0 && !in_array($procId, $this->claimedProcIds, TRUE)) {
      $this->claimedProcIds[] = $procId;
    }
    return $claimedCount;
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

  /**
   * Registers a function to be executed on shutdown.
   *
   * Should be more reliable at logging fatal errors which bypass normal error
   * handling, such as memory errors.
   */
  private function registerShutdownFunction() {
    if (!self::$shutdownFunctionRegistered) {
      register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
          $errorMessage = "Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}";
          kohana::log('error', $errorMessage);
          // In case scheduled tasks running in browser context.
          echo 'Fatal error during shutdown: ' . $errorMessage . '<br/>';
          $errorDetail = [
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'type' => $error['type'],
            'shutdown' => TRUE,
          ];
          // Update only tasks claimed by this specific process.
          foreach ($this->claimedProcIds as $procId) {
            try {
              $this->db->query("UPDATE work_queue SET error_detail=? WHERE claimed_by=? AND error_detail IS NULL", [json_encode($errorDetail), $procId]);
            }
            catch (Throwable $dbError) {
              kohana::log('error', "Failed to update work_queue on shutdown: " . $dbError->getMessage());
            }
          }
        }
        else {
          kohana::log('debug', 'Work queue shutdown complete without fatal error.');
        }
      });
      self::$shutdownFunctionRegistered = TRUE;
    }
  }

}
