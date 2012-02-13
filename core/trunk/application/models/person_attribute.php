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
 * Model class for the Person_Attributes table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Person_Attribute_Model extends ATTR_ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'termlist');
  
  // The person attributes are defined per website, not per survey
  protected $has_survey_restriction = false;

  protected $has_many = array(
    'person_attributes_values',
  );

  protected $has_and_belongs_to_many = array('websites');
  
  /**
   * After saving, ensures that the join records linking the attribute to a website are created or deleted.
   * @return boolean Returns true to indicate success. 
   */
  protected function postSubmit() {
    // Record has saved correctly or is being reused
    $websites = ORM::factory('website')->find_all();
    foreach ($websites as $website) {
      // Check for website checkbox ticked
      $this->set_attribute_website_record($this->id, $website->id, null, isset($_POST['website_'.$website->id]));
    }
    return true;
  }

}
