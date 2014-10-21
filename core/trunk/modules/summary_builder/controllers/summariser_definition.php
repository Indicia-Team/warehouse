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

/* Stage 2
 * TODO: configurable merge taxa with same meaning.
 * TODO: sample attribute averaging. store as json in a period level record.
 * TODO: new download reports.
 * TODO: Store Weeknumber definitions in a period level record
 * TODO: comment DB columns, and functions
 */

/*
 * Proposed development path
 * 1) Currently only week based summarisation, as that is what UKBMS want. Expand to allow Month based summarisation
 * 2) Allow >1 summarisation per survey
 * 3) Add in gradual rebuild of data
 * 4) Currently deals with super/subsample with locations, as that is what UKBMS want. Expand to handle other cases.
 * 5) Amalgamation of raw data into a daily summary. Would need to store sample list.
 * 6) Make 0-1 rounding configurable
 * 7) Configurable end of year processing: Include data for overlap, include periods that overlap
*/

/*
 * Notes:
 * 1) user level summarisation for taxon is done in back end
 * 2) branch level summarisation to be done in front end (by summing up the locations)
 * 3) year level and species level summarisation (row and column totals) done by the front end
 * 4) links to be handled by front end.
 */

/**
 * Controller providing CRUD access to the surveys list.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Summariser_definition_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('summariser_definition');
    $this->columns = array(
      'title' => 'Survey title',
      'website'      => ''
    );
    $this->pagetitle = "Survey based data summariser definition";
  }
  
  protected function get_action_columns() {
  	return array(
  			array(
  					'caption'=>'Setup Summariser',
  					'url'=>'summariser_definition/edit_from_survey/{survey_id}'
  			)
  	);
  }

  public function edit_from_survey($id) {
  	if (!is_null($id) && !is_null($this->auth_filter) && !in_array($id, $this->auth_filter['values'])) {
  		$this->access_denied();
  		return;
  	}
  	$this->model = new Summariser_definition_Model(array('survey_id' => $id, 'deleted'=>'f'));
  	$values = $this->getModelValues();
  	if ( !$this->model->loaded ) {
  		$values['summariser_definition:survey_id']=$id;
  		$values['summariser_definition:period_start']='weekday=7';
  		$values['summariser_definition:period_one_contains']='Jan-01';
  	}
    $other = $this->prepareOtherViewData($values);
  	$this->setView($this->editViewName(), $this->model->caption(), array(
  	  'existing'=>$this->model->loaded,
      'values'=>$values
     ,'other_data'=>$other
    ));
  	$this->defineEditBreadcrumbs();
  }

  public function edit($id){
  	Kohana::show_404();
  }
  public function create(){
  	Kohana::show_404();
  }
  
  protected function show_submit_fail()
  {
    $page_errors=$this->model->getPageErrors();
  	if (count($page_errors)!=0) {
  		$this->session->set_flash('flash_error', implode('<br/>',$page_errors));
  	} else {
  		$this->session->set_flash('flash_error', 'The record could not be saved.');
  	}
  	$values = $this->getDefaults();
  	$values = array_merge($values, $_POST);
  	$other = $this->prepareOtherViewData($values);
  	$this->setView($this->editViewName(), $this->model->caption(), array(
  	  'existing'=>$this->model->loaded,
      'values'=>$values
     ,'other_data'=>$other
    ));
  	$this->defineEditBreadcrumbs();
  }
  
  protected function prepareOtherViewData($values)
  { 
    $survey = new Survey_Model($values['summariser_definition:survey_id']);
    $attrsRet = array(''=>'(Each occurrence has count=1)');
    
    $attrs = ORM::factory('occurrence_attribute')->
    		where(array('data_type'=>'I','public'=>'t','deleted'=>'f'))->
    		orderby('caption')->find_all();
    foreach ($attrs as $attr) 
    	$attrsRet[$attr->id] = $attr->caption.' (ID '.$attr->id.')';
    return array(
      'survey_title' => $survey->title,
      'occAttrs' => $attrsRet
    );
  }

  /**
   * Check access to a survey when editing. The survey's website must be in the list
   * of websites the user is authorised to administer.   
   */
  protected function record_authorised ($id)
  {
    if (!is_null($id) AND !is_null($this->auth_filter))
    {
      $survey = new Survey_Model($id);
      return (in_array($survey->website_id, $this->auth_filter['values']));
    }
    return true;
  }
  
  /**
   * You can only access the list of surveys if at least an editor of one website.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('editor');
  }
  
}
/* 
 * This is triggered by changes to occurrence records, not by a report.
 * How this is output is determined by the front end.
 */
?>
