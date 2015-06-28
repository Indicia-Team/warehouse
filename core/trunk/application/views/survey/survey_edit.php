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
$readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
?>
<p>This page allows you to specify the details of a survey in which samples and records can be organised.</p>
<form class="cmxform" action="<?php echo url::site().'survey/save'; ?>" method="post" id="survey-edit">
<?php echo $metadata ?>
<fieldset>
<legend>Survey dataset details</legend>
<?php 
echo data_entry_helper::hidden_text(array(
  'fieldname'=>'survey:id',
  'default'=>html::initial_value($values, 'survey:id')
));
echo data_entry_helper::text_input(array(
  'label'=>'Title',
  'fieldname'=>'survey:title',
  'default'=>html::initial_value($values, 'survey:title'),
  'validation' => 'required',
  'helpText' => 'Provide a title for your survey dataset',
));
echo data_entry_helper::textarea(array(
  'label'=>'Description',
  'fieldname'=>'survey:description',
  'default'=>html::initial_value($values, 'survey:description'),
  'validation' => 'required',
  'helpText' => 'Provide an optional description of your survey to help when browsing survey datasets on the warehouse'
));
echo data_entry_helper::autocomplete(array(
		'label' => 'Parent survey',
		'fieldname' => 'survey:parent_id',
		'table' => 'survey',
		'captionField' => 'title',
		'valueField' => 'id',
		'extraParams' => $readAuth,
		'default' => html::initial_value($values, 'survey:parent_id'),
		'defaultCaption' => html::initial_value($values, 'parent:title'),
    'helpText' => 'Set a parent for your survey to allow grouping of survey datasets in reports'
));
echo data_entry_helper::select(array(
  'label'=>'Website',
  'fieldname'=>'survey:website_id',
  'default'=>html::initial_value($values, 'survey:website_id'),
  'lookupValues'=>$other_data['websites'],
  'helpText'=>'The survey must belong to a website registration'
));
//Only show fields, if fields have been found in the database (the auto verify module is installed)
if (array_key_exists('survey:auto_accept',$values)) {
  echo data_entry_helper::checkbox(array(
  'label'=>'Auto Accept',
  'fieldname'=>'survey:auto_accept',
  'default'=>html::initial_value($values, 'survey:auto_accept'),
  'helpText'=>'Should the automatic verification module attempt to auto verify records in this survey?'
  ));
}
if (array_key_exists('survey:auto_accept_max_difficulty',$values)) {
  echo data_entry_helper::text_input(array(
    'label'=>'Auto Accept Maximum Difficulty',
    'fieldname'=>'survey:auto_accept_max_difficulty',
    'class'=>'control-width-1',
    'default'=> html::initial_value($values, 'survey:auto_accept_max_difficulty'),
    'helpText'=>'If Auto Accept is set, then this is the minimum identification difficulty that will be auto verified.'
  ));
}
?>
</fieldset>
<?php if (array_key_exists('attributes', $values) && count($values['attributes'])>0) : ?>
 <fieldset>
 <legend>Custom attributes</legend>
 <ol>
 <?php
 foreach ($values['attributes'] as $attr) {
	$name = 'srvAttr:'.$attr['survey_attribute_id'];
  // if this is an existing attribute, tag it with the attribute value record id so we can re-save it
  if ($attr['id']) $name .= ':'.$attr['id'];
	switch ($attr['data_type']) {
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
  }
	
}
 ?>
 </ol>
 </fieldset>
<?php 
endif;
echo html::form_buttons(html::initial_value($values, 'survey:id')!=null);
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::enable_validation('survey-edit');
data_entry_helper::link_default_stylesheet();
data_entry_helper::$javascript .= "
// ensure the parent lookup does not allow an inappropriate survey to be selected (i.e. self or wrong website)
function setParentFilter() {  
  var filter={\"query\":{}};
  filter.query.notin=['id', [1]];
  filter.query.where=['website_id', $('#survey\\\\:website_id').val()];
  filter.query=JSON.stringify(filter.query);
  $('#survey\\\\:parent_id\\\\:title').setExtraParams(filter);
}
$('#survey\\\\:website_id').change(function() {
  $('#survey\\\\:parent_id\\\\:title').val('');
  $('#survey\\\\:parent_id').val('');
  setParentFilter();
});
setParentFilter();
";
echo data_entry_helper::dump_javascript();
?>
</form>
