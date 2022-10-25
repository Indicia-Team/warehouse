<?php

/**
 * @file
 * Controller for the list of UKSI operations.
 *
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
 * @link https://github.com/indicia-team/warehouse/
 */

class Taxa_search_Controller extends Indicia_Controller {

  public function index() {
    $view = new View('taxa_search/index');
    $view->defaultListId = kohana::config('uksi_operations.taxon_list_id', FALSE, FALSE);
    $view->taxonLists = $this->loadPermittedTaxonLists();
    $this->template->title = 'Taxon search';
    $this->template->content = $view;
  }

  /**
   * As the UKSI list is global, need to be admin to change it.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->logged_in('UKSIAdmin') || $this->auth->has_any_website_access('editor');
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
    }
    $lists = [];
    foreach ($query->get()->result() as $list) {
      $lists[$list->id] = $list->title;
    }
    return $lists;
  }

}
