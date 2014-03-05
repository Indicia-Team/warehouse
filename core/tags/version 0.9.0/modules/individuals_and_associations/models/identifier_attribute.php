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
 * Model class for the identifier_attributes table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Identifier_attribute_Model extends ATTR_ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'termlist');

  // The identifier attributes are website wide
  protected $has_survey_restriction = false;

  protected $has_many = array(
    'identifier_attribute_values',
    );

  protected $has_and_belongs_to_many = array('websites');

  /**
   * Get the list of known system functions for identifier attributes, each with a title and description
   * of their usage.
   * @return array List of the system known functions that an identifier attribute can have.
   */
  public function get_system_functions() {
    return array(
      // add system function definitions as required in the form
      'sequence' => array(
        'title'=>'Unique Sequence/Code',
        'description'=>'A text attribute to record the sequence associated with the identifier, for example, a ring code.'
      ),
      'base_colour' => array(
        'title'=>'Base/Background Colour',
        'description'=>'A Lookup attribute referencing a termlist of colours to be recorded as the base or '.
          'background colour associated with the identifier, for example, the main colour on a coloured ring.'
      ),
      'text_colour' => array(
        'title'=>'Text/Code Colour',
        'description'=>'A Lookup attribute referencing a termlist of colours to be recorded as the colour of the '.
          'text/Code/Sequence on the identifier, for example, the colour of any text sequence on a coloured ring.'
      ),
      'position' => array(
        'title'=>'Position on the Organism',
        'description'=>'A Lookup attribute referencing a termlist of possible positions on the body for '.
          'identifiers to be placed or occur.'
      ),
    );
  }

}
