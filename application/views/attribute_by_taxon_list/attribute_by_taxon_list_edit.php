<?php

/**
 * @file
 * View page for editing an attribute's link to a survey.
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

$dataType = $model->taxa_taxon_list_attribute->data_type;
switch ($dataType) {
  case "T":
    // Text.
    $enable_list = array(
      'valid_required',
      'valid_length',
      'valid_length_min',
      'valid_length_max',
      'valid_alpha',
      'valid_email',
      'valid_url',
      'valid_alpha_numeric',
      'valid_numeric',
      'valid_standard_text',
      'valid_decimal',
      'valid_dec_format',
      'valid_regex',
      'valid_regex_format',
      'valid_time',
    );
    break;

  case "L":
    // Lookup List.
    $enable_list = array(
      'valid_required',
    );
    break;

  case "I":
    // Integer.
    $enable_list = array(
      'valid_required',
      'valid_numeric',
      'valid_digit',
      'valid_integer',
      'valid_decimal',
      'valid_dec_format',
      'valid_regex',
      'valid_regex_format',
      'valid_min',
      'valid_min_value',
      'valid_max',
      'valid_max_value',
    );
    break;

  case "F":
    // Float.
    $enable_list = array(
      'valid_required',
      'valid_numeric',
      'valid_decimal',
      'valid_dec_format',
      'valid_regex',
      'valid_regex_format',
      'valid_min',
      'valid_min_value',
      'valid_max',
      'valid_max_value',
    );
    break;

  case "D":
    // Specific Date.
  case "V":
    // Vague Date.
    $enable_list = array(
      'valid_required',
      'valid_min',
      'valid_min_value',
      'valid_max',
      'valid_max_value',
      'valid_date_in_past',
    );
    break;

  case "B":
    // Boolean.
    $enable_list = array(
      'valid_required',
    );
    break;

  default:
    $enable_list = array();
    break;
}


?>
<p>This page allows you to modify the settings of the taxon attribute within the context of the
<?php echo $other_data['taxonList']; ?> list.</p>
<form id="entry_form" action="<?php echo url::site() ?>attribute_by_taxon_list/save/1" method="post">
  <fieldset id="validation-rules">
  <legend><?php echo $other_data['name']; ?> attribute details<?php echo $metadata; ?></legend>
    <p>Set the validation rules to apply to values submitted for this attribute below.</p>
    <input type="hidden" name="taxon_lists_taxa_taxon_list_attribute:id"
      value="<?php echo $values['taxon_lists_taxa_taxon_list_attribute:id']; ?>" />
    <input type="hidden" name="taxon_lists_taxa_taxon_list_attribute:taxa_taxon_list_attribute_id"
      value="<?php echo $values['taxon_lists_taxa_taxon_list_attribute:taxa_taxon_list_attribute_id']; ?>" />
    <?php
    if (in_array('valid_required', $enable_list)) {
      echo data_entry_helper::checkbox([
        'label' => 'Required',
        'fieldname' => 'valid_required',
        'default' => $model->valid_required,
        'helpText' => 'Force a value to be provided.',
      ]);
    }
    if (in_array('valid_length', $enable_list)) {
      echo data_entry_helper::checkbox([
        'label' => 'Length',
        'fieldname' => 'valid_length',
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
    }
    if (in_array('valid_alpha', $enable_list)) {
      echo data_entry_helper::checkbox([
        'label' => 'Alphabetic',
        'fieldname' => 'valid_alpha',
        'default' => $model->valid_alpha,
        'helpText' => 'Enforce that any value provided consists of alphabetic characters only.',
      ]);
    }
    if (in_array('valid_numeric', $enable_list)) {
      echo data_entry_helper::checkbox([
        'fieldname' => 'valid_numeric',
        'label' => 'Numeric characters only',
        'default' => $model->valid_numeric,
        'helpText' => 'Enforce that any value provided consists of numeric characters only.',
      ]);
    }
    if (in_array('valid_alpha_numeric', $enable_list)) {
      echo data_entry_helper::checkbox([
        'fieldname' => 'valid_alpha_numeric',
        'label' => 'Alphanumeric characters only',
        'default' => $model->valid_alpha_numeric,
        'helpText' => 'Enforce that any value provided consists of alphabetic and numeric characters only.',
      ]);
    }
    if (in_array('valid_digit', $enable_list)) {
      echo data_entry_helper::checkbox([
        'fieldname' => 'valid_digit',
        'label' => 'Digits only',
        'default' => $model->valid_digit,
        'helpText' => 'Enforce that any value provided consists of digits (0-9) only, with no decimal points or dashes.',
      ]);
    }
    if (in_array('valid_integer', $enable_list)) {
      echo data_entry_helper::checkbox([
        'fieldname' => 'valid_integer',
        'label' => 'Integer',
        'default' => $model->valid_integer,
        'helpText' => 'Enforce that any value provided is a valid whole number. Consider using an integer data type instead of text.',
      ]);
    }
    if (in_array('valid_standard_text', $enable_list)) {
      echo data_entry_helper::checkbox([
        'fieldname' => 'valid_standard_text',
        'label' => 'Standard text',
        'default' => $model->valid_standard_text,
        'helpText' => 'Enforce that any value provided is valid text (Letters, numbers, whitespace, dashes, full-stops and underscores are allowed..',
      ]);
    }
    if (in_array('valid_email', $enable_list)) {
      echo data_entry_helper::checkbox([
        'fieldname' => 'valid_email',
        'label' => 'Email address',
        'default' => $model->valid_email,
        'helpText' => 'Enforce that any value provided is a valid email address format.',
      ]);
    }
    if (in_array('valid_url', $enable_list)) {
      echo data_entry_helper::checkbox([
        'fieldname' => 'valid_url',
        'label' => 'URL',
        'default' => $model->valid_url,
        'helpText' => 'Enforce that any value provided is a valid URL format.',
      ]);
    }
    if (in_array('valid_decimal', $enable_list)) {
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
    }
    if (in_array('valid_regex', $enable_list)) {
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
    }
    if (in_array('valid_min', $enable_list)) {
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
    }
    if (in_array('valid_max', $enable_list)) {
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
    }
    if (in_array('valid_date_in_past', $enable_list)) {
      echo data_entry_helper::checkbox([
        'fieldname' => 'valid_date_in_past',
        'label' => 'Date in past',
        'default' => $model->valid_date_in_past,
        'helpText' => 'Ensure that the date values provided are in the past.',
      ]);
    }
    if (in_array('valid_time', $enable_list)) {
      echo data_entry_helper::checkbox([
        'fieldname' => 'valid_time',
        'label' => 'Time',
        'default' => $model->valid_time,
        'helpText' => 'Ensure that the value provided is a valid time format.',
      ]);
    }
    ?>
  </fieldset>
  <fieldset>
    <legend>Other settings</legend>
    <?php
    $readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
    echo data_entry_helper::outputAttribute(
      array(
        'caption' => 'Default value',
        'data_type' => $dataType,
        'fieldname' => 'default_value',
        'id' => 'default_value',
        'termlist_id' => $model->taxa_taxon_list_attribute->termlist_id,
        'default' => $model->default_value,
        'defaultUpper' => $model->default_upper_value,
        'allow_ranges' => $model->taxa_taxon_list_attribute->allow_ranges,
      ),
      array(
        'extraParams' => $readAuth,
      )
    );
    $controlTypeId = html::initial_value($values, 'taxon_lists_taxa_taxon_list_attribute:control_type_id');
    $types = array('' => '<Not specified>');

    foreach ($other_data['controlTypes'] as $controlType) {
      $types[$controlType->id] = $controlType->control;
    }
    echo data_entry_helper::select(array(
      'label' => 'Default control type',
      'fieldname' => $model->object_name . ':control_type_id',
      'lookupValues' => $types,
      'default' => $controlTypeId,
    ));
    $masterListId = warehouse::getMasterTaxonListId();
    if ($masterListId) {
      echo "<div class=\"alert alert-info\">If this attribute is only available for some taxa, list them below.</div>";
      echo '<label>Taxon restrictions</label>';
      echo '<input type="hidden" name="has-taxon-restriction-data" value="1" />';
      $speciesChecklistOptions = [
        'lookupListId' => $masterListId,
        'rowInclusionCheck' => 'alwaysRemovable',
        'extraParams' => $readAuth,
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
    else {
      echo <<<HTML
<div class="alert alert-warning">
  Set the master_list_id configuration in application/config/indicia.php to enable linking attributes to taxa.
</div>
HTML;
    }
    echo data_entry_helper::dump_javascript();
    echo html::form_buttons(html::initial_value($values, 'taxon_lists_taxa_taxon_list_attribute:id') !== NULL, FALSE, FALSE);
    ?>
  </fieldset>
</form>