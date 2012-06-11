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
   * Returns a caption to identify this model instance.
   */ 
  public function caption()
  {
    return 'Record of '.$this->taxa_taxon_list->taxon->taxon;
  }

  public function validate(Validation $array, $save = false) {
    $array->pre_filter('trim');
    $array->add_rules('sample_id', 'required');
    $array->add_rules('website_id', 'required');
    $fieldlist = $array->as_array();
    if(!array_key_exists('use_determination', $fieldlist) || $fieldlist['use_determination'] == 'N') {
        $array->add_rules('taxa_taxon_list_id', 'required');
    }
    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'comment',
      'determiner_id',
      'deleted',
      'record_status',
      'downloaded_flag',
      'verified_by_id',
      'verified_on',
      'confidential',
      'use_determination',
      'external_key',
      'zero_abundance',
      'last_verification_check_date',
      'last_verification_check_taxa_taxon_list_id',
      'last_verification_check_version'
    );
    if(array_key_exists('id', $fieldlist)) {
      // existing data must not be set to download_flag=F (final download) otherwise it 
      // is read only
      $array->add_rules('downloaded_flag', 'chars[N,I]');
    }
    return parent::validate($array, $save);
  }

  // Override preSubmit to add in the verifier (verified_by_id) and verification date (verified_on) if the
  // occurrence is being set to status=V(erified) or R(ejected).
  protected function preSubmit()
  {
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
    } else {
      // If we update an occurrence but don't set the verification state, revert it to 
      // completed/awaiting verification.
      $this->submission['fields']['verified_by_id']['value']='';
      $this->submission['fields']['verified_on']['value']='';
      $this->submission['fields']['record_status']['value']='C';
    }
    parent::preSubmit();
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
    foreach (kohana::config('sref_notations.sref_notations') as $code=>$caption) {
      $srefs[] = "$code:$caption";
    }
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
  
  /**
   * Force occurrences to appear in the cache so that they are immediately available to report on.
   */
  protected function postSubmit($isInsert) {
    if ($isInsert && class_exists('cache_builder'))
      cache_builder::insert($this->db, 'occurrences', $this->id);
    return true;
  }
  
}
?>
