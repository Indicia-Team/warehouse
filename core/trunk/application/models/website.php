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
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the Websites table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Website_Model extends ORM
{
  protected $auth = null;

  protected $has_many = array(
    'termlists',
    'taxon_lists'
  );
  protected $belongs_to = array(
    'created_by'=>'user',
    'updated_by'=>'user'
  );
  protected $has_and_belongs_to_many = array(
    'locations',
    'users'
  );

  public $password2;

  /**
   * Validate and save the data.
   */
  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('title', 'required', 'length[1,100]');
    $array->add_rules('url', 'required', 'length[1,500]', 'url');
    // NOTE password is stored unencrypted.
    // The repeat password held in password2 does not get through preSubmit during the submit process
    // and is not present in the validation object at this point. The "matches" validation rule does not
    // work in these circumstances, so a new "matches_post" has been inserted into MY_valid.php
    $array->add_rules('password', 'required', 'length[7,30]', 'matches_post[password2]');
    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'description',
      'deleted'
    );

    return parent::validate($array, $save);
  }

  /**
   * Filters to the user's authorised list of ORM websites. This acts like calling
   * where on the ORM object.
   * @param string $role The role to require on that website. Defaults to Admin, other
   * possible values are User or Editor.
   * @return ORM object so can be used for method chaining.
   */
  public function in_allowed_websites($role='Admin') {
    if (!isset($this->auth))
      $this->auth = new Auth;

    if (!$this->auth->logged_in('CoreAdmin')) {
      $websites = $this->db->select('users_websites.website_id')
          ->from('users_websites')
          ->join('site_roles', 'site_roles.id', 'users_websites.site_role_id')
          ->where(array('users_websites.user_id'=>$_SESSION['auth_user']->id,
              'site_roles.title'=>$role))->get();
      $arr = array();
      foreach ($websites as $website)
        $arr[] = $website->website_id;
      $this->in('id', $arr);
    }
    return $this;
  }

}

?>
