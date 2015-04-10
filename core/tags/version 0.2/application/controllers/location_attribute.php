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
 * Controller for location attributes.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Location_attribute_Controller extends Attr_Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('location_attribute',		// modelname
              'Location',				// name
              'location_attributes_website',	// website table modelname
              'gv_location_attribute',	// gridmodelname
              'custom_attribute/index',	// viewname
              NULL);						// controllerpath
    $this->columns = array(
      'website'=>'',
      'survey'=>'',
      'caption'=>'',
      'data_type'=>'');
    $this->pagetitle = "Location Attribute";
    $this->model = ORM::factory('location_attribute');
    $this->auth_filter = $this->gen_auth_filter;
  }

}
?>