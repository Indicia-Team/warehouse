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
 * Controller class for the login page.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Login_Controller extends Indicia_Controller {

  public function index()
  {
    $login_config = Kohana::config('login');
    if ( $login_config['login_by_email'] == 'YES')
    {
        $this->login_by_email();
        return;
    }
    if ($this->auth->logged_in())
    {
      $this->template->title = 'Already Logged In';
      $this->template->content = new View('login/login_message');
      $this->template->content->message = 'You are already logged in.<br />';
      $this->template->content->link_to_home = 'YES';
      $this->template->content->link_to_logout = 'YES';
      return;
    }
    $this->build_template('login_by_username');
    if (request::method() == 'post')
    {
      if ($this->auth->login(array('username' => $_POST['UserName']), $_POST['Password'], isset($_POST['remember_me'])))
      {        
// I don't trust the results!! There is something funny going on where the
// number of rows in a query is not being reported correctly - an invalid username returns
// a valid login with the first real user.
// THIS IS A DOUBLE CHECK. IF THE USERNAME DOESN'T MATCH, FORCE A LOG OFF.
        if ($_POST['UserName'] == $_SESSION['auth_user']->username)
        {
          $user = new User_Model($_SESSION['auth_user']->id);
          $user->__set('forgotten_password_key', NULL);
          $user->save();
          $url=arr::remove('requested_page', $_SESSION);
          // Ensure that the session is being saved to the Cookie properly
          $this->session->write_close();
          if (!cookie::get('kohanasession')) {
            $this->session->set_flash('flash_error', "Indicia could not log you in because cookies are not enabled on your browser. Please enable cookies then try again.");
          } else {
            url::redirect($url);
            return;
          }
        } else {
          $this->auth->logout(TRUE);
        }
      } else {
        $this->session->set_flash('flash_error', "<strong>Login failed.</strong><br/> Either your username or password was incorrect or your login does not have enough privileges to access this Indicia warehouse.");
      }
    }
  }

  public function login_by_email()
  {
    $login_config = Kohana::config('login');
    if ($this->auth->logged_in())
    {
      $this->template->title = 'Already Logged In';
      $this->template->content = new View('login/login_message');
      $this->template->content->message = 'You are already logged in.';
      $this->template->content->link_to_home = 'YES';
      $this->template->content->link_to_logout = 'YES';
      return;
    }
    $this->build_template('login_by_email');
    if ( $login_config['login_by_email'] != 'YES')
    {
      $this->template->content->link_to_username = 'YES';
    }

    if (request::method() == 'post')
    {
      # this is name complete as needs to convert from email address to username
      # or to extend auth model
      $person = ORM::factory('person')->like('email_address', $_POST['Email'], false)->find();

      if ($this->auth->login(array('person_id' => $person->id), $_POST['Password'], isset($_POST['remember_me'])))
      {
          $user = new User_Model($_SESSION['auth_user']->id);
          $user->__set('forgotten_password_key', NULL);
          $user->save();
          url::redirect(arr::remove('requested_page', $_SESSION));
          return;
      }
      $this->template->content->error_message = 'Invalid Email address/Password Combination, or insufficient privileges';
    }
  }

  /**
   * Builds the required login view for a specific template.
   *
   * @param string $template Name of the template to build the view for, either
   * login_by_username or login_by_password.
   */
  private function build_template($template) {
    $this->template->title = 'User Login';
    $this->template->content = new View('login/'.$template);
    $introduction = new View('login/introduction');
    $login_config=Kohana::config('login');
    $introduction->admin_contact = $login_config['admin_contact'];
    $this->template->content->introduction = $introduction;
    $this->template->content->error_message = '';
  }

}