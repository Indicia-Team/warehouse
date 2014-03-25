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
 * @package	Individuals and associations
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

$id = html::initial_value($values, 'identifiers_subject_observation:id');
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
$readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
?>
<p>This page allows you to specify the details of an identifier for a subject observation.</p>
<form class="cmxform" action="<?php echo url::site().'identifiers_subject_observation/save'; ?>" method="post" id="entry-form">
<?php 
data_entry_helper::enable_validation('entry-form');
echo $metadata; 
?>
<fieldset>
<input type="hidden" name="identifiers_subject_observation:id" value="<?php echo $id ?>" />
<input type="hidden" name="identifiers_subject_observation:subject_observation_id" value="<?php echo html::initial_value($values, 'identifiers_subject_observation:subject_observation_id'); ?>" />
<legend>Identifier Details</legend>
<?php
echo data_entry_helper::autocomplete(array(
  'label' => 'Identifier',
  'fieldname' => 'identifiers_subject_observation:identifier_id',
  'table' => 'identifier',
  'captionField' => 'coded_value',
  'valueField' => 'id',
  'extraParams' => $readAuth,
  'default' => html::initial_value($values, 'identifiers_subject_observation:identifier_id'),
  'defaultCaption' => html::initial_value($values, 'identifier:coded_value'),
  'validation'=>'required'
));
echo data_entry_helper::checkbox(array(
  'fieldname'=>'identifiers_subject_observation:matched',
  'label'=>'Matched',
  'helpText'=>'Does this observation match a known identifier?',
  'default'=>html::initial_value($values, 'identifiers_subject_observation:matched')
));
echo data_entry_helper::select(array(
  'fieldname'=>'identifiers_subject_observation:verified_status',
  'label'=>'Verified status',
  'helpText'=>'Status of this identifier observation.',
  'lookupValues'=>array(''=>'Please select>', 'U'=>'unknown', 'M'=>'misread', 'V'=>'verified'),
  'default'=>html::initial_value($values, 'identifiers_subject_observation:verified_status'),
  'validation'=>'required'
));
  
?>
</fieldset>

 <?php if (array_key_exists('attributes', $values) && count($values['attributes'])>0) : ?>
  <fieldset>
 <legend>Custom Attributes</legend>
 <ol>
 <?php
 foreach ($values['attributes'] as $attr) :
	$name = 'isoAttr:'.$attr['identifiers_subject_observation_attribute_id'];
  // if this is an existing attribute, tag it with the attribute value record id so we can re-save it
  if ($attr['id']) $name .= ':'.$attr['id'];
	switch ($attr['data_type']) :
    case 'D':
    case 'V':
      echo data_entry_helper::date_picker(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['value']
      ));
      break;
    case 'L':
      echo data_entry_helper::select(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['raw_value'],
        'lookupValues' => $values['terms_'.$attr['termlist_id']],
        'blankText' => '<Please select>'
      ));
      break;
    case 'B':
      echo data_entry_helper::checkbox(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['value']
      ));
      break;
    default:
      echo data_entry_helper::text_input(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['value']
      ));
  endswitch;
	
endforeach;
 ?>
 </ol>
 </fieldset>
<?php 
endif;
?>
<?php 
echo html::form_buttons($id!=null, false, false); 
data_entry_helper::link_default_stylesheet();
// No need to re-link to jQuery
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
</form>