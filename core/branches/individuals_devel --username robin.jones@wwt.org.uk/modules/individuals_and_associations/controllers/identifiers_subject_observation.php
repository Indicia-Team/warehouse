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
 * @package Individuals and associations
 * @subpackage Controllers
 * @author	Indicia Team
 * @link http://code.google.com/p/indicia/
 * @license http://www.gnu.org/licenses/gpl.html GPL
 */

/**
 * Controller for the Identifiers_subject_observation tab.
 *
 * @package Individuals and associations
 * @subpackage Controllers
 */
class Identifiers_subject_observation_Controller extends Gridview_Base_Controller {

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('identifiers_subject_observation');
    $this->columns = array(
      'coded_value'=>'',
      'verified_status'=>''      
    );
    $this->pagetitle = "Identifier Subject Observations";
  }

  public function page_authorised()
  {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('editor');
  }
  
  /**
  * Override the default index functionality to filter by subject_observation_id.
  */
  public function index()
  {
    if ($this->uri->total_arguments()>0) {
      $this->base_filter=array('subject_observation_id' => $this->uri->argument(1));
    }
    parent::index();
    // pass the subject_observation_id into the view, so the create button can use it to autoset
    // the subject observation of the new recprd.
    if ($this->uri->total_arguments()>0) {
      $this->view->subject_observation_id=$this->uri->argument(1);
    }
  }
  
  /**
   * Override the default return page behaviour so that after saving an identifier you
   * are returned to the identifiers_subject_observation entry which has the identifier.
   */
  protected function get_return_page() {
    if (array_key_exists('identifiers_subject_observation:subject_observation_id', $_POST)) {
      return "subject_observation/edit/".$_POST['identifiers_subject_observation:subject_observation_id']."?tab=Identifiers";
    } else {
      return $this->model->object_name;
    }
  }
  
  /**
   * Define non-standard behaviuor for the breadcrumbs, since this is accessed via a subject observation
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('subject_observation', 'Subject Observations');
    if ($this->model->id) {
      // editing an existing item
      $soId = $this->model->subject_observation_id;
    } else {
      // creating a new one so our argument is the subject obs id
      $soId = $this->uri->argument(1);
    }
    $so = ORM::factory('subject_observation', $soId);
    $this->page_breadcrumbs[] = html::anchor('subject_observation/edit/'.$so, $so->caption());
    $this->page_breadcrumbs[] = $this->model->caption();
  }
  
  /**
   *  Setup the default values to use when loading this controller to create a new image.   
   */
  protected function getDefaults() {    
    $r = parent::getDefaults();    
    if ($this->uri->method(false)=='create') {
      // subject_observation_id is passed as first argument in URL when creating. 
      $r['identifiers_subject_observation:subject_observation_id'] = $this->uri->argument(1);
    }
    if (array_key_exists('identifiers_subject_observation:id', $_POST)) {
      // some entity specific code to get the website(s)
      $r = $this->addSubjectObservation($r, $_POST['identifiers_subject_observation:subject_observation_id']);
      $r = $this->addSample($r, $_POST['subject_observation:sample_id']);
      $websiteId = $r['subject_observation:website_id'];
      $this->loadAttributes($r, array(
        'website_id'=>$websiteId,
        // get survey_id from an appropriate source.
        'restrict_to_survey_id'=>array(null, $r['sample:survey_id']),
        // other stuff here
      ));
    }
    return $r;
  }
  
  /**
   * Retrieves additional values from the model that are required by the edit form.
   * @return array List of additional values required by the form.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    if ($this->model->identifier_id)
      $r['identifier:coded_value'] = $this->model->identifier->coded_value;
    // some entity specific code to get the website(s)
    $websiteId = $this->model->subject_observation->website_id;
    // get survey_id from an appropriate source.
    $surveyId = $this->model->subject_observation->sample->survey_id;
    $this->loadAttributes($r, array(
      'website_id'=>$websiteId,
        'restrict_to_survey_id'=>array(null, $surveyId),
      // other stuff here
    ));
    return $r;      
  }
  
  /**
   * Adds sample data to the values array. 
   */
  private function addSubjectObservation($values, $id) {
    $subjectObservation = ORM::Factory('subject_observation', $id);
    $values['subject_observation:id'] = $subjectObservation->id;
    $values['subject_observation:website_id'] = $subjectObservation->website_id;
    $values['subject_observation:sample_id'] = $subjectObservation->sample_id;
    return $values;
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
    return $values;
  }
  
}

?>