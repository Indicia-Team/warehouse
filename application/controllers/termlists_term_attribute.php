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
 * Controller providing CRUD access to the termlists_term attributes.
 */
class Termlists_term_attribute_Controller extends Attr_Base_Controller {

  public function __construct() {
    $this->prefix = 'termlists_term';
    parent::__construct();
    $this->pagetitle = "Term attributes";
    // Override the default columns for custom attributes, as termlists_term
    // attributes are attached to termlists.
    $this->columns = array(
      'id' => '',
      'termlist' => 'Term list',
      'caption' => '',
      'data_type' => 'Data type',
    );
  }

  /**
   * Returns the view specific to taxon attribute edits.
   */
  protected function editViewName() {
    $this->associationsView = new View('templates/attribute_associations_termlist');
    return 'custom_attribute/custom_attribute_edit';
  }

  /**
   * Returns some addition information required by the edit view, which is not
   * associated with a particular record.
   */
  protected function prepareOtherViewData(array $values) {
    $baseData = parent::prepareOtherViewData($values);
    $qry = $this->db
      ->select([
        'tl.id',
        "tl.title || 
          ' (ID=' || tl.id || 
          CASE WHEN tl.website_ID IS NOT NULL THEN
            ', Website=' || websites.title
          ELSE
            ''
          END ||
          ')' as title",
        'tla.id as termlists_termlists_term_attributes_id',
      ])
      ->from('termlists as tl')
      ->join('termlists_termlists_term_attributes as tla', [
        'tla.termlist_id' => 'tl.id',
        'tla.deleted' => FALSE,
         // If no existing record, deliberately join to nothing.
        'tla.termlists_term_attribute_id' => empty($values['termlists_term_attribute:id']) ? -1 : $values['termlists_term_attribute:id'],
      ], NULL, 'LEFT')
      ->join('websites', 'websites.id', 'tl.website_id', 'LEFT')
      ->where('tl.deleted', 'f');
    if (!is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
      $qry->in('tl.website_id', $this->auth_filter['values']);
    }
    $termLists = $qry
      ->orderby(['tl.title' => 'ASC'])
      ->get()->result_array(TRUE);
    return array_merge(
      $baseData,
      [
        'termLists' => $termLists,
      ]
    );
  }

}
