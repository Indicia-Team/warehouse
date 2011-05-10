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
 * @package  Core
 * @subpackage Models
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   http://code.google.com/p/indicia/
 */

/**
 * Model class for the Location_Attribute_Values table.
 *
 * @package  Core
 * @subpackage Models
 * @link  http://code.google.com/p/indicia/wiki/DataModel
 */
class Location_Attribute_Value_Model extends Attribute_Value_ORM {
  public static $search_field='text_value';

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'location', 'location_attribute');

  public function validate(Validation $array, $save = FALSE) {
    self::attribute_validation($array, 'location');    
    return parent::validate($array, $save);
  }
  
}
