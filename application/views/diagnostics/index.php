<?php

/**
 * @file
 * View template for the output of a warehouse diagnostic info.
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

 /*
  * Generates a paginated grid for table view. Requires a number of variables
  * passed to it:
  *  $columns - array of column names
  *  $pagination - the pagination object
  *  $body - gridview_table object.
  */

warehouse::loadHelpers(['report_helper']);
$readAuth = report_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
$restApiEnabled = in_array(MODPATH . 'rest_api', kohana::config('config.modules'));
?>
<div class="row">
  <div class="col-md-12">
    <h3>Maintenance tools</h3>
    <button class="btn btn-primary" type="button" id="run-maintenance">Run background process maintenance</button>
    <div id="maintenance-output" style="display: none" class="panel panel-info">
      <div class="panel-heading">Maintenance output</div>
      <div class="panel-body"></div>
    </div>
  </div>
</div>

</div>
<div class="row">

<div class="col-md-6">
<h3>Scheduled task summary</h3>
<div class="alert alert-info">The following list of scheduled tasks shows the timestamp each task has processed
data up to.</div>
<?php
echo report_helper::report_grid([
  'id' => 'scheduled_tasks_report',
  'readAuth' => $readAuth,
  'dataSource' => 'library/system/scheduled_task_summary',
]);
?>
</div>

<div class="col-md-6">
<h3>Work queue summary</h3>
<div class="alert alert-info">Summary of current entries in the Work Queue.</div>
<?php
echo report_helper::report_grid([
  'id' => 'work_queue_report',
  'readAuth' => $readAuth,
  'dataSource' => 'library/work_queue/summary'
]);
?>
</div>

</div>

<?php if ($restApiEnabled) : ?>
  <h3>REST API data feed delays</h3>
  <div class="alert alert-info">The following table shows the number of update tasks behind that REST API feeds are,
  e.g. into Elasticsearch. Note that not all these feeds may be currently enabled.</div>
  <?php
  echo report_helper::report_grid([
    'id' => 'autofeed_delay_report',
    'readAuth' => $readAuth,
    'dataSource' => 'rest_api/autofeed_delays',
  ]);
  ?>
<?php endif; ?>

<?php if (class_exists('request_logging')) : ?>
  <h3>Request performance - top culprits</h3>
  <div class="alert alert-info">The following are most resource intensive API requests of the last 2000.</div>
  <?php
  echo report_helper::report_grid([
    'id' => 'request_log_culprits_report',
    'readAuth' => $readAuth,
    'dataSource' => 'library/request_log_entries/main_culprits',
    'itemsPerPage' => 5,
  ]);
endif;

echo report_helper::dump_javascript();

