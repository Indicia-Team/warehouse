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

$attrModelName = str_replace('s_website', '', $model->object_name);
$dataType = $model->$attrModelName->data_type;
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
<p>This page allows you to modify the settings of the <?php echo strtolower($other_data['name']); ?> attribute within the context of the
<?php echo $other_data['survey']; ?> survey.</p>
<form id="entry_form"
  action="<?php echo url::site() . "attribute_by_survey/save/1?type=" . $_GET['type']; ?>"
  method="post">
  <fieldset id="validation-rules">
  <legend><?php echo $other_data['name']; ?> attribute details<?php echo $metadata; ?></legend>
    <p>Set the validation rules to apply to values submitted for this attribute below.</p>
    <input type="hidden" name="<?php echo $this->type; ?>_attributes_website:id"
      value="<?php echo $values[$this->type . '_attributes_website:id']; ?>" />
    <input type="hidden" name="<?php echo $this->type; ?>_attributes_website:restrict_to_survey_id"
      value="<?php echo $values[$this->type . '_attributes_website:restrict_to_survey_id']; ?>" />
    <input type="hidden" name="<?php echo $this->type; ?>_attributes_website:<?php echo $this->type; ?>_attribute_id"
      value="<?php echo $values[$this->type . '_attributes_website:' . $this->type . '_attribute_id']; ?>" />
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
        'termlist_id' => $model->$attrModelName->termlist_id,
        'default' => $model->default_value,
        'defaultUpper' => $model->default_upper_value,
        'allow_ranges' => $model->$attrModelName->allow_ranges,
      ),
      array(
        'extraParams' => $readAuth,
      )
    );
    $controlTypeId = html::initial_value($values, $_GET['type'] . '_attributes_website:control_type_id');
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

    if ($_GET['type'] === 'location') {
      $terms = array('' => '<Not specified>') + $this->get_termlist_terms('indicia:location_types');
      echo data_entry_helper::select(array(
        'label' => 'Location Type',
        'fieldname' => 'location_attributes_website:restrict_to_location_type_id',
        'lookupValues' => $terms,
        'default' => html::initial_value($values, 'location_attributes_website:restrict_to_location_type_id'),
        'helpText' => 'If you want this attribute to only apply for locations of a certain type, select the type here.',
      ));
    }
    elseif ($_GET['type'] === 'sample') {
      $terms = array('' => '<Not specified>') + $this->get_termlist_terms('indicia:sample_methods');
      echo data_entry_helper::select(array(
        'label' => 'Sample Method',
        'fieldname' => 'sample_attributes_website:restrict_to_sample_method_id',
        'lookupValues' => $terms,
        'default' => html::initial_value($values, 'sample_attributes_website:restrict_to_sample_method_id'),
        'helpText' => 'If you want this attribute to only apply for samples of a certain method, select the method here.',
      ));
    }
    elseif ($_GET['type'] === 'occurrence' && $model->occurrence_attribute->system_function === 'sex_stage_count') {
      // For abundance attributes, the survey can opt-into auto-handling of the
      // occurrence's zero abundance flag.
      echo data_entry_helper::checkbox([
        'fieldname' => 'occurrence_attributes_website:auto_handle_zero_abundance',
        'label' => 'Auto-handle zero abundance flag',
        'default' => html::initial_value($values, 'occurrence_attributes_website:auto_handle_zero_abundance'),
        'helpText' => <<<TXT
          Tick this box for attributes which encapsulate all the captured information about the abundance of the
          record, therefore a value of 0, none, absent etc can be assumed to mean a zero abundance record/record
          of absence.
        TXT,
      ]);
    }
    // Use a species checklist to capture information about taxon restrictions
    // for this attribute.
    if ($_GET['type'] === 'sample' || $_GET['type'] === 'occurrence') {
      $masterListId = warehouse::getMasterTaxonListId();
      if ($masterListId) {
        $msg = empty($other_data['sexStageOccAttrs']) ? 'taxa' : 'taxa and sex/stage combinations';
        echo <<<HTML
<div class="alert alert-info">
  <p>If this attribute is only available for some $msg, list them below.</p>
 <p> Note that any attributes associated with a taxon via a restriction in the list below may be superceded by an attribute
  of the same type linked lower down the taxon hierarchy. For example, if you link a stage attribute to a high level
  taxon such as Animalia plus a stage attribute to a lower level taxon such as a genus, then the one linked at the
  lower level will block the higher level linked attribute from appearing. This allows you to link more specific
  attributes of the same type further down the taxonomic hierarchy. Note that for these purposes, sex, stage and
  abundance attributes are treated as the same thing since they often interact. So, if you have a sex and stage
  attribute linked to a higher level taxon, then if you add a stage attribute to a lower level and want to use the same
  sex attribute you should re-link the sex attribute at the lower level.</p>
</div>
<label>Taxon restrictions</label>
<input type="hidden" name="has-taxon-restriction-data" value="1" />

HTML;
        require_once 'client_helpers/prebuilt_forms/includes/language_utils.php';
        $speciesChecklistOptions = [
          'lookupListId' => $masterListId,
          'rowInclusionCheck' => 'alwaysRemovable',
          'extraParams' => $readAuth,
          'survey_id' => $values[$this->type . '_attributes_website:restrict_to_survey_id'],
          'language' => iform_lang_iso_639_2(kohana::config('indicia.default_lang')),
          'occAttrs' => $other_data['sexStageOccAttrs'],
        ];
        if (!empty($other_data['taxon_restrictions'])) {
          $restrictionsJson = json_encode($other_data['taxon_restrictions']);
          data_entry_helper::$javascript .= <<<JS
indiciaFns.loadExistingRestrictions($restrictionsJson);

JS;
          if (count($other_data['taxon_restrictions']) > 0) {
            $speciesChecklistOptions['listId'] = $masterListId;
          }
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
    }
    echo data_entry_helper::dump_javascript();
    echo html::form_buttons(html::initial_value($values, 'custom_attribute:id') !== NULL, FALSE, FALSE);
    ?>
  </fieldset>
</form>
