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
 * Controller providing CRUD access to the sample attributes list.
 *
 * @package	Core
 */
class ArrayException extends Kohana_Exception {
  /**
   * @var boolean Defines if the user is logged into the warehouse.
   */
  protected $in_warehouse = false;

  private $errors = array();

  /**
   * Override constructor to accept an errors array
   */
  public function __construct($message, $errors) {
    $this->errors = $errors;
    // make sure everything is assigned properly
    parent::__construct($message);
  }

  public function errors() {
    return $this->errors;
  }
}

/**
 * Exception class for Indicia services.
 *
 * @package	Core
 * @subpackage Controllers
 */
class ServiceError extends Kohana_Exception {
}


/**
 * Base controller class for Indicia Service controllers.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Service_Base_Controller extends Controller {

/**
  * Before a request is accepted, this method ensures that the POST data contains the
  * correct digest token so we know the request was from the website.
  *
  * @param string $mode Whether the authentication token is required to have read or write access.
  * Possible values are 'read' and 'write'. Defaults to 'write'.
  */
  protected function authenticate($mode = 'write')
  {
    // Read calls are done using get values, so we merge the two arrays
    $array = array_merge($_POST, $_GET);
    $authentic = FALSE; // default
    kohana::log('debug', 'authenticating, '.print_r($array, true));
    if (array_key_exists('nonce', $array) && array_key_exists('auth_token',$array))
    {
      $nonce = $array['nonce'];
      $this->cache = new Cache;
      // get all cache entries that match this nonce
      $paths = $this->cache->exists($nonce);
      foreach($paths as $path) {
        // Find the parts of each file name, which is the cache entry ID, then the mode.
        $tokens = explode('~', basename($path));
        // check this cached nonce is for the correct read or write operation.
        if ($mode == $tokens[1]) {
          $id = $this->cache->get($tokens[0]);
          if ($id>0) {
            // normal state, the ID is positive, which means we are authenticating a remote website
            $website = ORM::factory('website', $id);
            if ($website->id) 
              $password = $website->password;
          } else
            $password = kohana::config('indicia.private_key');
          // calculate the auth token from the nonce and the password. Does it match the request's auth token?
          if (isset($password) && sha1("$nonce:$password")==$array['auth_token']) {
            Kohana::log('info', "Authentication successful.");
            // cache website_password for subsequent use by controllers
            $this->website_password = $password;
            $authentic=true;            
          }
          if ($authentic) {
            if ($id>0) 
              $this->website_id = $id;
            else {
              $this->in_warehouse = true;
              $this->website_id = 0; // the Warehouse
              $this->user_id = 0 - $id; // user id was passed as a negative number to differentiate from a website id
              // get a list of the websites this user can see
              $user = ORM::Factory('user', $this->user_id);
              $this->user_is_core_admin=($user->core_role_id===1);
              if (!$this->user_is_core_admin) {
                $this->user_websites = array();
                $userWebsites = ORM::Factory('users_website')->where(array('user_id'=>$this->user_id, 'site_role_id is not'=>null, 'banned'=>'f'))->find_all();
                foreach ($userWebsites as $userWebsite) 
                  $this->user_websites[] = $userWebsite->website_id;
              }
            }
            // reset the nonce if requested. Doing it here will mean only gets reset if not already timed out.
            if(array_key_exists('reset_timeout', $array) && $array['reset_timeout']=='true') {
              Kohana::log('info', "Nonce timeout reset.");
              $this->cache->set($nonce, $id, $mode);
            } 
          }
        }        
      }
    } else {
      $auth = new Auth();
      $authentic = ($auth->logged_in() || $auth->auto_login());
      $this->in_warehouse = $authentic;
    }

    if (!$authentic)
    {
      Kohana::log('info', "Unable to authenticate.");
      throw new ServiceError("unauthorised");
    };
  }
  
  /**
   * Set the content type and then issue the response.
   */
  protected function send_response()
  {
    // last thing we do is set the output
    if (isset($this->content_type))
    {
      header($this->content_type);
    }
    echo $this->response;
  }


  /**
   * Return an error XML or json document to the client
   */
  protected function handle_error($e)
  {
    $mode = $this->get_input_mode();
    if ($mode=='xml') {
      $view = new View("services/error");
      $view->message = $e->getMessage();
      $view->render(true);
    } else {
      $response = array(
        'error'=>$e->getMessage()
      );
      if (get_class($e)=='ArrayException') {
        $response['errors'] = $e->errors();
      } elseif (get_class($e)!='ServiceError') {
        $response['file']=$e->getFile();
        $response['line']=$e->getLine();
        $response['trace']=array();        
      }
      $a = json_encode($response);
      if (array_key_exists('callback', $_GET))
      {
        $a = $_GET['callback']."(".$a.")";
      }
      echo $a;
    }
  }


  /**
   * Retrieve the output mode for a RESTful request from the GET or POST data.
   * Defaults to xml. Other options are json and csv, or a view loaded from the views folder.
   */
  protected function get_output_mode() {
    if (array_key_exists('mode', $_GET))
      $result = $_GET['mode'];
    elseif (array_key_exists('mode', $_POST))
      $result = $_POST['mode'];
    else
      $result='xml';
    return $result;
  }

  /**
   * Retrieve the input mode for a RESTful request from the POST data.
   * Defaults to json. Other options not yet implemented.
   */
  protected function get_input_mode() {
    if (array_key_exists('mode', $_POST)){
      $result = $_POST['mode'];
    } else {
      $result = 'json';
    }
    return $result;
  }

}

?>
