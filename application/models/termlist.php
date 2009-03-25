<?php defined('SYSPATH') or die('No direct script access.');


class Termlist_Model extends ORM_Tree {

	protected $children = "termlists";
	protected $belongs_to = array('website', 'created_by'=>'user', 'updated_by'=>'user');
	protected $has_and_belongs_to_many = array('terms');
	protected $has_many = array('termlists_terms');

	public function validate(Validation $array, $save = FALSE) {
		$array->pre_filter('trim');
		$array->add_rules('title', 'required');
		$array->add_callbacks('deleted', array($this, '_dependents'));

		// Explicitly add those fields for which we don't do validation
		$this->description = $array['description'];
		$this->website_id = $array['website_id'];
		$this->parent_id = $array['parent_id'];
		$this->deleted = $array['deleted'];
		return parent::validate($array, $save);
	}
	/**
	 * If we want to delete the record, we need to check that no dependents exist.
	 */
	public function _dependents(Validation $array, $field){
		if ($array['deleted'] == 'true'){
			$record = ORM::factory('termlist', $array['id']);
#			if ($record->children->count()!=0){
#				$array->add_error($field, 'has_children');
#			}
			if ($record->terms->count()!=0){
				$array->add_error($field, 'has_terms');
			}
		}
	}
}
