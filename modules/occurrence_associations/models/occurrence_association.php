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
 * @link https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the occurrence_associations table.

 * @link https://github.com/indicia-team/warehouse/wiki/DataModel
 */
class Occurrence_association_Model extends ORM {

  public $search_field = 'id';

  protected $to_occurrence_id_pointer = FALSE;

  public static $to_occurrence_id_pointers = array();

  protected $has_one = [
    'from_occurrence' => 'occurrence',
    'to_occurrence' => 'occurrence',
    'association_type' => 'termlists_term',
    'part' => 'termlists_term',
    'position' => 'termlists_term',
    'impact' => 'termlists_term',
  ];

  protected $belongs_to = array(
    'created_by' => 'user',
    'updated_by' => 'user',
  );

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('association_type_id', 'required');
    $array->add_rules('from_occurrence_id', 'required');
    $array->add_rules('to_occurrence_id', 'required');
    $this->unvalidatedFields = [
      'part_id',
      'position_id',
      'impact_id',
      'comment',
      'deleted',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Field set handler.
   *
   * Override set handler to trap pointers in to_occurrence_id to occurrences
   * that don't yet exist, because they come later in the submission. These
   * values come in the form ||pointer||. We have to temporarily null out the
   * field, then store the pointer for later.
   */
  public function __set($key, $value) {
    if (substr($key, -16) === 'to_occurrence_id' && preg_match('/^\|\|.+\|\|$/', $value)) {
      $this->to_occurrence_id_pointer = str_replace('||', '', $value);
      $value = NULL;
    }
    parent::__set($key, $value);
  }

  /**
   * Post submission handler.
   *
   * After submission, if we stored a pointer to a to_occurrence_id that does
   * not yet exist, then store it in a static array with the
   * occurrence_association_id so we can fill it in at the end of the
   * submission.
   */
  public function postSubmit($isInsert) {
    if ($this->to_occurrence_id_pointer) {
      self::$to_occurrence_id_pointers[$this->id] = $this->to_occurrence_id_pointer;
      $this->to_occurrence_id_pointer = FALSE;
      kohana::log('debug', 'Pointers: ' . var_export(self::$to_occurrence_id_pointers, TRUE));
    }
    return TRUE;
  }

  // Define underlying fields which the user would not normally see, e.g. so
  // they can be hidden from selection during a csv import.
  protected $hidden_fields = [
    'from_occurrence_id',
    'to_occurrence_id',
  ];

}
