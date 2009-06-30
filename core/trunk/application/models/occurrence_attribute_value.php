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
 * Model class for the Occurrence_Attribute_Values table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Occurrence_Attribute_Value_Model extends ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'occurrence', 'occurrence_attribute');

  protected $search_field='text_value';

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('occurrence_attribute_id', 'required');
    $array->add_rules('occurrence_id', 'required');

    // We apply the validation rules specified in the occurrence attribute
    // table to the value given.
    if (array_key_exists('occurrence_attribute_id', $array->as_array())) {
      $id = $array->as_array();
      $id = $id['occurrence_attribute_id'];
      $oam = ORM::factory('occurrence_attribute', $id);
      switch ($oam->data_type) {
      case 'T':
      $vf = 'text_value';
      break;
      case 'F':
      $vf = 'float_value';
      break;
      case 'D':
        $array->add_rules('date_start_value', 'required');
        $array->add_rules('date_end_value', 'required');
        $array->add_rules('date_type_value', 'required');
        $vf = null;
      break;
      case 'V':
      // Vague date - presumably already validated?
        $array->add_rules('date_start_value', 'required');
        $array->add_rules('date_end_value', 'required');
        $array->add_rules('date_type_value', 'required');
        $vf = null;
      break;
      default:
      $vf = 'int_value';
      }
      // Require the field with the value in
      if ($vf != null) $array->add_rules($vf, 'required');
      // Now get the custom attributes
      if ($oam->validation_rules != '') {
        $rules = explode("\r\n", $oam->validation_rules);
        foreach ($rules as $a){
          $array->add_rules($vf, $a);
        }
      }
    }
    return parent::validate($array, $save);
  }
}
