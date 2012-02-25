<?php defined('SYSPATH') or die('No direct script access.');

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
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the People table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Person_Model extends ORM {
  public $search_field='surname';
  
  protected $has_one = array('user');
  protected $has_many = array('person_attribute_values');
  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'title');
  
  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes=true;
  // A public attribute does NOT need to be linked to a website to form part of the submissable data for a person (unlike, say,
  // sample attributes which are not submissable unless linked via a sample_attributes_websites record).
  public $include_public_attributes = true;
  protected $attrs_submission_name='psnAttributes';
  protected $attrs_field_prefix='psnAttr';

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('first_name', 'required', 'length[1,30]');
    $array->add_rules('surname', 'required', 'length[1,30]');
    $array->add_rules('initials', 'length[1,6]');
    $array->add_rules('address', 'length[1,200]');
    // If this person is new, then setting id to -1 causes uniqueness check to include all existing records.
    $id = array_key_exists('id', $array->as_array()) ? $array['id'] : -1; 
    $array->add_rules('email_address', 'email', 'length[1,50]', 'unique[people,email_address,'.$id.']');    
    $array->add_rules('website_url', 'length[1,1000]', 'url[lax]');  
    $array->add_rules('external_key', 'length[1,50]');
    
    // Any fields that don't have a validation rule need to be copied into the model manually
    if (isset($array['title_id'])) $this->title_id = (is_numeric ($array['title_id']) ? $array['title_id'] : NULL);
    $this->unvalidatedFields = array('deleted');
    return parent::validate($array, $save);
  }

  public function email_validate(Validation $array, $save = FALSE) {
    // this function is used when updating the email address through the new_password screen
    // This would happen on initial login for the admin user - the setup procedure does not include the email address for the
    // the admin user
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('email_address', 'required', 'email', 'length[1,50]', 'unique[people,email_address,'.$array->id.']');

    return parent::validate($array, $save);
  }

  public function preSubmit() {
    if (isset($this->submission['fields']['email_address']))
      if ($this->submission['fields']['email_address']['value'] == '')
        $this->submission['fields']['email_address']['value'] = NULL;
    if (isset($this->submission['fields']['title_id']))
      if (!is_numeric($this->submission['fields']['title_id']['value']))
        $this->submission['fields']['title_id']['value'] = NULL;
    return parent::preSubmit();
  }

  /**
   * Return a displayable caption for the item.
   * For People, this should be a combination of the Firstname and Surname.
   */
  public function caption()
  {
    return ($this->first_name.' '.$this->surname);
  }
  
  /**
   * Indicates if this model type can create new instances from data supplied in its caption format. 
   * @return boolean, override to true if your model supports this.
   */
  protected function canCreateFromCaption() {
    return true;
  }
  
  /**
   * Overridden if this model type can create new instances from data supplied in its caption format. 
   * @return integer, the id of the first matching record with the supplied caption or 0 if no match.
   */
  protected function findByCaption($caption) {
    $id = 0;
    $caption = trim($caption);
    $matches = $this->db->from('list_people')->
      select('caption', 'id')->
      like('caption', $caption)
      ->get();
    foreach ($matches as $row) {
      if (strtolower($row->caption) === strtolower($caption)) {
        $id = $row->id;
        break;
      }
    }
    return $id;
  }
  
  /**
   * Overridden if this model type can create new instances from data supplied in its caption format. 
   * Does nothing if not overridden.
   * @return boolean, override to true if your model supports this.
   */
  protected function handleCaptionSubmission() {
    // create record from caption data
    if (!empty($this->submission['fields']['caption']))
      $this->deriveFieldsFromCaption();
    return true;
  }

  /**
   * User the caption value to provide values for all required fields so that a record can be
   * created from data supplied in its caption format. 
   * @return boolean, true if able to infer field values, false if not.
   */
  protected function deriveFieldsFromCaption() {
    // todo - improve these rules
    Kohana::log('debug', 'Commencing person deriveFieldsFromCaption. '.
      print_r($this->submission['fields'], true));
      // check we have exactly one caption
    if (sizeof($this->submission['fields']['caption'])!==1 
      || empty($this->submission['fields']['caption']['value']))
      return true;
    $names = explode(' ', trim($this->submission['fields']['caption']['value']));
    $count = sizeof($names);
    if ($count>0 && empty($this->submission['fields']['first_name']) 
      && empty($this->submission['fields']['surname'])) {
      $this->submission['fields']['first_name'] = array();
      $this->submission['fields']['surname'] = array();
      $this->submission['fields']['first_name']['value'] = '';
      for ($i = 0; $i < $count-1; $i++) {
        $this->submission['fields']['first_name']['value'] .= ucfirst(strtolower($names[$i]));
      }
      $this->submission['fields']['surname']['value'] = ucfirst($names[$count-1]);
    }
    unset($this->submission['fields']['caption']);
    Kohana::log('debug', 'Leaving person deriveFieldsFromCaption. '.print_r($this->submission['fields'], true));
    return true;
  }
  
  /** 
   * Prepares the db object query builder to query the list of custom attributes for this model.
   * @param boolean $required Optional. Set to true to only return required attributes (requires 
   * the website and survey identifier to be set).
   * @param int @typeFilter Not used
   */
  protected function setupDbToQueryAttributes($required = false, $typeFilter = null) {
    $this->db->select('person_attributes.id', 'person_attributes.caption');
    $this->db->from('person_attributes');
    
    if ($required && $this->id!==0) {
      // extra joins to link to the person websites so we can find which fields are required
      $this->db->join('person_attributes_websites','person_attributes_websites.person_attribute_id', 'person_attributes.id', 'left');
      $this->db->join('users_websites', 'users_websites.website_id', 'person_attributes_websites.website_id', 'left');
      $this->db->join('users', 'users.id', 'users_websites.user_id', 'left');
      $this->db->in('users.person_id', array(null, $this->id));
      // note we concatenate the validation rules to check both global and website specific rules for requiredness. 
      $this->db->where("(person_attributes_websites.validation_rules like '%required%' or person_attributes.validation_rules like '%required%')");
    } elseif ($required) {
      $this->db->like('person_attributes.validation_rules', '%required%');
    }
    $this->db->where('person_attributes.deleted', 'f');
    $this->db->orwhere('person_attributes.public','t');
    // deliberate repeat of this clause - it needs to be both sides of the orwhere
    $this->db->where('person_attributes.deleted', 'f');
    if ($required && $this->id!==0) {
      $this->db->in('person_attributes_websites.deleted', array('f', null));
      $this->db->in('users.deleted', array('f', null)); 
      $this->db->where('users_websites.site_role_id is not null');
    }
  }
  
  /**
   * Override implementation of a method which retrieves the cache key required to store the list
   * of required fields. For people each combination of website IDs defines a cache entry.
   * @param type $typeFilter Not used
   */
  protected function getRequiredFieldsCacheKey($typeFilter) {
    $keyArr = array_merge(array('required', $this->object_name), $this->identifiers);
    if ($this->id!==0) {
      // find the websites for this person ID
      $r = $this->db->select('website_id')
          ->from('users')
          ->join('users_websites', 'users_websites.user_id', 'users.id')
          ->where(array('users.deleted'=>'f', 'users.person_id'=>$this->id))
          ->where('users_websites.site_role_id is not null')
          ->orderby('website_id','ASC')
          ->get()->result();
      foreach ($r as $row) {
        $keyArr[] = $row->website_id;
      }
    }
    return implode('-', $keyArr);
  }
}
