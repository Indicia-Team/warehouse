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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Controller providing CRUD access to the surveys list.
 */
class Survey_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('survey');
    $this->columns = array(
      'id'          => '',
      'title'       => '',
      'description' => '',
      'website'     => ''
    );
    $this->pagetitle = "Survey datasets";
    $this->set_website_access('admin');
  }

  public function fields($id) {
    $this->view = new View('survey/fields');
    $survey = ORM::factory('survey', $id);
    $this->view->fields = array_merge(
      ORM::factory('sample')->getSubmittableFields(TRUE, FALSE,['website_id' => $survey->website_id, 'survey_id' => $id]),
      ORM::factory('occurrence')->getSubmittableFields(TRUE, FALSE, ['website_id' => $survey->website_id, 'survey_id' => $id])
    );
    $this->view->requiredFields = array_merge(
      ORM::factory('sample')->getRequiredFields(TRUE, ['website_id' => $survey->website_id, 'survey_id' => $id]),
      ORM::factory('occurrence')->getRequiredFields(TRUE, ['website_id' => $survey->website_id, 'survey_id' => $id])
    );
    $this->template->content = $this->view;
  }

  protected function prepareOtherViewData(array $values) {
    $websites = ORM::factory('website');
    if (!empty($values['survey:parent_id']))
      // parent list already has a link to a website, so we can't change it
      $websites = $websites->in('id', ORM::factory('survey', $values['survey:parent_id'])->website_id);
    elseif (!empty($values['survey:website_id']))
      // can't change website for existing survey
      $websites = $websites->where('id', $values['survey:website_id']);
    elseif (!$this->auth->logged_in('CoreAdmin') && $this->auth_filter['field'] === 'website_id')
      $websites = $websites->in('id',$this->auth_filter['values']);
    $arr = array();
    foreach ($websites->where('deleted','false')->orderby('title','asc')->find_all() as $website)
      $arr[$website->id] = $website->title;

    $otherData = array(
      'websites' => $arr
    );

    $otherData['taxon_restrictions'] = [];
    $masterListId = warehouse::getMasterTaxonListId();
    if ($masterListId && array_key_exists('survey:id', $values)) {

      $tmIdVals = $this->db
        ->select('s.auto_accept_taxa_filters')
        ->from('surveys AS s')
        ->where([
          's.id' => $values['survey:id'],
        ])
        ->get()->result();

      $valsCSV = trim($tmIdVals[0]->auto_accept_taxa_filters ?? '', "{}");

      $ttlIds = $this->db
          ->select('id')
          ->from('cache_taxa_taxon_lists as cttl')
          ->in('taxon_meaning_id', explode(",", $valsCSV))
          ->where([
            'cttl.preferred' => true
          ])
          ->get()->result();

      foreach ($ttlIds as $ttlId) {
        array_push($otherData['taxon_restrictions'], array("taxa_taxon_list_id" => $ttlId->id));
      }
    }
    return $otherData;
  }

  /**
   * Override the default action columns for a grid - just an edit link - to
   * add a link to the attributes list for othe survey.
   */
  protected function get_action_columns() {
    return array(
      array(
        'caption'=>'edit',
        'url'=>$this->controllerpath."/edit/{id}"
      ),
      array(
        'caption'=>'setup attributes',
        'url'=>"attribute_by_survey/{id}?type=sample"
      )
    );
  }

    /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return array(
      array(
        'controller' => 'survey_comment',
        'title' => 'Comments',
        'actions'=>array('edit')
      ), array(
        'controller' => 'survey_medium',
        'title' => 'Media Files',
        'views'=>'survey',
        'actions'=>array('edit')
      ),
      array(
        'controller' => 'survey/fields',
        'title' => 'Database fields',
        'actions'=>array('edit')
      )
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

  /**
   * Retrieves additional values from the model that are required by the edit form.
   * @return array List of additional values required by the form.
   */
  protected function getModelValues() {
  	$r = parent::getModelValues();
  	if ($this->model->parent_id)
  		$r['parent:title'] = $this->model->parent->title;
    $r['website_id']=$this->model->website_id;
    $this->loadAttributes($r, array(
        'website_id'=>array($r['website_id'])
    ));
    // Convert the JSON in the db for core field additional validation rules
    // into default values for the UI form.
    if (!empty($this->model->core_validation_rules)) {
      $ruleTables = json_decode($this->model->core_validation_rules, TRUE);
      foreach ($ruleTables as $table => $rules) {
        foreach ($rules as $field => $rule) {
          if (strpos($rule, 'required') !== FALSE) {
            $r["{$table}-{$field}-required"] = '1';
          }
        }
      }
    }
  	return $r;
  }

  /**
   * Load default values either when creating a survey new or reloading after a validation failure.
   * This adds the custome attributes list to the data available for the view.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    $r['website_id'] = $this->model->website_id;
    $this->loadAttributes($r, array(
      'website_id'=>array($r['website_id'])
    ));
    return $r;
  }

  /**
   * Override save method.
   *
   * Translate validation rule field checkboxes on edit form into the JSON
   * core_validation_rules field value.
   */
  public function save() {
    $rules = [];
    foreach ($_POST as $field => $value) {
      if (preg_match('/^(?P<table>(occurrence|sample))\-(?P<field>.+)\-(?P<rule>.+)$/', $field, $matches)) {
        if (!isset($rules[$matches['table']])) {
          $rules[$matches['table']] = [];
        }
        // If checkbox checked, then set the rule.
        if ($value === '1') {
          $rules[$matches['table']][$matches['field']] = $matches['rule'];
        }
      }
    }
    $_POST['survey:core_validation_rules'] = json_encode($rules);
    parent::save();
  }

}