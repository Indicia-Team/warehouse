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
<form
  action="<?php echo url::site() . "attribute_by_survey/save/1?type=" . $_GET['type']; ?>"
  method="post">
  <fieldset class="validation-rules">
  <legend><?php echo $other_data['name']; ?> Attribute details</legend>
    <p>Set the validation rules to apply to values submitted for this attribute below.</p>
    <input type="hidden" name="<?php echo $this->type; ?>_attributes_website:id"
	    value="<?php echo $values[$this->type . '_attributes_website:id']; ?>" />
    <input type="hidden" name="<?php echo $this->type; ?>_attributes_website:restrict_to_survey_id" value="<?php echo $values[$this->type.'_attributes_website:restrict_to_survey_id']; ?>" />
    <input type="hidden" name="<?php echo $this->type; ?>_attributes_website:<?php echo $this->type; ?>_attribute_id" value="<?php echo $values[$this->type.'_attributes_website:'.$this->type.'_attribute_id']; ?>" />
<?php if (in_array('valid_required', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_required', TRUE, isset($model->valid_required) AND ($model->valid_required == 't')) ?>
      Required</label>
    </div>
<?php endif; ?>
<?php if (in_array('valid_length', $enable_list)) : ?>
    <div class="form-inline">
      <div class="checkbox">
        <label>
          <?php echo form::checkbox('valid_length', TRUE, isset($model->valid_length) && ($model->valid_length == 't')) ?>
        Length</label>
      </div>
      <input id="valid_length_min" name="valid_length_min" class="form-control"
        value="<?php echo html::specialchars($model->valid_length_min); ?>" />
      -
      <input id="valid_length_max" name="valid_length_max" class="form-control"
        value="<?php echo html::specialchars($model->valid_length_max); ?>" />
    </div>
<?php echo html::error_message($model->getError('valid_length'));
endif;
if (in_array('valid_alpha', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_alpha', TRUE, isset($model->valid_alpha) && ($model->valid_alpha === 't')) ?>
        Alphabetic
      </label>
    </div>
<?php endif;
if (in_array('valid_email', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_email', TRUE, isset($model->valid_email) && ($model->valid_email === 't')) ?>
        Valid email address format
    </label>
    </div>
<?php endif;
if (in_array('valid_url', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_url', TRUE, isset($model->valid_url) && ($model->valid_url === 't')) ?>
        Valid URL format
      </label>
    </div>
<?php endif;
if (in_array('valid_alpha_numeric', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_alpha_numeric', TRUE, isset($model->valid_alpha_numeric) && ($model->valid_alpha_numeric === 't')) ?>
        Alphanumeric (letters and numbers only)
      </label>
    </div>
<?php endif;
if (in_array('valid_numeric', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_numeric', TRUE, isset($model->valid_numeric) && ($model->valid_numeric === 't')) ?>
        Numeric
      </label>
    </div>
<?php endif;
if (in_array('valid_digit', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_url', TRUE, isset($model->valid_digit) && ($model->valid_digit === 't')) ?>
        Digits only
      </label>
    </div>
<?php endif;
// only integers will have this option, so set by default.
if (in_array('valid_integer', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_integer', TRUE, !isset($model->valid_integer) || ($model->valid_integer === 't')) ?>
        Integer
      </label>
    </div>
<?php endif;
if (in_array('valid_standard_text', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_standard_text', TRUE, isset($model->valid_standard_text) && ($model->valid_standard_text === 't')) ?>
        Standard text only (letters, numbers, whitespace, dashes, periods, and underscores are allowed)
      </label>
    </div>
<?php endif;
if (in_array('valid_decimal', $enable_list)) : ?>
    <div class="form-inline">
      <div class="checkbox">
        <label>
          <?php echo form::checkbox('valid_decimal', TRUE, isset($model->valid_decimal) && ($model->valid_decimal === 't')) ?>
          Decimal format (e.g. "2" to specify 2 decimal places, or "4,2" to force 4 digits and 2 decimals)
        </label>
      </div>
      <input id="valid_dec_format" name="valid_dec_format" class="form-control"
        value="<?php echo html::specialchars($model->valid_dec_format); ?>" />
    </div>
<?php echo html::error_message($model->getError('valid_decimal'));
endif;
if (in_array('valid_regex', $enable_list)) : ?>
    <div class="form-inline">
      <div class="checkbox">
        <label>
          <?php echo form::checkbox('valid_regex', TRUE, isset($model->valid_regex) && ($model->valid_regex === 't')) ?>
          Enforce format using a regular expression
        </label>
      </div>
      <input id="valid_regex_format" name="valid_regex_format" class="form-control"
        value="<?php echo html::specialchars($model->valid_regex_format); ?>" />
    </div>
<?php echo html::error_message($model->getError('valid_regex'));
endif;
if (in_array('valid_min', $enable_list)) : ?>
    <div class="form-inline">
      <div class="checkbox">
        <label>
          <?php echo form::checkbox('valid_min', TRUE, isset($model->valid_min) && ($model->valid_min === 't')) ?>
          Minimum value
        </label>
      </div>
      <input id="valid_min_value" name="valid_min_value" class="form-control"
        value="<?php echo html::specialchars($model->valid_min_value); ?>" />
    </div>
<?php endif;
if (in_array('valid_max', $enable_list)) : ?>
    <div class="form-inline">
      <div class="checkbox">
        <label>
          <?php echo form::checkbox('valid_max', TRUE, isset($model->valid_max) && ($model->valid_max === 't')) ?>
          Maximum value
        </label>
      </div>
      <input id="valid_max_value" name="valid_max_value" class="form-control"
        value="<?php echo html::specialchars($model->valid_max_value); ?>" />
    </div>
<?php endif;
if (in_array('valid_date_in_past', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_date_in_past', TRUE, isset($model->valid_date_in_past) && ($model->valid_date_in_past === 't')) ?>
        Date is in the past
      </label>
    </div>
<?php endif;
if (in_array('valid_time', $enable_list)) : ?>
    <div class="checkbox">
      <label>
        <?php echo form::checkbox('valid_time', TRUE, isset($model->valid_time) && ($model->valid_time === 't')) ?>
        Valid time format (hh:mm)
      </label>
    </div>
<?php endif; ?>
  </fieldset>
  <fieldset>
  <legend>Other information</legend>
<?php
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
echo data_entry_helper::outputAttribute(
  array(
    'caption' => 'Default value',
    'data_type' => $dataType,
    'fieldname' => 'default_value',
    'id' => 'default_value',
    'termlist_id' => $model->$attrModelName->termlist_id,
    'default' => $model->default_value
  ),
  array(
    'extraParams' => $readAuth
  )
);
?>
<?php
$controlTypeId = html::initial_value($values, $_GET['type'] . '_attributes_website:control_type_id');
$types = array('' => '<Not specified>');

foreach ($other_data['controlTypes'] as $controlType) {
  $types[$controlType->id] = $controlType->control;
}
echo data_entry_helper::select(array(
  'label' => 'Default control type',
  'fieldname' => $model->object_name . ':control_type_id',
  'lookupValues' => $types,
  'default' => $controlTypeId
));

if ($_GET['type']=='location') {
  $terms = array(''=>'<Not specified>')+$this->get_termlist_terms('indicia:location_types');
  echo data_entry_helper::select(array(
    'label' => 'Location Type',
    'fieldname' => 'location_attributes_website:restrict_to_location_type_id',
    'lookupValues' => $terms,
    'default' => html::initial_value($values, 'location_attributes_website:restrict_to_location_type_id'),
    'helpText' => 'If you want this attribute to only apply for locations of a certain type, select the type here.'
  ));
} elseif ($_GET['type']=='sample') {
  $terms = array(''=>'<Not specified>')+$this->get_termlist_terms('indicia:sample_methods');
  echo data_entry_helper::select(array(
    'label' => 'Sample Method',
    'fieldname' => 'sample_attributes_website:restrict_to_sample_method_id',
    'lookupValues' => $terms,
    'default' => html::initial_value($values, 'sample_attributes_website:restrict_to_sample_method_id'),
    'helpText' => 'If you want this attribute to only apply for samples of a certain method, select the method here.'
  ));
}
echo $metadata;
echo html::form_buttons(html::initial_value($values, 'custom_attribute:id')!=null, false, false);
?></fieldset></form>
