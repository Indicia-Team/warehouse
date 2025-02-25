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
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	https://github.com/indicia-team/warehouse/
 */

/**
 * Model class for the milestone_awards table.
 *
 * * @link	https://github.com/indicia-team/warehouse/wiki/DataModel
 */
class Milestone_Award_Model extends ORM {
  protected $has_many = array('milestones','users');

  protected $belongs_to = array(
    'milestone',
    'user',
    'created_by'=>'user',
    'updated_by'=>'user'
  );

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('milestone_id', 'required');
    $array->add_rules('user_id', 'required');
    $this->unvalidatedFields = array('deleted');
    return parent::validate($array, $save);
  }
}