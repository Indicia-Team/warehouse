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
    $this->auth_filter = $this->gen_auth_filter;
  }
  
  /** 
   * Override the index page controller action to add filters for the parent list if viewing the child lists.
   */
  public function page($page_no, $filter=null) {
    // This constructor normally has 1 argument which is the grid page. If there is a second argument
    // then it is the parent list ID. 
    if ($this->uri->total_arguments()>1) {
      $this->base_filter=array('parent_id' => $this->uri->argument(2));
    }
    parent::page($page_no, $filter);
    // pass the parent id into the view, so the create list button can use it to autoset
    // the parent of the new list.
    if ($this->uri->total_arguments()>1) {
      $parent_id = $this->uri->argument(2);
      $this->view->parent_id=$parent_id;
    }
  }
  
  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   *  In this case, the parent_id and website_id are passed as $_POST data if creating 
   *  a new sublist.   
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create' && array_key_exists('parent_id', $_POST)) {
      // Parent_id is passed in as POST params for a new record.
      $r['taxon_list:parent_id'] = $_POST['parent_id'];
    }
    return $r;    
  }
  
  /**
   * Get a list of the websites that the user is allowed to assign this checklist to.
   */
  protected function prepareOtherViewData($values)
  { 
    $websites = ORM::factory('website');
    $parent_id=html::initial_value($values, 'taxon_list:parent_id');    
    if ($parent_id != null) {
      // parent list already has a link to a website, so we can't change it 
      $websites = $websites->in('id', ORM::factory('taxon_list', $parent_id)->website_id);
    } else {
      if (!$this->auth->logged_in('CoreAdmin'))
        $websites = $websites->in('id',$this->auth_filter['values']);
    }
    return array(
      'websites' => $websites->where('deleted','false')->orderby('title','asc')->find_all()
    );
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
   * Must be core admin or website editor/admin to use the taxon lists.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('editor');
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
  
  /**
   * Existing entries owned by warehouse are read only, unless you are core admin
   */
  protected function get_read_only($values) {
    return (html::initial_value($values, 'taxon_list:id') && 
      !$this->auth->logged_in('CoreAdmin') && 
      !html::initial_value($values, 'taxon_list:website_id'));
  }
  
  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return array(array(
      'controller' => 'taxa_taxon_list',
      'title' => 'Taxa',
      'views'=>'taxon_list',
      'actions'=>array('edit')
    ), array(
      'controller' => 'taxon_list',
      'title' => 'Child lists',
      'views'=>'taxon_list',
      'actions'=>array('edit')
    ));
  }
}
?>
