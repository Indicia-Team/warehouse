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

require_once(DOCROOT.'client_helpers/data_entry_helper.php');
?>
<form class="iform" action="<?php echo url::site(); ?>cache_builder_status/save" method="post" id="entry-form"">
<fieldset>
<legend>Cache Statuses</legend>
<?php
data_entry_helper::link_default_stylesheet();
foreach($values as $field => $value)
  echo data_entry_helper::checkbox(array(
		'label'=>$field,
		'fieldname'=>$field,
		'default'=> $value,
  		'disabled'=> ($value ? '' : 'disabled'),
		'helpText' => 'Cache missing data checks completed.',
  ));

echo html::form_buttons(false, false, false);
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
</fieldset>
</form>
<?php
$systemTableEntries = $this->db->select('*')->from('system')->where('name','cache_builder')->get()->as_array(true);
foreach($systemTableEntries as $systemTableEntry) {
	echo 'Cache Builder module version : '.$systemTableEntry->version.'<br>Last scheduled tasks ran : '.$systemTableEntry->last_scheduled_task_check.'<span style="display:none;">ID '.$systemTableEntry->id.", last script : ".$systemTableEntry->last_run_script."</span><br>";
}
?>