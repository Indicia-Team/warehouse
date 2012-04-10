<?php defined('SYSPATH') or die('No direct script access.');
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
 * @package Services
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

require_once(DOCROOT.'client_helpers/secure_msg.php');

/**
 * Class to provide webservice functions to support a centrally administered
 * user authentication and authorisation service for client websites.
 *
 * @author Indicia Team
 * @package Services
 * @subpackage Security
 */
class Site_User_Controller extends Service_Base_Controller {

  /**
   * Implements the webservice call from remote websites to check user credentials and
   * obtain matching warehouse user_id.
   * Does not support requests from users on the warehouse itself (website_id < 0)
   * Expects HTTP POST with username, password, options and usual service credentials.
   * Catches any Exceptions and passes them to handle_error()
   *
   * @return string (encrypted array) with user_id and optional user profile array
   */

  public function authenticate_user() {
    try
    {
      // authenticate requesting website for this service
      $this->authenticate('read');

      // decrypt and check the input
      $input = secure_msg::unseal_request($_POST, $this->website_password);
      if (array_key_exists(secure_msg::ERROR_MSG, $input)) {
        throw new ServiceError($input[secure_msg::ERROR_MSG]);
      }
      kohana::log('debug', 'Site_User_Controller::authenticate_user, unsealed input is '.print_r($input, true));
      $options = array_key_exists('options', $input) ? $input['options'] : array();

      // authenticate user
      $this->auth = new Auth;
      $user_id = $this->auth->site_login($input['username'], $input['password'], $options, $this->website_id);
      $response = array('user_id' => $user_id);

      // get profile if user has been authenticated and profile has been requested
      $getprofile = (array_key_exists('getprofile', $options)) ? $options['getprofile'] : false;
      if ($user_id > 0 && $getprofile) {
        $response['profile'] = $this->_get_user_profile($user_id);
      }

      // seal response to secure it from prying or tampering
      kohana::log('debug', 'Site_User_Controller::authenticate_user, unsealed response is '.print_r($response, true));
      $sealed = secure_msg::SEALED.secure_msg::seal($response, $this->website_password);
      kohana::log('debug', 'Site_User_Controller::authenticate_user, sealed response is '.print_r($sealed, true));
      echo $sealed;
    }
    catch (Exception $e)
    {
      $this->handle_error($e);
    }
  }
  
  /**
   * Implements the webservice call from remote websites to send a password reset email.
   * 
   * Does not support requests from users on the warehouse itself (website_id < 0)
   * Expects HTTP POST with userid, options and usual service credentials.
   * Catches any Exceptions and passes them to handle_error()
   *
   * @return json array with result code??
   */

  public function request_password_reset() {
    try
    {
      $this->authenticate('read');
      
      $username_or_email = $_POST['userid'];
      
      $this->auth = new Auth;
      $returned = $this->auth->user_and_person_by_username_or_email($_POST['userid']);
      if (array_key_exists('error_message', $returned)) {
        $returned['result'] = false;
        $this->response = json_encode($returned);
        return;
      }
      $user = $returned['user'];
      $person = $returned['person'];
      
      if (! $this->auth->is_website_user($user->id, $this->website_id) )
      {
        $result = array('result' => false,
          'error_message' => $_POST['userid'].' does not have permission to log on to this website');
        $this->response = json_encode($result);
        return;
      }
      
      $this->auth->send_forgotten_password_mail($user, $person);

      $result = array('result' => true);
      // set response
      $this->response = json_encode($result);
      kohana::log('debug', 'Site_User_Controller::request_password_reset, response is '.print_r($this->response));
    }
    catch (Exception $e)
    {
      $this->handle_error($e);
    }
  }
  
  /**
   * Returns a json string containing user profile data for the supplied use id.
   * The user must have a role on the requesting warehouse and not be logically deleted.
   * Data for banned users is returned.
   * 
   * Does not support requests from users on the warehouse itself (website_id < 0)
   * Expects HTTP POST with usual service credentials.
   * User_id is supplied as part of the URI and passed to this function as an argument.
   * Catches any Exceptions and passes them to handle_error()
   *
   * @return json array with profile data as follows<ul>
   * <li>title</li>
   * <li>first_name</li>
   * <li>surname</li>
   * <li>initials</li>
   * <li>email_address</li>
   * <li>website_url</li>
   * <li>address</li>
   * <li>home_entered_sref</li>
   * <li>home_entered_sref_system</li>
   * <li>interests</li>
   * <li>location_name</li>
   * <li>email_visible</li>
   * <li>view_common_names</li>
   * <li>username</li>
   * <li>default_digest_mode</li>
   * <li>activated</li>
   * <li>banned</li>
   * <li>site_role</li>
   * <li>registration_datetime</li>
   * <li>last_login_datetime</li>
   * <li>preferred_sref_system</li>
   * </ul>
   */

  public function get_user_profile($user_id) {
    try
    {
      $this->authenticate('read');
      
      $profile = $this->_get_user_profile($user_id);

      // set response
      $this->response = json_encode($profile);
      kohana::log('debug', 'Site_User_Controller::get_user_profile, response is '.print_r($this->response));
    }
    catch (Exception $e)
    {
      $this->handle_error($e);
    }
  }
  
  /**
   * Reusable private implementation of get_user_profile which just returns an array
   * and doesn't authenticate service.
   */

  private function _get_user_profile($user_id) {
    try
    {
      $user = ORM::factory('user')->where(
        array('id' => $user_id,
        'deleted' => 'f'))->find();
      if (! $user->loaded) {
        return array('result' => false,
        'error_message' => 'No non-deleted user found for user_id '.$user_id);
      }
      $website = ORM::factory('users_website')->where(
        array('user_id' => $user_id,
        'website_id' => $this->website_id))->find();
      if (! $website->loaded) {
        return array('result' => false,
        'error_message' => 'user '.$USER_ID.' has no role on website '.$this->website_id);
      }
      $person = new Person_Model($user->person_id);
      if (is_numeric($person->title_id)) {
        $title = new Title_Model($person->title_id);
      }
      if (is_numeric($website->site_role_id)) {
        $site_role = new Site_Role_Model($website->site_role_id);
      }
      
      $profile = array(
      'result' => true,
      'title' => is_numeric($person->title_id) ? $title->title : '',
      'first_name' => $person->first_name,
      'surname' => $person->surname,
      'initials' => $person->initials,
      'email_address' => $person->email_address,
      'website_url' => $person->website_url,
      'address' => $person->address,
      'home_entered_sref' => $user->home_entered_sref,
      'home_entered_sref_system' => $user->home_entered_sref_system,
      'interests' => $user->interests,
      'location_name' => $user->location_name,
      'email_visible' => $user->email_visible,
      'view_common_names' => $user->view_common_names,
      'username' => $user->username,
      'default_digest_mode' => $user->default_digest_mode,
      'activated' => $website->activated,
      'banned' => $website->banned,
      'site_role' => is_numeric($website->site_role_id) ? $site_role->title : '',
      'registration_datetime' => $website->registration_datetime,
      'last_login_datetime' => $website->last_login_datetime,
      'preferred_sref_system' => $website->preferred_sref_system,
      );

      return $profile;
    }
    catch (Exception $e)
    {
      $this->handle_error($e);
    }
  }
  
}
?>
