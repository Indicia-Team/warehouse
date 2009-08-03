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
  'media/js/thickbox-compressd.js',
  'media/js/jquery.autocomplete.js',
  'media/js/OpenLayers.js',
  'media/js/spatial-ref.js'
), FALSE); ?>
<script type='text/javascript' src='http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1'></script>
<script type='text/javascript'>
(function($){
  $(document).ready(function() {
    init_map('<?php echo url::base(); ?>', <?php echo ($model->id) ? "'$model->geom'" : 'null'; ?>, 'entered_sref', 'entered_geom', true);
  });
})(jQuery);
</script>
<form class="cmxform"  name='editList' action="<?php echo url::site().'occurrence/save' ?>" method="POST">
<?php print form::hidden('id', html::specialchars($model->id)); ?>
<?php print form::hidden('survey_id', $model->survey_id); ?>
<fieldset>
<legend>Sample Details</legend>
<ol>
<li>
<label for='vague_date'>Date:</label>
<?php print form::input('vague_date', $model->vague_date);  ?>
</li>
<li>
<label for="entered_sref">Spatial Ref:</label>
<input id="entered_sref" class="narrow" name="entered_sref"
value="<?php echo html::specialchars($model->entered_sref); ?>"
onblur="exit_sref();"
onclick="enter_sref();"/>
<select class="narrow" id="entered_sref_system" name="entered_sref_system">
<?php foreach (kohana::config('sref_notations.sref_notations') as $notation=>$caption) {
 if ($model->entered_sref_system==$notation)
 $selected=' selected="selected"';
 else
   $selected = '';
 echo "<option value=\"$notation\"$selected>$caption</option>";}
 ?>
 </select>
 <input type="hidden" name="entered_geom" id="entered_geom" />
 <?php echo html::error_message($model->getError('entered_sref')); ?>
 <?php echo html::error_message($model->getError('entered_sref_system')); ?>
 <p class="instruct">Zoom the map in by double-clicking then single click on the location's centre to set the
 spatial reference. The more you zoom in, the more accurate the reference will be.</p>
 <div id="map" class="smallmap" style="width: 600px; height: 350px;"></div>
 </li>
 <li>
 <label for='location_name'>Location Name:</label>
 <?php
 print form::input('location_name', $model->location_name);
 echo html::error_message($model->getError('location_name'));
 ?>
 </li>
 <li>
 <label for="recorder_names">Recorder Names:<br />(one per line)</label>
 <?php
 print form::textarea('recorder_names', $model->recorder_names);
 echo html::error_message($model->getError('recorder_names'));
 ?>
 <li>
 <label for='sample_method_id'>Sample Method:</label>
 <?php
 print form::dropdown('sample_method_id', $method_terms, $model->sample_method_id);
 echo html::error_message($model->getError('sample_method_id'));
 ?>
 </li>
 </ol>
 </fieldset>
 <?php echo $metadata ?>
 <?php echo $occurrences ?>
 <input type="submit" name="submit" value="Submit" />
 <input type="submit" name="submit" value="Delete" />