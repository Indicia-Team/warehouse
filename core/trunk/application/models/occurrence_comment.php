<?php defined('SYSPATH') or die('No direct script access.');

class Occurrence_comment_model extends ORM {

	protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'occurrence');

	public function validate(Validation $array, $save = FALSE) {
		// uses PHP trim() to remove whitespace from beginning and end of all fields before validation
		$array->pre_filter('trim');
		$array->add_rules('comment','required', 'length[1,1000]');
		$array->add_rules('occurrence_id', 'required');
		
		// Explicitly add those fields for which we don't do validation
		$extraFields = array(
			'email_address',
			'person_name',
			'deleted'
		);
		foreach ($extraFields as $a) {
			if (array_key_exists($a, $array->as_array())){
				$this->__set($a, $array[$a]);
			}
		}
		return parent::validate($array, $save);
			
	}

}
