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

  /**
   * @var array Holds a list of log messages describing the results of an import.
   */
  private $log=array();
  
  /**
   * @var integer The user's ID.
   */
  private $userId;
  
  /**
   * @var integer The ID of the website we are importing into.
   */
  private $website_id;
  
  /**
   * @const SQL_FETCH_ALL_SAMPLE_ATTRS Query definition which retrieves all the sample attribute details for a survey 
   * ID in preparation for export.
   */
  const SQL_FETCH_ALL_SAMPLE_ATTRS = "select a.caption, a.data_type, a.applies_to_location, a.validation_rules, a.multi_value, a.public, a.applies_to_recorder, a.system_function,
          sm.term as aw_restrict_to_sample_method_id_term, aw.validation_rules as aw_validation_rules, aw.weight as aw_weight, aw.control_type_id as aw_control_type_id, 
          aw.website_id as aw_website_id, aw.default_text_value as aw_default_text_value, aw.default_float_value as aw_default_float_value, aw.default_int_value as aw_default_int_value, 
          aw.default_date_start_value as aw_default_date_start_value, aw.default_date_end_value as aw_default_date_end_value, aw.default_date_type_value as aw_default_date_type_value,
          fsb1.name as fsb1_name, fsb1.weight as fsb1_weight, fsb2.name as fsb2_name, fsb2.weight as fsb2_weight, t.termlist_title as termlist_title, 
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
          fsb1.name, fsb1.weight, fsb2.name, fsb2.weight, t.termlist_title
        order by fsb1.weight, fsb2.weight, aw.weight";
  
  /**
   * @const SQL_FETCH_ALL_OCCURRENCE_ATTRS Query definition which retrieves all the occurrence attribute details for a survey 
   * ID in preparation for export.
   */
  const SQL_FETCH_ALL_OCCURRENCE_ATTRS = "select 	a.id, a.caption, a.data_type, a.validation_rules, a.multi_value, a.public, a.system_function,
          aw.validation_rules as aw_validation_rules, aw.weight as aw_weight, aw.control_type_id as aw_control_type_id, 
          aw.website_id as aw_website_id, aw.default_text_value as aw_default_text_value, aw.default_float_value as aw_default_float_value, aw.default_int_value as aw_default_int_value, 
          aw.default_date_start_value as aw_default_date_start_value, aw.default_date_end_value as aw_default_date_end_value, aw.default_date_type_value as aw_default_date_type_value,
          fsb1.name as fsb1_name, fsb1.weight as fsb1_weight, fsb2.name as fsb2_name, fsb2.weight as fsb2_weight, t.termlist_title as termlist_title, 
          array_to_string(array_agg((t.term || '|' || t.language_iso || '|' || coalesce(t.sort_order::varchar, '') || '|' || coalesce(tp.term::varchar, ''))::varchar order by t.sort_order, t.term), '**') as terms
        from occurrence_attributes a
        join occurrence_attributes_websites aw on aw.occurrence_attribute_id=a.id and aw.deleted=false
        left join cache_termlists_terms t on t.termlist_id=a.termlist_id
        left join cache_termlists_terms tp on tp.id=t.parent_id
        left join form_structure_blocks fsb1 on fsb1.id=aw.form_structure_block_id and fsb1.survey_id=aw.restrict_to_survey_id
        left join form_structure_blocks fsb2 on fsb2.id=fsb1.parent_id and fsb2.survey_id=aw.restrict_to_survey_id
        where a.deleted=false
        {where}
        group by a.id, a.caption, a.data_type, a.validation_rules, a.multi_value, a.public, a.system_function,
          aw.validation_rules, aw.weight, aw.control_type_id, 
          aw.website_id, aw.default_text_value, aw.default_float_value, aw.default_int_value, 
          aw.default_date_start_value, aw.default_date_end_value, aw.default_date_type_value,
          fsb1.name, fsb1.weight, fsb2.name, fsb2.weight, t.termlist_title
        order by fsb1.weight, fsb2.weight, aw.weight";

  /**
   * @const SQL_FIND_ATTRS Query definition which searches for an existing attribute which matches the 
   * definition of one being imported. Uses an array aggregation to get details of all terms which must be manually
   * tested after running the query, since PostgreSQL does not support aggregates in the where clause. The order by
   * clause puts any attributes already used by this website at the top.
   */        
  const SQL_FIND_ATTRS = "select a.id, a.caption, a.data_type, a.validation_rules, a.multi_value, a.public, a.system_function{extraFields}, 
	t.termlist_title as termlist_title, aw.website_id,
	array_to_string(array_agg((t.term || '|' || t.language_iso || '|' || coalesce(t.sort_order::varchar, '') || '|' || coalesce(tp.term::varchar, ''))::varchar order by t.sort_order, t.term), '**') as terms
from {type}_attributes a
left join cache_termlists_terms t on t.termlist_id=a.termlist_id
left join cache_termlists_terms tp on tp.id=t.parent_id
left join {type}_attributes_websites aw on aw.{type}_attribute_id=a.id and aw.deleted=false and aw.website_id={websiteId}
where a.deleted=false
and (a.public=true or aw.id is not null)
{where}
group by a.id, a.caption, a.data_type, a.validation_rules, a.multi_value, a.public, a.system_function, t.termlist_title, aw.website_id{extraFields}
order by aw.website_id is null, aw.website_id={websiteId}";

  /**
   * @const SQL_FIND_TERMLIST Query definition which searches for an existing termlist which matches the 
   * definition of one being imported. Uses an array aggregation to get details of all terms which must be manually
   * tested after running the query, since PostgreSQL does not support aggregates in the where clause.
   */ 
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
   * Controller action for the export tab content. Displays the view containing a block of 
   * exportable content as well as a textarea into which exports from elsewhere can be pasted.
   */
  public function index() {
    $this->view = new View('survey_structure_export/index');
    $this->view->surveyId=$this->uri->last_segment();
    // Get the attribute data (including termlists) associated with the survey ready to export
    $export = $this->getSurveyAttributes($this->view->surveyId);
    $this->view->export = json_encode($export);
    $this->template->content = $this->view;
  }
 
  /**
   * Controller action called when Save clicked. Perform the import when text has been pasted into the import text area.
   */
  public function save() {
    $surveyId = $_POST['survey_id'];
    $survey = $this->db
        ->select('website_id, title')
        ->from('surveys')
        ->where(array('id'=>$surveyId))
        ->get()->result_array(FALSE);
    $this->website_id=$survey[0]['website_id'];
    try {
      $importData = json_decode($_POST['import_survey_structure'], true);
      $this->doImport($importData, $_POST['survey_id']);
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
    $this->surveyTitle = $survey[0]['title'];
    $this->page_breadcrumbs[] = html::anchor('survey', 'Surveys');
    $this->page_breadcrumbs[] = html::anchor('survey/edit/'.$surveyId, $this->surveyTitle);
    $this->page_breadcrumbs[] = $this->template->title;
  }

  /**
   * Import a pasted definition of a set of custom attributes.
   *
   * @param array $importData The array definition of the attributes to import.
   * @param int $surveyId The ID of the survey in the database we are importing into.
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
      $this->processAttribute('sample', $importAttrDef, array('applies_to_location', 'applies_to_recorder'));
    }
    foreach($importData['occAttrs'] as $importAttrDef) {
      $this->processAttribute('occurrence', $importAttrDef, array());
    }
  }
  
  /**
   * Handles the import of a single occurrence or sample custom attribute.
   *
   * @param string $type occurrence or sample.
   * @param array $importAttrDef Definition of the attribute in an array, as retrieved from the imported
   * data.
   * @param array $extraFields List of non-standard fields in this attributes database table.
   */
  private function processAttribute($type, $importAttrDef, $extraFields) {
    $this->log[] = "Processing $type attribute: $importAttrDef[caption]";
    // fetch possible matches based on the following SQL field matches
    $fieldsToMatch = array(
      'caption'=>'a.caption', 
      'data_type'=>'a.data_type', 
      'validation_rules'=>'a.validation_rules',  
      'multi_value'=>'a.multi_value',  
      'public'=>'a.public', 
      'system_function'=>'a.system_function'
    );
    // Depending on the type of attribute (occurrence or sample), there might be some additional fields to match. 
    // We also need these in a string suitable for adding to the SQL select and group by clauses.
    $extras = ''; 
    foreach ($extraFields as $field) {
      $fieldsToMatch[$field] = "a.$field";
      $extras .= ", a.$field";
    }
    // build the where clause required to do the match to see if an existing attribute meets our needs
    $where = '';
    foreach ($fieldsToMatch as $field => $fieldsql) {
      if ($importAttrDef[$field]==='' || $importAttrDef[$field]===null)
        $where .= "and coalesce($fieldsql, '')='$importAttrDef[$field]' ";
      else
        $where .= "and $fieldsql='$importAttrDef[$field]' ";
    }
    
    $query = str_replace(array('{type}', '{where}', '{extraFields}', '{websiteId}'), 
        array($type, $where, $extras, $this->website_id), self::SQL_FIND_ATTRS);
    $possibleMatches = $this->db->query($query)->result_array(FALSE);
    // we now have one or more possible matching attributes. Strip out any that don't match the aggregated termlist data. 
    $existingAttrs = array();
    foreach ($possibleMatches as $possibleMatch) {
      // additional checks that can't be done in SQL because aggregates don't work in SQL where clauses.  
      if ($possibleMatch['termlist_title']===$importAttrDef['termlist_title'] && $possibleMatch['terms']===$importAttrDef['terms'])
        $existingAttrs[] = $possibleMatch;
    }
    $this->log[] = 'Matching attributes: '.count($existingAttrs);
    if (count($existingAttrs)===0)
      $this->createAttr($type, $importAttrDef, $extraFields);
    else 
      // Because the find query puts the attributes already used by this website at the top, we 
      // can use $existingAttrs[0] to link to safely.
      $this->linkAttr($type, $importAttrDef, $existingAttrs[0]);
  }
  
  /**
   * Create a sample or occurrence custom attribute.
   * 
   * @param string $type Type of custom attribute, sample or occurrence.
   * @param array $attrDef Definition of the attribute in an array, as retrieved from the imported
   * data.
   * @param array $extraFields List of non-standard fields in this attributes database table.
   * @return type
   */
  private function createAttr($type, $attrDef, $extraFields) {
    $array=array(
      'caption' => $attrDef['caption'],
      'data_type' => $attrDef['data_type'],
      'validation_rules' => $attrDef['validation_rules'],
      'multi_value' => $attrDef['multi_value'],
      'public' => $attrDef['public'],
      'system_function' => $attrDef['system_function']
    );
    // Depending on if it is an occurrence or sample attribute there might be extra fields to copy
    foreach ($extraFields as $field) 
      $array[$field] = $attrDef[$field];
    // Lookups need to link to or create a termlist
    if ($attrDef['data_type'] === 'L') {
      // Find termlists with the same name that are available to this website?
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
      // Do we have any matching termlists?
      if (count($termlists)>=1) 
        // use the existing termlist to provide terms for the new custom attribute
        $array['termlist_id'] = $termlists[0]['termlist_id'];
      else {
        $this->log[] = "Creating new termlist.";
        // create a new termlist for the new custom attribute
        $array['termlist_id'] = $this->createTermlist($attrDef);
      }
    }
    $a = ORM::factory("{$type}_attribute");
    $a->set_submission_data($array);
    if (!$a->submit()) 
      $this->log[] = "Error creating $type attribute for $attrDef[caption]";
    else {
      $this->log[] = "Created $type attribute $attrDef[caption]";
      $this->linkAttr($type, $attrDef, $a->as_array());
    }
  }
  
  /**
   * Create a new termlist and populate it with the terms required for a new lookup 
   * custom attribute.
   * 
   * @param array $attrDef Definition of the attribute as defined by the imported data.
   * @return integer Returns the database ID of the created termist.
   */
  private function createTermlist($attrDef) {
    $tl = ORM::factory('termlist');
    $tl->set_submission_data(array(
      'title' => $attrDef['termlist_title'],
      'description' => "Terms for the $attrDef[caption] attribute",
      'website_id' => $attrDef['aw_website_id']
    ));
    if (!$tl->submit()) 
      $this->log[] = "Error creating termlist $attrDef[termlist_title] for $attrDef[caption]";
    else {
      // now we need to create the terms required by the termlist. Split the terms string into individual terms.
      $terms = explode('**', $attrDef['terms']);
      foreach ($terms as $term) {
        // the tokens defining the term are separated by pipes. 
        $term = explode('|', $term);
        // sanitise the sort order
        $term[2] = empty($term[2]) ? 'null' : $term[2];
        $this->db->query("select insert_term('$term[0]', '$term[1]', $term[2], {$tl->id}, null);");
      }
      // Now re-iterate through the terms and set the term parents
      foreach ($terms as $term) {
        // the tokens defining the term are separated by pipes. 
        $term = explode('|', $term);
        if (!empty($term[3])) {
          $this->db->query("update termlists_terms tlt set parent_id=tltp.id 
            from terms t, termlists_terms tltp
            join terms tp on tp.id=tltp.term_id and tp.deleted=false and tp.term='$term[2]'
            where tlt.termlist_id={tl->id} and t.id=tlt.term_id and t.deleted=false and t.term='$term[0]' 
            and tltp.termlist_id=tlt.termlist_id and tltp.deleted=false");
        }
      }
      $this->log[] = "Created termlist $attrDef[termlist_title]";
    }
    return $tl->id;
  }
  
  /**
   * Link an attribute to the survey by checking a sample_attributes_websites or occurrence_attributes_websites
   * record exists and if not then creates it.
   * 
   * @param string $type Type of attribute we are working on, occurrence or sample.
   * @param array $attrDef Definition of the attribute as defined by the imported data.
   * @param array $existingAttr The array definition of the attribute to link, which must 
   * already exist.
   */
  private function linkAttr($type, $importAttrDef, $existingAttr) {
    $aw = ORM::factory("{$type}_attributes_website")->where(array("{$type}_attribute_id"=>$existingAttr['id'], 'restrict_to_survey_id'=>$_POST['survey_id']))->find();
    if ($aw->loaded)
      $this->log[] = 'An attribute similar to this is already link to the survey - no action taken.';
    else {
      // Need to create a link in sample_attributes_websites to link the existing attribute to the survey
      $fkName = "{$type}_attribute_id";
      $aw->$fkName=$existingAttr['id'];
      $aw->website_id=$importAttrDef['aw_website_id'];
      $aw->restrict_to_survey_id=$_POST['survey_id'];
      $aw->validation_rules=$importAttrDef['aw_validation_rules'];
      $aw->weight=$importAttrDef['aw_weight'];
      $aw->control_type_id=$importAttrDef['aw_control_type_id'];
      $aw->default_text_value=$importAttrDef['aw_default_text_value'];
      $aw->default_float_value=$importAttrDef['aw_default_float_value'];
      $aw->default_int_value=$importAttrDef['aw_default_int_value'];
      $aw->default_date_start_value=$importAttrDef['aw_default_date_start_value'];
      $aw->default_date_end_value=$importAttrDef['aw_default_date_end_value'];
      $aw->default_date_type_value=$importAttrDef['aw_default_date_type_value'];
      $aw->form_structure_block_id=$this->getFormStructureBlockId($type, $importAttrDef);
      $aw->created_on=date("Ymd H:i:s");
      $aw->created_by_id=$this->userId;
      if ($type==='sample' && !empty($importAttrDef['aw_restrict_to_sample_method_id_term'])) {
        $sm = $this->db->query("select id from cache_termlists_terms "
            . "where termlist_title='Sample methods' and term='$importAttrDef[aw_restrict_to_sample_method_id_term]'")->result_array(FALSE);
        if (count($sm)===0) {
          $this->db->query("select insert_term('$importAttrDef[aw_restrict_to_sample_method_id_term]', 'eng', null, null, 'indicia:sample_methods');");
        } else {
          $aw->restrict_to_sample_method_id = $sm[0]['id'];
        }
          
      }
      if (!$aw->save()) {
        $this->log[] = "Error creating $type attributes website record to associate $attrDef[caption].";
      }
    }
  }
  
  /**
   * Given an attribute import definition, work out if the correct form structure blocks are already available
   * and return the appropriate ID. If not already available then the form structure blocks are created.
   * 
   * @todo Should probably use the database agnostic query builder here.
   * @param string $type Type of attribute we are working on, occurrence or sample.
   * @param array $attrDef Definition of the attribute as defined by the imported data.
   * @return integer The form structure block ID to link this attribute to.
   */
  private function getFormStructureBlockId($type, $attrDef) {
    $type = ($type==='sample') ? 'S' : 'O';
    $query = "select fsb1.id
        from form_structure_blocks fsb1
        left join form_structure_blocks fsb2 on fsb2.id=fsb1.parent_id
        where fsb1.name='$attrDef[fsb1_name]' and fsb1.survey_id=$_POST[survey_id] and fsb1.type='$type'\n";
    if (empty($attrDef['fsb2_name']))
      $query .= 'and fsb2.id is null';
    else
      $query .= "and fsb2.name='$attrDef[fsb2_name]' and fsb2.survey_id=$_POST[survey_id] and fsb2.type='$type'";
    $matches = $this->db->query($query)->result_array(FALSE);
    if (count($matches))
      // Matching form structure block exists
      return $matches[0]['id'];
    else {
      // Need to create the form structure block. 
      $parentId=false;
      if (!empty($attrDef['fsb2_name'])) {
        // If we have a parent block, find an existing one or create a new one as appropriate
        $matches = $this->db->query("select id from form_structure_blocks
            where name='$attrDef[fsb2_name]' and survey_id=$_POST[survey_id] and parent_id is null")->result_array(FALSE);
        if (count($matches))
          $parent_id=$matches[0]['id'];
        else {
          $parent = ORM::factory('form_structure_block');
          $parent->name=$attrDef['fsb2_name'];
          $parent->survey_id=$_POST['survey_id'];
          $parent->type=$type;
          $parent->weight=$attrDef['fsb2_weight'];
          $parent->save();
          $parent_id=$parent->id;
        }
      }
      // now create the child
      $child = ORM::factory('form_structure_block');
      $child->name=$attrDef['fsb1_name'];
      $child->survey_id=$_POST['survey_id'];
      $child->type=$type;
      $child->weight=$attrDef['fsb1_weight'];
      if ($parent_id)
        $child->parent_id=$parent_id;
      $child->save();
      return $child->id;
    }
  }
  
  /**
   * Retrieves the data for a list of attributes associated with a given survey.
   * 
   * @param type $surveyId
   * @return array A version of the data which has been changed into structured
   * arrays of the data from the tables.
   */
  public function getSurveyAttributes($id) {
    $smpAttrs = $this->db->query(str_replace('{where}', "and aw.restrict_to_survey_id=$id", self::SQL_FETCH_ALL_SAMPLE_ATTRS))->result_array(FALSE);
    $occAttrs = $this->db->query(str_replace('{where}', "and aw.restrict_to_survey_id=$id", self::SQL_FETCH_ALL_OCCURRENCE_ATTRS))->result_array(FALSE);
    return array(
      'smpAttrs'=>$smpAttrs,
      'occAttrs'=>$occAttrs
    );
  }
  
}