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
  * Generates a paginated grid for table view. Requires a number of variables passed to it:
  *  $columns - array of column names
  *  $pagination - the pagination object
  *  $body - gridview_table object.
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
echo data_entry_helper::report_grid(array(
  'id' => $id,
  'mode'=>'direct',
  'dataSource' => $source,
  'view' => 'gv',
  'readAuth' => $readAuth,
  'includeAllColumns' => false,
  'columns' => $colDefs,
  'extraParams' => array('orderby'=>$orderby),
  'filters' => $filter
));
data_entry_helper::link_default_stylesheet();
// No need to re-link to jQuery
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
