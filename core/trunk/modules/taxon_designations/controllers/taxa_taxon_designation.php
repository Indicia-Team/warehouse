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
 * @package	Taxon Designations
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller class for the taxon designations plugin module list of designations
 * for a taxon.
 */
class Taxa_taxon_designation_Controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('taxa_taxon_designation', 'gv_taxa_taxon_designation', 'taxa_taxon_designation/index');
    $this->columns = array(
      'id'          => '',
      'title'       => '',
      'category'    => ''
    );
    $this->pagetitle = "Taxon Designations";
    $this->model = ORM::factory('taxa_taxon_designation');
    $this->auth_filter = $this->gen_auth_filter;
  }

  /**
   * To save the plugin needing to modify the routes config file, we write a hard-
   * coded routing from index to the page function for page 1. The filter here
   * should be the taxa_taxon_list_id.
   * @param <type> $filter
   */
  public function index($filter = null) {
    self::page(1, $filter);
  }

  public function  page($page_no, $filter = null) {
    $ttl = ORM::Factory('taxa_taxon_list', $filter);
    $this->base_filter['taxa_taxon_list_id'] = $filter;
    parent::page($page_no, $filter);
    $this->view->taxon_id = $ttl->taxon_id;
  }

  /**
   * Get the list of designations ready to pick from.
   */
  protected function prepareOtherViewData($values)
  {
    $results=$this->db->select('taxon_designations.id, taxon_designations.title')
        ->from('taxon_designations')
        ->orderby (array('taxon_designations.title'=>'ASC'))
        ->get();
    $designations=array();
    foreach ($results as $row) {
      $designations[$row->id]=$row->title;
    }
    return array(
      'designations' => $designations
    );
  }

  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create') {
      // Taxon id is passed as first argument in URL when creating
      $r['taxa_taxon_designation:taxon_id'] = $this->uri->argument(1);
    }
    return $r;
  }

}