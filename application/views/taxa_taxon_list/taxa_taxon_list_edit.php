<?php

/**
 * @file
 * View template for the taxa taxon list edit form.
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

require_once 'application/views/multi_value_data_editing_support.php';
warehouse::loadHelpers(['data_entry_helper', 'map_helper']);
$id = html::initial_value($values, 'taxa_taxon_list:id');
$isNewTaxon = empty($id);
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));

echo html::error_message($model->getError('deleted'));
?>
<form id="entry_form" action="<?php echo url::site() . 'taxa_taxon_list/save' ?>" method="post">
  <fieldset>
    <legend>Naming<?php echo $metadata; ?></legend>
    <input type="hidden" name="taxa_taxon_list:id" value="<?php echo html::initial_value($values, 'taxa_taxon_list:id'); ?>" />
    <input type="hidden" name="taxa_taxon_list:taxon_list_id" value="<?php echo html::initial_value($values, 'taxa_taxon_list:taxon_list_id'); ?>" />
    <input type="hidden" name="taxon:id" value="<?php echo html::initial_value($values, 'taxon:id'); ?>" />
    <input type="hidden" name="taxon_meaning:id" value="<?php echo html::initial_value($values, 'taxon_meaning:id'); ?>" />
    <input type="hidden" name="taxa_taxon_list:preferred" value="t" />
    <?php if ($isNewTaxon) : ?>
      <input type="hidden" name="metaFields:synonyms" id="pending-synonyms" value="" />
      <input type="hidden" name="metaFields:commonNames" id="pending-common-names" value="" />
    <?php endif; ?>
    <?php
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:taxon',
      'label' => 'Taxon name',
      'default' => html::initial_value($values, 'taxon:taxon'),
      'validation' => ['required'],
    ]);
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:attribute',
      'label' => 'Attribute',
      'default' => html::initial_value($values, 'taxon:attribute'),
      'helpText' => 'E.g. sensu stricto or leave blank',
    ]);
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:authority',
      'label' => 'Authority',
      'default' => html::initial_value($values, 'taxon:authority'),
    ]);
    echo data_entry_helper::select([
      'fieldname' => 'taxon:language_id',
      'label' => 'Language',
      'default' => html::initial_value($values, 'taxon:language_id'),
      'table' => 'language',
      'valueField' => 'id',
      'captionField' => 'language',
      'extraParams' => $readAuth + ['orderby' => 'language'],
      'validation' => ['required'],
      'blankText' => '<please select>',
    ]);
    ?>
  </fieldset>
  <fieldset>
    <legend>Other Details</legend>
    <?php
    echo data_entry_helper::select([
      'fieldname' => 'taxon:taxon_group_id',
      'label' => 'Taxon group',
      'default' => html::initial_value($values, 'taxon:taxon_group_id'),
      'table' => 'taxon_group',
      'valueField' => 'id',
      'captionField' => 'title',
      'extraParams' => $readAuth + ['orderby' => 'title'],
      'validation' => ['required'],
      'blankText' => '<please select>',
    ]);
    echo data_entry_helper::select([
      'fieldname' => 'taxon:taxon_rank_id',
      'label' => 'Taxon rank',
      'default' => html::initial_value($values, 'taxon:taxon_rank_id'),
      'table' => 'taxon_rank',
      'valueField' => 'id',
      'captionField' => 'rank',
      'extraParams' => $readAuth + ['orderby' => 'sort_order'],
      'blankText' => '<please select>',
    ]);
    echo data_entry_helper::textarea([
      'fieldname' => 'taxon:description',
      'label' => 'Description',
      'default' => html::initial_value($values, 'taxon:description'),
      'helpText' => 'General description which applies to this taxon on all lists it is linked to.',
    ]);
    echo data_entry_helper::textarea([
      'fieldname' => 'taxa_taxon_list:description',
      'label' => 'Description on this list',
      'default' => html::initial_value($values, 'taxa_taxon_list:description'),
      'helpText' => 'Description which applies only to this taxon within the context of this list.',
    ]);
    $helpText = <<<TXT
Unique identifier for the accepted name for this taxon as defined by an external source. For example in the UK this field is
typically used to store an NBN Taxon Version Key for the accepted name.
TXT;
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:external_key',
      'label' => 'Accepted name unique identifier (external key)',
      'default' => html::initial_value($values, 'taxon:external_key'),
      'helpText' => $helpText,
      'attributes' => ['maxlength' => 50],
    ]);
    $helpText = <<<TXT
Unique identifier for this taxon name as defined by an external source. For example in the UK this field is
typically used to store an NBN Taxon Version Key for the name, which will therefore be the same as the accepted name
unique identifier, but any synonyms and common names would have a different Taxon Version Key / taxon name unique
identifier.
TXT;
    echo data_entry_helper::text_input([
      'label' => 'Taxon name unique identifier (search code)',
      'fieldname' => 'taxon:search_code',
      'default' => html::initial_value($values, 'taxon:search_code'),
      'helpText' => $helpText,
      'attributes' => ['maxlength' => 20],
    ]);
    $helpText = <<<TXT
Unique identifier for this taxon concept as defined by an external source. When linking to UKSI, this
field is used to store the Organism Key.
TXT;
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:organism_key',
      'label' => 'Organism unique identifier (organism key)',
      'default' => html::initial_value($values, 'taxon:organism_key'),
      'helpText' => $helpText,
    ]);
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon_meaning:id',
      'label' => 'Taxon meaning ID',
      'default' => html::initial_value($values, 'taxon_meaning:id'),
      'helpText' => 'Unique ID assigned to this taxomic concept by Indicia.',
      'disabled' => TRUE,
    ]);
    echo data_entry_helper::species_autocomplete([
      'label' => 'Parent taxon',
      'fieldname' => 'taxa_taxon_list:parent_id',
      'default' => html::initial_value($values, 'taxa_taxon_list:parent_id'),
      'extraParams' => $readAuth + [
        'taxon_list_id' => $values['taxa_taxon_list:taxon_list_id'],
      ],
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeAuthorities' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Sort order in list',
      'fieldname' => 'taxa_taxon_list:taxonomic_sort_order',
      'default' => html::initial_value($values, 'taxa_taxon_list:taxonomic_sort_order'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Marine',
      'fieldname' => 'taxon:marine_flag',
      'default' => html::initial_value($values, 'taxon:marine_flag'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Freshwater',
      'fieldname' => 'taxon:freshwater_flag',
      'default' => html::initial_value($values, 'taxon:freshwater_flag'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Terrestrial',
      'fieldname' => 'taxon:terrestrial_flag',
      'default' => html::initial_value($values, 'taxon:terrestrial_flag'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Non-native',
      'fieldname' => 'taxon:non_native_flag',
      'default' => html::initial_value($values, 'taxon:non_native_flag'),
    ]);
    ?>
  </fieldset>
  <fieldset class="row">
    <legend>Data entry flags</legend>
    <div class="col-md-4">
      <?php
      echo data_entry_helper::checkbox([
        'label' => 'Allow data entry',
        'fieldname' => 'taxa_taxon_list:allow_data_entry',
        'default' => html::initial_value($values, 'taxa_taxon_list:allow_data_entry'),
        'helpText' => 'Untick this to leave the taxon in the database, but block it from searches when adding new records.',
      ]);
      ?>
    </div>
    <div class="col-md-4">
      <?php
      echo data_entry_helper::checkbox([
        'label' => 'Manually entered',
        'fieldname' => 'taxa_taxon_list:manually_entered',
        'default' => html::initial_value($values, 'taxa_taxon_list:manually_entered'),
        'helpText' => 'Ticked for taxa that were entered manually as opposed to via automatic synchronisation with external lists.',
      ]);
      ?>
    </div>
  </fieldset>
  <fieldset class="row">
    <legend>Taxon deprecation and naming</legend>
    <div class="col-md-4">
      <?php
      echo data_entry_helper::checkbox([
        'label' => 'Organism deprecated',
        'fieldname' => 'taxon:organism_deprecated',
        'default' => html::initial_value($values, 'taxon:organism_deprecated'),
        'helpText' => 'For internal use by scripts which sync names from other databases.',
      ]);
      ?>
    </div>
    <div class="col-md-4">
      <?php
      echo data_entry_helper::checkbox([
        'label' => 'Name deprecated',
        'fieldname' => 'taxon:name_deprecated',
        'default' => html::initial_value($values, 'taxon:name_deprecated'),
        'helpText' => 'For internal use by scripts which sync names from other databases.',
      ]);
      ?>
    </div>
    <div class="col-md-4">
      <?php
      echo data_entry_helper::text_input([
        'label' => 'Name form',
        'fieldname' => 'taxon:name_form',
        'default' => html::initial_value($values, 'taxon:name_form'),
        'maxlength' => 1,
        'helpText' => 'For internal use by scripts which sync names from other databases.',
      ]);
      ?>
    </div>
  </fieldset>
  <div id="delete-replacement-check-msg" class="alert alert-info" style="display: none">
    Checking if there are existing occurrences for this taxon...
  </div>
  <div id="delete-replacement" class="alert alert-warning" style="display: none">
    <p>There are existing occurrences for this taxon. Please confirm which taxon you would like to replace them with.</p>
    <?php
    echo data_entry_helper::select([
      'label' => 'List to search in',
      'fieldname' => 'filter-taxon_list_id',
      'lookupValues' => $other_data['taxon_lists'],
      'default' => html::initial_value($values, 'taxa_taxon_list:taxon_list_id'),
    ]);
    echo data_entry_helper::species_autocomplete([
      'label' => 'Replacement taxon',
      'fieldname' => 'new_taxa_taxon_list_id',
      'extraParams' => $readAuth + [
        'taxon_list_id' => $values['taxa_taxon_list:taxon_list_id'],
      ],
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeAuthorities' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
    ]);
    ?>
    <input type="submit" name="submit" value="Delete" class="btn btn-warning" id="confirm-delete-btn" />
  </div>
  <?php
  data_entry_helper::enableValidation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>

<?php
$preferredId = (int) html::initial_value($values, 'taxa_taxon_list:id');
$taxonMeaningId = (int) html::initial_value($values, 'taxon_meaning:id');
$taxonListId = (int) html::initial_value($values, 'taxa_taxon_list:taxon_list_id');
$hasAttributeValues = FALSE;
foreach ($values['attributes'] as $attribute) {
  if (!empty($attribute['id'])) {
    $hasAttributeValues = TRUE;
    break;
  }
}

/**
 * Render an editable row in the related names grid.
 *
 * @param array $rows
 *   Related name records to render.
 * @param string $type
 *   The related name type, either synonym or common_name.
 *
 * @return void
 *   Outputs the related name rows.
 */
$renderNameRows = function ($rows, $type) use ($preferredId, $taxonMeaningId, $taxonListId, $other_data, $hasAttributeValues) {
  foreach ($rows as $row) {
    $isSynonym = $type === 'synonym';
    $rowId = (int) $row['id'];
    ?>
    <tr>
      <td colspan="<?php echo $isSynonym ? 9 : 8; ?>">
        <form id="related-name-save-<?php echo $rowId; ?>" method="post" action="<?php echo url::site(); ?>taxa_taxon_list/save_related_name" class="form-inline related-name-form">
          <input type="hidden" name="id" value="<?php echo $rowId; ?>" />
          <input type="hidden" name="taxon_meaning_id" value="<?php echo $taxonMeaningId; ?>" />
          <input type="hidden" name="taxon_meaning_preferred_id" value="<?php echo $preferredId; ?>" />
          <input type="hidden" name="taxon_list_id" value="<?php echo $taxonListId; ?>" />
          <input type="hidden" name="name_type" value="<?php echo $isSynonym ? 'synonym' : 'common_name'; ?>" />
          <input class="form-control" name="taxon" value="<?php echo html::specialchars($row['taxon']); ?>" required />
          <?php if ($isSynonym) : ?>
            <input class="form-control" name="authority" placeholder="Authority" value="<?php echo html::specialchars($row['authority']); ?>" />
          <?php endif; ?>
          <?php if (!$isSynonym) : ?>
            <select class="form-control" name="language_iso">
              <?php foreach ($other_data['name_languages'] as $language) : ?>
                <option value="<?php echo html::specialchars($language['iso']); ?>" <?php echo $language['iso'] === $row['language_iso'] ? 'selected' : ''; ?>><?php echo html::specialchars($language['language']); ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
          <input class="form-control" name="search_code" placeholder="Search code" value="<?php echo html::specialchars($row['search_code']); ?>" />
          <?php if ($isSynonym) : ?>
            <select class="form-control" name="taxon_rank_id">
              <option value="">&lt;rank&gt;</option>
              <?php foreach ($other_data['name_ranks'] as $rank) : ?>
                <option value="<?php echo (int) $rank['id']; ?>" <?php echo (int) $rank['id'] === (int) $row['taxon_rank_id'] ? 'selected' : ''; ?>><?php echo html::specialchars($rank['rank']); ?></option>
              <?php endforeach; ?>
            </select>
            <input class="form-control" name="attribute" placeholder="Attribute" value="<?php echo html::specialchars($row['attribute']); ?>" />
          <?php else : ?>
            <input type="hidden" name="taxon_rank_id" value="" />
            <input type="hidden" name="attribute" value="" />
          <?php endif; ?>
          <input class="form-control" name="name_form" placeholder="Name form" maxlength="1" value="<?php echo html::specialchars($row['name_form']); ?>" />
          <label><input type="checkbox" name="allow_data_entry" value="t" <?php echo $row['allow_data_entry'] === 't' ? 'checked' : ''; ?> /> Data entry</label>
          <label><input type="checkbox" name="manually_entered" value="t" <?php echo $row['manually_entered'] === 't' ? 'checked' : ''; ?> /> Manually entered</label>
          <label><input type="checkbox" name="name_deprecated" value="t" <?php echo $row['name_deprecated'] === 't' ? 'checked' : ''; ?> /> Deprecated</label>
        </form>
        <div class="related-name-actions">
          <button type="submit" form="related-name-save-<?php echo $rowId; ?>" name="submit" value="Save" class="btn btn-xs btn-primary">Save</button>
          <?php if ($isSynonym) : ?>
            <form method="post" action="<?php echo url::site(); ?>taxa_taxon_list/promote_synonym" class="form-inline related-name-action-form" onsubmit="return <?php echo $hasAttributeValues ? 'handlePromoteSynonym(this)' : 'true'; ?>;">
              <input type="hidden" name="id" value="<?php echo $rowId; ?>" />
              <input type="hidden" name="preferred_id" value="<?php echo $preferredId; ?>" />
              <input type="hidden" name="move_attributes" value="t" />
              <button type="submit" class="btn btn-xs btn-success">Make preferred</button>
            </form>
          <?php endif; ?>
          <?php if (!$isSynonym && empty($row['is_default'])) : ?>
            <form method="post" action="<?php echo url::site(); ?>taxa_taxon_list/make_default_common_name" class="form-inline related-name-action-form">
              <input type="hidden" name="id" value="<?php echo $rowId; ?>" />
              <input type="hidden" name="preferred_id" value="<?php echo $preferredId; ?>" />
              <button type="submit" class="btn btn-xs btn-success">Make default</button>
            </form>
          <?php endif; ?>
          <form method="post" action="<?php echo url::site(); ?>taxa_taxon_list/delete_related_name" class="form-inline related-name-action-form" onsubmit="return confirm('Delete this name?');">
            <input type="hidden" name="id" value="<?php echo $rowId; ?>" />
            <input type="hidden" name="taxon_meaning_preferred_id" value="<?php echo $preferredId; ?>" />
            <button type="submit" class="btn btn-xs btn-danger">Delete</button>
          </form>
          <?php if (!$isSynonym && !empty($row['is_default'])) : ?>
            <strong class="default-common-name-badge">Default</strong>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php
  }
};
?>
<fieldset>
  <legend>Synonyms</legend>
  <p>Manage alternative scientific names individually. Make preferred swaps the accepted name for this taxonomic concept.</p>
  <table class="table table-striped related-names">
    <thead><tr><th>Name and details</th></tr></thead>
    <tbody id="pending-synonyms-grid"><?php $renderNameRows($other_data['related_names']['synonyms'], 'synonym'); ?></tbody>
  </table>
  <form method="post" action="<?php echo url::site(); ?>taxa_taxon_list/save_related_name" class="form-inline related-name-form<?php echo $isNewTaxon ? ' pending-related-name-form' : ''; ?>" data-name-type="synonym">
    <input type="hidden" name="taxon_meaning_id" value="<?php echo $taxonMeaningId; ?>" />
    <input type="hidden" name="taxon_meaning_preferred_id" value="<?php echo $preferredId; ?>" />
    <input type="hidden" name="taxon_list_id" value="<?php echo $taxonListId; ?>" />
    <input type="hidden" name="name_type" value="synonym" />
    <input class="form-control" name="taxon" placeholder="New synonym" required />
    <input class="form-control" name="authority" placeholder="Authority" />
    <input class="form-control" name="search_code" placeholder="Search code" />
    <select class="form-control" name="taxon_rank_id">
      <option value="">&lt;rank&gt;</option>
      <?php foreach ($other_data['name_ranks'] as $rank) : ?><option value="<?php echo (int) $rank['id']; ?>"><?php echo html::specialchars($rank['rank']); ?></option><?php endforeach; ?>
    </select>
    <input class="form-control" name="attribute" placeholder="Attribute" />
    <input class="form-control" name="name_form" placeholder="Name form" maxlength="1" />
    <label><input type="checkbox" name="allow_data_entry" value="t" checked /> Data entry</label>
    <label><input type="checkbox" name="manually_entered" value="t" checked /> Manually entered</label>
    <label><input type="checkbox" name="name_deprecated" value="t" /> Deprecated</label>
    <div class="related-name-actions">
      <button type="<?php echo $isNewTaxon ? 'button' : 'submit'; ?>" class="btn btn-primary btn-xs<?php echo $isNewTaxon ? ' add-pending-related-name' : ''; ?>">Add synonym</button>
    </div>
  </form>
</fieldset>
<fieldset>
  <legend>Common names</legend>
  <p>Manage common names individually, including their language and data-entry settings.</p>
  <table class="table table-striped related-names">
    <thead><tr><th>Name and details</th></tr></thead>
    <tbody id="pending-common-names-grid"><?php $renderNameRows($other_data['related_names']['common_names'], 'common_name'); ?></tbody>
  </table>
  <form method="post" action="<?php echo url::site(); ?>taxa_taxon_list/save_related_name" class="form-inline related-name-form<?php echo $isNewTaxon ? ' pending-related-name-form' : ''; ?>" data-name-type="common_name">
    <input type="hidden" name="taxon_meaning_id" value="<?php echo $taxonMeaningId; ?>" />
    <input type="hidden" name="taxon_meaning_preferred_id" value="<?php echo $preferredId; ?>" />
    <input type="hidden" name="taxon_list_id" value="<?php echo $taxonListId; ?>" />
    <input type="hidden" name="name_type" value="common_name" />
    <input class="form-control" name="taxon" placeholder="New common name" required />
    <select class="form-control" name="language_iso">
      <?php foreach ($other_data['name_languages'] as $language) : ?><option value="<?php echo html::specialchars($language['iso']); ?>"><?php echo html::specialchars($language['language']); ?></option><?php endforeach; ?>
    </select>
    <input class="form-control" name="search_code" placeholder="Search code" />
    <input class="form-control" name="name_form" placeholder="Name form" maxlength="1" />
    <label><input type="checkbox" name="allow_data_entry" value="t" checked /> Data entry</label>
    <label><input type="checkbox" name="manually_entered" value="t" checked /> Manually entered</label>
    <label><input type="checkbox" name="name_deprecated" value="t" /> Deprecated</label>
    <div class="related-name-actions">
      <button type="<?php echo $isNewTaxon ? 'button' : 'submit'; ?>" class="btn btn-primary btn-xs<?php echo $isNewTaxon ? ' add-pending-related-name' : ''; ?>">Add common name</button>
    </div>
  </form>
</fieldset>
<?php if (count($values['attributes']) > 0) : ?>
  <fieldset id="taxon-attributes">
    <legend>Taxon Attributes</legend>
    <p>Manage additional attributes for this taxon. These are defined by the list and can be used to store extra information about the taxon.</p>
    <ol>
<?php endif; ?>
    <?php
    // The $values['attributes'] array has multi-value attributes on separate
    // rows, so organise these into sub array.
    $attrsWithMulti = organise_values_attribute_array('taxa_taxon_list_attribute', $values['attributes']);
    // Cycle through the attributes and draw them to the screen.
    foreach ($attrsWithMulti as $taxaTaxonListAttributeId => $wholeAttrToDraw) {
      // Multi-attributes are in a sub array, so the caption is not present
      // at the first level so we can detect this.
      if (!empty($wholeAttrToDraw['caption'])) {
        handle_single_value_attributes('taxAttr', $taxaTaxonListAttributeId, $wholeAttrToDraw, $values);
      } else {
        handle_multi_value_attributes('taxAttr', $taxaTaxonListAttributeId, $wholeAttrToDraw, $values);
      }
    }
    ?>
<?php if (count($values['attributes']) > 0) : ?>
    </ol>
  </fieldset>
<?php endif; ?>
<div id="taxon-form-actions">
  <?php echo html::form_buttons(html::initial_value($values, 'taxa_taxon_list:id') !== NULL); ?>
</div>
<script>
  function handlePromoteSynonym(form) {
    var dialog = document.createElement('div');
    var panel = document.createElement('div');
    var heading = document.createElement('h4');
    var message = document.createElement('p');
    var moveButton = document.createElement('button');
    var keepButton = document.createElement('button');
    var cancelButton = document.createElement('button');

    dialog.id = 'promote-synonym-dialog';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.style.cssText = 'position: fixed; z-index: 1050; inset: 0; background: rgba(0, 0, 0, 0.35); display: flex; align-items: center; justify-content: center;';
    panel.style.cssText = 'background: #fff; border: 1px solid #ccc; border-radius: 4px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5); max-width: 32rem; padding: 1.5rem;';
    heading.textContent = 'Make this synonym the accepted name?';
    message.textContent = 'Choose how to handle attribute values attached to the current preferred name.';
    moveButton.type = 'button';
    moveButton.className = 'btn btn-primary';
    moveButton.textContent = 'Move attribute values';
    keepButton.type = 'button';
    keepButton.className = 'btn btn-default';
    keepButton.textContent = 'Keep attribute values';
    cancelButton.type = 'button';
    cancelButton.className = 'btn btn-link';
    cancelButton.textContent = 'Cancel';

    function closeDialog() {
      dialog.remove();
    }
    function submitPromotion(moveAttributes) {
      form.querySelector('[name="move_attributes"]').value = moveAttributes ? 't' : 'f';
      closeDialog();
      form.submit();
    }
    moveButton.addEventListener('click', function () { submitPromotion(true); });
    keepButton.addEventListener('click', function () { submitPromotion(false); });
    cancelButton.addEventListener('click', closeDialog);
    dialog.addEventListener('click', function (event) {
      if (event.target === dialog) {
        closeDialog();
      }
    });
    panel.appendChild(heading);
    panel.appendChild(message);
    panel.appendChild(moveButton);
    panel.appendChild(document.createTextNode(' '));
    panel.appendChild(keepButton);
    panel.appendChild(document.createTextNode(' '));
    panel.appendChild(cancelButton);
    dialog.appendChild(panel);
    document.body.appendChild(dialog);
    moveButton.focus();
    return false;
  }

  (function () {
    var relatedNameForms = document.querySelectorAll('.related-name-form');
    var warning = 'You have unsaved changes to a common name or synonym. If you continue, those changes will be lost. Continue?';

    relatedNameForms.forEach(function (form) {
      form.addEventListener('input', function () {
        form.setAttribute('data-related-name-dirty', 'true');
      });
      form.addEventListener('change', function () {
        form.setAttribute('data-related-name-dirty', 'true');
      });
      form.addEventListener('submit', function () {
        form.removeAttribute('data-related-name-dirty');
      });
    });

    document.querySelectorAll('#taxon-form-actions button, #taxon-form-actions input').forEach(function (control) {
      var label = control.value || control.textContent.trim();
      if (control.type === 'submit' && label === 'Save') {
        control.addEventListener('click', function (event) {
          if (document.querySelector('[data-related-name-dirty="true"]') && !window.confirm(warning)) {
            event.preventDefault();
          }
        });
      }
    });
  }());

  <?php if ($isNewTaxon) : ?>
  (function () {
    var pendingNames = {synonym: [], common_name: []};

    function renderPendingNames(type) {
      var grid = document.getElementById('pending-' + (type === 'synonym' ? 'synonyms' : 'common-names') + '-grid');
      grid.querySelectorAll('.pending-related-row').forEach(function (row) { row.remove(); });
      pendingNames[type].forEach(function (name, index) {
        var row = document.createElement('tr');
        row.className = 'pending-related-row';
        var cell = document.createElement('td');
        cell.colSpan = type === 'synonym' ? 9 : 8;
        cell.textContent = name.taxon + (type === 'synonym' && name.authority ? ' ' + name.authority : '')
          + (type === 'common_name' && name.language_iso ? ' (' + name.language_iso + ')' : '');
        if (type === 'common_name' && name.is_default === 't') {
          cell.appendChild(document.createTextNode(' [Default]'));
        }
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-xs btn-danger pull-right';
        remove.textContent = 'Remove';
        remove.addEventListener('click', function () {
          pendingNames[type].splice(index, 1);
          renderPendingNames(type);
        });
        cell.appendChild(remove);
        row.appendChild(cell);
        grid.appendChild(row);
      });
    }

    document.querySelectorAll('.add-pending-related-name').forEach(function (button) {
      button.addEventListener('click', function () {
        var form = button.closest('form');
        var taxon = form.querySelector('[name="taxon"]');
        if (!taxon.value.trim()) {
          taxon.reportValidity();
          return;
        }
        var name = {};
        form.querySelectorAll('[name]').forEach(function (field) {
          if (['taxon_meaning_id', 'taxon_meaning_preferred_id', 'taxon_list_id', 'name_type'].indexOf(field.name) !== -1) {
            return;
          }
          if (field.type === 'radio' && !field.checked) {
            return;
          }
          name[field.name] = field.type === 'checkbox' ? (field.checked ? 't' : 'f') : field.value.trim();
        });
        pendingNames[form.dataset.nameType].push(name);
        if (form.dataset.nameType === 'common_name' && name.is_default === 't') {
          pendingNames.common_name.forEach(function (pendingName) {
            if (pendingName !== name) {
              pendingName.is_default = 'f';
            }
          });
        }
        renderPendingNames(form.dataset.nameType);
        form.reset();
        form.removeAttribute('data-related-name-dirty');
      });
    });

    document.getElementById('entry_form').addEventListener('submit', function () {
      document.getElementById('pending-synonyms').value = JSON.stringify(pendingNames.synonym);
      document.getElementById('pending-common-names').value = JSON.stringify(pendingNames.common_name);
    });
  }());
  <?php endif; ?>
  document.querySelectorAll('#taxon-attributes [name]').forEach(function (control) {
    control.setAttribute('form', 'entry_form');
  });
  document.querySelectorAll('#taxon-form-actions [name]').forEach(function (control) {
    control.setAttribute('form', 'entry_form');
  });
</script>
