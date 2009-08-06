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

 defined('SYSPATH') or die('No direct script access.');

/**
 * Base class for controllers which support paginated grids of any datatype. Also
 * supports basic CSV data upload into the grid's underlying model.
 *
 * @package	Core
 * @subpackage Controllers
 */
abstract class Gridview_Base_Controller extends Indicia_Controller {

  /* Constructor. $modelname = name of the model for the grid.
   * $viewname = name of the view which contains the grid.
   * $controllerpath = path the controller from the controllers folder
   * $viewname and $controllerpath can be ommitted if the names are all the same.
   */
  public function __construct($modelname, $gridmodelname=NULL, $viewname=NULL, $controllerpath=NULL) {
    $this->model=ORM::factory($modelname);
    $this->gridmodelname=is_null($gridmodelname) ? $modelname : $gridmodelname;
    $this->viewname=is_null($viewname) ? $modelname : $viewname;
    $this->controllerpath=is_null($controllerpath) ? $modelname : $controllerpath;
    $this->pageNoUriSegment = 3;
    $this->base_filter = array('deleted' => 'f');
    $this->auth_filter = null;
    $this->gen_auth_filter = null;
    $this->pagetitle = "Abstract gridview class - override this title!";

    parent::__construct();
  }

  protected function page_authorised()
  {
    return $this->auth->logged_in();
  }

  /**
   * This is the main controller action method for the index page of the grid.
   */
  public function page($page_no, $limit) {
    if ($this->page_authorised() == false) {
      $this->access_denied();
      return;
    }
    $this->prepare_grid_view();

    $grid =	Gridview_Controller::factory($this->gridmodel,
      $page_no,
      $limit,
      $this->pageNoUriSegment);
    $grid->base_filter = $this->base_filter;
    $grid->auth_filter = $this->auth_filter;
    $grid->columns = array_intersect_key($this->columns, $grid->columns);
    $grid->actionColumns = $this->actionColumns;

    // Add table to view
    $this->view->table = $grid->display(true);

    // Templating
    $this->template->title = $this->pagetitle;
    $this->template->content = $this->view;
  }

  protected function prepare_grid_view() {
    $this->get_auth_websites();
    $this->view = new View($this->viewname);
    $this->gridmodel = ORM::factory($this->gridmodelname);
    if (!$this->columns) {
      // If the controller class has not defined the list of columns, use the entire list as a default
      $this->columns = $this->gridmodel->table_columns;
    }
    $this->actionColumns = array('edit' => $this->controllerpath."/edit/£id£");
    $this->add_upload_csv_form();
  }

  public function page_gv($page_no, $limit) {
    $this->auto_render = false;
    $grid =	Gridview_Controller::factory($this->gridmodel,
      $page_no,
      $limit,
      $this->pageNoUriSegment);
    $grid->base_filter = $this->base_filter;
    $grid->auth_filter = $this->auth_filter;
    $grid->columns = array_intersect_key($this->columns, $grid->columns);
    $grid->actionColumns = $this->actionColumns;
    return $grid->display();
  }

  /**
   * Retrieve the list of websites the user has access to. The list is then stored in
   * $this->gen_auth_filter.
   */
  protected function get_auth_websites() {
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
   * Controller action to build the page that allows each field in an uploaded CSV
   * file to be mapped to the appropriate model attributes.
   */
  public function upload_mappings() {
    $_FILES = Validation::factory($_FILES)
      ->add_rules('csv_upload', 'upload::valid',
               'upload::required', 'upload::type[csv]', 'upload::size[1M]');
    if ($_FILES->validate()) {
      // move the file to the upload directory
      $csvTempFile = upload::save('csv_upload');
      $_SESSION['uploaded_csv'] = $csvTempFile;

      // Following helps for files from Macs
      ini_set('auto_detect_line_endings',1);
      $handle = fopen($csvTempFile, "r");
      $this->template->title = "Map CSV File columns to ".$this->pagetitle;
      $view = new View('upload_mappings');
      $view->columns = fgetcsv($handle, 1000, ",");
      fclose($handle);
      $view->model = $this->model;
      $view->controllerpath = $this->controllerpath;
      $this->template->content = $view;
    } else {
      // TODO: error message needs a back button.
      $this->setError('File missing', 'Please select a CSV file to upload before clicking the Upload button.');
    }
  }

  /**
   * Controller action that performs the import of data in an uploaded CSV file.
   */
  public function upload() {
    $csvTempFile = $_SESSION['uploaded_csv'];
    // make sure the file still exists
    if (file_exists($csvTempFile))
    {
      // Following helps for files from Macs
      ini_set('auto_detect_line_endings',1);
      // create the file pointer
      $handle = fopen ($csvTempFile, "r");
      // skip the header row
      $headers = fgetcsv($handle, 1000, ",");
      $problems = array();
      $count=0;
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $count++;
        $index = 0;
        $saveArray = array();
        foreach ($_POST as $col=>$attr) {
          if (isset($data[$index])) {
            if ($attr!='<please select>') {
              if (strpos($attr, '.')!==false) {
                // This is an attribute for another record, e.g. taxon.language_id
                $tokens = explode('.', $attr);
                $extraRecords[$tokens[0]][$tokens[1]]=$data[$index];
              } else {
                // Add the data to the main record save array
                $saveArray[$attr] = $data[$index];
              }
            }
          } else {
            // This is one of our static fields at the end
            $saveArray[$col] = $attr;
          }
          $index++;
        }
        // Save the record
        $this->model->clear();
        $this->model->submission = $this->wrap($saveArray, true);
        if (($id = $this->model->submit()) != null) {
          // Record has saved correctly
          $this->submit_succ($id);
        } else {
          // Record has errors - now embedded in model
          $this->submit_fail();
          array_push($data, implode('<br/>', $this->model->getAllErrors()));
            array_push($problems, $data);
        }
      }
      fclose($handle);

      // clean up the uploaded file
      unlink($csvTempFile);
      if (count($problems)>0) {
        $view = new View('upload_problems');
        $view->headers = $headers;
        $view->problems = $problems;
        $this->template->title = "Upload Problems";
        $this->template->content = $view;
      } else {
        $this->session->set_flash('flash_info', "The upload was successful. $count records were uploaded.");
        url::redirect($this->controllerpath);
      }
    }

  }
}
