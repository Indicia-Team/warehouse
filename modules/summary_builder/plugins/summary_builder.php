<?php
/**
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
 * @package	Modules
 * @subpackage Cache builder
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Hook into the task scheduler. This uses the queries defined in the summary_builder.php
 * file to create and populate cache tables. The tables are not always up to date as they
 * are only updated when the scheduler runs, but they have the advantage of simplifying
 * the data model for reporting as well as reducing the need to join in queries, therefore
 * significantly improving report performance.
 * @param string $last_run_date Date last run, or null if never run
 * @param object $db Database object.
 */
function summary_builder_scheduled_task($last_run_date, $db) {

  $verbose = isset($_GET['verbose']);
  $rebuild = isset($_GET['force_summary_rebuild']) ? ($_GET['force_summary_rebuild'] != '' ? $_GET['force_summary_rebuild'] : true) : false;
  $clear = isset($_GET['force_summary_clear']) && $_GET['force_summary_clear'] != '' ? $_GET['force_summary_clear'] : false;
  $missing_check = isset($_GET['force_summary_missing_check']) ? ($_GET['force_summary_missing_check'] != '' ? $_GET['force_summary_missing_check'] : true) : false;
  $location = isset($_GET['location_id']) && $_GET['location_id'] != '' ? $_GET['location_id'] : false;
  $only = isset($_GET['only']) && $_GET['only'] != '' ? $_GET['only'] : false;
  
  if($verbose) {
	echo 'Summary Builder module: Optional URL Parameters<br/><ul>';
	echo '<li><b>verbose</b> : present</li>';
	echo '<li><b>force_summary_rebuild</b> : '.($rebuild === false ? 'absent' : ($rebuild === true ? 'present (rebuilds all surveys)' : $rebuild.' (Survey ID)')).'</li>';
	echo '<li><b>force_summary_clear</b> : '.($clear === false ? 'absent or survey not specified' : $clear.' (Survey ID)').'</li>';
	if($clear !== false) echo '<li><b>location_id</b> : '.($location === false ? 'absent' : $location.' (Location ID)').'</li>';
	echo '<li><b>force_summary_missing_check</b> : '.($missing_check === false ? 'absent (missing checks as defined in summary definition for individual survey)' : ($missing_check === true ? 'present (missing checks forced on all surveys)' : $missing_check.' (Survey ID, missing checks forced on this survey, for all other surveys missing checks as defined in summary definition)')).'</li>';
	echo '<li><b>only</b> : '.($only === false ? 'absent' : $only).'</li></ul><br/>';
  }
  
  if(isset($_GET['help'])) {
	echo 'Summary Builder module task help:<br/>Optional URL Parameters<br/><ul>';
	echo '<li><b>&amp;help</b> : displays this message detailing the available URL parameters when running the Summary Builder module scheduled task. No other processing takes place.<br/>';
	echo '<li><b>&amp;verbose</b> : if present, increases the amount of messages displayed, e.g. include metrics on number of records processed.</li>';
	echo '<li><b>&amp;force_summary_rebuild[=&lt;n&gt;]</b> : if present will change the creation date of all the summary entries for either the specified survey ID &lt;n&gt; (if given), or all surveys (if no parameter value given), to the day before the creation date of the first sample on the survey. This then allows the data to be fully rebuilt using the missing summary checks, whilst leaving data present. No other processing will take place on this invocation for the affected survey(s).</li>';
	echo '<li><b>&amp;force_summary_clear=&lt;n&gt;[&amp;location_id=&lt;x&gt;]</b> : if present will remove all the summary entries for the specified survey ID &lt;n&gt;, optionally restricting the removal to the data for location &lt;x&gt;. This then allows the data to be fully rebuilt using the missing summary checks. No other processing will take place on this invocation for the affected survey.</li>';
	echo '<li><b>&amp;force_summary_missing_check</b> : Carries out extra checks to see if any data has been missed. Normally checks are restricted to those samples created/deleted or occurrences created/updated/deleted since the last run of this scheduled task. With this option, the checks are extended to include any deleted locations with data in the cache, and any samples created/deleted plus any occurrences created/updated/deleted after the relevant summary record was created. These checks have a greater performance hit than the normal checks.</li>';
	echo '<li><b>&amp;only=[locations|samples|occurrences]</b> : There are 3 distinct stages to the checks carried out (both normal and missing): this parameter restricts the processing to one of the three stages. This may be especially useful in catch up (missing check) mode, where performance may be marginal.</li></ul>';
	return;
  }

  if ($last_run_date===null)
    // first run, so get all records changed in last day. Missing_check query will automatically gradually pick up the rest.
    $last_run_date=date('Y-m-d', time()-60*60*24);
  try {
  	// unlike cache builder, summary has a single table.
    summary_builder::populate_summary_table($db, $last_run_date, $verbose, $rebuild, $clear, $missing_check);
  } catch (Exception $e) {
    echo $e->getMessage();
  }
}

function summary_builder_alter_menu($menu, $auth) {
	if ($auth->logged_in('CoreAdmin') || $auth->has_any_website_access('admin'))
		$menu['Admin']['Summariser']='summariser_definition';
	return $menu;
}

function summary_builder_extend_data_services() {
	return array(
			'summariser_definitions'=>array(),
			'summary_occurrences'=>array()
	);
}
?>
