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
$id = html::initial_value($values, 'sample:id');
require_once(DOCROOT.'client_helpers/map_helper.php');
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
require_once(DOCROOT.'client_helpers/form_helper.php');
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));
?>
<form class="cmxform" action="<?php echo url::site().'sample/save' ?>" method="post" id="sample-edit">
<?php echo $metadata; ?>
<fieldset>
<input type="hidden" name="sample:id" value="<?php echo html::initial_value($values, 'sample:id'); ?>" />
<input type="hidden" name="sample:survey_id" value="<?php echo html::initial_value($values, 'sample:survey_id'); ?>" />
<input type="hidden" name="website_id" value="<?php echo html::initial_value($values, 'website_id'); ?>" />
<legend>Sample Details</legend>
<label for='survey'>Survey:</label>
<input readonly="readonly" class="ui-state-disabled" id="survey" value="<?php echo $model->survey->title; ?>" /><br/>
<?php
echo data_entry_helper::date_picker(array(
  'label' => 'Date',
  'fieldname' => 'sample:date',
  'default' => html::initial_value($values, 'sample:date'),
  'class' => 'required'
));
echo data_entry_helper::sref_and_system(array(
    'label' => 'Spatial Ref',
    'fieldname' => 'sample:entered_sref',
    'geomFieldname' => 'sample:geom',
    'default' => html::initial_value($values, 'sample:entered_sref'),
    'defaultGeom' => html::initial_value($values, 'sample:geom'),
    'systems' => kohana::config('sref_notations.sref_notations'),
    'defaultSystem' => html::initial_value($values, 'sample:entered_sref_system'),
    'class' => 'control-width-3',
    'validation'=>'required'
));
?>
<p class="instruct">Zoom the map in by double-clicking then single click on the sample's centre to set the
spatial reference. The more you zoom in, the more accurate the reference will be.</p>
<?php
$readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
echo map_helper::map_panel(array(
    'readAuth' => $readAuth,
    'presetLayers' => array('virtual_earth'),
    'editLayer' => true,
    'layers' => array(),
    'initial_lat'=>52,
    'initial_long'=>-2,
    'initial_zoom'=>7,
    'width'=>870,
    'height'=>400,
    'initialFeatureWkt' => html::initial_value($values, 'sample:geom'),
    'standardControls' => array('layerSwitcher','panZoom')
));
echo data_entry_helper::text_input(array(
  'label' => 'Location Name',
  'fieldname' => 'sample:location_name',
  'default' => html::initial_value($values, 'sample:location_name')
));
echo data_entry_helper::textarea(array(
  'label' => 'Recorder Names',
  'description' => 'Enter the names of the recorders, one per line',
  'fieldname' => 'sample:recorder_names',
  'default' => html::initial_value($values, 'sample:recorder_names')
));
echo data_entry_helper::select(array(
  'label' => 'Sample Method',
  'fieldname' => 'sample:sample_method_id',
  'default' => html::initial_value($values, 'sample:sample_method_id'),
  'lookupValues' => $other_data['method_terms'],
  'blankText' => '<Please select>'
));
echo data_entry_helper::textarea(array(
  'label' => 'Comment',
  'fieldname' => 'sample:comment',
  'default' => html::initial_value($values, 'sample:comment')
));
 ?>
 </fieldset>
 <fieldset>
 <legend>Survey Specific Attributes</legend>
 <ol>
 <?php
 foreach ($values['attributes'] as $attr) {
	$name = 'smpAttr:'.$attr['sample_attribute_id'];
  // if this is an existing attribute, tag it with the attribute value record id so we can re-save it
  if ($attr['id']) $name .= ':'.$attr['id'];
	switch ($attr['data_type']) {
    case 'Specific Date':
    case 'Vague Date':
      echo data_entry_helper::date_picker(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['value']
      ));
      break;
    case 'Lookup List':
      echo data_entry_helper::select(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['raw_value'],
        'lookupValues' => $values['terms_'.$attr['termlist_id']],
        'blankText' => '<Please select>'
      ));
      break;
    case 'Boolean':
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
echo html::form_buttons($id!=null, false, false); 
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::enable_validation('sample-edit');
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</form>
 