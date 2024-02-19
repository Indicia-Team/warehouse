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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Model class for the Websites table.
 */
class Website_Model extends ORM {
  protected $auth = NULL;

  protected $has_many = [
    'termlists',
    'taxon_lists',
  ];
  protected $belongs_to = [
    'created_by' => 'user',
    'updated_by' => 'user',
  ];
  protected $has_and_belongs_to_many = [
    'locations',
    'users',
  ];

  public $password2;

  /**
   * Validate and save the data.
   */
  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $array->add_rules('title', 'required', 'length[1,100]');
    $array->add_rules('url', 'required', 'length[1,500]', 'url');
    $array->add_rules('staging_urls', 'url_list');
    // NOTE password is stored unencrypted.
    // The repeat password held in password2 does not get through preSubmit
    // during the submit process and is not present in the validation object at
    // this point. The "matches" validation rule does not work in these
    // circumstances, so a new "matches_post" has been inserted into
    // MY_valid.php.
    $array->add_rules('password', 'required', 'length[7,30]', 'matches_post[password2]');
    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
      'description',
      'deleted',
      'verification_checks_enabled',
      'public_key',
      'allow_anon_jwt_post',
    ];

    return parent::validate($array, $save);
  }

  /**
   * Set staging URLs converts from text area to array.
   *
   * @param string $key
   *   Column name
   * @param mixed $value
   *   Value to set.
   */
  public function __set($key, $value) {
    if ($key === 'staging_urls' && is_string($value)) {
      $value = str_replace("\r\n", "\n", $value);
      $value = str_replace("\r", "\n", $value);
      $value = explode("\n", trim($value));
      array_walk($value, 'trim');
    }
    parent::__set($key, $value);
  }

  /**
   * Retrieve staging URLs converts from array to text area.
   *
   * @param string $column
   *   Column name.
   *
   * @return mixed
   *   Column value.
   */
  public function __get($column) {
    if ($column === 'staging_urls') {
      kohana::log('debug', 'Getting staging_urls');
      kohana::log('debug', var_export(parent::__get($column), TRUE));
      $value = trim(parent::__get($column) ?? '', '{}');
      return str_replace(',', "\n", $value);
    }
    return parent::__get($column);
  }

  /**
   * Filters to the user's authorised list of ORM websites.
   *
   * This acts like calling where on the ORM object.
   *
   * @param string $role
   *   The role to require on that website. Defaults to Admin,
   *   other possible values are User or Editor.
   *
   * @return ORM
   *   Returns the object so can be used for method chaining.
   */
  public function in_allowed_websites($role = 'Admin') {
    if (!isset($this->auth)) {
      $this->auth = new Auth();
    }

    if (!$this->auth->logged_in('CoreAdmin')) {
      $websites = $this->db->select('websites.id')
        ->from('users_websites')
        ->join('site_roles', 'site_roles.id', 'users_websites.site_role_id')
        ->join('websites', 'websites.id', 'users_websites.website_id')
        ->where([
          'users_websites.user_id' => $_SESSION['auth_user']->id,
          'site_roles.title' => $role,
          'websites.deleted' => 'f',
        ])->get();
    }
    else {
      $websites = $this->db->select('id')
        ->from('websites')
        ->where(['websites.deleted' => 'f'])
        ->get();
    }
    $arr = [];
    foreach ($websites as $website) {
      $arr[] = $website->id;
    }
    $this->in('id', $arr);
    return $this;
  }

  /**
   * Save method override.
   *
   * Override the save method to additionally refresh index_websites_website_agreement with the
   * latest information about website agreements.
   */
  public function save() {
    $v = parent::save();
    $this->db->query('SELECT refresh_index_websites_website_agreements();');
    return $v;
  }

}
