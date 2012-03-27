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
 * @package	Data Cleaner
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
 
 
echo $grid;
?>
<form action="<?php echo url::site().'verification_rule/create'; ?>">
<input type="submit" value="New verification_rule" class="ui-corner-all ui-state-default button" />
</form>
<form class="linear-form" method="post" action="<?php echo url::site().'verification_rule/upload_rule_files'; ?>">
<fieldset>
  <legend>Upload verification rule files from a local folder</legend>
  <label>Enter local folder containing files:<input type="text" id="path" name="path" class="control-width-6"
                              value="/Applications/XAMPP/xamppfiles/htdocs/data_cleaner_rules"/></label>
  <input type="submit" value="Upload from local folder"/>
</fieldset>
</form>
<form class="linear-form" method="post" action="<?php echo url::site().'verification_rule/load_from_server'; ?>">
<fieldset>
  <p>Load verification rule files from a Record Cleaner server list. Please select the servers to use when loading files.</p>
  <table>
    <thead>
      <th></th><th>Server owner</th><th>Date</th>
    <tbody>
    <?php foreach ($serverList as $idx => $server) : ?>
    <tr><td><input type="checkbox" name="svr:<?php echo $idx; ?>"/></td><td><?php echo $server['author']; ?></td><td><?php echo $server['date']; ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <input type="submit" value="Load from Record Cleaner server"/>
</fieldset>
</form>