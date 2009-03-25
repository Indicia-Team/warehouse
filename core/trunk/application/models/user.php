<?php defined('SYSPATH') or die('No direct script access.');

class User_Model extends ORM {

	protected $belongs_to = array('person', 'core_role',
		'created_by'=>'user', 'updated_by'=>'user');
	protected $has_many = array(
		'termlist'=>'created_by','termlist'=>'updated_by',
		'website'=>'created_by','website'=>'updated_by',
		'location'=>'created_by','location'=>'updated_by',
		);

	protected $search_field='username';
	
	public $users_websites = array();

	public function validate(Validation $array, $save = FALSE) {
		// uses PHP trim() to remove whitespace from beginning and end of all fields before validation
		// Any fields that don't have a validation rule need to be copied into the model manually
		// note that some of the fields are optional.
		// Checkboxes only appear in the POST array if they are checked, ie TRUE. Have to convert to PgSQL boolean values, rather than PHP
		$array->pre_filter('trim');
		$array->add_rules('username', 'required', 'length[5,30]', 'unique[users,username,'.$array->id.']');
		if (array_key_exists('password', $_POST)) $array->add_rules('password', 'required', 'length[7,30]', 'matches_post[password2]');
		$this->interests = $array['interests'];
		$this->location_name = $array['location_name'];
		$this->core_role_id = $array['core_role_id'];
		$this->email_visible = $array['email_visible'];
		$this->view_common_names = $array['view_common_names'];
		$this->person_id = $array['person_id'];

		return parent::validate($array, $save);
	}

	public function preSubmit() {

		if (!is_numeric($this->submission['fields']['core_role_id']['value']))
			$this->submission['fields']['core_role_id']['value'] = NULL;

		$this->submission['fields']['email_visible']	 = array('value' => (isset($this->submission['fields']['email_visible']) ? 't' : 'f'));
		$this->submission['fields']['view_common_names'] = array('value' => (isset($this->submission['fields']['view_common_names']) ? 't' : 'f'));

		return parent::preSubmit();
	}

	public function password_validate(Validation $array, $save = FALSE) {
		$array->pre_filter('trim');
		$array->add_rules('password', 'required', 'length[7,30]', 'matches[password2]');
		$this->forgotten_password_key = NULL;
		 
		return parent::validate($array, $save);
	}
	
	public function __set($key, $value)
	{
		if ($key === 'password')
		{
			// Use Auth to hash the password
			$value = Auth::instance()->hash_password($value);			
		}

		parent::__set($key, $value);
	}
	
}
