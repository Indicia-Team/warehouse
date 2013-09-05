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
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
?><p>This page allows you to specify the details of a trigger, fired when an occurrence is entered which meets certain 
conditions.</p>
<form class="iform" action="<?php 
echo url::site().'trigger/edit_params';
if ($this->model->id) echo "/".$this->model->id;
 ?>" method="post">
<?php echo $metadata ?>
<fieldset>
<input type="hidden" name="trigger:id" value="<?php echo html::initial_value($values, 'trigger:id'); ?>" />
<legend>Trigger details</legend>
<?php
echo data_entry_helper::text_input(array(
  'label' => 'Name',
  'fieldname' => 'trigger:name',
  'default' => html::initial_value($values, 'trigger:name'),
  'class'=>'control-width-5'
));
echo data_entry_helper::textarea(array(
  'label' => 'Description',
  'fieldname' => 'trigger:description',
  'default' => html::initial_value($values, 'trigger:description')
));
echo data_entry_helper::select(array(
  'label' => 'Trigger template',
  'fieldname' => 'trigger:trigger_template_file',
  'default' => html::initial_value($values, 'trigger:trigger_template_file'),
  'lookupValues' => $other_data['triggerFileList']
));
echo data_entry_helper::checkbox(array(
  'label' => 'Public',
  'fieldname' => 'trigger:public',
  'default' => html::initial_value($values, 'trigger:public')
));
data_entry_helper::link_default_stylesheet();
// No need to re-link to jQuery
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
</fieldset>
<fieldset class="button-set">
<input type="submit" name="submit" value="<?php echo kohana::lang('misc.next'); ?>" class="ui-corner-all ui-state-default button ui-priority-primary" />
<input type="submit" name="submit" value="<?php echo kohana::lang('misc.cancel'); ?>" class="ui-corner-all ui-state-default button" />
<input type="submit" name="submit" value="<?php echo kohana::lang('misc.delete'); ?>" onclick="if (!confirm('<?php echo kohana::lang('misc.confirm_delete'); ?>')) {return false;}" class="ui-corner-all ui-state-default button" />
</fieldset>
</form>
