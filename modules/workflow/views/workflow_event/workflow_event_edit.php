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
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
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
    $messages = [];
    // Where controls have no choice available, show a message instead.
    if (count($other_data['groupSelectItems']) === 1) {
      $group = array_keys($other_data['groupSelectItems'])[0];
      echo data_entry_helper::hidden_text(array(
        'fieldname' => 'workflow_event:group_code',
        'default' => $group,
      ));
      $messages[] = "associated with $group";
    }
    if (count($other_data['entitySelectItems']) === 1) {
      $entity = array_keys($other_data['entitySelectItems'])[0];
      echo data_entry_helper::hidden_text(array(
        'fieldname' => 'workflow_event:entity',
        'default' => $entity,
      ));
      $messages[] = "for changes to " . inflector::plural($entity);
    }
    if (count($messages)) {
      echo '<p>This event will be ' . implode(' ', $messages) . '.</p>';
    }
    if (count($other_data['groupSelectItems']) !== 1) {
      echo data_entry_helper::select(array(
        'label' => 'Workflow group',
        'fieldname' => 'workflow_event:group_code',
        'lookupValues' => $other_data['groupSelectItems'],
        'default' => html::initial_value($values, 'workflow_event:group_code'),
        'validation' => array('required'),
        'helpText' => 'The workflow groups available must be configured by the warehouse administrator and define ' .
          'which website\'s records will be affected by this event definition.'
      ));
    }
    if (count($other_data['entitySelectItems']) !== 1) {
      echo data_entry_helper::select(array(
        'label' => 'Entity',
        'fieldname' => 'workflow_event:entity',
        'lookupValues' => $other_data['entitySelectItems'],
        'default' => html::initial_value($values, 'workflow_event:entity'),
        'validation' => array('required'),
      ));
    }
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
    // Code currently assumes only taxa_taxon_list_external_key possible in the
    // key options.
    $params = $readAuth;
    if ($listId = warehouse::getMasterTaxonListId()) {
      $params += array('taxon_list_id' => $listId);
    }
    echo data_entry_helper::species_autocomplete(array(
      'label' => 'Linked taxon',
      'fieldname' => 'workflow_event:key_value',
      'valueField' => 'external_key',
      'default' => html::initial_value($values, 'workflow_event:key_value'),
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
      'extraParams' => $params,
      'validation' => array('required'),
    ));
    echo data_entry_helper::select(array(
      'label' => 'Alternative species checklist',
      'fieldname' => 'taxon_list_id',
      'table' => 'taxon_list',
      'valueField' => 'id',
      'captionField' => 'title',
      'default' => $listId,
      'extraParams' => $readAuth,
      'helpText' => 'If using taxa not on the master species list, choose the alternative list here before searching.',
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
    echo data_entry_helper::checkbox(array(
      'label' => 'Rewind record state first',
      'fieldname' => 'workflow_event:mimic_rewind_first',
      'default' => html::initial_value($values, 'workflow_event:mimic_rewind_first'),
      'helpText' => 'Reset the record to its initial state before applying the changes below.',
    ));
    echo data_entry_helper::jsonwidget(array(
      'fieldname' => 'workflow_event:values',
      'schema' => $other_data['jsonSchema'],
      'default' => html::initial_value($values, 'workflow_event:values'),
    ));

    echo $metadata;
    echo html::form_buttons(html::initial_value($values, 'workflow_event:id') != NULL, FALSE, FALSE);

    data_entry_helper::$indiciaData['entities'] = $other_data['entities'];

    data_entry_helper::$dumped_resources[] = 'jquery';
    data_entry_helper::$dumped_resources[] = 'jquery_ui';
    data_entry_helper::$dumped_resources[] = 'fancybox';
    echo data_entry_helper::dump_javascript();
    ?>
  </fieldset>
</form>
