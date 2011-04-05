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
 * Controller providing CRUD access to the list of termlists.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Termlist_Controller extends Gridview_Base_Controller {
  
  public function __construct() {
    parent::__construct('termlist','gv_termlist','termlist/index');
    $this->columns = array(
      'id'=>'',
      'title'=>'',
      'description'=>'',
      'website'=>''
      );
    $this->pagetitle = "Term lists";
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
      $r['termlist:parent_id'] = $_POST['parent_id'];
    }   
    return $r;    
  }

  /**
   *  Auxilliary function for handling Ajax requests from the edit method gridview component.
   */
  public function edit_gv($id,$page_no) {
    $this->auto_render=false;

    $gridmodel = ORM::factory('gv_termlist',$id);

    $grid =	Gridview_Controller::factory($gridmodel, $page_no, 4);
    $grid->base_filter = $this->base_filter;
    $grid->base_filter['parent_id'] = $id;
    $grid->columns = array_intersect_key($grid->columns, array(
      'title'=>'',
      'description'=>''));
    $grid->actionColumns = array(
      'edit' => 'termlist/edit/$id£'
    );
    return $grid->display();
  }
  
  /**
   * Returns an array of all values from this model and its super models ready to be 
   * loaded into a form.  
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    if ($this->model->parent_id) {
      $r['parent_website_id']=$this->model->parent->website_id;
    } 
    return $r;    
  }

  /**
   * Reports if editing a termlist is authorised based on the website id. If a new list,
   * then the parent list's website is used to check authorisation.
   * 
   * @param int $id Id of the termlist that is being checked, or null for a new record.
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
      $termlist = new Termlist_Model($idToCheck);
      return (in_array($termlist->website_id, $this->auth_filter['values']));
    }
    return true;
  }

  /**
   * After a submission, override the default return page behaviour so that if the
   * termlist has a parent id, the edit page for that record is returned to.
   */
  protected function get_return_page() {
    if ($this->model->parent_id != null) {
      return "termlist/edit/".$this->model->parent_id."?tab=sublists";
    } else {
      return $this->model->object_name;
    }
  }
  
  /**
   * Existing entries owned by warehouse are read only, unless you are core admin
   */
  protected function get_read_only($values) {
    return (html::initial_value($values, 'termlist:id') && 
      !$this->auth->logged_in('CoreAdmin') && 
      !html::initial_value($values, 'termlist:website_id'));
  }
  
  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return array(array(
      'controller' => 'termlists_term',
      'title' => 'Terms',
      'views'=>'termlist',
      'actions'=>array('edit')
    ), array(
      'controller' => 'termlist',
      'title' => 'Child lists',
      'views'=>'termlist',
      'actions'=>array('edit')
    ));
  }
}
?>
