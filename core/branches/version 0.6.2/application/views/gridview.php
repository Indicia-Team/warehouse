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
?>
<div class="gvFilter">
<form action='<?php echo url::site(Router::$routed_uri); ?>' method="get" id="filterForm-<?php echo $id; ?>">
<?php 
$filter_text = array_key_exists('filters', $_GET) ? $_GET['filters'] : null;
$filter_col = array_key_exists('columns', $_GET) ? $_GET['columns'] : null;
?>
Filter for
<input type="text" name="filters" class="filterInput" 
  <?php if(!is_null($filter_text)) echo 'value="' . $filter_text . '"'; ?>
/>
in <select name="columns" class="filterSelect">
<?php foreach ($columns as $name => $newname) {
  if (!$newname) $newname = $name;
  echo '<option value="' . $name. '" '. 
    (($filter_col == $name)? 'selected="selected"' : '') . '>' . $newname.
    '</option>';
}
?>
</select>
<input type="submit" value="Filter" class="ui-corner-all ui-state-default"/>
</form>
</div>
<table id="pageGrid-<?php echo $id; ?>" class="ui-widget ui-widget-content">
<thead class="ui-widget-header">
<tr class='headingRow'>
<?php
$sortClass = $sortable ? 'gvSortable' : '';
foreach ($columns as $name => $newname) {
  if (!$newname) $newname = $name;
  echo "<th class='$sortClass gvCol' id='$name'>".str_replace('_', ' ', ucwords($newname))."</th>";
}
if (count($actionColumns)>0) {
  echo "<th class='gvAction'>Task</th>";
}
?>
</tr>
</thead>
<tbody id="gridBody-<?php echo $id; ?>">
<?php echo $body ?>
</tbody>
</table>
<div id="pager-<?php echo $id; ?>">
<?php echo $pagination ?>
</div>