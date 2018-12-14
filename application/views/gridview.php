<?php

/**
 * @file
 * View template for the output of a data entity's index grid.
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
$colDefs = array();
if (isset($columns)) {
  foreach ($columns as $fieldname => $title) {
    if (!isset($orderby)) {
      $orderby = $fieldname;
    }
    $def = array(
      'fieldname' => $fieldname,
      'display' => empty($title) ? str_replace('_', ' ', ucfirst($fieldname)) : $title,
    );
    if ($fieldname == 'path') {
      $def['img'] = TRUE;
    }
    $colDefs[] = $def;
  }
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
$options = array(
  'id' => $id,
  'class' => 'report-grid table',
  'readAuth' => $readAuth,
  'extraParams' => array(),
  'itemsPerPage' => kohana::config('pagination.default.items_per_page'),
);
if (isset($orderby)) {
  $options['orderby'] = $orderby;
}
if ($gridReport) {
  $options['dataSource'] = $gridReport;
  $options['extraParams'] += $filter;
  $options['columns'] = $colDefs;
}
else {
  $options['mode'] = 'direct';
  $options['dataSource'] = $source;
  $options['view'] = 'gv';
  $options['filters'] = $filter;
  $options['includeAllColumns'] = FALSE;
  $options['columns'] = $colDefs;
}
echo report_helper::report_grid($options);
echo report_helper::dump_javascript();
