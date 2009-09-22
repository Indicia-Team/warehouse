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
 * @package	Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Controller providing CRUD access to the locations data.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Location_Controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('location', 'location', 'location/index');
    $this->columns = array(
                        'name'=>'',
                        'code'=>'',
                        'centroid_sref'=>'');
        $this->pagetitle = "Locations";

    // Get the list of locations the user is allowed to see.
    // @todo Is this a performance bottleneck with large lists of locations?
    if(!is_null($this->gen_auth_filter)){
      $locations=ORM::factory('locations_website')->in('website_id', $this->gen_auth_filter['values'])->find_all();
      $location_id_values = array();
      foreach($locations as $location)
        $location_id_values[] = $location->location_id;
      $this->auth_filter = array('field' => 'id', 'values' => $location_id_values);
    }
  }

  /**
   * Get the list of terms ready for the location types list. 
   */
  protected function prepareOtherViewData()
  {    
    return array(
      'type_terms' => $this->get_termlist_terms('indicia:location_types')    
    );   
  }

  protected function record_authorised ($id)
  {
    if (!is_null($id) AND !is_null($this->auth_filter))
    {
      return (in_array($id, $this->auth_filter['values']));
    }
    return true;
  }
  
}

?>
