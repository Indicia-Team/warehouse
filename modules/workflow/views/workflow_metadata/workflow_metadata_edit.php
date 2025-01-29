<?php

/**
 * @file
 * View template for the workflow metadata edit form.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/warehouse
 */

require_once DOCROOT . 'client_helpers/data_entry_helper.php';
if (isset($_POST)) {
  data_entry_helper::dump_errors(array('errors' => $this->model->getAllErrors()));
}
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
?>
<form class="iform" action="<?php echo url::site(); ?>workflow_metadata/save" method="post" id="entry-form">
  <fieldset>
    <legend>Workflow Metadata specification details</legend>
    <?php
    data_entry_helper::link_default_stylesheet();
    data_entry_helper::enable_validation('entry-form');
    echo data_entry_helper::hidden_text(array(
        'fieldname' => 'workflow_metadata:id',
        'default' => html::initial_value($values, 'workflow_metadata:id')
    ));
    $messages = [];
    // Where controls have no choice available, show a message instead.
    if (count($other_data['groupSelectItems']) === 1) {
      $group = array_keys($other_data['groupSelectItems'])[0];
      echo data_entry_helper::hidden_text(array(
        'fieldname' => 'workflow_metadata:group_code',
        'default' => $group,
      ));
      $messages[] = "associated with $group";
    }
    if (count($other_data['entitySelectItems']) === 1) {
      $entity = array_keys($other_data['entitySelectItems'])[0];
      echo data_entry_helper::hidden_text(array(
        'fieldname' => 'workflow_metadata:entity',
        'default' => $entity,
      ));
      $messages[] = "for " . inflector::plural($entity);
    }
    if (count($messages)) {
      echo '<p>This metadata definition will be ' . implode(' ', $messages) . '.</p>';
    }
    if (count($other_data['groupSelectItems']) !== 1) {
      echo data_entry_helper::select(array(
        'label' => 'Workflow group',
        'fieldname' => 'workflow_metadata:group_code',
        'lookupValues' => $other_data['groupSelectItems'],
        'default' => html::initial_value($values, 'workflow_metadata:group_code'),
        'validation' => array('required'),
      ));
    }
    if (count($other_data['entitySelectItems']) !== 1) {
      echo data_entry_helper::select(array(
        'label' => 'Entity',
        'fieldname' => 'workflow_metadata:entity',
        'lookupValues' => $other_data['entitySelectItems'],
        'default' => html::initial_value($values, 'workflow_metadata:entity'),
        'validation' => array('required'),
      ));
    }
    echo data_entry_helper::hidden_text(array(
      'fieldname' => 'old_workflow_metadata_key',
      'default' => html::initial_value($values, 'workflow_metadata:key'),
    ));
    echo data_entry_helper::select(array(
      'label' => 'Key',
      'fieldname' => 'workflow_metadata:key',
      'lookupValues' => array(),
      'validation' => array('required'),
    ));
    // Code currently assumes only taxa_taxon_list_external_key possible in the key options.
    $params = $readAuth;
    if ($listId = warehouse::getMasterTaxonListId()) {
      $params += array('taxon_list_id' => $listId);
    }
    echo data_entry_helper::species_autocomplete(array(
      'label' => 'Linked taxon',
      'fieldname' => 'workflow_metadata:key_value',
      'valueField' => 'external_key',
      'default' => html::initial_value($values, 'workflow_metadata:key_value'),
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
      'extraParams' => $params,
      'validation' => array('required'),
      'helpText' => 'Search for the taxon name this metadata form applies to.'
    ));
    echo data_entry_helper::text_input(array(
      'label' => 'Verification is due in',
      'fieldname' => 'workflow_metadata:verification_delay_hours',
      'default' => html::initial_value($values, 'workflow_metadata:verification_delay_hours'),
      'validation' => array('required', 'integer', 'min(0)'),
      'helpText' => 'Number of hours after record entry when verification becomes overdue.',
      'afterControl' => 'hours',
    ));
    echo data_entry_helper::checkbox(array(
      'label' => 'Send immediate notification emails to verifiers',
      'fieldname' => 'workflow_metadata:verifier_notifications_immediate',
      'default' => html::initial_value($values, 'workflow_metadata:verifier_notifications_immediate'),
    ));
    if (isset($values['workflow_metadata:id'])) {
      $default = html::initial_value($values, 'workflow_metadata:log_all_communications');
    }
    else {
      $default = TRUE;
    }
    echo data_entry_helper::checkbox(array(
      'label' => 'Log all communications',
      'fieldname' => 'workflow_metadata:log_all_communications',
      'default' => $default,
    ));

    echo $metadata;
    echo html::form_buttons(html::initial_value($values, 'workflow_metadata:id') != NULL, FALSE, FALSE);

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
              if (entities[entityKeys[i]].keys.length === 1) {
                $('#ctrl-wrap-workflow_metadata-key').hide();
              } else {
                $('#ctrl-wrap-workflow_metadata-key').show();
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
