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
  
  public function map($auth, $args, $tabalias, $options, $path) {
    if (empty($options['layerLocationTypes']))
      return '<h3>Please provide a layerLocationTypes attribute for the [site_hierarchy_navigator.map] map control on the edit tab<h3>';
    iform_load_helpers(array('map_helper','report_helper'));
    $jsPath = iform_client_helpers_path().'prebuilt_forms/extensions/site_hierarchy_navigator.js';
    drupal_add_js($jsPath);
    //The location types are supplied by the user in a comma seperated list.
    //The first number is used as the initial location type to display.
    //The second number is used after the user clicks the first time on a feature and so on
    $layerLocationTypes =  explode(',',$options['layerLocationTypes']);
    $options = iform_map_get_map_options($args, $auth);
    $olOptions = iform_map_get_ol_options($args);
    $options['readAuth'] = $options['readAuth']['read'];
    $options['clickForSpatialRef'] = false;
    //When user clicks on map, run specified Javascript function
    $options['clickableLayersOutputMode'] = 'customFunction';
    $options['customClickFn']='reload_map_with_sub_sites_for_clicked_feature';
    $options['clickableLayersOutputDiv'] = '';
    //Tell the system which layers we to be clickable. As we initially only use
    //one layer to start with, we only supply one item.
    $options['clickableLayers']=array('indiciaData.initialreportlayer');
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
  
}
  
  
  
  
  
  
  
  
  
  /*
    // This is used for drawing, so need an editlayer, but not used for input
    $options['editLayer'] = true;
    $options['editLayerInSwitcher'] = true;
    $options['clickForSpatialRef'] = false;
    $options['readAuth'] = $options['readAuth']['read'];
    $r .= map_helper::map_panel(
      $options,
      $olOptions
    );
    $extraParams['expertise_taxon_groups'] = '1,2,3';
    $paramDefaults['records'] = 'unverified';
    $auth = $auth['read'];
    $args['report_name'] = 'library/occurrences/verification_list_3';
    $args['report_name'] = 'library/locations/locations_with_geometry_for_location_type';
    $opts = array_merge(
      iform_report_get_report_options($args, $auth),
      array(
        'id' => 'verification-grid',
        'reportGroup' => 'reporting',
        'paramsFormButtonCaption' => lang::get('Filter'),
        'paramPrefix'=>'<div class="report-param">',
        'paramSuffix'=>'</div>',
      )
    );
    $mapOptions = array(
      /*
      'dataSource' => 'library/locations/locations_with_geometry_for_location_type',
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'autoParamsForm' => false,
      'extraParams' => $extraParams,
      'paramDefaults' => $paramDefaults,
      'reportGroup' => 'verification',
      'clickableLayersOutputMode' => 'report',
      //'location_type_ids'=>$layerLocationTypes[0],
      'sharing'=>'verification',
      'ajax'=>TRUE  
       
      'dataSource' => !empty($args['mapping_report_name']) ? $args['mapping_report_name'] : $args['report_name'],
      'mode' => 'report',
      'readAuth' => $auth,
      'autoParamsForm' => false,
      'extraParams' => $extraParams,
      'paramDefaults' => $paramDefaults,
      //'reportGroup' => 'verification',
      'clickableLayersOutputMode' => 'report',
      'rowId'=>'occurrence_id',
      'sharing'=>'reporting',
      'ajax'=>true,
      'zoomMapToOutput'=>true
    );
    $mapOptions = array_merge($opts,$mapOptions);
    $r .= report_helper::report_map($mapOptions);
    return $r;
  }*/
  
