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
  if ($last_run_date===null)
    // first run, so get all records changed in last day. Query will automatically gradually pick up the rest.
    $last_run_date=date('Y-m-d', time()-60*60*24);
  try {
  	// unlike cache builder, summary has a single table.
    summary_builder::populate_summary_table($db, $last_run_date, (isset($_GET['force_summary_rebuild']) ? ($_GET['force_summary_rebuild'] != '' ? $_GET['force_summary_rebuild'] : true) : false));
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
