<?php defined('SYSPATH') or die('No direct script access.');

class Location_Attribute_Model extends ATTR_ORM {

	protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'termlist');

	protected $has_many = array(
		'location_attributes_values',
		);

	protected $has_and_belongs_to_many = array('websites');

}
