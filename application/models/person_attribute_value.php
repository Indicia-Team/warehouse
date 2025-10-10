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
 * Model class for the Person_Attribute_Values table.
 */
class Person_Attribute_Value_Model extends Attribute_Value_ORM {
  public $search_field='text_value';

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'person', 'person_attribute');

  public function validate(Validation $array, $save = FALSE) {
    self::attribute_validation($array, 'person');
    return parent::validate($array, $save);
  }

  /**
   * Allow submission with a User ID, rather than a Person ID, for simplicity.
   */
  protected function preSubmit()
  {
    if (array_key_exists('user_id', $this->submission['fields']) && !array_key_exists('person_id', $this->submission['fields'])) {
      $user = $this->db->select('person_id')->from('users')->where(array('id'=>$this->submission['fields']['user_id']['value']))
          ->get()->current();
      $this->submission['fields']['person_id']=array('value'=>$user->person_id);
    }
  }
}
