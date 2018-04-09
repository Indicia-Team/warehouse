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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

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
<form action="<?php echo url::site() . 'occurrence/save' ?>" method="post" id="occurrence-edit">
  <fieldset class="readonly">
    <legend>Sample summary<?php echo $metadata; ?></legend>
    <label>Sample link:</label>
    <a href="<?php echo url::site(); ?>sample/edit/<?php echo $sample->id; ?>">ID <?php echo $sample->id;?></a><br/>
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Survey',
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
    <legend>Occurrence Details</legend>
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
      if (!empty($model->determiner->first_name)) {
        $defaultDeterminer = $model->determiner->first_name . ' ' . $model->determiner->last_name;
      }
      else {
        $defaultDeterminer = $model->determiner->last_name;
      }
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
    echo data_entry_helper::checkbox([
      'label' => 'Confidential',
      'fieldname' => 'occurrence:confidential',
      'default' => html::initial_value($values, 'occurrence:confidential'),
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
    echo 'Setting plausible not working';
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
    ?>

<li>
<label for='occurrence:release_status'>Release Status:</label>
<?php
print form::dropdown('occurrence:release_status', array('R' => 'Released', 'P' => 'Recorder has requested a precheck before release',
    'U'=>'Record is part of a project that has not yet released its records'),
    html::initial_value($values, 'occurrence:release_status'));
echo html::error_message($model->getError('occurrence:release_status'));
?>
</li>
<?php if (html::initial_value($values, 'occurrence:record_status') == 'V'): ?>
<li>
Verified on <?php echo html::initial_value($values, 'occurrence:verified_on') ?> by <?php echo $model->verified_by->username; ?>
</li>
<?php endif; ?>
<li>
<label for='occurrence:downloaded_flag'>Download Status:</label>
<?php
print form::dropdown('occurrence:downloaded_flag', array('N' => 'Not Downloaded', 'I' => 'Trial Downloaded', 'F' => 'Downloaded - Read Only'),
    html::initial_value($values, 'occurrence:downloaded_flag'), 'disabled="disabled"');
echo html::error_message($model->getError('occurrence:downloaded_flag'));
?>
</li>
<?php if (html::initial_value($values, 'occurrence:downloaded_flag') == 'I' || html::initial_value($values, 'occurrence:downloaded_flag') == 'F'): ?>
<li>
Downloaded on <?php echo html::initial_value($values, 'occurrence:downloaded_on') ?>
</li>
<?php endif; ?>
<li>
<label for='occurrence:metadata'>Metadata:</label>
<?php
print form::textarea('occurrence:metadata', empty($values['occurrence:metadata']) ? NULL : $values['occurrence:metadata']);
echo html::error_message($model->getError('occurrence:metadata'));
?>
</li>
</ol>
</fieldset>
<fieldset>
 <legend>Survey Specific Attributes</legend>
 <ol>
 <?php
foreach ($values['attributes'] as $attr) {
  $name = 'occAttr:'.$attr['occurrence_attribute_id'];
  // if this is an existing attribute, tag it with the attribute value record id so we can re-save it
  if ($attr['id']) $name .= ':'.$attr['id'];
  echo '<li><label for="">'.$attr['caption']."</label>\n";
  switch ($attr['data_type']) {
    case 'D':
      echo form::input($name, $attr['value'], 'class="date-picker"');
      break;
    case 'V':
      echo form::input($name, $attr['value'], 'class="vague-date-picker"');
      break;
    case 'L':
      echo form::dropdown($name, $values['terms_'.$attr['termlist_id']], $attr['raw_value']);
      break;
    case 'B':
      echo form::dropdown($name, array(''=>'','0'=>'false','1'=>'true'), $attr['value']);
      break;
    default:
      echo form::input($name, $attr['value']);
  }
  echo '<br/>'.html::error_message($model->getError($name)).'</li>';

}
 ?>
 </ol>
  </fieldset>

  <?php
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('occurrence-edit');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
