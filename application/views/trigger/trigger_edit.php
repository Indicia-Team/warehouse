<?php

/**
 * @file
 * View template for the trigger edit form.
 *
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

warehouse::loadHelpers(['data_entry_helper']);
$formPostUrl = url::site() . 'trigger/edit_params' . (empty($this->model->id) ? '' : '/' . $this->model->id);
?>
<p>This page allows you to specify the details of a trigger, fired when an occurrence is entered which meets certain
conditions.</p>
<form action="<?php echo $formPostUrl; ?>" method="post">
  <fieldset>
    <legend>Trigger details<?php echo $metadata ?></legend>
    <input type="hidden" name="trigger:id" value="<?php echo html::initial_value($values, 'trigger:id'); ?>" />
    <?php
    echo data_entry_helper::text_input(array(
      'label' => 'Name',
      'fieldname' => 'trigger:name',
      'default' => html::initial_value($values, 'trigger:name'),
    ));
    echo data_entry_helper::textarea(array(
      'label' => 'Description',
      'fieldname' => 'trigger:description',
      'default' => html::initial_value($values, 'trigger:description'),
    ));
    echo data_entry_helper::select(array(
      'label' => 'Trigger template',
      'fieldname' => 'trigger:trigger_template_file',
      'default' => html::initial_value($values, 'trigger:trigger_template_file'),
      'lookupValues' => $other_data['triggerFileList'],
    ));
    echo data_entry_helper::checkbox(array(
      'label' => 'Public',
      'fieldname' => 'trigger:public',
      'default' => html::initial_value($values, 'trigger:public'),
    ));
    echo data_entry_helper::dump_javascript();
    ?>
  </fieldset>
  <fieldset class="button-set">
    <input type="submit" name="submit" value="<?php echo kohana::lang('misc.next'); ?>" class="btn btn-primary" />
    <input type="submit" name="submit" value="<?php echo kohana::lang('misc.cancel'); ?>" class="btn btn-default" />
    <input type="submit" name="submit" value="<?php echo kohana::lang('misc.delete'); ?>" onclick="if (!confirm('<?php echo kohana::lang('misc.confirm_delete'); ?>')) {return false;}" class="btn btn-warning" />
  </fieldset>
</form>
