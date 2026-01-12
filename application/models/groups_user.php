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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the groups_users table.
 */
class Groups_user_Model extends ORM {

  protected $has_one = array('group','user');

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('group_id', 'required');
    $array->add_rules('user_id', 'required');
    $array->add_rules('access_level', 'integer');
    $this->unvalidatedFields = array('administrator', 'deleted', 'pending');
    return parent::validate($array, $save);
  }

  /**
   * Override preSubmit to implement an UPSERT. This prevents multiple instances of
   * groups_users records being created e.g. when adding an admin to a group if the
   * user already a member.
   */
  protected function preSubmit() {
    if (empty($this->submission['fields']['id']['value'])) {
      $existing = $this->db->select('id')->from('groups_users')
          ->where(array(
            'group_id' => $this->submission['fields']['group_id']['value'],
            'user_id' => $this->submission['fields']['user_id']['value'],
            'deleted' => 'f'
          ))
          ->get()->as_array(false);
      if (count($existing)) {
        $this->submission['fields']['id']['value'] = $existing[0]['id'];
      }
    }
    parent::preSubmit();
  }


}