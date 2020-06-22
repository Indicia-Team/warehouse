<?php

/**
 * @file
 * View template for the summary builder work queue status form.
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

if (!empty($successMessage)) {
    echo '<p class="alert alert-success">' . $successMessage . '</p>';
}
if (!empty($errorMessage)) {
    echo '<p class="alert alert-danger">' . $errorMessage . '</p>';
}
?>
<table class="table"><tr><th>Statistic</th><th>Value</th></tr><tbody>
<?php

$grid = [['Number of outstanding entries on the job queue'],
    ['Number of job queue entries older than today','created_on < CURRENT_DATE'],
    ['Number of job queue entries older than one week','created_on < (CURRENT_DATE - interval \'7 days\')'],
    ['Number of job queue entries claimed older than 1 hour','claimed_on < (now() - interval \'1 hour\')'],
    ['Number of job queue entries with errors','error_detail IS NOT NULL AND error_detail <> \'\'']];

foreach($grid as $gridRow) {
  $count = $this->db
    ->select('count(*)')
    ->from('work_queue')
    ->like('task', 'task_summary_builder_%', FALSE);
  if(!empty($gridRow[1])) {
    $count = $count->where($gridRow[1]);
  }
  $count = $count->get()->as_array(true);
  echo '<tr><td>' . $gridRow[0] .' </td><td>' . $count[0]->count . '</td></tr>';
}
echo '</tbody></table>';

$taskEntriesQueue = $this->db
  ->select('*')
  ->from('work_queue')
  ->like('task', 'task_summary_builder_%', FALSE)
  ->orderby('created_on')
  ->limit(10)
  ->get()->as_array(true);
  

if (count($taskEntriesQueue) > 0) {
  // @TODO Add ability to reset entries on the queue
  echo '<h2>Task Queue</h2>';
  echo '<table class="table"><tr><th>ID</th><th>Task</th><th>Record ID</th><th>Created on</th><th>Claimed on</th><th>Error</th></tr><tbody>';
  foreach ($taskEntriesQueue as $task) {
      echo '<tr><td>' . $task->id . '</td><td>' . $task->task . '</td><td>' . $task->record_id . '</td><td>' . $task->created_on . '</td><td>' . $task->claimed_on . '</td><td>' . $task->error_detail . '</td></tr>';
  }
  echo '</tbody></table>';
}

$systemTableEntries = $this->db
  ->select('*')
  ->from('system')
  ->where('name','summary_builder')
  ->get()->as_array(true);
foreach($systemTableEntries as $systemTableEntry) {
    echo '<span style="display:none;">ID ' . $systemTableEntry->id . ', last script : ' . $systemTableEntry->last_run_script . "</span><br>";
}
?>
<h2>Reset summary values</h2>
Location [for a year]: loop through all taxa for existing summary records, look through all samples that year.
<form action="<?php echo url::site() . 'summariser_definition/work_queue_reset_sample'; ?>" method="post" class="cmxform">
  <div class="form-group">
    <label for="sample">Sample ID</label>
    <div class="input-group">
      <input type="text" id="sample" name="sample_id" class="form-control" required="true"/>
      <div class="input-group-addon ctrl-addons"><span class="deh-required">*</span></div>
    </div>
  </div>
  <input type="submit" value="Reset sample" class="btn btn-primary" />
</form>