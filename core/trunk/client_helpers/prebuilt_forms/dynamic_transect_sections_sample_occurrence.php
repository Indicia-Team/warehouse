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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */
 
require_once('dynamic_sample_occurrence.php');

/**
 * A input form with a grid for entering records with a section ID attribute that links the record
 * to a given section in a transect. 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_dynamic_transect_sections_sample_occurrence extends iform_dynamic_sample_occurrence {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_dynamic_transect_sections_sample_occurrence_definition() {
    return array(
      'title'=>'Dynamic transect sections sample occurrence',
      'category' => 'General Purpose Data Entry Forms',
      'description' => 'A variant of the dynamic sample occurrence form which allows records to be attributes to sections of a transect using a section ID attribute in the grid. ' .
         'This form does not currently support occurrence media or different methods of detecting if a record is present in the grid.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    $r = array_merge(
      parent::get_parameters(),
      array(
        array(
            'fieldname'=>'section_id_attribute',
            'label'=>'Section ID attribute',
            'helpText'=>'Choose the custom occurrence attribute which is used to store the section ID.',
            'type' => 'select',
            'table'=>'occurrence_attribute',
            'captionField'=>'caption',
            'valueField'=>'id',
            'group'=>'Species'
        )
      )
    );
    return $r;
  }
  
  protected static function get_control_species($auth, $args, $tabAlias, $options) {
    data_entry_helper::$onload_javascript .= "indiciaFns.bindTabsActivate($($('#$tabAlias').parent()), function(event, ui) {
      if (ui.panel.id==='$tabAlias') { setSectionDropDown(); }
    });\n";
    // we need a place to store the subsites, to save loading from the db on submission
    $r = '<input type="hidden" name="subsites" id="subsites" value="" />';
    // plus hiddens to store the main sample's sref info
    $r .= data_entry_helper::hidden_text(array(
      'fieldname'=>'sample:entered_sref',
      'id'=>'imp-sref'
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname'=>'sample:entered_sref_system',
      'id'=>'imp-sref-system'
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname'=>'sample:geom',
      'id'=>'imp-geom'
    ));
    // plus the sample method ids
    $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Transect', 'Transect Section'));
    $r .= '<input type="hidden" name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />';
    $r .= '<input type="hidden" name="subsample:sample_method_id" value="'.$sampleMethods[1]['id'].'" />';
    // This option forces the grid to load all child sample occurrences, though we will ignore the hidden SampleIDX column and instead
    // use the section column to bind to samples
    $options['speciesControlToUseSubSamples']=true;
    $r .= parent::get_control_species($auth, $args, $tabAlias, $options);
    // build an array of existing sub sample IDs, keyed by subsite location Id.
    $subSampleIds = array();
    if (isset(data_entry_helper::$entity_to_load)) {
      foreach (data_entry_helper::$entity_to_load as $key => $value) {
        if (preg_match('/^sc:(\d+):(\d+):sample:id$/', $key, $matches)) {
          $subSampleIds[data_entry_helper::$entity_to_load["sc:$matches[1]:$matches[2]:sample:location_id"]] = $value;
        }
      }
    }
    $r .= '<input type="hidden" name="subSampleIds" value="'.htmlspecialchars(json_encode($subSampleIds)).'" />';
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $submission = self::build_sample_subsamples_occurrences_submission($values, $args['section_id_attribute']);
    return($submission);
  }
  
  /**
   * Helper function to simplify building of a submission that contains a single supersample,
   * with multiple subsamples, each of which has multiple occurrences records, as generated 
   * by a species_checklist control.
   *
   * @param array $values List of the posted values to create the submission from.
   * @param integer $section_id_attribute The attribute ID that holds the section this record is against
   * @param boolean $include_if_any_data If true, then any list entry which has any data
   * set will be included in the submission. Set this to true when hiding the select checkbox
   * in the grid.
   * @param array $zero_attrs Set to an array of abundance attribute field IDs that can be
   * treated as abundances. Alternatively set to true to treat all occurrence custom attributes
   * as possible zero abundance indicators.
   * @param array $zero_values Set to an array of values which are considered to indicate a 
   * zero abundance record if found for one of the zero_attrs. Values are case-insensitive. Defaults to 
   * array('0','None','Absent').
   * of values that can be treated as meaning a zero abundance record. E.g.
   * array('

   * @return array Sample submission array
   */
  public static function build_sample_subsamples_occurrences_submission($values, $section_id_attribute, $include_if_any_data=false,
       $zero_attrs = true, $zero_values=array('0','None','Absent'))
  {
    $subsites = json_decode($values['subsites'], true);
    unset($values['subsites']);
    $subSampleIds = json_decode($values['subSampleIds'], true);
    unset($values['subSampleIds']);
    // We're mainly submitting to the sample model
    $sampleMod = submission_builder::wrap_with_images($values, 'sample');
    $subModels = self::wrap_species_checklist_with_subsamples($values, $section_id_attribute, $subsites, $subSampleIds, $include_if_any_data,
        $zero_attrs, $zero_values);

    // Add the subsamples/occurrences in as subModels without overwriting others such as a sample image
    if (array_key_exists('subModels', $sampleMod)) {
      $sampleMod['subModels'] = array_merge($sampleMod['subModels'], $subModels);
    } else {
      $sampleMod['subModels'] = $subModels;
    }

    return $sampleMod;
  }
  
  public static function wrap_species_checklist_with_subsamples($arr, $section_id_attribute, $subsites, $subSampleIds, $include_if_any_data=false,
          $zero_attrs = true, $zero_values=array('0','None','Absent')) {
    if (array_key_exists('website_id', $arr)){
      $website_id = $arr['website_id'];
    } else {
      throw new Exception('Cannot find website id in POST array!');
    }
    // determiner and record status can be defined globally for the whole list.
    if (array_key_exists('occurrence:determiner_id', $arr))
      $determiner_id = $arr['occurrence:determiner_id'];
    if (array_key_exists('occurrence:record_status', $arr))
      $record_status = $arr['occurrence:record_status'];
    // Set the default method of looking for rows to include - either using data, or the checkbox (which could be hidden)
    $include_if_any_data = $include_if_any_data || (isset($arr['rowInclusionCheck']) && $arr['rowInclusionCheck']=='hasData');
    // Species checklist entries take the following format.
    // sc:<subsampleIndex>:[<sample_id>]:sample:deleted
    // sc:<subsampleIndex>:[<sample_id>]:sample:geom
    // sc:<subsampleIndex>:[<sample_id>]:sample:entered_sref
    // sc:<subsampleIndex>:[<sample_id>]:smpAttr:[<sample_attribute_id>]
    // sc:<rowIndex>:[<occurrence_id>]:occurrence:sampleIDX (val set to subsample index)
    // sc:<rowIndex>:[<occurrence_id>]:present (checkbox with val set to ttl_id
    // sc:<rowIndex>:[<occurrence_id>]:occAttr:<occurrence_attribute_id>[:<occurrence_attribute_value_id>]
    // sc:<rowIndex>:[<occurrence_id>]:occurrence:comment
    // sc:<rowIndex>:[<occurrence_id>]:occurrence_medium:fieldname:uniqueImageId
    $occurrenceRecords = array();
    $sampleRecords = array();
    $subModels = array();
    foreach ($arr as $key=>$value){
      if (substr($key, 0, 3)=='sc:' && substr($key, 2, 7)!=':-idx-:' && substr($key, 2, 3)!=':n:'){ //discard the hidden cloneable rows
        // Don't explode the last element for occurrence attributes
        $a = explode(':', $key, 4);
        $b = explode(':', $a[3], 3);
        if ($value && count($b)>=2) {
          if($b[0] == "occAttr" && $b[1] == $section_id_attribute){ 
            if (!isset($sampleRecords['smp'.$value])) {
              $sampleRecords['smp'.$value] = array();
            }
            $occurrenceRecords[$a[1]]['sectionIdVal']=$value;
          }
        }
        $occurrenceRecords[$a[1]][$a[3]] = $value;
        if($a[2]) $occurrenceRecords[$a[1]]['id'] = $a[2];        
      }
    }
    foreach ($occurrenceRecords as $record) {
      $present = !empty($record['present']);
      if (array_key_exists('id', $record) || $present) { // must always handle row if already present in the db
        if (!$present)
          // checkboxes do not appear if not checked. If uncheck, delete record.
          $record['deleted'] = 't';
        $record['zero_abundance']=self::recordZeroAbundance($record, $section_id_attribute);
        $record['taxa_taxon_list_id'] = $record['present'];
        $record['website_id'] = $website_id;       
        if (isset($determiner_id)) 
          $record['determiner_id'] = $determiner_id;
        if (isset($record_status))
          $record['record_status'] = $record_status;
        $occ = data_entry_helper::wrap($record, 'occurrence');
        // At this point, a deleted record only has present=0 and an id. No link to the sample since our section ID field has been
        // disabled. So we need to use the subSampleIds data to work out the original site ID and link via that.        
        if (isset($record['sectionIdVal']))
          $sectionId = $record['sectionIdVal'];
        else {
          $orderedSubSites = array_keys($subSampleIds);
          $sectionId = $orderedSubSites[$record['occurrence:sampleIDX']];
        }
        $sampleRecords["smp$sectionId"]['occurrences'][] = array('fkId' => 'sample_id','model' => $occ);
      } 
    }
    // convert subsites to a keyed array, for easier lookup
    $keyedSS = array();
    foreach ($subsites as $ss) {
      $keyedSS["ss$ss[id]"] = $ss;
    }
    foreach ($sampleRecords as $id => $sampleRecord) {
      $idx = preg_replace('/^smp/', '', $id);
      $subsite = $keyedSS["ss$idx"];
      $occs = $sampleRecord['occurrences'];
      unset($sampleRecord['occurrences']);
      $sampleRecord['website_id'] = $website_id;
      // copy essentials down to each subsample
      if (!empty($arr['survey_id']))
        $sampleRecord['survey_id'] = $arr['survey_id'];
      if (!empty($arr['sample:date']))
        $sampleRecord['date'] = $arr['sample:date'];
      if (!empty($arr['subsample:sample_method_id']))
        $sampleRecord['sample_method_id'] = $arr['subsample:sample_method_id'];
      $sampleRecord['entered_sref']=$subsite['centroid_sref'];
      $sampleRecord['entered_sref_system']=$subsite['centroid_sref_system'];
      $sampleRecord['geom']=$subsite['boundary_geom'];
      $sampleRecord['location_id']=$subsite['id'];
      if (!empty($subSampleIds[$sampleRecord['location_id']]))
        $sampleRecord['id'] = $subSampleIds[$sampleRecord['location_id']];
      if (!empty($arr['sample:entered_sref_system']))
        $sampleRecord['entered_sref_system'] = $arr['sample:entered_sref_system'];
      if (!empty($arr['sample:location_name']) && empty($sampleRecord['location_name']))
        $sampleRecord['location_name'] = $arr['sample:location_name'];
      if (!empty($arr['sample:input_form']))
        $sampleRecord['input_form'] = $arr['sample:input_form'];
      $subSample = data_entry_helper::wrap($sampleRecord, 'sample');
      // Add the subsample/soccurrences in as subModels without overwriting others such as a sample image
      if (array_key_exists('subModels', $subSample)) {
        $subSample['subModels'] = array_merge($sampleMod['subModels'], $occs);
      } else {
        $subSample['subModels'] = $occs;
      }
      $subModel = array('fkId' => 'parent_id', 'model' => $subSample);
      $copyFields = array();
      if(!isset($sampleRecord['date'])) $copyFields = array('date_start'=>'date_start','date_end'=>'date_end','date_type'=>'date_type');
      if(!isset($sampleRecord['survey_id'])) $copyFields['survey_id'] = 'survey_id';
      if(count($copyFields)>0) $subModel['copyFields'] = $copyFields; // from parent->to child
      $subModels[] = $subModel;
    }
    return $subModels;
  }
  
  private static function recordZeroAbundance($record, $section_id_attribute) {
    $zeros=false;
    $nonZeros=false;
    foreach ($record as $attr => $value) {
      if (preg_match('/^occAttr:/', $attr) && !preg_match("/^occAttr:$section_id_attribute(:\d+)?$/", $attr)) {
        if ($value===0 || $value==='0') {
          $zeros=true;
        } elseif ($value) {
          $nonZeros=true;
        }
      }
    }
    return $zeros && !$nonZeros ? 't' : 'f';
  }

}
