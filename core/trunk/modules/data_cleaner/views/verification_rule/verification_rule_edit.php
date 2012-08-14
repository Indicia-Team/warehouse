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

require_once(DOCROOT.'client_helpers/data_entry_helper.php');
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));
?>
<form class="iform" action="<?php echo url::site(); ?>verification_rule/save" method="post" id="entry-form"">
<fieldset>
<legend>Verification rule details</legend>
<?php
data_entry_helper::link_default_stylesheet();
data_entry_helper::enable_validation('entry-form');
if (isset($values['verification_rule:id'])) : ?>
  <input type="hidden" name="verification_rule:id" value="<?php echo html::initial_value($values, 'verification_rule:id'); ?>" />
<?php endif;
echo data_entry_helper::text_input(array(
  'label'=>'Title',
  'fieldname'=>'verification_rule:title',
  'class'=>'control-width-4',
  'validation'=>array('required'),
  'default'=> html::initial_value($values, 'verification_rule:title')
));
echo data_entry_helper::textarea(array(
  'label'=>'Description',
  'fieldname'=>'verification_rule:description',
  'class'=>'control-width-6',
  'default'=>html::initial_value($values, 'verification_rule:description')
));
echo data_entry_helper::text_input(array(
  'label'=>'Test Type',
  'fieldname'=>'verification_rule:test_type',
  'class'=>'control-width-4',
  'validation'=>array('required'),
  'default'=> html::initial_value($values, 'verification_rule:test_type')
));
echo data_entry_helper::text_input(array(
  'label'=>'Source URL',
  'fieldname'=>'verification_rule:source_url',
  'class'=>'control-width-6',
  'default'=> html::initial_value($values, 'verification_rule:source_url'),
  'helpText'=>'When this verification rule file was imported, this identifies the name of the file '.
      'it was imported from'
));
echo data_entry_helper::text_input(array(
  'label'=>'Source Filename',
  'fieldname'=>'verification_rule:source_filename',
  'class'=>'control-width-6',
  'default'=> html::initial_value($values, 'verification_rule:source_filename')
));
echo data_entry_helper::text_input(array(
  'label'=>'Error Message',
  'fieldname'=>'verification_rule:error_message',
  'class'=>'control-width-6',
  'validation'=>array('required'),
  'default'=> html::initial_value($values, 'verification_rule:error_message')
));
echo data_entry_helper::checkbox(array(
  'label'=>'Reverse Rule',
  'fieldname'=>'verification_rule:reverse_rule',
  'default'=>html::initial_value($values, 'verification_rule:reverse_rule'),
  'helpText'=>'Tick this box to reverse the rule logic - i.e. items that pass the test are flagged as failures.'
));

echo data_entry_helper::textarea(array(
  'label'=>'Metadata',
  'fieldname'=>'metaFields:metadata',
  'class'=>'control-width-6',
  'default'=>html::initial_value($values, 'metaFields:metadata'),
  'helpText'=>'Additional settings from the header of the verification rule file, in parameter=value format with '.
      'one parameter per line'
));
echo data_entry_helper::textarea(array(
  'label'=>'Other Data',
  'fieldname'=>'metaFields:data',
  'class'=>'control-width-6',
  'default'=>html::initial_value($values, 'metaFields:data'),
  'helpText'=>'Additional settings from the data part of the verification rule file, with blocks of data items '.
      'started by a header name in square brackets, followed by parameters in parameter=value format with '.
      'one parameter per line'
));
echo $metadata;
echo html::form_buttons(html::initial_value($values, 'verification_rule:id')!=null, false, false);
data_entry_helper::link_default_stylesheet();
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
</fieldset>
</form>