<?php defined('SYSPATH') or die('No direct script access.');

class Language_Model extends ORM {

	protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user');

	protected $has_many = array(
		'terms',
		'taxa'
		);

	protected $search_field='language';

	public function validate(Validation $array, $save = FALSE) {
		// uses PHP trim() to remove whitespace from beginning and end of all fields before validation
		$array->pre_filter('trim');
		$array->add_rules('iso', 'required', 'length[3]');
		$array->add_rules('language','required', 'length[1,100]');
		// Any fields that don't have a validation rule need to be copied into the model manually
		return parent::validate($array, $save);
	}

}
