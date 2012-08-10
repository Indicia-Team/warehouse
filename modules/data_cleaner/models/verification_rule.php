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
 * Model class for the taxon_designations table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Verification_rule_Model extends ORM {

  protected $belongs_to = array(
      'created_by'=>'user',
      'updated_by'=>'user');
  
  protected $has_many = array(
    'verification_rule_data',
    'verification_rule_metadata'
  );
  
  /**
   * @var boolean Is this model instance being used to create a new rule?
   */
  protected $isNewRule = false;
  
  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('title', 'required', 'length[1,100]');
    $array->add_rules('test_type', 'required');
    $array->add_rules('error_message', 'required');
    // sourcr_url is not validated as a url because the NBN zip file paths don't validate but must be accepted.
    $this->unvalidatedFields = array('description', 'source_filename', 'deleted', 'source_url');
    return parent::validate($array, $save);
  }

  /**
   * Return the submission structure, which includes defining the text for the data
   * in the rule file as a metaField which is specially handled.
   * 
   * @return array Submission structure for a verification_rule entry.
   */
  public function get_submission_structure()
  {
    return array(
        'model'=>$this->object_name,
        'metaFields'=>array('data','metadata')      
    );
  }
  
  /**
   * Saves the part of the rule file metadata section which needs to go into the verification_rule
   * record. A rule file will be overwritten if it comes from the same source url and filename.
   * @param string $source_url URL the file was sourced from. The model instance points to the
   * created/updated verification record after the operation.
   * @param string $filename Rule file filename.
   * @param array $metadata Array of key/value pairs read from the file's metadata section.
   */
  public function save_verification_rule($source_url, $filename, &$metadata) {
    // find existing or new verification rule record. Empty string stored in db as null.
    if (empty($source_url))
      $source_url=null;  
    $this->where(array('source_url'=>$source_url, 'source_filename'=>$filename))->find();
    if (isset($metadata['shortname']))
      $title = $metadata['shortname'];
    else {
      // no short name in the rule, so build a valid title
      $titleArr=array($metadata['testtype']);
      if (isset($metadata['organisation']))
        $titleArr[] = $metadata['organisation'];
      $title = implode(' - ', $titleArr);
    }
    if (isset($metadata['errormsg']))
      $errorMsg = $metadata['errormsg'];
    else
      $errorMsg = 'Test failed';
    $submission = array(
      'verification_rule:title'=>$title,
      'verification_rule:test_type'=>$metadata['testtype'],
      'verification_rule:source_url'=>'SOURCE!', //$source_url,
      'verification_rule:source_filename'=>$filename,
      'verification_rule:error_message'=>$errorMsg,
      // The error message gives us a useful description in the absence of a specific one
      'verification_rule:description'=>isset($metadata['description']) ?
          $metadata['description'] : $errorMsg
    );
    $this->isNewRule = $this->id===0;
    if (!$this->isNewRule)
      $submission['verification_rule:id']=$this->id;
    $this->set_submission_data($submission);
    $this->submit();
    $vr = ORM::factory('verification_rule', $this->id);
    kohana::log('debug', 'Saved as: '.print_r($vr->as_array(), true));
    // remove things from the metadata which we have put in the verification_rule record
    // so they don't get processed again later
    unset($metadata['testtype']);
    unset($metadata['description']);
    unset($metadata['shortname']);
    unset($metadata['organisation']);
    unset($metadata['errormsg']);
    unset($metadata['group']);
    unset($metadata['lastchanged']);
    if (count($this->getAllErrors())>0)
      throw new exception("Errors saving $filename to database - ".print_r($this->getAllErrors(), true));
  }
  
  /**
   * Uses to associative array of metadata values to ensure that the correct list of metadata
   * records exists for the current rule instance.
   * @param array $currentRule Definition of the current rule
   * @param array $metadata Associative array of metadata key/value pairs.
   */
  public function save_verification_rule_metadata($currentRule, $metadata) {
    $recordsInSubmission=array();
    // work out the fields to submit into the metadata table
    $fields = array_merge_recursive($currentRule['required'], $currentRule['optional']);
    if (isset($fields['Metadata'])) {
      // A quick test to ensure we don't have keys in the data we shouldn't.
      $got = array_keys(array_change_key_case($metadata));
      $expect = array_map('strtolower', $fields['Metadata']);
      $dontWant = array_diff($got, $expect);
      if (count($dontWant))
        throw new exception('The following metadata items are not recognised for this rule type: '.implode(',', $dontWant));
      // force keys lowercase for case-insensitive lookup
      $metadata = array_change_key_case($metadata, CASE_LOWER);
      foreach ($fields['Metadata'] as $idx=>$field) {
        if (array_key_exists(strtolower($field), $metadata) && !empty($metadata[strtolower($field)])) {
          $recordsInSubmission[] = "(verification_rule_id='".$this->id."' and key='".$field."')";
          $vrm = ORM::Factory('verification_rule_metadatum')->where(array(
              'verification_rule_id'=>$this->id, 'key'=>$field
          ))->find();
          $submission=array(
            'verification_rule_metadatum:key'=>$field,
            'verification_rule_metadatum:value'=>$metadata[strtolower($field)],
            'verification_rule_metadatum:verification_rule_id'=>$this->id,
            'verification_rule_metadatum:deleted'=>'f'
          );
          if ($vrm->id!==0)
            $submission['verification_rule_metadatum:id']=$vrm->id;
          $vrm->set_submission_data($submission);
          $vrm->submit();
          if (count($vrm->getAllErrors())>0)
            throw new exception("Errors saving $filename to database - ".print_r($vrm->getAllErrors(), true));
        } elseif (isset($currentRule['required']['Metadata']) && in_array($field, $currentRule['required']['Metadata'])) 
          throw new exception("Required field $field missing from the metadata");
      }
    }
    // Construct a query to remove any existing records
    $deleteQuery = 'update verification_rule_metadata set deleted=true where verification_rule_id='.$this->id;
    if (count($recordsInSubmission))
      $deleteQuery .= ' and not ('.implode(' or ', $recordsInSubmission).')';
    $this->db->query($deleteQuery);     
  }
  
  public function save_verification_rule_data($currentRule, $data) {
    $fields = array_merge_recursive($currentRule['required'], $currentRule['optional']);
    unset($fields['Metadata']);
    // counter to keep track of groups of related field values in a data section. Not implemented properly 
    // at the moment but we are likely to need this e.g. for periodInYear checks with multiple stages.
    $dataGroup=1;
    $rows = array();
    // A quick test to ensure we don't have sections in the data we shouldn't.
    $got = array_keys(array_change_key_case($data));
    $expect = array_keys(array_change_key_case($fields));
    $dontWant = array_diff($got, $expect);
    if (count($dontWant)) {
      
      kohana::log('debug', 'got '.print_r($got, true)); // this is the nested values, should be top level
      kohana::log('debug', 'expect '.print_r($expect, true)); // this is the top level category
      kohana::log('debug', 'dontWant '.print_r($dontWant, true));
      throw new exception('The following data sections are not recognised for this rule type: '.implode(',', $dontWant));
    }
    foreach($fields as $dataSection=>$dataContent) {
      if (isset($data[strtolower($dataSection)])) {
        if (!in_array('*', $dataContent)) {
          // A quick test to ensure we don't have keys in the data we shouldn't. Test not required if we have a wildcard key allowed.
          $got = array_keys(array_change_key_case($data[strtolower($dataSection)]));
          $expect = array_map('strtolower', $dataContent);
          $dontWant = array_diff($got, $expect);
          if (count($dontWant))
            throw new exception('The following data keys are not recognised for this rule type: '.implode(',', $dontWant).print_r($dataContent, true));
        }
      
        foreach ($dataContent as $key) {
          if ($key==='*') {
            // * means that any field value is allowed
            foreach ($data[strtolower($dataSection)] as $anyField=>$anyValue)
              $rows[] = array('dataSection'=>$dataSection, 'dataGroup'=>$dataGroup, 'key'=>$anyField, 'value'=>$anyValue);
          }
          elseif (isset($data[strtolower($dataSection)][strtolower($key)])) 
            // doing specific named fields
            $rows[] = array('dataSection'=>$dataSection, 'dataGroup'=>$dataGroup, 'key'=>$key, 
                'value'=>$data[strtolower($dataSection)][strtolower($key)]);
          else {
            if (isset($currentRule['required'][$dataSection]) && in_array($key, $currentRule['required'][$dataSection])) 
              throw new exception("Required field $key missing from the data for section $dataSection");
            
          }
        }
      }
    }
    $this->save_verification_rule_data_records($rows);
  }
  
  /**
   * Call this method after making any updates to a verification rule including the data
   * and metadata.
   * @param array $currentRule Definition of the rule.
   */
  public function postProcess($currentRule) {
    $ppMethod = $currentRule['plugin'].'_data_cleaner_postprocess';
    require_once(MODPATH.$currentRule['plugin'].'/plugins/'.$currentRule['plugin'].'.php');
    if (function_exists($ppMethod)) {
      call_user_func($ppMethod, $this->id, $this->db);
    }
    
  }
  
  /**
   * Save a verification rule data record, either overwriting existing or creating a new one.
   * Avoids ORM for performance reasons as some files can be pretty big.
   * @param integer $vrId
   * @param array $rows
   */
  private function save_verification_rule_data_records($rows) { 
    $done=array();
    $recordsInSubmission=array();
    // only worth trying an update if we are updating an existing rule.
    if (!$this->isNewRule) {
      foreach ($rows as $idx=>$row) {
        $updated = $this->db->update('verification_rule_data', 
          array('value'=>$row['value'], 'updated_on'=>date("Ymd H:i:s"), 
            'updated_by_id'=>$_SESSION['auth_user']->id, 'deleted'=>'f'),
          array(
            'header_name'=>$row['dataSection'], 'data_group'=>$row['dataGroup'], 
            'verification_rule_id'=>$this->id, 'key'=>strval($row['key'])
          )
        );
        if (count($updated))
          $done[]=$idx;
        $recordsInSubmission[] = "(header_name='".$row['dataSection']."' and data_group='".$row['dataGroup']."'". 
            " and verification_rule_id='".$this->id."' and key='".strval($row['key'])."')";
      }
      // Construct a query to remove any existing records
      $deleteQuery = 'update verification_rule_data set deleted=true where verification_rule_id='.$this->id;
      if (count($recordsInSubmission))
        $deleteQuery .= ' and not ('.implode(' or ', $recordsInSubmission).')';
      $this->db->query($deleteQuery);
    }
    // build a multirow insert as it is faster than doing lots of single inserts
    $rowList = '';
    foreach ($rows as $idx=>$row) {
      if (array_search($idx, $done)===false) {
        if ($rowList!=='')
          $rowList .= ',';
        $rowList .= "('".$row['dataSection']."',".$row['dataGroup'].",$this->id,'".strval($row['key'])."','".
            $row['value']."','".date("Ymd H:i:s")."',".$_SESSION['auth_user']->id.",'".date("Ymd H:i:s")."',".$_SESSION['auth_user']->id.")";
      }
    }
    if ($rowList)
      $this->db->query("insert into verification_rule_data(header_name, data_group, verification_rule_id, key, value, ".
          "updated_on, updated_by_id, created_on, created_by_id) values $rowList");
  }
  
 /**
  * Overrides the postSubmit function to add in additional metadata and data values. 
  */
  protected function postSubmit($isInsert)
  {
    require_once(DOCROOT.'client_helpers/helper_base.php');
    $result = true;
    if (isset($this->submission['metaFields'])) { 
      $currentRule = data_cleaner::get_rule($this->test_type);
      if (isset($this->submission['metaFields']['metadata']['value'])) {
        $metadata = helper_base::explode_lines_key_value_pairs($this->submission['metaFields']['metadata']['value']);
        $this->save_verification_rule_metadata($currentRule, $metadata);
        $data = data_cleaner::parse_test_file($this->submission['metaFields']['data']['value']);
        $this->save_verification_rule_data($currentRule, $data);
        $this->postProcess($currentRule);
      }
    }
    return true;
    
    
  }
  
}