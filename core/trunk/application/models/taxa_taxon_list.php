<?php defined('SYSPATH') or die('No direct script access.');

class Taxa_taxon_list_Model extends ORM_Tree {

	protected $belongs_to = array('taxon', 'taxon_list',  'taxon_meaning',
		'created_by' => 'user',
		'updated_by' => 'user');

	protected $children = 'taxa_taxon_lists';

	public function validate(Validation $array, $save = FALSE) {
		$array->pre_filter('trim');
		$array->add_rules('taxon_id', 'required');
		$array->add_rules('taxon_list_id', 'required');
		$array->add_rules('taxon_meaning_id', 'required');
#		$array->add_callbacks('deleted', array($this, '__dependents'));

		// Explicitly add those fields for which we don't do validation
		$extraFields = array(
			'taxonomic_sort_order',
			'parent_id',
			'deleted',
			'preferred',
			'image_path',
			'description'
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
			$record = ORM::factory('taxa_taxon_list', $array['id']);
			if ($record->children->count()!=0){
				$array->add_error($field, 'has_children');
			}
		}
	}
	/**
	 * Return a displayable caption for the item.
	 * For People, this should be a combination of the Firstname and Surname.
	 */
	public function caption()
	{

		return ($this->taxon_id != null ? $this->taxon->taxon : '');
	}

	public function getSubmittableFields() {
		return array(
			'taxon' => '',
			'fk_language' => '',
			'fk_taxon_group' => '',
			'authority' => '',
			'search_code' => '',
			'external_key' => '',
			'fk_parent' => '',
			'taxonomic_sort_order' => '',
		);
	}

}
