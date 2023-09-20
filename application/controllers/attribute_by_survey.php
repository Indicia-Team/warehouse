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
 * Controller providing the ability to configure the list of attributes joined to a survey.
 */
class Attribute_By_Survey_Controller extends Indicia_Controller {
  private $_survey = NULL;
  private $_website_id = NULL;
  private $_survey_id = NULL;

  /**
   * Attribute type, e.g. occurrence, survey.
   *
   * @var string
   */
  public $type;

  public function __construct() {
    parent::__construct();
    if (!is_numeric($this->uri->last_segment()))
      throw new Exception('Page cannot be accessed without a survey filter');
    if (!isset($_GET['type']))
      throw new Exception('Page cannot be accessed without a type parameter');
    if ($_GET['type'] !== 'sample' && $_GET['type'] !== 'occurrence' && $_GET['type'] !== 'location')
      throw new Exception('Type parameter in URL is invalid');
    $this->type = $_GET['type'];
    $this->pagetitle = 'Attributes for a survey';
    $this->get_auth();
    $this->model = ORM::factory($this->type . '_attributes_website');
  }

  public function index() {
    // Get the survey id from the segments in the URI.
    $segments = $this->uri->segment_array();
    $this->_survey_id = $segments[2];
    $this->pagetitle = 'Attributes for ' . $this->getSurvey()->title;
    $this->page_breadcrumbs[] = html::anchor('survey', 'Survey datasets');
    $this->page_breadcrumbs[] = $this->pagetitle;
    $this->template->content = new View('attribute_by_survey/index');
    $this->template->title = $this->pagetitle;
    $filter = array('survey_id' => $this->_survey_id);
    $top_blocks = ORM::factory('form_structure_block')
      ->where('parent_id', NULL)
      ->where('type', strtoupper(substr($this->type, 0, 1)))
      ->where($filter)
      ->orderby('weight', 'ASC')
      ->find_all();
    $this->template->content->top_blocks = $top_blocks;
    $this->template->content->filter = $filter;
    $this->template->content->controlfilter = ['restrict_to_survey_id' => $this->_survey_id];
    // Provide a list of publicly available attributes so existing ones can be added.
    $attrs = ORM::factory($this->type . '_attribute')
      ->where(array('public' => 't', 'deleted' => 'f'))
      ->orderby('caption')
      ->find_all();
    $this->template->content->existingAttrs = $attrs;
  }

  public function edit($id) {
    $segments = $this->uri->segment_array();
    $m = ORM::factory($_GET['type'] . '_attributes_website', $segments[3]);
    $this->_website_id = $m->website_id;
    return parent::edit($id);
  }

  /**
   * Handle the layout_update action, which uses $_POST data to find a list of commands
   * for re-ordering the controls.
   */
  public function layout_update() {
    // Get the survey id from the segments in the URI.
    $segments = $this->uri->segment_array();
    $this->_survey_id = $segments[3];
    $structure = json_decode($_POST['layout_updates'], TRUE);
    $websiteId = ORM::Factory('survey', $this->_survey_id)->website_id;
    $this->saveBlockList($structure['blocks'], NULL, $websiteId);
    $this->saveControlList($structure['controls'], NULL, $websiteId);
    $this->session->set_flash('flash_info', "The form layout changes have been saved.");
    url::redirect("attribute_by_survey/$this->_survey_id?type=$this->type");
  }

  private function saveBlockList($list, $blockId, $websiteId) {
    $weight = 0;
    foreach ($list as $block) {
      $changed = FALSE;
      if (substr($block['id'], 0, 10) === 'new-block-') {
        $model = ORM::factory('form_structure_block');
        $model->name = $block['name'];
        $model->survey_id = $this->_survey_id;
        $model->weight = $weight;
        $model->type = strtoupper(substr($_GET['type'], 0, 1));
        $model->parent_id = $blockId;
        $changed = TRUE;
      }
      elseif (substr($block['id'], 0, 6) == 'block-') {
        $id = str_replace('block-', '', $block['id']);
        $model = ORM::factory('form_structure_block', $id);
        if ($model->weight != $weight || $model->parent_id != $blockId || $model->name != $block['name']) {
          $model->parent_id = $blockId;
          $model->weight = $weight;
          $model->name = $block['name'];
          $changed = TRUE;
        }
      }
      else {
        continue;
      }
      if (isset($block['deleted']) && $block['deleted']) {
        // Deleting, so existing blocks must be removed.
        if (substr($block['id'], 0, 6) === 'block-') {
          $model->delete();
        }
        $id = NULL;
      }
      elseif ($changed) {
        $model->save();
        $id = $model->id;
      }
      if (isset($block['blocks'])) {
        $this->saveBlockList($block['blocks'], $model->id, $websiteId);
      }
      $this->saveControlList($block['controls'], $id, $websiteId);
      $weight++;
    }
  }

  private function saveControlList($list, $blockId, $websiteId) {
    $weight = 0;
    foreach ($list as $control) {
      $changed = FALSE;
      if (substr($control['id'], 0, 8) === 'control-') {
        $ctrlId = str_replace('control-', '', $control['id']);
        $model = ORM::factory($_GET['type'] . '_attributes_website', $ctrlId);
      }
      elseif (substr($control['id'], 0, 10) === 'attribute-') {
        $attrId = str_replace('attribute-', '', $control['id']);
        // Get model for a new record.
        $model = ORM::factory($_GET['type'] . '_attributes_website');
        $attrVar = $this->type . '_attribute_id';
        // Link the model to the existing attribute we have the ID for.
        $model->$attrVar = $attrId;
        $model->restrict_to_survey_id = $this->_survey_id;
        $model->website_id = $websiteId;
        $changed = TRUE;
      }
      else {
        continue;
      }
      if ($model->weight != $weight) {
        $model->weight = $weight;
        $changed = TRUE;
      }
      if ($model->form_structure_block_id != $blockId) {
        $model->form_structure_block_id = $blockId;
        $changed = TRUE;
      }
      $weight++;
      if (isset($control['deleted']) && $control['deleted']) {
        // Deleting, so existing control must be removed.
        if (substr($control['id'], 0, 8) == 'control-') {
          $model->deleted = 't';
          $changed = TRUE;
        }
        $id = NULL;
      }
      if ($changed) {
        $model->set_metadata();
        $model->save();
        if (count($model->getAllErrors()) !== 0) {
          throw new Exception(kohana::debug($model->getAllErrors()));
        }
      }
    }
  }

  /**
   * Retrieve the list of websites the user has access to. The list is then stored in
   * $this->auth_filter. Also checks if the user is core admin.
   */
  protected function get_auth() {
    // If not logged in as a Core admin, restrict access to available websites.
    if (!$this->auth->logged_in('CoreAdmin')) {
      $site_role = (new Site_role_Model('Admin'));
      $websites = ORM::factory('users_website')
        ->where(['user_id' => $_SESSION['auth_user']->id, 'site_role_id' => $site_role->id])
        ->find_all();
      $website_id_values = array();
      foreach ($websites as $website) {
        $website_id_values[] = $website->website_id;
      }
      $website_id_values[] = NULL;
      $this->auth_filter = array('field' => 'website_id', 'values' => $website_id_values);
    }
    else {
      $this->auth_filter = NULL;
    }
  }

  /**
   * Returns the name for the edit view, since all *_attribute_websites models share the same code.
   */
  protected function editViewName() {
    return "attribute_by_survey/attribute_by_survey_edit";
  }

  /**
   * Setup the values to be loaded into the edit view. For this class, we need to explode the
   * items out of the validation_rules field, which our base class can do.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $this->model->populate_validation_rules();
    return $r;
  }

  /**
   * Load additional data required by the edit view.
   */
  protected function prepareOtherViewData(array $values) {
    $survey = ORM::Factory('survey', $values[$this->type . '_attributes_website:restrict_to_survey_id']);
    $attr = ORM::Factory($_GET['type'] . '_attribute', $values[$this->type . '_attributes_website:' . $this->type . '_attribute_id']);
    $controlTypes = $this->db
      ->select('id, control')
      ->from('control_types')
      ->where('for_data_type', $attr->data_type)
      ->get();
    $otherData = array(
      'name' => $attr->caption,
      'survey' => $survey->title,
      'controlTypes' => $controlTypes,
    );
    if ($_GET['type'] === 'occurrence' || $_GET['type'] === 'sample') {
      $sexStageSysFuncs = ['sex', 'stage', 'sex_stage'];
      // Output a grid attribute for restricting this attribute according to a
      // sex/stage term. We don't want this option for the actual sex/stage
      // attributes though and to do this we need to know the survey ID so
      // we can retrieve an appropriate list of attributes to pick from.
      if (!empty($values["$_GET[type]_attributes_website:restrict_to_survey_id"])
          && (
            $_GET['type'] !== 'occurrence'
            || !in_array($this->model->occurrence_attribute->system_function, $sexStageSysFuncs)
          )) {
        $attrs = $this->db
          ->select('a.id')
          ->from('occurrence_attributes as a')
          ->join('occurrence_attributes_websites as aw', 'aw.occurrence_attribute_id', 'a.id')
          ->join('occurrence_attribute_taxon_restrictions as atr', 'atr.occurrence_attributes_website_id', 'aw.id', 'LEFT')
          ->in('a.system_function', $sexStageSysFuncs)
          ->where([
            'a.data_type' => 'L',
            'a.deleted' => 'f',
            'aw.deleted' => 'f',
            'aw.restrict_to_survey_id' => $values["$_GET[type]_attributes_website:restrict_to_survey_id"],
            'atr.id' => NULL,
          ])
          ->get();
        // Convert the sex stage attribute list to a simple array of IDs.
        $sexStageOccAttrs = [];
        foreach ($attrs as $attr) {
          $sexStageOccAttrs[] = $attr->id;
        }
        $otherData['sexStageOccAttrs'] = $sexStageOccAttrs;
      }
      else {
        // Otherwise, don't want any extra attrs.
        $otherData['sexStageOccAttrs'] = [];
      }
      // If linking to taxa for an existing sample or occurrence attribute, we
      // need a caption to display.
      $masterListId = warehouse::getMasterTaxonListId();
      if ($masterListId) {
        $otherData['taxon_restrictions'] = $this->db
          ->select('t.id as taxa_taxon_list_id, tr.restrict_to_taxon_meaning_id, tr.restrict_to_stage_term_meaning_id, stage.id as restrict_to_stage_termlists_term_id')
          ->from('cache_taxa_taxon_lists AS t')
          ->join("$_GET[type]_attribute_taxon_restrictions AS tr", 'tr.restrict_to_taxon_meaning_id', 't.taxon_meaning_id')
          ->join('cache_termlists_terms AS stage', [
            'stage.meaning_id' => 'tr.restrict_to_stage_term_meaning_id',
            'stage.preferred' => TRUE,
          ], NULL, 'LEFT')
          ->where([
            "tr.$_GET[type]_attributes_website_id" => $values["$_GET[type]_attributes_website:id"],
            't.preferred' => 't',
            't.taxon_list_id' => $masterListId,
            'tr.deleted' => 'f',
          ])
          ->get()->result_array(FALSE);
      }
    }
    return $otherData;
  }

  public function save() {
    // Build the validation_rules field from the set of controls that are associated with it.
    $rules = [];
    $ruleNames = ([
      'required',
      'alpha',
      'email',
      'url',
      'alpha_numeric',
      'numeric',
      'standard_text',
      'date_in_past',
      'time',
      'digit',
      'integer',
    ]);
    foreach ($ruleNames as $rule) {
      if (array_key_exists('valid_' . $rule, $_POST) && $_POST['valid_' . $rule] == 1) {
        array_push($rules, $rule);
      }
    }
    if (array_key_exists('valid_length', $_POST) && $_POST['valid_length']==1)   $rules[] = 'length['.$_POST['valid_length_min'].','.$_POST['valid_length_max'].']';
    if (array_key_exists('valid_decimal', $_POST) && $_POST['valid_decimal']==1) $rules[] = 'decimal['.$_POST['valid_dec_format'].']';
    if (array_key_exists('valid_regex', $_POST) && $_POST['valid_regex']==1)     $rules[] = 'regex['.$_POST['valid_regex_format'].']';
    if (array_key_exists('valid_min', $_POST) && $_POST['valid_min']==1)         $rules[] = 'minimum['.$_POST['valid_min_value'].']';
    if (array_key_exists('valid_max', $_POST) && $_POST['valid_max']==1)         $rules[] = 'maximum['.$_POST['valid_max_value'].']';

    $_POST['validation_rules'] = implode("\r\n", $rules);

    parent::save();
  }

  protected function get_return_page() {
    $surveyPostKey = $this->type.'_attributes_website:restrict_to_survey_id';
    if (isset($_POST[$surveyPostKey])) {
      return 'attribute_by_survey/'.$_POST[$surveyPostKey].'?type='.$this->type;
    } else {
      // If $_POST data not available, then just return to the survey list. Shouldn't really happen.
      return 'survey';
    }
  }

  /**
   * Set the edit page breadcrumbs to cope with the fact this controller handles all *_attributes_website models.
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('survey', 'Survey datasets');
    $survey = ORM::Factory('survey', $this->model->restrict_to_survey_id);
    $this->page_breadcrumbs[] = html::anchor('/attribute_by_survey/'.$this->model->restrict_to_survey_id.'?type='.$this->type, 'Attributes for '.$survey->title);
    $this->page_breadcrumbs[] = $this->model->caption();
  }

  /**
   * Prevent users accessing other surveys if they are not core admin.
   * @return boolean True if access granted.
   */
  protected function page_authorised() {
    if (isset($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
      if (!$this->_website_id) {
        $survey = $this->getSurvey();
        $this->_website_id = $survey->website_id;
      }
      return in_array($this->_website_id, $this->auth_filter['values']);
    } else
      return true;
  }

  /**
   * Lazy loading of the survey ORM object. Only want to do this once.
   */
  protected function getSurvey() {
    if ($this->_survey === NULL) {
      $this->_survey = ORM::factory('survey', $this->_survey_id);
    }
    return $this->_survey;
  }


}