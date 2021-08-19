<?php

/**
 * @file
 * View template for the upload Shape file config form.
 *
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */
?>
<form action="<?php echo url::site() . $controllerpath . '/upload_shp2'; ?>" method="post" >
  <fieldset>
    <div class="form-group">
      <label for="boundary">Shape file geometries are</label>
        <select id="geometries" name="geometries" class="form-control">
          <option value="boundary" selected="selected">Location boundaries</option>
          <option value="centroid">Location centroids</option>
        </select>
      </label>
    </div>
    <div class="checkbox">
      <label>
        <input type="checkbox" name="use_parent" id="use_parent" />
        Associate the locations with a parent location?
      </label>
    </div>
    <div class="form-group" id="ctrl-wrap-parent-link-field" style="display: none">
      <label for="boundary">Which field is used to link to the parent location?</label>
        <select id="parent_link_field" name="parent_link_field" class="form-control">
          <option value="name" selected="selected">Location name</option>
          <option value="code">Location code</option>
        </select>
      </label>
    </div>
    <div class="form-group">
      <label for="SRID">SRID used in Shapefile:</label>
      <select id='SRID' name='srid' class="form-control">
        <?php
        foreach ($systems as $code => $system) {
          echo "<option value=\"$code\">EPSG:$code $system</option>";
        }
        ?>
      </select>
    </div>
    <div class="checkbox">
      <label>
        <input type="checkbox" name="use_sref_system" />
        Select this checkbox when using the boundary above and you wish the centroid to be generated using this sref.
        If not checked, the centroid will be generated in EPSG:4326 (Lat Long).
      </label>
    </div>
    <div class="form-group">
      <label for="type">Set imported locations' type to:</label>
      <select id="type" name="type" class="form-control">
        <?php
        $terms = $this->db
          ->select('id, term')
          ->from('list_termlists_terms')
          ->where('termlist_external_key', 'indicia:location_types')
          ->orderby('term', 'asc')
          ->get()->result();
        echo '<option value="" >&lt;Nothing&gt;</option>';
        foreach ($terms as $term) {
          echo '<option value="' . $term->id . '" >' . $term->term . '</option>';
        }
        ?>
      </select>
    </div>
    <div class="form-group">
      <label for="website_id">Default Website to attach any new locations to:</label>
      <select id="website_id" name="website_id" class="form-control">
        <?php
        if (!is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
          $websites = ORM::factory('website')
            ->in('id', $this->auth_filter['values'])
            ->where(array('deleted' => 'f'))
            ->orderby('title', 'asc')->find_all();
        }
        else {
          $websites = ORM::factory('website')->orderby('title', 'asc')->find_all();
          echo '<option value="all" >&lt;Available to all&gt;</option>';
        }
        foreach ($websites as $website) {
          echo '<option value="' . $website->id . '" >' . $website->title . '</option>';
        }
        ?>
      </select>
    </div>
    <div class="form-group">
      <label for="prepend">Optional text to prepend to field identified below to create location name in database</label>
      <input type="text" id="prepend" name="prepend" class="form-control" />
    </div>
    <input type='hidden' name='uploaded_zip' <?php echo 'value="' . $_SESSION['uploaded_zip'] . '"'; ?>/><br/>
    <input type='hidden' name='extracted_basefile' <?php echo 'value="' . $_SESSION['extracted_basefile'] . '"'; ?>/><br/>
    <p>Please indicate which column in the DBF file should be used to identify the name of the location.<br />
    You can also indicate a column to identify a parent location and a code field.</p>
    <table class="table">
      <thead>
        <tr>
          <th>Column in DBF File</th
          ><th>Name?</th>
          <th>Parent Name/Code?</th>
          <th>Code?</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($columns as $col) : ?>
          <?php $colName = $col->getName(); ?>
          <tr>
            <td><?php echo $colName; ?></td>
            <td><input type="radio" class="narrow" value="<?php echo $colName; ?>" name="name" /></td>
            <td><input type="radio" class="narrow" value="<?php echo $colName; ?>" name="parent" /></td>
            <td><input type="radio" class="narrow" value="<?php echo $colName; ?>" name="code" /></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <input type="submit" value="Upload Data" id="upload-button" class="btn btn-primary" />
    <br/>
    <?php
    // We stick these at the bottom so that all the other things will be parsed
    // first.
    foreach ($this->input->post() as $a => $b) {
      echo "<input type=\"radio\" value=\"$b\" name=\"$a\" />";
    }
    ?>
  </fieldset>
</form>

<script type="text/javascript">
$(document).ready(function() {
  $('#use_parent').change(function() {
    if ($('#use_parent:checked').length > 0) {
      $('#ctrl-wrap-parent-link-field').show();
    }
    else {
      $('#ctrl-wrap-parent-link-field').hide();
    }
  });
});
</script>
