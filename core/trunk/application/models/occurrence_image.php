<?php defined('SYSPATH') or die('No direct script access.');

class Occurrence_Image_Model extends ORM {

	protected $belongs_to = array('created_by' => 'user', 'updated_by' => 'user',
		'occurrence');

	protected $search_field = 'caption';

	public function validate(Validation $array, $save = false) {

		$array->pre_filter('trim');
		$array->add_rules('occurrence_id', 'required');
		$array->add_rules('path', 'required');

		return parent::validate($array, $save);
	}

}
