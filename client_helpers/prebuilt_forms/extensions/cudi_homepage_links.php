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


class extension_cudi_homepage_links {
  /*
   * Displays a list of links back to the homepage for use with the cudi project.
   * Each link returns to the appropriate position on the homepage map
   */
  public function homepage_links($auth, $args, $tabalias, $options) {
    global $base_url;
    //Get the ids of the locations to display in the breadcrumb, these are supplied by the
    //calling page.
    $homepageLinkIdsArray = explode(',',$_GET['breadcrumb']);
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
        //Get the name of the location and its location type for each link we are creating
        foreach ($locationRecords as $locationRecord) {     
          if ($locationRecord['id']===$homepageLinkLocationId) {
            $homepageLinkLocationName = $locationRecord['name'];
            $locationTypeIdToZoomTo = $locationRecord['location_type_id'];
          }
        }
        //For the homepage to recreate its map breadcrumb, we need to supply it with the
        //location id we want the map to default to, as well as the location type id.
        $homepageLinkParamToSendBack='breadcrumb='.$homepageLinkLocationId.','.$locationTypeIdToZoomTo;
        $r .= '<li id="homepageLink-part-"'.$num.'>';
        //The homepageLink link is a name, with a url back to the homepage containing the location id and location_type_id
        $r .= '<a href="'.$base_url.(variable_get('clean_url', 0) ? '' : '?q=').$options['homepage_path'].(variable_get('clean_url', 0) ? '?' : '&').$homepageLinkParamToSendBack.'">'.$homepageLinkLocationName.'<a>';
        $r .= '</li>';
      }
      $r .= '</ul></div>';
    }   
    return $r;
  }
}
  