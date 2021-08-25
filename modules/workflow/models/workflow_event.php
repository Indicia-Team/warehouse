<?php

defined('SYSPATH') or die('No direct script access.');

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
 * @package Modules
 * @subpackage Workflow
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/
 */

/**
 * Model class for the workflow_event table.
 */
class Workflow_event_Model extends ORM {
  public $search_field = 'id';

  protected $belongs_to = [
    'created_by' => 'user',
    'updated_by' => 'user',
  ];
  protected $has_and_belongs_to_many = [];

  /**
   * Define model validation behaviour.
   */
  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $array->add_rules('entity', 'required');
    $array->add_rules('group_code', 'required');
    $array->add_rules('event_type', 'required');
    $array->add_rules('key', 'required');
    $array->add_rules('key_value', 'required');
    $array->add_rules('values', 'required');

    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
      'deleted',
      'mimic_rewind_first',
      'attrs_filter_term',
      'attrs_filter_values',
      'location_ids_filter',
    ];

    return parent::validate($array, $save);
  }

  /**
   * Tidy form data to prepare for submission.
   *
   * Converts attr_filter_values from form submission string to array. Also
   * ensures location_ids_filter array is cleaned.
   */
  public function preSubmit() {
    if (!empty($this->submission['fields']['attrs_filter_values']['value'])
        && is_string($this->submission['fields']['attrs_filter_values']['value'])) {
      $valueList = str_replace("\r\n", "\n", $this->submission['fields']['attrs_filter_values']['value']);
      $valueList = str_replace("\r", "\n", $valueList);
      $valueList = explode("\n", trim($valueList));
      $this->submission['fields']['attrs_filter_values'] = ['value' => $valueList];
    }
    // Due to the way the sub_list control works, we can have hidden empty
    // values which need to be cleaned.
    if (!empty($this->submission['fields']['location_ids_filter']['value'])
        && is_array($this->submission['fields']['location_ids_filter']['value'])) {
      $this->submission['fields']['location_ids_filter']['value'] = array_values(array_filter($this->submission['fields']['location_ids_filter']['value']));
    }
  }

}
