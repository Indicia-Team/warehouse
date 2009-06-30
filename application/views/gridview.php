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
<script type="text/javascript" src='<?php echo url::base() ?>application/views/gridview.js' ></script>
<div id='gvFilter'>
<form name='Filter' action='' method='get'>
Filter for
<input type='text' name='filters'/>
in <select name='columns'>
<?php foreach ($columns as $name => $newname) {
  if (!$newname) $newname = $name;
  echo "<option value='".$name."'>".$newname."</option>";
}
?>
</select>
<input id='gvFilterButton' type='submit' value='Filter'/>
</form>
</div>
<table id='pageGrid'>
<thead>
<tr class='headingRow'>
<?php
foreach ($columns as $name => $newname) {
  if (!$newname) $newname = $name;
  echo "<th class='gvSortable gvCol' id='$name'>".ucwords($newname)."</th>";
}
foreach ($actionColumns as $name => $action) {
  echo "<th class='gvAction'>".ucwords($name)."</th>";
}
?>
</tr>
</thead>
<tbody id='gvBody'/>
<?php echo $body ?>
</tbody>
</table>
<div class='pager'>
<?php echo $pagination ?>
</div>
