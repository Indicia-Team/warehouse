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
class Website_Controller extends Gridview_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('website');

    $this->columns = array(
        'id'          => 'ID',
        'title'       => '',
        'description' => '',
        'url'         => ''
    );

    $this->pagetitle = "Websites";
    $this->set_website_access('admin');
  }

  /**
   * Returns an array of all values from this model and its super models ready to be
   * loaded into a form. For this controller, we need to double up the password field.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $r['password2']=$r['website:password'];
    return $r;
  }

  /**
   * If trying to edit an existing website record, ensure the user has rights to this website.
   */
  public function record_authorised ($id) {
    if (is_null($id))
      // creating a new website registration so must be core admin.
      return $this->auth->logged_in('CoreAdmin');
    elseif (!is_null($id) AND !is_null($this->auth_filter))
      // editing a website registration - so must have rights to it.
      return (in_array($id, $this->auth_filter['values']));
    return true;
  }

  /**
   * Core admin or website admins can see the list of websites
   */
  public function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }
  
  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return array(array(
      'controller' => 'websites_website_agreement',
      'title' => 'Agreements',
      'actions'=>array('edit')
    ));
  }

}

?>
