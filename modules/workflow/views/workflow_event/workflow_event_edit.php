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

warehouse::loadHelpers(['data_entry_helper']);
$id = html::initial_value($values, 'workflow_event:id');
if (isset($_POST)) {
  data_entry_helper::dump_errors(['errors' => $this->model->getAllErrors()]);
}
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
?>
<form action="<?php echo url::site(); ?>workflow_event/save" method="post" id="entry_form">
  <fieldset>
    <legend>Workflow Event definition details</legend>
    <?php
    echo data_entry_helper::hidden_text([
      'fieldname' => 'workflow_event:id',
      'default' => $id,
    ]);
    $messages = [];
    // Where controls have no choice available, show a message instead.
    if (count($other_data['groupSelectItems']) === 1) {
      $group = array_keys($other_data['groupSelectItems'])[0];
      echo data_entry_helper::hidden_text([
        'fieldname' => 'workflow_event:group_code',
        'default' => $group,
      ]);
      $messages[] = "associated with $group";
    }
    if (count($other_data['entitySelectItems']) === 1) {
      $entity = array_keys($other_data['entitySelectItems'])[0];
      echo data_entry_helper::hidden_text([
        'fieldname' => 'workflow_event:entity',
        'default' => $entity,
      ]);
      $messages[] = "for changes to " . inflector::plural($entity);
    }
    if (count($messages)) {
      echo '<p>This event will be ' . implode(' ', $messages) . '.</p>';
    }
    if (count($other_data['groupSelectItems']) !== 1) {
      echo data_entry_helper::select([
        'label' => 'Workflow group',
        'fieldname' => 'workflow_event:group_code',
        'lookupValues' => $other_data['groupSelectItems'],
        'default' => html::initial_value($values, 'workflow_event:group_code'),
        'validation' => ['required'],
        'helpText' => 'The workflow groups available must be configured by the warehouse administrator and define which website\'s records will be affected by this event definition.',
      ]);
    }
    if (count($other_data['entitySelectItems']) !== 1) {
      echo data_entry_helper::select([
        'label' => 'Entity',
        'fieldname' => 'workflow_event:entity',
        'lookupValues' => $other_data['entitySelectItems'],
        'default' => html::initial_value($values, 'workflow_event:entity'),
        'validation' => ['required'],
      ]);
    }
    echo data_entry_helper::hidden_text([
      'fieldname' => 'old_workflow_event_key',
      'default' => html::initial_value($values, 'workflow_event:key'),
    ]);
    echo data_entry_helper::select([
      'label' => 'Key',
      'fieldname' => 'workflow_event:key',
      'lookupValues' => [],
      'validation' => ['required'],
    ]);
    // Code currently assumes only taxa_taxon_list_external_key possible in the
    // key options.
    $params = $readAuth;
    if ($listId = warehouse::getMasterTaxonListId()) {
      $params += ['taxon_list_id' => $listId];
      $helpText = 'Search for a taxon name in the main species list configured for this warehouse.';
    }
    else {
      $helpText = 'Search for a taxon name.';
    }
    echo data_entry_helper::species_autocomplete([
      'label' => 'Linked taxon',
      'helpText' => $helpText,
      'fieldname' => 'workflow_event:key_value',
      'valueField' => 'external_key',
      'default' => html::initial_value($values, 'workflow_event:key_value'),
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
      'extraParams' => $params,
      'validation' => ['required'],
    ]);
    echo data_entry_helper::select([
      'label' => 'Alternative species checklist',
      'fieldname' => 'taxon_list_id',
      'table' => 'taxon_list',
      'valueField' => 'id',
      'captionField' => 'title',
      'default' => $listId,
      'extraParams' => $readAuth,
      'helpText' => 'If using taxa not on the master species list, choose the alternative list here before searching.',
    ]);
    echo data_entry_helper::hidden_text([
      'fieldname' => 'old_workflow_event_event_type',
      'default' => html::initial_value($values, 'workflow_event:event_type'),
    ]);
    echo data_entry_helper::select([
      'label' => 'Event type',
      'fieldname' => 'workflow_event:event_type',
      'lookupValues' => [],
      'validation' => ['required'],
    ]);
    echo '<fieldset><legend>Event filters</legend>';
    echo data_entry_helper::select([
      'label' => 'Location type',
      'fieldname' => 'location_type',
      'table' => 'termlists_term',
      'valueField' => 'id',
      'captionField' => 'term',
      'extraParams' => $readAuth + ['termlist_external_key' => 'indicia:location_types', 'order_by' => 'term'],
      'blankText' => '<Please select>',
      'helpText' => 'Choose the location type to search within when adding a location boundary filter below.',
    ]);
    echo data_entry_helper::sub_list([
      'label' => 'Location boundary filter',
      'fieldname' => 'workflow_event:location_ids_filter',
      'helpText' => lang::get('When this event should only trigger if a record falls inside a geographic region, ' .
        'specify the list of locations covering that region (or regions).'),
      'table' => 'location',
      'captionField' => 'name',
      'valueField' => 'id',
      'extraParams' => $readAuth,
      'addToTable' => FALSE,
      'default' => empty($values['location_ids_filter_array']) ? NULL : $values['location_ids_filter_array'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Attribute value filter term',
      'fieldname' => 'workflow_event:attrs_filter_term',
      'helpText' => lang::get('When this event should only trigger if a certain attribute value is present, specify ' .
        'the DwC term here which identifies the attribute to use (e.g. ReproductiveCondition or Stage). Typically ' .
        'used to limit bird events to breeding ReproductiveCondition terms.'),
      'default' => html::initial_value($values, 'workflow_event:attrs_filter_term'),
    ]);
    $filterValues = html::initial_value($values, 'workflow_event:attrs_filter_values');
    if (!empty($filterValues)) {
      $filterValues = trim($filterValues, '{}');
      $filterValues = str_replace(',', "\n", $filterValues);
    }
    echo data_entry_helper::textarea([
      'label' => 'Attribute value filter values',
      'fieldname' => 'workflow_event:attrs_filter_values',
      'default' => $filterValues,
    ]);
    echo '</fieldset>';
    echo '<fieldset><legend>Data updates</legend>';
    echo data_entry_helper::checkbox([
      'label' => 'Rewind record state first',
      'fieldname' => 'workflow_event:mimic_rewind_first',
      'default' => html::initial_value($values, 'workflow_event:mimic_rewind_first'),
      'helpText' => 'Reset the record to its initial state before applying the changes below.',
    ]);
    echo data_entry_helper::jsonwidget([
      'fieldname' => 'workflow_event:values',
      'schema' => $other_data['jsonSchema'],
      'default' => html::initial_value($values, 'workflow_event:values'),
    ]);
    echo '</fieldset>';
    echo $metadata;
    data_entry_helper::$indiciaData['entities'] = $other_data['entities'];

    echo html::form_buttons(!empty($id), FALSE, FALSE);
    data_entry_helper::enable_validation('entry_form');
    echo data_entry_helper::dump_javascript();
    ?>
  </fieldset>
</form>
