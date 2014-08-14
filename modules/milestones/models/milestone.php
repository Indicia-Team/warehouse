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
 * @package	Core
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the milestones table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Milestone_Model extends ORM {
  protected $has_many = array('milestone_awards');
  
  protected $belongs_to = array(
    'filter',
    'group',
    'created_by'=>'user',
    'updated_by'=>'user'
  );

  public function validate(Validation $array, $save = FALSE) { 
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $id = isset($array->id) ? $array->id : '';
    $array->add_rules('title','required', 'length[1,100]', 'unique[milestones,title,'.$id.']'); 
    $array->add_rules('count', 'required','digit');
    $array->add_rules('entity', 'required');
    $array->add_rules('filter_id', 'required');
    $array->add_rules('success_message', 'required');
    $array->add_rules('website_id', 'required');  
    $array->add_rules('awarded_by', 'required');
    $this->unvalidatedFields = array('group_id','deleted');
    return parent::validate($array, $save);
  }
  
  /**
   * Return the submission structure, which includes defining filter
   * as the parent (super) model
   *
   * @return array Submission structure for a milestone entry.
   */
  public function get_submission_structure() {
    return array(
      'model'=>$this->object_name,
      'superModels'=>array(
        'filter'=>array('fk' => 'filter_id'),
      ),
    );
  }
}