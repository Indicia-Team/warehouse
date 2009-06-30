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
 * Model class for the Termlists_Terms table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Termlists_term_Model extends ORM_Tree {
  // TODO: this is a temporary placeholder. Need to think how we can get the term (from the terms table)
  // in as the search field in termlists_terms. Perhaps a view?
  protected $search_field='id';

  protected $belongs_to = array(
    'term', 'termlist',
    'created_by' => 'user',
    'updated_by' => 'user'
  );

  protected $children = 'termlists_terms';

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('term_id', 'required');
    $array->add_rules('termlist_id', 'required');
    $array->add_rules('meaning_id', 'required');
    // $array->add_callbacks('deleted', array($this, '__dependents'));

    // Explicitly add those fields for which we don't do validation
    $extraFields = array(
      'parent_id',
      'preferred',
      'deleted',
      'sort_order'
    );
    return parent::validate($array, $save, $extraFields);
  }
  /**
   * If we want to delete the record, we need to check that no dependents exist.
   */
  public function __dependents(Validation $array, $field){
    if ($array['deleted'] == 'true'){
      $record = ORM::factory('termlists_term', $array['id']);
      if (count($record->children)!=0){
        $array->add_error($field, 'has_children');
      }
    }
  }

}
