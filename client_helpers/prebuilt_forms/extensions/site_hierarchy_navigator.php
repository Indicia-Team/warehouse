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
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Extension class that supplies a new control which allows the user to click on a location on a map
 * and zoom to sub-sites of a given location type.
 */
class extension_site_hierarchy_navigator {
  
  /**
   * Hierarchy map control, which can be added to user interface form configurations using [site_hierarchy_navigator.map].
   *
   * Display a map with polygons loaded onto it of a particular location type. When the user clicks on one, reloads the map layer
   * to show the intersecting polygons from the next location type. Continues down the locations hierarchy in a supplied sequence of 
   * location types (e.g. you might set the location type sequence to Country, County, Parish, Site). 
   *
   * Supply an option @layerLocationTypes with a comma separated array of the location types ID to load in top down order.
   */
  public function map($auth, $args, $tabalias, $options, $path) {
    if (empty($options['layerLocationTypes']))
      return '<p>Please provide an @layerLocationTypes option for the [site_hierarchy_navigator.map] map control on the edit tab</p>';
    if (!preg_match('/^([0-9]*,\s*)*[0-9]*\s*$/', $options['layerLocationTypes']))
      return '<p>The supplied @layerLocationTypes is not of the required format, a comma separated list of location type ids (from the termlists_terms table).</p>';
    iform_load_helpers(array('map_helper','report_helper'));
    drupal_add_js(iform_client_helpers_path().'prebuilt_forms/extensions/site_hierarchy_navigator.js');
    //The location types are supplied by the user in a comma seperated list.
    //The first number is used as the initial location type to display.
    //The second number is used after the user clicks the first time on a feature and so on
    $layerLocationTypes = explode(',', $options['layerLocationTypes']);  
    $options = iform_map_get_map_options($args, $auth);
    $olOptions = iform_map_get_ol_options($args);
    $options['readAuth'] = $options['readAuth']['read'];
    $options['clickForSpatialRef'] = false;
    //When user clicks on map, run specified Javascript function
    $options['clickableLayersOutputMode'] = 'customFunction';
    $options['customClickFn']='reload_map_with_sub_sites_for_clicked_feature';
    $options['clickableLayersOutputDiv'] = '';
    //Tell the system which layers we to be clickable.
    $options['clickableLayers']=array('indiciaData.reportlayer');
    $r .= map_helper::map_panel(
      $options,
      $olOptions
    );
    //Send the user supplied location type options to Javascript
    map_helper::$javascript .= "indiciaData.layerLocationTypes=".json_encode($layerLocationTypes).";\n";
    //Run the report that shows the locations (features) to the user when the map loads the first time.
    map_helper::$javascript .= "indiciaData.layerReportRequest='".
       report_helper::get_report_data(array(
         'linkOnly'=>'true',
         'dataSource'=>'library/locations/locations_with_geometry_for_location_type',
         'readAuth'=>$auth['read']
       ))."';\n";
    return $r;
  }
  
  /*
   * A breadcrumb trail of the site hierarchy locations the user has clicked through as a seperate control
   */
  public function breadcrumb($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('map_helper'));
    map_helper::$javascript .= "indiciaData.useBreadCrumb=true;\n";
    $r .= '<ul id="map-breadcrumb"></ul>';
    return $r;
  }
  
}
  