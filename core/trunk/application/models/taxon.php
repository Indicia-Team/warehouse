<?php defined('SYSPATH') or die('No direct script access.');

class Taxon_Model extends ORM {
	protected $search_field='taxon';
	protected $belongs_to = array('meaning', 'language', 'created_by' => 'user', 'updated_by' => 'user');
	protected $has_many = array('taxa_taxon_lists');
	protected $has_and_belongs_to_many = array('taxon_lists');

	public function validate(Validation $array, $save = FALSE) {
		$array->pre_filter('trim');
		$array->add_rules('taxon', 'required');
		$array->add_rules('scientific', 'required');
		$array->add_rules('taxon_group_id', 'required');

		// Explicitly add those fields for which we don't do validation
		$extraFields = array(
			'language_id',
			'external_key',
			'authority',
			'deleted',
			'search_code'
		);
		foreach ($extraFields as $a) {
			if (array_key_exists($a, $array->as_array())){
				$this->__set($a, $array[$a]);
			}
		}
		return parent::validate($array, $save);
	}

	protected function preSubmit(){

		// Call the parent preSubmit function
		parent::preSubmit();

		// Set scientific as necessary
		$l = ORM::factory('language');
		$sci = 'f';
		if ($l->find(
			$this->submission['fields']['language_id']['value'])->iso == "lat") {
				$sci = 't';
			}
		$this->submission['fields']['scientific'] = array(
			'value' =>  $sci
		);

	}
}

