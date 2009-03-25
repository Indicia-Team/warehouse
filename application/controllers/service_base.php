<?php


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

class ServiceError extends Kohana_Exception {
}



class Service_Base_Controller extends Controller {


	/**
	 * Return an error XML or json document to the client
	 */
	protected function handle_error($e)
	{
		$mode = $this->get_input_mode();
		if ($mode=='xml') {
			$view = new View("services/error");
			$view->message = $message;
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
				$response['trace']=$e->getTrace();
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
