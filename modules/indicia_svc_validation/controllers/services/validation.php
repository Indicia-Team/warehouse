<?php

class Validation_Controller extends Service_Base_Controller {

	private $aError = array('errors' => array());
	private $response;

	/**
	  * Service call method. This will parse the POST data for a submission type
	  * format encapsulating some data to be validated and the rules to validate
	  * it against.
	  */
	public function check()
	{
		if (!array_key_exists('submission', $_POST)) {
			$this->response = $this->errEncode('No array submitted');
		} else {
			$mode = $this->get_input_mode();
			switch ($mode) {
				case 'json':
					$s = json_decode($_POST['submission'], true);
					break;
			}
			if (!$s)
				$this->response = 'failure';//$this->errEncode('Submission is not valid JSON');
			else
				$this->checkDecoded($s);
		}
		$output_mode = $this->get_output_mode();
		switch ($output_mode) {
			case 'json':
				echo json_encode($this->response);
				break;
			default:
				echo json_encode($this->response);
		}
	}

	private function errEncode($string) {
		$this->aError['errors'][] = $string;
		return $this->aError;
	}

	/**
	 * Checks a JSON submission that has been converted to an array.
	 */
	private function checkDecoded($s) {
		try {
			if (array_key_exists('fields', $s)) {
				$fields = array();
				$rules = array();
				foreach ($s['fields'] as $name => $arr) {
					// We build an array, convert it to a validation object
					// and add all of the rules. Then we validate it.
					if (array_key_exists('value', $arr)){
						$fields[$name] = $arr['value'];
						if (array_key_exists('rules', $arr)){
							foreach ($arr['rules'] as $rule => $result){
								$rules[$name][] = $rule;
							}
						}
					}
				}
				$val = Validation::factory($fields);
				foreach ($rules as $name => $arr){
					foreach ($arr as $rule) {
						$val->add_rules($name, $rule);
					}
				}
				if ($val->validate()){
					$this->response = 'success';
				} else {
					$this->response = 'failure';
					$errRules = $val->errors();
					$errMessages = $val->errors('form_error_messages');
					foreach ($errRules as $name => $rule){
						$msg = $errMessages[$name];
						$s['fields'][$name]['rules'][$rule] = $msg;
					}
					$this->response = $s;
				}
			}
		} catch (Exception $e) {
			$this->response = $this->errEncode($e);
		}
	}

}
