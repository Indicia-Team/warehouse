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

/**
 * Controller providing CRUD access to the list of websites registered on this Warehouse instance.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Website_agreement_Controller extends Gridview_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('website_agreement');

    $this->columns = array(
        'id'          => 'ID',
        'title'       => '',
        'description' => ''
    );

    $this->pagetitle = "Website Agreements";
    $this->set_website_access('admin');
  }

  /**
   * Returns an array of all values from this model and its super models ready to be
   * loaded into a form. For this controller, we need to double up the password field.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    return $r;
  }
  
  /**
   * Website agreements only editable by core admin
   */
  public function record_authorised ($id) {
    return $this->auth->logged_in('CoreAdmin');
  }

  /**
   * Core admin can see the list of websites
   */
  public function page_authorised() {
    return $this->auth->logged_in('CoreAdmin');
  }

}

?>
