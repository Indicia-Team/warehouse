<?php defined('SYSPATH') or die('No direct script access.');

class Termlists_term_Model extends ORM_Tree {
	// TODO: this is a temporary placeholder. Need to think how we can get the term (from the terms table)
	// in as the search field in termlists_terms. Perhaps a view?
	protected $search_field='id';

	protected $belongs_to = array(
		'term', 'termlist',
		'created_by' => 'user',
		'updated_by' => 'user'
	);

	protected $children = 'termlists_terms';

	public function validate(Validation $array, $save = FALSE) {
		$array->pre_filter('trim');
		$array->add_rules('term_id', 'required');
		$array->add_rules('termlist_id', 'required');
		$array->add_rules('meaning_id', 'required');
		// $array->add_callbacks('deleted', array($this, '__dependents'));

		// Explicitly add those fields for which we don't do validation
		$extraFields = array(
			'parent_id',
			'preferred',
			'deleted',
			'sort_order'
		);
		foreach ($extraFields as $a) {
			if (array_key_exists($a, $array->as_array())){
				$this->__set($a, $array[$a]);
			}
		}
		return parent::validate($array, $save);
	}
	/**
	 * If we want to delete the record, we need to check that no dependents exist.
	 */
	public function __dependents(Validation $array, $field){
		if ($array['deleted'] == 'true'){
			$record = ORM::factory('termlists_term', $array['id']);
			if (count($record->children)!=0){
				$array->add_error($field, 'has_children');
			}
		}
	}

}
