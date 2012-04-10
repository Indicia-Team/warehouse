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
 * @package	Groups and individuals module
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

require_once(DOCROOT.'client_helpers/data_entry_helper.php');
require_once(DOCROOT.'client_helpers/report_helper.php');
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));
?>
<?php $tabs = false;
if (!empty($values['subject_observation:id']) 
  && is_numeric($values['subject_observation:id'])
  && $values['subject_observation:id'] > 0) : // edit so show tabs 
  $tabs = true;
?>
<div id="tabs">
<?php
data_entry_helper::enable_tabs(array('divId'=>'tabs')); 
echo data_entry_helper::tab_header(array('tabs'=>array(
  '#details'=>'Subject Observation',
  '#occurrences'=>'Occurrences',
)));
?>
<div id="details">
<?php  endif; ?>
<form class="iform" action="<?php echo url::site(); ?>subject_observation/save" method="post">
<?php // echo '$values: '.print_r($values, true).'<br />'; // for debug ?>
<?php // echo '$other_data: '.print_r($other_data, true); // for debug ?>
<?php  
echo $metadata; 
if (isset($values['subject_observation:id'])) : ?>
  <input type="hidden" name="subject_observation:id" value="<?php echo html::initial_value($values, 'subject_observation:id'); ?>" />
<?php endif; ?>
<input type="hidden" name="website_id" value="<?php echo html::initial_value($values, 'website_id'); ?>" />
<fieldset class="readonly">
<legend>Sample summary</legend>
<label>Sample link:</label>
<a href="<?php echo url::site(); ?>sample/edit/<?php echo html::initial_value($values, 'sample:id'); ?>">
ID <?php echo html::initial_value($values, 'sample:id');?></a><br />
<input type="hidden" name="subject_observation:sample_id" value="<?php echo html::initial_value($values, 'subject_observation:sample_id'); ?>" />
<input type="hidden" name="subject_observation:website_id" value="<?php echo html::initial_value($values, 'subject_observation:website_id'); ?>" />
<?php 
echo data_entry_helper::text_input(array(
  'label' => 'Survey',
  'fieldname' => 'survey:title',
  'default'=>html::initial_value($values, 'survey:title'),
  'class'=>'readonly control-width-4',
));
echo data_entry_helper::text_input(array(
  'label' => 'Date',
  'fieldname' => 'sample:date_start',
  'default'=>html::initial_value($values, 'sample:date_start'),
  'class'=>'readonly',
));
echo data_entry_helper::text_input(array(
  'label' => 'Spatial reference',
  'fieldname' => 'sample:entered_sref:no_validate', // append to name to avoid validation rules being applied
  'default'=>html::initial_value($values, 'sample:entered_sref:no_validate'),
  'class'=>'readonly',
));
?>
</fieldset>
<fieldset>
<legend>Subject observation details</legend>
<?php 
$readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
echo data_entry_helper::listbox(array(
  'label' => 'Occurrences',
  'fieldname' => 'joinsTo:occurrence:id[]',
  'table' => 'occurrence',
  'captionField' => 'taxon',
  'valueField' => 'id',
  'class' => 'control-width-3',
  'multiselect' => true,
  'default' => array_key_exists('joinsTo:occurrence:id', $values) ? $values['joinsTo:occurrence:id'] : '',
  'extraParams' => $readAuth + array('view' => 'gv','sample_id' => html::initial_value($values, 'sample:id'),),
));
echo data_entry_helper::select(array(
  'label' => 'Subject Type',
  'fieldname' => 'subject_observation:subject_type_id',
  'default' => html::initial_value($values, 'subject_observation:subject_type_id'),
  'lookupValues' => $other_data['subject_type_terms'],
  'blankText' => '<Please select>',
  'extraParams' => $readAuth,
));
echo data_entry_helper::text_input(array(
  'label' => 'Count',
  'fieldname' => 'subject_observation:count',
  'class'=>'control-width-1',
  'default' => html::initial_value($values, 'subject_observation:count'),
  'suffixTemplate' => 'nosuffix',
));
echo data_entry_helper::select(array(
  'label' => null,
  'fieldname' => 'subject_observation:count_qualifier_id',
  'default' => html::initial_value($values, 'subject_observation:count_qualifier_id'),
  'lookupValues' => $other_data['count_qualifier_terms'],
  'blankText' => '<Please select>',
  'extraParams' => $readAuth,
));
echo data_entry_helper::textarea(array(
  'label'=>'Comment',
  'fieldname'=>'subject_observation:comment',
  'class'=>'control-width-6',
  'default' => html::initial_value($values, 'subject_observation:comment')
));
?>
</fieldset>
<?php if (array_key_exists('attributes', $values) && count($values['attributes'])>0) : ?>
<fieldset>
<legend>Custom Attributes</legend>
<ol>
<?php
 foreach ($values['attributes'] as $attr) :
	$name = 'sjoAttr:'.$attr['subject_observation_attribute_id'];
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
echo html::form_buttons(html::initial_value($values, 'subject_observation:id')!=null, false, false);
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'json';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</form>
<?php if ($tabs) : ?>
</div>
<div id="occurrences">
TODO - filter occurrences list to display linked occurrences
</div>
</div>
<?php endif; ?>