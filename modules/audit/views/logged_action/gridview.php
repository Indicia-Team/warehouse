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
 * @package	Core
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

 /**
  * Generates a paginated grid for the logged action table view. Loosely based on standard gridview,
  * but filtering is different
  */

require_once(DOCROOT.'client_helpers/data_entry_helper.php');
$readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
$colDefs = array();
foreach ($columns as $fieldname => $title) {
  if (!isset($orderby)) $orderby=$fieldname;
  $def = array(
    'fieldname' => $fieldname,
    'display' => empty($title) ? str_replace('_', ' ', ucfirst($fieldname)) : $title
  );
  if ($fieldname == 'path') 
    $def['img'] = true;
  $colDefs[] = $def;
}
$actions = $this->get_action_columns();
foreach ($actions as &$action) {
  if (substr($action['url'], 0, 4) != 'http') {
    $action['url'] = url::base(true).$action['url'];
  }
}
if (count($actions)>0) 
  $colDefs[] = array(
    'display' => 'Actions',
    'actions' => $actions
  );

// New filters
$reloadUrl = data_entry_helper::get_reload_link_parts();

// Filter by postgresql transaction
$value = (isset($_GET['transaction_id'])) ? ' value="'.$_GET['transaction_id'].'"' : '';
$r = '<form action="'.$reloadUrl['path'].'" method="get" class="linear-form" id="loggedActionFilterForm-Transaction">'.
		'<label for="transaction_id" class="auto" style="width:auto">'.lang::get('Filter Events for a Postgres Transaction ID of ').'</label> '.
		'<input type="text" name="transaction_id" id="transaction_id" class="filterInput"'.$value.'/> '.
		'<input type="submit" value="Filter" class="run-filter ui-corner-all ui-state-default"/>'.
		"</form>\n";

// Filter by indicia table and id
$tables = array('samples', 'occurrences', 'locations');
$search_key = (isset($_GET['search_key'])) ? ' value="'.$_GET['search_key'].'"' : '';
$r .= '<form action="'.$reloadUrl['path'].'" method="get" class="linear-form" id="loggedActionFilterForm-Key">'.
		'<label for="search_table_name" class="auto" style="width:auto">'.lang::get('Filter Events for ').'</label> '.
		'<select name="search_table_name" class="filterSelect" id="search_table_name"><option value="">&lt;Please select table name&gt;</option>';
foreach ($tables as $table) {
	$selected = (isset($_GET['search_table_name']) && $_GET['search_table_name']==$table) ? ' selected="selected"' : '';
  	$r .= "<option value=\"".$table."\"$selected>".ucfirst($table)."</option>";
}
$r .= "</select> ".
		'<label for="search_key" class="auto">'.lang::get('records with an Indicia ID of').'</label> '.  
		'<input type="text" name="search_key" id="search_key" class="filterInput"'.$search_key.'/> '.
		'<input type="submit" value="Filter" class="run-filter ui-corner-all ui-state-default"/>'.
		"</form>\n";
echo $r;

echo data_entry_helper::report_grid(array(
  'id' => $id,
  'mode'=>'direct',
  'dataSource' => $source,
  'view' => 'gv',
  'readAuth' => $readAuth,
  'includeAllColumns' => false,
  'columns' => $colDefs,
  'extraParams' => array('orderby'=>$orderby),
  'filters' => $filter,
  'itemsPerPage' => 1000,
  'autoParamsForm' => false,
  'ajax' => false
));
data_entry_helper::link_default_stylesheet();
// No need to re-link to jQuery
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
