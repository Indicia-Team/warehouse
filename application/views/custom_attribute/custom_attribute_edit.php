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
 * along with this program. If not, see http://www.gnu.org/licenses/gpl.html.
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
<?php echo $metadata; ?>
<form id="custom-attribute-edit"
      enctype="multipart/form-data"
      action="<?php echo url::site() . "$other_data[controllerpath]/save"; ?>"
      method="post">
  <input type="hidden" name="<?php echo $model->object_name; ?>:id" value="<?php echo $id; ?>" />
  <input type="hidden" name="metaFields:disabled_input" value="<?php echo $disabled_input; ?>" />
  <fieldset<?php echo $disabled_input === 'YES' ? ' class="ui-state-disabled"' : ''; ?>>
    <legend><?php echo $other_data['name']; ?> attribute details</legend>
    <?php
    echo data_entry_helper::text_input([
      'fieldname' => "$model->object_name:caption",
      'label' => 'Caption',
      'default' => html::initial_value($values, "$model->object_name:caption"),
      'validation' => ['required'],
      'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
    ]);
    if (array_key_exists('caption_i18n', $this->model->as_array())) {
      $defaultLang = kohana::config('indicia.default_lang');
      $helpText = <<<TXT
If you need to localise the attribute caption into different languages for use in report outputs, specify the caption
above using language code $defaultLang and enter additional translations here. Enter one per line, followed by a pipe
(|) character then the ISO language code. E.g.<br/>
Compter|fra<br/>
Anzahl|deu<br/>
TXT;
      echo data_entry_helper::textarea([
        'fieldname' => "$model->object_name:caption_i18n",
        'label' => 'Caption in other languages',
        'default' => html::initial_value($values, "$model->object_name:caption_i18n"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'helpText' => $helpText,
      ]);
    }
    if (array_key_exists('unit', $this->model->as_array())) {
      echo data_entry_helper::text_input([
        'fieldname' => "$model->object_name:unit",
        'label' => 'Unit',
        'default' => html::initial_value($values, "$model->object_name:unit"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'helpText' => 'Specify the unit or unit abbreviation where appropriage (e.g. mm).',
      ]);
    }
    if (array_key_exists('term_name', $this->model->as_array())) {
      $helpText = <<<TXT
If the attribute is linked to a standardised glossary such as Darwin Core then provide the term name. Otherwise provide
a brief alphanumeric only (with no spaces) version of the attribute name to give it a unique identifier within the
context of the survey dataset to make it easier to refer to in configuration.
TXT;
      echo data_entry_helper::text_input([
        'fieldname' => "$model->object_name:term_name",
        'label' => 'Term name',
        'default' => html::initial_value($values, "$model->object_name:term_name"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'helpText' => $helpText,
      ]);
    }
    if (array_key_exists('term_identifier', $this->model->as_array())) {
      $helpText = <<<TXT
If the attribute is linked to a standardised glossary such as Darwin Core then provide the term identifier, typically
the URL to the term definition.
TXT;
      echo data_entry_helper::text_input([
        'fieldname' => "$model->object_name:term_identifier",
        'label' => 'Term identifier',
        'default' => html::initial_value($values, "$model->object_name:term_identifier"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'helpText' => $helpText,
      ]);
    }
    if (array_key_exists('description', $this->model->as_array())) {
      echo data_entry_helper::textarea([
        'fieldname' => "$model->object_name:description",
        'label' => 'Description',
        'default' => html::initial_value($values, "$model->object_name:description"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
      ]);
    }
    if (array_key_exists('description_i18n', $this->model->as_array())) {
      $defaultLang = kohana::config('indicia.default_lang');
      $helpText = <<<TXT
If you need to localise the attribute description into different languages for use in report outputs, specify the
description above using language code $defaultLang and enter additional translations here. Enter one per line, followed
by a pipe (|) character then the ISO language code. E.g.<br/>
Comte d'organismes|fra<br/>
Anzahl der Organismen|deu<br/>
TXT;
      echo data_entry_helper::textarea([
        'fieldname' => "$model->object_name:description_i18n",
        'label' => 'Description in other languages',
        'default' => html::initial_value($values, "$model->object_name:description_i18n"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'helpText' => $helpText,
      ]);
    }
    if (array_key_exists('image_path', $this->model->as_array())) {
      $helpText = <<<TXT
If an image is required to explain the attribute, select it here. The image can be displayed alongside the input control
on the data entry form.
TXT;
      echo data_entry_helper::image_upload(array(
        'fieldname' => "image_upload",
        'label' => 'Image',
        'helpText' => $helpText,
        'existingFilePreset' => 'med',
      ));
      if (html::initial_value($values, "$model->object_name:image_path")) {
        echo html::sized_image(html::initial_value($values, "$model->object_name:image_path")) . '</br>';
      }
      echo data_entry_helper::hidden_text([
        'fieldname' => "$model->object_name:image_path",
        'default' => html::initial_value($values, "$model->object_name:image_path"),
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
    if (array_key_exists('reporting_category_id', $this->model->as_array()) && !empty($other_data['reporting_category_terms'])) {
      echo data_entry_helper::select([
        'fieldname' => "$model->object_name:reporting_category_id",
        'label' => 'Attribute output reporting category',
        'helpText' => 'Group the attribute by this category in output reports',
        'default' => html::initial_value($values, "$model->object_name:reporting_category_id"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'blankText' => '-none-',
        'lookupValues' => $other_data['reporting_category_terms'],
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
    echo '<a id="termlist-link" target="_blank" href="">edit terms in new tab</a>';
    echo data_entry_helper::checkbox([
      'fieldname' => "$model->object_name:multi_value",
      'label' => 'Allow multiple values',
      'default' => html::initial_value($values, "$model->object_name:multi_value"),
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => "$model->object_name:allow_ranges",
      'label' => 'Allow ranges',
      'default' => html::initial_value($values, "$model->object_name:allow_ranges"),
      'helpText' => 'Allow a range to be specified as a value, e.g. 0.4 - 1.6',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => "$model->object_name:public",
      'label' => $other_data['publicFieldName'],
      'default' => html::initial_value($values, "$model->object_name:public"),
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
  <fieldset id="validation-rules"<?php echo $disabled_input === 'YES' ? ' class="ui-state-disabled"' : ''; ?>>
    <legend>Validation rules</legend>
    <?php
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_required',
      'label' => 'Required',
      'default' => $model->valid_required,
      'helpText' => 'Note, checking this option will make the attribute GLOBALLY required for all surveys which use it. ' .
        'Consider making it required on a survey dataset basis instead.',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_length',
      'label' => 'Length',
      'default' => $model->valid_length,
      'helpText' => 'Enforce the minimum and/or maximum length of a text value.',
    ]);
    $valMin = html::specialchars($model->valid_length_min);
    $valMax = html::specialchars($model->valid_length_max);
    echo <<<HTML
<div id="valid_length_inputs">
length between <input type="text" id="valid_length_min" name="valid_length_min" value="$valMin"/>
and <input type="text" id="valid_length_max" name="valid_length_max" value="$valMax"/> characters
</div>

HTML;
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_alpha',
      'label' => 'Alphabetic characters only',
      'default' => $model->valid_alpha,
      'helpText' => 'Enforce that any value provided consists of alphabetic characters only.',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_numeric',
      'label' => 'Numeric characters only',
      'default' => $model->valid_numeric,
      'helpText' => 'Enforce that any value provided consists of numeric characters only.',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_alpha_numeric',
      'label' => 'Alphanumeric characters only',
      'default' => $model->valid_alpha_numeric,
      'helpText' => 'Enforce that any value provided consists of alphabetic and numeric characters only.',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_digit',
      'label' => 'Digits only',
      'default' => $model->valid_digit,
      'helpText' => 'Enforce that any value provided consists of digits (0-9) only, with no decimal points or dashes.',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_integer',
      'label' => 'Integer',
      'default' => $model->valid_integer,
      'helpText' => 'Enforce that any value provided is a valid whole number. Consider using an integer data type instead of text.',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_standard_text',
      'label' => 'Standard text',
      'default' => $model->valid_standard_text,
      'helpText' => 'Enforce that any value provided is valid text (Letters, numbers, whitespace, dashes, full-stops and underscores are allowed..',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_email',
      'label' => 'Email address',
      'default' => $model->valid_email,
      'helpText' => 'Enforce that any value provided is a valid email address format.',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_url',
      'label' => 'URL',
      'default' => $model->valid_url,
      'helpText' => 'Enforce that any value provided is a valid URL format.',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_decimal',
      'label' => 'Formatted decimal',
      'default' => $model->valid_decimal,
      'helpText' => 'Validate a decimal format against the provided pattern, e.g. 2 (2 digits) or 2,2 (2 digits before and 2 digits after the decimal point).',
    ]);
    $val = html::specialchars($model->valid_dec_format);
    echo <<<HTML
<div id="valid_decimal_inputs">
Format <input type="text" id="valid_dec_format" name="valid_dec_format" value="$val"/>
</div>

HTML;
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_regex',
      'label' => 'Regular expression',
      'default' => $model->valid_regex,
      'helpText' => 'Validate the supplied value against a regular expression, e.g. /^(sunny|cloudy)$/',
    ]);
    $val = html::specialchars($model->valid_regex_format);
    echo <<<HTML
<div id="valid_regex_inputs">
<input type="text" id="valid_regex_format" name="valid_regex_format" value="$val"/>
</div>

HTML;
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_min',
      'label' => 'Minimum value',
      'default' => $model->valid_min,
      'helpText' => 'Ensure the value is at least the minimum that you specify',
    ]);
    $val = html::specialchars($model->valid_min_value);
    echo <<<HTML
<div id="valid_min_inputs">
Value must be at least <input type="text" id="valid_min_value" name="valid_min_value" value="$val"/>
</div>

HTML;
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_max',
      'label' => 'Maximum value',
      'default' => $model->valid_max,
      'helpText' => 'Ensure the value is at most the maximum that you specify',
    ]);
    $val = html::specialchars($model->valid_max_value);
    echo <<<HTML
<div id="valid_max_inputs">
Value must be at most <input type="text" id="valid_max_value" name="valid_max_value" value="$val"/>
</div>

HTML;
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_date_in_past',
      'label' => 'Date in past',
      'default' => $model->valid_date_in_past,
      'helpText' => 'Ensure that the date values provided are in the past.',
    ]);
    echo data_entry_helper::checkbox([
      'fieldname' => 'valid_time',
      'label' => 'Time',
      'default' => $model->valid_time,
      'helpText' => 'Ensure that the value provided is a valid time format.',
    ]);

    ?>
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
  ?>
</form>

<script type="text/javascript">
$(document).ready(function() {
  $('#quick_termlist_create').change(function (e) {
    if ($(e.currentTarget).is(':checked')) {
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
  var enable = [];
  var data_type = $('select#data_type').val();
  var allRules = [
    'required',
    'length',
    'alpha',
    'alpha_numeric',
    'numeric',
    'email',
    'url',
    'digit',
    'integer',
    'standard_text',
    'decimal',
    'regex',
    'min',
    'max',
    'date_in_past',
    'time'
  ];
  $('select#termlist_id').attr('disabled', 'disabled');
  $("#termlist-link").hide();
  $('#quick-termlist').hide();

  switch(data_type) {
    case "T": // text
      enable = [
        'required',
        'length',
        'alpha',
        'email',
        'url',
        'alpha_numeric',
        'numeric',
        'standard_text',
        'decimal',
        'regex',
        'time'
      ];
      break;
    case "L": // Lookup List
      $('select#termlist_id').removeAttr('disabled');
      enable = [
        'required'
      ];
      <?php if (empty($id)) : ?>
      $('#quick-termlist').show();
      <?php endif; ?>
      break;
    case "I": // Integer
      enable = [
        'required',
        'digit',
        'decimal',
        'regex',
        'min',
        'max'
      ];
      break;
    case "F": // Float
      enable = [
        'required',
        'numeric',
        'decimal',
        'regex',
        'min',
        'max'
      ];
      break;
    case "D": // Specific Date
    case "V": // Vague Date
      enable = [
        'required',
        'min',
        'max',
        'date_in_past'
      ];
      break;
    case "B": // Boolean
      enable = [
        'required'
      ];
      break;
    default:
      enable = [];
      break;
  };
  $.each(allRules, function(i, item) {
    if ($.inArray(item, enable) === -1) {
      $('#ctrl-wrap-valid_' + item).hide();
    } else {
      $('#ctrl-wrap-valid_' + item).show();
    }
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
  // Changing a checkbox for a validation rule may need to show or hide the
  // related inputs.
  $('#validation-rules :checkbox').change(function(evt) {
    var selector = '#' + evt.currentTarget.id + '_inputs';
    if ($(selector).length>0) {
      if ($(evt.currentTarget).is(':checked')) {
        $(selector).slideDown();
      } else {
        $(selector).slideUp();
      }
    }
  });
  // Perform initial setup of inputs linked to rule checkboxes.
  $.each($('#validation-rules :checkbox'), function() {
    var selector = '#' + this.id + '_inputs';
    if ($(selector).length>0 && !$(this).is(':checked')) {
      $(selector).hide();
    }
  });
  function changeDataType() {
    if ($('#data_type').val() === 'I' || $('#data_type').val() === 'F') {
      $('#ctrl-wrap-<?php echo $model->object_name; ?>-allow_ranges').show();
    } else {
      $('#ctrl-wrap-<?php echo $model->object_name; ?>-allow_ranges').hide();
    }
  }
  $('#data_type').change(changeDataType);
  changeDataType();
});
<?php
  }
?>
</script>
