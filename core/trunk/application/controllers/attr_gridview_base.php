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
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
* Base class for controllers which provide CRUD access to the lists of custom attributes
* associated with locations, occurrences or sample entities.
*
* @package	Core
* @subpackage Controllers
* @subpackage Controllers
*/

abstract class Attr_Gridview_Base_Controller extends Gridview_Base_Controller {

  public function __construct()
  {    
    parent::__construct($this->prefix.'_attribute', 'custom_attribute/index');
    $this->pagetitle = ucfirst($this->prefix).' Custom Attributes';
    $this->columns = array
    (
      'id'=>'',
      'website'=>'',
      'survey'=>'',
      'caption'=>'',
      'data_type'=>'Data type'
    );
    $this->set_website_access('admin');
  }
  
  /**
   * Returns the shared view for all custom attribute edits.
   */
  protected function editViewName() {
    return 'custom_attribute/custom_attribute_edit';
  }
  
  /**
   * Returns some addition information required by the edit view, which is not associated with 
   * a particular record. 
   */
  protected function prepareOtherViewData($values)
  {    
    return array(
      'name' => ucfirst($this->prefix),
      'controllerpath' => $this->controllerpath,
      'webrec_entity' => $this->prefix.'_attributes_website',
      'webrec_key' => $this->prefix.'_attribute_id'
    );   
  }
  
  /**
   * Force the base class methods to link the form values to controls named custom_attribute:* which allows
   * a single generic form to be used for several different models.      
   */
  protected function getAttrPrefix() {
    return 'custom_attribute';
  }

 /**
   * Setup the values to be loaded into the edit view.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    // Can the user edit the actual attribute? If not they can still assign it to their surveys.
    if ($this->auth->logged_in('CoreAdmin')) {
      $r['metaFields:disabled_input']='NO';
    } else {
      // We need to know if this attribute is unique to the website
      $count = ORM::factory($this->prefix.'_attributes_website')->where($this->model->object_name.'_id',$this->model->id)->find_all()->count();    
      $r['metaFields:disabled_input']=$count<=1 ? 'NO' : 'YES';
    }
    $this->model->populate_validation_rules();
    return $r;  
  }
  
  public function save() {
    if ($_POST['metaFields:disabled_input'] == 'NO') {
      // Build the validation_rules field from the set of controls that are associated with it.
      $rules = array();
      foreach(array('required', 'alpha', 'email', 'url', 'alpha_numeric', 'numeric', 'standard_text','date_in_past') as $rule) {          
        if (array_key_exists('valid_'.$rule, $_POST) && $_POST['valid_'.$rule]==1) {            
          array_push($rules, $rule);
        }
      }
      if (array_key_exists('valid_length', $_POST) && $_POST['valid_length']==1)   $rules[] = 'length['.$_POST['valid_length_min'].','.$_POST['valid_length_max'].']';
      if (array_key_exists('valid_decimal', $_POST) && $_POST['valid_decimal']==1) $rules[] = 'decimal['.$_POST['valid_dec_format'].']';
      if (array_key_exists('valid_regex', $_POST) && $_POST['valid_regex']==1)		 $rules[] = 'regex['.$_POST['valid_regex_format'].']';
      if (array_key_exists('valid_min', $_POST) && $_POST['valid_min']==1)		     $rules[] = 'minimum['.$_POST['valid_min_value'].']';
      if (array_key_exists('valid_max', $_POST) && $_POST['valid_max']==1)		     $rules[] = 'maximum['.$_POST['valid_max_value'].']';

      if (!empty($rules)) {
        $_POST['custom_attribute:validation_rules'] = implode("\r\n", $rules);        
        kohana::log('debug', 'Posted rules '.$_POST['custom_attribute:validation_rules']);
      }
      // Make sure checkboxes have a value
      if (!array_key_exists('custom_attribute:public', $_POST)) $_POST['custom_attribute:public'] = '0'; 
      if (!array_key_exists('custom_attribute:multi_value', $_POST)) $_POST['custom_attribute:multi_value'] = '0';
    }       
    parent::save();    
  }
  
  /**
   * You can always get to the edit page for an attribute though the form might be read only.
   */
  protected function record_authorised ($id)
  {
    return true;
  }

}
