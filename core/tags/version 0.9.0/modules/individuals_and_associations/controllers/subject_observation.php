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
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Controller providing CRUD access to the subject_observation data.
 *
 * @package	Groups and individuals module
 * @subpackage Controllers
 */
class Subject_observation_Controller extends Gridview_Base_Controller {
  
  public $sample_id;

  public function __construct()
  {
    parent::__construct('subject_observation');
    $this->pagetitle = 'Subject Observations';
    $this->actionColumns = array
    (
      'Edit Sub' => 'subject_observation/edit/{id}',
      'Edit Smp' => 'sample/edit/{sample_id}'
    );
    $this->columns = array
    (
      'id' => 'ID',
      'survey' => 'Survey',
      'taxa' => 'Taxa',
      'subject_type' => 'Subject type',
      'count' => 'Count',
      'short_comment' => 'Comment',
      'entered_sref' => 'Spatial Ref',
      'date_start' => 'Date'
    );
    $this->set_website_access('editor');
  }

  /**
   * Override the index controller action to add filters for the parent sample if viewing the child subject_observations.
   */
  public function index() {
    // This constructor normally has 1 argument which is the grid page. If there is a second argument
    // then it is the sample ID (should it be the parent ID?)
    $this->sample_id;
    if ($this->uri->total_arguments()>0) {
      $this->base_filter=array('sample_id' => $this->uri->argument(1));
      $this->sample_id = $this->uri->argument(1);
    }
    parent::index();
    $this->view->sample_id = $this->sample_id;
  }
  
  /**
   * Returns an array of all values from this model and its super models ready to be 
   * loaded into a form. For this controller, we need to also setup the custom attributes
   * available to display on the form.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $r['joinsTo:occurrence:id'] = 
      $this->reformatTaxaJoinsForList($r, 'occurrence', true);
    $r = $this->addSample($r, $r['subject_observation:sample_id']);
    $this->loadAttributes($r, array(
        'website_id'=>array($r['subject_observation:website_id']),
        'restrict_to_survey_id'=>array(null, $r['sample:survey_id']),
    ));
   return $r;  
  }
  
  /**
   * Load default values either when creating a sample new or reloading after a validation failure.
   * This adds the custom attributes list to the data available for the view. 
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    $r['joinsTo:occurrence:id'] = 
      $this->reformatTaxaJoinsForList($r, 'occurrence', true);
    $r = $this->addSample($r, $_POST['subject_observation:sample_id']);
    $this->loadAttributes($r, array(
        'website_id'=>array($r['subject_observation:website_id']),
        'restrict_to_survey_id'=>array(null, $r['sample:survey_id']),
    ));
    return $r;
  }
  
  /**
   * Adds sample data to the values array. 
   */
  private function addSample($values, $id) {
    $sample = ORM::Factory('sample', $id);
    $values['sample:id'] = $sample->id;
    $values['sample:date_start'] = $sample->date_start;
    $values['sample:entered_sref:no_validate'] = $sample->entered_sref;
    $values['sample:survey_id'] = $sample->survey_id;
    $values['survey:title'] = $sample->survey->title;
    $values['subject_observation:website_id'] = $sample->survey->website_id;
    $values['subject_observation:sample_id'] = $sample->id;
    return $values;
  }
  
  /**
   * Get the list of terms ready for the subject type list. 
   */
  protected function prepareOtherViewData($values)
  {    
    return array(
      'subject_type_terms' => $this->get_termlist_terms('indicia:assoc:subject_type'),  
      'count_qualifier_terms' => $this->get_termlist_terms('indicia:assoc:count_qualifier'),  
      'sample_id' => $this->sample_id,  
    );   
  }
  
  protected function reformatTaxaJoinsForList($values, $singular_table, $id_only=false) {
    // re-format values for joined taxa. These are returned suitable for checkboxes, 
    // but we put them in an array suitable for a list type control
    // as array(id = 'value', ... ) or id $id_only is true, array(id1, id2, ...)
    $join_ids = array();
    $join_keys = preg_grep('/^joinsTo:'.$singular_table.':[0-9]+$/', array_keys($values));
    foreach ($join_keys as $key) {
      $id = substr($key, strlen('joinsTo:'.$singular_table.':'));
      if ($id_only) {
        $join_ids[] = $id;
      } else {
        $name = ORM::Factory($singular_table, $id)->taxa_taxon_list->taxon->taxon;
        $join_ids[$id] = $name;
      }
    }              
    return $join_ids;      
  }
  
  public function save()
  {
    /*
    // unchecked check boxes are not in POST, so set false values.
    if (!isset($_POST['subject_observation:confidential']))
      $_POST['subject_observation:confidential']='f';
    if (!isset($_POST['subject_observation:zero_abundance']))
      $_POST['subject_observation:zero_abundance']='f';
    */
    parent::save();
  }
  
  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    $param = !empty($this->sample_id) ? '/'.$this->sample_id : '';
    $param = '/3';
    return array(
      array(
        'controller' => 'occurrence/index'.$param,
        'title' => 'Occurrences',
        'actions'=>array('edit')
      ),
      array(
        'controller' => 'identifiers_subject_observation',
        'title' => 'Identifiers',
        'actions'=>array('edit')
      ),
    );
  }

  /**
   * Check access to a subject_observation when editing. The subject_observation's website must be in the list
   * of websites the user is authorised to administer.
   */
  protected function record_authorised ($id)
  {
    if (!is_null($id) AND !is_null($this->auth_filter))
    {
      $sob = new Subject_Observation_Model($id);
      return (in_array($sob->website_id, $this->auth_filter['values']));
    }
    return true;
  }
}