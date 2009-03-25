<?php defined('SYSPATH') or die('No direct script access.');

class Taxon_meaning_Model extends ORM {
	protected $search_field='id';

	protected $has_many = array(
			'taxa_taxon_lists'
		);

	public function insert(){
		$nextval = $this->db->query("SELECT nextval('taxon_meanings_id_seq'::regclass)")
			->current()->nextval;
		$this->id = $nextval;
		 return $this->save();
	}

	public function validate(Validation $array, $save = FALSE){
		$this->insert();
		return true;
	}

}
