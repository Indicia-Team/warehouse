<?php

/**
 * @file
 * View template for the survey dataset edit form.
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
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
?>
<p>This page allows you to specify the details of a survey in which samples and records can be organised.</p>
<form action="<?php echo url::site() . 'survey/save'; ?>" method="post" id="entry_form">
  <fieldset>
    <legend>Survey dataset details<?php echo $metadata ?></legend>
    <?php
    echo data_entry_helper::hidden_text([
      'fieldname' => 'survey:id',
      'default' => html::initial_value($values, 'survey:id'),
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Title',
      'fieldname' => 'survey:title',
      'default' => html::initial_value($values, 'survey:title'),
      'validation' => 'required',
      'helpText' => 'Provide a title for your survey dataset',
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Description',
      'fieldname' => 'survey:description',
      'default' => html::initial_value($values, 'survey:description'),
      'validation' => 'required',
      'helpText' => 'Provide an optional description of your survey to help when browsing survey datasets on the warehouse',
    ]);
    ?>
    <fieldset>
      <legend>Enforce required fields</legend>
      <p>
        Certain fields are provided by Indicia for every sample and record added to the database. Tick the fields
        below for fields which you would like to ensure are always populated when records are submitted to this survey
        dataset.
      </p>
      <?php
      echo data_entry_helper::checkbox([
        'label' => 'Occurrence comment',
        'fieldname' => 'occurrence-comment-required',
        'default' => html::initial_value($values, 'occurrence-comment-required'),
        'helpText' => 'Is a record comment required when saving an occurrence?',
      ]);
      echo data_entry_helper::checkbox([
        'label' => 'Occurrence sensitivity precision',
        'fieldname' => 'occurrence-sensitivity_precision-required',
        'default' => html::initial_value($values, 'occurrence-sensitivity_precision-required'),
        'helpText' => 'Is an sensitivity precision (blur) required when saving an occurrence? This enforces that records will be sensitive for this survey dataset.',
      ]);
      echo data_entry_helper::checkbox([
        'label' => 'Sample comment',
        'fieldname' => 'sample-comment-required',
        'default' => html::initial_value($values, 'sample-comment-required'),
        'helpText' => 'Is an overall comment required when saving a sample?',
      ]);
      echo data_entry_helper::checkbox([
        'label' => 'Sample licence',
        'fieldname' => 'sample-licence_id-required',
        'default' => html::initial_value($values, 'sample-licence_id-required'),
        'helpText' => 'Is an explicit record licence (e.g. CC0) required when saving a sample?',
      ]);
      echo data_entry_helper::checkbox([
        'label' => 'Sample location ID',
        'fieldname' => 'sample-location_id-required',
        'default' => html::initial_value($values, 'sample-location_id-required'),
        'helpText' => 'Is a link to a location record required when saving a sample?',
      ]);
      echo data_entry_helper::checkbox([
        'label' => 'Sample location name',
        'fieldname' => 'sample-location_name-required',
        'default' => html::initial_value($values, 'sample-location_name-required'),
        'helpText' => 'Is a location name required when saving a sample?',
      ]);
      echo data_entry_helper::checkbox([
        'label' => 'Sample recorder names',
        'fieldname' => 'sample-recorder_names-required',
        'default' => html::initial_value($values, 'sample-recorder_names-required'),
        'helpText' => 'Is the recorder names field value required when saving a sample?',
      ]);
      ?>
    </fieldset>
    <?php
    echo data_entry_helper::autocomplete([
      'label' => 'Parent survey',
      'fieldname' => 'survey:parent_id',
      'table' => 'survey',
      'captionField' => 'title',
      'valueField' => 'id',
      'extraParams' => $readAuth,
      'default' => html::initial_value($values, 'survey:parent_id'),
      'defaultCaption' => html::initial_value($values, 'parent:title'),
      'helpText' => 'Set a parent for your survey to allow grouping of survey datasets in reports',
    ]);
    echo data_entry_helper::select([
      'label' => 'Website',
      'fieldname' => 'survey:website_id',
      'default' => html::initial_value($values, 'survey:website_id'),
      'lookupValues' => $other_data['websites'],
      'helpText' => 'The survey must belong to a website registration',
    ]);
    // Only show fields, if fields have been found in the database (the auto
    // verify module is installed).
    if (array_key_exists('survey:auto_accept', $values)) {
      echo data_entry_helper::checkbox([
        'label' => 'Auto Accept',
        'fieldname' => 'survey:auto_accept',
        'default' => html::initial_value($values, 'survey:auto_accept'),
        'helpText' => 'Should the automatic verification module attempt to auto verify records in this survey?',
      ]);
    }
    if (array_key_exists('survey:auto_accept_max_difficulty', $values)) {
      echo data_entry_helper::text_input([
        'label' => 'Auto Accept Maximum Difficulty',
        'fieldname' => 'survey:auto_accept_max_difficulty',
        'default' => html::initial_value($values, 'survey:auto_accept_max_difficulty'),
        'helpText' => 'If Auto Accept is set, then this is the minimum identification difficulty that will be auto verified.',
      ]);
    }
    if (array_key_exists('survey:auto_accept_taxa_filters', $values)) {
      $masterListId = warehouse::getMasterTaxonListId();
      if ($masterListId) {
        echo <<<HTML
<div class="alert alert-info">
 <p>You can use the taxon selection control below to
 select one or more higher level taxa to which recorded taxa must belong in order to
 quality for auto-verification. Leave the list empty for no filtering. You must also
 check the Auto Accept box for these filters to take effect.</p>
</div>
<label>Taxon restrictions</label>
<input type="hidden" name="has-taxon-restriction-data" value="1" />
HTML;
        require_once 'client_helpers/prebuilt_forms/includes/language_utils.php';
        $speciesChecklistOptions = [
          'lookupListId' => $masterListId,
          'rowInclusionCheck' => 'alwaysRemovable',
          'extraParams' => $readAuth,
          'survey_id' => $values['survey:id'],
          'language' => iform_lang_iso_639_2(kohana::config('indicia.default_lang')),
          'occAttrs' => [],
        ];
        if (!empty($other_data['taxon_restrictions'])) {
          $speciesChecklistOptions['listId'] = $masterListId;
          $speciesChecklistOptions['preloadTaxa'] = [];
          foreach ($other_data['taxon_restrictions'] as $restriction) {
            $speciesChecklistOptions['preloadTaxa'][] = $restriction['taxa_taxon_list_id'];
          }
        }
        echo data_entry_helper::species_checklist($speciesChecklistOptions);
        echo '<br/>';
      }
    }
    ?>
  </fieldset>
  <?php if (array_key_exists('attributes', $values) && count($values['attributes']) > 0) : ?>
  <fieldset>
    <legend>Custom attributes</legend>
    <?php
    foreach ($values['attributes'] as $attr) {
      $name = 'srvAttr:' . $attr['survey_attribute_id'];
      // If this is an existing attribute, tag it with the attribute value
      // record id so we can re-save it.
      if ($attr['id']) {
        $name .= ':' . $attr['id'];
      }
      switch ($attr['data_type']) {
        case 'D':
          echo data_entry_helper::date_picker([
            'label' => $attr['caption'],
            'fieldname' => $name,
            'default' => $attr['value'],
          ]);
          break;

        case 'V':
          echo data_entry_helper::date_picker([
            'label' => $attr['caption'],
            'fieldname' => $name,
            'default' => $attr['value'],
            'allowVagueDates' => TRUE,
          ]);
          break;

        case 'L':
          echo data_entry_helper::select([
            'label' => $attr['caption'],
            'fieldname' => $name,
            'default' => $attr['raw_value'],
            'lookupValues' => $values['terms_' . $attr['termlist_id']],
            'blankText' => '<Please select>',
          ]);
          break;

        case 'B':
          echo data_entry_helper::checkbox([
            'label' => $attr['caption'],
            'fieldname' => $name,
            'default' => $attr['value'],
          ]);
          break;

        default:
          echo data_entry_helper::text_input([
            'label' => $attr['caption'],
            'fieldname' => $name,
            'default' => $attr['value'],
          ]);
      }
    }
    ?>
  </fieldset>
  <?php
  endif;
  echo html::form_buttons(html::initial_value($values, 'survey:id') !== NULL);
  data_entry_helper::enable_validation('entry_form');
  data_entry_helper::$javascript .= <<<JS
// ensure the parent lookup does not allow an inappropriate survey to be selected (i.e. self or wrong website)
function setParentFilter() {
  var filter={"query":{}};
  filter.query.notin=['id', [1]];
  filter.query.where=['website_id', $('#survey\\:website_id').val()];
  filter.query=JSON.stringify(filter.query);
  $('#survey\\:parent_id\\:title').setExtraParams(filter);
}
$('#survey\\:website_id').change(function() {
  $('#survey\\:parent_id\\:title').val('');
  $('#survey\\:parent_id').val('');
  setParentFilter();
});
setParentFilter();

JS;
  echo data_entry_helper::dump_javascript();
  ?>
</form>
