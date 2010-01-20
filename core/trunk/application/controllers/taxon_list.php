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
 * @package	Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller providing CRUD access to the list of taxon checklists.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Taxon_list_Controller extends Gridview_Base_Controller {
  
  public function __construct() {
    parent::__construct('taxon_list','taxon_list','taxon_list/index');
    $this->columns = array(
      'id'=>'',
      'title'=>'',
      'description'=>'');
    $this->pagetitle = "Species lists";
    $this->model = ORM::factory('taxon_list');
    $this->auth_filter = $this->gen_auth_filter;
  }
  
  /**
   * Returns an array of all values from this model and its super models ready to be 
   * loaded into a form. For this controller, we need to also setup the child taxon lists grid   
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    // Configure the grid
    $grid =	Gridview_Controller::factory($this->model, 1, 4);
    $grid->base_filter = array('deleted' => 'f', 'parent_id' => $this->model->id);
    $grid->columns =  $this->columns;
    $grid->actionColumns = array(
      'edit' => 'taxon_list/edit/£id£'
    );
    $r['table'] = $grid->display();
    if ($this->model->parent_id) {
      $r['parent_website_id']=$this->model->parent->website_id;
    } 
    return $r;    
  }
  
  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   *  In this case, the parent_id and website_id are passed as $_POST data if creating 
   *  a new sublist.   
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create' && array_key_exists('parent_id', $_POST)) {
      // Parent_id and website_id are passed in as POST params for a new record.
      $r['taxon_list:parent_id'] = $_POST['parent_id'];
      $r['taxon_list:website_id'] = $_POST['website_id'];
      $r['parent_website_id']=ORM::factory('taxon_list', $_POST['parent_id'])->website_id;
    }   
    return $r;    
  }

  /**
   * Auxilliary function for handling Ajax requests from the edit method child lists gridview component
   */
  public function edit_gv($id,$page_no) {
    $this->auto_render=false;
    $model = ORM::factory('taxon_list',$id);
    $grid =	Gridview_Controller::factory($model, $page_no, 4);
    $grid->base_filter = array('deleted' => 'f', 'parent_id' => $id);
    $grid->columns = array_intersect_key($grid->columns, array(
      'title'=>'',
      'description'=>''));
    $grid->actionColumns = array(
      'edit' => 'taxon_list/edit/£id£'
    );
    return $grid->display();
  }

  /**
   * Reports if editing a taxon list is authorised based on the website id. If a new list,
   * then the parent list's website is used to check authorisation.
   * 
   * @param int $id Id of the taxon list that is being checked, or null for a new record.
   */
  protected function record_authorised($id)
  {    
    if (!$id && array_key_exists('parent_id', $_POST)) {
      $idToCheck=$_POST['parent_id'];
    } else {
      $idToCheck=$id;
    }    
    if (!is_null($idToCheck) AND !is_null($this->auth_filter))
    {
      $taxon_list = new Taxon_list_Model($idToCheck);
      return (in_array($taxon_list->website_id, $this->auth_filter['values']));
    }
    return true;
  }

  /**
   * After a submission, override the default return page behaviour so that if the
   * list has a parent id, the edit page for that record is returned to with the sublists
   * tab selected.
   */
  protected function get_return_page() {
    if ($this->model->parent_id != null) {
      return "taxon_list/edit/".$this->model->parent_id."?tab=sublists";
    } else {
      return $this->model->object_name;
    }
  }
}
?>
