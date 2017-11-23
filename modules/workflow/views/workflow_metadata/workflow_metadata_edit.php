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
 * @package    Modules
 * @subpackage Workflow
 * @author     Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link       https://github.com/Indicia-Team/
 */

require_once(DOCROOT.'client_helpers/data_entry_helper.php');
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));
?>
<form class="iform" action="<?php echo url::site(); ?>workflow_metadata/save" method="post" id="entry-form">
  <fieldset>
    <legend>Workflow Metadata specification details</legend>
<?php
data_entry_helper::link_default_stylesheet();
data_entry_helper::enable_validation('entry-form');
echo data_entry_helper::hidden_text(array(
    'fieldname'=>'workflow_metadata:id',
    'default'=>html::initial_value($values, 'workflow_metadata:id')
));
echo data_entry_helper::select(array(
    'label'=>'Entity',
    'fieldname'=>'workflow_metadata:entity',
    'lookupValues' => $other_data['entitySelectItems'],
    'default'=>html::initial_value($values, 'workflow_metadata:entity'),
    'validation' => array('required')
));
echo data_entry_helper::hidden_text(array(
    'fieldname'=>'old_workflow_metadata_key',
    'default'=>html::initial_value($values, 'workflow_metadata:key')
));
echo data_entry_helper::select(array(
    'label'=>'Key',
    'fieldname'=>'workflow_metadata:key',
    'lookupValues' => array(),
    'validation' => array('required')
));
echo data_entry_helper::text_input(array(
    'label'=>'Key Value',
    'fieldname'=>'workflow_metadata:key_value',
    'default'=>html::initial_value($values, 'workflow_metadata:key_value'),
    'validation' => array('required')
));
echo data_entry_helper::text_input(array(
    'label'=>'Verification delay (hours)',
    'fieldname'=>'workflow_metadata:verification_delay_hours',
    'default'=>html::initial_value($values, 'workflow_metadata:verification_delay_hours'),
    'validation' => array('required','integer','min(0)')
));
echo data_entry_helper::checkbox(array(
    'label' => 'Send immediate notification emails to verifiers',
    'fieldname' => 'workflow_metadata:verifier_notifications_immediate',
    'default' => html::initial_value($values, 'workflow_metadata:verifier_notifications_immediate') // default false
));
if(isset($values['workflow_metadata:id'])) {
  $default = html::initial_value($values, 'workflow_metadata:log_all_communications');
} else {
  $default = true;
}
echo data_entry_helper::checkbox(array(
    'label' => 'Log all communications',
    'fieldname' => 'workflow_metadata:log_all_communications',
    'default' => $default // default true
));

echo $metadata;
echo html::form_buttons(html::initial_value($values, 'workflow_metadata:id')!=null, false, false);

?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $("#workflow_metadata\\:entity").change(function() {
      var entities =
<?php
echo json_encode($other_data['entities']);
?>,
          previous_value = $("#workflow_metadata\\:key").val(),
          entityKeys = Object.keys(entities);
      // Build keys list for select.
      if(previous_value === null || previous_value==="")
        previous_value = $("#old_workflow_metadata_key").val();
      $("#workflow_metadata\\:key option").remove();
      for(var i = 0; i< entityKeys.length; i++) {
        if(entityKeys[i] === $("#workflow_metadata\\:entity").val()) {
          for(var j = 0; j< entities[entityKeys[i]].keys.length; j++) {
            $("#workflow_metadata\\:key").append('<option value="' + entities[entityKeys[i]].keys[j].db_store_value +
                '">' + entities[entityKeys[i]].keys[j].title + '</option>');
          }
        }
      }
      $("#workflow_metadata\\:key").val(previous_value);
    });
    $("#workflow_metadata\\:entity").change();
});

</script>
<?php

data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
  </fieldset>
</form>