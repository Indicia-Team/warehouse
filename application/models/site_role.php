<?php defined('SYSPATH') or die('No direct script access.');

class Site_Role_Model extends ORM {

	protected $has_many = array('users_websites');

	public function unique_key($id)
	{
		if ( ! empty($id) AND is_string($id) AND ! ctype_digit($id))
		{
			return 'title';
		}

		return parent::unique_key($id);
	}
	
} // End site Role Model