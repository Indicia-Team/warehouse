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
 * Controller providing CRUD access to the taxa that belong to a checklist.
 */
class Taxa_taxon_list_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('taxa_taxon_list');
    $this->base_filter['parent_id'] = NULL;
    $this->base_filter['preferred'] = 't';
    $this->columns = [
      'taxon' => '',
      'authority' => '',
      'taxon_group' => 'Taxon group',
      'language' => '',
      'taxonomic_sort_order' => 'Sort order',
    ];
    $this->pagetitle = "Species";
  }

 /**
  * Override the default index functionality to filter by taxon_list.
  */
  public function index() {
    $taxon_list_id = $this->uri->argument(1);
    $taxonList = ORM::factory('taxon_list', $taxon_list_id);
    $this->pagetitle = "Species in $taxonList->title";
    $this->internal_index($taxon_list_id, $taxonList);
  }

  /**
   * Ensure the edit form is only loaded for the preferred name in a taxon list.
   *
   * Synonyms and common names share the taxon meaning with the preferred name,
   * but the edit form submits the preferred flag as true. Redirecting here
   * prevents a non-preferred name from being promoted if the form is saved.
   *
   * @param int $id
   *   The taxa_taxon_list id to edit.
   */
  public function edit($id) {
    $id = (int) $id;
    if (!$this->record_authorised($id)) {
      $this->access_denied();
      return;
    }

    $taxa = ORM::factory('taxa_taxon_list', $id);
    if ($taxa->deleted !== 't' && $taxa->preferred !== 't') {
      $preferred = ORM::factory('taxa_taxon_list')->where([
        'taxon_list_id' => $taxa->taxon_list_id,
        'taxon_meaning_id' => $taxa->taxon_meaning_id,
        'preferred' => 't',
        'deleted' => 'f',
      ])->find();
      if ($preferred->loaded) {
        $query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        url::redirect("taxa_taxon_list/edit/$preferred->id$query");
        return;
      }
    }

    parent::edit($id);
  }

  public function children($id) {
    $parentTtl = ORM::factory('taxa_taxon_list', $id);
    $this->base_filter['parent_id'] = $id;
    $taxonList = ORM::factory('taxon_list', $parentTtl->taxon_list_id);
    $this->internal_index($parentTtl->taxon_list_id, $taxonList);
    // Pass the parent id into the view, so the create list button can use it
    // to autoset the parent of the new list.
    $this->view->parent_id = $id;
  }

  private function internal_index($taxon_list_id, $taxonList) {
    // No further filtering of the gridview required as the very fact you can access the parent taxon list
    // means you can access all the taxa for it.
    if (!$this->taxon_list_authorised($taxon_list_id)) {
      $this->access_denied("table to view records with a taxon list ID=$taxon_list_id");
      return;
    }
    $this->base_filter['taxon_list_id'] = $taxon_list_id;
    if (!empty($taxonList->parent_id)) {
      unset($this->base_filter['parent_id']);
    }
    parent::index();
    $this->view->taxon_list_id = $taxon_list_id;
    $this->view->parent_list_id = $taxonList->parent_id;
    $this->upload_csv_form->staticFields = array(
      'taxa_taxon_list:taxon_list_id' => $taxon_list_id
    );
    $this->upload_csv_form->returnPage = $taxon_list_id;
  }

  /**
   * Define non-standard behaviuor for the breadcrumbs, since this is accessed via a taxon list
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('taxon_list', 'Species Lists');
    if ($this->model->id) {
      // Editing an existing item, so our argument is the taxa in taxon list
      // id.
      $listId = $this->model->taxon_list_id;
    }
    else {
      // Creating a new one so our argument is the taxon list id.
      $listId = $this->uri->argument(1);
    }
    $listTitle = ORM::Factory('taxon_list', $listId)->title;
    $this->page_breadcrumbs[] = html::anchor("taxon_list/edit/$listId?tab=taxa", $listTitle);
    $this->page_breadcrumbs[] = $this->model->caption();
  }

  /**
   * Returns an array of all values from this model and its super models ready to be
   * loaded into a form. For this controller, we need to also setup the child taxon grid
   * and the synonyms/common names plus the list of images.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $this->loadAttributes($r, ['taxon_list_id' => [$this->model->taxon_list_id]]);
    return $r;
  }

  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(FALSE) === 'create') {
      // List id is passed as first argument in URL when creating.
      $r['taxa_taxon_list:taxon_list_id'] = $this->uri->argument(1);
      // Parent id might be passed in $_POST if creating as child of another
      // taxon.
      if (array_key_exists('taxa_taxon_list:parent_id', $_POST)) {
        $r['taxa_taxon_list:parent_id'] = $_POST['taxa_taxon_list:parent_id'];
      }
    }
    else {
      // After a validation failure, the list id is in the post data.
      $r['taxa_taxon_list:taxon_list_id'] = $_POST['taxa_taxon_list:taxon_list_id'];
    }
    // Default when entering via the UI is flag as manually entered, so sync
    // cripts can distinguish from taxa added by automated sync.
    $r['taxa_taxon_list:manually_entered'] = 't';
    $this->loadAttributes($r,
        array('taxon_list_id' => array($r['taxa_taxon_list:taxon_list_id']))
    );
    return $r;
  }

  /**
   * Reports if editing a taxon in taxon list is authorised.
   *
   * @param int $id
   *   Id of the taxa_taxon_list that is being checked, or NULL for a new
   *   record.
   */
  protected function record_authorised($id) {
    if ($id===NULL) {
      // Creating a new record, so the taxon list id is an argument.
      $list_id = $this->uri->argument(1);
    }
    else {
      $taxa = new Taxa_taxon_list_Model($id);
      // The id should already exist, otherwise the user is attempting to
      // create by passing a param to the edit function.
      if (!$taxa->loaded) {
        return FALSE;
      }
      else {
        $list_id = $taxa->taxon_list_id;
      }
    }
    return ($this->taxon_list_authorised($list_id));
  }

  protected function taxon_list_authorised($id) {
    // for this controller, any null ID taxon_list can not be accessed
    if (is_null($id)) return FALSE;
    $websites = $this->get_allowed_website_id_list('editor', FALSE);
    if (!is_null($websites))
    {
      $taxon_list = new Taxon_list_Model($id);
      // for this controller, any taxon_list that does not exist can not be accessed.
      if (!$taxon_list->loaded) return FALSE;
      return (in_array($taxon_list->website_id, $websites));
    }
    return TRUE;
  }

  /**
   * Override the default return page behaviour so that after saving a taxa you
   * are returned to the list of taxa on the sub-tab of the list.
   */
  protected function get_return_page() {
    if (array_key_exists('taxa_taxon_list:taxon_list_id', $_POST)) {
      // After saving a record, the list id to return to is in the POST data.
      // User may select to continue adding new taxa.
      if (isset($_POST['what-next'])) {
        if ($_POST['what-next'] === 'add') {
          return 'taxa_taxon_list/create/' . $_POST['taxa_taxon_list:taxon_list_id'];
        }
      }
      // Or, just return to the list page.
      return 'taxon_list/edit/' . $_POST['taxa_taxon_list:taxon_list_id'] . '?tab=taxa';
    }
    elseif (array_key_exists('taxa_taxon_list:taxon_list_id', $_GET)) {
      // After uploading records, the list id is in the URL get parameters.
      return 'taxon_list/edit/' . $_GET['taxa_taxon_list:taxon_list_id'] . '?tab=taxa';
    }
    else {
      // Last resort if we don't know the list, just show the whole lot of
      // lists.
      return "taxon_list";
    }
  }

  /**
   * Retrieves the value to display in the textarea for the scientific names.
   *
   * @return string
   *   Value for scientific names.
   */
  private function formatScientificSynonomy(ORM_Iterator $res) {
    $syn = "";
    foreach ($res as $synonym) {
      if ($synonym->taxon->language->iso == "lat") {
        $syn .= $synonym->taxon->taxon;
        if ($synonym->taxon->authority) {
          $syn .= ' | ' . $synonym->taxon->authority;
        }
        $syn .= "\n";
      }
    }
    return $syn;
  }

  /**
   * Retrieves the value to display in the textarea for the common names.
   *
   * @return string
   *   Value for common names.
   */
  private function formatCommonSynonomy(ORM_Iterator $res) {
    $syn = "";
    foreach ($res as $synonym) {
      if ($synonym->taxon->language->iso != "lat") {
        $syn .= $synonym->taxon->taxon;
        $syn .= ($synonym->taxon->language_id != NULL) ?
          " | " . $synonym->taxon->language->iso . "\n" :
          '';
      }
    }
    return $syn;
  }

  /**
   * Controller action for the lumping and splitting tab.
   */
  public function lumping_splitting($id) {
    $ttl = ORM::Factory('taxa_taxon_list', $id);
    $this->setView('taxa_taxon_list/lumping_splitting', '', array(
      'values' => array(
        'taxa_taxon_list:id' => $id,
        'taxa_taxon_list:taxon_list_id' => $ttl->taxon_list_id,
        'taxon_meaning:id' => $ttl->taxon_meaning_id,
      )
    ));
  }

  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return array(array(
      'controller' => 'taxon_medium',
      'title' => 'Media Files',
      'actions' => array('edit'),
    ), array(
      'controller' => 'taxon_code',
      'title' => 'Codes',
      'actions' => array('edit'),
    ), array(
      'controller' => 'taxa_taxon_list/children',
      'title' => 'Child Taxa',
      'actions' => array('edit'),
    ), array(
      'controller' => 'taxon_relation',
      'title' => 'Relations',
      'actions' => array('edit'),
    ), array(
      'controller' => 'taxa_taxon_list/lumping_splitting',
      'title' => 'Lumping & Splitting',
      'actions' => array('edit'),
    ));
  }

  /**
   * Function used by the AJAX methods which allow adding a taxon from a parent list into
   * the current list. Adds a single taxon.
   *
   * @param int $parentTtlId
   *   The taxa_taxon_list_id to add, from the parent list.
   * @param int $thisListId
   *   The current list ID.
   */
  private function add_single_taxon_from_parent_list($parentTtlId, $thisListId) {
    // Get the selected name.
    $ttl = ORM::factory('taxa_taxon_list', $parentTtlId);
    // Find a list of the taxon ids for this meaning which are already in the
    // list.
    $existing = ORM::factory('taxa_taxon_list')->where(array(
      'taxon_list_id' => $thisListId,
      'taxon_meaning_id' => $ttl->taxon_meaning_id,
      'deleted' => 'f',
    ))->find_all();
    $existingTaxa = array();
    foreach ($existing as $item) {
      $existingTaxa[] = $item->taxon_id;
    }
    // We must copy across all names for the taxon not just the selected one.
    $all_names = ORM::factory('taxa_taxon_list')->where(array(
      'taxon_list_id' => $ttl->taxon_list_id,
      'taxon_meaning_id' => $ttl->taxon_meaning_id,
    ))->find_all();
    $existingCount = 0;
    $newCount = 0;
    $r = array('added_preferred_taxa_taxon_list_id' => FALSE, 'message' => '');
    // Loop through the names.
    foreach ($all_names as $name) {
      $data = $name->as_array();
      if (in_array($data['taxon_id'], $existingTaxa)) {
        $existingCount++;
      }
      else {
        unset($data['id']);
        $data['taxon_list_id'] = $_POST['taxon_list_id'];
        // Create a new model using the existing ttl data but a new list id.
        $newttl = ORM::factory('taxa_taxon_list');
        $newttl->validate(new Validation($data), TRUE);
        // we want to return the id of the preferred taxon copied over
        if ($newttl->preferred === 't') {
          $r['added_preferred_taxa_taxon_list_id'] = $newttl->id;
        }
        $newCount++;
      }
    }
    if (!$r['added_preferred_taxa_taxon_list_id']) {
      // Failed to add something, so generate a message as to why.
      if ($existingCount > 0) {
        $r['message'] = 'The taxon already exists in the list.';
      }
      elseif ($newCount > 0) {
        $r['message'] = 'The taxon already exists in the list but some names were missing, so they have '.
          'been copied across.';
      }
      else {
        $r['message'] = 'Failed to add the taxon to the list.';
      }
    }
    return $r;
  }

  /**
   * AJAX controller method for the ability to add a taxon from a parent list into a child list.
   * Takes the child (destination) taxon list id and the source taxa taxon list id as parameters
   * in the $_POST data.
   */
  public function add_parent_taxon() {
    // no template as this is for AJAX
    $this->auto_render = FALSE;
    $outcome = $this->add_single_taxon_from_parent_list($_POST['taxa_taxon_list_id'], $_POST['taxon_list_id']);
    if ($outcome['added_preferred_taxa_taxon_list_id'])
      echo $outcome['added_preferred_taxa_taxon_list_id'];
    else
      echo $outcome['message'];
  }

  public function add_parent_taxon_list() {
    // no template as this is for AJAX
    $this->auto_render = FALSE;
    // convert the pasted text into an array
    $pasted_taxa = str_replace("\r\n", "\n", $_POST['taxa_to_add']);
    $pasted_taxa = str_replace("\r", "\n", $pasted_taxa);
    $pasted_taxa = explode("\n", trim($pasted_taxa));
    $thisListId = $_POST['taxon_list_id'];
    $list = ORM::factory('taxon_list', $thisListId);
    if (!$list->parent_id) {
      throw new exception('Trying to copy taxa into a child list but the list has no parent');
    };
    $messages = array();
    foreach ($pasted_taxa as $pasted_taxon) {
      $pasted_taxon = trim($pasted_taxon);
      if (empty($pasted_taxon))
        continue; // to next in list, as empty line found
      $rows = $this->db->select('id, taxon_meaning_id, taxon_id, preferred, allow_data_entry, taxon')
        ->from('list_taxa_taxon_lists')
        ->where(array(
          $_POST['search_method'] => $pasted_taxon,
          'taxon_list_id' => $list->parent_id
        ))
        ->orderby(array('preferred' => 'DESC', 'allow_data_entry' => 'DESC'))
        ->get()->result_array(FALSE);
      if (empty($rows)) {
        $messages[] = "$pasted_taxon could not be found in the parent list";
      }
      elseif (count($rows) > 1 && ($rows[0]['preferred'] === 'f' || $rows[1]['preferred'] === 't') &&
          ($rows[0]['allow_data_entry'] === 'f' || $rows[1]['allow_data_entry'] === 't')) {
        $messages[] = "$pasted_taxon was found but could not be used to identify a unique taxon in the parent list";
      }
      else {
        // Found a unique hit, either the only matching preferred name, or the only matching name
        // so we can add it to the sublist database.
        $outcome = $this->add_single_taxon_from_parent_list($rows[0]['id'], $thisListId);
        $taxon = $rows[0]['taxon'];
        if ($outcome['added_preferred_taxa_taxon_list_id'])
          $messages[] = "$taxon was added to the list";
        else {
          $messages[] = "$taxon - $outcome[message]";
        }
      }
    }
    echo json_encode($messages);
  }

  /**
   * Controller action for AJAX to check if occurrences exist when deleting.
   *
   * Informs the delete button UI if occurrences exist for this taxa, giving
   * the user the chance to replace the taxon with another.
   */
  public function check_occurrences() {
    header("Content-Type: application/json");
    if (empty($_GET['taxa_taxon_list_id'])) {
      http_response_code(400);
      $this->template->content = json_encode(['status' => 400, 'msg' => 'Bad Request']);
      return;
    }
    $taxaTaxonListId = $_GET['taxa_taxon_list_id'];
    if (!$this->record_authorised($taxaTaxonListId)) {
      http_response_code(404);
      $this->template->content = json_encode(['status' => 404, 'msg' => 'Unauthorized']);
      return;
    };
    $sql = <<<SQL
SELECT 1 FROM occurrences WHERE taxa_taxon_list_id IN (
  SELECT ttl.id FROM taxa_taxon_lists ttl
  JOIN taxa_taxon_lists ttlany ON ttlany.taxon_meaning_id=ttl.taxon_meaning_id AND ttlany.id=?
) AND deleted=false LIMIT 1;
SQL;
    $existsCheck = $this->db->query($sql, [$taxaTaxonListId])->current();
    $this->template->content = json_encode([
      'status' => 200,
      'msg' => 'OK',
      'found' => $existsCheck ? TRUE : FALSE,
    ]);
  }

  /**
   * Additional information for the edit view.
   *
   * Returns some addition information required by the edit view, which is not
   * associated with a particular record.
   *
   * @param array $values
   *   The values prepared for the edit view.
   *
   * @return array
   *   Additional data required by the edit view.
   */
  protected function prepareOtherViewData(array $values) {
    return [
      'taxon_lists' => $this->loadPermittedTaxonLists(),
      'related_names' => $this->loadRelatedNames($this->model->taxon_meaning_id, $this->model->taxon_list_id),
      'name_languages' => $this->db->select('iso, language')->from('languages')->orderby('language')->get()->result_array(FALSE),
      'name_ranks' => $this->db->select('id, rank')->from('taxon_ranks')->orderby('sort_order')->get()->result_array(FALSE),
    ];
  }

  /**
   * Load the active non-preferred names for the related names grids.
   *
   * @param int $taxonMeaningId
   *   The taxon meaning ID shared by the names.
   * @param int $taxonListId
   *   The taxon list ID containing the names.
   *
   * @return array
   *   Related names grouped into synonyms and common names.
   */
  private function loadRelatedNames($taxonMeaningId, $taxonListId) {
    $rows = $this->db->select('ttl.id, ttl.allow_data_entry, ttl.manually_entered, '
        . 't.id AS taxon_id, t.taxon, t.authority, t.attribute, t.search_code, '
        . 't.name_deprecated, t.name_form, t.taxon_rank_id, l.iso AS language_iso, '
        . 'tr.rank AS taxon_rank')
      ->from('taxa_taxon_lists AS ttl')
      ->join('taxa AS t', 't.id', 'ttl.taxon_id')
      ->join('languages AS l', 'l.id', 't.language_id', 'LEFT')
      ->join('taxon_ranks AS tr', 'tr.id', 't.taxon_rank_id', 'LEFT')
      ->where([
        'ttl.taxon_meaning_id' => $taxonMeaningId,
        'ttl.taxon_list_id' => $taxonListId,
        'ttl.preferred' => 'f',
        'ttl.deleted' => 'f',
        't.deleted' => 'f',
      ])
      ->orderby(['l.iso' => 'ASC', 't.taxon' => 'ASC'])
      ->get()->result_array(FALSE);
    $relatedNames = ['synonyms' => [], 'common_names' => []];
    foreach ($rows as $row) {
      $relatedNames[$row['language_iso'] === 'lat' ? 'synonyms' : 'common_names'][] = $row;
    }
    return $relatedNames;
  }

  /**
   * Save an individual common name or synonym from the related names grid.
   *
   * The record data is read from the POST request. New names inherit shared
   * taxon data from the preferred name in the current taxon list.
   *
   * @return void
   *   Redirects to the preferred taxon edit page after saving or reporting an
   *   error.
   */
  public function save_related_name() {
    $id = !empty($_POST['id']) ? (int) $_POST['id'] : NULL;
    $ttl = $id ? ORM::factory('taxa_taxon_list', $id) : ORM::factory('taxa_taxon_list');
    $isNew = !$ttl->loaded;
    $listId = !empty($_POST['taxon_list_id']) ? (int) $_POST['taxon_list_id'] : NULL;
    $preferred = ORM::factory('taxa_taxon_list', (int) $_POST['taxon_meaning_preferred_id']);
    $meaningId = (int) $_POST['taxon_meaning_id'];
    if (!$ttl->loaded && !$listId) {
      $this->relatedNameError('A taxon list is required.');
      return;
    }
    $authorisedListId = $ttl->loaded ? $ttl->taxon_list_id : $listId;
    if (!$preferred->loaded || $preferred->preferred !== 't' || $preferred->deleted === 't'
        || (int) $preferred->taxon_list_id !== (int) $authorisedListId
        || (int) $preferred->taxon_meaning_id !== $meaningId
        || !$this->taxon_list_authorised($authorisedListId)
        || ($ttl->loaded && ($ttl->preferred === 't' || (int) $ttl->taxon_meaning_id !== $meaningId))) {
      $this->relatedNameError('The related taxon name cannot be edited.');
      return;
    }
    $isSynonym = isset($_POST['name_type']) && $_POST['name_type'] === 'synonym';
    $language = $isSynonym ? 'lat' : (!empty($_POST['language_iso']) ? $_POST['language_iso'] : 'eng');
    $languageId = ORM::factory('language')->where(['iso' => $language])->find()->id;
    if (!$languageId || empty($_POST['taxon'])) {
      $this->relatedNameError('A name and valid language are required.');
      return;
    }
    $_POST['taxa_taxon_list:id'] = $id ?: '';
    $_POST['taxa_taxon_list:taxon_list_id'] = $authorisedListId;
    $_POST['taxa_taxon_list:taxon_meaning_id'] = $meaningId;
    $_POST['taxa_taxon_list:preferred'] = 'f';
    $_POST['taxa_taxon_list:allow_data_entry'] = !empty($_POST['allow_data_entry']) ? 't' : 'f';
    $_POST['taxa_taxon_list:manually_entered'] = !empty($_POST['manually_entered']) ? 't' : 'f';
    $_POST['taxon:id'] = $ttl->loaded ? $ttl->taxon_id : '';
    $_POST['taxon:taxon'] = trim($_POST['taxon']);
    $_POST['taxon:language_id'] = $languageId;
    $_POST['taxon:authority'] = $isSynonym ? trim($_POST['authority'] ?? '') : '';
    $_POST['taxon:attribute'] = trim($_POST['attribute'] ?? '');
    $_POST['taxon:search_code'] = trim($_POST['search_code'] ?? '');
    $_POST['taxon:name_deprecated'] = !empty($_POST['name_deprecated']) ? 't' : 'f';
    $_POST['taxon:name_form'] = trim($_POST['name_form'] ?? '');
    $_POST['taxon:taxon_rank_id'] = $isSynonym && !empty($_POST['taxon_rank_id'])
      ? (int) $_POST['taxon_rank_id']
      : ($isNew ? $preferred->taxon->taxon_rank_id : NULL);
    if ($isNew) {
      $_POST['taxon:taxon_group_id'] = $preferred->taxon->taxon_group_id;
      $_POST['taxon:external_key'] = $preferred->taxon->external_key;
      $_POST['taxon:organism_key'] = $preferred->taxon->organism_key;
    }
    $ttl->set_submission_data($_POST);
    if ($ttl->submit()) {
      url::redirect('taxa_taxon_list/edit/' . (int) $_POST['taxon_meaning_preferred_id']);
      return;
    }
    $errors = $ttl->getAllErrors();
    $messages = [];
    foreach ($errors as $field => $message) {
      $messages[] = is_array($message) ? implode(' ', $message) : $message;
    }
    $this->relatedNameError(implode(' ', $messages) ?: 'The related taxon name could not be saved.');
  }

  /**
   * Soft-delete one related common name or synonym.
   *
   * The record IDs are read from the POST request.
   *
   * @return void
   *   Redirects to the preferred taxon edit page.
   */
  public function delete_related_name() {
    $ttl = ORM::factory('taxa_taxon_list', (int) $_POST['id']);
    $preferred = ORM::factory('taxa_taxon_list', (int) $_POST['taxon_meaning_preferred_id']);
    if (!$ttl->loaded || !$preferred->loaded || $ttl->preferred === 't' || $preferred->preferred !== 't'
        || $ttl->taxon_list_id !== $preferred->taxon_list_id
        || $ttl->taxon_meaning_id !== $preferred->taxon_meaning_id
        || !$this->taxon_list_authorised($ttl->taxon_list_id)) {
      $this->relatedNameError('The related taxon name cannot be deleted.');
      return;
    }
    $ttl->deleted = 't';
    $ttl->save();
    url::redirect('taxa_taxon_list/edit/' . (int) $_POST['taxon_meaning_preferred_id']);
  }

  /**
   * Promote a synonym while preserving one preferred record per concept.
   *
   * The synonym and preferred record IDs are read from the POST request.
   *
   * @return void
   *   Redirects to the newly preferred taxon edit page.
   */
  public function promote_synonym() {
    $synonym = ORM::factory('taxa_taxon_list', (int) $_POST['id']);
    $preferred = ORM::factory('taxa_taxon_list', (int) $_POST['preferred_id']);
    if (!$synonym->loaded || !$preferred->loaded || $synonym->preferred === 't'
        || $preferred->preferred !== 't' || $synonym->deleted === 't' || $preferred->deleted === 't'
        || $synonym->taxon_list_id !== $preferred->taxon_list_id
        || $synonym->taxon_meaning_id !== $preferred->taxon_meaning_id
        || !$this->taxon_list_authorised($preferred->taxon_list_id)) {
      $this->relatedNameError('The synonym cannot be made preferred.');
      return;
    }
    $this->db->query('BEGIN');
    try {
      $this->db->query('UPDATE taxa_taxon_lists SET common_taxon_id=?, parent_id=? WHERE id=?', [
        $preferred->common_taxon_id,
        $preferred->parent_id,
        $synonym->id,
      ]);
      $searchCode = trim((string) $synonym->taxon->search_code);
      if ($searchCode !== '') {
        $this->db->query(<<<SQL
          UPDATE taxa t
          SET external_key=?, updated_on=now(), updated_by_id=?
          FROM taxa_taxon_lists ttl
          WHERE ttl.taxon_meaning_id=?
          AND t.id=ttl.taxon_id
          AND t.deleted=false
        SQL, [$searchCode, security::getUserId(), $synonym->taxon_meaning_id]);
      }
      $this->db->query('UPDATE taxa_taxon_lists SET preferred=false, updated_on=now(), updated_by_id=? WHERE id=?', [security::getUserId(), $preferred->id]);
      $this->db->query('UPDATE taxa_taxon_lists SET preferred=true, updated_on=now(), updated_by_id=? WHERE id=?', [security::getUserId(), $synonym->id]);
      $this->db->query('COMMIT');
    }
    catch (Exception $e) {
      $this->db->query('ROLLBACK');
      throw $e;
    }
    if (in_array(MODPATH . 'cache_builder', Kohana::config('config.modules'))) {
      $this->db->query(<<<SQL
        INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
        SELECT 'task_cache_builder_taxonomy_occurrence', 'taxa_taxon_list', id, 100, 3, now()
        FROM taxa_taxon_lists
        WHERE taxon_meaning_id=? AND deleted=false
        ON CONFLICT DO NOTHING
      SQL, [$synonym->taxon_meaning_id]);
    }
    url::redirect('taxa_taxon_list/edit/' . (int) $synonym->id);
  }

  /**
   * Report a related-name operation error and return to the edit page.
   *
   * @param string $message
   *   The error message to display to the user.
   *
   * @return void
   *   Redirects to the preferred taxon edit page.
   */
  private function relatedNameError($message) {
    $this->session->set_flash('flash_error', $message);
    url::redirect('taxa_taxon_list/edit/' . (int) $_POST['taxon_meaning_preferred_id']);
  }

  /**
   * Retrieves the list of lists that the user has rights to.
   *
   * @return array
   *   List of taxon list titles, keyed by ID.
   */
  private function loadPermittedTaxonLists() {
    $query = $this->db->select('taxon_lists.id, taxon_lists.title')
      ->from('taxon_lists')
      ->join('websites', 'websites.id', 'taxon_lists.website_id', 'LEFT')
      ->orderby('taxon_lists.title')
      ->where('taxon_lists.deleted', 'f');
    if (!empty($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
      $query->in('taxon_lists.website_id', $this->auth_filter['values']);
      // Actually don't want the public lists.
      $query->where('taxon_lists.website_id is not null');
    }
    $lists = [];
    foreach ($query->get()->result() as $list) {
      $lists[$list->id] = $list->title;
    }
    return $lists;
  }

  /**
   * Override save so we can safely map records to a proposed replacement taxa.
   */
  public function save() {
    if ($this->page_authorised() && $_POST['submit'] == kohana::lang('misc.delete') && !empty($_POST['new_taxa_taxon_list_id'])) {
      $q = new WorkQueue();
      $q->enqueue($this->db, [
        'task' => 'task_replace_taxon',
        'entity' => 'taxa_taxon_list',
        'record_id' => $_POST['new_taxa_taxon_list_id'],
        'params' => json_encode(['old_taxa_taxon_list_id' => $_POST['taxa_taxon_list:id']]),
        'cost_estimate' => 100,
        'priority' => 3,
      ]);
    }
    parent::save();
  }

}
