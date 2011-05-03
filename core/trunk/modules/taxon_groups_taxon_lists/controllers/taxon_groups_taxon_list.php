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
 * Calling modes:
 * index - taxon_list_taxon_group/1 where 1 is the taxon_list_id
 */
class Taxon_groups_taxon_list_Controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('taxon_groups_taxon_list', 'taxon_groups_taxon_list/index', 
        null, 'taxon_groups_taxon_list');
    $this->columns = array(
      'title' => 'Taxon Group'
    );
    $this->pagetitle = "Taxon Groups";
  }
  
  /**
   * Apply the filter for the selected taxon list to the list of groups.
   */
  public function index() {
    if ($this->uri->total_arguments()>0) {
      $this->taxon_list_id = $this->uri->argument(1);
      $this->base_filter['taxon_list_id'] = $this->taxon_list_id;
    }
    parent::index();
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
      array(
        'caption' => 'delete',
        'url' => $this->controllerpath."/delete/{id}"
      )
    );
  }
  
  /**
   * Controller action for AJAX to add a taxon group to this list.
   */
  public function add_taxon_group() {
    // no template as this is for AJAX
    $this->auto_render=false;
    $new = ORM::factory('taxon_groups_taxon_list');
    $data['taxon_list_id']=$_POST['taxon_list_id'];
    $data['taxon_group_id']=$_POST['taxon_group_id'];
    if ($new->validate(new Validation($data), true))
      echo $new->id;
    else {
      echo implode("\n", array_values($new->getAllErrors()));
    }
  }

}

?>