<?php

/**
 * @file
 * View template for the trigger action edit form.
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
?>
<form class="iform" action="<?php echo url::site(); ?>trigger_action/save" method="post">
  <fieldset>
    <legend>Email digest frequency</legend>
    <input type="hidden" name="trigger_action:id" value="<?php echo html::initial_value($values, 'trigger_action:id'); ?>" />
    <input type="hidden" name="trigger_action:trigger_id" value="<?php echo html::initial_value($values, 'trigger_action:trigger_id'); ?>" />
    <input type="hidden" name="trigger_action:type" value="E" />
    <input type="hidden" name="trigger_action:param1" value="<?php echo html::initial_value($values, 'trigger_action:param1'); ?>" />
    <input type="hidden" name="return_url" value="<?php echo url::site(); ?>trigger" />
    <?php
    echo data_entry_helper::radio_group(array(
      'fieldname' => 'trigger_action:param2',
      'label' => 'Notification frequency',
      'class' => 'check-or-radio-box',
      'default' => html::initial_value($values, 'trigger_action:param2'),
      'helpText' => 'Please specify how frequently you would like to receive email notifications for this trigger?',
      'lookupValues' => array(
        'N' => 'No emails',
        'I' => 'Immediate',
        'D' => 'Daily',
        'W' => 'Weekly',
      ),
      'sep' => '<br/>',
    ));
    echo data_entry_helper::textarea(array(
      'label' => 'Copy email to',
      'helpText' => 'Provide a comma separated list of email addresses to copy this notification to.',
      'fieldname' => 'trigger_action:param3',
      'default' => html::initial_value($values, 'trigger_action:param3'),
    ));
    echo data_entry_helper::dump_javascript();
    ?>
  </fieldset>
  <input type="submit" name="submit" value="<?php echo kohana::lang('misc.save'); ?>" class="btn btn-primary" />
  <input type="submit" name="submit" value="<?php echo kohana::lang('misc.cancel'); ?>" class="btn btn-warning" />
  <input type="submit" name="submit" value="<?php echo kohana::lang('misc.unsubscribe'); ?>" onclick="if (!confirm('<?php echo kohana::lang('misc.confirm_unsubscribe'); ?>')) {return false;}" class="btn btn-default" />
</form>
