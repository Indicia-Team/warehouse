<?php defined('SYSPATH') or die('No direct script access.');

class Taxon_list_Model extends ORM_Tree {

	protected $children = "taxon_lists";
	protected $belongs_to = array('website', 'created_by'=>'user', 'updated_by'=>'user');
	protected $has_many = array('taxon_lists_terms');

	public function validate(Validation $array, $save = FALSE) {
		$array->pre_filter('trim');
		$array->add_rules('title', 'required');

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
				$array->add_error($field, 'has_taxa');
			}
		}
	}

}
