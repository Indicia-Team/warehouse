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
 * @package	Core
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the identifiers_subject_observations table.
 *
 * @package	Groups and individuals module
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Identifiers_subject_observation_Model extends ORM {

  protected $belongs_to = array(
    'subject_observation',
    'identifier',
    'created_by'=>'user',
    'updated_by'=>'user',
  );
  protected $has_many = array('identifiers_subject_observation_attribute_values');
  
  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes=true;
  // A public attribute does NOT need to be linked to a website to form part of the submissable data for a identifiers_subject_observation (unlike, say,
  // sample attributes which are not submissable unless linked via a sample_attributes_websites record).
  public $include_public_attributes = false;
  protected $attrs_submission_name='isoAttributes';
  protected $attrs_field_prefix='isoAttr';

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('subject_observation_id','required');
    $array->add_rules('identifier_id','required');

    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'matched', 'verified_status', 'verified_by_id', 'verified_on', 'deleted', 'website_id',
    );
    return parent::validate($array, $save);
  }
  
  public function caption()
  { 
    $species = array();
    $result = $this->db->select('o.taxon')
        ->from('occurrences_subject_observations as oso')
        ->join('list_occurrences as o', array('o.id'=>'oso.occurrence_id'))
        ->where(array('oso.deleted'=>'f', 'oso.subject_observation_id'=>$this->subject_observation_id))
        ->get()->result();
    foreach($result as $row) 
      $species[] = $row->taxon;
    return 'Identifier '.$this->identifier->coded_value .' for observation of '.implode(',',$species);
  }
  
  // Override preSubmit to add in the verifier (verified_by_id) and verification date (verified_on) if the
  // identifiers subject observation is being set to status=V(erified)
  protected function preSubmit()
  {
    if (array_key_exists('verified_status', $this->submission['fields']))
    {
      $rs = $this->submission['fields']['verified_status']['value'];
      // If we are making it verified in the submitted data, but we don't already have a verifier in
      // the database
      if (($rs == 'V') && !$this->verified_by_id)
      {
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        // Set the verifier to the logged in user, or the default user ID from config if not logged
        // into Warehouse, if it is not in the submission
        if (!array_key_exists('verified_by_id', $this->submission['fields']))
          $this->submission['fields']['verified_by_id']['value'] = isset($_SESSION['auth_user']) ? $_SESSION['auth_user'] : $defaultUserId;
        // and store the date of the verification event if not specified.
        if (!array_key_exists('verified_on', $this->submission['fields']))
          $this->submission['fields']['verified_on']['value'] = date("Ymd H:i:s");
      } else {
        // Completed or in progress data not verified
        $this->submission['fields']['verified_by_id']['value']='';
        $this->submission['fields']['verified_on']['value']='';
      }
    }
    parent::preSubmit();
  }

}

?>
