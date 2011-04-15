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
echo html::script(array(
  'media/js/jquery.ajaxQueue.js',
  'media/js/jquery.bgiframe.min.js',
  'media/js/jquery.autocomplete.js',
  'media/js/OpenLayers.js',
  'media/js/spatial-ref.js'
), FALSE); 
$id = html::initial_value($values, 'sample:id');
?>
<script type='text/javascript' src='http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1'></script>
<script type='text/javascript'>
(function($){
  $(document).ready(function() {
    init_map('<?php echo url::base()."', '".html::initial_value($values, 'sample:geom'); ?>', 'entered_sref', 'entered_geom', true);
    jQuery('.vague-date-picker').datepicker({dateFormat : '<?php echo kohana::lang('dates.format_js'); ?>', constrainInput: false});    
    jQuery('.date-picker').datepicker({dateFormat : '<?php echo kohana::lang('dates.format_js'); ?>', constrainInput: false});
  });
})(jQuery);
</script>
<form class="cmxform" action="<?php echo url::site().'sample/save' ?>" method="post">
<?php echo $metadata; ?>
<fieldset>
<?php 
print form::hidden('sample:id', html::specialchars($id));
print form::hidden('sample:survey_id', html::initial_value($values, 'sample:survey_id')); 
print form::hidden('website_id', html::initial_value($values, 'website_id'));
?>
<legend>Sample Details</legend>
<ol>
<li>
<label for='survey'>Survey:</label>
<input readonly="readonly" class="ui-state-disabled" id="survey" value="<?php echo $model->survey->title; ?>" />
</li>
<li>
<label for='sample:date'>Date:</label>
<?php print form::input('sample:date', html::initial_value($values, 'sample:date'), 'class="date-picker"');  ?>
</li>
<li>
<label for="sample:entered_sref">Spatial Ref:</label>
<input id="sample:entered_sref" class="narrow" name="sample:entered_sref"
value="<?php echo html::initial_value($values, 'sample:entered_sref'); ?>"
onblur="exit_sref();"
onclick="enter_sref();"/>
<select class="narrow" id="sample:entered_sref_system" name="sample:entered_sref_system">
<?php
$entered_sref_system=html::initial_value($values, 'sample:entered_sref_system'); 
foreach (kohana::config('sref_notations.sref_notations') as $notation=>$caption) {
 if ($entered_sref_system==$notation)
   $selected=' selected="selected"';
 else
   $selected = '';
 echo "<option value=\"$notation\"$selected>$caption</option>";
}
 ?>
 </select>
 <input type="hidden" name="sample:entered_geom" value="<?php echo html::initial_value($values, 'sample:entered_geom'); ?>" />
 <?php echo html::error_message($model->getError('sample:entered_sref')); ?>
 <?php echo html::error_message($model->getError('sample:entered_sref_system')); ?>
 <p class="instruct">Zoom the map in by double-clicking then single click on the location's centre to set the
 spatial reference. The more you zoom in, the more accurate the reference will be.</p>
 <div id="map" class="smallmap" style="width: 600px; height: 350px;"></div>
 </li>
 <li>
 <label for='sample:location_name'>Location Name:</label>
 <?php
 print form::input('sample:location_name', html::initial_value($values, 'sample:location_name'));
 echo html::error_message($model->getError('sample:location_name'));
 ?>
 </li>
 <li>
 <label for="sample:recorder_names">Recorder Names:<br />(one per line)</label>
 <?php
 print form::textarea('sample:recorder_names', html::initial_value($values, 'sample:recorder_names'));
 echo html::error_message($model->getError('sample:recorder_names'));
 ?>
 </li>
 <li>
 <label for='sample:sample_method_id'>Sample Method:</label>
 <?php
 print form::dropdown('sample:sample_method_id', $other_data['method_terms'], html::initial_value($values, 'sample:sample_method_id'));
 echo html::error_message($model->getError('sample:sample_method_id'));
 ?>
 </li>
 <li>
 <label for='sample:comment'>Comment:</label>
 <?php
 print form::textarea('sample:comment', html::initial_value($values, 'sample:comment'));
 echo html::error_message($model->getError('sample:comment'));
 ?>
 </li>
 </ol>
 </fieldset>
 <fieldset>
 <legend>Survey Specific Attributes</legend>
 <ol>
 <?php
foreach ($values['attributes'] as $attr) {
	$name = 'smpAttr:'.$attr['sample_attribute_id'];
  // if this is an existing attribute, tag it with the attribute value record id so we can re-save it
  if ($attr['id']) $name .= ':'.$attr['id'];
	echo '<li><label for="">'.$attr['caption']."</label>\n";
	switch ($attr['data_type']) {
	  case 'Specific Date':
	  	echo form::input($name, $attr['value'], 'class="date-picker"');
	    break;
	  case 'Vague Date':
	    echo form::input($name, $attr['value'], 'class="vague-date-picker"');
	    break;
	  case 'Lookup List':	    
	  	echo form::dropdown($name, $values['terms_'.$attr['termlist_id']], $attr['raw_value']);
	  	break;
	  case 'Boolean':
	  	echo form::dropdown($name, array(''=>'','0'=>'false','1'=>'true'), $attr['value']);
	  	break;
	  default:
	  	echo form::input($name, $attr['value']);
	}
	echo '<br/>'.html::error_message($model->getError($name)).'</li>';
	
}
 ?>
 </ol>
 </fieldset>
 <?php echo html::form_buttons($id!=null, false, false); ?>
</form>
 