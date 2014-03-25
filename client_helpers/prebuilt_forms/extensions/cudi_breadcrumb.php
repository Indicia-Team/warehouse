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


class extension_cudi_breadcrumb {
  /*
   * Displays a breadcrumb for use with the Cudi project
   */
  public function breadcrumb($auth, $args, $tabalias, $options) {
    global $base_url;
    $breadCrumbLocationNamesArray=array();
    //The location ids to display in the breadcrumb are held in the URL if the user
    //is returning from another page.
    $breadCrumbLocationIdsArray = explode(',',$_GET['breadcrumb']);
    $locationRecords = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'],
      'nocache' => true,

    ));
    //Get the names associated with the ids
    foreach ($breadCrumbLocationIdsArray as $breadCrumbLocationId) {
      foreach ($locationRecords as $locationRecord) {
        if ($locationRecord['id']===$breadCrumbLocationId) {
          $breadCrumbLocationNamesArray[] = $locationRecord['name'];
        }
      }
    }
    $r = '';
    //Only display links to homepage if we have links to show
    if (!empty($breadCrumbLocationNamesArray)) {
      $r .= '<label><h4>Links to homepage</h4></label></br>';
      $r .= '<div>';
      $r .= '<ul id="homepage-breadcrumb">';
      //Loop through the links to show
      foreach ($breadCrumbLocationNamesArray as $num=>$breadCrumbLocationName) {
        //For each link back to the homepage, we need to give the homepage some locations IDs to rebuild
        //its breadcrumb with. So we need to include ids of any locations that are "above" the location we are linking back with.
        //e.g. If the link is for Guildford, then we would need to supply the ids for Guildford, Surrey and South England
        //as well to the homepage can make a full breadcrumb trail to guildford.
        if (empty($breadCrumbParamToSendBack)) 
          $breadCrumbParamToSendBack='breadcrumb='.$breadCrumbLocationIdsArray[$num];
        else
          $breadCrumbParamToSendBack .= ','.$breadCrumbLocationIdsArray[$num];
        $r .= '<li id="breadcrumb-part-"'.$num.'>';
        //The breadcrumb link is a name, with a url back to the homepage containing ids for the homepage
        //to show in its breadcrumb
        $r .= '<a href="'.$base_url.(variable_get('clean_url', 0) ? '' : '?q=').$options['homepage_path'].(variable_get('clean_url', 0) ? '?' : '&').$breadCrumbParamToSendBack.'">'.$breadCrumbLocationName.'<a>';
        $r .= '</li>';
      }
      $r .= '</ul></div>';
    }   
    return $r;
  }
}
  