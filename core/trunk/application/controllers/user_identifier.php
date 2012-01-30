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
 * Controller providing CRUD access to the list of websites in agreements.
 *
 * @package	Core
 * @subpackage Controllers
 */
class User_identifier_Controller extends Gridview_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('user_identifier', 'user_identifier/index');

    $this->columns = array(
        'identifier'=> 'Identifier',
        'type'   => ''
    );

    $this->pagetitle = "User identifiers";
    // @todo Users should have access to their own identifiers
    $this->set_website_access('admin');
  }
  
  /**
  * Override the default index functionality to filter by website.
  */
  public function index()
  {
    $user_id = $this->uri->argument(1);
    $this->base_filter['user_id'] = $user_id;
    parent::index();
    $this->view->user_id = $user_id;
  }
  
  /**
  * Override the default index functionality to filter by website.
  */
  public function index_from_person()
  {
    $person_id = $this->uri->argument(1);
    $this->base_filter['person_id'] = $person_id;
    parent::index();
    $r = $this->db->select('id')
        ->from('users')
        ->where(array('person_id'=>$person_id))
        ->get()->result_array(false);
    $this->view->user_id = $r[0]['id'];
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
   *  Setup the default values to use when loading this controller to edit a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create') {
      // User id is passed as first argument in URL when creating
      $r['user_identifier:user_id'] = $this->uri->argument(1);
    }
    return $r;
  }

  
  /**
   * User identifiers only editable by core admin or the owner.
   * @todo Owner can access
   */
  public function record_authorised ($id) {
    return true;
  }

  /**
   * Core admin plus the owner can see the list of identifiers
   * @todo Owner can access
   */
  public function page_authorised() {
    return $this->auth->logged_in('CoreAdmin');
  }
  
  /**
   * Define non-standard behaviour for the breadcrumbs, since this is accessed via a user list
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('user', 'Users');
    if ($this->model->id) {
      // editing an existing item, so our argument is the user_id
      $userId = $this->model->user_id;
    } else {
      // creating a new one so our argument is the user id
      $userId = $this->uri->argument(1);
    }
    $user = ORM::Factory('user', $userId)->username;
  	$this->page_breadcrumbs[] = html::anchor('user/edit/'.$userId.'?tab=Identifiers', $user);
	  $this->page_breadcrumbs[] = $this->model->caption();
  }
  
  /**
   * Override the default return page behaviour so that after saving an identifier you
   * are returned to the list of identifiers on the sub-tab of the user.
   */
  protected function get_return_page() {
    if (array_key_exists('user_identifier:user_id', $_POST)) {
      // after saving a record, the website id to return to is in the POST data
      // user may select to continue adding new terms
      if (isset($_POST['what-next'])) {
        if ($_POST['what-next']=='add')
          return 'user_identifier/create/'.$_POST['user_identifier:user_id'];
      }
      // or just return to the user page
      return "user/edit/".$_POST['user_identifier:user_id']."?tab=Identifiers";
    } elseif (array_key_exists('user_identifier:user_id', $_GET))
      // after uploading records, the website id is in the URL get parameters
      return "user/edit/".$_GET['user_identifier:user_id']."?tab=Identifiers";
    else
      // last resort if we don't know the list, just show the whole lot of agreements
      return $this->model->object_name;
  }
  
  /**
   * Get the list of terms ready for the sample methods list. 
   */
  protected function prepareOtherViewData($values)
  {    
    return array(
      'identifier_types' => $this->get_termlist_terms('indicia:user_identifier_types')
    );   
  }

}

?>
