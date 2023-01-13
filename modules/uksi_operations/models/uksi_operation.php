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
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	https://github.com/indicia-team/warehouse/
 */

/**
 * Model class for the uksi_operations table.
 */
class Uksi_operation_Model extends ORM {

  protected $belongs_to = [
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  public $search_field = 'sequence';

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('sequence', 'required', 'integer');
    $array->add_rules('operation', 'required');
    $array->add_rules('batch_processed_on', 'required');
    $array->add_rules('operation_priority', 'integer');
    $this->unvalidatedFields = [
      'operation_processed',
      'error_detail',
      'organism_key',
      'current_organism_key',
      'current_name',
      'taxon_version_key',
      'rank',
      'taxon_name',
      'authority',
      'attribute',
      'parent_organism_key',
      'parent_name',
      'synonym',
      'taxon_group_key',
      'marine',
      'terrestrial',
      'freshwater',
      'non_native',
      'redundant',
      'deleted_date',
      'batch_processed_on',
      'notes',
      'testing_comment',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Auto-populate operation_priority so easy to process in correct order.
   */
  protected function preSubmit() {
    if (array_key_exists('operation', $this->submission['fields']) && !empty($this->submission['fields']['operation']['value'])) {
      $mappings = [
        'new taxon' => 1,
        'extract name' => 2,
        'amend taxon' => 3,
        'promote name' => 4,
        'rename taxon' => 5,
        'merge taxa' => 6,
        'add synonym' => 7,
        'amend name' => 8,
        'move name' => 9,
        'deprecate name' => 10,
        'remove deprecation' => 11,
      ];
      $priority = isset($mappings[strtolower($this->submission['fields']['operation']['value'])]) ? $mappings[strtolower($this->submission['fields']['operation']['value'])] : 999;
      $this->submission['fields']['operation_priority'] = ['value' => $priority];
    }
  }

}
