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

/*
 * Extension class that creates links back to the map homepage. Clicking on a link 
 * passes a location_id (id) in the URL which allows the homepage to draw the map in the correct state.
 */
class extension_cudi_homepage_links {
  //The layerLocationTypes is specified on the edit tab and must match what is specified on the homepage.
  //$urlParameter is specified on the edit tab by the user. It specifies what parameter holds the location id in the url as
  //this might vary (e.g it might just be 'id' or it could be dynamic-location_id)
  private function get_links_hierarchy($auth, $layerLocationTypes,$countUnitBoundaryTypeId,$urlParameter) {
    iform_load_helpers(array('report_helper'));
    $locationId = $_GET[$urlParameter];
    $locationRecord = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'] + array('id' => $locationId),
      'nocache' => true,
    ));
    $locationTypeId = $locationRecord[0]['location_type_id'];

    $i=-1;
    //Cycle round the list of all Location Types that can be displayed on the homepage map in order.
    //Then stop when we reach the location type that is the same as the location we have clicked on. This gives us a list of location
    //types up until that point.
    do {
      $i++;
      if (!empty($SupportedLocationTypeIdsAsString)) {
        $SupportedLocationTypeIdsAsString=$SupportedLocationTypeIdsAsString.','.$layerLocationTypes[$i];
      } else {
        $SupportedLocationTypeIdsAsString=$layerLocationTypes[$i];
      }
    } while ($locationTypeId != $layerLocationTypes[$i] &&
             $i < count($layerLocationTypes)-1);
    
    //Use a report to get a list of locations that match the different layer location types and also intersect the location we are interested in.
    $reportOptions = array(
      'dataSource'=>'reports_for_prebuilt_forms/CUDI/get_map_hierarchy_for_current_position',
      'readAuth'=>$auth['read'],
      'mode'=>'report',
      'extraParams' => array('location_id'=>$locationId,'location_type_ids'=>$SupportedLocationTypeIdsAsString)
    );
    
    $breadcrumbHierarchy = report_helper::get_report_data($reportOptions);
    //The report doesn't know the order of the layers we want, so re-order the data.
    $breadcrumbHierarchy = self::reorderBreadcrumbHierarchy($breadcrumbHierarchy,$layerLocationTypes);
    return $breadcrumbHierarchy;
  }
  
  /*
   * The report that returns the data for the homepage links doesn't know the order of the layers we need, so we need to reorder the data.
   */
  private function reorderBreadcrumbHierarchy($breadcrumbHierarchy,$layerLocationTypes) {
    $orderedBreadcrumbHierarchy = array();
    foreach ($layerLocationTypes as $locationTypeLayerId) {
      foreach ($breadcrumbHierarchy as $breadcrumbHierarchyItem) {
        if ($locationTypeLayerId===$breadcrumbHierarchyItem['location_type_id']) {
          array_push($orderedBreadcrumbHierarchy,$breadcrumbHierarchyItem);
        }
      }   
    }
    return $orderedBreadcrumbHierarchy;
  }
  
  /*
   * Displays a list of links back to the homepage for use with the cudi project.
   * Each link returns to the appropriate position on the homepage map
   */
  public function homepage_links($auth, $args, $tabalias, $options) {
    //Get the user specified list of layers they want, this must match the homepage for correct operation.
    $layerLocationTypes = explode(',',$options['layerLocationTypes']);
    //Get the location data to make the links with
    $breadcrumbHierarchy = self::get_links_hierarchy($auth, $layerLocationTypes,$options['countUnitBoundaryTypeId'],$options['urlParameter']);
    $homepageLinkIdsArray = array();
    //Get the ids of the locations
    foreach ($breadcrumbHierarchy as $id => $hierarchyItem) {
      array_push($homepageLinkIdsArray,$hierarchyItem['id']);
    }
    $locationRecords = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'],
      'nocache' => true,
    ));
    $r = '';
    //Only display links to homepage if we have links to show
    if (!empty($homepageLinkIdsArray)) {
      $r .= '<label><h4>Links to homepage</h4></label></br>';
      $r .= '<div>';
      $r .= '<ul id="homepage-homepageLink">';
      //Loop through the links to show
      foreach ($homepageLinkIdsArray as $num=>$homepageLinkLocationId) {
        //Get the name of the location for each link we are creating
        foreach ($locationRecords as $locationRecord) {     
          if ($locationRecord['id']===$homepageLinkLocationId) {
            $homepageLinkLocationName = $locationRecord['name'];
          }
        }
        //For the homepage to recreate its map breadcrumb, we need to supply it with the
        //location id we want the map to default to
        $homepageLinkParamToSendBack='id='.$homepageLinkLocationId;
        $r .= '<li id="homepageLink-part-"'.$num.'>';
        //The homepageLink link is a name, with a url back to the homepage containing the location id
        $nodeurl = url($options['homepage_path'], array('query'=>$homepageLinkParamToSendBack));
        $r .= '<a href="'.$nodeurl.'">'.$homepageLinkLocationName.'</a>';
        $r .= '</li>';
      }
      $r .= '</ul></div>';
    }   
    return $r;
  }
}
  