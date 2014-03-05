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
 * Model class for the known_subject_attributes table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Known_subject_attribute_Model extends ATTR_ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'termlist');

  // The known_subject attributes are website wide
  protected $has_survey_restriction = false;

  protected $has_many = array(
    'known_subject_attribute_values',
    );

  protected $has_and_belongs_to_many = array('websites');

  /**
   * Get the list of known system functions for known_subject attributes, each with a title and description
   * of their usage.
   * @return array List of the system known functions that an known_subject attribute can have.
   */
  public function get_system_functions() {
    return array(
      /*
      // add system function definitions as required in the form
      'sex_stage' => array(
        'title'=>'Sex/stage',
        'description'=>'A text or lookup attribute where the value corresponds to the sex or life stage of the recorded organism(s).'
      ),
       */
    );
  }

}
