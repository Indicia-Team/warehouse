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
 * Model class for the Triggers table.
 */
class Trigger_Action_Model extends ORM {
  public $search_field='id';

  protected $belongs_to = array(
    'created_by'=>'user',
    'updated_by'=>'user',
    'trigger'
  );

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('trigger_id', 'required');
    $array->add_rules('type', 'required');
    $values = $array->as_array();
    $this->unvalidatedFields = array('param1','param2');
    // for email notifications, param 3 is a comma separated list of emails.
    if ($values['type']=='E') {
      $array->add_rules('param3', 'email_list');
    } else
      $this->unvalidatedFields[] = 'param3';
    return parent::validate($array, $save);
  }
}