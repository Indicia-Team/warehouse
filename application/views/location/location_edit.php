<?php

/**
 * @file
 * View template for the location edit form.
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

require_once 'application/views/multi_value_data_editing_support.php';
$disabled_input = html::initial_value($values, 'metaFields:disabled_input');
$disabled = ($disabled_input === 'YES') ? 'disabled="disabled"' : '';

$id = html::initial_value($values, 'location:id');
$parent_id = html::initial_value($values, 'location:parent_id');
$boundary_geom = html::initial_value($values, 'location:boundary_geom');
$centroid_geom = html::initial_value($values, 'location:centroid_geom');
warehouse::loadHelpers([
  'map_helper',
  'data_entry_helper',
]);
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
?>
<script type="text/javascript">

jQuery(document).ready(function() {

  if ($('#location\\:public').attr('checked')) {
    $('#websites-list').hide();
  }
    $("#location\\:public").change(function() {
    if ($(this).attr('checked')) {
      $('input:checked[name^="joinsTo\\:website"]').attr('checked', false);
      $('#attrs').hide();
    }
    $('#websites-list').toggle('slow');
  });

});
</script>

<p>
<?php if ($disabled_input === 'YES') : ?>
The location is available to all websites so you don't have permission to change it.
Please contact the warehouse owner to request changes.
<?php else : ?>
This page allows you to specify the details of a location.
<?php endif; ?>
</p>

<form id="entry_form" action="<?php echo url::site() . 'location/save'; ?>" method="post" >
  <div id="details">
    <fieldset>
      <legend>Location details<?php echo $metadata; ?></legend>
      <input type="hidden" name="location:id" value="<?php echo html::initial_value($values, 'location:id'); ?>" />
      <?php
      echo data_entry_helper::text_input([
        'label' => 'Name',
        'fieldname' => 'location:name',
        'default' => $values['location:name'] ?? NULL,
        'validation' => 'required',
        'disabled' => $disabled,
      ]);
      if (!empty($parent_id)) : ?>
        <div class="alert alert-info">
          This location is a child of
          <a href="<?php echo url::site() ?>location/edit/<?php echo $parent_id ?>" >
            <?php echo ORM::factory("location", $parent_id)->name ?>
          </a>
        </div>
      <?php endif;
      echo data_entry_helper::autocomplete([
        'label' => 'Parent location',
        'fieldname' => 'location:parent_id',
        'table' => 'location',
        'captionField' => 'name',
        'valueField' => 'id',
        'extraParams' => $readAuth,
        'default' => html::initial_value($values, 'location:parent_id'),
        'defaultCaption' => html::initial_value($values, 'parent:name'),
        'disabled' => $disabled,
        'helpText' => 'To set the parent of this location, search for the parent by typing the first few characters of its name.',
      ]);
      echo data_entry_helper::textarea([
        'label' => 'Comment',
        'fieldname' => 'location:comment',
        'default' => html::initial_value($values, 'location:comment'),
        'disabled' => $disabled,
      ]);
      ?>
      <div class="row">
        <div class="col-md-4">
          <?php
          echo data_entry_helper::sref_and_system([
            'label' => 'Spatial Ref',
            'fieldname' => 'location:centroid_sref',
            'geomFieldname' => 'location:centroid_geom',
            'default' => html::initial_value($values, 'location:centroid_sref'),
            'defaultGeom' => html::initial_value($values, 'location:centroid_geom'),
            'systems' => spatial_ref::system_list(),
            'defaultSystem' => html::initial_value($values, 'location:centroid_sref_system'),
            'validation' => 'required',
            'disabled' => $disabled,
            'blankText' => '- Please select -',
          ]);
          echo data_entry_helper::text_input([
            'label' => 'Location code',
            'fieldname' => 'location:code',
            'default' => $values['location:code'] ?? NULL,
            'disabled' => $disabled,
          ]);
          echo data_entry_helper::select([
            'label' => 'Location type',
            'fieldname' => 'location:location_type_id',
            'default' => html::initial_value($values, 'location:location_type_id'),
            'lookupValues' => $other_data['type_terms'],
            'blankText' => '<Please select>',
            'disabled' => $disabled,
          ]);
          ?>
        </div>
        <div class="col-md-8">
          <input type="hidden" name="location:boundary_geom" id="imp-boundary-geom" value="<?php echo $boundary_geom; ?>"/>
          <p class="instruct">Zoom the map in by double-clicking then single click on the location's centre to set the
          spatial reference. The more you zoom in, the more accurate the reference will be.</p>
          <?php
          $controls = ['layerSwitcher', 'panZoomBar', 'fullscreen'];
          if ($disabled_input !== 'YES') {
            $controls = array_merge(
              $controls,
              ['drawPolygon', 'drawLine', 'modifyFeature']
            );
          }
          echo map_helper::map_panel([
            'readAuth' => $readAuth,
            'presetLayers' => ['osm'],
            'editLayer' => TRUE,
            'layers' => [],
            'initial_lat' => 52,
            'initial_long' => -2,
            'initial_zoom' => 7,
            'width' => '100%',
            'height' => 400,
            'initialFeatureWkt' => $centroid_geom,
            'standardControls' => $controls,
            'allowPolygonRecording' => TRUE,
          ]);
          ?>
        </div>
      </div>
    </fieldset>
  </div>
  <?php
  // No need to display for public locations unless core admin.
  if (empty($id) || $this->auth->logged_in('CoreAdmin') || ($values['location:public'] === 'f')) :
  ?>
  <div id="websites">
    <fieldset>
      <legend>Location websites</legend>
      <?php
      if ($this->auth->logged_in('CoreAdmin')) {
        // Only core admin can create public locations.
        echo data_entry_helper::checkbox([
          'label' => 'Available to all websites',
          'fieldname' => 'location:public',
          'default' => html::initial_value($values, 'location:public'),
          'disabled' => $disabled,
        ]);
      }
      ?>
      <div id="websites-list">
      <p>This location is available to any website ticked in the list below:</p>
      <ol>
        <?php
        $websiteIds = $this->get_allowed_website_id_list('editor');
        $linkedWebsites = [];
        if (!is_null($websiteIds)) {
          $websites = ORM::factory('website')
            ->in('id', $websiteIds)
            ->where('deleted', 'false')
            ->orderby('title', 'asc')
            ->find_all();
        }
        else {
          $websites = ORM::factory('website')
            ->where('deleted', 'false')
            ->orderby('title', 'asc')
            ->find_all();
        }
        foreach ($websites as $website) {
          echo '<li><label for="website_' . $website->id . '" class="wide">' . $website->title . '</label>';
          echo '<input type="checkbox" name="joinsTo:website:' . $website->id . '" ';
          if (!empty($id)) {
            if (array_key_exists('joinsTo:website:' . $website->id, $values)) {
              echo "checked=\"checked\"";
              $linkedWebsites[] = $website->id;
            }
          }
          echo '></li>';
        }
        ?>
    </ol>
    </fieldset>
  </div>
  <?php endif; ?>
  <?php
  // No need to display for new locations or public locations.
  if (!empty($id) && $values['location:public'] === 'f') :
  ?>
  <div id="attrs">
    <fieldset>
      <legend>Additional Attributes</legend>
      <ol>
        <?php
        // The $values['attributes'] array has multi-value attributes on separate rows, so organise these into sub array
        $attrsWithMulti = organise_values_attribute_array('location_attribute', $values['attributes']);
        // Cycle through the attributes and drawn them to the screen
        foreach ($attrsWithMulti as $locationAttributeId => $wholeAttrToDraw) {
        // Multi-attributes are in a sub array, so the caption is not present at the first level so we can detect this
        if (!empty($wholeAttrToDraw['caption'])) {
            handle_single_value_attributes('locAttr', $locationAttributeId, $wholeAttrToDraw, $values);
          } else {
            handle_multi_value_attributes('locAttr', $locationAttributeId, $wholeAttrToDraw, $values);
          }
        }
        ?>
      </ol>
    </fieldset>
  </div>
  <?php
  endif;
  echo html::form_buttons(html::initial_value($values, 'location:id') != NULL, $disabled_input === 'YES');
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
