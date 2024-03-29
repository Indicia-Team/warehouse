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
 * Model class for the classification_events table.
 *
 * Each row represents a single request or set of requests to an image
 * classifier.
 */
class Classification_event_Model extends ORM {

  protected $has_many = [
    'classification_result',
  ];

  protected $has_one = [
    'occurrence',
    'determination',
  ];

  protected $belongs_to = [
    'created_by' => 'user',
  ];

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $this->unvalidatedFields = [
      'deleted',
    ];
    return parent::validate($array, $save);
  }

}
