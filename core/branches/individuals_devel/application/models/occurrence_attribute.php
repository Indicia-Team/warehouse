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
 * Model class for the Occurrence_Attributes table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Occurrence_Attribute_Model extends ATTR_ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'termlist');

  protected $has_many = array(
    'occurrence_attributes_values',
  );

  protected $has_and_belongs_to_many = array('websites');
  
  /**
   * Get the list of known system functions for occurrence attributes, each with a title and description
   * of their usage.
   * @return array List of the system known functions that an occurrence attribute can have.
   */
  public function get_system_functions() {
    return array(
      'sex_stage' => array(
        'title'=>'Sex/stage',
        'description'=>'A text or lookup attribute where the value corresponds to the sex or life stage of the recorded organism(s).'
      ),
      'sex_stage_count' => array(
        'title'=>'Count or abundance of a sex or life stage.',
        'description'=>'An attribute corresponding to the abundance of a particular stage, indicated by the caption of the attribute. ' .
          'The attribute can be an integer count, in which case zero means not present, a checkbox corresponding to presence/absence, ' .
          'or a lookup where terms "Absent","None", "Not Present" or "0" indicate not present.'
      ),
      'certainty' => array(
        'title' => 'Certainty of the record accuracy',
        'description'=>'Attribute value describes how certain the recorder is of the record. Please ensure that any terms corresponding '.
          'to a certain record have a sort order of less than 100, any terms corresponding to a likely record which is not certain have '.
          'a sort order of 100-199 and any terms corresponding to a record which is not at least considered likely have a sort order of '.
          '200 or more.'
      ),
      'det_first_name' => array(
        'title' => 'Determiner First name',
        'description'=>'A text attribute corresponding to the first name of the person determining (identifying) the record.'
      ),
      'det_last_name' => array(
        'title' => 'Determiner Last name',
        'description'=>'A text attribute corresponding to the last name of the person determining (identifying) the record.'
      ),
    );
  }

}
	