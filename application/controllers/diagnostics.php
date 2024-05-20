<?php

/**
 * @file
 * Controller for a diagnostics dashboard.
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
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Controller class for a diagnostics dashboard.
 */
class Diagnostics_Controller extends Indicia_Controller {

  public function index() {
    $this->template->title = 'Warehouse diagnostics & maintenance';
    $this->template->content = new View('diagnostics/index');
  }

  /**
   * Resatrict page to core admin.
   *
   * @return bool
   *   True if user has core admin role.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin');
  }

  /**
   * Ajax end-point for running maintenance tasks.
   */
  public function maintenance() {
    // no template as this is for AJAX
    $this->auto_render = FALSE;
    $db = new Database();
    $log = [];

    $this->repairWorkQueueDeadlocks($db, $log);
    $this->repairRecentSamplesWithoutSpatialIndex($db, $log);
    $this->repairStuckLogstashTasks($db, $log);

    echo json_encode(['status' => 200, 'msg' => 'Maintenance done', 'log' => $log]);
  }

  /**
   * Repairs deadlocks in the work queue which can simply be re-queued.
   *
   * @param Database $db
   *   Database connection
   * @param array $log
   *   Output message log that can be appended to.
   */
  private function repairWorkQueueDeadlocks($db, array &$log) {
    // Re-queue work_queue entries that failed just due to deadlock.
    $query = <<<SQL
      update work_queue
      set error_detail=null, claimed_on=null, claimed_by=null
      where error_detail like '%deadlock detected%';
SQL;
    $fixed = $db->query($query)->count();
    if ($fixed > 1) {
      $log[] =  "$fixed work queue tasks were reset due to a query deadlock.";
    }
    elseif ($fixed === 1) {
      $log[] =  "One work queue task was reset due to a query deadlock.";
    }
  }

  /**
   * Re-indexes any recent samples that missed spatial indexing for some reason.
   *
   * Note that this can cause the scheduled tasks to become stuck so the
   * last_scheduled_task_check never goes past the date of an unindexed sample.
   *
   * @param Database $db
   *   Database connection
   * @param array $log
   *   Output message log that can be appended to.
   */
  private function repairRecentSamplesWithoutSpatialIndex($db, array &$log) {
    // Find the lowest sample ID of new ocurrences since we last successfully
    // ran cache_builder. Take 1000 off as arbitrary belt-and-braces as some
    // samples may have no occurrences (and the exact number not important).
    $query = <<<SQL
      select min(sample_id) - 1000 as check_from_sample_id
      from cache_occurrences_functional o, system sys
      where o.updated_on>=sys.last_scheduled_task_check
      and o.created_on>=sys.last_scheduled_task_check
      and sys.name='cache_builder';
SQL;
    $minSampleId = $db->query($query)->current()->check_from_sample_id;
    $minSampleId = 0;

    // The most likely cause of a cache builder block is the failure of some
    // samples to spatially index. So scan for any in the recent data (using
    // the ID collected above as a filter for performance) and requeue. Note
    // that spatial indexing of occurrences is automatically redone when the
    // sample gets done.
    $query = <<<SQL
      insert into work_queue(task, entity, record_id, cost_estimate, priority, created_on)
      select 'task_spatial_index_builder_sample', 'sample', s.id, 100, 2, now()
      from cache_samples_functional s
      left join work_queue q on q.record_id=s.id and q.task='task_spatial_index_builder_sample' and q.entity='sample'
      where s.id>$minSampleId
      and s.location_ids is null
      and q.id is null;
SQL;

    $fixed = $db->query($query)->count();
    if ($fixed > 1) {
      $log[] =  "$fixed samples were requeued for spatial indexing.";
    }
    elseif ($fixed === 1) {
      $log[] =  "One sample was requeued for spatial indexing.";
    }
  }

  /**
   * Restarts stuck logstash tasks.
   *
   * Tasks running during a server crash may be left with their running
   * semaphore set, so never restart.
   *
   * @param Database $db
   *   Database connection
   * @param array $log
   *   Output message log that can be appended to.
   */
  private function repairStuckLogstashTasks($db, array &$log) {
    $query = <<<SQL
      -- Remove stuck running semaphores after a crash.
      delete
      from variables v1
      using variables v2
      where v2.name || '-running'=v1.name
      and v1.name like 'rest-autofeed-%running'
      and (
        nullif((v2.value::json->0->>'last_tracking_id')::integer, 0) < (select max(tracking) - 100000 from cache_occurrences_functional)
          or (v2.value::json->0->>'last_tracking_date')::timestamp < now() - '2 hours'::interval
      );
SQL;
    $fixed = $db->query($query)->count();
    if ($fixed > 1) {
      $log[] =  "$fixed stuck Logstash tasks were reset.";
    }
    elseif ($fixed === 1) {
      $log[] =  "One stuck Logstash task was reset.";
    }
  }

}