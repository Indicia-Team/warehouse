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
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   https://github.com/indicia-team/warehouse/
 */

/**
 * Model class for the identifiers_subject_observation_attribute_values table.
 */
class Identifiers_subject_observation_attribute_value_Model extends Attribute_Value_ORM {
  public $search_field='text_value';

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'identifiers_subject_observation', 'identifiers_subject_observation_attribute');

  public function validate(Validation $array, $save = FALSE) {
    self::attribute_validation($array, 'identifiers_subject_observation');
    return parent::validate($array, $save);
  }
}
