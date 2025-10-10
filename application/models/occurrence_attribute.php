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
 * Model class for the Occurrence_Attributes table.
 *
 * @link http://indicia-docs.readthedocs.io/en/latest/developing/data-model.html
 */
class Occurrence_Attribute_Model extends ATTR_ORM {

  protected $belongs_to = [
    'created_by' => 'user',
    'updated_by' => 'user',
    'termlist_id' => 'termlist',
    'source_id' => 'termlists_term',
    'reporting_category_id' => 'termlists_term',
  ];

  protected $has_many = ['occurrence_attributes_values'];

  protected $has_and_belongs_to_many = ['websites'];

  /**
   * Retrieve system functions for occurrence attributes.
   *
   * Get the list of known system functions for sample attributes, each with a
   * title and description of their usage. Note that any entry in this list
   * should be replicated as a field called attr_* in the
   * cache_occurrences_nonfunctional table.
   *
   * @return array
   *   List of the system known functions that a sample attribute can have.
   */
  public function get_system_functions() {
    return [
      'behaviour' => [
        'title' => 'Behaviour',
        'description' => 'The behavior shown by the subject at the time the Occurrence was recorded.',
      ],
      'certainty' => [
        'title' => 'Certainty of the record accuracy',
        'friendly' => 'Certainty',
        'description' => 'Attribute value describes how certain the recorder is of the record. Please ensure that any terms corresponding ' .
          'to a certain record have a sort order of less than 100, any terms corresponding to a likely record which is not certain have ' .
          'a sort order of 100-199 and any terms corresponding to a record which is not at least considered likely have a sort order of ' .
          '200 or more.',
      ],
      'det_first_name' => [
        'title' => 'Determiner first name',
        'description' => 'A text attribute corresponding to the first name of the person determining (identifying) the record.',
      ],
      'det_full_name' => [
        'title' => 'Determiner full name',
        'friendly' => 'Identified by',
        'description' => 'A text attribute corresponding to the full name of the person determining (identifying) the record.',
      ],
      'det_last_name' => [
        'title' => 'Determiner last name',
        'description' => 'A text attribute corresponding to the last name of the person determining (identifying) the record.',
      ],
      'sex_stage' => [
        'title' => 'Sex/stage',
        'description' => 'A text or lookup attribute where the value corresponds to the sex or life stage of the recorded organism(s).',
      ],
      'reproductive_condition' => [
        'title' => 'Reproductive condition',
        'description' => 'The reproductive condition of the biological individual(s) represented in the Occurrence.',
      ],
      'sex' => [
        'title' => 'Sex',
        'description' => 'A text or lookup attribute where the value corresponds to the sex of the recorded organism(s).',
      ],
      'stage' => [
        'title' => 'Stage',
        'description' => 'A text or lookup attribute where the value corresponds to the life stage of the recorded organism(s).',
      ],
      'sex_stage_count' => [
        'title' => 'Count or abundance of a sex or life stage.',
        'friendly' => 'Abundance',
        'description' => 'An attribute corresponding to the abundance of a particular stage, indicated by the caption of the attribute. ' .
          'The attribute can be an integer count, in which case zero means not present, a checkbox corresponding to presence/absence, ' .
          'or a lookup where terms "Absent","None", "Not Present" or "0" indicate not present.',
      ],
    ];
  }

}
