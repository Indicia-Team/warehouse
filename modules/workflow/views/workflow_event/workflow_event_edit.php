<?php

/**
 * @file
 * View template for the workflow event edit form.
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
 * @package Modules
 * @subpackage Workflow
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/
 */

require_once DOCROOT . 'client_helpers/data_entry_helper.php';
if (isset($_POST)) {
  data_entry_helper::dump_errors(array('errors' => $this->model->getAllErrors()));
}
?>
<form action="<?php echo url::site(); ?>workflow_event/save" method="post" id="entry-form">
  <fieldset>
    <legend>Workflow Event definition details</legend>
    <?php
    data_entry_helper::link_default_stylesheet();
    data_entry_helper::enable_validation('entry-form');
    echo data_entry_helper::hidden_text(array(
      'fieldname' => 'workflow_event:id',
      'default' => html::initial_value($values, 'workflow_event:id'),
    ));
    echo data_entry_helper::select(array(
      'label' => 'Workflow group',
      'fieldname' => 'workflow_event:group_code',
      'lookupValues' => $other_data['groupSelectItems'],
      'default' => html::initial_value($values, 'workflow_event:group_code'),
      'validation' => array('required'),
    ));
    echo data_entry_helper::select(array(
      'label' => 'Entity',
      'fieldname' => 'workflow_event:entity',
      'lookupValues' => $other_data['entitySelectItems'],
      'default' => html::initial_value($values, 'workflow_event:entity'),
      'validation' => array('required'),
    ));
    echo data_entry_helper::hidden_text(array(
      'fieldname' => 'old_workflow_event_event_type',
      'default' => html::initial_value($values, 'workflow_event:event_type'),
    ));
    echo data_entry_helper::select(array(
      'label' => 'Event Type',
      'fieldname' => 'workflow_event:event_type',
      'lookupValues' => array(),
      'validation' => array('required'),
    ));
    echo data_entry_helper::hidden_text(array(
      'fieldname' => 'old_workflow_event_key',
      'default' => html::initial_value($values, 'workflow_event:key'),
    ));
    echo data_entry_helper::select(array(
      'label' => 'Key',
      'fieldname' => 'workflow_event:key',
      'lookupValues' => array(),
      'validation' => array('required'),
    ));
    echo data_entry_helper::text_input(array(
      'label' => 'Key Value',
      'fieldname' => 'workflow_event:key_value',
      'default' => html::initial_value($values, 'workflow_event:key_value'),
      'validation' => array('required'),
    ));
    echo data_entry_helper::checkbox(array(
      'label' => 'Mimic Rewind first',
      'fieldname' => 'workflow_event:mimic_rewind_first',
      'default' => html::initial_value($values, 'workflow_event:mimic_rewind_first'),
    ));
    echo data_entry_helper::jsonwidget(array(
      'fieldname' => 'workflow_event:values',
      'schema' => $other_data['jsonSchema'],
      'default' => html::initial_value($values, 'workflow_event:values'),
    ));

    echo $metadata;
    echo html::form_buttons(html::initial_value($values, 'workflow_event:id') != NULL, FALSE, FALSE);

    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $("#workflow_event\\:entity").change(function() {
          var entities =
    <?php
    echo json_encode($other_data['entities']);
    ?>,
              previous_value = $("#workflow_event\\:event_type").val(),
              entityKeys = Object.keys(entities);
          // First build event types list for select.
          if(previous_value === null || previous_value==="")
            previous_value = $("#old_workflow_event_event_type").val();
          $("#workflow_event\\:event_type option").remove();
          for(var i = 0; i< entityKeys.length; i++) {
            if(entityKeys[i] == $("#workflow_event\\:entity").val()) {
              for(var j = 0; j< entities[entityKeys[i]].event_types.length; j++) {
                $("#workflow_event\\:event_type").append('<option value="'+entities[entityKeys[i]].event_types[j].code+
                    '">'+entities[entityKeys[i]].event_types[j].title+'</option>');
              }
            }
          }
          $("#workflow_event\\:event_type").val(previous_value);
          // now do Keys
          previous_value = $("#workflow_event\\:key").val();
          if(previous_value === null || previous_value==="")
            previous_value = $("#old_workflow_event_key").val();
          $("#workflow_event\\:key option").remove();
          for(var i = 0; i< entityKeys.length; i++) {
            if(entityKeys[i] === $("#workflow_event\\:entity").val()) {
              for(var j = 0; j< entities[entityKeys[i]].keys.length; j++) {
                $("#workflow_event\\:key").append('<option value="'+entities[entityKeys[i]].keys[j].db_store_value+
                    '">'+entities[entityKeys[i]].keys[j].title+'</option>');
              }
            }
          }
          $("#workflow_event\\:key").val(previous_value);
        });
        $("#workflow_event\\:entity").change();
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
