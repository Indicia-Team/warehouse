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
 * Model class for the groups table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Group_Model extends ORM {

  protected $has_one = array('filter');
      
  protected $has_and_belongs_to_many = array('users', 'locations');
  
  protected $has_many = array('group_invitations', 'group_pages');
  
  /** 
   * @var boolean Flag indicating if the group's private records status is changing, indicating we need to update the release status of records.
   */
  protected $wantToUpdateReleaseStatus=false;

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('title', 'required');
    $array->add_rules('group_type_id', 'required');
    $array->add_rules('website_id', 'required');
    $this->unvalidatedFields = array('code', 'description', 'from_date','to_date','private_records',
        'filter_id', 'joining_method', 'deleted', 'implicit_record_inclusion', 'view_full_precision', 'logo_path');
    // has the private records flag changed?
    $this->wantToUpdateReleaseStatus = isset($this->submission['fields']['private_records']) && 
        $this->submission['fields']['private_records']!==$this->private_records;
    return parent::validate($array, $save);
  }
  
  /**
   * If changing the private records setting, then must update the group's records release_status.
   */
  public function postSubmit($isInsert) {
    if (!$isInsert && $this->wantToUpdateReleaseStatus) {
      $status = $this->private_records==='1' ? 'U' : 'R';
      $sql="update #table# o
set release_status='$status'
from samples s
where s.deleted=false and s.id=o.sample_id and s.group_id=$this->id";
      $this->db->query(str_replace('#table#', 'occurrences', $sql));
      $this->db->query(str_replace('#table#', 'cache_occurrences', $sql));
    }
    return true;
  }

}