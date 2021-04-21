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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the Taxa table.
 */
class Taxon_Model extends ORM {

  public $search_field = 'taxon';

  protected $belongs_to = [
    'meaning',
    'language',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  protected $has_many = ['taxa_taxon_lists'];

  protected $has_and_belongs_to_many = ['taxon_lists'];

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('taxon', 'required');
    $array->add_rules('language_id', 'required');
    $array->add_rules('taxon_group_id', 'required');

    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
      'attribute',
      'external_key',
      'authority',
      'deleted',
      'search_code',
      'description',
      'taxon_rank_id',
      'marine_flag',
      'freshwater_flag',
      'terrestrial_flag',
      'non_native_flag',
      'organism_key',
    ];
    return parent::validate($array, $save);
  }

  protected function preSubmit(){

    // Call the parent preSubmit function.
    parent::preSubmit();

    // Set scientific if latin.
    $l = ORM::factory('language');
    $sci = 'f';
    if ($l->find($this->submission['fields']['language_id']['value'])->iso === 'lat') {
      $sci = 't';
    }
    $this->submission['fields']['scientific'] = [
      'value' =>  $sci,
    ];
  }

  /**
   * Set default values for a new taxon entry.
   */
  public function getDefaults() {
    return [
      // Latin.
      'language_id' => 2,
    ];
  }

}
