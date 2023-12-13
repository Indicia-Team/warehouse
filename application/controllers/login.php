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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Controller class for the login page.
 */
class Login_Controller extends Indicia_Controller {

  public function index() {
    $login_config = Kohana::config('login');
    if ($this->auth->logged_in()) {
      $this->template->title = 'Already Logged In';
      $this->template->content = new View('login/login_message');
      $this->template->content->message = 'You are already logged in.<br />';
      $this->template->content->link_to_home = 'YES';
      $this->template->content->link_to_logout = 'YES';
      return;
    }
    $this->buildTemplate();
    if (request::method() == 'post') {
      $ok = $this->auth->login(array('username' => $_POST['user']), $_POST['password'], isset($_POST['remember_me']));
      // If failed to log in with username then try email address.
      if (!$ok) {
        $person = ORM::factory('person')->like('email_address', $_POST['user'], FALSE)->find();
        $ok = $this->auth->login(array('person_id' => $person->id), $_POST['password'], isset($_POST['remember_me']));
      }
      if ($ok) {
        $user = new User_Model($_SESSION['auth_user']->id);
        $user->__set('forgotten_password_key', NULL);
        $user->save();
        $url = arr::remove('requested_page', $_SESSION);
        $url = $url ?? '';
        // Ensure that the session is being saved to the Cookie properly.
        $this->session->write_close();
        if (!cookie::get('kohanasession')) {
          $this->session->set_flash('flash_error', "Indicia could not log you in because cookies are not enabled on your browser. Please enable cookies then try again.");
        }
        else {
          url::redirect($url);
          return;
        }
      }
      else {
        $this->session->set_flash('flash_error', "<strong>Login failed.</strong><br/> Either your username or password was incorrect or your login does not have enough privileges to access this Indicia warehouse.");
      }
    }
  }

  /**
   * Builds the login view.
   */
  private function buildTemplate() {
    $this->template->title = 'User Login';
    $this->template->content = new View('login/login');
    $introduction = new View('login/introduction');
    $login_config = Kohana::config('login');
    $introduction->admin_contact = $login_config['admin_contact'];
    $this->template->content->introduction = $introduction;
    $this->template->content->error_message = '';
  }

}