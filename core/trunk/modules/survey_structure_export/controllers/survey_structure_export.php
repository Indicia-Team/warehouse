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
 * @package    Survey Structure Export
 * @subpackage Controllers
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Controller class for the survey structure export plugin module.
 */
class Survey_structure_export_Controller extends Indicia_Controller {

  private $log=array();
  
  private $userId;
  
  const SQL_FETCH_ALL_SAMPLE_ATTRS = "select 	a.id, a.caption, a.data_type, a.applies_to_location, a.validation_rules, a.multi_value, a.public, a.applies_to_recorder, a.system_function,
          sm.term as aw_restrict_to_sample_method_id_term, aw.validation_rules as aw_validation_rules, aw.weight as aw_weight, aw.control_type_id as aw_control_type_id, 
          aw.website_id as aw_website_id, aw.default_text_value as aw_default_text_value, aw.default_float_value as aw_default_float_value, aw.default_int_value as aw_default_int_value, 
          aw.default_date_start_value as aw_default_date_start_value, aw.default_date_end_value as aw_default_date_end_value, aw.default_date_type_value as aw_default_date_type_value,
          fsb1.name as fsb1_name, fsb1.weight as fsb1_weight, fsb2.name as fsb2_name, fsb2.weight as fsb2_weight,
          max(t.termlist_title) as termlist_title, 
          array_to_string(array_agg((t.term || '|' || t.language_iso || '|' || coalesce(t.sort_order::varchar, '') || '|' || coalesce(tp.term::varchar, ''))::varchar order by t.sort_order, t.term), '**') as terms
        from sample_attributes a
        join sample_attributes_websites aw on aw.sample_attribute_id=a.id and aw.deleted=false
        left join cache_termlists_terms t on t.termlist_id=a.termlist_id
        left join cache_termlists_terms tp on tp.id=t.parent_id
        left join cache_termlists_terms sm on sm.id=aw.restrict_to_sample_method_id
        left join form_structure_blocks fsb1 on fsb1.id=aw.form_structure_block_id and fsb1.survey_id=aw.restrict_to_survey_id
        left join form_structure_blocks fsb2 on fsb2.id=fsb1.parent_id and fsb2.survey_id=aw.restrict_to_survey_id
        where a.deleted=false
        {where}
        group by a.id, a.caption, a.data_type, a.applies_to_location, a.validation_rules, a.multi_value, a.public, a.applies_to_recorder, a.system_function,
          sm.term, aw.validation_rules, aw.weight, aw.control_type_id, 
          aw.website_id, aw.default_text_value, aw.default_float_value, aw.default_int_value, 
          aw.default_date_start_value, aw.default_date_end_value, aw.default_date_type_value,
          fsb1.name, fsb1.weight, fsb2.name, fsb2.weight
        order by fsb1.weight, fsb2.weight, aw.weight";
 
  const SQL_FIND_SAMPLE_ATTRS = "select a.id, a.caption, a.data_type, a.applies_to_location, a.validation_rules, a.multi_value, a.public, a.applies_to_recorder, a.system_function,
	max(t.termlist_title) as termlist_title, 
	array_to_string(array_agg((t.term || '|' || t.language_iso || '|' || coalesce(t.sort_order::varchar, '') || '|' || coalesce(tp.term::varchar, ''))::varchar order by t.sort_order, t.term), '**') as terms
from sample_attributes a
left join cache_termlists_terms t on t.termlist_id=a.termlist_id
left join cache_termlists_terms tp on tp.id=t.parent_id
where a.deleted=false
{where}
group by a.id, a.caption, a.data_type, a.applies_to_location, a.validation_rules, a.multi_value, a.public, a.applies_to_recorder, a.system_function";

  const SQL_FIND_TERMLIST = "select t.termlist_id, t.termlist_title, 
      array_to_string(array_agg((t.term || '|' || t.language_iso || '|' || coalesce(t.sort_order::varchar, '') || '|' || coalesce(tp.term::varchar, ''))::varchar order by t.sort_order, t.term), '**') as terms
    from cache_termlists_terms t
    left join cache_termlists_terms tp on tp.id=t.parent_id
    where {where}
    group by t.termlist_id, t.termlist_title";
    
  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();
  }
  
  /**
   * Controller action for the export tab content. Display the export page.
   */
  public function index() {
    $this->view = new View('survey_structure_export/index');
    $this->view->surveyId=$this->uri->last_segment();
    //Get the attribute data (including termlists) associated with the survey ready to export
    $export = $this->getSurveyAttributes($this->view->surveyId);
    $this->view->export = json_encode($export);
    $this->template->content = $this->view;
  }
 
  /**
   * Perform the import
   */
  public function save() {
    $surveyId = $_POST['survey_id'];
    try {
      $importData = json_decode($_POST['import_survey_structure'], true);
      $this->doImport($importData,$_POST['survey_id']);
      $this->template->title = 'Import Complete';
      $this->view = new View('survey_structure_export/import_complete');
      $this->view->log = $this->log;
      $this->template->content = $this->view;
    } catch (Exception $e) {
      error::log_error('Exception during survey structure import', $e);
      $this->template->title = 'Error during survey structure import';
      $this->view = new View('templates/error_message');
      $this->view->message='An error occurred during the survey structure import. ' .
                           'Please make sure the import data is valid. More information can be found in the warehouse logs.';
      $this->template->content = $this->view;
    }
    $survey = $this->db
      ->select('website_id, title')
      ->from('surveys')
      ->where(array('id'=>$surveyId))
      ->get()->result_array(FALSE);
    $this->surveyTitle = $survey[0]['title'];
    $this->page_breadcrumbs[] = html::anchor('survey', 'Surveys');
    $this->page_breadcrumbs[] = html::anchor('survey/edit/'.$surveyId, $this->surveyTitle);
    $this->page_breadcrumbs[] = $this->template->title;
  }

  /**
   * Call the methods required to do the import.
   *
   * @param array $importData
   * @param int $surveyId The ID of the survey in the database we are importing into.
   * @todo Is the load of existing data scalable? Does it just load data available to
   * the website for the current survey?
   */
  public function doImport($importData, $surveyId) {
    if (isset($_SESSION['auth_user']))
      $this->userId = $_SESSION['auth_user']->id;
    else {
      global $remoteUserId;
      if (isset($remoteUserId))
        $this->userId = $remoteUserId;
      else {
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        $this->userId = ($defaultUserId ? $defaultUserId : 1);
      }
    }
    foreach($importData['smpAttrs'] as $importAttrDef) {
      $this->processSampleAttribute($importAttrDef);
    }
  }
  
  private function processSampleAttribute($importAttrDef) {
    $this->log[] = 'Processing sample attribute: '.$importAttrDef['caption'];
    // fetch possible matches based on the following SQL field matches
    $fieldsToMatch = array(
      'caption'=>'a.caption', 
      'data_type'=>'a.data_type', 
      'validation_rules'=>'a.validation_rules',  
      'multi_value'=>'a.multi_value',  
      'public'=>'a.public', 
      'system_function'=>'a.system_function'
    );
    $where = '';
    foreach ($fieldsToMatch as $field => $fieldsql) {
      if ($importAttrDef[$field]==='' || $importAttrDef[$field]===null)
        $where .= "and coalesce($fieldsql, '')='$importAttrDef[$field]' ";
      else
        $where .= "and $fieldsql='$importAttrDef[$field]' ";
    }
    $possibleMatches = $this->db->query(str_replace('{where}', $where, self::SQL_FIND_SAMPLE_ATTRS))->result_array(FALSE);
    // we now have one or more possible matching attributes. Strip out any that don't match the aggregated termlist data. 
    $existingSmpAttrs = array();
    foreach ($possibleMatches as $possibleMatch) {
      // additional checks that can't be done in SQL because aggregates don't work in SQL where clauses.  
      if ($possibleMatch['termlist_title']===$importAttrDef['termlist_title'] && $possibleMatch['terms']===$importAttrDef['terms'])
        $existingSmpAttrs[] = $possibleMatch;
    }
    $this->log[] = 'Matching attributes: '.count($existingSmpAttrs);
    if (count($existingSmpAttrs)===0)
      $this->createSampleAttr($importAttrDef);
    elseif (count($existingSmpAttrs)===1)
      $this->linkSampleAttr($importAttrDef, $existingSmpAttrs[0]);
    else
      $this->linkToOneOfSampleAttrs($importAttrDef, $existingSmpAttrs);
  }
  
  private function createSampleAttr($attrDef) {
    $sa = ORM::factory('sample_attribute');
    $array=array(
      'caption' => $attrDef['caption'],
      'data_type' => $attrDef['data_type'],
      'applies_to_location' => $attrDef['applies_to_location'],
      'validation_rules' => $attrDef['validation_rules'],
      'multi_value' => $attrDef['multi_value'],
      'public' => $attrDef['public'],
      'system_function' => $attrDef['system_function']
    );
    // Lookups need to link to or create a termlist
    if ($attrDef['data_type'] === 'L') {
      // Find termlists with the same name
      $possibleMatches = $this->db->query(
          str_replace('{where}', "t.termlist_title='$attrDef[termlist_title]' and (t.website_id=$attrDef[aw_website_id] or t.website_id is null)", 
          self::SQL_FIND_TERMLIST)
      )->result_array(FALSE);
      // Now double check that the found termlist(s) have the same set of terms we are expecting.
      $termlists = array();
      foreach ($possibleMatches as $possibleMatch) {
        if ($possibleMatch['terms'] === $attrDef['terms'])
          $termlists[] = $possibleMatch;
      }
      if (count($termlists)>=1) 
        $array['termlist_id'] = $termlists[0]['termlist_id'];
      else {
        $array['termlist_id'] = $this->createTermlist($attrDef);
        return;
      }
    }
    $sa->set_submission_data($array);
    if (!$sa->submit()) 
      $this->log[] = "Error creating sample attribute for $attrDef[caption]";
    else {
      $this->log[] = "Created attribute $attrDef[caption]";
      $this->linkSampleAttr($attrDef, $sa->as_array());
    }
  }
  
  private function createTermlist($attrDef) {
    $tl = ORM::factory('termlist');
    $tl->set_submission_data(array(
      'title' => $attrDef['termlist_title'],
      'description' => "Terms for the $attrDef[caption] attribute";
      'website_id' => $attrDef['aw_website_id']
    ));
    if (!$tl->submit()) 
      $this->log[] = "Error creating termlist $attrDef[termlist_title] for $attrDef[caption]";
    else {
      $this->log[] = "Created termlist $attrDef[termlist_title] for $attrDef[caption]";
      // now we need to create the terms required by the termlist. Split the terms string into individual terms.
      $terms = explode('**', $attrDef['terms']);
      foreach ($terms as $term) {
        // the tokens defining the term are separated by pipes. 
        $term = explode('|', $term);
        // sanitise the sort order
        $term[2] = empty($term[2]) ? 'null' : $term[2];
        $this->db->query("select insert_term('$term[0]', '$term[1]', $term[2], {$tl->id}, null);");
      }
    }
    return $tl->id;
  }
  
  private function linkSampleAttr($importAttrDef, $existingAttr) {
    $saw = ORM::factory('sample_attributes_website')->where(array('sample_attribute_id'=>$existingAttr['id'], 'restrict_to_survey_id'=>$_POST['survey_id']))->find();
    if ($saw->loaded)
      $this->log[] = 'An attribute similar to this is already link to the survey - no action taken.';
    else {
      // Need to create a link in sample_attributes_websites to link the existing attribute to the survey
      $saw->sample_attribute_id=$existingAttr['id'];
      $saw->website_id=$importAttrDef['aw_website_id'];
      $saw->restrict_to_survey_id=$_POST['survey_id'];
      $saw->validation_rules=$importAttrDef['aw_validation_rules'];
      $saw->weight=$importAttrDef['aw_weight'];
      $saw->control_type_id=$importAttrDef['aw_control_type_id'];
      $saw->default_text_value=$importAttrDef['aw_default_text_value'];
      $saw->default_float_value=$importAttrDef['aw_default_float_value'];
      $saw->default_int_value=$importAttrDef['aw_default_int_value'];
      $saw->default_date_start_value=$importAttrDef['aw_default_date_start_value'];
      $saw->default_date_end_value=$importAttrDef['aw_default_date_end_value'];
      $saw->default_date_type_value=$importAttrDef['aw_default_date_type_value'];
      $saw->form_structure_block_id=$this->getFormStructureBlockId($importAttrDef);
      $saw->created_on=date("Ymd H:i:s");
      $saw->created_by_id=$this->userId;
      if (!$saw->save()) {
        $this->log[] = "Error creating a sample attributes website record to associate $attrDef[caption].";
      }
    }
  }
  
  /**
   * Given an attribute import definition, work out if the correct form structure blocks are already available
   * and return the appropriate ID. If not already available then the form structure blocks are created.
   */
  private function getFormStructureBlockId($attrDef) {
    $this->log[] = 'Form structure block links not yet implemented';
    return null;
  }
  
  private function linkToOneOfSampleAttrs($attrDefs) {
    $this->log[] = 'Link to one of a list of sample attributes not implemented';
  }
 
  /**
   * Retrieves the data for a list of attributes associated with a given survey.
   * @param type $surveyId
   * @return array A version of the data which has been changed into structured
   * arrays of the data from the tables.
   */
  public function getSurveyAttributes($id) {
    $smpAttrs = $this->db->query(str_replace('{where}', "and aw.restrict_to_survey_id=$id", self::SQL_FETCH_ALL_SAMPLE_ATTRS))->result_array(FALSE);
    $occAttrs = $this->db->query("select 	a.id, a.caption, a.data_type, a.validation_rules, a.multi_value, a.public, a.system_function,
          aw.validation_rules as aw_validation_rules, aw.weight as aw_weight, aw.control_type_id as aw_control_type_id, 
          aw.default_text_value as aw_default_text_value, aw.default_float_value as aw_default_float_value, aw.default_int_value as aw_default_int_value, 
          aw.default_date_start_value as aw_default_date_start_value, aw.default_date_end_value as aw_default_date_end_value, aw.default_date_type_value as aw_default_date_type_value,
          fsb1.name as fsb1_name, fsb1.weight as fsb1_weight, fsb2.name as fsb2_name, fsb2.weight as fsb2_weight,
          max(t.termlist_title) as termlist_title, 
          array_to_string(array_agg((t.term || '|' || t.language_iso || '|' || coalesce(t.sort_order::varchar, '') || '|' || coalesce(tp.term::varchar, ''))::varchar order by t.sort_order, t.term), '**') as terms
        from occurrence_attributes a
        join occurrence_attributes_websites aw on aw.occurrence_attribute_id=a.id and aw.restrict_to_survey_id=$id and aw.deleted=false
        left join cache_termlists_terms t on t.termlist_id=a.termlist_id
        left join cache_termlists_terms tp on tp.id=t.parent_id
        left join form_structure_blocks fsb1 on fsb1.id=aw.form_structure_block_id and fsb1.survey_id=aw.restrict_to_survey_id
        left join form_structure_blocks fsb2 on fsb2.id=fsb1.parent_id and fsb2.survey_id=aw.restrict_to_survey_id
        where a.deleted=false
        group by a.id, a.caption, a.data_type, a.validation_rules, a.multi_value, a.public, a.system_function,
          aw.validation_rules, aw.weight, aw.control_type_id, 
          aw.default_text_value, aw.default_float_value, aw.default_int_value, 
          aw.default_date_start_value, aw.default_date_end_value, aw.default_date_type_value,
          fsb1.name, fsb1.weight, fsb2.name, fsb2.weight
        order by fsb1.weight, fsb2.weight, aw.weight")->result_array(FALSE);
    return array(
      'smpAttrs'=>$smpAttrs,
      'occAttrs'=>$occAttrs
    );
  }
  
  /**Method that adds a created by, created date, updated by, updated date to a row of data
     we are going to add/update to the database.
   * @param array $row A row of data we are adding/updating to the database.
   * @param string $tableName The name of the table we are adding the row to. We need this as the
   * attribute_websites tables don't have updated by and updated on fields.
   */
  public function setMetadata(&$row=null, $tableName=null) {
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
}
?>