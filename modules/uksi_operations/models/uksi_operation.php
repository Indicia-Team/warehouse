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
    'created_by'=>'user',
    'updated_by'=>'user',
  ];

  public $search_field = 'sequence';

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('sequence', 'required', 'integer');
    $array->add_rules('operation', 'required');
    $array->add_rules('batch_processed_on', 'required');
    $this->unvalidatedFields = [
      'operation_processed',
      'error_detail',
      'organism_key',
      'taxon_version_key',
      'rank',
      'taxon_name',
      'authority',
      'attribute',
      'parent_organism_key',
      'parent_name',
      'synonym',
      'output_group',
      'marine',
      'terrestrial',
      'freshwater',
      'non_native',
      'redundant',
      'deleted_date',
      'batch_processed_on',
    ];
    return parent::validate($array, $save);
  }

}