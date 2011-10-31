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
 * Model class for the Users table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class User_Model extends ORM {
  public $search_field='username';

  protected $belongs_to = array('person', 'core_role',
    'created_by'=>'user', 'updated_by'=>'user');
  protected $has_many = array(
    'termlist'=>'created_by','termlist'=>'updated_by',
    'website'=>'created_by','website'=>'updated_by',
    'location'=>'created_by','location'=>'updated_by',
  );

  protected $droppedFields;

  public $users_websites = array();

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    // Any fields that don't have a validation rule need to be copied into the model manually
    // note that some of the fields are optional.
    // Checkboxes only appear in the POST array if they are checked, ie TRUE. Have to convert to PgSQL boolean values, rather than PHP
    $array->pre_filter('trim');
    $id = isset($array->id) ? $array->id : '';
    $array->add_rules('username', 'required', 'length[5,30]', "unique[users,username,$id]");
    if (array_key_exists('password', $_POST)) {
      $array->add_rules('password', 'required', 'length[7,30]', 'matches_post[password2]');
    }
    $this->unvalidatedFields = array(
      'interests',
      'location_name',
      'core_role_id',
      'email_visible',
      'view_common_names',
      'person_id');
    if (!array_key_exists('core_role_id', $array->as_array())) {
    	// if core role id is blank, make sure it is nulled out.
      $array['core_role_id'] = null;
    }
    return parent::validate($array, $save);
  }

  public function preSubmit() {

    if (!is_numeric($this->submission['fields']['core_role_id']['value']))
      $this->submission['fields']['core_role_id']['value'] = NULL;

    $this->submission['fields']['email_visible']	 = array('value' => (isset($this->submission['fields']['email_visible']) ? 't' : 'f'));
    $this->submission['fields']['view_common_names'] = array('value' => (isset($this->submission['fields']['view_common_names']) ? 't' : 'f'));
    // Ensure that the website fields remain available (as they are not proper model columns so get
    // stripped from the model).
    $this->droppedFields = array_diff_key($this->submission['fields'],
        $this->table_columns);
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

  /**
   * After submitting a user record, we also need to preserve the users_websites settings if the
   * submission came from the warehouse form which lets the users_websites be set up from the same
   * submission. If this is the case, the users_websites data will be stored in $this->droppedFields
   * since it is not part of the main user submission.
   */
  public function postSubmit() {
    if (count($this->droppedFields)>0) {
      try {
        $websites = ORM::factory('website')->in_allowed_websites()->find_all();
        foreach ($websites as $website) {
          $users_websites = ORM::factory('users_website', array(
            'user_id' => $this->id,
            'website_id' => $website->id
          ));
          $save_array = array(
            'id' => $users_websites->object_name,
            'fields' => array(
              'user_id' => array('value' => $this->id),
              'website_id' => array('value' => $website->id)
            ),
            'fkFields' => array(),
            'superModels' => array()
          );
          if ($users_websites->loaded || is_numeric($this->droppedFields['website_'.$website->id]['value'])) {
            // If this is an existing users_websites record, preserve the id.
            if ($users_websites->loaded)
              $save_array['fields']['id'] = array('value' => $users_websites->id);
            $save_array['fields']['site_role_id'] = array(
              'value' => is_numeric($this->droppedFields['website_'.$website->id]['value']) ?
                    $this->droppedFields['website_'.$website->id]['value'] :
                    null
            );
            $users_websites->submission = $save_array;
            $users_websites->submit();
          }
        }
      } catch (Exception $e) {
        $this->errors['general']='<strong>An error occurred</strong><br/>'.$e->getMessage();
        error::log_error('Exception during postSubmit in user model.', $e);
        return false;
      }
    }
    return true;
  }

}
