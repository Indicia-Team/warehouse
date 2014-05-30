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
 * Model class for the Occurrences table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Occurrence_Model extends ORM
{
  protected $requeuedForVerification = false;
  
  protected $has_many=array(
    'occurrence_attribute_values',
    'determinations'
  );
  protected $belongs_to=array(
    'determiner'=>'person',
    'sample',
    'taxa_taxon_list',
    'created_by'=>'user',
    'updated_by'=>'user',
    'verified_by'=>'user'
  );
  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes=true;
  protected $attrs_submission_name='occAttributes';
  protected $attrs_field_prefix='occAttr';
  protected $additional_csv_fields=array(
    // allow details of 4 images to be uploaded in CSV files
    'occurrence_image:path:1'=>'Image Path 1',
    'occurrence_image:caption:1'=>'Image Caption 1',
    'occurrence_image:path:2'=>'Image Path 2',
    'occurrence_image:caption:2'=>'Image Caption 2',
    'occurrence_image:path:3'=>'Image Path 3',
    'occurrence_image:caption:3'=>'Image Caption 3',
    'occurrence_image:path:4'=>'Image Path 4',
    'occurrence_image:caption:4'=>'Image Caption 4'    
  );
  
  /**
   * Should a determination be logged if this is a changed record?
   * @var boolean
   */
  protected $logDetermination=false;

  /**
   * Returns a caption to identify this model instance.
   */ 
  public function caption()
  {
    return 'Record of '.$this->taxa_taxon_list->taxon->taxon;
  }
  
  public function validate(Validation $array, $save = false) {
    if ($save) 
      $this->logDeterminations($array);
    if ($this->id && preg_match('/[RDV]/', $this->record_status) && empty($this->submission['fields']['record_status']) && 
        empty($this->submission['fields']['release_status']) && $this->wantToUpdateMetadata) {
      // If we update a processed occurrence but don't set the verification or release state, revert it to completed/awaiting verification.
      $array->verified_by_id=null;
      $array->verified_on=null;
      $array->record_status='C';
      $this->requeuedForVerification=true;
    }
    $array->pre_filter('trim');
    $array->add_rules('sample_id', 'required');
    $array->add_rules('website_id', 'required');
    $fieldlist = $array->as_array();
    if(!array_key_exists('all_info_in_determinations', $fieldlist) || $fieldlist['all_info_in_determinations'] == 'N') {
      $array->add_rules('taxa_taxon_list_id', 'required');
    }
    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'comment',
      'determiner_id',
      'deleted',
      'record_status',
      'release_status',
      'downloaded_flag',
      'verified_by_id',
      'verified_on',
      'confidential',
      'all_info_in_determinations',
      'external_key',
      'zero_abundance',
      'last_verification_check_date',
      'training',
      'sensitivity_precision'
    );
    if(array_key_exists('id', $fieldlist)) {
      // existing data must not be set to download_flag=F (final download) otherwise it 
      // is read only
      $array->add_rules('downloaded_flag', 'chars[N,I]');
    }
    return parent::validate($array, $save);
  }
  
  private function logDeterminations(Validation $array) {

    //Only log a determination for the occurrence if the species is changed.
    //Also the all_info_in_determinations flag must be off to avoid clashing with other functionality
    //and the config setting must be enabled.
    if (kohana::config('indicia.auto_log_determinations')===true && !empty($this->taxa_taxon_list_id) && 
      !empty($this->submission['fields']['taxa_taxon_list_id']['value']) && $this->all_info_in_determinations!=='Y' &&
      $this->taxa_taxon_list_id != $this->submission['fields']['taxa_taxon_list_id']['value']) {
      $this->logDetermination = true;
      $currentUserId = $this->get_current_user_id();
      //We log the old taxon
      $rowToAdd['taxa_taxon_list_id']=$this->taxa_taxon_list_id;
      $rowToAdd['determination_type'] = 'B';
      $rowToAdd['occurrence_id'] = $this->id;
      //Last change to the occurrence is really the create metadata for this determination, since we are copying it out of the existing occurrence record.
      $rowToAdd['created_by_id'] = $this->updated_by_id;
      $rowToAdd['updated_by_id'] = $this->updated_by_id;
      $rowToAdd['created_on'] = $this->updated_on;
      $rowToAdd['updated_on'] = $this->updated_on;
      $rowToAdd['person_name'] = $this->get_person_name_and_update_determiner($this->as_array(), $this->updated_by_id);
      
      $insert = $this->db
       ->from('determinations')
       ->set($rowToAdd)
       ->insert();
      
      if ($currentUserId!==1)
        $this->submission['fields']['determiner_id']['value'] = $currentUserId;
    }
  }
  
  /**Method that adds a created by, created date, updated by, updated date to a row of data
     we are going to add/update to the database.
   * @param array $row A row of data we are adding/updating to the database.
   * @param string $tableName The name of the table we are adding the row to. We need this as the
   * attribute_websites tables don't have updated by and updated on fields.
   */
  public function set_metadata_for_row_array(&$row=null, $tableName=null) {
    if (isset($_SESSION['auth_user']))
      $userId = $_SESSION['auth_user']->id;
    else {
      global $remoteUserId;
      if (isset($remoteUserId))
        $userId = $remoteUserId;
      else {
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        $userId = ($defaultUserId ? $defaultUserId : 1);
      }
    }
    $row['created_on'] = date("Ymd H:i:s");
    $row['created_by_id'] = $userId;
    //attribute websites tables don't have updated by/date details columns so we need a special case not to set them
    if ($tableName!=='sample_attributes_websites'&&$tableName!=='occurrence_attributes_websites') {
      $row['updated_on'] = date("Ymd H:i:s");
      $row['updated_by_id'] = $userId;
    }
  }
  
  /*
   * Collect the user id for the current user, this will be 1 unless logged into warehouse or Easy Login is enabled in instant-indicia.
   */
  public function get_current_user_id() {
    if (isset($_SESSION['auth_user'])) 
      $userId = $_SESSION['auth_user']->id;
    else {
      global $remoteUserId;
      if (isset($remoteUserId))
        $userId = $remoteUserId;
      else {
        // Don't force overwrite of user IDs that already exist in the record, since
        // we are just using a default.
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        $userId = ($defaultUserId ? $defaultUserId : 1);
      }
    }
    return $userId;
  }
  
  /*
   * Method that is called when attempting to fill in determinations.person_name using a determination
   * occurrence attribute value. Code has its own method as the code could be called several times for
   * different determination occurrence attributes (first name, last name, full name)
   */
  public function get_and_update_occ_attr_determiner($determinerNameAttrId, $occurrenceId, $detNameType, $currentUserNames) {
    //Firstly try and get the determiner for the occurrence from an attribute value
    $determinerNameAttempt = $this->db
      ->select('id','text_value')
      ->from('occurrence_attribute_values')
      ->where(array('occurrence_attribute_id'=>$determinerNameAttrId,'occurrence_id'=>$occurrenceId))
      ->get()->as_array();
    if (!empty($determinerNameAttempt)) {
      $determinerNameAttrValId = $determinerNameAttempt[0]->id;
      $determinerName = $determinerNameAttempt[0]->text_value;
    }
    //If we have successfully retrieved a determiner name that we can log into the determinations table
    //then we can overwrite the determiner attribute with the current user (who is changing the occurrence
    //species so becomes the new determiner).
    if (!empty($determinerName)) {
      switch($detNameType) {
        case 'det_full_name':
          $theNameToAdd = $currentUserNames->surname.', '.$currentUserNames->first_name;
          break;
        case 'det_last_name':
          $theNameToAdd = $currentUserNames->surname;
          break;
        case 'det_first_name':
          $theNameToAdd = $currentUserNames->first_name;
          break;
        default:
          $theNameToAdd = 'Unknown';
      }

      $nameToAddRow = array('text_value'=>$theNameToAdd);
      $this->set_metadata_for_row_array($nameToAddRow, 'occurrence_attribute_values');
      $update = $this->db
        ->from('occurrence_attribute_values')
        ->set($nameToAddRow)
        ->where(array('id'=>$determinerNameAttrValId))
        ->update();
      return $determinerName;
    }
  }
  
  /*
   * When logging species into the determinations table, this method uses various rules to
   * work out what the determinations.person_name field will be.
   * It also calls get_and_update_occ_attr_determiner to update the occurrence attribute
   * determiner to the current user (who is changing the occurrence species).
   */
  public function get_person_name_and_update_determiner($oldValues, $currentUserId) {
    //We can only set the person name from an determiner system function occurrence attribute 
    //if we have easy login enable ($currentUserId !== 1) because after we have logged the determination
    //we need to update the determiner for the original occurrence to the current user as the current
    //user has changed the occurrence.
    if ($currentUserId !== 1) {
      $currentUserNames = $this->get_user_firstname_and_surname($currentUserId);
      //Find the occurrence attributes that have a determiner name system function set
      $occurrenceAttributesWithDetFuncs = $this->db
        ->select('id','system_function')
        ->from('occurrence_attributes')
        ->where(array('system_function'=>'det_first_name'))
        ->orwhere(array('system_function'=>'det_last_name'))
        ->orwhere(array('system_function'=>'det_full_name'))
        ->get()->as_array();

      //Go through all the occurrence attributes with determiner name system functions
      //and split them up into a first name, a surname name and a full name function.
      //As the system can actually have more than one occurrence_attribute with each
      //determiner name type system function, then we just take the first one we come accross.
      //In practice there will probably be only one.
      foreach ($occurrenceAttributesWithDetFuncs as $occurrenceAttrRow) {
        if ($occurrenceAttrRow->system_function==='det_full_name' && empty($determinerFullNameAttributeId)) {
          $determinerFullNameAttributeId = $occurrenceAttrRow->id;
        }
        if ($occurrenceAttrRow->system_function==='det_last_name' && empty($determinerLastNameAttributeId)) {
          $determinerLastNameAttributeId = $occurrenceAttrRow->id;
        }

        if ($occurrenceAttrRow->system_function==='det_first_name' && empty($determinerFirstNameAttributeId)) {
          $determinerFirstNameAttributeId = $occurrenceAttrRow->id;
        }
      }
      //There are several rules we can use to collect the name for the person_name field in the determination.
      //The first rule is to see if we can collected it from the occurrence attribute with the determiner full name
      //system function.
      $occurrenceId = $oldValues['id'];
      if (!empty($determinerFullNameAttributeId)) {
        //Try and get the name from the occurrence attribute with the full name system function first.
        $determinerName = $this->get_and_update_occ_attr_determiner($determinerFullNameAttributeId, $occurrenceId, 'det_full_name', $currentUserNames);
      } 
      //if we can't set the person_name from the fullname occurrence attribute, see if we can do it with the last name    
      if (!empty($determinerLastNameAttributeId) && empty($determinerName)) {
        $determinerName = $this->get_and_update_occ_attr_determiner($determinerLastNameAttributeId, $occurrenceId, 'det_last_name', $currentUserNames);
        //If we have managed to find a surname then we can attempt to find their first name
        if (!empty($determinerName)) {
          $determinerFirstName = $this->get_and_update_occ_attr_determiner($determinerFirstNameAttributeId, $occurrenceId, 'det_first_name', $currentUserNames);
          //If we have found a first name then add it to the variable we are going to put in determinations.person_name
          if (!empty($determinerFirstName))
            $determinerName = $determinerName.', '.$determinerFirstName;
        }
      }
    }
    //if we still haven't found a person name, apply further rules to find one
    if (empty($determinerName)) {
      //If there aren't currently any logged determinations for the occurrence we want to 
      //attempt to get a person_name from recorders in cache_occurrences
      $determinationsForOccurrence = $this->db
        ->select('id')
        ->from('determinations')
        ->where(array('occurrence_id'=>$oldValues['id']))
        ->get()->as_array();
      if (empty($determinationsForOccurrence)) {
        $determinerNameArray = $this->db
          ->select('recorders')
          ->from('cache_occurrences')
          ->where(array('id'=>$oldValues['id']))
          ->get()->as_array();
        if (!empty($determinerNameArray[0]->recorders))
          $determinerName = $determinerNameArray[0]->recorders; 
      }
    }
    //If we still haven't got a person name, try to get the previous updater's name
    if (empty($determinerName)) {
      if($oldValues['updated_by_id'] !== 1) {
        $determinerNames = $this->get_user_firstname_and_surname($oldValues['updated_by_id']);
        $determinerName = $determinerNames->surname.', '.$determinerNames->first_name;
      }
    }
    //If after working through all the rules we still haven't found a person name, the set to 'Unknown'
    if (empty($determinerName)) {
      $determinerName='Unknown';
    }
    return $determinerName;
  }
  
  /*
   * Return a single row array which contains an object with surname and first_name properties.
   * It is more flexible to have the first and surnames seperately like this as we can use them seperately
   * or format them as surname, first name etc
   */
  public function get_user_firstname_and_surname($userId) {
    $updatedByPersonId = $this->db
      ->select('person_id')
      ->from('users')
      ->where(array('id'=>$userId))
      ->get()->as_array();
    $determinerNameArray = $this->db
      ->select('first_name','surname')
      ->from('people')
      ->where(array('id'=>$updatedByPersonId[0]->person_id))
      ->get()->as_array();
    return $determinerNameArray[0];
  }
  
  // Override preSubmit to add in the verifier (verified_by_id) and verification date (verified_on) if the
  // occurrence is being set to status=V(erified) or R(ejected).
  protected function preSubmit()
  {     
    //If determination logging is on and the occurrence species has changed ($logDetermination is true), we can
    //set the determiner_id on the occurrence to the current user providing easy login is on ($currentUserId!==1).
    if ($this->logDetermination) {
      $currentUserId = $this->get_current_user_id(); 
      if ($currentUserId!==1) 
        $this->submission['fields']['determiner_id']['value']=$currentUserId;
    }
    if (array_key_exists('record_status', $this->submission['fields']))
    { 
      $rs = $this->submission['fields']['record_status']['value'];
      // If we are making it verified in the submitted data, but we don't already have a verifier in
      // the database
      if (($rs == 'V' || $rs == 'R') && !$this->verified_by_id)
      {
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        // Set the verifier to the logged in user, or the default user ID from config if not logged
        // into Warehouse, if it is not in the submission
        if (!array_key_exists('verified_by_id', $this->submission['fields']))
          $this->submission['fields']['verified_by_id']['value'] = isset($_SESSION['auth_user']) ? $_SESSION['auth_user'] : $defaultUserId;
        // and store the date of the verification event if not specified.
        if (!array_key_exists('verified_on', $this->submission['fields']))
          $this->submission['fields']['verified_on']['value'] = date("Ymd H:i:s");
      } elseif ($rs=='C' || $rs=='I') {
        // Completed or in progress data not verified
        $this->submission['fields']['verified_by_id']['value']='';
        $this->submission['fields']['verified_on']['value']='';
      }
    }
    parent::preSubmit();
  }
  
  /**
   * If this occurrence record status was reset after an edit, then log a comment.
   */
  public function postSubmit($isInsert) {
    if ($this->requeuedForVerification && !$isInsert) {
      $data = array(
        'occurrence_id'=>$this->id,
        'comment'=>kohana::lang('misc.recheck_verification'),
        'auto_generated'=>'t'
      );
      $comment = ORM::factory('occurrence_comment');
      $comment->validate(new Validation($data), true);
    }
    return true;
  }
 
  /**
  * Defines a submission structure for occurrences that lets samples be submitted at the same time, e.g. during CSV upload.
  */
  public function get_submission_structure() {
    return array(
        'model'=>$this->object_name,
        'superModels'=>array(
          'sample'=>array('fk' => 'sample_id')
        )     
    );
  }
  
  /**
   * Define a form that is used to capture a set of predetermined values that apply to every record during an import.
   */
  public function fixed_values_form() {
    $srefs = array();
    $systems = spatial_ref::system_metadata();
    foreach ($systems as $code=>$metadata) 
      $srefs[] = "$code:".$metadata['title'];
    return array(
      'website_id' => array( 
        'display'=>'Website', 
        'description'=>'Select the website to import records into.', 
        'datatype'=>'lookup',
        'population_call'=>'direct:website:id:title' 
      ),
      'survey_id' => array(
        'display'=>'Survey', 
        'description'=>'Select the survey to import records into.', 
        'datatype'=>'lookup',
        'population_call'=>'direct:survey:id:title',
        'linked_to'=>'website_id',
        'linked_filter_field'=>'website_id'
      ),
      'sample:entered_sref_system' => array(
        'display'=>'Spatial Ref. System', 
        'description'=>'Select the spatial reference system used in this import file. Note, if you have a file with a mix of spatial reference systems then you need a '.
            'column in the import file which is mapped to the Sample Spatial Reference System field containing the spatial reference system code.', 
        'datatype'=>'lookup',
        'lookup_values'=>implode(',', $srefs)
      ),
      // Also allow a field to be defined which defines the taxon list to look in when searching for species during a csv upload
      'fkFilter:taxa_taxon_list:taxon_list_id'=>array(
        'display' => 'Species list',
        'description'=>'Select the species checklist which will be used when attempting to match species names.', 
        'datatype'=>'lookup',
        'population_call'=>'direct:taxon_list:id:title',
        'linked_to'=>'website_id',
        'linked_filter_field'=>'website_id'
      ),
      'occurrence:record_status' => array(
        'display' => 'Record Status',
        'description' => 'Select the initial status for imported records',
        'datatype' => 'lookup',
        'lookup_values' => 'C:Data entry complete/unverified,V:Verified,I:Data entry still in progress',
        'default' => 'C'
      )
    );
  }
  
}
?>
