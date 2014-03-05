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
  $r = array(
    array(
      'name'=>'map_centroid_lat',
      'caption'=>'Centre of Map Latitude',
      'description'=>'WGS84 Latitude of the initial map centre point, in decimal form. Set to "default" to use the settings '.
          'defined in the IForm Settings page.',
      'type'=>'text_input',
      'group'=>'Initial Map View',
      'default'=>'default'
    ),
    array(
      'name'=>'map_centroid_long',
      'caption'=>'Centre of Map Longitude',
      'description'=>'WGS84 Longitude of the initial map centre point, in decimal form. Set to "default" to use the settings defined in the IForm Settings page.',
      'type'=>'text_input',
      'group'=>'Initial Map View',
      'default'=>'default'
    ),
    array(
      'name'=>'map_zoom',
      'caption'=>'Map Zoom Level',
      'description'=>'Zoom level of the initially displayed map. Set to "default" to use the settings defined in the IForm Settings page.',
      'type'=>'text_input',
      'group'=>'Initial Map View',
      'default'=>'default'
    ),
    array(
      'name'=>'map_width',
      'caption'=>'Map Width',
      'description'=>'Width in pixels of the map, or a css specification for the width, e.g. 75%.',
      'type'=>'text_input',
      'group'=>'Initial Map View',
      'default'=>'100%'
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
      'name'=>'remember_pos',
      'caption'=>'Remember Position',
      'description'=>'Tick this box to get the map to remember it\'s last position when reloading the page. This uses cookies so cookies must be enabled for it to work and '.
          'you must notify your users to ensure you comply with European cookie law.',
      'type'=>'checkbox',
      'required'=>false,
      'group'=>'Initial Map View'
    ),
    array(
      'name' => 'location_boundary_id',
      'caption' => 'Location boundary to draw',
      'description' => 'ID of a location whose boundary should be shown on the map (e.g. to define the perimeter of a survey area).',
      'type' => 'textfield',
      'group'=>'Initial Map View',
      'required'=>false
    ),
    array(
      'name'=>'preset_layers',
      'caption'=>'Preset Base Layers',
      'description'=>'Select the preset base layers that are available for the map. When using Google map layers, please ensure you adhere to the '.
          '<a href="http://code.google.com/apis/maps/terms.html">Google Maps/Google Earth APIs Terms of Service</a>. When using the Bing map layers, '.
          'please ensure that you read and adhere to the <a href="http://www.microsoft.com/maps/product/terms.html">Bing Maps terms of use</a>. '.
          'The Microsoft Virtual Earth layer is now mapped to the Bing Aerial layer so is provided for backwards compatibility only.',
      'type'=>'list',
      'options' => array(
        'google_physical' => 'Google Physical',
        'google_streets' => 'Google Streets',
        'google_hybrid' => 'Google Hybrid',
        'google_satellite' => 'Google Satellite',
        'virtual_earth' => 'Microsoft Virtual Earth',
        'bing_aerial' => 'Bing Aerial',
        'bing_hybrid' => 'Bing Hybrid',
        'bing_shaded' => 'Bing Shaded',
        'osm' => 'OpenStreetMap',
        'osm_th' => 'OpenStreetMap Tiles@Home'
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
    ),
    array(
      'name' => 'indicia_wms_layers',
      'caption' => 'WMS layers from GeoServer',
      'description' => 'List of WMS feature type names, one per line, which are installed on the GeoServer and are to be added to the map as overlays.',
      'type' => 'textarea',
      'group'=>'Other Map Settings',
      'required'=>false
    ),
    array(
      'name' => 'standard_controls',
      'caption' => 'Controls to add to map',
      'description' => 'List of map controls, one per line. Select from layerSwitcher, zoomBox, panZoom, panZoomBar, drawPolygon, drawPoint, drawLine, '.
         'hoverFeatureHighlight, clearEditLayer, modifyFeature, graticule. If using a data entry form and you add drawPolygon or drawLine controls then your '.
         'form will support recording against polygons and lines as well as grid references and points.',
      'type' => 'textarea',
      'group'=>'Other Map Settings',
      'required'=>false,
      'default'=>"layerSwitcher\npanZoomBar"
    )
  );
  // Check for easy login module to allow integration into profile locations. If no module_exists function then 
  // we are in the AJAX call for the form details for a new iform, so must show it as we have no way of knowing.
  if (!function_exists('module_exists') || module_exists('easy_login')) {
    $r[] = array(
      'name'=>'display_user_profile_location',
      'caption'=>'Display location from user profile',
      'description'=>'Tick this box to display the outline of the user\'s preferred recording location from the user '.
          'account on the map. The map will be centred and zoomed to this location on first usage. This option has no effect if '.
          '"Location boundary to draw" is ticked.',
      'type'=>'checkbox',
      'required'=>false,
      'group'=>'Initial Map View'
    );
  }
  return $r;
}

/**
 * Return a list of parameter definitions for a form that includes definition of a georeference_lookup control.
 * @return array List of parameter definitions.
 */
function iform_map_get_georef_parameters() {
  return array(
    array(
      'name'=>'georefPreferredArea',
      'caption'=>'Preferred area for georeferencing.',
      'description'=>'Preferred area to look within when trying to resolve a place name. For example set this to the region name you are recording within. Can be left blank to not specify '.
          'in which case users can add a comma plus the region to search if needed, e.g. "wimborne, Dorset".',
      'type'=>'string',
      'default'=>'',
      'group'=>'Georeferencing',
      'siteSpecific'=>true,
      'required'=>false
    ),
    array(
      'name'=>'georefCountry',
      'caption'=>'Preferred country for georeferencing.',
      'description'=>'Preferred country to look within when trying to resolve a place name. Can be left blank to not specify, in which case users can add a comma then the country to search. ',
      'type'=>'string',
      'default'=>'United Kingdom',
      'group'=>'Georeferencing',
      'siteSpecific'=>true,
      'required'=>false
    ),
    array(
      'name'=>'georefDriver',
      'caption'=>'Web service used for georeferencing',
      'description'=>'Choose the web service used for resolving place names to points on the map. Each web-service has a '.
           'different set of characteristics. If you are unsure which to use, the Yahoo! GeoPlanet service is a good starting point.',
      'type'=>'select',
      'default'=>'geoplanet',
      'options' => array(
        'geoplanet' => 'Yahoo! GeoPlanet (all round place search)',
        'google_search_api' => 'Google AJAX Search API (works well with postcodes or for places near the preferred area). Note this API is deprecated and may not be supported in future.',
        'geoportal_lu' => 'ACT Geoportal Luxembourg (for places in Luxumbourg)',
        'indicia_locations' => 'Search the Indicia locations list.'
      ),
      'group'=>'Georeferencing'
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
  if (!empty($args['preset_layers'])) {
    foreach($args['preset_layers'] as $key => $value) {
      if (is_int($key)) {
        // normally a checkbox group would just output an array
        $presetLayers[] = $value;
      } elseif ($value!==0) {
        // but the Drupal version of the the parameters form (deprecated) leaves a strange array structure in the parameter value.
        $presetLayers[] = $key;
      }
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
    'width'=>$args['map_width'],
    'height'=>$args['map_height'],
    'standardControls'=>array('layerSwitcher','panZoomBar'),
    'rememberPos'=>isset($args['remember_pos']) ? ($args['remember_pos']==true) : false
  );
  // If they have defined a custom base layer, add it
  if ($args['wms_base_title'] && $args['wms_base_url'] && $args['wms_base_layer']) {
    data_entry_helper::$onload_javascript .= "var baseLayer = new OpenLayers.Layer.WMS(
      '".$args['wms_base_title']."',
      '".$args['wms_base_url']."',
      {layers: '".$args['wms_base_layer']."', sphericalMercator: true}, {singleTile: true}
    );\n";
    $options['layers'][] = 'baseLayer';
  }
  // Also add any tilecaches they have defined
  if ($args['tile_cache_layers']) {
    $options['tilecacheLayers'] = json_decode($args['tile_cache_layers'], true);
  }
  // And any indicia Wms layers from the GeoServer
  if ($args['indicia_wms_layers']) {
    $options['indiciaWMSLayers'] = explode("\n", $args['indicia_wms_layers']);
  }
  // set up standard control list if supplied
  if (array_key_exists('standard_controls', $args) && $args['standard_controls']) {
    $args['standard_controls'] = str_replace("\r\n", "\n", $args['standard_controls']);
    $options['standardControls']=explode("\n", $args['standard_controls']);
    // If drawing controls are enabled, then allow polygon recording.
    if (in_array('drawPolygon', $options['standardControls']) || in_array('drawLine', $options['standardControls']))
      $options['allowPolygonRecording']=true;
  }
  // And pass through any translation strings, only if they exist
  $msgGeorefSelectPlace = lang::get('LANG_Georef_SelectPlace');
  if ($msgGeorefSelectPlace!='LANG_Georef_SelectPlace') $options['msgGeorefSelectPlace'] = $msgGeorefSelectPlace;
  $msgGeorefNothingFound = lang::get('LANG_Georef_NothingFound');
  if ($msgGeorefNothingFound!='LANG_Georef_NothingFound') $options['msgGeorefNothingFound'] = $msgGeorefNothingFound;
  // if in Drupal, and IForm proxy is installed, then use this path as OpenLayers proxy
  if (defined('DRUPAL_BOOTSTRAP_CONFIGURATION') && module_exists('iform_proxy')) {
    global $base_url;
    $options['proxy'] = $base_url . '/?q=' . variable_get('iform_proxy_path', 'proxy') . '&url=';
  }
  // And a single location boundary if defined
  if (!empty($args['location_boundary_id'])) 
    $location = $args['location_boundary_id'];
  elseif (isset($args['display_user_profile_location']) && $args['display_user_profile_location']) {
    $location = hostsite_get_user_field('location');
  }
  if (!empty($location)) {
    iform_map_zoom_to_location($location, $readAuth);
  }
  return $options;
}

/**
 * Adds a vector to the map for a particular location, and zooms into it.
 */
function iform_map_zoom_to_location($locationId, $readAuth) {
  $getPopDataOpts = array(
    'table' => 'location',
    'extraParams' => $readAuth + array('id'=>$locationId,'view' => 'detail')
  );
  $response = data_entry_helper::get_population_data($getPopDataOpts);
  $geom = $response[0]['boundary_geom'] ? $response[0]['boundary_geom'] : $response[0]['centroid_geom'];
  // Note, since the following moves the map, we want it to be the first mapInitialisationHook
  data_entry_helper::$javascript .= "
mapInitialisationHooks.unshift(function(mapdiv) {
  var parser, feature, loclayer = new OpenLayers.Layer.Vector(
    '".lang::get('My Preferred Locality')."',
    {'sphericalMercator': true, displayInLayerSwitcher: true}
  );
  parser = new OpenLayers.Format.WKT();
  feature = parser.read('".$geom."');
  feature.style = {fillOpacity: 0, strokeColor: '#0000ff', strokeWidth: 2};  
  feature.style.fillOpacity=0;
  loclayer.addFeatures([feature]);
  // Don't zoom to the locality if the map is set to remember last position
  var bounds=feature.geometry.getBounds();
  if (typeof $.cookie === 'undefined' || mapdiv.settings.rememberPos===false || $.cookie('maplon')===null) {
    if (mapdiv.map.getZoomForExtent(bounds) > mapdiv.settings.maxZoom) {
      // if showing something small, don't zoom in too far
      mapdiv.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
    }
    else {
      // Set the default view to show the feature we are loading
      //mapdiv.map.zoomToExtent(bounds, true);
      mapdiv.map.setCenter(bounds.getCenterLonLat(), mapdiv.map.getZoomForExtent(bounds));
    }  
  }
  mapdiv.map.addLayer(loclayer);
});\n";
}

/**
 * Return a list of OpenLayers options to pass to the data_entry_helper::map_panel method, built from the prebuilt
 * form arguments.
 * @param $args
 * @return array Options array for OpenLayers, or null if not specified.
 */
function iform_map_get_ol_options($args) {
  if ($args['openlayers_options']) {
    $opts = json_decode($args['openlayers_options'], true);
  } else {
    $opts = array();
  }
  if (!isset($opts['theme']))
    $opts['theme'] = data_entry_helper::$js_path . 'theme/default/style.css';
  return $opts;
}

/**
 * Return a list of options to pass to the data_entry_helper::georeference_lookup method, built from the prebuilt
 * form arguments.
 * @param $args
 * @param $readAuth
 * @return array Options array for the georeferencer.
 */
function iform_map_get_georef_options($args, $readAuth) {
  return array(
    'driver'=>$args['georefDriver'],
    'label' => lang::get('LANG_Georef_Label'),
    'georefPreferredArea' => $args['georefPreferredArea'],
    'georefCountry' => $args['georefCountry'],
    'georefLang' => $args['language'],
    'readAuth' => $readAuth
  );
}