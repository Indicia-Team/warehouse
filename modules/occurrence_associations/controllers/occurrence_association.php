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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Controller for occurrence associations.
 */
class Occurrence_association_Controller extends Gridview_Base_Controller {

  /**
   * Constructor, initiates grid columns.
   */
  public function __construct() {
    parent::__construct('occurrence_association', NULL, NULL, 'occurrence_association');
    $this->columns = array(
      'id' => '',
      'from_occurrence' => '',
      'association_type' => '',
      'to_occurrence' => '',
    );
    $this->pagetitle = "Associations";
  }

  /**
   * Controller method for the index action.
   *
   * @param integer $filter
   *   ID of the occurrence we are viewing to filter to.
   */
  public function index($filter = NULL) {
    if (!$filter) {
      throw new exception('Associations index view needs to filter to an occurrence.');
    }
    // Filter the list to the occurrence we are viewing.
    $occ = ORM::Factory('occurrence', $filter);
    $this->base_filter['occurrence_id'] = $filter;
    parent::index($filter);
    $this->view->occurrence_id = $filter;
  }

  /**
   * Prepare other information required for the edit view.
   *
   * @param array $values
   *   Form values.
   * @return array
   *   Additional information for the view.
   */
  protected function prepareOtherViewData(array $values) {
    $oaId = (int) $this->uri->argument(1);
    // Find basic info about the occurrence association.
    $sql = <<<SQL
      SELECT DISTINCT ofrom.id, ofrom.survey_id, ofrom.website_id,
        cttlfrom.preferred_taxon as from_taxon, cttlto.preferred_taxon as to_taxon
      FROM occurrence_associations oa
      JOIN cache_occurrences_functional ofrom ON ofrom.id=oa.from_occurrence_id
      JOIN cache_taxa_taxon_lists cttlfrom ON cttlfrom.id=ofrom.taxa_taxon_list_id
      JOIN cache_occurrences_functional oto ON oto.id=oa.to_occurrence_id
      JOIN cache_taxa_taxon_lists cttlto ON cttlto.id=oto.taxa_taxon_list_id
      WHERE oa.id=?;
    SQL;
    $ids = $this->db->query($sql, [$oaId])->current();
    $otherData = [
      'from_taxon' => $ids->from_taxon,
      'to_taxon' => $ids->to_taxon,
    ];
    // Store the occurrence ID we are viewing the association from, e.g. to use
    // when building breadcrumbs.
    $this->occurrence_id = $ids->id;
    // Since the warehouse does not stipulate which termlists to use for the
    // associations metadata fields, find the termlists already in use for this
    // survey and use the terms from those for the edit form.
    $sql = <<<SQL
SELECT string_agg(distinct ttype.termlist_id::text, ',') AS association_type_termlist_ids,
  string_agg(distinct tpart.termlist_id::text, ',') AS part_termlist_termlist_ids,
  string_agg(distinct tposition.termlist_id::text, ',') AS position_termlist_ids,
  string_agg(distinct timpact.termlist_id::text, ',') AS impact_termlist_ids
FROM cache_occurrences_functional o2
JOIN occurrence_associations oa2 ON oa2.from_occurrence_id=o2.id
LEFT JOIN cache_termlists_terms ttype ON ttype.id=oa2.association_type_id
LEFT JOIN cache_termlists_terms tpart ON tpart.id=oa2.part_id
LEFT JOIN cache_termlists_terms tposition ON tposition.id=oa2.position_id
LEFT JOIN cache_termlists_terms timpact ON timpact.id=oa2.impact_id
WHERE o2.website_id=? AND o2.survey_id=?
SQL;
    $termlists = $this->db->query($sql, [$ids->website_id, $ids->survey_id])->current();
    $termDataToFetch = [
      'association_type_termlist_ids' => 'type_terms',
      'part_termlist_termlist_ids' => 'part_terms',
      'position_termlist_ids' => 'position_terms',
      'impact_termlist_ids' => 'impact_terms',
    ];
    foreach ($termDataToFetch as $listIdsField => $termsField) {
      $otherData[$termsField] = [];
      if (!empty($termlists->$listIdsField)) {
        foreach (explode(',', $termlists->$listIdsField) as $listId) {
          $otherData[$termsField] += $this->get_termlist_terms($listId);
        }
      }
    }
    return $otherData;
  }

  /**
   * Set the edit page breadcrumbs to link back through the occurrences.
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('occurrence', 'Occurrences');
    $this->page_breadcrumbs[] = html::anchor('occurrence/edit/' . $this->occurrence_id . '?tab=associations', "Occurrence $this->occurrence_id");
    $this->page_breadcrumbs[] = $this->model->caption();
  }

  /**
   * After save, return to the main occurrence.
   *
   * @return string
   *   Path to the page to return to.
   */
  protected function get_return_page() {
    $oa = ORM::Factory('occurrence_association', $_POST['occurrence_association:id']);
    return "occurrence/edit/$oa->from_occurrence_id?tab=Associations";
  }

}
