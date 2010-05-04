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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/**
 * List of methods that can be used for a prebuilt form map configuration.
 * @package Client
 * @subpackage PrebuiltForms.
 */

/**
 * Return a list of parameter definitions for a form that includes definition of a map and its base layers.
 * @return array List of parameter definitions.
 */
function iform_map_get_map_parameters() {
  return array(
    array(
      'name'=>'map_centroid_lat',
      'caption'=>'Centre of Map Latitude',
      'description'=>'WGS84 Latitude of the initial map centre point, in decimal form.',
      'type'=>'string',
      'group'=>'Initial Map View'
    ),
    array(
      'name'=>'map_centroid_long',
      'caption'=>'Centre of Map Longitude',
      'description'=>'WGS84 Longitude of the initial map centre point, in decimal form.',
      'type'=>'string',
      'group'=>'Initial Map View'
    ),
    array(
      'name'=>'map_zoom',
      'caption'=>'Map Zoom Level',
      'description'=>'Zoom level of the initially displayed map.',
      'type'=>'int',
      'group'=>'Initial Map View'
    ),
    array(
      'name'=>'map_width',
      'caption'=>'Map Width (px)',
      'description'=>'Width in pixels of the map.',
      'type'=>'int',
      'group'=>'Initial Map View',
      'default'=>500
    ),
    array(
      'name'=>'map_height',
      'caption'=>'Map Height (px)',
      'description'=>'Height in pixels of the map.',
      'type'=>'int',
      'group'=>'Initial Map View',
      'default'=>600
    ),
    array(
      'name'=>'preset_layers',
      'caption'=>'Preset Base Layers',
      'description'=>'Select the preset base layers that are available for the map.',
      'type'=>'list',
      'options' => array(
        'google_physical' => 'Google Physical',
        'google_streets' => 'Google Streets',
        'google_hybrid' => 'Google Hybrid',
        'google_satellite' => 'Google Satellite',
        'virtual_earth' => 'Microsoft Virtual Earth',
        'multimap_default' => 'Multimap',
        'multimap_landranger' => 'Multimap with OS Landranger'
      ),
      'group'=>'Base Map Layers',
      'required'=>false
    ),
    array(
      'name' => 'wms_base_title',
      'caption' => 'Additional WMS Base Layer Caption',
      'description' => 'Caption to display for the optional WMS base map layer',
      'type' => 'textfield',
      'group'=>'Base Map Layers',
      'required'=>false
    ),
    array(
      'name' => 'wms_base_url',
      'caption' => 'Additional WMS Base Layer Service URL',
      'description' => 'URL of the WMS service to display for the optional WMS base map layer',
      'type' => 'textfield',
      'group'=>'Base Map Layers',
      'required'=>false
    ),
    array(
      'name' => 'wms_base_layer',
      'caption' => 'Additional WMS Base Layer Name',
      'description' => 'Layername of the WMS service layer for the optional WMS base map layer',
      'type' => 'textfield',
      'group'=>'Base Map Layers',
      'required'=>false
    ),
    array(
      'name' => 'tile_cache_layers',
      'caption' => 'Tile cache JSON',
      'description' => 'JSON describing the tile cache layers to make available. For advanced users only.',
      'type' => 'textarea',
      'group'=>'Advanced Base Map Layers',
      'required'=>false
    ),
    array(
      'name' => 'openlayers_options',
      'caption' => 'OpenLayers Options JSON',
      'description' => 'JSON describing the options to pass through to OpenLayers. For advanced users only, leave blank
          for default behaviour.',
      'type' => 'textarea',
      'group'=>'Advanced Base Map Layers',
      'required'=>false
    )
    
  );

}

/**
 * Return a list of options to pass to the data_entry_helper::map_panel method, built from the prebuilt
 * form arguments.
 * @param $args
 * @param $readAuth
 * @return array Options array for the map.
 */
function iform_map_get_map_options($args, $readAuth) {
  // read out the activated preset layers
  $presetLayers = array();
  foreach($args['preset_layers'] as $layer => $active) {
    if ($active!==0) {
      $presetLayers[] = $layer;
    }
  }
  $options = array(
    'readAuth' => $readAuth,
    'presetLayers' => $presetLayers,
    'editLayer' => true,
    'layers' => array(),
    'initial_lat'=>$args['map_centroid_lat'],
    'initial_long'=>$args['map_centroid_long'],
    'initial_zoom'=>(int) $args['map_zoom'],
    'width'=>(int) $args['map_width'],
    'height'=>(int) $args['map_height']
  );
  // If they have defined a custom base layer, add it
  if ($args['wms_base_title'] && $args['wms_base_url'] && $args['wms_base_layer']) {
    data_entry_helper::$javascript .= "var baseLayer = new OpenLayers.Layer.WMS(
      '".$args['wms_base_title']."',
      '".$args['wms_base_url']."',
      {layers: '".$args['wms_base_layer']."', 'sphericalMercator': true}
    );\n";
    $options['layers'][] = 'baseLayer';
  }
  // Also add any tilecaches they have defined
  if ($args['tile_cache_layers']) {
    $options['tilecacheLayers'] = json_decode($args['tile_cache_layers']);
  }
  return $options;
}

/**
 * Return a list of OpenLayers options to pass to the data_entry_helper::map_panel method, built from the prebuilt
 * form arguments.
 * @param $args
 * @return array Options array for OpenLayers, or null if not specified.
 */
function iform_map_get_ol_options($args) {
  if ($args['openlayers_options']) {
    return json_decode($args['openlayers_options']);
  } else {
    return null;
  }
}