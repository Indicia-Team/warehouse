<?php

/**
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
 * @package	Core
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
 
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
$attrModelName = str_replace('s_website', '', $model->object_name);
$dataType = $model->$attrModelName->data_type;
switch ($dataType) {
  case "T": // text
    $enable_list = array('valid_required','valid_length','valid_length_min','valid_length_max','valid_alpha','valid_email','valid_url','valid_alpha_numeric','valid_numeric','valid_standard_text','valid_decimal','valid_dec_format','valid_regex','valid_regex_format');
    break;
  case "L": // Lookup List
    $enable_list = array('valid_required');
    break;
  case "I": // Integer
  case "F": // Float
    $enable_list = array('valid_required','valid_numeric','valid_decimal','valid_dec_format','valid_regex','valid_regex_format','valid_min','valid_min_value','valid_max','valid_max_value');
    break;
  case "D": // Specific Date
  case "V": // Vague Date
    $enable_list = array('valid_required','valid_min','valid_min_value','valid_max','valid_max_value','valid_date_in_past');
    break;
  case "B": // Boolean
    $enable_list = array('valid_required');
    break;
  default:
    $enable_list = array();
    break;
}

?>
<p>This page allows you to modify the settings of the <?php echo strtolower($other_data['name']); ?> attribute within the context of the
<?php echo $other_data['survey']; ?> survey.</p>
<form class="cmxform"
  action="<?php echo url::site()."attribute_by_survey/save/1?type=".$_GET['type']; ?>"
  method="post">
<fieldset>
<legend><?php echo $other_data['name']; ?> Attribute details</legend>
<input type="hidden" name="<?php echo $this->type; ?>_attributes_website:id"
	value="<?php echo $values[$this->type.'_attributes_website:id']; ?>" />
<input type="hidden" name="<?php echo $this->type; ?>_attributes_website:restrict_to_survey_id" value="<?php echo $values[$this->type.'_attributes_website:restrict_to_survey_id']; ?>" />
<input type="hidden" name="<?php echo $this->type; ?>_attributes_website:<?php echo $this->type; ?>_attribute_id" value="<?php echo $values[$this->type.'_attributes_website:'.$this->type.'_attribute_id']; ?>" />
<ol>
<?php if (in_array('valid_required', $enable_list)) : ?>
	<li id="li_valid_required"><label class="narrow" for="valid_required">Required</label><?php echo form::checkbox('valid_required', TRUE, isset($model->valid_required) AND ($model->valid_required == 't'), 'class="vnarrow" ' ) ?></li>
<?php endif; 
if (in_array('valid_length', $enable_list)) : ?>
	<li id="li_valid_length"><label class="narrow" for="valid_length">Length</label><?php echo form::checkbox('valid_length', TRUE, isset($model->valid_length) AND ($model->valid_length == 't'), 'class="vnarrow"'  ) ?><input
		class="narrow" id="valid_length_min" name="valid_length_min"
		value="<?php echo html::specialchars($model->valid_length_min); ?>" /> - <input class="narrow"
		id="valid_length_max" name="valid_length_max"
		value="<?php echo html::specialchars($model->valid_length_max); ?>" /> <?php echo html::error_message($model->getError('valid_length')); ?>
	</li>
<?php endif; 
if (in_array('valid_alpha', $enable_list)) : ?>
	<li id="li_valid_alpha"><label class="narrow" for="valid_alpha">Alphabetic</label><?php echo form::checkbox('valid_alpha', TRUE, isset($model->valid_alpha) AND ($model->valid_alpha == 't'), 'class="vnarrow" ' ) ?></li>
<?php endif; 
if (in_array('valid_email', $enable_list)) : ?>
	<li id="li_valid_email"><label class="narrow" for="valid_email">Email
	Address</label><?php echo form::checkbox('valid_email', TRUE, isset($model->valid_email) AND ($model->valid_email == 't'), 'class="vnarrow" ' ) ?></li>
<?php endif; 
if (in_array('valid_url', $enable_list)) : ?>
	<li id="li_valid_url"><label class="narrow" for="valid_url">URL</label><?php echo form::checkbox('valid_url', TRUE, isset($model->valid_url) AND ($model->valid_url == 't'), 'class="vnarrow" ' ) ?></li>
<?php endif; 
if (in_array('valid_alpha_numeric', $enable_list)) : ?>	
	<li id="li_valid_alpha_numeric"><label class="narrow"
		for="valid_alpha_numeric">Alphanumeric</label><?php echo form::checkbox('valid_alpha_numeric', TRUE, isset($model->valid_alpha_numeric) AND ($model->valid_alpha_numeric == 't'), 'class="vnarrow" ' ) ?></li>
<?php endif; 
if (in_array('valid_numeric', $enable_list)) : ?>		
	<li id="li_valid_numeric"><label class="narrow" for="valid_numeric">Numeric</label><?php echo form::checkbox('valid_numeric', TRUE, isset($model->valid_numeric) AND ($model->valid_numeric == 't'), 'class="vnarrow" ' ) ?></li>
<?php endif; 
if (in_array('valid_standard_text', $enable_list)) : ?>	
	<li id="li_valid_standard_text"><label class="narrow"
		for="valid_standard_text">Standard Text</label><?php echo form::checkbox('valid_standard_text', TRUE, isset($model->valid_standard_text) AND ($model->valid_standard_text == 't'), 'class="vnarrow" ' ) ?></li>
<?php endif; 
if (in_array('valid_decimal', $enable_list)) : ?>		
	<li id="li_valid_decimal"><label class="narrow" for="valid_decimal">Formatted
	Decimal</label><?php echo form::checkbox('valid_decimal', TRUE, isset($model->valid_decimal) AND ($model->valid_decimal == 't'), 'class="vnarrow" ' ) ?><input
		class="narrow" id="valid_dec_format" name="valid_dec_format"
		value="<?php echo html::specialchars($model->valid_dec_format); ?>" />
		<?php echo html::error_message($model->getError('valid_decimal')); ?>
	</li>
<?php endif; 
if (in_array('valid_regex', $enable_list)) : ?>	
	<li id="li_valid_regex"><label class="narrow" for="valid_regex">Regular
	Expression</label><?php echo form::checkbox('valid_regex', TRUE, isset($model->valid_regex) AND ($model->valid_regex == 't'), 'class="vnarrow" ' ) ?><input
		class="narrow" id="valid_regex_format" name="valid_regex_format"
		value="<?php echo html::specialchars($model->valid_regex_format); ?>" />
		<?php echo html::error_message($model->getError('valid_regex')); ?>
	</li>
<?php endif; 
if (in_array('valid_min', $enable_list)) : ?>	
	<li id="li_valid_min"><label class="narrow" for="valid_min">Minimum
	value</label><?php echo form::checkbox('valid_min', TRUE, isset($model->valid_min) AND ($model->valid_min == 't'), 'class="vnarrow" ' ) ?><input
		class="narrow" id="valid_min_value" name="valid_min_value"
		value="<?php echo html::specialchars($model->valid_min_value); ?>" />
		<?php echo html::error_message($model->getError('valid_min')); ?>
	</li>
<?php endif; 
if (in_array('valid_max', $enable_list)) : ?>	
	<li id="li_valid_max"><label class="narrow" for="valid_max">Maximum
	value</label><?php echo form::checkbox('valid_max', TRUE, isset($model->valid_max) AND ($model->valid_max == 't'), 'class="vnarrow" ' ) ?><input
		class="narrow" id="valid_max_value" name="valid_max_value"
		value="<?php echo html::specialchars($model->valid_max_value); ?>" />
		<?php echo html::error_message($model->getError('valid_max')); ?>
	</li>
<?php endif; 
if (in_array('valid_date_in_past', $enable_list)) : ?>	
	<li id="li_valid_date_in_past">
		<label class="narrow" for="valid_date_in_past">Date is in past</label><?php 
			echo form::checkbox('valid_date_in_past', TRUE, isset($model->valid_date_in_past) AND ($model->valid_date_in_past == 't'), 'class="vnarrow" ' ); 
		  echo html::error_message($model->getError('valid_date_in_past')); ?>
	</li>
<?php endif; ?>
</ol>
</fieldset>
<fieldset>
<legend>Other information</legend>
<?php 
$readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
echo data_entry_helper::outputAttribute(array(
    'caption' => 'Default value',
    'data_type' => $dataType,
    'fieldname' => 'default_value',
    'id' => 'default_value',
    'termlist_id' => $model->$attrModelName->termlist_id, 
    'default' => $model->default_value),
  array(
    'extraParams' => $readAuth
  )
);
?>
<label for="control_type_id">Default control type:</label>
<select name="<?php echo $model->object_name; ?>:control_type_id" id="control_type_id">
<option value="">&lt;Not specified&gt;</option>
<?php
$controlTypeId = html::initial_value($values, $_GET['type'].'_attributes_website:control_type_id');
foreach ($other_data['controlTypes'] as $controlType) {
  $selected = ($controlType->id==$controlTypeId) ? ' selected="selected"' : '';
  echo "<option value=\"$controlType->id\"$selected>$controlType->control</option>";
}
?>
</select>
<?php
echo $metadata;
echo html::form_buttons(html::initial_value($values, 'custom_attribute:id')!=null, false, false);
?></fieldset></form>
