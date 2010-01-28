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
      echo json_encode($response);
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
