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
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link       https://github.com/Indicia-Team/
 */

/**
 * Model class for the workflow_undo table.
 */
class Workflow_undo_Model extends ORM {
  public $search_field='id';

  protected $belongs_to = array('created_by'=>'user');
  protected $has_and_belongs_to_many = array();

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('entity', 'required');
    $array->add_rules('entity_id', 'required');
    $array->add_rules('event_type', 'required');

    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array('active',
                                     'original_values');

    return parent::validate($array, $save);
  }

}
