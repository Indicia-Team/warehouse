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
<form action="<?php echo url::site().$controllerpath.'/upload_shp2'; ?>" method="post" class="cmxform">
<fieldset>
<input type='checkbox' class="vnarrow" name="boundary"/>Select this checkbox if the data should be loaded into the boundary geometry in the location (as opposed to the centroid geometry).<br/>
<input type='checkbox' class="vnarrow" name="use_parent"/>Select the checkbox if the locations are associated with a parent location.<br/>
<input type='checkbox' class="vnarrow" name="use_parent_code"/>If so, use the code to join to the parent rather than the name?.<br/>
<label for='SRID' class='wide' >SRID used in Shapefile</label>
<select id='SRID' name='srid' >
  <option value="27700">EPSG:27700 British National Grid</option>
  <option value="4326">EPSG:4326 WGS 84</option>
  <option value="900913">EPSG:900913: Google Projection</option>
  <option value="2169">EPSG:2169 Luxembourg 1930</option>
</select><br/>
<input type='checkbox' class="vnarrow" name="use_sref_system"/>Select this checkbox when using the boundary above and you wish the centroid to be generated using this sref. If not checked, the centroid will be generated in EPSG:4326 (Lat Long)<br/>
<label for='type' class='wide' >Location type for all</label>
<select id='type' name='type' >
<?php 
  $terms = $this->db->select('id, term')->from('list_termlists_terms')->where('termlist_external_key', 'indicia:location_types')->orderby('term', 'asc')->get()->result();
  
  echo '<option value="" >&lt;Not applicable&gt;</option>';
  foreach ($terms as $term) {
    echo '<option value="'.$term->id.'" >'.$term->term.'</option>';
  }
  ?>
</select><br/>
<label for='website_id' class='wide' >Default Website to attach any new locations to</label>
<select id='website_id' name='website_id' >
<?php
  if (!is_null($this->auth_filter))
    $websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->where(array('deleted'=>'f'))->orderby('title','asc')->find_all();
  else {
    $websites = ORM::factory('website')->orderby('title','asc')->find_all();        
    echo '<option value="all" >&lt;Available to all&gt;</option>';
  }
  foreach ($websites as $website) {
    echo '<option value="'.$website->id.'" >'.$website->title.'</option>';
  }
  ?>
</select><br/>

<label for='prepend' class ="wide">Optional text to prepend to field identified below to create location name in database</label>
<input id='prepend' name="prepend" /><br />
<input type='hidden' name='uploaded_zip' <?php echo 'value="'.$_SESSION['uploaded_zip'].'"'; ?>/><br/>
<input type='hidden' name='extracted_basefile' <?php echo 'value="'.$_SESSION['extracted_basefile'].'"'; ?>/><br/>
<p>Please indicate which column in the DBF file should be used to identify the name of the location.<br />
You can also indicate a column to identify a parent location and a code field.</p>
<table class="ui-widget ui-widget-content">
<thead class="ui-widget-header">
<tr><th>Column in DBF File</th><th>Name?</th><th>Parent Name/Code?</th><th>Code?</th></tr>
</thead>
<tbody>
<?php $i=0;
foreach ($columns as $col): ?>
  <tr class="<?php echo ($i % 2 == 0) ? 'evenRow' : 'oddRow'; ?>">
  <?php $i++; ?>
    <td><?php echo $col['name']; ?></td>
    <td><input type="radio" class="narrow" value="<?php echo $col['name']; ?>" name="name" /></td>
    <td><input type="radio" class="narrow" value="<?php echo $col['name']; ?>" name="parent" /></td>
    <td><input type="radio" class="narrow" value="<?php echo $col['name']; ?>" name="code" /></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<input type="submit" value="Upload Data" id="upload-button" class="default" />
<br/>
<?php
// We stick these at the bottom so that all the other things will be parsed first
foreach ($this->input->post() as $a => $b) {
  echo "<input type=\"radio\" value=\"$b\" name=\"$a\" />";
}
?>
</fieldset>
</form>

