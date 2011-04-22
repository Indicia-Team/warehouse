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
 * Controller class for the forgotten password page.
 *
 * @package Core
 * @subpackage Controllers
 */
class Forgotten_Password_Controller extends Indicia_Controller {

  public function index()
  {

    if ($this->auth->logged_in())
    {
      $this->template->title = 'Already Logged In';
      $this->template->content = new View('login/login_message');
      $this->template->content->message = 'You are already logged in.<br />';
      $this->template->content->link_to_home = 'YES';
      $this->template->content->link_to_logout = 'YES';
      return;
    }
    $this->template->title = 'Forgotten Password Email Request';
    $this->template->content = new View('login/forgotten_password');
    if (request::method() == 'post')
    {
      $post = new Validation($_POST);
      $post->pre_filter('trim', TRUE);
      $post->add_rules('UserID', 'required');
      
      $returned = $this->auth->user_and_person_by_username_or_email($_POST['UserID']);
      if (array_key_exists('error_message', $returned)) {
        $this->template->content->error_message = $returned['error_message'];
        return;
      }
      $user = $returned['user'];
      $person = $returned['person'];
      if (!$this->check_can_login($user)) return;
      
      $this->auth->send_forgotten_password_mail($user, $person);
      
      $this->template->title = 'Email Sent';
      $this->template->content = new View('login/login_message');
      $this->template->content->message = 'An email providing a link which will allow your password to be reset has been sent to the specified email address, or if a username was provided, to the registered email address for that user.<br />';
    }
  }
  
  public function check_can_login($user) {
    if (is_null($user->core_role_id) && ORM::factory('users_website')->where('user_id', $user->id)->where('site_role_id IS NOT ', null)->find_all()===0)
    {
      $this->template->content->error_message = $_POST['UserID'].' does not have permission to log on to this website';
      return false;
    }
    return true;
  }

  public function send_from_user($id = null)
  {
    $email_config = Kohana::config('email');

    $this->template->title = 'Forgotten Password Email Request';
    $this->template->content = new View('login/login_message');
    $this->template->content->message = 'You are already logged in.<br />';
    $this->template->content->link_to_home = 'YES';
    $person = ORM::factory('person', $id);
    if ( ! $person->loaded )
    {
      $this->template->content->message = 'Invalid Person ID';
      return;
    }
    $user = ORM::factory('user', array('person_id' => $id));
    if ( ! $user->loaded )
    {
      $this->template->content->message = 'No user details have been set up for this Person';
      return;
    }
    if (!$this->check_can_login($user)) return;
    $link_code = $this->auth->hash_password($user->username);
    $user->__set('forgotten_password_key', $link_code);
    $user->save();
    try
    {
      $swift = email::connect();
      $message = new Swift_Message($email_config['forgotten_passwd_title'],
                                 View::factory('templates/forgotten_password_email_2')->set(array('server' => $email_config['server_name'], 'new_password_link' => '<a href="'.url::site().'new_password/email/'.$link_code.'">'.url::site().'new_password/email/'.$link_code.'</a>')),
                                 'text/html');
      $recipients = new Swift_RecipientList();
      $recipients->addTo($person->email_address, $person->first_name.' '.$person->surname);
      $swift->send($message, $recipients, $email_config['address']);
    }
    catch (Swift_Exception $e)
    {
      kohana::log('error', "Error sending forgotten password: " . $e->getMessage());
      throw new Kohana_User_Exception('swift.general_error', $e->getMessage());
    }
    kohana::log('info', "Forgotten password sent to $person->first_name $person->surname");
    url::redirect('user');
  }

}