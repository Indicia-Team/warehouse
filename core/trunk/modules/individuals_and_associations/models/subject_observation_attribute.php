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
 * Model class for the subject_observation_attributes table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Subject_observation_attribute_Model extends ATTR_ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'termlist');

  protected $has_many = array(
    'subject_observation_attribute_values',
    );

  protected $has_and_belongs_to_many = array('websites');

  /**
   * Get the list of known system functions for subject_observation attributes, each with a title and description
   * of their usage.
   * @return array List of the system known functions that a subject_observation attribute can have.
   */
  public function get_system_functions() {
    return array(
      // add system function definitions as required in the form
      'gender' => array(
        'title'=>'Sex/Gender',
        'description'=>'A text or lookup attribute where the value corresponds to the sex of the recorded organism(s).',
      ),
      'stage' => array(
        'title'=>'Age/Stage Classification',
        'description'=>'A text or lookup attribute where the value corresponds to the age or life stage of the recorded organism(s).',
      ),
      'attachment' => array(
        'title'=>'Attached Device',
        'description'=>'A text or lookup attribute where the value indicates a type of device which may be attached to the recorded organism(s). '.
          'Unlike an identifier, an attachment is not uniquely identifiable in the field without specialist equipment. '.
          'Examples include data loggers or radio transmitters.',
      ),
      'life_status' => array(
        'title'=>'Alive or Dead',
        'description'=>'A text or lookup attribute where the value indicates if the observed organism was alive, dead or in an unknown state.',
      ),
    );
  }

}
