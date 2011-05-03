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
$id = html::initial_value($values, 'location:id');
$parent_id = html::initial_value($values, 'location:parent_id');
$boundary_geom = html::initial_value($values, 'location:boundary_geom');
$centroid_geom = html::initial_value($values, 'location:centroid_geom');
?>
<script type="text/javascript" src="http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1"></script>
<script type="text/javascript">

jQuery(document).ready(function() {
  init_map('<?php echo url::base(); ?>', <?php 
      if ($id && $boundary_geom) 
        echo "'$boundary_geom'"; 
      elseif ($id && $centroid_geom) 
        echo "'$centroid_geom'";
      else echo 'null';
    ?>, 'centroid_sref', 'centroid_geom', true, null, null, <?php 
      echo kohana::config('indicia.default_map_y').', '.kohana::config('indicia.default_map_x').', '.
      kohana::config('indicia.default_map_zoom');
    ?>);

  jQuery("input#parent").autocomplete("<?php echo url::site() ?>index.php/services/data/location", {
    minChars : 1,
    mustMatch : true,
    extraParams : {
      orderby : "name",
      mode : "json"
    },
    parse: function(data) {
      var results = [];
      var obj = JSON.parse(data);
      jQuery.each(obj, function(i, item) {
        results[results.length] = {
          'data' : item,
          'value' : item.id,
          'result' : item.name };
      });
      return results;
    },
    formatItem: function(item) {
      return item.name;
    },
    formatResult: function(item) {
      return item.id;
    }
  });
  jQuery("input#parent").result(function(event, data){
    jQuery("input#parent_id").attr('value', data.id);
  });
  jQuery('.vague-date-picker').datepicker({dateFormat : '<?php echo kohana::lang('dates.format_js'); ?>', constrainInput: false});    
  jQuery('.date-picker').datepicker({dateFormat : '<?php echo kohana::lang('dates.format_js'); ?>', constrainInput: false});
});
</script>
<p>This page allows you to specify the details of a location.</p>
<form class="cmxform" action="<?php echo url::site().'location/save'; ?>" method="post">
<div id="details">
<?php echo $metadata; ?>
<fieldset>
<input type="hidden" name="location:id" value="<?php echo html::initial_value($values, 'location:id'); ?>" />
<legend>Location details</legend>
<ol>
<li>
<label for="name">Name</label>
<input id="name" name="location:name" value="<?php echo html::initial_value($values, 'location:name'); ?>" />
<?php echo html::error_message($model->getError('location:name')); ?>
</li>
<li>
<label for="code">Code</label>
<input id="code" name="location:code" value="<?php echo html::initial_value($values, 'location:code'); ?>" />
<?php echo html::error_message($model->getError('location:code')); ?>
</li>
<li>
 <label for='location:location_type_id'>Location Type:</label>
 <?php
 echo form::dropdown('location:location_type_id', $other_data['type_terms'], html::initial_value($values, 'location:location_type_id'));
 echo html::error_message($model->getError('location:location_type_id'));
 ?>
 </li>
 <li>
<label for="centroid_sref">Spatial Ref:</label>
<input id="centroid_sref" class="narrow" name="location:centroid_sref"
  value="<?php echo html::initial_value($values, 'location:centroid_sref'); ?>"
  onblur="exit_sref();"
  onclick="enter_sref();"/>
<select class="narrow" id="centroid_sref_system" name="centroid_sref_system">
<?php foreach (kohana::config('sref_notations.sref_notations') as $notation=>$caption) {
  if (html::initial_value($values, 'location:centroid_sref_system')==$notation)
    $selected=' selected="selected"';
  else
    $selected = '';
  echo "<option value=\"$notation\"$selected>$caption</option>";}
?>
</select>
<input type="hidden" name="location:centroid_geom" id="centroid_geom" value="<?php echo $centroid_geom; ?>"/>
<input type="hidden" name="location:boundary_geom" id="boundary_geom" value="<?php echo $boundary_geom; ?>"/>
<?php echo html::error_message($model->getError('location:centroid_sref')); ?>
<?php echo html::error_message($model->getError('location:centroid_sref_system')); ?>
<p class="instruct">Zoom the map in by double-clicking then single click on the location's centre to set the
spatial reference. The more you zoom in, the more accurate the reference will be.</p>
<div id="map" class="smallmap" style="width: 600px; height: 350px;"></div>
</li>
<li>
<input type="hidden" name="location:parent_id" value="<?php echo $parent_id; ?>" />
<label for="parent">Parent Location</label>
<input id="parent" name="location:parent" value="<?php echo (($parent_id != null) ? html::specialchars(ORM::factory('location', $parent_id)->name) : ''); ?>" />
</li>
</ol>
</fieldset>
<fieldset>
<legend>Location Websites</legend>
<ol>
<?php
  $websiteIds = $this->get_allowed_website_id_list('editor');  
  if (!is_null($websiteIds))
    $websites = ORM::factory('website')->in('id', $websiteIds)->orderby('title','asc')->find_all();
  else
    $websites = ORM::factory('website')->orderby('title','asc')->find_all();        
  foreach ($websites as $website) {
    echo '<li><label for="website_'.$website->id.'" class="wide">'.$website->title.'</label>';
    echo '<input type="checkbox" name="joinsTo:website:'.$website->id.'" ';
    if(!is_null($id)){      
      if (array_key_exists('joinsTo:website:'.$website->id, $values)) echo "checked=\"checked\"";
    }
    echo '></li>';
  }  
?>
</ol>
</fieldset>
</div>
<?php if ($id != null) : ?>
<div id="attrs">
<fieldset>
 <legend>Additional Attributes</legend>
 <ol>
 <?php
foreach ($values['attributes'] as $attr) {
  $name = 'smpAttr:'.$attr['location_attribute_id'];
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
</div>
<?php 
endif;
echo html::form_buttons(html::initial_value($values, 'location:id')!=null);
?>
</form>
  