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
 * @package  Core
 * @subpackage Controllers
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Base class for controllers which support paginated grids of any datatype. Also
 * supports basic CSV data upload into the grid's underlying model.
 *
 * @package  Core
 * @subpackage Controllers
 */
abstract class Gridview_Base_Controller extends Indicia_Controller {

  private $gridId = null;

  /* Constructor. $modelname = name of the model for the grid.
   * $viewname = name of the view which contains the grid.
   * $controllerpath = path the controller from the controllers folder
   * $viewname and $controllerpath can be ommitted if the names are all the same.
   */
  public function __construct($modelname, $gridmodelname=NULL, $viewname=NULL, $controllerpath=NULL, $gridId=NULL) {
    $this->model=ORM::factory($modelname);
    $this->gridmodelname=is_null($gridmodelname) ? $modelname : $gridmodelname;
    $this->viewname=is_null($viewname) ? $modelname : $viewname;
    $this->controllerpath=is_null($controllerpath) ? $modelname : $controllerpath;
    $this->gridId = $gridId;
    $this->pageNoUriSegment = 3;
    $this->base_filter = array('deleted' => 'f');
    $this->auth_filter = null;
    $this->pagetitle = "Abstract gridview class - override this title!";

    parent::__construct();
    $this->get_auth();
  }

  /**
   * This is the main controller action method for the index page of the grid.
   */
  public function page($page_no, $filter=null) {
    $this->prepare_grid_view();
    $this->add_upload_csv_form();
    
    $grid =  Gridview_Controller::factory($this->gridmodel,
        $page_no,
        $this->pageNoUriSegment,
        $this->gridId);
    $grid->base_filter = $this->base_filter;
    $grid->auth_filter = $this->auth_filter;
    $grid->columns = array_intersect_key($this->columns, $grid->columns);
    $grid->actionColumns = $this->get_action_columns();
    if (isset($this->fixedSort)) {
      $grid->fixedSort=$this->fixedSort;
      $grid->fixedSortDir=$this->fixedSortDir;
    }

    // Add table to view
    $this->view->table = $grid->display(true);

    // Templating
    $this->template->title = $this->pagetitle;
    $this->template->content = $this->view;
    
    // Setup breadcrumbs
    $this->page_breadcrumbs[] = html::anchor($this->gridmodelname, $this->pagetitle);
  }

  protected function prepare_grid_view() {
    $this->view = new View($this->viewname);
    $this->gridmodel = ORM::factory($this->gridmodelname);
    if (!$this->columns) {
      // If the controller class has not defined the list of columns, use the entire list as a default
      $this->columns = $this->gridmodel->table_columns;
    }
  }

  /**
   * Return the default action columns for a grid - just an edit link. If required,
   * override this in controllers to specify a different set of actions.
   */
  protected function get_action_columns() {
    return array('edit' => $this->controllerpath."/edit/£id£");
  }
  
  /**
   * Override to control the visibility of each action.
   * @param Array $row Row data in an associative array.
   * @param string $actionName Name of the action to check for visibility in this row.
   */
  protected function get_action_visibility($row, $actionName) {
    return true;
  }
  

  /**
   * Method to retrieve pages for the index grid of taxa_taxon_list entries from an AJAX
   * pagination call.
   */
  public function page_gv($page_no, $filter=null) {
    $this->prepare_grid_view();
    $this->auto_render = false;
    $grid = Gridview_Controller::factory($this->gridmodel, $page_no, $this->pageNoUriSegment, $this->gridId);
    $grid->base_filter = $this->base_filter;
    $grid->auth_filter = $this->auth_filter;
    $grid->columns = array_intersect_key($this->columns, $grid->columns);
    $grid->actionColumns = $this->get_action_columns();
    return $grid->display();
  }

  /**
   * Retrieve the list of websites the user has access to. The list is then stored in
   * $this->gen_auth_filter. Also checks if the user is core admin.
   */
  protected function get_auth() {
    // If not logged in as a Core admin, restrict access to available websites.
    if(!$this->auth->logged_in('CoreAdmin')){
      $site_role = (new Site_role_Model('Admin'));
      $websites=ORM::factory('users_website')->where(
      array('user_id' => $_SESSION['auth_user']->id,
              'site_role_id' => $site_role->id))->find_all();
      $website_id_values = array();
      foreach($websites as $website)
        $website_id_values[] = $website->website_id;
      $website_id_values[] = null;
      $this->gen_auth_filter = array('field' => 'website_id', 'values' => $website_id_values);
    }
    else $this->gen_auth_filter = null;    
  }

  /**
   * Adds the upload csv form to the view (which should then insert it at the bottom of the grid).
   */
  protected function add_upload_csv_form() {
    $this->upload_csv_form = new View('templates/upload_csv');
    $this->upload_csv_form->returnPage = 1;
    $this->upload_csv_form->staticFields = null;
    $this->upload_csv_form->controllerpath = $this->controllerpath;
    $this->view->upload_csv_form = $this->upload_csv_form;
  }
  
  /**
   * Overridable function to determine if an edit page should be read only or not.
   * @return boolean True if edit page should be read only.
   */
  protected function get_read_only($values) {
    return false;   
  }
  
  /** 
   * Controller function to display a generic import wizard for any data.
   */
  public function importer() {
    $this->SetView('importer', '', array('model'=>$this->controllerpath));
    $this->template->title=$this->pagetitle.' Import';
    // Setup a breadcrumb as if we are in the edit page since this will give us the correct links upwards
    $this->defineEditBreadcrumbs();
    // but make it clear the bottom level breadcrumb is the importer
    $this->page_breadcrumbs[count($this->page_breadcrumbs)-1] = kohana::lang('misc.model_import', $this->model->caption());
  }
  
  /**
   * Loads the custom attributes for a sample, location or occurrence into the load array. Also sets up
   * any lookup lists required.
   * This is only called by sub-classes for entities that have associated attributes.
   */
  protected function loadAttributes(&$r) {
    // Grab all the custom attribute data
    $attrs = $this->db->
        from('list_'.$this->model->object_name.'_attribute_values')->
        where($this->model->object_name.'_id', $this->model->id)->
        get()->as_array(false);
    $r['attributes'] = $attrs;
    foreach ($attrs as $attr) {
      // if there are any lookup lists in the attributes, preload the options     
      if (!empty($attr['termlist_id'])) {
        $r['terms_'.$attr['termlist_id']]=$this->get_termlist_terms($attr['termlist_id']);
        $r['terms_'.$attr['termlist_id']][''] = '-no value-';
      }
    }
  }

}
