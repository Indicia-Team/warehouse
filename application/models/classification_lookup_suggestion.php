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
 * Model class for the classification_lookup_suggestions table.
 *
 * Each row represents a single term suggestion made by an image
 * classifier in response to a request to identify some images. A term might be
 * a suggested habitat for example.
 */
class Classification_lookup_suggestion_Model extends ORM {

  protected $belongs_to = [
    'classification_result',
    'termlists_term',
    'created_by' => 'user',
  ];

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('classification_result_id', 'required', 'integer');
    $array->add_rules('sample_attribute_id', 'integer');
    $array->add_rules('occurrence_attribute_id', 'integer');
    $array->add_rules('location_attribute_id', 'integer');
    $array->add_rules('termlists_term_id', 'integer');
    $array->add_rules('probability_given', 'minimum[0]', 'maximum[1]');
    $this->unvalidatedFields = [
      'term_given',
      'deleted',
      'classifier_chosen',
      'human_chosen',
    ];
    $targetCount =
      (empty($array['sample_attribute_id']) ? 0 : 1) +
      (empty($array['occurrence_attribute_id']) ? 0 : 1) +
      (empty($array['location_attribute_id']) ? 0 : 1);
    if ($targetCount !== 1) {
      $array->add_error('sample_attribute_id', 'exactly_one_target_attribute_required');
    }
    return parent::validate($array, $save);
  }

}
