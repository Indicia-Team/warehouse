<?php defined('SYSPATH') or die('No direct script access.');

class Title_Model extends ORM {

	protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user');
	
	protected $has_many = array('people');

	public function validate(Validation $array, $save = FALSE) {
		// uses PHP trim() to remove whitespace from beginning and end of all fields before validation
		$array->pre_filter('trim');
		$array->add_rules('title', 'required', 'length[1,10]');
		// Any fields that don't have a validation rule need to be copied into the model manually
		$extraFields = array(
			'deleted',
		);
		foreach ($extraFields as $a) {
			if (array_key_exists($a, $array->as_array())){
				$this->__set($a, $array[$a]);
			}
		}
		return parent::validate($array, $save);
	}
	
} // End Title Model