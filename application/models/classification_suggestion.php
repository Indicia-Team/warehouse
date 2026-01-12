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
 * Model class for the classification_suggestions table.
 *
 * Each row represents a single taxonomic suggestion made by an image
 * classifier in response to a request to identify some images.
 */
class Classification_suggestion_Model extends ORM {

  protected $belongs_to = [
    'classification_result',
    'created_by' => 'user',
  ];

  protected $has_and_belongs_to_many = [
    'occurrence_media',
  ];

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('classification_result_id', 'required', 'integer');
    $array->add_rules('taxa_taxon_list_id', 'integer');
    $array->add_rules('probability_given', 'minimum[0]', 'maximum[1]');
    $this->unvalidatedFields = [
      'taxon_name_given',
      'deleted',
      'classifier_chosen',
      'human_chosen',
    ];
    return parent::validate($array, $save);
  }

}
