<?php

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

    $array->add_rules('caption', 'required');
    $array->add_rules('data_type', 'required');
    if ($array['data_type'] == 'L') {
      if (empty($array['termlist_id'])) {
        $this->errors['termlist_id']='A lookup (term) list must be provided when the data type is "Lookup List"';
        $save=false;
      } else
        $this->termlist_id = $array['termlist_id'];
    }
    $this->multi_value = array_key_exists('multi_value', $array) ? $array['multi_value'] : 'f';
    $this->public = array_key_exists('public', $array) ? $array['public'] : 'f';
    $this->validation_rules = $array['validation_rules'];
    // do validation for validation_rules here
    $this->populate_validation_rules();
    // do validation for validation_rules here
    if ($this->valid_length == true){
      if (!empty($this->valid_length_min) AND !is_numeric($this->valid_length_min)) {
        $this->errors['valid_length']='Minimum length must be empty or a number';
        $save=false;
      }
      else if (!empty($this->valid_length_max) AND !is_numeric($this->valid_length_max)) {
        $this->errors['valid_length']='Maximum length must be empty or a number';
        $save=false;
      } else if (empty($this->valid_length_min) AND empty($this->valid_length_max)) {
        $this->errors['valid_length']='One or both minimum length and/or maximum length must be provided';
        $save=false;
      }
    }
    if ($this->valid_decimal == true){
      if (empty($this->valid_dec_format)) {
        $this->errors['valid_decimal']='Format String must be provided';
        $save=false;
      }
    }
    if ($this->valid_regex == true){
      if (empty($this->valid_regex_format)) {
        $this->errors['valid_regex']='Format String must be provided';
        $save=false;
      }
    }
    if ($this->valid_min == true){
      if (empty($this->valid_min_value)) {
        $this->errors['valid_min']='Minimum value must be provided';
        $save=false;
      }
    }
    if ($this->valid_max == true){
      if (empty($this->valid_max_value)) {
        $this->errors['valid_max']='Maximum value must be provided';
        $save=false;
      }
    }
    $parent_valid = parent::validate($array, $save);
    return $save AND $parent_valid;
  }

  public function preSubmit() {
    if (!is_numeric($this->submission['fields']['survey_id']['value']))
      $this->submission['fields']['survey_id']['value'] = NULL;
    if ($this->submission['fields']['disabled_input']['value'] == 'NO') {
      $this->checkSubmitNumericField('termlist_id');
      $this->checkSubmitBoolField('multi_value');
      $this->checkSubmitBoolField('public');

      $rules = array();
      foreach(array('required', 'alpha', 'email', 'url', 'alpha_numeric', 'numeric', 'standard_text') as $rule) {
        if (isset($this->submission['fields']['valid_'.$rule]))
          $rules[] = $rule;
      }

      if (isset($this->submission['fields']['valid_length']))		$rules[] = 'length['.$this->submission['fields']['valid_length_min']['value'].','.$this->submission['fields']['valid_length_max']['value'].']';
      if (isset($this->submission['fields']['valid_decimal']))	$rules[] = 'decimal['.$this->submission['fields']['valid_dec_format']['value'].']';
      if (isset($this->submission['fields']['valid_regex']))		$rules[] = 'regex['.$this->submission['fields']['valid_regex_format']['value'].']';
      if (isset($this->submission['fields']['valid_min']))		$rules[] = 'min['.$this->submission['fields']['valid_min_value']['value'].']';
      if (isset($this->submission['fields']['valid_max']))		$rules[] = 'max['.$this->submission['fields']['valid_max_value']['value'].']';

      if (!empty($rules))
        $this->submission['fields']['validation_rules'] = array('value' => implode("\r\n", $rules));
      else
        $this->submission['fields']['validation_rules'] = array('value' => NULL);
    }
    return parent::preSubmit();
  }

  /**
   * Checks a field in the submission. If missing or not a number, sets it to null.
   *
   * @param string $field Name of field to check
   */
  private function checkSubmitNumericField($field) {
    if (!array_key_exists($field, $this->submission['fields']) ||
        !is_numeric($this->submission['fields']['termlist_id']['value'])) {
      $this->submission['fields']['termlist_id']=NULL;
    }
  }

  /**
   * Checks a boolean field in the submission. If missing or false, sets it to f, otherwise t.
   *
   * @param string $field Name of field to check
   */
  private function checkSubmitBoolField($field) {
    if (!array_key_exists($field, $this->submission['fields']) ||
        !$this->submission['fields'][$field]['value']) {
      $this->submission['fields'][$field]='f';
    } else {
      $this->submission['fields'][$field]='t';
    }
  }

  public function populate_validation_rules() {
    if (empty($this->validation_rules)) return;

    $rules_list = explode("\r\n", $this->validation_rules);
    foreach($rules_list as $rule) {
      // argument extraction is complicated by fact that for regex holds a regular expression.

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

}

?>
