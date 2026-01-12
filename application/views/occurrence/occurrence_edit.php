<?php

/**
 * @file
 * View template for the occurrence edit form.
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
 * @link https://github.com/indicia-team/warehouse
 */

require_once 'application/views/multi_value_data_editing_support.php';
warehouse::loadHelpers(['data_entry_helper']);
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
$id = html::initial_value($values, 'occurrence:id');
$sample = $model->sample;
$website_id = $sample->survey->website_id;
?>
<script type="text/javascript" >
$(document).ready(function() {
  jQuery('.vague-date-picker').datepicker({dateFormat : '<?php echo kohana::lang('dates.format_js'); ?>', constrainInput: false});
  jQuery('.date-picker').datepicker({dateFormat : '<?php echo kohana::lang('dates.format_js'); ?>', constrainInput: false});
});
</script>
<form action="<?php echo url::site() . 'occurrence/save' ?>" method="post" id="entry_form">
  <fieldset class="readonly">
    <legend>Sample summary<?php echo $metadata; ?></legend>
    <label>Sample link:</label>
    <a href="<?php echo url::site(); ?>sample/edit/<?php echo $sample->id; ?>">ID <?php echo $sample->id;?></a><br/>
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Survey dataset',
      'fieldname' => 'sample-survey',
      'default' => $sample->survey->title,
      'readonly' => TRUE,
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Date',
      'fieldname' => 'sample-date',
      'default' => $sample->date,
      'readonly' => TRUE,
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Spatial reference',
      'fieldname' => 'sample-entered-sref',
      'default' => $sample->entered_sref,
      'readonly' => TRUE,
    ]);
    $licence = $sample->licence;
    if (!empty($licence->code)) {
      echo data_entry_helper::text_input([
        'label' => 'Licence',
        'fieldname' => 'sample-licence-code',
        'default' => $licence->code,
        'readonly' => TRUE,
      ]);
      if (!empty($licence->url_readable)) {
        echo "<a href=\"$licence->url_readable\" target=\"_blank\">more info</a> ";
      }
      if (!empty($sample->licence->url_legal)) {
        echo "<a href=\"$licence->url_legal\" target=\"_blank\">legal</a> ";
      }
    }
    ?>
  </fieldset>
  <fieldset>
    <legend>Occurrence details</legend>
    <?php
    print form::hidden('occurrence:id', $id);
    print form::hidden('occurrence:website_id', html::initial_value($values, 'occurrence:website_id'));
    print form::hidden('occurrence:sample_id', html::initial_value($values, 'occurrence:sample_id'));
    echo data_entry_helper::species_autocomplete([
      'label' => 'Taxon',
      'fieldname' => 'occurrence:taxa_taxon_list_id',
      'default' => html::initial_value($values, 'occurrence:taxa_taxon_list_id'),
      'defaultCaption' => $model->taxa_taxon_list->taxon->taxon,
      'extraParams' => $readAuth + [
        'taxon_list_id' => $model->taxa_taxon_list->taxon_list_id,
      ],
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeAuthorities' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Record comment',
      'fieldname' => 'occurrence:comment',
      'default' => html::initial_value($values, 'occurrence:comment'),
    ]);
    $defaultDeterminer = '';
    if ($model->determiner_id) {
      $defaultDeterminer = $model->determiner->first_name . ' ' . $model->determiner->surname;
    }
    echo data_entry_helper::autocomplete([
      'label' => 'Determiner',
      'fieldname' => 'occurrence:determiner_id',
      'report' => 'library/people/people_names_lookup',
      'captionField' => 'person_name',
      'valueField' => 'id',
      'extraParams' => $readAuth,
      'default' => html::initial_value($values, 'occurrence:determiner_id'),
      'defaultCaption' => $defaultDeterminer,
    ]);
    echo data_entry_helper::select([
      'label' => 'Sensitivity precision',
      'fieldname' => 'occurrence:sensitivity_precision',
      'default' => html::initial_value($values, 'occurrence:sensitivity_precision'),
      'lookupValues' => [
        '100' => '100m',
        '1000' => '1km',
        '2000' => '2km',
        '10000' => '10km',
        '100000' => '100km',
      ],
      'blankText' => '<not sensitive>',
      'helpText' => 'Sensitive records may be blurred to an appropriate precision. They are publically viewable in ' .
        'their blurred form.',
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Confidential',
      'fieldname' => 'occurrence:confidential',
      'default' => html::initial_value($values, 'occurrence:confidential'),
      'helpText' => 'Confidential records are completely blocked from public facing reports.',
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Zero abundance',
      'fieldname' => 'occurrence:zero_abundance',
      'default' => html::initial_value($values, 'occurrence:zero_abundance'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Training',
      'fieldname' => 'occurrence:training',
      'default' => html::initial_value($values, 'occurrence:training'),
      'helpText' => 'Tick if a fake record for training purposes',
    ]);
    echo data_entry_helper::text_input([
      'label' => 'External key',
      'fieldname' => 'occurrence:external_key',
      'default' => html::initial_value($values, 'occurrence:external_key'),
      'helpText' => 'Key from external system which this record was sourced from, if relevant.',
    ]);
    echo data_entry_helper::select([
      'label' => 'Record status',
      'fieldname' => 'occurrence:record_status:combined',
      'lookupValues' => [
        'V' => 'Accepted',
        'V1' => 'Accepted as correct',
        'V2' => 'Accepted as considered correct',
        'C' => 'Pending review',
        'C3' => 'Plausible',
        'R' => 'Not accepted',
        'R4' => 'Not accepted as unable to verify',
        'R5' => 'Not accepted as incorrect',
        'D' => 'Dubious',
        'T' => 'Test',
        'I' => 'Incomplete',
      ],
      'default' =>
        html::initial_value($values, 'occurrence:record_status') .
        html::initial_value($values, 'occurrence:record_substatus'),
    ]);
    echo data_entry_helper::select([
      'label' => 'Release status',
      'fieldname' => 'occurrence:release_status',
      'lookupValues' => [
        'R' => 'Released',
        'P' => 'Recorder has requested a precheck before release',
        'U' => 'Record is part of a project that has not yet released its records',
      ],
      'default' => html::initial_value($values, 'occurrence:release_status'),
    ]);
    if (html::initial_value($values, 'occurrence:record_status') == 'V') {
      echo 'Verified on ' . html::initial_value($values, 'occurrence:verified_on') .
        ' by ' . $model->verified_by->username;
    }
    echo data_entry_helper::select([
      'label' => 'Download status',
      'fieldname' => 'occurrence:downloaded_flag',
      'lookupValues' => [
        'N' => 'Not downloaded',
        'I' => 'Trial downloaded',
        'F' => 'Downloaded - read only',
      ],
      'default' => html::initial_value($values, 'occurrence:downloaded_flag'),
      'helpText' => 'Flag used to track when records have been transferred elsewhere so this is no longer the top copy.',
    ]);
    if (html::initial_value($values, 'occurrence:downloaded_flag') !== 'N'
      && !empty($values['occurrence:downloaded_on'])) {
      echo 'Downloaded on ' . html::initial_value($values, 'occurrence:downloaded_on');
    }
    echo data_entry_helper::textarea([
      'label' => 'Metadata',
      'fieldname' => 'occurrence:metadata',
      'default' => html::initial_value($values, 'occurrence:metadata'),
      'helpText' => 'JSON format additional metadata for this record.',
    ]);
    ?>
  </fieldset>
  <fieldset>
  <legend>Survey specific attributes</legend>
    <ol>
      <?php
      // The $values['attributes'] array has multi-value attributes on separate rows, so organise these into sub array
      $attrsWithMulti = organise_values_attribute_array('occurrence_attribute', $values['attributes']);
      // Cycle through the attributes and drawn them to the screen
      foreach ($attrsWithMulti as $occurrenceAttributeId => $wholeAttrToDraw) {
        // Multi-attributes are in a sub array, so the caption is not present at the first level so we can detect this
        if (!empty($wholeAttrToDraw['caption'])) {
          handle_single_value_attributes('occAttr', $occurrenceAttributeId, $wholeAttrToDraw, $values);
        } else {
          handle_multi_value_attributes('occAttr', $occurrenceAttributeId, $wholeAttrToDraw, $values);
        }
      }
      ?>
    </ol>
  </fieldset>
  <?php
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
