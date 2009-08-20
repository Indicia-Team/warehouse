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

?>
<?php echo html::script(array(
  'media/js/jquery.ajaxQueue.js',
  'media/js/jquery.bgiframe.min.js',
  'media/js/thickbox-compressd.js',
  'media/js/jquery.autocomplete.js',
  'media/js/OpenLayers.js',
  'media/js/spatial-ref.js'
), FALSE); ?>
<script type="text/javascript" src="http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1"></script>
<script type="text/javascript">

jQuery(document).ready(function() {
  init_map('<?php echo url::base(); ?>', <?php if ($model->id) echo "'$model->centroid_geom'"; else echo 'null'; ?>,
    'centroid_sref', 'centroid_geom', true);

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
});
</script>
<p>This page allows you to specify the details of a location.</p>
<form class="cmxform" action="<?php echo url::site().'location/save'; ?>" method="post">
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<fieldset>
<legend>Location details</legend>
<ol>
<li>
<label for="name">Name</label>
<input id="name" name="name" value="<?php echo html::specialchars($model->name); ?>" />
<?php echo html::error_message($model->getError('name')); ?>
</li>
<li>
<label for="code">Code</label>
<input id="code" name="code" value="<?php echo html::specialchars($model->code); ?>" />
<?php echo html::error_message($model->getError('code')); ?>
</li>
<li>
 <label for='location_type_id'>Location Type:</label>
 <?php
 print form::dropdown('location_type_id', $type_terms, $model->location_type_id);
 echo html::error_message($model->getError('location_type_id'));
 ?>
 </li>
 <li>
<label for="centroid_sref">Spatial Ref:</label>
<input id="centroid_sref" class="narrow" name="centroid_sref"
  value="<?php echo html::specialchars($model->centroid_sref); ?>"
  onblur="exit_sref();"
  onclick="enter_sref();"/>
<select class="narrow" id="centroid_sref_system" name="centroid_sref_system">
<?php foreach (kohana::config('sref_notations.sref_notations') as $notation=>$caption) {
  if ($model->centroid_sref_system==$notation)
    $selected=' selected="selected"';
  else
    $selected = '';
  echo "<option value=\"$notation\"$selected>$caption</option>";}
?>
</select>
<input type="hidden" name="centroid_geom" id="centroid_geom" />
<?php echo html::error_message($model->getError('centroid_sref')); ?>
<?php echo html::error_message($model->getError('centroid_sref_system')); ?>
<p class="instruct">Zoom the map in by double-clicking then single click on the location's centre to set the
spatial reference. The more you zoom in, the more accurate the reference will be.</p>
<div id="map" class="smallmap" style="width: 600px; height: 350px;"></div>
</li>
<li>
<input type="hidden" name="parent_id" id="parent_id" value="<?php echo html::specialchars($model->parent_id); ?>" />
<label for="parent">Parent Location</label>
<input id="parent" name="parent" value="<?php echo (($model->parent_id != null) ? html::specialchars(ORM::factory('location', $model->parent_id)->name) : ''); ?>" />
</li>
</ol>
</fieldset>
<fieldset>
<legend>Location Websites</legend>
<ol>
<?php
  if (!is_null($this->gen_auth_filter))
    $websites = ORM::factory('website')->in('id',$this->gen_auth_filter['values'])->orderby('title','asc')->find_all();
  else
    $websites = ORM::factory('website')->orderby('title','asc')->find_all();
  foreach ($websites as $website) {
    echo '<li><label for="website_'.$website->id.'">'.$website->title.'</label>';
    echo '<input type="checkbox" name="website_'.$website->id.'" ';
    if(!is_null($model->id)){
      $locations_website = ORM::factory('locations_website', array('website_id' => $website->id, 'location_id' => $model->id));
      if(ORM::factory('locations_website', array('website_id' => $website->id, 'location_id' => $model->id))->loaded) echo "checked=\"checked\"";
    }
    echo '></li>';
  }
?>
</ol>
</fieldset>
<?php echo $metadata ?>
<input type="submit" value="Save" name="submit"/>
<input type="submit" value="Delete" name="submit"/>
</form>
