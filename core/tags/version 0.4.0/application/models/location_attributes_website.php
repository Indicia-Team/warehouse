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
 * @package	Core
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the Location_Attributes_Websites table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Location_Attributes_Website_Model extends Valid_ORM
{

  protected $has_one = array(
    'location_attribute',
    'website',
  );
  
  protected $belongs_to = array(
    'created_by'=>'user',
  );

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $this->unvalidatedFields = array('location_attribute_id', 'website_id', 'restrict_to_survey_id');
    return parent::validate($array, $save, array());
  }
  
  /**
   * Return a displayable caption for the item.   
   */
  public function caption()
  {
    if ($this->id) {
      return ($this->location_attribute != null ? $this->location_attribute->caption : '');
    } else {
      return 'Location Attribute';
    }    
  }

}

?>
