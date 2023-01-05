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

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the Users_Websites table.
 */
class Users_website_Model extends ORM {

  protected $has_one = [
    'user',
    'website',
    'site_role',
  ];
  protected $belongs_to = [
    'created_by' => 'user',
    'updated_by' => 'user'
  ];

  public function validate(Validation $array, $save = FALSE) {
    if ($save) {
      $this->applyLicence($array->as_array());
    }
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');

    $this->unvalidatedFields = [
      'user_id',
      'website_id',
      'site_role_id',
      'licence_id',
      'media_licence_id',
    ];
    return parent::validate($array, $save);
  }

  /**
   * If a user sets a licence for the first time, update their records.
   *
   * @param array $new
   *   New data being saved.
   */
  public function applyLicence(array $new) {
    // Are we applying a first time licence for records belonging to this user?
    if (!empty($new['licence_id']) && empty($this->licence_id)) {
      $sql = <<<SQL
update samples s
set licence_id=$new[licence_id]
from surveys su
where su.website_id=$new[website_id]
and s.created_by_id=$new[user_id]
and su.id=s.survey_id
and s.licence_id is null;
SQL;
      $this->db->query($sql);
      <<<SQL
update cache_samples_nonfunctional snf
set licence_code=l.code
from licences l, cache_samples_functional s
where s.id=snf.id
and s.website_id=$new[website_id]
and s.created_by_id=$new[user_id]
and coalesce(snf.licence_code, '')<>l.code
and s.licence_id=l.id
and l.id=$new[licence_id]
SQL;
      $this->db->query($sql);
      $sql = <<<SQL
update cache_occurrences_functional o
set licence_id=l.id
from licences l
where o.website_id=$new[website_id]
and o.created_by_id=$new[user_id]
and o.licence_id is null
and l.id=$new[licence_id]
SQL;
      $this->db->query($sql);
      $sql = <<<SQL
update cache_occurrences_nonfunctional onf
set licence_code=l.code
from licences l, cache_occurrences_functional o
where o.id=onf.id
and o.website_id=$new[website_id]
and o.created_by_id=$new[user_id]
and coalesce(onf.licence_code, '')<>l.code
and o.licence_id=l.id
and l.id=$new[licence_id]
SQL;
      $this->db->query($sql);
    }
    // Same again, for media licences.
    if (!empty($new['media_licence_id']) && empty($this->media_licence_id)) {
      $sql = <<<SQL
update sample_media u
set licence_id=$new[media_licence_id]
from samples s
inner join surveys su on su.id=s.survey_id and su.website_id=$new[website_id]
where u.created_by_id=$new[user_id]
and u.sample_id=s.id
and u.licence_id is null;
SQL;
      $this->db->query($sql);
      $sql = <<<SQL
update occurrence_media u
set licence_id=$new[media_licence_id]
from occurrences o
where o.website_id=$new[website_id]
and u.created_by_id=$new[user_id]
and u.occurrence_id=o.id
and u.licence_id is null;
SQL;
      $this->db->query($sql);
      $sql = <<<SQL
update location_media u
set licence_id=$new[media_licence_id]
from locations l
inner join locations_websites lw on lw.location_id=l.id and lw.website_id=$new[website_id] and lw.deleted=false
where u.created_by_id=$new[user_id]
and u.location_id=l.id
and u.licence_id is null;
SQL;
      $this->db->query($sql);
    }
  }

}
