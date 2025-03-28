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
 * Model class for the taxa_taxon_designations table.
 */
class Taxa_taxon_designation_Model extends ORM {

  protected $belongs_to = [
    'taxa',
    'taxon_designation',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  public $lookup_against = 'gv_taxa_taxon_designations';

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('taxon_id', 'required');
    $array->add_rules('taxon_designation_id', 'required');
    $this->unvalidatedFields = [
      'start_date',
      'source',
      'geographical_constraint',
      'deleted',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Return a displayable caption for the item.
   */
  public function caption() {
    if ($this->id) {
      return ($this->taxon_designation_id != NULL ? $this->taxon_designation->title : '');
    }
    else {
      return 'Taxon Designation';
    }
  }

}
