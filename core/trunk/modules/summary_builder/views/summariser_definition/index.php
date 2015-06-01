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
 * @package	Summary builder
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
 
 
echo $grid;
$systemTableEntries = $this->db->select('*')->from('system')->where('name','summary_builder')->get()->as_array(true);
foreach($systemTableEntries as $systemTableEntry) {
	echo 'Summary Builder module version : '.$systemTableEntry->version.'<br>Last scheduled tasks ran : '.$systemTableEntry->last_scheduled_task_check.'<span style="display:none;">ID '.$systemTableEntry->id.", last script : ".$systemTableEntry->last_run_script."</span><br>";
}
?>