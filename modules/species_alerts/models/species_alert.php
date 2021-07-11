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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Model class for the species_details table.
 */
class Species_Alert_Model extends ORM {
  protected $belongs_to = [
    'user_id' => 'user',
    'location_id' => 'location',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $array->add_rules('user_id', 'required');
    $array->add_rules('required');
    $array->add_rules('external_key', 'length[1,50]');
    $array->add_rules('location_id', 'integer');
    $array->add_rules('survey_id', 'integer');
    $array->add_rules('taxon_meaning_id', 'integer');
    $array->add_rules('taxon_list_id', 'integer');
    $this->unvalidatedFields = ['deleted'];
    return parent::validate($array, $save);
  }

}
