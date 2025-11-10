<?php

/**
 * @file
 * Model for the dna_occurrence entity.
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
 * Model class for the dna_occurrences table.
 */
class Dna_occurrence_Model extends ORM {

  /**
   * Indicates database trigger on table which accesses a sequence.
   *
   * Set to true for trigger function sync_occurrence_dna_derived.
   *
   * @var bool
   */
  protected $hasTriggerWithSequence = TRUE;

  protected $belongs_to = [
    'occurrence' => 'occurrence',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  /**
   * Validate and optionally save.
   *
   * @param Validation $array
   *   Data values to validate and save.
   * @param bool $save
   *   True if the data should be saved, false to just validate (default).
   */
  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('occurrence_id', 'integer', 'required');
    $array->add_rules('dna_sequence', 'required');
    $array->add_rules('target_gene', 'required');
    $array->add_rules('pcr_primer_reference', 'required');
    $this->unvalidatedFields = [
      'associated_sequences',
      'env_broad_scale',
      'env_local_scale',
      'env_medium',
      'otu_db',
      'otu_seq_comp_appr',
      'otu_class_appr',
      'target_subfragment',
      'pcr_primer_name_forward',
      'pcr_primer_forward',
      'pcr_primer_name_reverse',
      'pcr_primer_reverse',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Override formatting of fields on setter.
   *
   * @param string $column
   *   Column name.
   *
   * @param mixed $value
   *   Submitted value.
   */
  public function __set($column, $value) {
    // Format associated sequences in any list string form into an array.
    if ($column === 'associated_sequences' && !empty($value)) {
      $value = preg_split('/[\r\n,;\t]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
    }
    parent::__set($column, $value);
  }

  /**
   * Override formatting of fields on getter.
   *
   * @param string $column
   *   Column name.
   */
  public function __get($column) {
    // Convert associated sequences array to semi-colon separated string.
    if ($column === 'associated_sequences') {
      $value = parent::__get($column);
      if (is_array($value)) {
        // Join array items with newlines.
        return implode("\n", $value);
      } elseif (is_string($value)) {
        // Remove curly braces and split PostgreSQL text[] string.
        $trimmed = trim($value, '{}');
        if ($trimmed === '') {
          return '';
        }
        // Split by comma, handle quoted values.
        $items = str_getcsv($trimmed, ',');
        return implode("\n", $items);
      }
      return $value;
    }
    return parent::__get($column);
  }

}
