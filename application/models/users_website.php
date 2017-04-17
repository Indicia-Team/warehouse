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
 * Model class for the Users_Websites table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Users_website_Model extends ORM
{

  protected $has_one = array(
    'user',
    'website',
    'site_role'
  );
  protected $belongs_to = array(
    'created_by'=>'user',
    'updated_by'=>'user'
  );

  public function validate(Validation $array, $save = FALSE) {
    if ($save)
      $this->applyLicence($array->as_array());
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');

    $this->unvalidatedFields = array('user_id', 'website_id', 'site_role_id', 'licence_id');
    return parent::validate($array, $save);
  }

  public function applyLicence($new) {
    // Are we applying a first time licence for records belonging to this user?
    if (!empty($new['licence_id']) && empty($this->licence_id)) {
      $this->db->query("update samples s" .
        " set licence_id=$new[licence_id]" .
        " from surveys su" .
        " where su.website_id=$new[website_id]" .
        " and s.created_by_id=$new[user_id]" .
        " and su.id=s.survey_id" .
        " and licence_id is null"
      );
      $this->db->query("update cache_occurrences_functional o" .
        " set licence_id=l.id" .
        " from licences l" .
        " where o.website_id=$new[website_id]" .
        " and o.created_by_id=$new[user_id]" .
        " and o.licence_id is null" .
        " and l.id=$new[licence_id]"
      );
      $this->db->query("update cache_occurrences_nonfunctional onf" .
        " set licence_code=l.code" .
        " from licences l, cache_occurrences_functional o " .
        " where o.id=onf.id" .
        " and o.website_id=$new[website_id]" .
        " and o.created_by_id=$new[user_id]" .
        " and coalesce(onf.licence_code, '')<>l.code" .
        " and o.licence_id=l.id" .
        " and l.id=$new[licence_id]"
      );
    }
  }
}