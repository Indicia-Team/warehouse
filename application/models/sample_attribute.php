<?php defined('SYSPATH') or die('No direct script access.');

class Sample_Attribute_Model extends ATTR_ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'termlist');

  protected $has_many = array(
    'sample_attributes_values',
  );

  protected $has_and_belongs_to_many = array('websites');

}
