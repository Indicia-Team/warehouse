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
 * Controller providing the ability to configure the list of attributes joined to a taxon list.
 */
class Attribute_By_Taxon_List_Controller extends Indicia_Controller {
  private $taxon_list_id = NULL;

  public function __construct() {
    parent::__construct();
    if (!is_numeric($this->uri->last_segment()))
      throw new Exception('Page cannot be accessed without a taxon list filter');
    $this->pagetitle = 'Attributes for a taxon list';
    $this->get_auth();
    $this->model = ORM::factory('taxon_lists_taxa_taxon_list_attribute');
  }

  public function edit($id) {
    $segments = $this->uri->segment_array();
    $m = ORM::factory('taxon_lists_taxa_taxon_list_attribute', $segments[3]);
    $this->taxon_list_id = $m->taxon_list_id;
    return parent::edit($id);
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

  protected function editViewName() {
    return "attribute_by_taxon_list/attribute_by_taxon_list_edit";
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
    $list = ORM::Factory('taxon_list', $values['taxon_lists_taxa_taxon_list_attribute:taxon_list_id']);
    $attr = ORM::Factory('taxa_taxon_list_attribute', $values['taxon_lists_taxa_taxon_list_attribute:taxa_taxon_list_attribute_id']);
    $controlTypes = $this->db
      ->select('id, control')
      ->from('control_types')
      ->where('for_data_type', $attr->data_type)
      ->get();
    $otherData = array(
      'name' => $attr->caption,
      'taxonList' => $list->title,
      'controlTypes' => $controlTypes,
    );
    // If linking to taxa for an existing sample or occurrence attribute, we
    // need a caption to display.
    $masterListId = warehouse::getMasterTaxonListId();
    if ($masterListId) {
      $otherData['taxon_restrictions'] = $this->db
        ->select('t.id as taxa_taxon_list_id, tr.restrict_to_taxon_meaning_id, tr.restrict_to_stage_term_meaning_id, stage.id as restrict_to_stage_termlists_term_id')
        ->from('cache_taxa_taxon_lists AS t')
        ->join("taxa_taxon_list_attribute_taxon_restrictions AS tr", 'tr.restrict_to_taxon_meaning_id', 't.taxon_meaning_id')
        ->join('cache_termlists_terms AS stage', [
          'stage.meaning_id' => 'tr.restrict_to_stage_term_meaning_id',
          'stage.preferred' => TRUE,
        ], NULL, 'LEFT')
        ->where([
          "tr.taxon_lists_taxa_taxon_list_attribute_id" => $values["taxon_lists_taxa_taxon_list_attribute:id"],
          't.preferred' => 't',
          't.taxon_list_id' => $masterListId,
          'tr.deleted' => 'f',
        ])
        ->get()->result_array(FALSE);
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
    $attrIdKey = 'taxon_lists_taxa_taxon_list_attribute:taxa_taxon_list_attribute_id';
    if (isset($_POST[$attrIdKey])) {
      return 'taxa_taxon_list_attribute/edit/' . $_POST[$attrIdKey];
    }
    else {
      // If $_POST data not available, then just return to the attribute list. Shouldn't really happen.
      return 'taxa_taxon_list_attribute';
    }
  }

  /**
   * Set the edit page breadcrumbs to cope with the fact this controller handles all *_attributes_website models.
   */
  protected function defineEditBreadcrumbs() {
    // @todo
  }

  /**
   * Prevent users accessing other taxon list attributes if they are not core admin.
   *
   * @return boolean
   *   True if access granted.
   */
  protected function page_authorised() {
    // @todo
    return TRUE;
  }

}
