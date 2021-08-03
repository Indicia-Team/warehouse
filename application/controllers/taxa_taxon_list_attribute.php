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
 * Controller providing CRUD access to the taxa_taxon_list attributes.
 */
class Taxa_taxon_list_attribute_Controller extends Attr_Base_Controller {

  public function __construct() {
    $this->prefix = 'taxa_taxon_list';
    parent::__construct();
    $this->pagetitle = "Taxon Attributes";
    // Override the default columns for custom attributes, as taxon attributes
    // are attached to taxon lists not websites.
    $this->columns = [
      'id' => '',
      'taxon_list' => 'Species List',
      'caption' => '',
      'data_type' => 'Data type',
    ];
  }

  /**
   * Returns the view specific to taxon attribute edits.
   */
  protected function editViewName() {
    $this->associationsView = new View('templates/attribute_associations_taxon_list');
    return 'custom_attribute/custom_attribute_edit';
  }

  protected function prepareOtherViewData(array $values) {
    $baseData = parent::prepareOtherViewData($values);
    $qry = $this->db
      ->select([
        'tl.id',
        'tl.title',
        'tla.id as taxon_lists_taxa_taxon_list_attributes_id',
      ])
      ->from('taxon_lists as tl')
      ->join('taxon_lists_taxa_taxon_list_attributes as tla', [
        'tla.taxon_list_id' => 'tl.id',
        'tla.deleted' => FALSE,
        // If no existing record, deliberately join to nothing.
        'tla.taxa_taxon_list_attribute_id' => empty($values['taxa_taxon_list_attribute:id']) ? -1 : $values['taxa_taxon_list_attribute:id'],
      ], NULL, 'LEFT')
      ->where('tl.deleted', 'f');
    if (!is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
      $qry->in('tl.website_id', $this->auth_filter['values']);
    }
    $taxonLists = $qry
      ->orderby(['tl.title' => 'ASC'])
      ->get()->result_array(TRUE);
    return array_merge(
      $baseData,
      [
        'taxonLists' => $taxonLists,
      ]
    );
  }

}
