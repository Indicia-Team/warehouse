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
  public function __construct($modelname, $viewname=NULL, $controllerpath=NULL, $gridId=NULL) {
    $this->model=ORM::factory($modelname);
    $this->modelname = $modelname;
    $this->viewname=is_null($viewname) ? $modelname : $viewname;
    $this->controllerpath=is_null($controllerpath) ? $modelname : $controllerpath;
    $this->gridId = $gridId;
    $this->base_filter = array();
    $this->auth_filter = null;
    $this->pagetitle = "Abstract gridview class - override this title!";

    parent::__construct();
  }

  /**
   * This is the main controller action method for the index page of the grid.
   */
  public function index() {
    $this->view = new View($this->viewname);
    $this->add_upload_csv_form();
    $grid = new View('gridview');
    $grid->source = $this->modelname;
    $grid->id = $this->modelname;
    $grid->columns = $this->columns;
    $filter = $this->base_filter;
    if (isset($this->auth_filter['field']))
      $filter[$this->auth_filter['field']] = $this->auth_filter['values'];
    $grid->filter = $filter;
    // Add grid to view
    $this->view->grid = $grid->render();

    // Templating
    $this->template->title = $this->pagetitle;
    $this->template->content = $this->view;
    
    // Setup breadcrumbs
    $this->page_breadcrumbs[] = html::anchor($this->modelname, $this->pagetitle);    
  }

  /**
   * Return the default action columns for a grid - just an edit link. If required,
   * override this in controllers to specify a different set of actions.
   */
  protected function get_action_columns() {
    return array(
      array(
        'caption' => 'edit',
        'url'=>$this->controllerpath."/edit/{id}"
      )
    );
  }

  /**
   * Sets the list of websites the user has access to according to the requested role.
   */
  protected function set_website_access($level='admin') {
    // If not logged in as a Core admin, restrict access to available websites.
    if ($this->auth->logged_in('CoreAdmin')) 
      $this->auth_filter = null;
    else {
      $ids = $this->get_allowed_website_id_list($level);
      $this->auth_filter = array('field' => 'website_id', 'values' => $ids);
    }
  }
  
  /**
   * Gets a list of the website IDs a user can access at a certain level.
   */
  protected function get_allowed_website_id_list($level, $includeNull=true) {
    if ($this->auth->logged_in('CoreAdmin'))
      return null;
    else {
      switch ($level) {
        case 'admin': $role=1; break;
        case 'editor': $role=2; break;
        case 'user': $role=3; break;
      }
      $user_websites = ORM::factory('users_website')->where(
          array('user_id' => $_SESSION['auth_user']->id,
          'site_role_id <=' => $role, 'site_role_id IS NOT' => NULL))->find_all();
      $ids = array();
      foreach ($user_websites as $user_website) {
        $ids[] = $user_website->website_id;
      }
      if ($includeNull) {
        // include a null to allow through records which have no associated website.
        $ids[] = null;
      }
      return $ids;
    }
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
