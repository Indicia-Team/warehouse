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
 * Model class for the Termlists table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Termlist_Model extends ORM_Tree {

  protected $ORM_Tree_children = "termlists";
  protected $belongs_to = array('website', 'created_by'=>'user', 'updated_by'=>'user');
  protected $has_and_belongs_to_many = array('terms');
  protected $has_many = array('termlists_terms');

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('title', 'required');
    $array->add_callbacks('deleted', array($this, '_dependents'));

    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array('description', 'website_id', 'parent_id', 'deleted');
    return parent::validate($array, $save);
  }
  /**
   * If we want to delete the record, we need to check that no dependents exist.
   */
  public function _dependents(Validation $array, $field){
    if ($array['deleted'] == 'true'){
      $record = ORM::factory('termlist', $array['id']);
#			if ($record->children->count()!=0){
#				$array->add_error($field, 'has_children');
#			}
      if ($record->terms->count()!=0){
        $array->add_error($field, 'has_terms');
      }
    }
  }
}
