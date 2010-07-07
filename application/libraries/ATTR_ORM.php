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

abstract class ATTR_ORM extends Valid_ORM {

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
    $parent_valid = parent::validate($array, $save);   
    return $save && $parent_valid;
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