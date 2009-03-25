<?php defined('SYSPATH') or die('No direct script access.');

class Taxon_Group_Model extends ORM {

	protected $has_many = array('taxa');

	protected $belongs_to = array(
		'created_by'=>'user',
		'updated_by'=>'user'
	);

	public function validate(Validation $array, $save = FALSE) {
		// uses PHP trim() to remove whitespace from beginning and end of all fields before validation
		$array->pre_filter('trim');
		$array->add_rules('title', 'required', 'length[1,100]');
		// Explicitly add those fields for which we don't do validation
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
}

?>
