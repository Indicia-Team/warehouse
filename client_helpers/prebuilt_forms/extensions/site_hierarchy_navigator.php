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
      return '<p>Please provide a @layerLocationTypes option for the [site_hierarchy_navigator.map] map control on the edit tab</p>';
    $msg=self::check_format($options, 'layerLocationTypes', 'location_type_id (from the termlists term table)', '/^([0-9]*,\s*)*[0-9]*\s*$/'); 
    if ($msg!==true) return $msg;
    //This option is optional, so don't need to check if it isn't present
    $msg=self::check_format($options, 'showCountUnitsForLayers', 'location_type_id (from the termlists term table)', '/^([0-9]*,\s*)*[0-9]*\s*$/');
    if ($msg!==true) return $msg;
    iform_load_helpers(array('map_helper','report_helper'));
    drupal_add_js(iform_client_helpers_path().'prebuilt_forms/extensions/site_hierarchy_navigator.js');
    //The location types are supplied by the user in a comma seperated list.
    //The first number is used as the initial location type to display.
    //The second number is used after the user clicks the first time on a feature and so on
    $layerLocationTypes = explode(',', $options['layerLocationTypes']); 
    //Comma seperated list of location types which signify which layers should also display the Count Unit location type. 
    //This should be a subset of $layerLocationTypes.
    $showCountUnitsForLayers = explode(',', $options['showCountUnitsForLayers']); 
    $locationTypesWithSymbols = explode(',', $options['locationTypesWithSymbols']); 
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
    //Send the user supplied options for layers to display count units to Javascript
    map_helper::$javascript .= "indiciaData.showCountUnitsForLayers=".json_encode($showCountUnitsForLayers).";\n";
    //Send the user supplied options about which layers should display symbols instead of polygons to Javascript
    map_helper::$javascript .= "indiciaData.locationTypesWithSymbols=".json_encode($locationTypesWithSymbols).";\n";
    $options = array(
      'linkOnly'=>'true',
      'dataSource'=>'library/locations/locations_with_geometry_for_location_type',
      'readAuth'=>$auth['read']
    );
    //Get the report options such as the Preset Parameters on the Edit Tab
    $options = array_merge(
      iform_report_get_report_options($args, $readAuth),
    $options);    
    //Run the report that shows the locations (features) to the user when the map loads the first time.
    map_helper::$javascript .= "indiciaData.layerReportRequest='".
       report_helper::get_report_data($options)."';\n";
    return $r;
  }
  
  /*
   * A breadcrumb trail of the site hierarchy locations the user has clicked through as a seperate control
   */
  public function breadcrumb($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('map_helper'));
    map_helper::$javascript .= "indiciaData.useBreadCrumb=true;\n";
    $breadcrumb = '<div><ul id="map-breadcrumb"></ul></div>';
    return $breadcrumb;
  }
  
  /*
   * A select list that displays the same locations as on the map. Selecting a location
   * from the select list is the same as clicking on it on the map with the exception that
   * if there is no data that can be displayed then the select list gives a warning and
   * the map just ignores the user's click.
   */
  public function selectlist($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('map_helper'));
    map_helper::$javascript .= "indiciaData.useSelectList=true;\n";
    $selectlist = '<div><select id="map-selectlist"></select></div>';
    return $selectlist;
  }
  
  /*
   * A control where we construct a button linking to a report page whose path and parameter are as per administrator supplied options.
   * The options format is comma seperated where the format of the elements is "location_type_id|report_path|report_parameter".
   * If an option is not found for the displayed layer's location type, then the report link button is hidden from view.
   */
  public function listreportlink($auth, $args, $tabalias, $options, $path) {
    global $base_root;
    iform_load_helpers(array('map_helper'));
    $msg=self::check_format($options, 'listReportLinks', 'location_type_id|report_path|report_parameter', 
        '/^([0-9]+\|[0-9a-z_\-\/]*\|[0-9a-z_\-]*,)*[0-9]+\|[0-9a-z_\-\/]*\|[0-9a-z_\-]*$/');
    if ($msg!==true) return $msg;
    //Tell the javascript we are using the report link control
    map_helper::$javascript .= "indiciaData.useListReportLink=true;\n";
    //Div to put the select list into.
    $selectlist = '<div id="map-listreportlink"></div>';
    $reportLinksToCreate=explode(',',$options['listReportLinks']);
    //Cycle through all the supplied options, get the options and save the locations types and the report path we are going to use.
    foreach ($reportLinksToCreate as $id=>$reportLinkToCreate) {
      $differentOptions=explode('|',$reportLinkToCreate);
      $locationTypesForListReport[$id]=$differentOptions[0];
      $reportLinkUrls[$id]=
          $base_root.base_path().
          //handle whether the drupal installation has clean urls setup.
          (variable_get('clean_url', 0) ? '' : '?q=').
          $differentOptions[1].(variable_get('clean_url', 0) ? '?' : '&').
          $differentOptions[2].'=';
    }
    //Send the data to javascript
    map_helper::$javascript .= "indiciaData.locationTypesForListReport=".json_encode($locationTypesForListReport).";\n";
    map_helper::$javascript .= "indiciaData.reportLinkUrls=".json_encode($reportLinkUrls).";\n";
    return $selectlist;
  }
  
  /*
   * Control button that takes user to Add Count Unit page whose path and parameter are as per administrator supplied options.
   * The parameter is used to automatically zoom the map to the area we want to add the count unit.
   * The options format is comma seperated where the format of the elements is "location_type_id|page_path|parameter_name".
   * If an option is not found for the displayed layer's location type, then the Add Count Unit button is hidden from view.
   */
  public function addcountunit($auth, $args, $tabalias, $options, $path) {
    global $base_root;
    iform_load_helpers(array('map_helper'));
    $msg=self::check_format($options, 'addCountUnitLinks', 'location_type_id|page_path|parameter_name', 
        '/^([0-9]+\|[0-9a-z_\/]*\|[0-9a-z_\-]*,)*[0-9]+\|[0-9a-z_\/]*\|[0-9a-z_\-]*$/');
    if ($msg!==true) return $msg;
    map_helper::$javascript .= "indiciaData.useAddCountUnit=true;\n";
    $addcountunit = '<div id="map-addcountunit"></div>';
    
    $linksToCreate=explode(',',$options['addCountUnitLinks']);
    //Cycle through all the supplied options, get the options and save the locations types and the paths we are going to use.
    foreach ($linksToCreate as $id=>$linkToCreate) {
      $differentOptions=explode('|',$linkToCreate);
      $locationTypesForAddCountUnit[$id]=$differentOptions[0];
      $linkUrls[$id]=
          $base_root.base_path().
          //handle whether the drupal installation has clean urls setup.
          (variable_get('clean_url', 0) ? '' : '?q=').
          $differentOptions[1].(variable_get('clean_url', 0) ? '?' : '&').
          $differentOptions[2].'=';
    }
    //Send the data to javascript
    map_helper::$javascript .= "indiciaData.locationTypesForAddCountUnits=".json_encode($locationTypesForAddCountUnit).";\n";
    map_helper::$javascript .= "indiciaData.addCountUnitLinkUrls=".json_encode($linkUrls).";\n";
    return $addcountunit;
  }
  
  /*
   * Control button that takes user to Add Site page whose path and parameter are as per administrator supplied options.
   * The parameter is used to automatically zoom the map to the region/site we want to add the new site to.
   * The options format is comma seperated where the format of the elements is "location_type_id|page_path|parameter_name".
   * If an option is not found for the displayed layer's location type, then the Add Site button is hidden from view.
   */
  public function addsite($auth, $args, $tabalias, $options, $path) {
    global $base_root;
    iform_load_helpers(array('map_helper'));
    $msg=self::check_format($options, 'addSiteLinks', 'location_type_id|page_path|parameter_name', 
        '/^([0-9]+\|[0-9a-z_\-\/]*\|[0-9a-z_\-]*,)*[0-9]+\|[0-9a-z_\-\/]*\|[0-9a-z_\-]*$/');
    if ($msg!==true) return $msg;
    map_helper::$javascript .= "indiciaData.useAddSite=true;\n";
    $addsite = '<div id="map-addsite"></div>';
    
    $linksToCreate=explode(',',$options['addSiteLinks']);
    //Cycle through all the supplied options, get the options and save the locations types and the paths we are going to use.
    foreach ($linksToCreate as $id=>$linkToCreate) {
      $differentOptions=explode('|',$linkToCreate);
      $locationTypesForAddSite[$id]=$differentOptions[0];
      $linkUrls[$id]=
          $base_root.base_path().
          //handle whether the drupal installation has clean urls setup.
          (variable_get('clean_url', 0) ? '' : '?q=').
          $differentOptions[1].(variable_get('clean_url', 0) ? '?' : '&').
          $differentOptions[2].'=';
    }
    //Send the data to javascript
    map_helper::$javascript .= "indiciaData.locationTypesForAddSites=".json_encode($locationTypesForAddSite).";\n";
    map_helper::$javascript .= "indiciaData.addSiteLinkUrls=".json_encode($linkUrls).";\n";
    return $addsite;
  }
  
  /**
   * Internal function that checks a form structure control option against a regular expression to check it's format.
   */
  private function check_format($options, $optionName, $friendlyFormat, $regex) {
    $testval = $options[$optionName];
    if (!preg_match($regex, $testval))
      return "<p>$testval</p>" .
          "<p>The supplied @$optionName option is not of the correct format, it should be a comma separated list with each item of the form \"{friendlyFormat}\".</p>";
    return true;
  }
    
}
  