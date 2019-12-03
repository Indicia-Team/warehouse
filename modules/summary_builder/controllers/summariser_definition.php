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
 * @link 	https://github.com/indicia-team/warehouse/
 */

/**
 * Controller providing CRUD access to the summariser_definition list.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Summariser_definition_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('summariser_definition');
    $this->columns = array(
      'title' => 'Survey title',
      'website' => ''
    );
    $this->pagetitle = "Survey based data summariser definition";
  }

  protected function get_action_columns() {
    return array(
        array(
          'caption' => 'Setup Summariser',
          'url' => 'summariser_definition/edit/{id}',
        )
    );
  }

  public function edit($id = NULL) {
    if ( is_null($id) ) {
      $this->access_denied();
      return;
    }
    $this->model = new Summariser_definition_Model(array('id' => $id, 'deleted'=>'f'));
    if ( !$this->model->loaded ) {
      $this->access_denied();
      return;
    }
    $survey = new Survey_Model(array('id' => $this->model->survey_id, 'deleted'=>'f'));
    if ( !$this->model->loaded ) {
      $this->access_denied();
      return;
    }
    if ( !is_null($this->auth_filter ) && !in_array($survey->website_id, $this->auth_filter['values']) ) {
      $this->access_denied();
      return;
    }

    $values = $this->getModelValues();
    $other = $this->prepareOtherViewData($values);
    $this->setView($this->editViewName(), $this->model->caption(), array(
      'existing' => TRUE,
      'values' => $values,
      'other_data' => $other
    ));
    $this->defineEditBreadcrumbs();
  }

  public function create(){
      $this->model = new Summariser_definition_Model();
      $values = $this->getModelValues();
      $values['summariser_definition:period_start']='weekday=7';
      $values['summariser_definition:period_one_contains']='Jan-01';
      $other = $this->prepareOtherViewData($values);
      if(count($other['surveys'])<1) {
        $this->access_denied();
        return;
      }
      $this->setView($this->editViewName(), $this->model->caption(), array(
          'existing'=>FALSE,
          'values'=>$values,
          'other_data'=>$other
      ));
      $this->defineEditBreadcrumbs();
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

  protected function prepareOtherViewData(array $values) {
    $survey = new Survey_Model($values['summariser_definition:survey_id']);
    $attrsRet = array(''=>'(Each occurrence has count=1)');

    $models = ORM::factory('occurrence_attributes_website')->
                where(array('deleted'=>'f'));
    if(!empty($values['summariser_definition:survey_id'])) {
      $models = $models->where(array('restrict_to_survey_id'=>$values['summariser_definition:survey_id']));
    }
    $models = $models->find_all();
    if(count($models)>0){
      $attrIds = array();
      foreach ($models as $model)
        $attrIds[] = $model->occurrence_attribute_id;
      $attrIds = array_unique($attrIds);
      $attrs = ORM::factory('occurrence_attribute')->
                where('deleted','f')->in('data_type',array('I','F'))->in('id',$attrIds)->
                orderby('caption')->find_all();
      if(count($attrs)>0)
        foreach ($attrs as $attr)
          $attrsRet[$attr->id] = $attr->caption.' (ID '.$attr->id.')';
    }

    $surveys = array();
    $surveyModels = ORM::factory('survey')->where(array('deleted'=>'f'))->find_all();
    foreach ($surveyModels as $model) {
      if (is_null($this->auth_filter) || in_array($model->website_id, $this->auth_filter['values'])) {
        $surveys["".$model->id] = $model->title;
      }
    }
    $existingModels = ORM::factory('summariser_definition')->where(array('deleted'=>'f'))->find_all();
    foreach ($existingModels as $model) {
      if(array_key_exists("".$model->id, $surveys)) {
        unset($surveys["".$model->survey_id]);
      }
    }
    asort($surveys);
    return array(
      'survey_title' => $survey->title,
      'occAttrs' => $attrsRet,
      'surveys' => $surveys,
    );
  }

  /**
   * Check access to a survey when editing. The survey's website must be in the list
   * of websites the user is authorised to administer.
   */
  protected function record_authorised ($id = NULL)
  {
    $model = new Summariser_definition_Model($id);
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
