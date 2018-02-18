<?php

/**
 * @file
 * Generic edit view template for custom attributes.
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

global $indicia_templates;
warehouse::loadHelpers(['data_entry_helper']);
$id = html::initial_value($values, "$model->object_name:id");
$disabled_input = html::initial_value($values, 'metaFields:disabled_input');
$enabled = ($disabled_input === 'YES') ? 'disabled="disabled"' : '';
?>
<?php if ($disabled_input === 'YES') : ?>
<div class="alert alert-warning">The attribute was created by another user so you don't have permission to change the
attribute's specification, although you can change the attribute assignments at the bottom of the page. Please contact
the warehouse owner to request changes.</div>
<?php else : ?>
<div class="alert alert-info">This page allows you to specify a new or edit an existing custom attribute for
<?php echo strtolower($other_data['name']); ?> data.</div>
<?php endif; ?>
<form id="custom-attribute-edit"
      action="<?php echo url::site() . "$other_data[controllerpath]/save"; ?>"
      method="post"><input type="hidden" name="<?php echo $model->object_name; ?>:id"
      value="<?php echo $id; ?>" />
  <input type="hidden" name="metaFields:disabled_input" value="<?php echo $disabled_input; ?>" />
  <fieldset<?php echo $disabled_input === 'YES' ? ' class="ui-state-disabled"' : ''; ?>>
    <legend><?php echo $other_data['name']; ?> attribute details<?php echo $metadata; ?></legend>
    <?php
    echo data_entry_helper::text_input([
      'fieldname' => "$model->object_name:caption",
      'label' => 'Caption',
      'default' => html::initial_value($values, "$model->object_name:caption"),
      'validation' => ['required'],
      'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
    ]);
    if (array_key_exists('description', $this->model->as_array())) {
      echo data_entry_helper::textarea([
        'fieldname' => "$model->object_name:description",
        'label' => 'Description',
        'default' => html::initial_value($values, "$model->object_name:description"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
      ]);
    }
    if (method_exists($this->model, 'get_system_functions')) {
      $options = [];
      $hints = [];
      foreach ($this->model->get_system_functions() as $function => $def) {
        $options[$function] = $def['title'];
        $hints[$def['title']] = $def['description'];
      }
      $indicia_templates['sys_func_item'] = '<option value="{value}" {selected} {title}>{caption}</option>';
      echo data_entry_helper::select([
        'fieldname' => "$model->object_name:system_function",
        'label' => 'System function',
        'default' => html::initial_value($values, "$model->object_name:system_function"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'blankText' => '-none-',
        'lookupValues' => $options,
        'optionHints' => $hints,
        'itemTemplate' => 'sys_func_item',
      ]);
    }
    if (array_key_exists('source_id', $this->model->as_array()) && !empty($other_data['source_terms'])) {
      echo data_entry_helper::select([
        'fieldname' => "$model->object_name:source_id",
        'label' => 'Source of attribute',
        'default' => html::initial_value($values, "$model->object_name:source_id"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'blankText' => '-none-',
        'lookupValues' => $other_data['source_terms'],
      ]);
    }
    echo data_entry_helper::select([
      'fieldname' => "$model->object_name:data_type",
      'id' => 'data_type',
      'label' => 'Data type',
      'default' => html::initial_value($values, "$model->object_name:data_type"),
      'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
      'lookupValues' => [
        'T' => 'Text',
        'L' => 'Lookup List',
        'I' => 'Integer',
        'F' => 'Float',
        'D' => 'Specific Date',
        'V' => 'Vague Date',
        'B' => 'Boolean',
      ],
      'validation' => ['required'],
    ]);
    echo "<div id=\"quick-termlist\" style=\"display: none;\">\n";
    echo data_entry_helper::checkbox([
      'fieldname' => 'metaFields:quick_termlist_create',
      'id' => 'quick_termlist_create',
      'label' => 'Create a new termlist',
      'helpText' => 'Tick this box to create a new termlist with the same name as this attribute and populate it with a provided list of terms.',
    ]);
    echo "<div id=\"quick-termlist-terms\" style=\"display: none;\">\n";
    echo data_entry_helper::textarea([
      'fieldname' => 'metaFields:quick_termlist_terms',
      'label' => 'Terms',
      'helpText' => 'Enter terms into this box, one per line. A termlist with the same name as the attribute will be created and populated with this list of terms in the order provided.',
    ]);
    echo '</div>';
    echo '</div>';
    echo data_entry_helper::select([
      'fieldname' => "$model->object_name:termlist_id",
      'id' => 'termlist_id',
      'label' => 'Termlist',
      'default' => html::initial_value($values, "$model->object_name:termlist_id"),
      'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
      'blankText' => '<Please select>',
      'lookupValues' => $other_data['termlists'],
    ]);
    echo '<a id="termlist-link" target="_blank" href="">edit in new tab</a>';
    echo data_entry_helper::checkbox([
      'fieldname' => "$model->object_name:multi_value",
      'label' => 'Allow multiple values',
      'default' => html::initial_value($values, "$model->object_name:multi_value"),
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => "$model->object_name:public",
      'label' => $other_data['publicFieldName'],
      'default' => html::initial_value($values,"$model->object_name:public"),
    ]);
    if ($model->object_name === 'sample_attribute') {
      echo data_entry_helper::checkbox([
        'fieldname' => "$model->object_name:applies_to_location",
        'label' => 'Applies to location',
        'default' => html::initial_value($values, "$model->object_name:applies_to_location"),
        'helpText' => 'Tick this box for attributes which describe something inherent to the site/location itself',
      ]);
    }
    elseif ($model->object_name === 'person_attribute') {
      echo data_entry_helper::checkbox([
        'fieldname' => "$model->object_name:synchronisable",
        'label' => 'Synchronisable with client website user profiles',
        'default' => html::initial_value($values, "$model->object_name:synchronisable"),
        'helpText' => 'Tick this box for attributes which can be linked to a user account profile on a client site.',
      ]);
    }
    ?>
  </fieldset>
  <fieldset <?php echo $disabled_input === 'YES' ? ' class="ui-state-disabled"' : ''; ?>>
  <legend>Validation Rules</legend>
<ol>
  <li id="li_valid_required">
    <label class="narrow" for="valid_required">Required</label>
    <?php echo form::checkbox('valid_required', TRUE, isset($model->valid_required) && ($model->valid_required == 't'), 'class="vnarrow" ' . $enabled); ?>
    <p class="helpText">Note, checking this option will make the attribute GLOBALLY required for all surveys which use it.
      Consider making it required on a survey dataset basis instead.</p>
  </li>
  <li id="li_valid_length"><label class="narrow" for="valid_length">Length</label><?php echo form::checkbox('valid_length', TRUE, isset($model->valid_length) && ($model->valid_length == 't'), 'class="vnarrow" ' . $enabled); ?>
    Between <input class="narrow" id="valid_length_min" name="valid_length_min"
    value="<?php echo html::specialchars($model->valid_length_min); ?>"
    <?php echo $enabled?> /> and <input class="narrow"
    id="valid_length_max" name="valid_length_max"
    value="<?php echo html::specialchars($model->valid_length_max); ?>"
    <?php echo $enabled?> /> <?php echo html::error_message($model->getError('valid_length')); ?>
  </li>
  <li id="li_valid_alpha"><label class="narrow" for="valid_alpha">Alphabetic</label><?php echo form::checkbox('valid_alpha', TRUE, isset($model->valid_alpha) && ($model->valid_alpha == 't'), 'class="vnarrow" ' . $enabled); ?></li>
  <li id="li_valid_email"><label class="narrow" for="valid_email">Email
  Address</label><?php echo form::checkbox('valid_email', TRUE, isset($model->valid_email) && ($model->valid_email == 't'), 'class="vnarrow" ' . $enabled); ?></li>
  <li id="li_valid_url"><label class="narrow" for="valid_url">URL</label><?php echo form::checkbox('valid_url', TRUE, isset($model->valid_url) && ($model->valid_url == 't'), 'class="vnarrow" ' . $enabled); ?></li>
  <li id="li_valid_alpha_numeric"><label class="narrow"
    for="valid_alpha_numeric">Alphanumeric</label><?php echo form::checkbox('valid_alpha_numeric', TRUE, isset($model->valid_alpha_numeric) && ($model->valid_alpha_numeric == 't'), 'class="vnarrow" ' . $enabled); ?></li>
  <li id="li_valid_numeric"><label class="narrow" for="valid_numeric">Numeric</label><?php echo form::checkbox('valid_numeric', TRUE, isset($model->valid_numeric) && ($model->valid_numeric == 't'), 'class="vnarrow" ' . $enabled); ?></li>
  <li id="li_valid_digit"><label class="narrow" for="valid_digit">Digits Only</label><?php echo form::checkbox('valid_digit', TRUE, isset($model->valid_digit) && ($model->valid_digit == 't'), 'class="vnarrow" ' . $enabled); ?></li>
  <li id="li_valid_integer"><label class="narrow" for="valid_integer">Integer</label><?php echo form::checkbox('valid_integer', TRUE, isset($model->valid_integer) && ($model->valid_integer == 't'), 'class="vnarrow" ' . $enabled); ?></li>
  <li id="li_valid_standard_text"><label class="narrow"
    for="valid_standard_text">Standard Text</label><?php echo form::checkbox('valid_standard_text', TRUE, isset($model->valid_standard_text) && ($model->valid_standard_text == 't'), 'class="vnarrow" ' . $enabled); ?></li>
  <li id="li_valid_decimal"><label class="narrow" for="valid_decimal">Formatted
  Decimal</label><?php echo form::checkbox('valid_decimal', TRUE, isset($model->valid_decimal) && ($model->valid_decimal == 't'), 'class="vnarrow" ' . $enabled); ?><input
    class="narrow" id="valid_dec_format" name="valid_dec_format"
    value="<?php echo html::specialchars($model->valid_dec_format); ?>"
    <?php echo $enabled?> /> <?php echo html::error_message($model->getError('valid_decimal')); ?>
  </li>
  <li id="li_valid_regex"><label class="narrow" for="valid_regex">Regular
  Expression</label><?php echo form::checkbox('valid_regex', TRUE, isset($model->valid_regex) && ($model->valid_regex == 't'), 'class="vnarrow" ' . $enabled); ?><input
    class="narrow" id="valid_regex_format" name="valid_regex_format"
    value="<?php echo html::specialchars($model->valid_regex_format); ?>"
    <?php echo $enabled?> /> <?php echo html::error_message($model->getError('valid_regex')); ?>
  </li>
  <li id="li_valid_min"><label class="narrow" for="valid_min">Minimum
  value</label><?php echo form::checkbox('valid_min', TRUE, isset($model->valid_min) && ($model->valid_min == 't'), 'class="vnarrow" ' . $enabled); ?><input
    class="narrow" id="valid_min_value" name="valid_min_value"
    value="<?php echo html::specialchars($model->valid_min_value); ?>"
    <?php echo $enabled?> /> <?php echo html::error_message($model->getError('valid_min')); ?>
  </li>
  <li id="li_valid_max"><label class="narrow" for="valid_max">Maximum
  value</label><?php echo form::checkbox('valid_max', TRUE, isset($model->valid_max) && ($model->valid_max == 't'), 'class="vnarrow" ' . $enabled); ?><input
    class="narrow" id="valid_max_value" name="valid_max_value"
    value="<?php echo html::specialchars($model->valid_max_value); ?>"
    <?php echo $enabled?> /> <?php echo html::error_message($model->getError('valid_max')); ?>
  </li>
  <li id="li_valid_date_in_past">
    <label class="narrow" for="valid_date_in_past">Date is in past</label>
    <?php
    echo form::checkbox('valid_date_in_past', TRUE, isset($model->valid_date_in_past) && ($model->valid_date_in_past == 't'), 'class="vnarrow" ' . $enabled);
    echo html::error_message($model->getError('valid_date_in_past'));
    ?>
  </li>
  <li id="li_valid_time"><label class="narrow" for="valid_integer">Time</label><?php echo form::checkbox('valid_time', TRUE, isset($model->valid_time) && ($model->valid_time == 't'), 'class="vnarrow" ' . $enabled); ?></li>
</ol>
</fieldset>
<?php
// Output the view that lets this custom attribute associate with websites,
// surveys, checklists or whatever is appropriate for the attribute type.
$this->associationsView->other_data = $other_data;
$this->associationsView->model = $model;
echo $this->associationsView;
echo html::form_buttons(!empty($id), FALSE, FALSE);
data_entry_helper::enable_validation('custom-attribute-edit');
echo data_entry_helper::dump_javascript();
?></form>

<script type="text/javascript">
$(document).ready(function() {
  $('#quick_termlist_create').change(function (e) {
    if ($(e.currentTarget).attr('checked')) {
      $('#quick-termlist-terms').show();
      $('#termlist-picker').hide();
    } else {
      $('#quick-termlist-terms').hide();
      $('#termlist-picker').show();
    }
  });
});
function showHideTermlistLink() {
  $("#termlist-link").attr('href', '<?php echo url::site() . 'termlist/edit/'; ?>'+$('#termlist_id').val());
  if ($('#termlist_id').val()!=='' && $('#data_type').val()==='L') {
    $("#termlist-link").show();
  } else {
    $("#termlist-link").hide();
  }
}

function toggleOptions() {
  var enable_list = [];
  var disable_list = [];
  var data_type = $('select#data_type').val();
  $('select#termlist_id').attr('disabled', 'disabled');
  $("#termlist-link").hide();
  $('#quick-termlist').hide();
  switch(data_type) {
    case "T": // text
      enable_list = ['valid_required','valid_length','valid_length_min','valid_length_max','valid_alpha','valid_email','valid_url','valid_alpha_numeric','valid_numeric','valid_standard_text','valid_decimal','valid_dec_format','valid_regex','valid_regex_format','valid_time'];
      disable_list = ['valid_digit','valid_integer','valid_min','valid_min_value','valid_max','valid_max_value','valid_date_in_past'];
      break;
    case "L": // Lookup List
      $('select#termlist_id').removeAttr('disabled');
      enable_list = ['valid_required'];
      disable_list = ['valid_length','valid_length_min','valid_length_max','valid_alpha','valid_email','valid_url','valid_alpha_numeric','valid_numeric','valid_digit','valid_integer','valid_standard_text','valid_decimal','valid_dec_format','valid_regex','valid_regex_format','valid_min','valid_min_value','valid_max','valid_max_value','valid_date_in_past','valid_time'];
<?php if (!html::initial_value($values, $model->object_name . ':id')) : ?>
      $('#quick-termlist').show();
<?php endif; ?>
      break;
    case "I": // Integer
        enable_list = ['valid_required','valid_digit','valid_integer','valid_decimal','valid_dec_format','valid_regex','valid_regex_format','valid_min','valid_min_value','valid_max','valid_max_value'];
        disable_list = ['valid_numeric','valid_length','valid_length_min','valid_length_max','valid_alpha','valid_email','valid_url','valid_alpha_numeric','valid_standard_text','valid_date_in_past','valid_time'];
        break;
    case "F": // Float
      enable_list = ['valid_required','valid_numeric','valid_decimal','valid_dec_format','valid_regex','valid_regex_format','valid_min','valid_min_value','valid_max','valid_max_value'];
      disable_list = ['valid_digit','valid_integer','valid_length','valid_length_min','valid_length_max','valid_alpha','valid_email','valid_url','valid_alpha_numeric','valid_standard_text','valid_date_in_past','valid_time'];
      break;
    case "D": // Specific Date
    case "V": // Vague Date
      enable_list = ['valid_required','valid_min','valid_min_value','valid_max','valid_max_value','valid_date_in_past'];
      disable_list = ['valid_length','valid_length_min','valid_length_max','valid_alpha','valid_email','valid_url','valid_alpha_numeric','valid_numeric','valid_standard_text','valid_decimal','valid_dec_format','valid_regex','valid_regex_format','valid_digit','valid_integer','valid_time'];
      break;
    case "B": // Boolean
      enable_list = ['valid_required'];
      disable_list = ['valid_length','valid_length_min','valid_length_max','valid_alpha','valid_email','valid_url','valid_alpha_numeric','valid_numeric','valid_standard_text','valid_decimal','valid_dec_format','valid_regex','valid_regex_format','valid_min','valid_min_value','valid_max','valid_max_value','valid_date_in_past','valid_digit','valid_integer','valid_time'];
      break;
    default:
      disable_list = ['valid_required','valid_length','valid_length_min','valid_length_max','valid_alpha','valid_email','valid_url','valid_alpha_numeric','valid_numeric','valid_standard_text','valid_decimal','valid_dec_format','valid_regex','valid_regex_format','valid_min','valid_min_value','valid_max','valid_max_value','valid_date_in_past','valid_digit','valid_integer','valid_time'];
      break;
  };
  $.each(enable_list, function(i, item) {
    $('#li_'+item).show();
  });
  $.each(disable_list, function(i, item) {
    $('#li_'+item).hide();
  });
  showHideTermlistLink();
};
<?php
  if ($disabled_input == 'NO') {
?>
$(document).ready(function() {
  toggleOptions();
  $('select#data_type').change(toggleOptions);
  $('#termlist_id').change(function(e) {
    showHideTermlistLink();
  });
});
<?php
  }
?>
</script>