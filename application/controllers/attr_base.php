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
 * Custom attribute controller base class.
 *
 * Base class for controllers which provide CRUD access to the lists of custom attributes
 * associated with locations, occurrences, taxa_taxon_list or sample entities.
 */
abstract class Attr_Base_Controller extends Gridview_Base_Controller {

  /**
   * Entity name to prefix to *_attribute or *_attribute_values.
   *
   * @var string
   */
  protected $prefix;

  /**
   * View for UI to define assocation from attribute to container, e.g. survey.
   *
   * @var View
   */
  protected $associationsView;

  /**
   * Controller constructor.
   *
   * Initialises grid columns.
   */
  public function __construct() {
    parent::__construct($this->prefix . '_attribute', 'custom_attribute/index');
    $this->pagetitle = ucwords(str_replace('_', ' ', $this->prefix)) . ' Attributes';
    $this->columns = array(
      'id' => '',
      'website' => '',
      'survey' => '',
      'caption' => '',
      'data_type' => 'Data type',
    );
    if (method_exists($this->model, 'get_system_functions')) {
      $this->columns['function'] = '';
    }
    $this->set_website_access('admin');
  }

  /**
   * Returns the shared view for all custom attribute edits.
   */
  protected function editViewName() {
    $this->associationsView = new View('templates/attribute_associations_website_survey');
    return 'custom_attribute/custom_attribute_edit';
  }

  /**
   * Additional information for the edit view.
   *
   * Returns some addition information required by the edit view, which is not
   * associated with a particular record.
   */
  protected function prepareOtherViewData(array $values) {
    $query = $this->db->select('termlists.id, termlists.title, websites.title as website_title')
      ->from('termlists')
      ->join('websites', 'websites.id', 'termlists.website_id', 'LEFT')
      ->orderby('termlists.title')
      ->where('termlists.deleted', 'f');
    if (!is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
      $query->in('termlists.website_id', $this->auth_filter['values']);
    }
    $termlistArray = [];
    foreach ($query->get()->result() as $list) {
      $website = $list->website_title ? ", Website=$list->website_title" : '';
      $termlistArray[$list->id] = "$list->title (ID=$list->id$website)";
    }
    return array(
      'name' => ucwords(str_replace('_', ' ', $this->prefix)),
      'controllerpath' => $this->controllerpath,
      'webrec_entity' => $this->prefix . '_attributes_website',
      'webrec_key' => $this->prefix . '_attribute_id',
      'publicFieldName' => 'Available to other Websites',
      'reporting_category_terms' => $this->get_termlist_terms("indicia:attr_reporting_category_$this->prefix"),
      'source_terms' => $this->get_termlist_terms('indicia:attribute_sources'),
      'termlists' => $termlistArray,
    );
  }

  /**
   * Setup the values to be loaded into the edit view.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    // Can the user edit the actual attribute? If not they can still assign it
    // to their surveys.
    if ($this->auth->logged_in('CoreAdmin')) {
      $r['metaFields:disabled_input'] = 'NO';
    }
    else {
      // We need to know if this attribute was created by the logged in user.
      $r['metaFields:disabled_input'] = $this->model->created_by_id == ($_SESSION['auth_user']->id) ? 'NO' : 'YES';
    }
    $this->model->populate_validation_rules();
    return $r;
  }

  /**
   * Returns default values for the edit form.
   *
   * @return array
   *   List of default values for each field.
   */
  protected function getDefaults() {
    return array('metaFields:disabled_input' => 'NO');
  }

  /**
   * Saves a submitted edit form.
   */
  public function save() {
    if ($_POST['metaFields:disabled_input'] === 'NO') {
      // Build the validation_rules field from the set of controls that are associated with it.
      $rules = array();
      $allAvailableRules = array(
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
      );
      foreach ($allAvailableRules as $rule) {
        if (array_key_exists('valid_' . $rule, $_POST) && $_POST['valid_' . $rule] == 1) {
          array_push($rules, $rule);
        }
      }
      // Trim the input data, incase spaces are left in the validation parameters which would affect our tests.
      $_POST = array_map('trim', $_POST);
      if (array_key_exists('valid_length', $_POST) && $_POST['valid_length'] == 1
          && !empty($_POST['valid_length_max'])) {
        $min = empty($_POST['valid_length_min']) ? '0' : $_POST['valid_length_min'];
        $rules[] = 'length[' . $min . ',' . $_POST['valid_length_max'] . ']';
      }
      if (array_key_exists('valid_decimal', $_POST) && $_POST['valid_decimal'] == 1 && !empty($_POST['valid_dec_format'])) {
        $rules[] = 'decimal[' . $_POST['valid_dec_format'] . ']';
      }
      if (array_key_exists('valid_regex', $_POST) && $_POST['valid_regex'] == 1 && !empty($_POST['valid_regex_format'])) {
        $rules[] = 'regex[' . $_POST['valid_regex_format'] . ']';
      }
      if (array_key_exists('valid_min', $_POST) && $_POST['valid_min'] == 1) {
        $rules[] = 'minimum[' . $_POST['valid_min_value'] . ']';
      }
      if (array_key_exists('valid_max', $_POST) && $_POST['valid_max'] == 1) {
        $rules[] = 'maximum[' . $_POST['valid_max_value'] . ']';
      }

      $_POST[$this->model->object_name . ':validation_rules'] = implode("\r\n", $rules);
      // Make sure checkboxes have a value as unchecked values don't appear in $_POST
      // @todo: If we use Indicia client helper controls for the attribute edit page, this becomes unnecessary
      if (!array_key_exists($this->model->object_name . ':public', $_POST)) {
        $_POST[$this->model->object_name . ':public'] = '0';
      }
      if (!array_key_exists($this->model->object_name . ':multi_value', $_POST)) {
        $_POST[$this->model->object_name . ':multi_value'] = '0';
      }
    }
    parent::save();
  }

  /**
   * You can always get to the edit page for an attribute though the form might be read only.
   */
  protected function record_authorised($id) {
    return TRUE;
  }

}
