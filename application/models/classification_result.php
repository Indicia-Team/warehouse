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
 * Model class for the classification_results table.
 *
 * Each row represents a single set of results (suggestions) from an image
 * classifier having been sent a set of images to classify.
 */
class Classification_result_Model extends ORM {

  protected $belongs_to = [
    'classification_event',
    'classifier' => 'termlists_term',
    'created_by' => 'user',
  ];

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('classification_event_id', 'required', 'integer');
    $array->add_rules('classifier_id', 'integer');
    $this->unvalidatedFields = [
      'additional_info_submitted',
      'classifier_version',
      'results_raw',
      'deleted',
    ];
    return parent::validate($array, $save);
  }

}
