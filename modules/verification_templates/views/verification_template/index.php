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
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link       https://github.com/Indicia-Team/
 */


echo $grid;
?>
<form action="<?php echo url::site().'verification_template/create'; ?>" method="post">
  <input type="submit" value="New Verification template" class="ui-corner-all ui-state-default button" />
</form>
<?php
$systemTableEntries = $this->db->select('*')->from('system')->where('name','verification_templates')->get()->as_array(true);
foreach($systemTableEntries as $systemTableEntry) {
  echo 'Verification_templates module version : '.$systemTableEntry->version.'<span style="display:none;">ID '.$systemTableEntry->id.", last script : ".$systemTableEntry->last_run_script."</span><br>";
}
?>