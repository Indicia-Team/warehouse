<?php

/**
 * @file
 * View template for the taxon group edit form.
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
$id = html::initial_value($values, 'uksi_operation:id');
data_entry_helper::add_resource('font_awesome');

function getOrganismKeyControl($fieldName, $label, $helpText, $values) {
  $options = [
    'label' => $label,
    'fieldname' => "uksi_operation:$fieldName",
    'default' => html::initial_value($values, "uksi_operation:$fieldName"),
    'helpText' => $helpText,
  ];
  if (!empty($values["uksi_operation:$fieldName"])) {
    $organismLink = url::site() . 'taxa_search?filter-param_organism_key=' . $values["uksi_operation:$fieldName"];
    $options['afterControl'] = "<a href=\"$organismLink\" target=\"_blank\" title=\"View names for this organism key\"><span class=\"fas fa-eye\"></span></a>";
  }
  return data_entry_helper::text_input($options);
}
?>
<p>This page allows you to specify the details of a UKSI operation.</p>
<?php if ($values['uksi_operation:operation_processed'] === 't') : ?>
<div class="alert alert-success">This operation was processed on <?php echo strftime('%c', strtotime($values['uksi_operation:processed_on'])); ?>.</div>
<?php endif; ?>

<form id="uksi-operation-edit" action="<?php echo url::site() . 'uksi_operation/save'; ?>" method="post">
  <input type="hidden" name="uksi_operation:id" value="<?php echo $id; ?>" />

  <fieldset>
    <legend>System info</legend>
    <div disabled="disabled">
      <?php
      echo data_entry_helper::checkbox([
        'label' => 'Processed',
        'fieldname' => 'uksi_operation:operation_processed',
        'default' => html::initial_value($values, 'uksi_operation:operation_processed'),
        'helpText' => 'Ticked if this operation has already been applied.',
      ]);
      ?>
    </div>
    <?php if (!empty($values['uksi_operation:error_detail'])) : ?>
      <div class="alert alert-danger">This operation has errors.</div>
      <?php
      echo data_entry_helper::textarea([
        'label' => 'Error detail',
        'fieldname' => 'uksi_operation:error_detail',
        'default' => html::initial_value($values, 'uksi_operation:error_detail'),
        'helpText' => 'Processing errors will be shown here. Once you have resolved the issues please clear the ' .
            'errors and save the operation. It will then be reattempted next time you process operations.',
      ]);
      ?>
      <button type="button" class="btn btn-success" id="clear-errors">Clear errors</button>
    <?php endif; ?>
  </fieldset>

  <fieldset>
    <legend>UKSI operation details<?php echo $metadata; ?></legend>
    <?php
    echo data_entry_helper::select([
      'label' => 'Operation',
      'fieldname' => 'uksi_operation:operation',
      'default' => html::initial_value($values, 'uksi_operation:operation'),
      'lookupValues' => [
        'Add synonym' => 'Add synonym',
        'Amend metadata' => 'Amend metadata (deprecated - use Amend taxon instead)',
        'Amend name' => 'Amend name',
        'Amend taxon' => 'Amend taxon',
        'Deprecate name' => 'Deprecate name',
        'Extract name' => 'Extract name',
        'Merge taxa' => 'Merge taxa',
        'Move name' => 'Move name',
        'New taxon' => 'New taxon',
        'Promote name' => 'Promote name',
        'Rename taxon' => 'Rename taxon',
      ],
      'validation' => ['required'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Sequence',
      'fieldname' => 'uksi_operation:sequence',
      'default' => html::initial_value($values, 'uksi_operation:sequence'),
      'validation' => ['required', 'integer'],
      'helpText' => 'Sequence number defining the order in which operations are applied.',
    ]);
    echo getOrganismKeyControl('organism_key', 'Organism Key', 'Organism Key created by this operation', $values);
    echo getOrganismKeyControl('current_organism_key', 'Current Organism Key', 'Existing Organism Key affected by this operation', $values);

    echo data_entry_helper::text_input([
      'label' => 'Taxon Version Key',
      'fieldname' => 'uksi_operation:taxon_version_key',
      'default' => html::initial_value($values, 'uksi_operation:taxon_version_key'),
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Rank',
      'fieldname' => 'uksi_operation:rank',
      'default' => html::initial_value($values, 'uksi_operation:rank'),
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Taxon name',
      'fieldname' => 'uksi_operation:taxon_name',
      'default' => html::initial_value($values, 'uksi_operation:taxon_name'),
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Authority',
      'fieldname' => 'uksi_operation:authority',
      'default' => html::initial_value($values, 'uksi_operation:authority'),
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Attribute',
      'fieldname' => 'uksi_operation:attribute',
      'default' => html::initial_value($values, 'uksi_operation:attribute'),
    ]);
    echo getOrganismKeyControl('parent_organism_key', 'Parent Organism Key', 'Organism Key of parent concept for this operation', $values);
    echo data_entry_helper::text_input([
      'label' => 'Parent Name',
      'fieldname' => 'uksi_operation:parent_name',
      'default' => html::initial_value($values, 'uksi_operation:parent_name'),
      'helpText' => 'Alternative to Parent Organism Key. Must refer to a previous New Taxon operation.',
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Synonym',
      'fieldname' => 'uksi_operation:synonym',
      'default' => html::initial_value($values, 'uksi_operation:synonym'),
      'helpText' => 'For promote name operations, the TVK of the name being promoted. For merge taxa ' .
          'operations, the organism key of the taxon being merged into another and relegated to junior synonym.',
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Taxon group key',
      'fieldname' => 'uksi_operation:taxon_group_key',
      'default' => html::initial_value($values, 'uksi_operation:taxon_group_key'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Marine',
      'fieldname' => 'uksi_operation:marine',
      'default' => html::initial_value($values, 'uksi_operation:marine'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Terrestrial',
      'fieldname' => 'uksi_operation:terrestrial',
      'default' => html::initial_value($values, 'uksi_operation:terrestrial'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Freshwater',
      'fieldname' => 'uksi_operation:freshwater',
      'default' => html::initial_value($values, 'uksi_operation:freshwater'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Non-native',
      'fieldname' => 'uksi_operation:non_native',
      'default' => html::initial_value($values, 'uksi_operation:non_native'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Redundant',
      'fieldname' => 'uksi_operation:redundant',
      'default' => html::initial_value($values, 'uksi_operation:redundant'),
    ]);
    echo data_entry_helper::date_picker([
      'label' => 'Deleted date',
      'fieldname' => 'uksi_operation:deleted_date',
      'default' => html::initial_value($values, 'uksi_operation:deleted_date'),
      'allowFuture' => TRUE,
    ]);
    echo data_entry_helper::date_picker([
      'label' => 'Batch processed on date',
      'fieldname' => 'uksi_operation:batch_processed_on',
      'default' => html::initial_value($values, 'uksi_operation:batch_processed_on'),
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Notes',
      'fieldname' => 'uksi_operation:notes',
      'default' => html::initial_value($values, 'uksi_operation:notes'),
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Testing comment',
      'fieldname' => 'uksi_operation:testing_comment',
      'default' => html::initial_value($values, 'uksi_operation:testing_comment'),
    ]);
    ?>
  </fieldset>

  <?php
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('uksi-operation-edit');
  echo data_entry_helper::dump_javascript();
  ?>
</form>

<script type="text/javascript">
  $('#clear-errors').click(function() {
    $('#uksi_operation\\:error_detail').val('');
  });
</script>