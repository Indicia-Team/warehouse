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
<table><thead><th>Column in CSV File</th><th>Maps to attribute</th></thead>
<tbody>
<?php foreach ($columns as $col): ?>
  <tr>
    <td><?php echo $col; ?></td>
    <td><select id="<?php echo $col; ?>" name="<?php echo $col; ?>">
    <option>Please Select</option>
    <?php foreach ($mappings as $map => $name) {
      echo "<option value='".$map."'>".$name."</option>";
    } ?>
    </select>
    </td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<input type="Submit" value="Upload Data" />
</form>

