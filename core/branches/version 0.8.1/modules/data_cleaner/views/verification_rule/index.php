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
<!--<form action="<?php echo url::site().'verification_rule/create'; ?>">
<input type="submit" value="New verification_rule" class="ui-corner-all ui-state-default button" />
</form>-->
<p>Verification rule files are created by uploading files that are compatible with the NBN Record Cleaner. 
For more information on creating these files, see 
<a href="http://www.nbn.org.uk/Tools-Resources/Recording-Resources/NBN-Record-Cleaner/Creating-verification-rules.aspx">
NBN Record Cleaner - creating verification rules</a>. You can either use the online file servers
as used by NBN Record Cleaner to obtain rules or zip your files into a batch to upload.</p>
<form class="linear-form" enctype="multipart/form-data" method="post" action="<?php echo url::site().'verification_rule/upload'; ?>">
<fieldset>
  <label>Select a zip file to upload:<input type="file" name="zipFile" class="control-width-6" /></label>
</fieldset>
<fieldset>
  <p>Or, load verification rule files from a Record Cleaner server list. Please select the servers to use when loading files.</p>
  <table>
    <thead>
      <th></th><th>Server owner</th><th>Date</th>
    <tbody>
    <?php foreach ($serverList as $idx => $server) : ?>
    <tr><td><input type="checkbox" name="svr:<?php echo $idx; ?>"/></td><td><?php echo $server['author']; ?></td><td><?php echo $server['date']; ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <input type="submit" value="Upload rule files"/>
</fieldset>
</form>