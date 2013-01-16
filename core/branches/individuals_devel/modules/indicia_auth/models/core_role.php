<?php defined('SYSPATH') or die('No direct script access.');

class Core_Role_Model extends Auth_Role_Model {

	// This class can be replaced or extended

	/**
	 * Allows finding roles by name.
	 */
	public function unique_key($id)
	{
		if ( ! empty($id) AND is_string($id) AND ! ctype_digit($id))
		{
			return 'title';
		}

		return parent::unique_key($id);
	}
	
} // End Role Model