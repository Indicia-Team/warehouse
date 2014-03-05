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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

require_once('helper_base.php');
 
/**
 * Static helper class that provides methods for dealing with maps.
 * @package	Client
 */
class map_helper extends helper_base {
 
  /**
  * Outputs a map panel.
  * The map panel can be augmented by adding any of the following controls which automatically link themselves
  * to the map:
  * <ul>
  * <li>{@link sref_textbox()}</li>
  * <li>{@link sref_system_select()}</li>
  * <li>{@link sref_and_system()}</li>
  * <li>{@link georeference_lookup()}</li>
  * <li>{@link location_select()}</li>
  * <li>{@link location_autocomplete()}</li>
  * <li>{@link postcode_textbox()}</li>
  * </ul>
  * To run JavaScript at the end of map initialisation, add a function to the global array
  * called mapInitialisationHooks. Code cannot access the map at any previous point because
  * maps may not be initialised when the page loads, e.g. if the map initialisation is
  * delayed until the tab it is on is shown.
  * To run JavaScript which updates any of the map settings, add a function to the
  * mapSettingsHooks global array. For example this is used to configure the map by report
  * parameters panels which need certain tools on the map.
  * @param array $options Associative array of options to pass to the jQuery.indiciaMapPanel plugin.
  * Has the following possible options:
  * <ul><li><b>indiciaSvc</b><br/>
  * </li>
  * <li><b>indiciaGeoSvc</b><br/>
  * </li>
  * <li><b>readAuth</b><br/>
  * Provides read authentication tokens for the warehouse. Only required when there is a location control linked to the warehouse associated with this map.
  * </li>
  * <li><b>height</b><br/>
  * Height of the map panel, in pixels.
  * </li>
  * <li><b>width</b><br/>
  * Width of the map panel, in pixels or as a percentage if followed by a % symbol.
  * </li>
  * <li><b>initial_lat</b><br/>
  * Latitude of the centre of the initially displayed map, using WGS84.
  * </li>
  * <li><b>initial_long</b><br/>
  * Longitude of the centre of the initially displayed map, using WGS84.
  * </li>
  * <li><b>initial_zoom</b><br/>
  * </li>
  * <li><b>scroll_wheel_zoom</b><br/>
  * Does the scroll wheel zoom the map in and out when over the map? Defaults to true. When using the scroll wheel to look up and down a data entry form
  * it can be easy to inadvertantly scroll the map, so it may be desirable to disable this feature in some cases.
  * </li>
  * <li><b>proxy</b><br/>
  * </li>
  * <li><b>displayFormat</b><br/>
  * </li>
  * <li><b>presetLayers</b><br/>
  * Array of preset layers to include. Options are 'google_physical', 'google_streets', 'google_hybrid',
  * 'google_satellite', 'openlayers_wms', 'nasa_mosaic', 'virtual_earth' (deprecated, use bing_aerial),
  * 'bing_aerial', 'bing_hybrid, 'bing_shaded', 'osm' (for OpenStreetMap), 'osm_th' (for OpenStreetMap Tiles@Home).
  * </li>
  * <li><b>tilecacheLayers</b><br/>
  * Array of layer definitions for tilecaches, which are pre-cached background tiles. They are less flexible but much faster
  * than typical WMS services. The array is associative, with the following keys:
  *   caption - The display name of the layer
  *   servers - array list of server URLs for the cache
  *   layerName - the name of the layer within the cache
  *   settings - any other settings that need to be passed to the tilecache, e.g. the server resolutions or file format.</li>
  * <li><b>indiciaWMSLayers</b><br/>
  * </li>
  * <li><b>indiciaWFSLayers</b><br/>
  * </li>
  * <li><b>layers</b><br/>
  * An array of JavaScript variables which point to additional OpenLayers layer objects to add to the map. The JavaScript for creating these layers 
  * can be added to data_entry_helper::$onload_javascript before calling the map_panel method.
  * </li>
  * <li><b>clickableLayers</b><br/>
  * If support for clicking on a layer to provide info on the clicked objects is required, set this to an array containing the JavaScript variable
  * names for the OpenLayers WMS layer objects you have created for the clickable layers. The JavaScript for creating these layers 
  * can be added to data_entry_helper::$onload_javascript before calling the map_panel method and they can be the same layers as those referred to in 
  * the layers parameter.
  * </li>
  * <li><b>clickableLayersOutputMode</b><br/>
  * Set to popup to display the information retrieved from a click operation on a popup window, set to div to
  * display the information in a specified HTML div, or to customFunction to call a JavaScript function
  * after the click operation allowing custom functionality such as navigation to another page. Default 
  * is popup.
  * </li>
  * <li><b>clickableLayersOutputDiv</b><br/>
  * ID of the HTML div to output information retrieved from a click operation into, if clickableLayersOutputMode
  * is set to div.
  * </li>
  * <li><b>allowBox</b><br/>
  * Default true. Set to false to disable drag boxes for selecting items on clickable layers. The advantage of this is that
  * the drag boxes don't hinder attempts to drag the map to navigate.
  * </li>
  * <li><b>customClickFn</b>
  * Set to the name of a global custom JavaScript function which will handle the event of clicking on the map if 
  * you want custom functionality. Provide this when clickableLayersOutputMode=customFunction. The function will 
  * receive a single parameter containing an array of features.
  * </li>
  * <li><b>clickableLayersOutputFn</b><br/>
  * Allows overridding of the appearance of the output when clicking on the map for WMS or vector layers. Should be set to a 
  * JavaScript function name which takes a list of features and the map div as parameters, then returns the HTML to output.</li>
  * <li><b>clickableLayersOutputColumns</b><br/>
  * An associated array of column field names with column titles as the values which defines the columns that are output when clicking on a data point. 
  * If ommitted, then all columns are output using their original field names.
  * </li>
  * <li><b>locationLayerName</b><br/>
  * If using a location select or autocomplete control, then set this to the name of a feature type exposed on GeoServer which contains the id, name and boundary
  * geometry of each location that can be selected. Then when the user clicks on the map the system is able to automatically populate the locations control with the 
  * clicked on location. Ensure that the feature type is styled on GeoServer to appear as required, though it will be added to the map with semi-transparency. To use
  * this feature ensure that a proxy is set, e.g. by using the Indicia Proxy module in Drupal.
  * </li>
  * <li><b>locationLayerFilter</b><br/>
  * If using a location layer, then set this to a cql filter in order to select e.g. locations for a website or locations of a type.
  * The filter can act on any fields in the feature type that locationLayerName refers to.
  * </li>
  * <li><b>controls</b><br/>
  * </li>
  * <li><b>toolbarDiv</b><br/>
  * If set to 'map' then any required toolbuttons are output directly onto the map canvas (in the top right corner). Alternatively can be set to 'top',
  * 'bottom' or the id of a div on the page to output them into.
  * </li>
  * <li><b>toolbarPrefix</b><br/>
  * Content to include at the beginning of the map toolbar. Not applicable when the toolbar is added directly to the map.
  * </li>
  * <li><b>toolbarSuffix</b><br/>
  * Content to include at the end of the map toolbar. Not applicable when the toolbar is added directly to the map.
  * </li>
  * <li><b>helpDiv</b><br/>
  * Set to 'bottom' to add a div containing help hints below the map. Set to the name of a div to output help hints into that
  * div. Otherwise no help hints are displayed.
  * </li>
  * <li><b>clickForSpatialRef</b><br/>
  * Does clicking on the map set the spatial reference of the sample input controls on the form the map appears on (if any)?
  * Defaults to true.
  * </li>
  * <li><b>allowPolygonRecording</b><br/>
  * If a drawPolygon or drawLine control is present, do these set the spatial reference of the sample input controls on the form the map appears on (if any)?
  * The spatial ref is set to the polygon centroid and the sample geometry is set to the polygon itself allowing polygons for records.
  * </li>
  * <li><b>editLayer</b><br/>
  * </li>
  * <li><b>editLayerName</b><br/>
  * </li>
  * <li><b>standardControls</b>
  * An array of predefined controls that are added to the map. Select from:<br/>
  *    layerSwitcher - a button in the corner of the map which opens a panel allowing selection of the visible layers.<br/>
  *    drawPolygon - a tool for drawing polygons onto the map edit layer.<br/>
  *    drawLine - a tool for drawing lines onto the map edit layer.<br/>
  *    drawPoint - a tool for drawing points onto the map edit layer.<br/>
  *    zoomBox - allow zooming to a bounding box, drawn whilst holding the shift key down. This functionality is provided by the panZoom and panZoomBar controls as well
  *    so is only relevant when they are not selected. 
  *    panZoom - simple controls in the corner of the map for panning and zooming.
  *    panZoomBar - controls in the corner of the map for panning and zooming, including a slide bar for zooming.
  *    modifyFeature - a tool for selecting a feature on the map edit layer then editing the vertices of the feature.
  *    selectFeature - a tool for selecting a feature on the map edit layer.
  *    hoverFeatureHighlight - highlights the feature on the map edit layer which is under the mouse cursor position.
  * Default is layerSwitcher, panZoom and graticule.
  * </li>
  * <li><b>initialFeatureWkt</b><br/>
  * Well known text for a geometry to load onto the map at startup, normally corresponding to the geometry of the record
  * being edited.
  * </li>
  * <li><b>initialBoundaryWkt</b><br/>
  * Well known text for a geometry to load onto the map at startup, normally corresponding to the geometry of the boundary
  * being edited (e.g. a site boundary).
  * </li>
  * <li><b>defaultSystem</b><br/>
  * </li>
  * <li><b>latLongFormat</b><br/>
  * Override the format for display of lat long references. Select from D (decimal degrees, the default), DM (degrees and decimal minutes)
  * or DMS (degrees, minutes and decimal seconds).</li>
  * <li><b>srefId</b><br/>
  * Override the id of the control that has the grid reference value
  * </li>
  * <li><b>srefSystemId</b><br/>
  * Override the id of the control that has the spatial reference system value
  * </li>
  * <li><b>geomId</b><br/>
  * </li>
  * <li><b>clickedSrefPrecisionMin</b><br/>
  * Specify the minimum precision allowed when clicking on the map to get a grid square. If not set then the grid square selected will increase to its maximum
  * size as the map is zoomed out. E.g. specify 4 for a 1km British National Grid square.
  * </li>
  * <li><b>clickedSrefPrecisionMax</b><br/>
  * Specify the maximum precision allowed when clicking on the map to get a grid square. If not set then the grid square selected will decrease to its minimum
  * size as the map is zoomed in. E.g. specify 4 for a 1km British National Grid square.
  * </li>
  * <li><b>msgGeorefSelectPlace</b><br/>
  * </li>
  * <li><b>msgGeorefNothingFound</b><br/>
  * </li>
  * <li><b>msgSrefOutsideGrid</b><br/>
  * Message displayed when point outside of grid reference range is clicked.
  * </li>
  * <li><b>msgSrefNotRecognised</b><br/>
  * Message displayed when a grid reference is typed that is not recognised.
  * </li>
  * <li><b>maxZoom</b><br/>
  * Limit the maximum zoom used when clicking on the map to set a point spatial reference. Use this to prevent over zooming on
  * background maps.</li>
  * <li><b>tabDiv</b><br/>
  * If loading this control onto a set of tabs, specify the tab control's div ID here. This allows the control to
  * automatically generate code which only generates the map when the tab is shown.</li>
  * <li><b>setupJs</b><br/>
  * When there is JavaScript to run before the map is initialised, put the JavaScript into this option. This allows the map to run the 
  * setup JavaScript just in time, immediately before the map is created. This avoids problems where the setup JavaScript causes the OpenLayers library 
  * to be initialised too earlier if the map is on a div.</li>
  * <li><b>graticuleProjection</b><br/>
  * EPSG code (including EPSG:) for the projection used for the graticule (grid overlay).</li>
  * <li><b>graticuleBounds</b><br/>
  * Array of the bounding box coordinates for the graticule(W,S,E,N).</li>
  * <li><b>rememberPos</b><br/>
  * Set to true to enable restoring the map position when the page is reloaded. Requires jquery.cookie plugin. As this feature
  * requires cookies, you should notify your users in compliance with European cookie law if you use this option.</li>
  * </ul>
  * <li><b>helpDiv</b><br/>
  * Set to bottom to output a help div under the map, or set to the ID of a div to output into.</li>
  * <li><b>helpToPickPrecisionMin</b><br/>
  * Set to a precision in metres (e.g. 10, 100, 1000) to provide help guiding the recorder to pick a grid square of at least that precision. Ensure that helpDiv is 
  * set when using this option.</li>
  * <li><b>helpToPickPrecisionMax</b><br/>
  * Set to a precision in metres (e.g. 10, 100, 1000) that the help system will accept as not requiring further refinement when a grid square of this precision is picked.</li>  
  * <li><b>helpToPickPrecisionSwitchAt</b><br/>
  * Set to a precision in metres (e.g. 10, 100, 1000) that the map will switch to the satellite layer (if Google or Bing satellite layers active) when
  * the recorder picks a grid square of at least that precision.</li>
  * <li><b>gridRefHint</b><br/>
  * Set to true to put the currently hovered over grid ref in an element with id grid-ref-hint. Use the next setting to automate adding this to the page.</li>
  * <li><b>gridRefHintInFooter</b><br/>
  * Defaults to true. If there is a grid ref hint, should it go in the footer area of the map? If so, there is no need to add an element id grid-ref-hint to the page.</li>
  * </ul>
  * @param array $olOptions Optional array of settings for the OpenLayers map object. If overriding the projection or
  * displayProjection settings, just pass the EPSG number, e.g. 27700.
  */
  public static function map_panel($options, $olOptions=null) {
    if (!$options) {
      return '<div class="error">Form error. No options supplied to the map_panel method.</div>';
    } else {
      global $indicia_templates;
      $presetLayers = array();
      // If the caller has not specified the background layers, then default to the ones we have an API key for
      if (!array_key_exists('presetLayers', $options)) {
        $presetLayers[] = 'google_satellite';
        $presetLayers[] = 'google_hybrid';
        $presetLayers[] = 'google_physical';
        $presetLayers[] = 'virtual_earth';
        $presetLayers[] = 'osm';
      }
      if (!empty(parent::$warehouse_proxy))
        $warehouseUrl = parent::$warehouse_proxy;
      else
        $warehouseUrl = parent::$base_url;
      $options = array_merge(array(
          'indiciaSvc'=>$warehouseUrl,
          'indiciaGeoSvc'=>self::$geoserver_url,
          'divId'=>'map',
          'class'=>'',
          'width'=>600,
          'height'=>470,
          'presetLayers'=>$presetLayers,
          'jsPath'=>self::$js_path,
          'clickForSpatialRef'=>true,
          'gridRefHintInFooter'=>true,
          'gridRefHint'=>false
      ), $options);
      // When using tilecache layers, the open layers defaults cannot be used. The caller must take control of openlayers settings
      if (isset($options['tilecacheLayers'])) {
        $options['useOlDefaults'] = false;
      }

      //width and height may be numeric, which is interpreted as pixels, or a css string, e.g. '50%'
      //width in % is causing problems with panning in Firefox currently. 13/3/2010.

      if (is_numeric($options['height']))
        $options['height'] .= 'px';
      if (is_numeric($options['width']))
        $options['width'] .= 'px';

      if (array_key_exists('readAuth', $options)) {
        // Convert the readAuth into a query string so it can pass straight to the JS class.
        $options['readAuth']='&'.self::array_to_query_string($options['readAuth']);
        str_replace('&', '&amp;', $options['readAuth']);
      }

      // convert textual true/false to boolean equivalents.
      foreach($options as $key=>$value){
        if($options[$key]==="false") $options[$key]=false;
        else if($options[$key]==="true") $options[$key]=true;
      }

      // Autogenerate the links to the various mapping libraries as required
      if (array_key_exists('presetLayers', $options)) {
        foreach ($options['presetLayers'] as $layer)
        {
          $a = explode('_', $layer);
          $a = strtolower($a[0]);
          switch($a)
          {
            case 'google':
              self::add_resource('googlemaps');
              break;
            case 'virtual':
              self::add_resource('virtualearth');
              break;
          }
          if ($a=='bing' && (!isset(self::$bing_api_key) || empty(self::$bing_api_key)))
            return '<p class="error">To use the Bing layers, please ensure that you declare a variable called $bing_api_key in the helper_config.php file set to an '.
                'empty string and specify a Bing API Key on the IForm settings page.</p>';
        }
      }

      // This resource has a dependency on the googlemaps resource so has to be added afterwards.
      self::add_resource('indiciaMapPanel');
      if (array_key_exists('standardControls', $options)) {
        if (in_array('graticule', $options['standardControls']))
          self::add_resource('graticule');
        if (in_array('clearEditLayer', $options['standardControls']))
          self::add_resource('clearLayer');
      }
      // We need to fudge the JSON passed to the JavaScript class so it passes any actual layers, functions
      // and controls, not the string class names.
      $json_insert='';
      $js_entities=array('controls', 'layers', 'clickableLayers');
      foreach($js_entities as $entity) {
        if (array_key_exists($entity, $options)) {
          $json_insert .= ',"'.$entity.'":['.implode(',', $options[$entity]).']';
          unset($options[$entity]);
        }
      }
      // Same for 'clickableLayersOutputFn' 
      if (isset($options['clickableLayersOutputFn'])) {
        $json_insert .= ',"clickableLayersOutputFn":'.$options['clickableLayersOutputFn'];
        unset($options['clickableLayersOutputFn']);
      }
      
      // Same for 'customClickFn' 
      if (isset($options['customClickFn'])) {
        $json_insert .= ',"customClickFn":'.$options['customClickFn'];
        unset($options['customClickFn']);
      }
      
      // make a copy of the options to pass into JavaScript, with a few entries removed.
      $jsoptions = array_merge($options);
      unset($jsoptions['setupJs']);
      unset($jsoptions['tabDiv']);
      if (isset(self::$bing_api_key))
        $jsoptions['bing_api_key'] = self::$bing_api_key;
      $json=substr(json_encode($jsoptions), 0, -1).$json_insert.'}';
      if ($olOptions) {
        $json .= ','.json_encode($olOptions);
      }
      $javascript = '';
      $mapSetupJs = '';
      if (isset($options['setupJs'])) {
        $mapSetupJs .= $options['setupJs']."\n";
      }
      $mapSetupJs .= "jQuery('#".$options['divId']."').indiciaMapPanel($json);\n";
      // trigger a change event on the sref if it's set in case locking in use. This will draw the polygon on the map.
      $srefId = empty($options['srefId']) ? '$.fn.indiciaMapPanel.defaults.srefId' : "'{$options['srefId']}'";
      if(!(isset($options['switchOffSrefRetrigger']) && $options['switchOffSrefRetrigger'] == true)){
        $mapSetupJs .= "      var srefId = $srefId;\n".
                       "      if (srefId && $('#' + srefId).length && $('#' + srefId).val()!=='' && indiciaData.mapdiv.settings.initialBoundaryWkt===null) {jQuery('#'+srefId).change();}\n";
      }
      // If the map is displayed on a tab, so we must only generate it when the tab is displayed as creating the 
      // map on a hidden div can cause problems. Also, the map must not be created until onload or later. So 
      // we have to set use the mapTabLoaded and windowLoaded to track when these events are fired, and only
      // load the map when BOTH the events have fired.
      if (isset($options['tabDiv'])) {
        $divId = preg_replace('/[^a-zA-Z0-9]/', '', $options['divId']);
        $javascript .= "var mapTabHandler = function(event, ui) { \n";
        $javascript .= "  if (ui.panel.id=='".$options['tabDiv']."' && typeof indiciaData.mapdiv !== 'undefined') {\n";
        $javascript .= "    indiciaData.mapdiv.map.updateSize();\n";
        $javascript .= "  }\n\n};\n";
        $javascript .= "jQuery(jQuery('#".$options['tabDiv']."').parent()).bind('tabsshow', mapTabHandler);\n";
        // Insert this script at the beginning, because it must be done before the tabs are initialised or the 
        // first tab cannot fire the event
        self::$javascript = $javascript . self::$javascript;
      }
      $options['suffixTemplate']='blank';
      self::$onload_javascript .= $mapSetupJs;
      $r = str_replace('{content}', self::apply_template('map_panel', $options), $indicia_templates['jsWrap']);
      if ($options['gridRefHintInFooter'] && $options['gridRefHint']) {
        $div = '<div id="map-footer" class="grid-ref-hints ui-helper-clearfix" style="width: '.$options['width'].'" ' .
            'title="When you hover the mouse over the map, the grid reference is displayed here. Hold the minus key or plus key when clicking on the map ' .
            'to decrease or increase the grid square precision respectively.">';
        if ($options['clickForSpatialRef'])
          $r .= $div . '<h3>' . lang::get('Click to set map ref') . '</h3>' .
              '<div class="grid-ref-hint hint-minus">' .
                  '<span class="label"></span><span class="data"></span> <span>('.lang::get('hold -').')</span></div>' .
              '<div class="grid-ref-hint hint-normal"><span class="label"> </span><span class="data"></span></div>' .
              '<div class="grid-ref-hint hint-plus">' .
                  '<span class="label"></span><span class="data"></span><span>('.lang::get('hold +').')</span></div>';
        else
          $r .= $div . '<h3>' . lang::get('Map ref at pointer') . '</h3>' .
              '<div class="grid-ref-hint hint-normal"><span class="label"></span><span class="data"></span></div>';
        $r .= '</div>';
      }
      return $r;
    }
  }
  
 /**
  * Outputs a map layer list panel which automatically integrates with the map_panel added to the same page. The list by default will 
  * behave like a map legend, showing an icon and caption for each visible layer, but can be configured to show all hidden layers 
  * and display a checkbox or radio button alongside each item, making it into a layer switcher panel.
  *
  * @param array $options Associative array of options to pass to the jQuery.indiciaMapPanel plugin.
  * Has the following possible options:
  * <ul>
  * <li><b>id</b><br/>
  * Optional CSS id for the output panel.
  * </li>
  * <li><b>includeIcons</b><br/>
  * Set to true to include icons alongside each layer item. Default true.
  * </li>
  * <li><b>includeSwitchers/b><br/>
  * Set to true to include radio buttons and/or checkboxes for switching on or off the visible base 
  * layers and overlays. Default false.
  * </li>
  * <li><b>includeHiddenLayers</b><br/>
  * True or false to include layers that are not currently visible on the map. Default is false.
  * </li>
  * <li><b>layerTypes</b><br/>
  * Array of layer types to include, options are base or overlay. Default is both.
  * </li>
  * <li><b>class</b><br/>
  * Class to add to the outer div.
  * </li>
  * </ul>
  */
  public static function layer_list($options) {
    $options = array_merge(array(
      'id' => 'layers',
      'includeIcons' => true,
      'includeSwitchers' => false,
      'includeHiddenLayers' => false,
      'layerTypes' => array('base','overlay'),
      'class'=>'',
      'prefix'=>'',
      'suffix'=>''
    ), $options);
    $options['class'] .= (empty($options['class']) ? '' : ' ').'layer_list';
    $r = '<div class="'.$options['class'].'" id="'.$options['id'].'" class="ui-widget ui-widget-content ui-corner-all">';
    $r .= $options['prefix']."\n<ul>";
    $r .= "</ul>\n".$options['suffix']."</div>";
    $funcSuffix = str_replace('-','_',$options['id']);
    self::$javascript .= "function getLayerHtml_$funcSuffix(layer, div) {\n  ";
    if (!$options['includeHiddenLayers']) 
      self::$javascript .= "if (!layer.visibility) {return '';}\n  ";
    if (!in_array('base', $options['layerTypes']))
      self::$javascript .= "if (layer.isBaseLayer) {return '';}\n  ";
    if (!in_array('overlay', $options['layerTypes']))
      self::$javascript .= "if (!layer.isBaseLayer) {return '';}\n  ";
    self::$javascript .= "var layerHtml = '<li id=\"'+layer.id.replace(/\./g,'-')+'\">';\n  ";
    if ($options['includeSwitchers']) {
      self::$javascript .= "
  if (!layer.displayInLayerSwitcher) { return ''; }
  var type='', name='';
  if (layer.isBaseLayer) {
    type='radio';
    name='base-".$options['id']."';
  } else {
    type='checkbox';
    name='base-'+layer.id.replace(/\./g,'-');
  }
  var checked = layer.visibility ? ' checked=\"checked\"' : '';
  layerHtml += '<input type=\"' + type + '\" name=\"' + name + '\" class=\"layer-switcher\" id=\"switch-'+layer.id.replace(/\./g,'-')+'\" ' + checked + '/>';\n  ";
    }
    if ($options['includeIcons']) 
      self::$javascript .= "if (layer.isBaseLayer) {
    layerHtml += '<img src=\"".self::getRootFolder() . self::client_helper_path()."../media/images/map.png\" width=\"16\" height=\"16\"/>';
  } else if (layer instanceof OpenLayers.Layer.WMS) {
    layerHtml += '<img src=\"' + layer.url + '?SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic&WIDTH=16&HEIGHT=16&LAYER='+layer.params.LAYERS+'&Format=image/jpeg'+
      '&STYLE='+layer.params.STYLES +'\" alt=\"'+layer.name+'\"/>';
  } else if (layer instanceof OpenLayers.Layer.Vector) {
    var style=layer.styleMap.styles['default']['defaultStyle'];
    layerHtml += '<div style=\"border: solid 1px ' + style.strokeColor +'; background-color: ' + style.fillColor + '\"> </div>';
  } else {
    layerHtml += '<div></div>';
  }\n";
    self::$javascript .= "  layerHtml += '<span class=\"layer-title\">' + layer.name + '</span>';
  return layerHtml;
}
    
function refreshLayers_$funcSuffix(div) {
  $('#".$options['id']." ul li').remove();
  $.each(div.map.layers, function(i, layer) {
    if (layer.displayInLayerSwitcher) {
      $('#".$options['id']." ul').append(getLayerHtml_$funcSuffix(layer, div));
    }
  });    
}

mapInitialisationHooks.push(function(div) { 
  refreshLayers_$funcSuffix(div);
  div.map.events.register('addlayer', div.map, function(object, element) {
    refreshLayers_$funcSuffix(div);
  });
  div.map.events.register('changelayer', div.map, function(object, element) {
    if (!object.layer.isBaseLayer) {
      refreshLayers_$funcSuffix(div);
    }
  });
  div.map.events.register('changebaselayer', div.map, function(object, element) {
    refreshLayers_$funcSuffix(div);
  });
  div.map.events.register('removelayer', div.map, function(object, element) {
    $('#'+object.layer.id.replace(/\./g,'-')).remove();
  });
  ";
    if ($options['includeSwitchers']) {
      self::$javascript .= "  var map=div.map;
  $('.layer-switcher').live('click', function() {
    var id = this.id.replace(/^switch-/, '').replace(/-/g, '.');
    var visible=this.checked;
    $.each(map.layers, function(i, layer) {
      if (layer.id==id) {
        if (layer.isBaseLayer) {
          if (visible) { map.setBaseLayer(layer); }
        } else {
          layer.setVisibility(visible);
        }
      }
    });
    
  });";    
    
    }
    self::$javascript .= "});\n";
    return $r;
  }
  
}