<?php defined('SYSPATH') or die('No direct script access.');

class Location_Model extends ORM_Tree {

	protected $children = "locations";
	protected $has_and_belongs_to_many = array('websites');
	protected $has_many = array('samples');
	protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user');

	protected $search_field='name';

	public function validate(Validation $array, $save = FALSE) {
		$orig_values = $array->as_array();

		// uses PHP trim() to remove whitespace from beginning and end of all fields before validation
		$array->pre_filter('trim');
		$array->add_rules('name', 'required');
		$system = $orig_values['centroid_sref_system'];
		$array->add_rules('centroid_sref', 'required', "sref[$system]");
		$array->add_rules('centroid_sref_system', 'required', 'sref_system');

		// Explicitly add those fields for which we don't do validation
		$extraFields = array(
			'code',
			'parent_id',
			'deleted',
			'centroid_geom',
			'boundary_geom'
		);
		foreach ($extraFields as $a) {
			if (array_key_exists($a, $array->as_array())){
				$this->__set($a, $array[$a]);
			}
		}

		return parent::validate($array, $save);
	}

	/**
	 * Override set handler to translate WKT to PostGIS internal spatial data.
	 */
	public function __set($key, $value)
	{
		if (substr($key,-5) == '_geom')
		{
			if ($value) {
				$row = $this->db->query("SELECT ST_GeomFromText('$value', ".kohana::config('sref_notations.internal_srid').") AS geom")->current();
				$value = $row->geom;
			}
		}
		parent::__set($key, $value);
	}

	/**
	 * Override get handler to translate PostGIS internal spatial data to WKT.
	 */
	public function __get($column)
	{
		$value = parent::__get($column);

		if  (substr($column,-5) == '_geom') {
			$row = $this->db->query("SELECT ST_asText('$value') AS wkt")->current();
			$value = $row->wkt;
		}
		return $value;
	}

}
