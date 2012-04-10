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
 * Model class for the Sample_Attributes table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Sample_Attribute_Model extends ATTR_ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'termlist');

  protected $has_many = array(
    'sample_attributes_values',
  );

  protected $has_and_belongs_to_many = array('websites');

  public function validate(Validation $array, $save = FALSE) {
    $this->unvalidatedFields = array('applies_to_location');
    return parent::validate($array, $save);
  }
  
  /**
   * Get the list of known system functions for sample attributes, each with a title and description
   * of their usage.
   * @return array List of the system known functions that a sample attribute can have.
   */
  public function get_system_functions() {
    return array(
      'email' => array(
        'title'=>'Email address',
        'description'=>'A text attribute corresponding to an email address.'
      ),
      'cms_user_id' => array(
        'title'=>'CMS User ID',
        'description'=>'An integer attribute corresponding to the user ID on the client website\'s content management system.'
      ),
      'cms_username' => array(
        'title' => 'CMS Username',
        'description'=>'A text attribute corresponding to the user login name on the client website\'s content management system'
      ),
      'first_name' => array(
        'title' => 'First name',
        'description'=>'A text attribute corresponding to the user\'s first name.'
      ),
      'last_name' => array(
        'title' => 'Last name',
        'description'=>'A text attribute corresponding to the user\'s last name.'
      ),
      'biotope' => array(
        'title' => 'Biotope',
        'description'=>'A text or lookup attribute where the value describes the biotope (often described as the habitat) of the sample.'
      )
      
    );
  }

}
