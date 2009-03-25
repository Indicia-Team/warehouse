<?php defined('SYSPATH') or die('No direct script access.');

class Survey_Model extends ORM {

	protected $belongs_to = array(
			'owner'=>'person',
			'website',
			'created_by'=>'user',
			'updated_by'=>'user');

	public function validate(Validation $array, $save = FALSE) {
		$array->pre_filter('trim');
		$array->add_rules('title', 'required');
		$array->add_rules('website_id', 'required');
		// Explicitly add those fields for which we don't do validation
		$this->description = $array['description'];
		return parent::validate($array, $save);
	}

}