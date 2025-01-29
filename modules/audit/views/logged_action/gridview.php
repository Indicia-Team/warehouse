<?php

/**
 * @file
 * View template for the logged audit actions table.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/*
 * Generates a paginated grid for the logged action table view. Loosely based
 * on the standard gridview, but filtering is different.
 */

warehouse::loadHelpers(['report_helper']);
$readAuth = report_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
$colDefs = array();
foreach ($columns as $fieldname => $title) {
  if (!isset($orderby)) {
    $orderby = $fieldname;
  }
  $def = array(
    'fieldname' => $fieldname,
    'display' => empty($title) ? str_replace('_', ' ', ucfirst($fieldname)) : $title,
  );
  if ($fieldname === 'path') {
    $def['img'] = TRUE;
  }
  $colDefs[] = $def;
}
$actions = $this->get_action_columns();
foreach ($actions as &$action) {
  if (substr($action['url'], 0, 4) != 'http') {
    $action['url'] = url::base(TRUE) . $action['url'];
  }
}
if (count($actions) > 0) {
  $colDefs[] = array(
    'display' => 'Actions',
    'actions' => $actions,
  );
}

$reloadUrl = report_helper::get_reload_link_parts();

// Filter by postgresql transaction.
$value = (isset($_GET['transaction_id'])) ? " value=\"$_GET[transaction_id]\"" : '';
$label = lang::get('Filter Events for a PostgreSQL transaction ID of ');
$r = <<<HTML
<form action="$reloadUrl[path]" method="get" class="form-inline" id="loggedActionFilterForm-Transaction">
  <div class="form-group">
    <label for="transaction_id">$label</label>
    <input type="text" name="transaction_id" id="transaction_id" class="filterInput form-control"$value/>
  </div>
  <input type="submit" value="Filter" class="run-filter btn btn-primary"/>
</form>
<br/>

HTML;

// Filter by indicia table and id
$tables = array('samples', 'occurrences', 'locations');
$search_key = (isset($_GET['search_key'])) ? ' value="' . $_GET['search_key'] . '"' : '';
$tableOptions = '';
foreach ($tables as $table) {
  $selected = (isset($_GET['search_table_name']) && $_GET['search_table_name'] === $table) ? ' selected="selected"' : '';
  $tableOptions .= "<option value=\"$table\"$selected>" . ucfirst($table) . "</option>";
}
$label1 = lang::get('Filter events for');
$label2 = lang::get('records with an Indicia ID of');
$r .= <<<HTML
<form action="$reloadUrl[path]" method="get" class="form-inline" id="loggedActionFilterForm-Key">
  <div class="form-group">
    <label for="search_table_name">$label1</label>
    <select name="search_table_name" class="filterSelect form-control" id="search_table_name">
      <option value="">&lt;Please select table name&gt;</option>
      $tableOptions
    </select>
  </div>
  <div class="form-group">
    <label for="search_key">$label2</label>
    <input type="text" name="search_key" id="search_key" class="filterInput form-control"$search_key/>
  </div>
  <input type="submit" value="Filter" class="btn btn-primary"/>
</form>
<br/>

HTML;
echo $r;

echo report_helper::report_grid(array(
  'id' => $id,
  'mode' => 'direct',
  'dataSource' => $source,
  'view' => 'gv',
  'readAuth' => $readAuth,
  'includeAllColumns' => FALSE,
  'columns' => $colDefs,
  'extraParams' => array('orderby' => $orderby),
  'filters' => $filter,
  'itemsPerPage' => 1000,
  'autoParamsForm' => FALSE,
  'ajax' => FALSE,
  'class' => 'report-grid table',
));

echo report_helper::dump_javascript();
