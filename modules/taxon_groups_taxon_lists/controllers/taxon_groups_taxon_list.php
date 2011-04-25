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
 * @package	Taxon groups taxon lists
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller class for the taxon groups taxon lists plugin module.
 */
class Taxon_groups_taxon_list_Controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('taxon_groups_taxon_list', 'gv_taxon_groups_taxon_list', 'taxon_groups_taxon_list/index');
    $this->columns = array(
      'title' => 'Taxon Group'
    );
    $this->pagetitle = "Taxon Groups";
    $this->model = ORM::factory('taxon_groups_taxon_list');
  }

  /**
   * This avoids the need for the plugin module to edit the router config file. 
   * @param integer $filter The Id of the taxon list being viewed.
   */
  public function index($filter = null) {
    self::page(1, $filter);
  }
  
  /**
   * Apply the filter for the selected taxon list to the list of groups.
   */
  public function page($page_no, $filter = null) {
    $this->base_filter['taxon_list_id'] = $filter;
    parent::page($page_no, $filter);
  }
  
  /** 
   * Controller action to delete the selected taxon_groups_taxon_lists record.
   */
  public function delete($id) {
    $model = ORM::factory('taxon_groups_taxon_list', $id);
    $model->deleted='t';
    $model->save();    
    // if called with JavaScript enabled then the JS will remove the row from the grid.
    if (!request::is_ajax())
      url::redirect('taxon_list/edit/'.$model->taxon_list_id.'?tab=Taxon_Groups');
  }
  
  /**
   * Override the default action columns for a grid to replace the edit link with a 
   * delete link, since we don't have an edit page.
   */
  protected function get_action_columns() {
    return array(
        'delete' => $this->controllerpath."/delete/#id#"
    );
  }

}

?>