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
 * Controller for taxon associations.
 */
class Taxon_association_Controller extends Gridview_Base_Controller {

  /**
   * Constructor, initiates grid columns.
   */
  public function __construct() {
    parent::__construct('taxon_association', NULL, NULL, 'taxon_association');
    $this->columns = array(
      'id' => '',
      'from_taxon' => '',
      'association_type' => '',
      'to_taxon' => '',
    );
    $this->pagetitle = "Associations";
  }

  /**
   * Controller method for the index action.
   *
   * @param integer $filter
   *   ID of the taxon we are viewing to filter to.
   */
  public function index($filter = NULL) {
    if (!$filter) {
      throw new exception('Associations index view needs to filter to an taxon.');
    }
    // Filter the list to the occurrence we are viewing.
    $this->base_filter['taxa_taxon_list_id'] = $filter;
    parent::index($filter);
    $this->view->taxa_taxon_list_id = $filter;
  }

  /**
   * Prepare other information required for the edit view.
   *
   * @param array $values
   *   Form values.
   *
   * @return array
   *   Additional information for the view.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $taId = (int) $this->uri->argument(1);
    // Find basic info about the occurrence association.
    $sql = <<<SQL
SELECT DISTINCT cttlfrom.id, cttlfrom.taxon_list_id,
  cttlfrom.preferred_taxon as from_taxon, cttlto.preferred_taxon as to_taxon
FROM taxon_associations ta
JOIN cache_taxa_taxon_lists cttlfrom ON cttlfrom.taxon_meaning_id=ta.from_taxon_meaning_id
JOIN cache_taxa_taxon_lists cttlto ON cttlto.taxon_meaning_id=ta.to_taxon_meaning_id
WHERE ta.id=?;
SQL;
    $ids = $this->db->query($sql, [$taId])->current();
    $r['from_taxon'] = $ids->from_taxon;
    $r['to_taxon'] = $ids->to_taxon;
    // Store the taxa_taxon_list ID we are viewing the association from, e.g. to use
    // when building breadcrumbs.
    $this->taxa_taxon_list_id = $ids->id;
    // Since the warehouse does not stipulate which termlists to use for the
    // associations metadata fields, find the termlists already in use for this
    // list and use the terms from those for the edit form.
    $sql = <<<SQL
SELECT string_agg(distinct ttype.termlist_id::text, ',') AS association_type_termlist_ids,
  string_agg(distinct tpart.termlist_id::text, ',') AS part_termlist_termlist_ids,
  string_agg(distinct tposition.termlist_id::text, ',') AS position_termlist_ids,
  string_agg(distinct timpact.termlist_id::text, ',') AS impact_termlist_ids
FROM cache_taxa_taxon_lists cttl
JOIN taxon_associations ta ON ta.from_taxon_meaning_id=cttl.taxon_meaning_id
LEFT JOIN cache_termlists_terms ttype ON ttype.id=ta.association_type_id
LEFT JOIN cache_termlists_terms tpart ON tpart.id=ta.part_id
LEFT JOIN cache_termlists_terms tposition ON tposition.id=ta.position_id
LEFT JOIN cache_termlists_terms timpact ON timpact.id=ta.impact_id
WHERE cttl.taxon_list_id=?;
SQL;
    $termlists = $this->db->query($sql, [$ids->taxon_list_id])->current();
    $termDataToFetch = [
      'association_type_termlist_ids' => 'type_terms',
      'part_termlist_termlist_ids' => 'part_terms',
      'position_termlist_ids' => 'position_terms',
      'impact_termlist_ids' => 'impact_terms',
    ];
    foreach ($termDataToFetch as $listIdsField => $termsField) {
      $r[$termsField] = [];
      if (!empty($termlists->$listIdsField)) {
        foreach (explode(',', $termlists->$listIdsField) as $listId) {
          $r[$termsField] += $this->get_termlist_terms($listId);
        }
      }
    }
    return $r;
  }

  /**
   * Set the edit page breadcrumbs to link back through the occurrences.
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('taxon_list', 'Species Lists');
    if ($this->model->id) {
      $taId = $taId = $this->uri->argument(1);
      $sql = <<<SQL
SELECT DISTINCT cttlfrom.id, cttlfrom.taxon, tl.id as taxon_list_id, tl.title as taxon_list_title, cttlfrom.preferred
FROM taxon_associations ta
JOIN cache_taxa_taxon_lists cttlfrom ON cttlfrom.taxon_meaning_id=ta.from_taxon_meaning_id
JOIN taxon_lists tl ON tl.id=cttlfrom.taxon_list_id
WHERE ta.id=?
ORDER BY cttlfrom.preferred DESC
LIMIT 1;
SQL;
      $info = $this->db->query($sql, [$taId])->current();

      $this->page_breadcrumbs[] = html::anchor("taxon_list/edit/$info->taxon_list_id?tab=associations", $info->taxon_list_title);
      $this->page_breadcrumbs[] = html::anchor("taxa_taxon_list/edit/$info->id?tab=associations", $info->taxon);
      $this->page_breadcrumbs[] = $this->model->caption();
    }
    else {
      $this->page_breadcrumbs[] = 'New association';
    }
  }

  /**
   * After save, return to the main occurrence.
   *
   * @return string
   *   Path to the page to return to.
   */
  protected function get_return_page() {
    $sql = <<<SQL
SELECT DISTINCT cttlfrom.id, cttlfrom.preferred
FROM taxon_associations ta
JOIN cache_taxa_taxon_lists cttlfrom ON cttlfrom.taxon_meaning_id=ta.from_taxon_meaning_id
WHERE ta.id=?
ORDER BY cttlfrom.preferred DESC
LIMIT 1;
SQL;
    $ids = $this->db->query($sql, [$_POST['taxon_association:id']])->current();
    return "taxa_taxon_list/edit/$ids->id?tab=Associations";
  }

}
