<?php

abstract class ORM extends ORM_Core {
	public $submission = array();
	protected $errors = array();

	// The default field that is searchable is called title. Override this when a different field name is used.
	// Used to match against, for example when importing csv values.
	protected $search_field='title';
	
	/**
	 * Override load_values to add in a vague date field.
	 */
	public function load_values(array $values)
	{
	  parent::load_values($values);
	  // Add in field
	  if (array_key_exists('date_type', $this->object))
	  {
	    $vd = vague_date::vague_date_to_string(array
	    (
	    date_create($this->object['date_start']),
	    date_create($this->object['date_end']),
	    $this->object['date_type']
	    ));
	    
	    $this->object['vague_date'] = $vd;
	  }
	  return $this;
	}
	
	/**
	 * Override the reload_columns method to add the vague_date virtual field
	 */
	public function reload_columns($force = FALSE)
	{
		if ($force === TRUE OR empty($this->table_columns))
		{
			// Load table columns
			$this->table_columns = $this->db->list_fields($this->table_name);
			// Vague date
			if (array_key_exists('date_type', $this->table_columns))
			{
			  $this->table_columns['vague_date'] = 'String';
			}
		}

		return $this;
	}
	
	/**
	 * Provide an accessor so that the view helper can retrieve the errors for the model by field name.
	 */
	public function getError($fieldname) {
		if (array_key_exists($fieldname, $this->errors)) {
			return $this->errors[$fieldname];
		} else {
			return '';
		}
	}

	/**
	 * Retrieve an array containing all errors
	 */
	public function getAllErrors()
	{
		return $this->errors;
	}

	/**
	 * Override the ORM validate method to store the validation errors in an array, making
	 * them accessible to the views.
	 */
	public function validate(Validation $array, $save = FALSE) {
		$this->set_metadata();
		if (parent::validate($array, $save)) {
			return TRUE;
		}
		else {
			// put the trimmed and processed data back into the model
			$arr = $array->as_array();
			$arr['created_on'] = $this->created_on;
			$arr['updated_on'] = $this->updated_on;
			$this->load_values($arr);
			$this->errors = $array->errors('form_error_messages');
			return FALSE;
		}
	}

	/**
	 * For a model that is about to be saved, sets the metadata created and
	 * updated field values.
	 */
	public function set_metadata() {
		$defaultUserId = Kohana::config('indicia.defaultPersonId');
		$force=false;
		// At this point we determine the id of the logged in user,
		// and use this in preference to the default id if possible.
		if (isset($_SESSION['auth_user'])) {
			$force = true;
			$userId = $_SESSION['auth_user']->id;
		} else
			$userId = ($defaultUserId ? $defaultUserId : 1);
		// Set up the created and updated metadata for the record
		if (!$this->id) {
			$this->created_on = date("Ymd H:i:s");
			if ($force or !$this->created_by_id) $this->created_by_id = $userId;
		}
		// TODO: Check if updated metadata present in this entity,
		// and also use correct user.
		$this->updated_on = date("Ymd H:i:s");
		if ($force or !$this->updated_by_id) $this->updated_by_id = $userId;
	}

	/**
	 * Do a default search for an item using the search_field setup for this model.
	 */
	public function lookup($search_text)
	{
		return $this->where($this->search_field, $search_text)->find();
	}

	/**
	 * Return a displayable caption for the item, defined as the content of the field with the
	 * same name as search_field.
	 */
	public function caption()
	{
		return $this->__get($this->search_field);
	}

	/**
	 * Property accessor for read only search_field.
	 */
	public function get_search_field()
	{
		return $this->search_field;
	}

	/**
	 * Ensures that the save array is validated before submission. Classes overriding
	 * this method should call this parent method after their changes to perform necessary
	 * checks unless they really want to skip them.
	 */
	protected function preSubmit(){
		//Overridden code happens here.

		// Ensure that the only fields being submitted are those present in the model.
		$this->submission['fields'] = array_intersect_key(
			$this->submission['fields'], $this->table_columns);


		// Where fields are numeric, ensure that we don't try to submit strings to
		// them.
		foreach ($this->submission['fields'] as $a => $b) {
			if ($b['value'] == '') {
				$type = $this->table_columns[$a];
				switch ($type) {
					case 'int':
						$this->submission['fields'][$a]['value'] = null;
						break;
					}
			}
		}


	}
	/**
	 * Submits the data by:
	 * - Calling the preSubmit function to clean data.
	 * - Linking in any foreign fields specified in the "fk-fields" array.
	 * - For each entry in the "supermodels" array, calling the submit function
	 *   for that model and linking in the resultant object.
	 * - Checking (by a where clause for all set fields) that an existing
	 *   record does not exist. If it does, return that.
	 * - Calling the validate method for the "fields" array.
	 * If successful, returns the id of the created/found record.
	 * If not, returns null - errors are embedded in the model.
	 */
	public function submit(){
		Kohana::log('info', 'Commencing new transaction.');
		$this->db->query('BEGIN;');
		$res = $this->inner_submit();
		if ($res) {
			Kohana::log('info', 'Committing transaction.');
			$this->db->query('COMMIT;');
		} else {
			Kohana::log('info', 'Rolling back transaction.');
			$this->db->query('ROLLBACK;');
		}
		return $res;
	}
	public function inner_submit(){
		$mn = $this->object_name;
		$return = true;
		$collapseVals = create_function('$arr', 'return $arr["value"];');
		// Link in foreign fields
		if (array_key_exists('fkFields', $this->submission)) {
			foreach ($this->submission['fkFields'] as $a => $b) {
				// Establish the correct model
				$m = ORM::factory($b['fkTable']);

				// Check that it has the required search field

				if (array_key_exists($b['fkSearchField'], $m->table_columns)) {
					$this->submission['fields'][$b['fkIdField']] =
						$m->where(array(
							$b['fkSearchField'] => $b['fkSearchValue']))
							->find()->id;
				}
			}
		}

		// Iterate through supermodels, calling their submit methods with subarrays
		if (array_key_exists('superModels', $this->submission)) {
			foreach ($this->submission['superModels'] as $a) {

				Kohana::log("info", "Submitting supermodel ".$a['model']['id'].".");

				// Establish the right model
				$m = ORM::factory($a['model']['id']);

				// Call the submit method for that model and
				// check whether it returns correctly
				$m->submission = $a['model'];
				$result = $m->inner_submit();
				if ($result) {
					Kohana::log("info", "Setting field ".$a['fkId']." to ".$result);
					$this->submission['fields'][$a['fkId']]['value'] = $result;
				} else {
					$return = null;
				}
				// We need to try attaching the model to get details back
				$this->add($m);
			}
		}

		// Call pre-submit
		$this->preSubmit();

		// Flatten the array to one that can be validated
		$vArray = array_map($collapseVals, $this->submission['fields']);
		Kohana::log("info", "About to validate the following array in model ".$this->object_name);
		foreach ($vArray as $a => $b){
			Kohana::log("info", $a.": ".$b);
		}
		// If we're editing an existing record.
		if (array_key_exists('id', $vArray) && $vArray['id'] != null) {
			$this->find($vArray['id']);
		}
		// Create a new record by calling the validate method
		if ($this->validate(new Validation($vArray), true)) {
			// Record has successfully validated. Return the id.
			Kohana::log("info", "Record ".
				$this->id.
				" has validated successfully");
			if ($return != null) $return = $this->id;
		} else {
			// Errors. Return null and rollback the transaction.
			Kohana::log("info", "Record did not validate.");
			// Print more detailed information on why
			foreach ($this->errors as $f => $e){
				Kohana::log("info", "Field ".$f.": ".$e.".");
			}
			$return = null;
		}
		// If there are submodels, submit them.
		if (array_key_exists('subModels', $this->submission)) {
			// Iterate through the subModel array, linking them to this model
			foreach ($this->submission['subModels'] as $a) {

				Kohana::log("info", "Submitting submodel ".$a['model']['id'].".");

				// Establish the right model
				$m = ORM::factory($a['model']['id']);

				// Set the correct parent key in the subModel
				$fkId = $a['fkId'];
				Kohana::log("info", "Setting field ".$fkId." to ".$this->id);
				$a['model']['fields'][$fkId]['value'] = $this->id;

				// Call the submit method for that model and
				// check whether it returns correctly
				$m->submission = $a['model'];
				$result = $m->inner_submit();
				if ($result == null) $return = null;
			}
		}


		// Call postSubmit
		if ($return != null) {
			$ps = $this->postSubmit();
				if ($ps == null) {
					$return = null;
				}
		}
		return $return;
	}

	/**
	 * Function to be overridden by and model doing post-submission processing (for example,
	 * submitting occurrence attributes.)
	 */
	protected function postSubmit(){
		return true;
	}

	/**
	 * Returns an array of fields that this model will take when submitting.
	 * By default, this will return the fields of the underlying table, but where
	 * supermodels are involved this may be overridden to include those also.
	 *
	 * When called with true, this will also add fk_ columns for any _id columns
	 * in the model.
	 */
	public function getSubmittableFields($fk = false) {
		$a = $this->table_columns;

		if ($fk == true) {
			foreach ($this->table_columns as $name => $type) {
				if (substr($name, -3) == "_id") {
					Kohana::log("info", $name." added as fk field.");
					$a["fk_".substr($name, 0, -3)] = $type;
				}
			}
		}

		return $a;
	}
}

?>
