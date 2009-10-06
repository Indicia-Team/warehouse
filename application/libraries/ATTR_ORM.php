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
 * @subpackage LIbraries
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

abstract class ATTR_ORM extends ORM {

  public $valid_required;
  public $valid_length;
  public $valid_length_min;
  public $valid_length_max;
  public $valid_alpha;
  public $valid_email;
  public $valid_url;
  public $valid_alpha_numeric;
  public $valid_numeric;
  public $valid_standard_text;
  public $valid_decimal;
  public $valid_dec_format;
  public $valid_regex;
  public $valid_regex_format;
  public $valid_min;
  public $valid_min_value;
  public $valid_max;
  public $valid_max_value;

  protected $search_field='caption';

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $this->unvalidatedFields = array('validation_rules', 'public', 'multi_value', 'deleted');
    $array->add_rules('caption', 'required');
    $array->add_rules('data_type', 'required');       
    if (array_key_exists('data_type', $array->as_array()) && $array['data_type'] == 'L') {      
      if (empty($array['termlist_id'])) {        
        $array->add_rules('termlist_id', 'required');
      } else
        array_push($this->unvalidatedFields, 'termlist_id');
    }    
    if (array_key_exists('validation_rules', $array->as_array())) {
      $this->validation_rules = $array['validation_rules'];      
      $save = $save && $this->validateValidationRules();
    } else {
      $this->validation_rules = null;
    }  
    $parent_valid = parent::validate($array, $save);   
    return $save && $parent_valid;
  }
  
  /** 
   * Applies validation logic to the loaded validation rules - e.g. for min validation we must have a min value to 
   * check against.
   * 
   * @return boolean Returns true if successful.
   */
  private function validateValidationRules() {
    $r = true;     
    // do validation for validation_rules here
    $this->populate_validation_rules();
    // do validation for validation_rules here
    if ($this->valid_length == true){
      if (!empty($this->valid_length_min) AND !is_numeric($this->valid_length_min)) {
        $this->errors['valid_length']='Minimum length must be empty or a number';
        $r=false;
      }
      else if (!empty($this->valid_length_max) AND !is_numeric($this->valid_length_max)) {
        $this->errors['valid_length']='Maximum length must be empty or a number';
        $r=false;
      } else if (empty($this->valid_length_min) AND empty($this->valid_length_max)) {
        $this->errors['valid_length']='One or both minimum length and/or maximum length must be provided';
        $r=false;
      }
    }
    if ($this->valid_decimal == true){
      if (empty($this->valid_dec_format)) {
        $this->errors['valid_decimal']='Format String must be provided';
        $r=false;
      }
    }
    if ($this->valid_regex == true){      
      if (empty($this->valid_regex_format)) {
        $this->errors['valid_regex']='Format String must be provided';
        $r=false;
      }
    }
    if ($this->valid_min == true){
      if (empty($this->valid_min_value) && $this->valid_min_value!=0) {
        $this->errors['valid_min']='Minimum value must be provided';
        $r=false;
      }
    }
    if ($this->valid_max == true){
      if (empty($this->valid_max_value)) {
        $this->errors['valid_max']='Maximum value must be provided';
        $r=false;
      }
    }    
    return $r;
  }

  public function populate_validation_rules() {
    if (empty($this->validation_rules)) return;

    $rules_list = explode("\r\n", $this->validation_rules);
    foreach($rules_list as $rule) {
      // argument extraction is complicated by fact that for regex holds a regular expression.
      if (substr($rule, -2)=='[]') {
        // Remove the empty params as this breaks the regex  
        $rule = substr($rule, 0, -2);
      }
      // Use the same method as the validation object
      $args = NULL;
      if (preg_match('/^([^\[]++)\[(.+)\]$/', $rule, $matches))
      {
        // Split the rule into the function and args
        $rule = $matches[1];
        $args = $matches[2];
      }      
      switch ($rule) {
        case 'required' :	$this->valid_required = true;
                break;
        case 'alpha' :	$this->valid_alpha = true;
                break;
        case 'email' :	$this->valid_email = true;
                break;
        case 'url' :	$this->valid_url = true;
                break;
        case 'alpha_numeric' :	$this->valid_alpha_numeric = true;
                break;
        case 'numeric' :	$this->valid_numeric = true;
                break;
        case 'standard_text' :	$this->valid_standard_text = true;
                break;
        case 'decimal' :	$this->valid_decimal = true;
                $this->valid_dec_format = $args;
                break;
        case 'regex' :	$this->valid_regex = true;        
                $this->valid_regex_format = $args;
                break;
        case 'min' :	$this->valid_min = true;
                $this->valid_min_value = $args;
                break;
        case 'max' :	$this->valid_max = true;
                $this->valid_max_value = $args;
                break;
        case 'length' :	$this->valid_length = true;
                $args = preg_split('/(?<!\\\\),\s*/', $matches[2]);
                $this->valid_length_min = $args[0];
                $this->valid_length_max = $args[1];
                break;
      }
    }
  }
  
  /**
   * As we share a generic form, the submission structure is generic to all custom attributes.   *
   */
  public function get_submission_structure() {
    return array(
    	'model'=>'custom_attribute',
      'fieldPrefix'=>'custom_attribute',
      'metaFields' => array('disabled_input')      
    );
  }
  
  /**
   * If saving a re-used attribute, then don't bother posting the main record data as it can't be changed. The postSubmit
   * can still occur though to link it to websites and surveys.
   *   
   * @return integer Id of the attribute.
   */
  protected function validateAndSubmit() {
    if ($this->submission['metaFields']['disabled_input']['value']=='YES') {      
      $this->find($this->submission['fields']['id']['value']);
      return $this->id;
    } else {     
      return parent::validateAndSubmit();
    }
  }
  
  protected function postSubmit() {
    // Record has saved correctly or is being reused
    /*if(!is_null($this->gen_auth_filter))
      $websites = ORM::factory('website')->in('id', $this->gen_auth_filter['values'])->find_all();
    else*/
      $websites = ORM::factory('website')->find_all();
    foreach ($websites as $website) {
      // First check for non survey specific checkbox
      $this->set_attribute_website_record($this->id, $website->id, null, isset($_POST['website_'.$website->id]));
      $surveys = ORM::factory('survey')->where('website_id', $website->id)->find_all();
      foreach ($surveys as $survey) {
        $this->set_attribute_website_record($this->id, $website->id, $survey->id, isset($_POST['website_'.$website->id.'_'.$survey->id]));
      }
    }          
    return true;
  }
  
  private function set_attribute_website_record($attr_id, $website_id, $survey_id, $checked)
  {    
    $attributes_website = ORM::factory(inflector::plural($this->object_name).'_website',
            array($this->object_name.'_id' => $attr_id
                , 'website_id' => $website_id
                , 'restrict_to_survey_id' => $survey_id));
    if($attributes_website->loaded) {
      // existing record
      if($checked == true and $attributes_website->deleted == 't') {
        $attributes_website->__set('deleted', 'f');
        $attributes_website->save();
      } else if ($checked == false and $attributes_website->deleted == 'f')  {
        $attributes_website->__set('deleted', 't');
        $attributes_website->save();
      }
    } else if ($checked == true) {
           $save_array = array(
                'id' => $attributes_website->object_name
                ,'fields' => array($this->object_name.'_id' => array('value' => $attr_id)
                          ,'website_id' => array('value' => $website_id)
                           ,'restrict_to_survey_id' => array('value' => $survey_id)
                          ,'deleted' => array('value' => 'f'))
                ,'fkFields' => array()
                ,'superModels' => array());
      $attributes_website->submission = $save_array;
      $attributes_website->submit();
    }
  }

}

?>