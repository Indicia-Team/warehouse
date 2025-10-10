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
 * Model class for the Determinations table.
 */
class Determination_Model extends ORM {

  protected $belongs_to = [
    'occurrence',
    'taxa_taxon_list',
    'classification_event',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  public function caption() {
    return $this->id;
  }

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('occurrence_id', 'integer', 'required');
    $array->add_rules('classification_event_id', 'integer');
    $array->add_rules('machine_involvement', 'integer', 'minimum[0]', 'maximum[5]');
    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
      'email_address',
      'person_name',
      'cms_ref',
      'taxa_taxon_list_id',
      'comment',
      'taxon_extra_info',
      'deleted',
      'determination_type',
      'taxon_details',
      'taxa_taxon_list_id_list',
    ];
    if (array_key_exists('taxa_taxon_list_id_list', $array->as_array())) {
    	if (count($array['taxa_taxon_list_id_list']) === 1 && $array['taxa_taxon_list_id_list'][0] == '') {
	      $array['taxa_taxon_list_id_list'] = [];
      }
    }
    return parent::validate($array, $save);
  }

}
