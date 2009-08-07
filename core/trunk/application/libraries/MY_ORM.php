<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package	Core
 * @subpackage Libraries
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

abstract class ORM extends ORM_Core {
  public $submission = array();

  /**
   * The default field that is searchable is called title. Override this when a different field name is used.
   */
  protected $search_field='title';

  protected $errors = array();
  protected $linkedModels = array();
  protected $missingAttrs = array();
  protected $identifiers = array('website_id'=>null,'survey_id'=>null);
  /**
   * This field allows the errors from this model to be reported against a predefined key,
   * not always table:fieldname. This is handy when reporting errors for custom attribute values,
   * when the key needs to be the custom attribute type and id, not the table and fieldname.
   */
  protected $forceErrorKey = '';

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
   * Provide an accessor so that the view helper can retrieve the for the model by field name.
   */
  public function getError($fieldname) {
    if (array_key_exists($fieldname, $this->errors)) {
      return $this->errors[$fieldname];
    } else {
      return '';
    }
  }

  /**
   * Retrieve an array containing all errors.
   * The array entries are of the form 'entity:field => value'.
   */
  public function getAllErrors()
  {
    $r = array();
    // Get this model's errors, ensuring array keys have prefixes identifying the entity
    foreach ($this->errors as $key => $value) {
      $r[$this->object_name.':'.$key]=$value;
    }
    // Now the custom attribute errors
    $r = array_merge($r, $this->missingAttrs);
    foreach ($this->linkedModels as $m) {
      // Get the linked model's errors, ensuring array keys have prefixes identifying the entity
      foreach($m->errors as $key => $value) {
        if ($m->forceErrorKey) {
          $key = $m->forceErrorKey;
        } else {
          $key = $m->object_name.':'.$key;
        }
        $r[$key]=$value;
      }
      // Now the linked model custom attribute errors
      $r = array_merge($r, $m->missingAttrs);
    }

    return $r;
  }


  /**
   * Override the ORM validate method to store the validation errors in an array, making
   * them accessible to the views.
   *
   * @param Validation $array Validation array object.
   * @param boolean $save Optional. True if this call also saves the data, false to just validate. Default is false.
   * @param array() $extraFields Optional. List of additional fields that are not validated but must be included in a submission.
   */
  public function validate(Validation $array, $save = FALSE, $extraFields=NULL) {
    if ($extraFields) {
       foreach ($extraFields as $a)
      {
        if (array_key_exists($a, $array->as_array()))
        {
          $this->__set($a, $array[$a]);
        }
      }
    }
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
  protected function preSubmit() {
    kohana::log('debug','preSubmit start');
    // Grab the survey id and website id if they are in the submission, as they are used to check
    // attributes that apply and other permissions.
    if (array_key_exists('website_id', $this->submission['fields'])) {
      $this->identifiers['website_id']=$this->submission['fields']['website_id']['value'];
    }
    if (array_key_exists('survey_id', $this->submission['fields'])) {
      $this->identifiers['survey_id']=$this->submission['fields']['survey_id']['value'];
    }

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
    kohana::log('debug','preSubmit end');
  }

  /**
   * Standardise the dumping of an exception message into the kohana log. Protected
   * so available to all models.
   *
   * @param string $msg A description of where the error occurred.
   * $param object $e The exception object.
   */
  protected function log_error($msg, $e) {
    kohana::log('error', $msg.'. '.$e->getMessage() .' at line '.
          $e->getLine().' in file '.$e->getFile());
    if (kohana::config('config.log_threshold')==4) {
      // Double check the log threshold to avoid unnecessary work.
      kohana::log('debug', '<pre>'.print_r($e->getTrace(), true).'</pre>');
    }
  }

  /**
   * Wraps the process of submission in a transaction.
   */
  public function submit(){
    Kohana::log('debug', 'Commencing new transaction.');
    $this->db->query('BEGIN;');
    try {
      $res = $this->inner_submit();
    } catch (Exception $e) {
      $this->log_error('Exception during inner_submit.', $e);
      $res = null;
      $this->errors['record'] = $e->getMessage();
    }
    if ($res) {
      Kohana::log('debug', 'Committing transaction.');
      $this->db->query('COMMIT;');
    } else {
      Kohana::log('debug', 'Rolling back transaction.');
      $this->db->query('ROLLBACK;');
    }
    return $res;
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
  public function inner_submit(){
    $mn = $this->object_name;
    $collapseVals = create_function('$arr', 'return $arr["value"];');
    $return = $this->populateFkLookups();
    $return = $this->createParentRecords() && $return;
    $this->preSubmit();
    // Validation will overwrite our errors array, so store it for later
    $errors = $this->errors;
    // Flatten the array to one that can be validated
    $vArray = array_map($collapseVals, $this->submission['fields']);
    Kohana::log("debug", "About to validate the following array in model ".$this->object_name);
    Kohana::log("debug", kohana::debug($vArray));

    // If we're editing an existing record.
    if (array_key_exists('id', $vArray) && $vArray['id'] != null) {
      $this->find($vArray['id']);
    }
    // Create a new record by calling the validate method
    if ($this->validate(new Validation($vArray), true)) {
      // Record has successfully validated. Return the id.
      Kohana::log("debug", "Record ".$this->id." has validated successfully");
      if ($return) $return = $this->id;
    } else {
      // Errors.
      Kohana::log("debug", "Record did not validate.");
      // Log more detailed information on why
      foreach ($this->errors as $f => $e){
        Kohana::log("debug", "Field ".$f.": ".$e.".");
      }
      $return = null;
    }
    $this->errors=array_merge($errors, $this->errors);
    $return = $this->checkRequiredAttributes() ? $return : null;
    $return = $this->createChildRecords() ? $return : null;
    $return = $this->createAttributes() ? $return : null;

    // Call postSubmit
    if ($return) {
      $ps = $this->postSubmit();
        if ($ps == null) {
          $return = null;
        }
    }
    kohana::log('debug', 'done inner submit');
    return $return;
  }

  /**
   * When a field is present in the model that is an fkField, this means it contains a lookup
   * caption that must be searched for in the fk entity. This method does the searching and
   * puts the fk id back into the main model so when it is saved, it links to the correct fk
   * record.
   *
   * @return boolean True if all lookups populated successfully.
   */
  private function populateFkLookups() {
    $r=true;
    if (array_key_exists('fkFields', $this->submission)) {
      foreach ($this->submission['fkFields'] as $a => $b) {
        // Establish the correct model
        $m = ORM::factory($b['fkTable']);

        // Check that it has the required search field
        if (array_key_exists($b['fkSearchField'], $m->table_columns)) {
          $fkRecords = $m->like(array(
              $b['fkSearchField'] => $b['fkSearchValue']))
              ->find_all();
          if (count($fkRecords)!=1) {
            $this->errors[$a] = 'Could not find a '.ucwords($b['fkTable']).' by looking for "'.$b['fkSearchValue'].
                '" in the '.ucwords($b['fkSearchField']).' field.';
            $r=false;
          }
          $this->submission['fields'][$b['fkIdField']] = $fkRecords[0]->id;
        }
      }
    }
    return $r;
  }

  /**
   * Generate any records that this model contains an FK reference to in the
   * Supermodels part of the submission.
   */
  private function createParentRecords() {
    // Iterate through supermodels, calling their submit methods with subarrays
    if (array_key_exists('superModels', $this->submission)) {
      foreach ($this->submission['superModels'] as $a) {
        // Establish the right model
        $m = ORM::factory($a['model']['id']);

        // Call the submit method for that model and
        // check whether it returns correctly
        $m->submission = $a['model'];
        $result = $m->inner_submit();
        if ($result) {
          Kohana::log("debug", "Setting field ".$a['fkId']." to ".$result);
          $this->submission['fields'][$a['fkId']]['value'] = $result;
        } else {
          if (!in_array($m, $this->linkedModels)) {
            array_push($this->linkedModels, $m);
          }
          return false;
        }
        // We need to try attaching the model to get details back
        $this->add($m);
      }
    }
    return true;
  }

  /**
   * Generate any records that refer to this model in the subModela part of the
   * submission.
   */
  private function createChildRecords() {
    if (array_key_exists('subModels', $this->submission)) {
      // Iterate through the subModel array, linking them to this model
      foreach ($this->submission['subModels'] as $a) {

        Kohana::log("debug", "Submitting submodel ".$a['model']['id'].".");

        // Establish the right model
        $m = ORM::factory($a['model']['id']);

        // Set the correct parent key in the subModel
        $fkId = $a['fkId'];
        Kohana::log("debug", "Setting field ".$fkId." to ".$this->id);
        $a['model']['fields'][$fkId]['value'] = $this->id;

        // Call the submit method for that model and
        // check whether it returns correctly
        $m->submission = $a['model'];
        $result = $m->inner_submit();
        if (!$result) {
          // Remember this model so that its errors can be reported
          if (!in_array($m, $this->linkedModels)) {
            array_push($this->linkedModels, $m);
          }
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Function that iterates through the required attributes of the current model, and
   * ensures that each of them has a submodel in the submission.
   */
  private function checkRequiredAttributes() {
    $this->missingAttrs = array();

    // Test if this model has an attributes sub-table.
    if (isset($this->has_attributes) && $this->has_attributes) {
      $db = new Database();
      $attr_entity = $this->object_name.'_attribute';
      $db->from($attr_entity.'s_websites');
      $db->join($attr_entity.'s', $attr_entity.'s.id', $attr_entity.'s_websites.'.$attr_entity.'_id', 'right');
      $db->select($attr_entity.'s.id', $attr_entity.'s.caption');
      $db->like('validation_rules','required');
      $db->where($attr_entity.'s.deleted', 'f');
      $db->where($attr_entity.'s_websites.website_id', $this->identifiers['website_id']);
      $db->in($attr_entity.'s_websites.restrict_to_survey_id', array($this->identifiers['survey_id'], null));
      $result=$db->get();
      $got_values=array();
      // Attributes are stored in a metafield. Find the ones we actually have a value for
      if (array_key_exists('metaFields', $this->submission) &&
          array_key_exists($this->attrs_submission_name, $this->submission['metaFields']))
      {
        foreach ($this->submission['metaFields'][$this->attrs_submission_name]['value'] as $idx => $attr) {
          if ($attr['fields']['value']['value']) {
            array_push($got_values, $attr['fields'][$this->object_name.'_attribute_id']['value']);
          }
        }
      }
      foreach($result as $row) {
        if (!in_array($row->id, $got_values)) {
          $this->missingAttrs[$this->attrs_field_prefix.':'.$row->id]='Please specify a value for the '.$row->caption;
          return false;
        }
      }
    }
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
          $a["fk_".substr($name, 0, -3)] = $type;
        }
      }
    }
    return $a;
  }

 /**
  * Create the records for any attributes attached to the current submission.
  */
  protected function createAttributes() {
    if (isset($this->has_attributes) && $this->has_attributes) {
      // Attributes are stored in a metafield.
      if (array_key_exists('metaFields', $this->submission) &&
          array_key_exists($this->attrs_submission_name, $this->submission['metaFields']))
      {
        foreach ($this->submission['metaFields'][$this->attrs_submission_name]['value'] as $idx => $attr)
        {
          $value = $attr['fields']['value'];
          if ($value['value'] != '') {
            $attrId = $attr['fields'][$this->object_name.'_attribute_id']['value'];
            $oa = ORM::factory($this->object_name.'_attribute', $attrId);
            $vf = null;
            switch ($oa->data_type) {
              case 'T':
                $vf = 'text_value';
                break;
              case 'F':
                $vf = 'float_value';
                break;
              case 'D':
                // Date
                $vd=vague_date::string_to_vague_date($value['value']);
                $attr['fields']['date_start_value']['value'] = $vd['start'];
                $attr['fields']['date_end_value']['value'] = $vd['end'];
                $attr['fields']['date_type_value']['value'] = $vd['type'];
                break;
              case 'V':
                // Vague Date
                $vd=vague_date::string_to_vague_date($value['value']);
                $attr['fields']['date_start_value']['value'] = $vd['start'];
                $attr['fields']['date_end_value']['value'] = $vd['end'];
                $attr['fields']['date_type_value']['value'] = $vd['type'];
                break;
              default:
                // Lookup in list
                $vf = 'int_value';
                break;
            }

            if ($vf != null) $attr['fields'][$vf] = $value;
            // Hook to the owning entity (the sample, location or occurrence)
            $attr['fields'][$this->object_name.'_id']['value'] = $this->id;

            $oam = ORM::factory($this->object_name.'_attribute_value');
            $oam->submission = $attr;
            if (!$oam->inner_submit()) {
              // For attribute value errors, we need to report e.g smpAttr:6 as the error key name, not
              // the table and field name as normal.
              $oam->forceErrorKey = $this->attrs_field_prefix.':'.$attrId;
              if (!in_array($oam, $this->linkedModels)) {
                array_push($this->linkedModels, $oam);
              }
              return false;
            }
          }
        }
      }
    }
    return true;
  }

  /**
   * Overrideable function to allow some models to handle additional records created on submission.
   * @return boolean True if successful.
   */
  protected function postSubmit() {
    return true;
  }

  /**
   * Accessor for search_field.
   * @return The searchable field in this model.
   */
  public function getSearchField() {
    return $this->search_field;
  }

  /**
   * Accessor for children.
   * @return The children in this model or an empty string.
   */
  public function getChildren() {
    if (isset($this->children)) {
      return $this->children;
    } else {
      return '';
    }
  }

  /**
   * Override the clear method to force cleanup of linked models.
   */
  public function clear() {
    $this->linkedModels=array();
    parent::clear();
  }

}

?>
