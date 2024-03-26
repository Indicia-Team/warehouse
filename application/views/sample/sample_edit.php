<?php

/**
 * @file
 * View template for the sample edit form.
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
warehouse::loadHelpers(['map_helper', 'data_entry_helper']);
$id = html::initial_value($values, 'sample:id');
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
$site = url::site();
?>
<form action="<?php echo url::site() . 'sample/save' ?>" method="post" id="entry_form">
  <fieldset>
    <legend>Sample Details<?php echo $metadata; ?></legend>
    <input type="hidden" name="sample:id" value="<?php echo html::initial_value($values, 'sample:id'); ?>" />
    <input type="hidden" name="sample:survey_id" value="<?php echo html::initial_value($values, 'sample:survey_id'); ?>" />
    <input type="hidden" name="website_id" value="<?php echo html::initial_value($values, 'website_id'); ?>" />
    <?php
    data_entry_helper::$entity_to_load = $values;
    echo data_entry_helper::text_input([
      'label' => 'Survey',
      'fieldname' => 'survey-label',
      'default' => $model->survey->title,
      'readonly' => TRUE,
    ]);
    $parent_id = html::initial_value($values, 'sample:parent_id');
    if (!empty($parent_id)) {
      echo "<h2>Child of: <a href=\"{$site}sample/edit/$parent_id\">Sample ID $parent_id</a></h2>";
    }
    echo data_entry_helper::date_picker([
      'label' => 'Date',
      'fieldname' => 'sample:date',
      'default' => html::initial_value($values, 'sample:date'),
      'validation' => ['required'],
    ]);
    echo data_entry_helper::sref_and_system([
      'label' => 'Spatial Ref',
      'fieldname' => 'sample:entered_sref',
      'geomFieldname' => 'sample:geom',
      'default' => html::initial_value($values, 'sample:entered_sref'),
      'defaultGeom' => html::initial_value($values, 'sample:geom'),
      'systems' => spatial_ref::system_list(),
      'defaultSystem' => html::initial_value($values, 'sample:entered_sref_system'),
    ]);
    ?>
    <p class="alert alert-info">Zoom the map in by double-clicking then single click on the sample's centre to set the
    spatial reference. The more you zoom in, the more accurate the reference will be.</p>
    <?php
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
      'initialFeatureWkt' => html::initial_value($values, 'sample:geom'),
      'standardControls' => ['layerSwitcher', 'panZoom', 'fullscreen'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Location Name',
      'fieldname' => 'sample:location_name',
      'default' => html::initial_value($values, 'sample:location_name'),
    ]);
    $location_id = html::initial_value($values, 'sample:location_id');
    if (!empty($location_id)) {
      echo "<h2>Associated with location record: <a href=\"{$site}location/edit/$location_id\" >" .
        ORM::factory("location", $location_id)->name . '</a></h2>';
    }
    echo data_entry_helper::autocomplete([
      'label' => 'Location',
      'fieldname' => 'sample:location_id',
      'table' => 'location',
      'captionField' => 'name',
      'valueField' => 'id',
      'extraParams' => $readAuth,
      'default' => $location_id,
      'defaultCaption' => (empty($location_id) ? NULL : html::specialchars($model->location->name)),
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Recorder Names',
      'helpText' => 'Enter the names of the recorders, one per line',
      'fieldname' => 'sample:recorder_names',
      'default' => html::initial_value($values, 'sample:recorder_names'),
    ]);
    echo data_entry_helper::select([
      'label' => 'Sample Method',
      'fieldname' => 'sample:sample_method_id',
      'default' => html::initial_value($values, 'sample:sample_method_id'),
      'lookupValues' => $other_data['method_terms'],
      'blankText' => '<Please select>',
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Comment',
      'fieldname' => 'sample:comment',
      'default' => html::initial_value($values, 'sample:comment'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Training',
      'fieldname' => 'sample:training',
      'default' => html::initial_value($values, 'sample:training'),
      'helpText' => 'Tick if a fake sample for training purposes',
    ]);
    echo data_entry_helper::text_input([
      'label' => 'External Key',
      'fieldname' => 'sample:external_key',
      'default' => html::initial_value($values, 'sample:external_key'),
    ]);
    echo data_entry_helper::select([
      'label' => 'Record status',
      'fieldname' => 'sample:record_status',
      'lookupValues' => [
        'V' => 'Accepted',
        'C' => 'Pending review',
        'R' => 'Not accepted',
        'D' => 'Dubious',
        'T' => 'Test',
        'I' => 'Incomplete',
      ],
      'default' => html::initial_value($values, 'sample:record_status'),
    ]);
    echo data_entry_helper::select([
      'label' => 'Licence',
      'helpText' => 'Licence which applies to all records and media held within this sample.',
      'fieldname' => 'sample:licence_id',
      'default' => html::initial_value($values, 'sample:licence_id'),
      'table' => 'licence',
      'valueField' => 'id',
      'captionField' => 'title',
      'blankText' => '<Please select>',
      'extraParams' => $readAuth,
    ]);
    ?>
  </fieldset>
  <fieldset>
  <legend>Survey specific attributes</legend>
    <ol>
      <?php
      // The $values['attributes'] array has multi-value attributes on separate rows, so organise these into sub array
      $attrsWithMulti = organise_values_attribute_array('sample_attribute', $values['attributes']);
      // Cycle through the attributes and drawn them to the screen
      foreach ($attrsWithMulti as $sampleAttributeId => $wholeAttrToDraw) {
        // Multi-attributes are in a sub array, so the caption is not present at the first level so we can detect this
        if (!empty($wholeAttrToDraw['caption'])) {
          handle_single_value_attributes('smpAttr', $sampleAttributeId, $wholeAttrToDraw, $values);
        } else {
          handle_multi_value_attributes('smpAttr', $sampleAttributeId, $wholeAttrToDraw, $values);
        }
      }
      ?>
    </ol>
  </fieldset>
  <?php
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
