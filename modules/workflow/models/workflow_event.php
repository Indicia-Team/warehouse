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
    ];

    return parent::validate($array, $save);
  }

  /**
   * Converts attr_filter_values from form submission string to array.
   */
  public function preSubmit() {
    if (!empty($this->submission['fields']['attrs_filter_values']['value'])
        && is_string($this->submission['fields']['attrs_filter_values']['value'])) {
      $keyList = str_replace("\r\n", "\n", $this->submission['fields']['attrs_filter_values']['value']);
      $keyList = str_replace("\r", "\n", $keyList);
      $keyList = explode("\n", trim($keyList));
      $this->submission['fields']['attrs_filter_values'] = ['value' => $keyList];
    }
    elseif (isset($this->submission['fields']['attrs_filter_values'])) {
      $this->submission['fields']['attrs_filter_values'] = ['value' => NULL];
    }
  }

}
