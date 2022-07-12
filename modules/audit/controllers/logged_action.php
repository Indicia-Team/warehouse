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
 * Controller providing Read access to the logged_action list.
 */
class Logged_action_Controller extends Indicia_Controller {

  // This is based loosely on the Gridview base controller, but the filters on the grid are different
  // also no CSV importer.

  private $gridId = NULL;

  /* Constructor. $modelname = name of the model for the grid.
   * $viewname = name of the view which contains the grid. Defaults to the model name + /index.
   * $controllerpath = path the controller from the controllers folder
   * $viewname and $controllerpath can be ommitted if the names are all the same.
   */
  public function __construct($modelname='logged_action', $viewname=NULL, $controllerpath=NULL, $gridId=NULL) {
    $this->model = ORM::factory($modelname);
    $this->modelname = $modelname;
    $this->viewname = is_null($viewname) ? "$modelname/index" : $viewname;
    $this->controllerpath = is_null($controllerpath) ? $modelname : $controllerpath;
    $this->gridId = $gridId;
    $this->base_filter = [];
    $this->auth_filter = null;

    parent::__construct();

    $this->columns = array(
      'id' => 'Event ID',
      'updated_by' => 'Updated By',
      'search_table_name' => 'Table',
      'search_key' => 'ID',
      'action' => 'Action',
      'action_tstamp_tx' => 'When',
      'website_title' => 'Website',
    );
    $this->pagetitle = "Audited data changes";
    $this->set_website_access('admin');
  }

  /**
   * This is the main controller action method for the index page of the grid.
   */
  public function index() {
    $this->view = new View($this->viewname);
    $grid = new View('logged_action/gridview');
    $grid->source = $this->modelname;
    $grid->id = $this->modelname;
    $grid->columns = $this->columns;
    $filter = $this->base_filter;

    // This should set up the allowable websites for this user.
    if (isset($this->auth_filter['field']))
      $filter[$this->auth_filter['field']] = $this->auth_filter['values'];

    if(isset($_GET['transaction_id']) && $_GET['transaction_id'] != '')
      $filter['transaction_id'] = $_GET['transaction_id'];
    else if(isset($_GET['search_table_name']) && $_GET['search_table_name'] != '') {
      $filter['search_table_name'] = $_GET['search_table_name'];
      if(isset($_GET['search_key']) && $_GET['search_key'] != '') {
        $filter['search_key'] = $_GET['search_key'];
      }
    }

    $grid->filter = $filter;
    // Add grid to view.
    $this->view->grid = $grid->render();

    // Templating.
    $this->template->title = $this->pagetitle;
    $this->template->content = $this->view;

    // Setup breadcrumbs.
    $this->page_breadcrumbs[] = html::anchor($this->modelname, $this->pagetitle);
  }

  protected function get_read_only($values) {
    return true;
  }


  protected function loadAttributes(&$r, $in) {
  }

  protected function get_action_columns() {
    return array(
      array(
        'caption'=>'View',
        'url'=>'logged_action/read_top_level/{id}'
      )
    );
  }

  protected function getEditPageTitle($model, $name)
  {
    return $name;
  }


  public function read_top_level($id) {
  // It is possible that only a sub table has been updated, so have to be able to handle that.
    // Could also have had multiple updates on same record in a single transaction.
    $init_model = new Logged_action_Model(array('id' => $id));
    if ( is_null($id) || !$init_model->loaded ) {
      Kohana::show_404();
    }

    // check if website OK for this user.
    if (!is_null($this->auth_filter)) {
      $websites = $this->db
          ->select('website_id')
        ->from(inflector::plural($init_model->object_name).'_websites')
          ->where(array("logged_action_id" => $id))
          ->in('website_id', $this->auth_filter['values'])
          ->get()->as_array(true);
      if(is_null($websites) || count($websites) == 0) {
        $this->access_denied();
        return;
      }
    }

    // we have no idea if this is a top level record. Try to load all TLR which match the search
    $values = $this->db
        ->select('*')
      ->from(inflector::plural($init_model->object_name))
      ->where(array("transaction_id" => $init_model->transaction_id,
            "event_table_name" => $init_model->search_table_name,
            "event_record_id" => $init_model->search_key))
      ->orderby(array("transaction_id"=>'ASC'))
      ->get()->as_array(true);
    // Try to load all non-TLR which match the search
    $subtableData = $this->db
      ->select('*')
      ->from(inflector::plural($init_model->object_name))
      ->where(array("transaction_id" => $init_model->transaction_id,
          "search_table_name" => $init_model->search_table_name,
          "search_key" => $init_model->search_key
      ))
      ->where("(event_table_name != '$init_model->search_table_name' OR event_record_id != '$init_model->search_key')")
      ->orderby(array("transaction_id"=>'ASC'))
      ->get()->as_array(true);

    $websites = $this->db
      ->select('website_id, w.title')
      ->from(inflector::plural($init_model->object_name).'_websites')
      ->join('websites as w', 'w.id', 'website_id')
      ->where(array("logged_action_id" => $id))
      ->get()->as_array(true);

    $this->setView($this->readTopLevelViewName(),
        "Show Top Level Auditing Record for Event ".$id,
        array(
      'existing'=>$this->model->loaded,
      'values'=>$values,
      'subtableData'=>$subtableData,
      'websites'=>$websites,
      'search'=>array("transaction_id" => $init_model->transaction_id,
            "search_table_name" => $init_model->search_table_name,
            "search_key" => $init_model->search_key)
    ));
    // Home is automatically added to front.
    $this->page_breadcrumbs[] = html::anchor($this->model->object_name, $this->pagetitle);
    $this->page_breadcrumbs[] = html::anchor($this->model->object_name.'?transaction_id='.$init_model->transaction_id, "Transaction ".$init_model->transaction_id);
    $this->page_breadcrumbs[] = html::anchor($this->model->object_name.'?search_table_name='.$init_model->search_table_name.'&search_key='.$init_model->search_key, ucfirst($init_model->search_table_name)." ".$init_model->search_key);
    $this->page_breadcrumbs[] = $id;
  }

  public function read($id) {
    // In this function we just want the exact record.
    $this->model = new Logged_action_Model(array('id' => $id));
    if ( is_null($id) || !$this->model->loaded ) {
      Kohana::show_404();
    }

    // check if website OK for this user.
    if (!is_null($this->auth_filter)) {
      $websites = $this->db
        ->select('website_id')
        ->from(inflector::plural($this->model->object_name).'_websites')
        ->where(array("logged_action_id" => $id))
        ->in('website_id', $this->auth_filter['values'])
        ->get()->as_array(true);
      if(is_null($websites) || count($websites) == 0) {
        $this->access_denied();
        return;
      }
    }

    $websites = $this->db
      ->select('website_id, w.title')
      ->from(inflector::plural($this->model->object_name).'_websites')
      ->join('websites as w', 'w.id', 'website_id')
      ->where(array("logged_action_id" => $id))
      ->get()->as_array(true);

    $values = $this->getModelValues();
    $this->setView($this->readViewName(),
        "Show Auditing Record for Event ".$id." (".ucfirst($values["logged_action:search_table_name"])." ".$values["logged_action:search_key"].")",
        array('values'=>$values,'websites'=>$websites));
    // Home is automatically added to front.
    $this->page_breadcrumbs[] = html::anchor($this->model->object_name, $this->pagetitle);
    $this->page_breadcrumbs[] = html::anchor($this->model->object_name.'/read_top_level/'.$id, 'Top Level Record for Event '.$id);
    $this->page_breadcrumbs[] = $this->model->caption();
  }

  /**
   * Returns the name for the read view.
   */
  protected function readTopLevelViewName() {
    $mn = $this->model->object_name;
    return $mn."/".$mn."_readTL";
  }

  /**
   * Returns the name for the read view.
   */
  protected function readViewName() {
    $mn = $this->model->object_name;
    return $mn."/".$mn."_read";
  }

  public function edit($id){
    Kohana::show_404();
  }
  public function create(){
    Kohana::show_404();
  }
  public function importer() {
    Kohana::show_404();
  }

  /**
   * You can only access the list of logged_actions if at least an editor of one website.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }

}