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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 *
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('includes/dynamic.php');
require_once('dynamic_sample_occurrence.php');


class iform_dynamic_sample_occurrence_splash extends iform_dynamic_sample_occurrence {

  // The ids we are loading if editing existing data
  protected static $loadedSampleId;
  protected static $loadedOccurrenceId;
  protected static $occurrenceIds = array();
 
  /**
   * The list of attributes loaded for occurrences. Keep a class level variable, so that we can track the ones we have already
   * emitted into the form globally.
   * @var array
   */
  protected static $occAttrs;

  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_dynamic_sample_occurrence_splash_definition() {
    return array(
      'title'=>'Splash sample with occurrences form for Epiphyte surveys',
      'category' => 'Forms for specific surveying methods',
      'description'=>'Form for submitting Splash sample/occurrence records for Epiphyte surveys'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'tree_occ_attrs',
          'caption'=>'Tree Occurrence Attributes',
          'description'=>'The occcurrence attribute that hold the Epiphyte count for trees 1 to 10 as a comma seperated list. The list should be in the correct order with tree 1 first e.g. 34,35,36,37,38,39,40,41,42,43',
          'required'=>true,
          'type'=>'string',
          'group'=>'Attribute Setup'
        ),
        array(
          'name'=>'tree_grid_ref_occ_attr_id',
          'caption'=>'Tree Grid Reference Occurrence Attribute',
          'description'=>'The occurrence attribute relating to a tree\'s grid reference',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Attribute Setup'
        ),
        array(
          'name'=>'occurrence_record_grid_id',
          'caption'=>'Occurrence Record Grid Occurrence Attribute',
          'description'=>'The occurrence attribute which holds the id of the grid that occurrences will be loaded onto in edit mode.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Attribute Setup'
        ),
      )
    );
    return $retVal;
  }
 
  /*
   * Override the get_form so that when we are in edit mode, the grid that displays existing occurrences has empty cells disabled.
   * This means to create new occurrences the user must fill in the clonable row or fill in the grid of prepopulated Epiphytes. Due to existing code, use of these disabled cells would not
   * work correctly in this sitution. Disabling them also makes the user interface much clearer.
   * This setup allows the occurrences to be saved correctly with minimal alteration to existing code that we already know works correctly.
   */
  public static function get_form($args, $node) {
    if (!empty($_GET['sample_id'])) {
      //Disable the existing records grid so the user can only delete items from here
      data_entry_helper::$javascript .= "$('#Epiphytes-free').find('input[type=checkbox]').attr('disabled','disabled');\n";
      //Need to stop user clicking on checkboxes as they are deliberately re-enabled on submit because disabled checkboxes don't submit to post. On validation error they enable
      //but user is left on the page, so stop them clicking on checkboxes by other means. This is a bit of a workaround.
      data_entry_helper::$javascript .= "$('#Epiphytes-free').find('input[type=checkbox]').click(false);\n";
      //Just before the post is processed, we re-enable the grid, so the values are exposed in the submission else they won't be processed
      data_entry_helper::$javascript .= "$('#entry_form').submit(function() { $('#Epiphytes-free').find('input[type=checkbox]').removeAttr('disabled');});\n";
    }
    return parent::get_form($args, $node);
  }
 
  /**
   * Returns the species checklist input control setup so that there is one subsample for each row on the grid.
   * @param array $auth Read authorisation tokens
   * @param array $args Form configuration
   * @param array $options additional options for the control, e.g. those configured in the form structure.
   * @return HTML for the species_checklist control.
   */
  protected static function get_control_species($auth, $args, $tabAlias, $options) {
    $options['subSamplePerRow']=true;
    $options['speciesControlToUseSubSamples']=true;
    $r = parent::get_control_species($auth, $args, $tabAlias, $options);
    return $r;
  }
 
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    // Any remembered fields need to be made available to the hook function outside this class.
    global $remembered;
    $remembered = isset($args['remembered']) ? $args['remembered'] : '';
    //Page only supported in grid mode at the moment.
    if (isset($values['gridmode']))
      $submission = self::get_splash_subsamples_occurrences_submission($args,$values);
    else
      drupal_set_message('Please set the page to "gridmode"'); 
    //Cycle through each occurrence
    foreach($submission['subModels'] as &$occurrenceAndSubSampleRecord) {
      //We need to copy the location information to the sub-sample else it won't get
      //picked up in the cache occurrences table.
      if (!empty($submission['fields']['location_id']['value']))
        $occurrenceAndSubSampleRecord['model']['fields']['location_id']['value'] = $submission['fields']['location_id']['value'];
      if (!empty($submission['fields']['location_name']['value']))
        $occurrenceAndSubSampleRecord['model']['fields']['location_name']['value'] = $submission['fields']['location_name']['value'];
    }  
    return($submission);
  }
 
 
  /*
   * Get the model structure for Splash and return it ready for subbmission.
   * @param array $args iform parameters.
   * @param array $values Associative array of form data values.
   * @return array Partially completed submission structure.
   */
  public static function get_splash_subsamples_occurrences_submission($args,$values, $include_if_any_data=false,
       $zero_attrs = false, $zero_values=array('0','None','Absent'))
  {
    $sampleMod = submission_builder::wrap_with_images($values, 'sample');
    $subModels = self::create_splash_subsample_occurrence_structure($values, $include_if_any_data,
        $zero_attrs, $zero_values, $args);

    // Add the subsamples/occurrences in as subModels without overwriting others such as a sample image
    if (array_key_exists('subModels', $sampleMod)) {
      $sampleMod['subModels'] = array_merge($sampleMod['subModels'], $subModels);
    } else {
      $sampleMod['subModels'] = $subModels;
    }
    return $sampleMod;
  }
 
  /*
   * Create the submission structure required for Splash.
   * The format is as follows,
   * Three grids: Trees and 2 Epiphytes grids
   * For each tree, a sub-sample is created as it needs its own grid reference.
   * Each tree also has a tree occurrence.
   * Each tree can also have any number of Epiphyte occurrences.
   * The Tree and Epiphyte occurrences are held as submodels of the tree sub-sample.
   *
   * On screen, the Epiphyte occurrences are held in 2 separate grids.
   * One has a prepopulated list of several commonly used taxa_taxon_ids, the other list allows free text
   * to be entered by the user to select any species from the species list.
   * There is an Epiphyte per row.
   * Each column is a count of Epiphytes for trees 1 to 10 held as occurrence attributes.
   * If a particular Epiphyte has a count associated with it for a given tree, then we know to create an occurrence for
   * that Epiphyte.
   * When in edit mode, all occurrences (including ones from the prepopulated grid are loaded onto the second Epiphytes
   * grid. There is one row per occurrence. Cells for trees which do not have a count value are disabled as filling these
   * in would result in existing code breaking the page, however it is also convenient as it makes the user interface much clearer.
   * A clonable row is still present allowing the user to still fill in free text occurrences.
   *
   */
  public static function create_splash_subsample_occurrence_structure($arr, $include_if_any_data=false,
          $zero_attrs, $zero_values=array('0','None','Absent'), $args) {

    if (array_key_exists('website_id', $arr)){
      $website_id = $arr['website_id'];
    } else {
      throw new Exception('Cannot find website id in POST array!');
    }
    //Use existing code to wrap subsamples on the grid.
    $subModels = data_entry_helper::wrap_species_checklist_with_subsamples($arr, $include_if_any_data,
          $zero_attrs, $zero_values);
    //Each epiphyte has an occurrence_attribute which holds the number found on a particular tree, the list of these custom attributes is supplied
    //to the code as an administrator supplied option.
    $treeEpiCountOccAttr=explode(',',$args['tree_occ_attrs']);
    //Get the get the tree and Epiphyte occurrence records.
    foreach ($arr as $key=>$value) {
      if (substr($key, 0, 3)=='sc:' && substr($key, 3, 5)=='trees'){
        $a = explode(':', $key, 4);
        $b = explode(':', $a[3], 2);
        if($b[0] != "sample" && $b[0] != "smpAttr"){
          $treeOccurrenceRecords[$a[1]][$a[3]] = $value;
          if($a[2]) $treeOccurrenceRecords[$a[1]]['id'] = $a[2]; 
        }  
      }
      if (substr($key, 0, 3)=='sc:' && substr($key, 3, 9)=='Epiphytes'){
        $a = explode(':', $key, 4);
        $b = explode(':', $a[3], 2);
        if($b[0] != "sample" && $b[0] != "smpAttr"){
          $epiphyteRecords[$a[1]][$a[3]] = $value;
          if($a[2]) $epiphyteRecords[$a[1]]['id'] = $a[2];
        }  
      }
    }
    foreach ($treeOccurrenceRecords as $key=>$treeOccurrenceRecord) {
      if (!array_key_exists('present',$treeOccurrenceRecord))
        unset($treeOccurrenceRecords[$key]);
    }
    foreach ($epiphyteRecords as $key=>$epiphyteRecord) {
      if (!array_key_exists('present',$epiphyteRecord))
        unset($epiphyteRecords[$key]);
    }
    //To start with, cycle through the sub-sample models i.e cycle through each tree record
    foreach ($subModels as $treeIdx=>&$subSampleModel) {
      //Up to this point we have used existing code to create the sub-sample model.
      //However there is a problem with this approach as the system doesn't understand how to create the Epiphyte occurrences correctly
      //So we need to delete these Epiphyte occurrence sub-models before creating our own.
      //Search through the occurrence records, then search through each value, and if we find one where the grid occurrence attribute is
      //is "trees" then keep the occurrence, otherwise remove it.
      $removeEpiphyteOccurrence=false;
      $keepOccurrence=false;
      foreach ($subSampleModel['model']['subModels'] as $occurrencesForTreeIdx => $occurrencesForTree) {
        //Cycle through the values that make up the Epiphyte record.
        foreach ($occurrencesForTree['model']['fields'] as $itemKey=>$itemValueArray) { 
          //If in edit mode, the key format can 'occAttr:<attribute id>:<attribute value id>', but when we do our tests we want to ignore the
          //attribute value id
          $itemKeyParts=explode(':',$itemKey);
          if ($itemKeyParts[0]=='occAttr' && $itemKeyParts[1]==$args['occurrence_record_grid_id'] && $itemValueArray['value']=='trees') {
            $keepOccurrence=true;
          }
        }
        if ($keepOccurrence!==true)
          unset($subSampleModel['model']['subModels'][$occurrencesForTreeIdx]);
        $keepOccurrence=false;
      }
    }
    foreach ($subModels as $treeIdx=>&$subSampleModel) {
      //If we unset any Epiphyte occurrences, there will be gaps in the arrays numbering, so reset the array numbering
      $subSampleModel['model']['subModels'] = array_values($subSampleModel['model']['subModels']);   
      //When we have removed the Epiphyte records we don't want, then if there isn't even a tree occurrence for the sub-sample,
      //then we can remove the sub-sample completely
      if (empty($subSampleModel['model']['subModels'])) {
        unset($subModels[$treeIdx]);
      } else {
        //Copy the grid reference from the trees grid reference occurrence attribute into the tree sub-sample so it can be saved.
        //When checking the occurrence attribute to use, then we just check the first two parts of the occurrence attribute value key,
        //e.g. occAttr:3, as if the record has already been saved to the database it will be of the form occAttr:3:245, so we need to
        //ignore the last bit which is the id of the occurrence_attribute_value
        foreach ($treeOccurrenceRecords['trees-'.$treeIdx] as $occurrenceItemKey=>$occurrenceRecordItem) {
          $explodedKey = explode(':',$occurrenceItemKey);
          if (!empty($explodedKey[1])) {
            if ($explodedKey[0].':'.$explodedKey[1]==='occAttr:'.$args['tree_grid_ref_occ_attr_id']) {
              if ($occurrenceRecordItem)
                $subSampleModel['model']['fields']['entered_sref']['value']=$occurrenceRecordItem;
                $subSampleModel['model']['fields']['entered_sref_system']['value']='OSGB';
            }
          }
        }
        //Cycle through the parts that make up the Epiphyte rows on the grid.
        foreach ($epiphyteRecords as $epiphyteRecord) {   
          $present = self::wrap_species_checklist_record_present($epiphyteRecord, $include_if_any_data,
            true, $zero_values, array($args['occurrence_record_grid_id']));       
          //If there is an existing records, and the user unchecks the presence checkbox, then delete the occurrence.
          if (array_key_exists('id', $epiphyteRecord)) {
            if ($present==0) {
              $epiphyteOccModel['model']['fields']['deleted']['value'] = 't';
            } else
              $epiphyteOccModel['model']['fields']['zero_abundance']['value']=$present ? 'f' : 't';
          }
          foreach ($epiphyteRecord as $itemKey=>$epiphyteRecordItemValue) {
            //These fields are part of the basic submission structure
            $epiphyteOccModel['fkId']='sample_id';
            $epiphyteOccModel['model']['id']='occurrence';
            $itemKeyParts=explode(':',$itemKey);
            //If there is an id we are dealing with an existing epiphyte occurrence record
            if (!empty($epiphyteRecord['id'])) {
              $epiphyteOccModel['model']['fields']['id']['value']=$epiphyteRecord['id'];
            }
            //Create an occurrence if an Epiphyte is ticked as being present
            if ($itemKeyParts[0]=='occAttr' && $itemKeyParts[1]==$treeEpiCountOccAttr[$treeIdx] && !empty($epiphyteRecordItemValue)) {
              //The different elements of the occurrence record are of the form occAttr:<occurrence attribute number> or if it already exists in the database
              //it is occAttr:<occurrence attribute id>:<occurrence attribute value id>. If we explode this key by ":" character, then if the
              //3rd item (index 2) of the resulting explosion is populated then we know we are dealing with editing of existing data rather than new data.
              if (!empty($itemKeyParts[2]))
                $epiphyteOccModel['model']['fields']['occAttr:'.$itemKeyParts[1].':'.$itemKeyParts[2]]['value']=$epiphyteRecord['occAttr:'.$itemKeyParts[1].':'.$itemKeyParts[2]];
              else
                $epiphyteOccModel['model']['fields']['occAttr:'.$itemKeyParts[1]]['value']=$epiphyteRecord['occAttr:'.$itemKeyParts[1]];
              if (!empty($epiphyteRecord['present']))
                $epiphyteOccModel['model']['fields']['present']['value']=$epiphyteRecord['present'];
              if (!empty($epiphyteOccModel['model']['fields']['present']['value']))
                $epiphyteOccModel['model']['fields']['taxa_taxon_list_id']['value']=$epiphyteOccModel['model']['fields']['present']['value'];

              $epiphyteOccModel['model']['fields']['record_status']['value']='C';
              $epiphyteOccModel['model']['fields']['website_id']=$website_id;
              //Create the attribute that tells the system which grid the occurrence is associated with, used for reloading page.
              //In this case we want to display the Epiphyte occurrences onto the free text grid (even if they were created on the pre-populated grid.
              //Trees are loaded onto the same grid they were created on. We only need to do this in add mode as field doesn't need changing after that.
              if (empty($itemKeyParts[2]))
                $epiphyteOccModel['model']['fields']['occAttr:'.$args['occurrence_record_grid_id']]['value']='Epiphytes-free';
              //Add the Epiphyte to the sub-models of the tree sub-sample
              if (!empty($epiphyteOccModel['model']['fields'])) {        
                $subSampleModel['model']['subModels'][]=$epiphyteOccModel;
                $epiphyteOccModel=array();
              }    
            }
          }
          //If there are no Epiphytes present in the checkboxes on the row, it might be because the user is deleting the record,
          //so it still needs to be submitted for deletion
          if (!empty($epiphyteOccModel['model']['fields'])) {        
            $subSampleModel['model']['subModels'][]=$epiphyteOccModel;
            $epiphyteOccModel=array();
          } 
        }  
      }
    }
    $subModels = array_values($subModels);
    return $subModels;
  }
 
  /**
   * Test whether the data extracted from the $_POST for a species_checklist grid row refers to an occurrence record.
   * @param array $record Record submission array from the form post.
   * @param boolean $include_if_any_data If set, then records are automatically created if any of the custom
   * attributes are filled in.
   * @param mixed $zero_attrs Optional array of attribute IDs to restrict checks for zero abundance records to,
   * or pass true to check all attributes.
   * @param array $zero_values Array of values to consider as zero, which might include localisations of words
   * such as "absent" and "zero" as well as "0".
   * @param array $hasDataIgnoreAttrs Array or attribute IDs to ignore when checking if record is present.
   * @access Private
   * @return boolean True if present, false if absent (zero abundance record), null if not defined in the data (no occurrence).
   */
  private static function wrap_species_checklist_record_present($record, $include_if_any_data, $zero_attrs, $zero_values, $hasDataIgnoreAttrs) {
    // present should contain the ttl ID, or zero if the present box was unchecked
    $gotTtlId=array_key_exists('present', $record) && $record['present']!='0';
    // as we are working on a copy of the record, discard the ID and taxa_taxon_list_id so it is easy to check if there is any other data for the row.
    unset($record['id']);
    unset($record['present']); // stores ttl id
    unset($record['occurrence:sampleIDX']);
    // also discard any attributes we included in $hasDataIgnoreAttrs
    foreach ($hasDataIgnoreAttrs as $attrID) {
      unset($record['occAttr:' . $attrID]);
    }
    // if zero attrs not an empty array, we must proceed to check for zeros
    if ($zero_attrs) {
      // check for zero abundance records. First build a regexp that will match the attr IDs to check. Attrs can be
      // just set to true, which means any attr will do.
      if (is_array($zero_attrs))
        $ids='['.implode('|',$zero_attrs).']';
      else
        $ids = '\d+';
      $zeroCount=0;
      $nonZeroCount=0;
      foreach ($record as $field=>$value) {
        // Is this a field used to trap zero abundance data, with a zero value
        if (preg_match("/occAttr:$ids(:\d+)?$/", $field)) { 
          if (in_array($value, $zero_values))
            $zeroCount++;
          else
            $nonZeroCount++;
        }
      }
      // return false (zero) if there are no non-zero abundance data, and at least one zero abundance indicators
      if ($zeroCount && !$nonZeroCount) {
        return false;
      }
    }
    $recordData=implode('',$record);
    $record = ($include_if_any_data && $recordData!='' && !preg_match("/^[0]*$/", $recordData)) ||       // inclusion of record is detected from having a non-zero value in any cell
        (!$include_if_any_data && $gotTtlId); // inclusion of record detected from the presence checkbox
    // return null if no record to create
    return $record ? true : null;
  }
}