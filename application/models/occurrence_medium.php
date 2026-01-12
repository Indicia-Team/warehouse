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
 * Model class for the Occurrence_Media table.
 *
 * @link http://indicia-docs.readthedocs.io/en/latest/developing/data-model.html
 */
class Occurrence_medium_Model extends Base_licensed_medium_Model {
  public $search_field = 'caption';

  protected $belongs_to = array(
    'created_by' => 'user',
    'updated_by' => 'user',
    'occurrence',
  );

  /**
   * Declare additional fields required when posting via CSV.
   *
   * @var array
   */
  protected $additional_csv_fields = [
    'occurrence_medium:fk_licence:code' => 'Licence code',
  ];

  public function validate(Validation $array, $save = FALSE) {

    $array->pre_filter('trim');
    $array->add_rules('occurrence_id', 'required');
    $array->add_rules('path', 'required');
    $array->add_rules('media_type_id', 'integer');
    $array->add_rules('licence_id', 'integer');

    $this->unvalidatedFields = array('caption', 'external_details', 'exif');
    return parent::validate($array, $save);
  }

}
