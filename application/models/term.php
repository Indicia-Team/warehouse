<?php defined('SYSPATH') or die('No direct script access.');

class Term_Model extends ORM {
	protected $search_field='term';
	protected $belongs_to = array('meaning', 'language', 'created_by' => 'user', 'updated_by' => 'user');
	protected $has_many = array('termlists_terms');
	protected $has_and_belongs_to_many = array('termlists');

	public function validate(Validation $array, $save = FALSE) {
		$array->pre_filter('trim');
		$array->add_rules('term', 'required');

		// Explicitly add those fields for which we don't do validation
		$this->language_id = $array['language_id'];
		return parent::validate($array, $save);
	}
}

