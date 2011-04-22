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
 * Controller providing CRUD access to the list of Warehouse users.
 *
 * @package	Core
 * @subpackage Controllers
 */
class User_Controller extends Gridview_Base_Controller {

  public function __construct() {
  	parent::__construct('user', 'gv_user', 'user/index');
    $this->columns = array(
      'name'=>'',
      'username'=>'',
      'core_role'=>''
    );
    $this->pagetitle = "Users";
    $this->model = new User_Model();
    if(!is_null($this->gen_auth_filter)) {
      // If not core admin, then you can only edit a person if they have a role on one of your websites that you administer or
      // you created the user
      $people_id_values = array();
      $list = $this->db
          ->select('people.id')
          ->from('people')
          ->join('users', 'users.person_id', 'people.id')
          ->join('users_websites','users_websites.user_id','users.id')
          ->where('users_websites.site_role_id IS NOT ', null)
          ->where('users.core_role_id IS ', null)
          ->in('users_websites.website_id', $this->gen_auth_filter['values'])
          ->get();
      foreach ($list as $item) {
        $people_id_values[] = $item->id;
      }
      // Also let you edit users that you created
      $list = $this->db
          ->select('people.id')
          ->from('people')
          ->join('users', 'users.person_id', 'people.id', 'LEFT')
          ->where('people.created_by_id', $_SESSION['auth_user']->id)
          ->where('users.core_role_id IS ', null)
          ->get();
      foreach ($list as $item) {
        $people_id_values[] = $item->id;
      }
      $this->auth_filter = array('field' => 'person_id', 'values' => $people_id_values);
    }

  }

  /**
   * Override the default page action (which loads the main grid of users) to add notes to the page as
   * a flash.
   */
  public function page($page_no, $filter=null) {
    $this->session->set_flash('flash_info', "<strong>Notes:</strong>" .
        "<p>All Users must have an associated 'Person' - in order to create a new user the 'Person' must exist first.</p>" .
        "<p>In order to be on the list of potential users, the person must have an email address.</p>");
    parent::page($page_no, $filter);
  }

  /**
   * Override the default action columns so we get Edit User, Edit Person and Send Forgotten Pwd Email links.
   */
  protected function get_action_columns() {
    return array(
      'Edit User Details' => 'user/edit_from_person/£person_id£',
      'Edit Person Details' => 'person/edit_from_user/£person_id£',
      'Send Forgotten Password Email' => 'forgotten_password/send_from_user/£person_id£',
    );
  }

  protected function password_fields($password = '', $password2 = '')
  {
    return '<li><label for="password">Password</label><input id="password" name="password" value="'.html::specialchars($password).'" />' .
        html::error_message($this->model->getError('password')) .
        '</li><li><label for="password">Repeat Password</label><input id="password2" name="password2" value="'.html::specialchars($password2).'" /></li>';
  }

  // Due to the way the Users gridview is displayed (ie driven off the person table)
  // there is no specific create function, as the edit function handles this when there
  // is no user record for the specified person id.

  /**
   * Subsiduary Action for user/edit page.
   * Displays a page allowing modification of an existing user or creation of a new user
   * driven by ther person id.
   */
  public function edit_from_person($id) {
    if (!is_null($id) && !is_null($this->auth_filter) && !in_array($id, $this->auth_filter['values'])) {
      $this->access_denied();
      return;
    }
    $this->model = new User_Model(array('person_id' => $id));
    $websites = ORM::Factory('website')->in_allowed_websites()->find_all();
    if ( $this->model->loaded ) {
      $this->setView('user/user_edit', 'User', array('password_field' => ''));
      foreach ($websites as $website) {
        $users_website = ORM::factory('users_website', array('user_id' => $this->model->id, 'website_id' => $website->id));
        $this->model->users_websites[$website->id]=
            array(
              'id' => $website->id
              ,'name' => 'website_'.$website->id
              ,'title' => $website->title
              ,'value' => ($users_website->loaded ? $users_website->site_role_id : null)
              );
      }
    } else {
      // new user
      $login_config = Kohana::config('login');
      $person = ORM::factory('person', $id);
       if ($person->email_address == null)
          {
           $this->setError('Invocation error: missing email address', 'You cannot create user details for a person who has no email_address');
          }
      else
      {
        $this->setView('user/user_edit', 'User',
          array('password_field' => $this->password_fields($login_config['default_password'], $login_config['default_password'])));
        $this->template->content->model->person_id = $id;
        $this->template->content->model->username = $this->new_username($person);
        foreach ($websites as $website)
          $this->model->users_websites[$website->id]=
              array(
                'id' => $website->id
                ,'name' => 'website_'.$website->id
                ,'title' => $website->title
                ,'value' => null
                );
      }
    }
    $this->defineEditBreadcrumbs();
  }

  protected function new_username($person)
  {
    $minlen=5;
    $inc=1;
    if($person->first_name=='')
      $base_username = $person->surname;
    else
      $base_username = $person->first_name.'.'.$person->surname;
    if(strlen($base_username) < $minlen)
      $username = sprintf($base_username.'%0'.($minlen-strlen($base_username)).'d', $inc++);
    else {
      $inc++; // numbers bolted on start at 2 on purpose.
      $username = $base_username;
    }
    // check for uniqueness
    while(ORM::factory('user', array('username'=>$username))->loaded){
      if(strlen($base_username) < $minlen)
        $username = sprintf($base_username.'%0'.($minlen-strlen($base_username)).'d', $inc++);
      else
        $username = sprintf($base_username.'%d', $inc++);
    }
    return $username;
  }

  protected function show_submit_fail() {
    $page_error=$this->model->getError('general');
    if ($page_error) {
      $this->session->set_flash('flash_error', $page_error);
    }
    $this->setView('user/user_edit', 'User',
        array('password_field' => array_key_exists('password', $_POST) ? $this->password_fields($_POST['password'], $_POST['password2']) : ''));

    // copy the values of the websites into the users_websites array
    $websites = ORM::Factory('website')->in_allowed_websites()->find_all();
    foreach ($websites as $website) {
      if (isset($_POST['website_'.$website->id])) {
        $this->model->users_websites[$website->id]=
          array('id' => $website->id
            ,'name' => 'website_'.$website->id
            ,'title' => $website->title
            ,'value' => (is_numeric($_POST['website_'.$website->id]) ? $_POST['website_'.$website->id] : NULL)
          );
      }
    }
  }
  
  /**
   * If trying to edit an existing user record, ensure the user has rights to a website the logged in user can access.
   * Note that the /user/edit action is not really expected as the user is edited from the person ID, but this is here 
   * as a safety check in case the user tries to guess the url for user editing and the base class edit method kicks in.
   */
  public function record_authorised($id) {
    if ($this->auth->logged_in('CoreAdmin'))
      return true;
    elseif (!is_null($id) AND !is_null($this->auth_filter)) {
      // auth filter here is a list of people ids
      $u = ORM::factory('user', $id);
      return (in_array($u->person_id, $this->auth_filter['values']));
    }
    return true;
  }

  /**
   * Website admins and core admins area allowed view the users list.
   */
  protected function page_authorised ()
  {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }
}

?>
