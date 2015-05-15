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
 * Model class for the Occurrence_Comments table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Occurrence_comment_model extends ORM {
  public $search_field = 'comment';
  
  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'occurrence');
  
  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('comment','required');
    $array->add_rules('occurrence_id', 'required');
    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'email_address',
      'person_name',
      'deleted',
      'auto_generated',
      'generated_by',
      'implies_manual_check_required',
      'query',
      'record_status',
      'record_substatus'
    );
    return parent::validate($array, $save);

  }
  
  /**
   * Returns an abbreviated version of the comment to act as a caption
   */
  public function caption()
  {
    if (strlen($this->comment)>30) 
      return substr($this->comment, 0, 30).'...';
    else 
      return $this->comment;
  }

  /**
   * Implement an instant update of the cache occurrences queried field, so the verification UI
   * can report on in as changes are made.
   * @param $isInsert
   * @return bool
   */
  public function postSubmit($isInsert) {
    if ($isInsert && $this->auto_generated!=='t' and $this->query==='t') {
      $this->db->query("update cache_occurrences set query='Q' where id={$this->occurrence_id}");
    }
    // answers don't need to be instant, just queries
    return true;
  }
  
  

}
