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

echo form::open($controllerpath.'/upload', array('class'=>'cmxform')); ?>
<p>Please map each column in the CSV file you are uploading to the associated attribute in the destination list.</p>
<br />
<table class="ui-widget ui-widget-content">
<thead class="ui-widget-header">
<tr><th>Column in CSV File</th><th>Maps to attribute</th></tr>
</thead>
<tbody>
<?php $options = html::model_field_options($model, '<please select>');
$i=0;
foreach ($columns as $col):
  echo '<tr class="';
  echo ($i % 2 == 0) ? 'evenRow">' : 'oddRow">';
  $i++;  ?>
    <td><?php echo $col; ?></td>
    <td><select <?php echo 'id="'.$col.'" name="'.$col.'">'.$options; ?></select></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<input type="Submit" value="Upload Data" />
<?php
// We stick these at the bottom so that all the other things will be parsed first
foreach ($this->input->post() as $a => $b) {
  print form::hidden($a, $b);
}
?>
</form>

