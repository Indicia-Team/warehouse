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
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));
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
    'labelClass' => 'align-top',
    'class' => 'check-or-radio-box',
    'default' => html::initial_value($values, 'trigger_action:param2'),
    'helpText' => 'Please specify how frequently you would like to receive email notifications for this trigger?',
    'lookupValues' => array('N'=>'No emails', 'I'=>'Immediate', 'D'=>'Daily', 'W'=>'Weekly'),
    'sep' => '<br/>'
));
echo data_entry_helper::textarea(array(
  'label' => 'Copy email to',
  'helpText'=>'Provide a comma separated list of email addresses to copy this notification to.',
  'labelClass' => 'align-top',
  'fieldname' => 'trigger_action:param3',
  'default' => html::initial_value($values, 'trigger_action:param3'),
));
?>
</fieldset>
<input type="submit" name="submit" value="<?php echo kohana::lang('misc.save'); ?>" class="ui-corner-all ui-state-default button ui-priority-primary" />
<input type="submit" name="submit" value="<?php echo kohana::lang('misc.cancel'); ?>" class="ui-corner-all ui-state-default button" />
<input type="submit" name="submit" value="<?php echo kohana::lang('misc.unsubscribe'); ?>" onclick="if (!confirm('<?php echo kohana::lang('misc.confirm_unsubscribe'); ?>')) {return false;}" class="ui-corner-all ui-state-default button" />
</form>