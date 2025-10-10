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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the Survey_Attributes_Websites table.
 */
class Survey_Attributes_Website_Model extends Valid_ORM
{

  protected $has_one = array(
    'survey_attribute',
    'website',
  );

  protected $belongs_to = array(
    'created_by'=>'user',
  );

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $this->unvalidatedFields = array(
      'survey_attribute_id',
      'website_id',
      'default_text_value',
      'default_float_value',
      'default_int_value',
      'default_upper_value',
      'default_date_start_value',
      'default_date_end_value',
      'default_date_type_value',
      'control_type_id',
    );
    return parent::validate($array, $save, array());
  }

  /**
   * Return a displayable caption for the item.
   */
  public function caption()
  {
    if ($this->id) {
      return ($this->survey_attribute != null ? $this->survey_attribute->caption : '');
    } else {
      return 'Survey Attribute';
    }
  }

  /**
   * Map a virtual field called default_value onto the relevant default value fields, depending on the data type.
   */
  protected function preSubmit()
  {
    if (isset($this->submission['fields']['default_value']['value'])) {
      $attr = ORM::factory('survey_attribute', $this->submission['fields']['survey_attribute_id']['value']);
      switch ($attr->data_type) {
        case 'T':
          $this->submission['fields']['default_text_value']['value']=$this->submission['fields']['default_value']['value'];
          break;
        case 'F':
          $this->submission['fields']['default_float_value']['value']=$this->submission['fields']['default_value']['value'];
          break;
        case 'I':
          case 'L':
          $this->submission['fields']['default_int_value']['value']=$this->submission['fields']['default_value']['value'];
          break;
        case 'D':
        case 'V':
          $vagueDate = vague_date::string_to_vague_date($this->submission['fields']['default_value']['value']);
          $this->submission['fields']['default_date_start_value']['value']=$vagueDate[0];
          $this->submission['fields']['default_date_end_value']['value']=$vagueDate[1];
          $this->submission['fields']['default_date_type_value']['value']=$vagueDate[2];
      }
    }
    return parent::presubmit();
  }

  /**
   * Create a virtual field called default_value from the relevant default value fields, depending on the data type.
   */
  public function __get($column)
  {
    if ($column=='default_value') {
      $attr = ORM::factory('survey_attribute', $this->survey_attribute_id);
      switch ($attr->data_type) {
        case 'T':
        return parent::__get('default_text_value');
        case 'F':
        return parent::__get('default_float_value');
        case 'I':
        case 'L':
        return parent::__get('default_int_value');
        case 'D':
        case 'V':
        $vagueDate = array(parent::__get('default_date_start_value'),
            parent::__get('default_date_end_value'),
          parent::__get('default_date_type_value'));
        return vague_date::vague_date_to_string($vagueDate);
      }
    } else
      return parent::__get($column);
  }

}