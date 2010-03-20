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
 * Model class for the Sample_Attribute_Values table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Sample_Attribute_Value_Model extends ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'sample', 'sample_attribute');

  protected $search_field='text_value';

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('sample_attribute_id', 'required');
    $array->add_rules('sample_id', 'required');
    kohana::log('debug', 'validating attribute');

    // We apply the validation rules specified in the sample attribute
    // table to the value given.
    if (array_key_exists('sample_attribute_id', $array->as_array())) {
      $id = $array->as_array();
      $id = $id['sample_attribute_id'];
      $oam = ORM::factory('sample_attribute', $id);
      switch ($oam->data_type) {
      case 'T':
        $vf = 'text_value';
        break;
      case 'I':
        $vf = 'int_value';
        $array->add_rules('int_value', 'digit');
        break;
      case 'F':
        $vf = 'float_value';
        $array->add_rules('float_value', 'numeric');
        break;
      case 'D':
        $array->add_rules('date_end_value', 'required');
        $array->add_rules('date_type_value', 'required');
        $vf = 'date_start_value';
      break;
      case 'V':
        // Vague date - presumably already validated?
        $array->add_rules('date_end_value', 'required');
        $array->add_rules('date_type_value', 'required');
        $vf = 'date_start_value';
        break;
      case 'B':
      	// Boolean
      	// The checkbox html control actually posts the value on
      	if ($array->int_value=='on') $array->int_value=1;
      	$array->add_rules('int_value', 'minimum[0]');
      	$array->add_rules('int_value', 'maximum[1]');
      	$vf = 'int_value';
      	break;
      default:
        $vf = 'int_value';
      }
      // Require the field with the value in
      if ($vf != null) $array->add_rules($vf, 'required');
      // Now get the custom attribute validation rules
      if ($oam->validation_rules != '') {
        $rules = explode("\n", $oam->validation_rules);
        foreach ($rules as $a){
          $array->add_rules($vf, trim($a));
        }
      }
    }
    return parent::validate($array, $save);
  }
}
