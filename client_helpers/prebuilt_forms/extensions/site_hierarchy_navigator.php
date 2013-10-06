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
    global $base_root;
    //Setup the path to the cudi information sheets. 
    //Include the parameter on the end of the path, but leave off the parameter values
    //as these will change for each path used.
    iform_load_helpers(array('map_helper','report_helper'));
    $informationSheetLinkParts = explode('|',$options['informationSheetLink']);
    $path = $base_root.base_path().
          //handle whether the drupal installation has clean urls setup.
          (variable_get('clean_url', 0) ? '' : '?q=').$informationSheetLinkParts[0].
          (variable_get('clean_url', 0) ? '?' : '&').$informationSheetLinkParts[1].'=';
    map_helper::$javascript .= "indiciaData.informationSheetLink='".$path."';\n";
    if (empty($options['layerLocationTypes']))
      return '<p>Please provide a @layerLocationTypes option for the [site_hierarchy_navigator.map] map control on the edit tab</p>';
    $msg=self::check_format($options, 'layerLocationTypes', 'location_type_id (from the termlists term table)', '/^([0-9]*,\s*)*[0-9]*\s*$/'); 
    if ($msg!==true) return $msg;
    //This option is optional, so don't need to check if it isn't present
    $msg=self::check_format($options, 'showCountUnitsForLayers', 'location_type_id (from the termlists term table)', '/^([0-9]*,\s*)*[0-9]*\s*$/');
    if ($msg!==true) return $msg;   
    drupal_add_js(iform_client_helpers_path().'prebuilt_forms/extensions/site_hierarchy_navigator.js');
    //The location types are supplied by the user in a comma seperated list.
    //The first number is used as the initial location type to display.
    //The second number is used after the user clicks the first time on a feature and so on
    $layerLocationTypes = explode(',', $options['layerLocationTypes']); 
    //Comma seperated list of location types which signify which layers should also display the Count Unit location type. 
    //This should be a subset of $layerLocationTypes.
    $showCountUnitsForLayers = explode(',', $options['showCountUnitsForLayers']); 
    $locationTypesWithSymbols = explode(',', $options['locationTypesWithSymbols']); 
    //Annotation location types as defined on edit tab
    $annotationTypeIds = explode(',', $options['annotationTypeIds']); 
    $mapOptions = iform_map_get_map_options($args, $auth);
    $olOptions = iform_map_get_ol_options($args);
    $mapOptions['readAuth'] = $mapOptions['readAuth']['read'];
    $mapOptions['clickForSpatialRef'] = false;
    //When user clicks on map, run specified Javascript function
    $mapOptions['clickableLayersOutputMode'] = 'customFunction';
    $mapOptions['customClickFn']='move_to_new_layer';
    $mapOptions['clickableLayersOutputDiv'] = '';
    //Tell the system which layers we to be clickable.
    $mapOptions['clickableLayers']=array('indiciaData.reportlayer');     
    $r .= map_helper::map_panel(
      $mapOptions,
      $olOptions
    );
    map_helper::$javascript .= "indiciaData.layerLocationTypes=".json_encode($layerLocationTypes).";\n";
    $reportOptions = array(
      'dataSource'=>'reports_for_prebuilt_forms/CUDI/get_layer_list_names',
      'readAuth'=>$auth['read'],
      'mode'=>'report',
      'extraParams' => array('layer_ids'=>$options['layerLocationTypes'])
    );
    //Return a list of location type names for the location type id layers list
    //provided by the user in the form structure.
    $locationTypeNamesDirty = data_entry_helper::get_report_data($reportOptions);
    //The data returned by the database is not a simple array of names, so convert the data and put into correct order
    foreach ($layerLocationTypes as $originalLayerIndex=>$layerLocationTypeFromOriginalList) {
      foreach ($locationTypeNamesDirty as $locationTypeNamesData) {
        if ($locationTypeNamesData['id']===$layerLocationTypeFromOriginalList)
          $locationTypeNamesClean[$originalLayerIndex] = $locationTypeNamesData['name'];
      }
    }
    //Send the array of names to javascript
    map_helper::$javascript .= "indiciaData.layerLocationTypesNames=".json_encode($locationTypeNamesClean).";\n";   
    //Send the user supplied options for layers to display count units to Javascript
    map_helper::$javascript .= "indiciaData.showCountUnitsForLayers=".json_encode($showCountUnitsForLayers).";\n";
    map_helper::$javascript .= "indiciaData.countUnitBoundaryTypeId=".$options['countUnitBoundaryTypeId'].";\n";
    map_helper::$javascript .= "indiciaData.annotationTypeIds=".json_encode($annotationTypeIds).";\n";
    map_helper::$javascript .= "indiciaData.deactivateSiteAttributeId=".$options['deactivateSiteAttributeId'].";\n";
    //Get translatable label for top-level breadcrub item.
    map_helper::$javascript .= "indiciaData.allSitesLabel='".lang::get('All Sites')."';\n";
    $reportOptions = array(
      'linkOnly'=>'true',
      'dataSource'=>'reports_for_prebuilt_forms/cudi/get_boundaries_and_locations_for_cudi_map',
      'readAuth'=>$auth['read']
    );
    //Get the report options such as the Preset Parameters on the Edit Tab
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $readAuth),
    $reportOptions);    
    //Run the report that shows the locations (features) to the user when the map loads the first time.
    map_helper::$javascript .= "indiciaData.layerReportRequest='".
       report_helper::get_report_data($reportOptions)."';\n";
    //Options for the report that is used to draw the map breadcrumb
    $reportOptions = array(
      'linkOnly'=>'true',
      'dataSource'=>'reports_for_prebuilt_forms/CUDI/get_map_hierarchy_for_current_position',
      'readAuth'=>$auth['read']
    );
    //Get the report options such as the Preset Parameters on the Edit Tab
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $readAuth),
    $reportOptions);    
    //Run the report that builds the map breadcrumb.
    map_helper::$javascript .= "indiciaData.breadcrumbReportRequest='".
       report_helper::get_report_data($reportOptions)."';\n";
    return $r;
  }
  
  /*
   * A breadcrumb trail of the site hierarchy locations the user has clicked through as a seperate control
   */
  public function breadcrumb($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('map_helper'));
    map_helper::$javascript .= "indiciaData.useBreadCrumb=true;\n";
    //If the id parameter is supplied in the url, it means the user has clicked on another page homepage link to zoom to the location, so we need to rebuild the 
    //map breadcrumb trail and zoom the map. In order to do this we need to supply the location id and location type id to the javascript in indiciaData.
    $locationTypeId = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'] + array('id' => $_GET[$options['urlParameter']], 'view' => $view),
      'nocache' => true,
      'sharing' => $sharing
    ));
    if ($_GET[$options['urlParameter']])
      map_helper::$javascript .= "indiciaData.preloadBreadcrumb='".$_GET[$options['urlParameter']].','.$locationTypeId[0]['location_type_id']."';\n";
    $breadcrumb = '<div>
                      <h4>Map breadcrumb</h4>
                      <ul id="map-breadcrumb"></ul>
                   </div>';
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
    //The button is included because if the user is at the bottom level of the tree, they will only be viewing a single count unit item
    //so the drop-down is changed to be a single button that has the same function as a single item drop-down (handled with jQuery).
    map_helper::$javascript .= "indiciaData.useSelectList=true;\n";
    $selectlist = "<div id=\"map-selectlist-div\">
                     <select id=\"map-selectlist\" onchange=\"get_map_hierarchy_for_current_position($('option:selected', this).attr('featureid'),$('option:selected', this).attr('featurelocationtypeid'))\">
                     </select>
                   </div>
                   <div id=\"map-selectlist-button-div\">
                   </div>";
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
        '/^([0-9]+\|[0-9a-z_\-\/]*\|[0-9a-z_\-]*,)*[0-9]+\|[0-9a-z_\-\/]*\|[0-9a-z_\-]*$/');
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
   * Control button that takes user to Review Count Units page whose path is per an administrator supplied option.
   * The options format is comma seperated where the format of the elements is "location_type_id|page_path".
   * If an option is not found for the displayed layer's location type, then the View Count Units Review button is hidden from view.
   */
  public function viwcountunitsreport($auth, $args, $tabalias, $options, $path) {
    global $base_root;
    iform_load_helpers(array('map_helper'));
    $msg=self::check_format($options, 'viewCountUnitsReportLinks', 'location_type_id|page_path', 
        '/^([0-9]+\|[0-9a-z_\-\/]*\|[0-9a-z_\-]*,)*[0-9]+\|[0-9a-z_\-\/]*/');
    if ($msg!==true) return $msg;
    map_helper::$javascript .= "indiciaData.useCountUnitsReview=true;\n";
    //Setup a div that the javascript can then use to put the button in.
    $reviewcountunits = '<div id="map-viewcountunitsreview"></div>';
    $linksToCreate=explode(',',$options['viewCountUnitsReviewLinks']);
    //Cycle through all the supplied options, get the options and save the locations types and the paths we are going to use.
    foreach ($linksToCreate as $id=>$linkToCreate) {
      $differentOptions=explode('|',$linkToCreate);
      $locationTypesForCountUnitsReview[$id]=$differentOptions[0];
      $linkUrls[$id]=
          $base_root.base_path().
          //handle whether the drupal installation has clean urls setup.
          (variable_get('clean_url', 0) ? '' : '?q=').
          $differentOptions[1];
    }
    //Send the data to javascript
    map_helper::$javascript .= "indiciaData.locationTypesFoCountUnitsReview=".json_encode($locationTypesForCountUnitsReview).";\n";
    map_helper::$javascript .= "indiciaData.countUnitsReviewLinkUrls=".json_encode($linkUrls).";\n";
    return $reviewcountunits;
  }
  
  /*
   * Control button that takes user to Add Site page whose path and parameter are as per administrator supplied options.
   * The location type of the site we are adding is taken from the location layer we are viewing, so this button is
   * not just limited to the site location type if users want to use it to add other location types.
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
    //Get the options are speciified in the Form Structure
    $optionResults = self::get_link_options($options['addSiteLinks']); 
    //Defines which layers we display the Add Site button for.
    $locationTypesForAddSite = $optionResults[0];
    $linkUrls = $optionResults[1]; 
    //Send the options to javascript
    map_helper::$javascript .= "indiciaData.locationTypesForAddSites=".json_encode($locationTypesForAddSite).";\n";
    map_helper::$javascript .= "indiciaData.addSiteLinkUrls=".json_encode($linkUrls).";\n";
    return $addsite;
  }
  
  /*
   * An extension that can be added to the Site Details page to limit the Local Organiser Region field so that it is 
   * displayed only when the location type is Site.
   */
  public function site_detail_ext_local_organiser_site_limiter($auth, $args, $tabalias, $options, $path) {
    //If in edit mode, get the details of the site we are viewing.
    if (!empty($_GET['location_id'])) {
      $existingLocationDetail = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('id' => $_GET['location_id']),
        'nocache' => true,
        'sharing' => $sharing
      ));
    }
    //If in edit mode and the location type is not site then hide Local Organiser region
    if (!empty($_GET['location_id'])) {
      if ($existingLocationDetail[0]['location_type_id']!=$options['site_location_type_id']) {
        data_entry_helper::$javascript .= "$('[for=\"locAttr\\\\:".$options['local_org_reg_attribute_id']."\"]').remove();\n
                                     $('#locAttr\\\\:".$options['local_org_reg_attribute_id']."').remove();\n";
      }
    } else {
      //If in add mode and the type we are adding is not site then hide Local Organiser region 
      if ($_GET['location_type_id']!=$options['site_location_type_id']) {
        data_entry_helper::$javascript .= "$('[for=\"locAttr\\\\:".$options['local_org_reg_attribute_id']."\"]').remove();\n
                                           $('#locAttr\\\\:".$options['local_org_reg_attribute_id']."').remove();\n";  
      }  
    }
  }
  
  /*
   * Allow the user to specify a button to edit the parent site with
   */
  public function editsite($auth, $args, $tabalias, $options, $path) {
    global $base_root;
    iform_load_helpers(array('map_helper'));
    $msg=self::check_format($options, 'editSiteLinks', 'location_type_id|page_path|parameter_name', 
        '/^([0-9]+\|[0-9a-z_\-\/]*\|[0-9a-z_\-]*,)*[0-9]+\|[0-9a-z_\-\/]*\|[0-9a-z_\-]*$/');
    if ($msg!==true) return $msg;
    map_helper::$javascript .= "indiciaData.useEditSite=true;\n";
    $editsite = '<div id="map-editsite"></div>';  
    //Get the options are speciified in the Form Structure
    $optionResults = self::get_link_options($options['editSiteLinks']); 
    //Defines which layers we display the Edit Site button for.
    $locationTypesForEditSite = $optionResults[0];
    $linkUrls = $optionResults[1]; 
    //Send the options to javascript
    map_helper::$javascript .= "indiciaData.locationTypesForEditSites=".json_encode($locationTypesForEditSite).";\n";
    map_helper::$javascript .= "indiciaData.editSiteLinkUrls=".json_encode($linkUrls).";\n";
    return $editsite;
  }
  
  /*
   * Collect options from the form sturcture for buttons whose options are in the format
   * location_type_id_to_display_button_for|path_to_page|parameter_to_send_to_page
   */
  private function get_link_options($linkOptions) {
    $linksToCreate=explode(',',$linkOptions);
    //Cycle through all the supplied options, get the options and save the locations types and the paths we are going to use.
    foreach ($linksToCreate as $id=>$linkToCreate) {
      $differentOptions=explode('|',$linkToCreate);
      $locationTypesForToDisplayButtonFor[$id]=$differentOptions[0];
      $linkUrls[$id]=
          $base_root.base_path().
          //handle whether the drupal installation has clean urls setup.
          (variable_get('clean_url', 0) ? '' : '?q=').
          $differentOptions[1].(variable_get('clean_url', 0) ? '?' : '&').
          $differentOptions[2].'=';
    }
    $optionResults[0] = $locationTypesForToDisplayButtonFor;
    $optionResults[1] = $linkUrls;
    return $optionResults;
  }        
          
  /**
   * Internal function that checks a form structure control option against a regular expression to check it's format.
   */
  private function check_format($options, $optionName, $friendlyFormat, $regex) {
    $testval = $options[$optionName];
    if (!preg_match($regex, $testval)&&!empty($testval))
      return "<p>$testval</p>" .
          "<p>The supplied @$optionName option is not of the correct format, it should be a comma separated list with each item of the form \"$friendlyFormat\".</p>";
    return true;
  }
    
}
  