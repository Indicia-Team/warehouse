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
  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'title');

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
}
