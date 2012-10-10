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
 * for a taxon. Calling modes:
 * index - taxa_taxon_designation/1 where 1 is the taxa_taxon_list_id
 * create - taxa_taxon_designation/create/1 where 1 is the taxa_taxon_list_id
 * edit - taxa_taxon_designation/edit/1?taxa_taxon_list_id=2 where 1 is the taxa_taxon_designation_id and 2 is the taxa_taxon_list_id.
 * Passing the taxa taxon list id like this allows the return page and breadcrumb to be set correctly, because the designation is 
 * bound to a taxon which is not specific to a list.
 */
class Taxa_taxon_designation_Controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('taxa_taxon_designation', 'taxa_taxon_designation/index');
    $this->columns = array(
      'id'          => '',
      'title'       => '',
      'category'    => ''
    );
    $this->pagetitle = "Taxon Designations";
  }
  
  /**
   * Override loading of action columns to ensure the taxa taxon list id is passed to the edit view.
   */
  protected function get_action_columns() {
    // taxa taxon list ID should be the first argument from the index view
    return array(array('caption' => 'edit', 'url' => $this->controllerpath."/edit/{id}?taxa_taxon_list_id=".$this->uri->argument(1)));
  }

  public function index($filter = null) {
    $ttl = ORM::Factory('taxa_taxon_list', $filter);
    $this->base_filter['taxa_taxon_list_id'] = $filter;
    parent::index($filter);
    $this->view->taxa_taxon_list_id = $ttl->id;
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
    // also setup a taxon name
    $this->taxon_name = ORM::Factory('taxon', $values['taxa_taxon_designation:taxon_id'])->caption();
    if ($this->uri->method(false)=='create') 
      // Taxa taxon list id is passed as first argument in URL when creating
      $ttl_id=$this->uri->argument(1);
    else
      $ttl_id = $_GET['taxa_taxon_list_id'];
    $this->taxon_list_id = ORM::Factory('taxa_taxon_list', $ttl_id)->taxon_list_id;
    return array(
      'designations' => $designations,
      'taxon_name' => $this->taxon_name,
      'taxon_list_id' => $this->taxon_list_id
    );
  }

  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create') {
      // Taxa taxon list id is passed as first argument in URL when creating
      $ttl = ORM::Factory('taxa_taxon_list', $this->uri->argument(1));
      $r['taxa_taxon_designation:taxon_id'] = $ttl->taxon_id;
    }
    return $r;
  }
  
  /**
   * After editing a taxon's designation, return to the designations tab of the taxon's edit page.
   */
  protected function get_return_page() {
    $ttl = ORM::Factory('taxa_taxon_list')->where(array(
        'taxon_id'=>$_POST['taxa_taxon_designation:taxon_id'],
        'taxon_list_id' => $_POST['taxon_list_id']
    ))->find();
    return 'taxa_taxon_list/edit/'.$ttl->id.'?tab=Designations';
  }
  
  /**
   * Set the edit page breadcrumbs to link back through the species and checklist.
   */
  protected function defineEditBreadcrumbs() { 
    $this->page_breadcrumbs[] = html::anchor('taxon_list', 'Species Lists');
    $listTitle = ORM::Factory('taxon_list', $this->taxon_list_id)->title;
    $this->page_breadcrumbs[] = html::anchor('taxon_list/edit/'.$this->taxon_list_id.'?tab=taxa', $listTitle);
    if ($this->uri->method(false)=='create') {
      $ttl_id = $this->uri->argument(1);
    } else {
      echo $this->taxon_list_id;
      $ttl_id = ORM::Factory('taxa_taxon_list')->where(array(
          'taxon_id' => $this->model->taxon_id,
          'taxon_list_id' => $this->taxon_list_id
      ))->find()->id;
    }
    $this->page_breadcrumbs[] = html::anchor('taxa_taxon_list/edit/'.$ttl_id.'?tab=Designations', $this->taxon_name);
    $this->page_breadcrumbs[] = $this->model->caption();
  }

}