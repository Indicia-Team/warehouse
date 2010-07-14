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
    $this->pagetitle = "Abstract gridview class - override this title!";

    parent::__construct();
    $this->get_auth();
  }

  protected function page_authorised()
  {
    return $this->auth->logged_in();
  }

  /**
   * This is the main controller action method for the index page of the grid.
   */
  public function page($page_no, $filter=null) {
    if ($this->page_authorised() == false) {
      $this->access_denied();
      return;
    }
    $this->prepare_grid_view();
    $this->add_upload_csv_form();
    
    $grid =  Gridview_Controller::factory($this->gridmodel,
        $page_no,
        $this->pageNoUriSegment);
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
   * Method to retrieve pages for the index grid of taxa_taxon_list entries from an AJAX
   * pagination call. Overrides the base class behaviour to enforce a filter on the
   * taxon list id.
   */
  public function page_gv($page_no, $filter=null) {
    $this->prepare_grid_view();
    $this->auto_render = false;
    $grid =  Gridview_Controller::factory($this->gridmodel,
    $page_no,
    $this->pageNoUriSegment);
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
   * Controller action to build the page that allows each field in an uploaded CSV
   * file to be mapped to the appropriate model attributes.
   */
  public function upload_mappings() {
    $_FILES = Validation::factory($_FILES)->add_rules(
        'csv_upload', 'upload::valid', 'upload::required', 'upload::type[csv]', 'upload::size[1M]'
    );
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
      $view->onCompletePage = 'test.php';
      fclose($handle);
      $view->model = $this->model;
      $view->controllerpath = $this->controllerpath;
      $this->template->content = $view;
      // Setup a breadcrumb
      $this->page_breadcrumbs[] = html::anchor($this->model->object_name, $this->pagetitle);
      $this->page_breadcrumbs[] = 'Setup upload';
    } else {
      // TODO: error message needs a back button.
      $this->setError('File missing', 'Please select a CSV file to upload before clicking the Upload button.');
    }
  }
  
  /**
   * Accepts a post array of column to attribute mappings and stores it for use during a chunked upload. This action 
   * is called by the JavaScript code responsible for a chunked upload, before the upload actually starts.
   */
  public function cache_upload_mappings() {
    $this->auto_render=false;
    $mappingFile = str_replace('.csv','-map.txt',$_GET['uploaded_csv']);
    $mappingHandle = fopen(DOCROOT . "upload/$mappingFile", "w");
	fwrite($mappingHandle, json_encode($_POST));
    fclose($mappingHandle);
    echo "OK";
  }

  /**
   * Controller action that performs the import of data in an uploaded CSV file.
   * Allows $_GET parameters to specify the offset and limit when uploading just a chunk at a time.
   * This method is called to perform the entire upload when JavaScript is not enabled, or can 
   * be called to perform part of an AJAX csv upload where only a part of the data is imported
   * on each call.
   */
  public function upload() {
    $csvTempFile = isset($_GET['uploaded_csv']) ? DOCROOT . "upload/" . $_GET['uploaded_csv'] : $_SESSION['uploaded_csv'];
    $mappings = $this->_get_mappings($csvTempFile);
    // make sure the file still exists
    if (file_exists($csvTempFile))
    {
      // Following helps for files from Macs
      ini_set('auto_detect_line_endings',1);
      // create the file pointer, plus one for errors
      $handle = fopen ($csvTempFile, "r");
      $errorHandle = $this->_get_error_file_handle($csvTempFile, $handle);
      $count=0;
      $limit = (isset($_GET['limit']) ? $_GET['limit'] : false);
      $offset = (isset($_GET['offset']) ? $_GET['offset'] : 0);
      // skip rows to allow for the offset
      while ($count<$offset && fgetcsv($handle, 1000, ",") !== FALSE) {
        $count++;
      }
      $count=0;
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && ($limit===false || $count<$limit)) {
        kohana::log('debug', 'Importing row data: '.implode(' | ', $data));
        $count++;
        $index = 0;
        $saveArray = $this->getDefaults();
        // Note, the mappings will always be in the same order as the columns of the CSV file
        foreach ($mappings as $col=>$attr) {
          if (isset($data[$index])) {
            if ($attr!='<please select>') {
              // Add the data to the record save array
              $saveArray[$attr] = utf8_encode($data[$index]);
            }
          } else {
            // This is one of our static fields at the end
            $saveArray[$col] = $attr;
          }
          $index++;
        }
        // Any $_GET data that contains field data should also go in the save array. For example, we may want to specify
        // the taxon list id for all imported taxon records. Valid data should always be keyed table:field. 
        foreach($_GET as $key=>$value) {
          if (strpos($key, ':')!==false) $saveArray[$key] = $value;
        }
        // Save the record
        $this->model->clear();
        $this->model->set_submission_data($saveArray, true);
        if (($id = $this->model->submit()) == null) {
          // Record has errors - now embedded in model, so dump them into the error file
          $errors = implode('<br/>', $this->model->getAllErrors());
          $data[] = $errors;
          $data[] = $count + $offset + 1; // 1 for header
          fputcsv($errorHandle, $data);
          kohana::log('debug', 'Failed to import CSV row: '.$errors);
        }
      }
      // Get percentage progress
      $progress = ftell($handle) * 100 / filesize($csvTempFile);
      fclose($handle);
      fclose($errorHandle);
      if (request::is_ajax()) {
        // An AJAX upload request will just receive the number of records uploaded and progress
        $this->auto_render=false;
        echo "{uploaded:$count,progress:$progress}";
        kohana::log('debug', "{uploaded:$count,progress:$progress}");
      } else {
        // Normal page access, so need to display the errors page or success.
        $this->display_upload_result($count + $offset + 1);
      }
    }
  }
  
  /**
   * Internal function that retrieves the mappings for a CSV upload. For AJAX requests, this comes 
   * from a cached file. For normal requests, the mappings should be in the $_POST data.
   */
  private function _get_mappings($csvTempFile) {
    // AJAX chunked uploads will have pre-cached the mappings. Normal requests will just post them with the request.
    if (request::is_ajax()) {
      $mappingFile = str_replace('.csv','-map.txt', $csvTempFile);
      $mappingHandle = fopen($mappingFile, "r");
      $mappings = json_decode(fgets($mappingHandle)); 
    } else {
      $mappings = $_POST;
    }
    return $mappings;
  }
  
  /**
   * During a csv upload, this method is called to retrieve a resource handle to a file that can 
   * contain errors during the upload. The file is created if required, and the headers from the 
   * uploaded csv file (referred to by handle) are copied into the first row of the new error file
   * allong with a header for the problem description and row number.
   * @return resource The error file's handle.
   */
  private function _get_error_file_handle($csvTempFile, $handle) {
    $errorFile = str_replace('.csv','-errors.csv',$csvTempFile);
    $needHeaders = !file_exists($errorFile);
    $errorHandle = fopen($errorFile, "a");
    // skip the header row, but add it to the errors file with additional field for row number.
    $headers = fgetcsv($handle, 1000, ",");
    if ($needHeaders) {
      $headers[] = 'Problem';
      $headers[] = 'Row no.';
      fputcsv($errorHandle, $headers);
    }
    return $errorHandle;
  }
  
  /**
   * Display the end result of an upload. Either displayed at the end of a non-AJAX upload, or redirected
   * to directly by the AJAX code that is performing a chunked upload when the upload completes.
   * @param integer @count Number of records that were uploaded.
   */
  public function display_upload_result($count) {
    $csvTempFile = isset($_GET['uploaded_csv']) ? DOCROOT . "upload/" . $_GET['uploaded_csv'] : $_SESSION['uploaded_csv'];    
    $mappingFile = str_replace('.csv','-map.txt', $csvTempFile);
    // clean up the uploaded file and mapping file, but not the error file as we will make it downloadable.
    unlink($csvTempFile);    
    unlink($mappingFile);

    $errorFile = str_replace('.csv','-errors.csv',$csvTempFile);
    // Grab the errors into a problems array
    $handle = fopen ($errorFile, "r");
    // get the header row
    $headers = fgetcsv($handle, 1000, ",");
    $problems = array();
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      $problems[] = $data;
    }
    fclose($handle);
    if (count($problems)>0) {
      $view = new View('upload_problems');
      $view->headers = $headers;
      $view->problems = $problems;
      $view->errorFile = 'upload/'.basename($errorFile);
      $this->template->title = "Upload Problems";
      $this->template->content = $view;
      // Setup a breadcrumb
      $this->page_breadcrumbs[] = html::anchor($this->model->object_name, $this->pagetitle);
      $this->page_breadcrumbs[] = 'Upload result';
    } else {
      unlink($errorFile);
      $this->session->set_flash('flash_info', "The upload was successful. $count records were uploaded.");
      url::redirect($this->get_return_page());
    }
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
        $r['terms_'.$attr['termlist_id']][0] = '-no value-';
      }
    }
  }

}
