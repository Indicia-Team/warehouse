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
 * Base class for controllers in the Indicia Core module. Provides standard functionality
 * across all pages, e.g. checking user is authenticated and if not redirecting them to the
 * home page, or checking if a system upgrade is available and required.
 *
 * @package Core
 * @subpackage Controllers
 */
class Indicia_Controller extends Template_Controller {

  // Template view name
  public $template = 'templates/template';
  
  /**
   * Array of page specific breadcrumbs. Subclasses can append to this as required. 
   * @var array $page_breadcrumbs
   */
  protected $page_breadcrumbs = array();

  public function __construct()
  {
    // AJAX requests don't need an outer template
    if (request::is_ajax()) {
      $this->template = 'templates/blank';
    }
    parent::__construct();

    // assign view array with system information
    //
    $this->template->system = Kohana::config_load('version');

    $this->db = Database::instance();
    $this->auth = new Auth;
    $this->session = new Session;
    if ($this->auth->logged_in())
      $this->template->menu = self::get_menu();
    $title=kohana::config('indicia.warehouse_title');
    $this->template->warehouseTitle = $title ? $title : 'Indicia Warehouse';
  }

  /**
   * Overriding the render method gives us a single point to check that this page
   * is authorised.
   */
  public function _render()
  {
    if(!$this->page_authorised())
      $this->access_denied('page');
    parent::_render();
  }
  
  /**
   * Method which builds the main menu. Has a default structure which can be modified by plugin modules.
   * @return array Menu structure
   */
  protected function get_menu() {
    // use caching, so things don't slow down if there are lots of plugins which extend the menu. Caching must be per
    // user as they will have different access rights.
    $cacheId = 'indicia-menu-'.$_SESSION['auth_user']->id;
    $cache = Cache::instance();
    if ($cached = $cache->get($cacheId)) {
      return $cached;
    } else {
      $menu = array ('Home' => array());
      if ($this->auth->has_any_website_access('editor') || $this->auth->logged_in('CoreAdmin'))
        $menu['Lookup Lists'] = array(
          'Species Lists'=>'taxon_list',
          'Taxon Groups'=>'taxon_group',
          'Term Lists'=>'termlist',
          'Locations'=>'location',
          'Survey datasets'=>'survey',
          'People'=>'person'
        );
      if ($this->auth->has_any_website_access('admin') || $this->auth->logged_in('CoreAdmin'))
        $menu['Custom Attributes'] = array(
          'Occurrence Attributes'=>'occurrence_attribute',
          'Sample Attributes'=>'sample_attribute',
          'Location Attributes'=>'location_attribute',
          'Person Attributes'=>'person_attribute',
          'Taxon Attributes'=>'taxa_taxon_list_attribute'
        );
      if ($this->auth->has_any_website_access('editor') || $this->auth->logged_in('CoreAdmin'))
        $menu['Entered Data'] = array(
          'Occurrences' => 'occurrence',
          'Samples' => 'sample',
          'Reports' => 'report'
        );
      $adminMenu = array('Triggers &amp; Notifications' => 'trigger');
      // Core admin can see all users or websites plus web admins can see their own users and websites.
      if ($this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin')) {
        $adminMenu['Websites']='website';
        $adminMenu['Users']='user';
      }
      if($this->auth->logged_in('CoreAdmin')) {
        $adminMenu['Website Agreements']='website_agreement';
        $adminMenu['Languages']='language';
        $adminMenu['Titles']='title';
        $adminMenu['Taxon Relations']='taxon_relation_type';
      }
      $menu['Admin'] = $adminMenu;
      $menu['Logged in as '.$_SESSION['auth_user']->username] = array(
          'Set New Password' => 'new_password',
          'Logout'=>'logout'
      );
      // Now look for any modules which extend the menu
      foreach (Kohana::config('config.modules') as $path) {
        $plugin = basename($path);
        if (file_exists("$path/plugins/$plugin.php")) {
          require_once("$path/plugins/$plugin.php");
          if (function_exists($plugin.'_alter_menu')) {
            $menu = call_user_func($plugin.'_alter_menu', $menu, $this->auth);
          }
        }
      }
      $cache->set($cacheId, $menu); 
    }
    return $menu;
  }
  
  /**
   * Handler for the Create action on all controllers. Creates the default data required
   * when instantiating a new record and loads it into the edit form view.    
   */
  public function create() {
    if (!$this->record_authorised(null)) {
      $this->access_denied();
      return;
    }
    $values = $this->getDefaults();
    if (!isset($values)) {
      throw new Exception('Internal error. getDefaults method did not return an array of values for '.
          $this->model->object_name.'. Please ensure the getDefaults method returns a value in the controller.');
    }
    $this->showEditPage($values);
  }
  
  /**
   * Handler for the Edit action on all controllers. Loads the values required from the model
   * and any attached supermodels.    
   */
  public function edit($id) {
    if (!$this->record_authorised($id)) {
      $this->access_denied();
      return;
    }
    $this->model = ORM::Factory($this->model->object_name, $id);
    $values = $this->getModelValues();
    $this->showEditPage($values);     
  }
  
  /**
   * Code that is run when showing a controller's edit page - either from the create action
   * or the edit action.
   * 
   * @param int $id The record id (for editing) or null (for create).
   * @param array $valuse Associative array of valuse to populate into the form.   * 
   * @access private   
   */
  protected function showEditPage($values) { 
    $other = $this->prepareOtherViewData($values);
    $this->setView($this->editViewName(), $this->model->caption(), array(
      'values'=>$values,
      'other_data'=>$other
    )); 
    $this->defineEditBreadcrumbs();
  }
  
  /**
   * Returns the default name for the edit view, but can be overridden.
   */
  protected function editViewName() {
    $mn = $this->model->object_name;
    return $mn."/".$mn."_edit";
  }
  
  /**
   * Default behaviour for the edit page breadcrumbs. Can be overrridden.
   */
  protected function defineEditBreadcrumbs() { 
    $this->page_breadcrumbs[] = html::anchor($this->model->object_name, $this->pagetitle);
    $this->page_breadcrumbs[] = $this->model->caption();
  }
  
  /**
   * Provide an overridable method for preparing any additional data required by a view that does
   * not depend on the specific record. This includes preparing the list of terms to preload 
   * into lookup lists or combo boxes. 
   * 
   * @param array $values Existing data values for the view.
   * @return array Array of additional data items required, or null.
   */
  protected function prepareOtherViewData($values)
  {    
    return null;   
  }
  
  /**
   * Default behaviour is to allow access to records if logged in.   
   */   
  protected function record_authorised($id) {
    return $this->page_authorised();
  }
  
  /**
   * Returns an array of all values from this model and its super models ready to be 
   * loaded into a form.   
   */
  protected function getModelValues() {
    $struct = $this->model->get_submission_structure();
    // Get this model's values. If the structure needs a specified field prefix then use it, otherwise it will default to the model name.    
    $r = $this->model->getPrefixedValuesArray(
        array_key_exists('fieldPrefix', $struct) ? $struct['fieldPrefix'] : null
    );
    if (array_key_exists('superModels', $struct)) {
      foreach ($struct['superModels'] as $super=>$content) {
        // Merge the supermodel's values into the main array. Use a specified fieldPrefix if there is one.         
        $r = array_merge($r, $this->model->$super->getPrefixedValuesArray(
            array_key_exists('fieldPrefix', $content) ? $content['fieldPrefix'] : null
        ));
      } 
    }
    // Output a list of values for each joined record in the joinsTo links.
    if (array_key_exists('joinsTo', $struct)) {
      foreach ($struct['joinsTo'] as $joinsTo) {        
        $ids = array();    
        foreach ($this->model->$joinsTo as $joinedModel) {
          $r['joinsTo:'.inflector::singular($joinsTo).':'.$joinedModel->id] = 'on';
        }                 
      }      
    }
    return $r;
  }
  
  /**
   * Constructs an array of the default values required when loading a new edit form. 
   * Each entry is of the form "model:field => value". Loads both the defaults from this 
   * controller's main model, and any supermodels it has.
   */
  protected function getDefaults() {    
    $struct = $this->model->get_submission_structure();
    $r = $this->model->getDefaults();    
    if (array_key_exists('superModels', $struct)) {
      foreach ($struct['superModels'] as $super=>$content) {         
        $r = array_merge($r, ORM::Factory($super)->getDefaults());
      } 
    }
    if (array_key_exists('metaFields', $struct)) {
      foreach ($struct['metaFields'] as $m) {
        $r["metaField:$m"]='';
      } 
    } 
    
    return $r;
  }

  /**
   * Overrideable function that checks the user has access rights to the current page. Can 
   * be used to check for a certain role, for example.
   */   
  protected function page_authorised()
  {
    return ($this->uri->segment(1)=='login') || $this->auth->logged_in();
  }
  
  /**
  * Handler for the Save action on all controllers. Saves the post array by 
  * passing it into the model and then submitting it. If the post array was 
  * sent by a submit button with value Delete, then the record is marked for 
  * deletion. 
  */
  public function save()
  {
    if (!$this->page_authorised()) {
      $this->session->set_flash('flash_error', "You appear to be attempting to edit a page you do not have rights to.");
      $this->redirectToIndex();
    }
    elseif ($_POST['submit']=='Cancel') {      
      $this->redirectToIndex();
    } else {
      // Are we editing an existing record? If so, load it.
      if (array_key_exists('id', $_POST)) {
        $this->model = ORM::factory($this->model->object_name, $_POST['id']);
      } else {
        $this->model = ORM::factory($this->model->object_name);
      }
      
      // Were we instructed to delete the post?      
      $deletion = $_POST['submit'] == kohana::lang('misc.delete') || $_POST['submit'] == kohana::lang('misc.unsubscribe');    
      $_POST['deleted'] = $deletion ? 't' : 'f';
      // Pass the post object to the model and then submit it
      $this->model->set_submission_data($_POST);
      $this->submit($deletion);
    }
  }
  

  /**
  * Retrieve a suitable title for the edit page, depending on whether it is a new record
  * or an existing one.
  */
  protected function getEditPageTitle($model, $name)
  {  
    if ($model->id)
      return "Edit ".$model->caption();
    else
      return "New ".$model->caption();
  }

  /**
  * Return the metadata sub-template for the edit page of any model. Returns nothing
  * if there is no ID (so no metadata).
  */
  protected function GetMetadataView($model)
  {
    if ($this->model->id)
    {
      $metadata = new View('templates/metadata');
      $metadata->model = $model;
      return $metadata;
    } else {
      return '';
    }
  }

  /**
  * set view
  *
  * @param string $name View name
  * @param string $pagetitle Page title
  */
  protected function setView( $name, $pagetitle = '', $viewArgs = array() )
  {  
    try{
      // on error rest on the website_edit page
      // errors are now embedded in the model
      $view                    = new View( $name );
      $view->metadata          = $this->GetMetadataView(  $this->model );
      $this->template->title   = $this->getEditPageTitle( $this->model, $pagetitle );
      $view->model             = $this->model;
      $view->tabs              = $this->getTabs($name);

      foreach ($viewArgs as $arg => $val) {
        $view->set($arg, $val);
      }
      $this->template->content = $view;
    } catch (Exception $e) {
      error::log_error("Problem displaying view $name", $e);
      throw $e;
    }
  }
  
  /**
   * Overrideable function which allows a controller to declare the different tabs it exposes for each view.
   */
  protected function getTabs($name) {
    return array();
  }

  /**
  * Sets the model submission, saves the submission array.
  */
  protected function submit($deletion=false)
  {
    if (($id = $this->model->submit()) != null)
    {
      // Record has saved correctly
      $this->show_submit_succ($id, $deletion);
    } else {
      // Record has errors - now embedded in model
      $this->show_submit_fail();
    }
  }

  /**
  * Returns to the index view for this controller.
  */
  protected function show_submit_succ($id, $deletion=false)
  {
    Kohana::log("debug", "Submitted record ".$id." successfully.");
    $action = $deletion ? "deleted" : "saved";
    $this->session->set_flash('flash_info', "The record was $action successfully. <a href=\"".url::site().$this->model->object_name."/edit/$id\">Click here to edit</a>.");
    $this->redirectToIndex();
  }
  
  /**
   * Redirects the browser to the relevant index page which this came from (e.g. after saving an edit).   *
   * @access private   
   */
  private function redirectToIndex() {
    // What to do next setting needs to be kept between sessions as it persists after the redirect, so 
    // we can repopulate the select on data entry forms with the previuos value
    if (isset($_POST['what-next'])) $_SESSION['what-next'] = $_POST['what-next'];
    if(isset($_POST['return_url'])) {
      url::redirect($_POST['return_url']);
    } else {
      url::redirect($this->get_return_page());
    }
  }

  /**
  * Returns to the edit page to correct errors - now embedded in the model
  */
  protected function show_submit_fail()
  {
    $page_errors=$this->model->getPageErrors();
    if (count($page_errors)!=0) {
      $this->session->set_flash('flash_error', implode('<br/>',$page_errors));
    } else {
      $this->session->set_flash('flash_error', 'The record could not be saved.');
    }
    $values = $this->getDefaults();
    $values = array_merge($values, $_POST);
    $this->showEditPage($values);
  }

  protected function setError($title, $message)
  {
    $this->template->title   = $title;
    $this->template->content = new View('templates/error_message');
    $this->template->content->message = $message;
  }

  /**
   * Handle the event of an access denied error. Sets a flash message and redirects
   * to the home page.
   * @param string $level Level of page access being requested
   */
  protected function access_denied($level = 'records')
  {
    if (isset($this->model))
      $prefix = $this->model->table_name.' ';
    else
      $prefix = '';
    $this->session->set_flash('flash_error', "You do not have sufficient permissions to access the $prefix$level.");
    url::redirect('home');
  }

  /**
   * Override the load view behaviour to display better error information when a view
   * fails to load.
   */
  public function _kohana_load_view($kohana_view_filename, $kohana_input_data)
  {
    if ($kohana_view_filename == '')
      return;

    // Buffering on
    ob_start();

    // Import the view variables to local namespace
    extract($kohana_input_data, EXTR_SKIP);

    // Views are straight HTML pages with embedded PHP, so importing them
    // this way insures that $this can be accessed as if the user was in
    // the controller, which gives the easiest access to libraries in views

    // Put the include in a try catch block
    try
    {
      include $kohana_view_filename;
    }
    catch (Exception $e)
    {
      // Put the error out
      error::log_error('Error occurred when loading view.', $e);
      // Can't set a flash message here, as view has already failed to load.
      echo "<div class=\"ui-widget-content ui-corner-all ui-state-error page-notice\">".
          "<strong>Error occurred when loading page.</strong><br/>".$e->getMessage().
          "<br/>For more information refer to the application log file.</div>";
    }

    // Fetch the output and close the buffer
    return ob_get_clean();
  }

  /**
   * Return the page to redirect to after a submission. Normally the same as the model name
   * (i.e. the controller's index page) but can be forced elsewhere by overriding this method.
   */
  protected function get_return_page() {
    $r = $this->model->object_name;
    if (isset($_POST['what-next'])) {
      if ($_POST['what-next']=='add') $r .= '/create';      
    }
    return $r;
    
  }

  /**
   * Returns a set of terms for a termlist, which can be used to populate a termlist drop down.
   *
   * @param string $termlist ID of the termlist or name of the termlist, from the 
   * termlist's external_key field.
   * @param array $where Associative array of field values to filter for.
   * @return array Associative array of terms, with each entry being id => term.
   */
  protected function get_termlist_terms($termlist, $where=null) {
    $arr=array();
    if (!is_numeric($termlist)) {
      // termlist is a string so check the termlist from the external key field
      $query = $this->db
          ->select('id')
          ->from('termlists')
          ->where('external_key', $termlist)
          ->get()->as_array();
      if (count($query)>0)
        $row=$query[0];
      elseif (count($query)>1)
        throw new exception("Duplicate termlist $termlist.");
      else
        throw new exception("Termlist $termlist not found.");
      $termlist = $row->id;
    }
    $terms = $this->db
        ->select('termlists_terms.id, term')
        ->from('termlists_terms')
        ->join('terms', 'terms.id', 'termlists_terms.term_id')
        ->where(array('termlists_terms.termlist_id' => $termlist, 'termlists_terms.deleted' => 'f', 'terms.deleted' => 'f'))
        ->orderby(array('termlists_terms.sort_order'=>'ASC', 'terms.term'=>'ASC'));
    if ($where) 
      $terms = $terms->where($where);
    $terms = $terms->get();
    foreach ($terms as $term) {
      $arr[$term->id] = $term->term;
    }
    return $arr;
  }
  
  public function get_breadcrumbs()
  {
    $breadcrumbHtml = '';
    $breadcrumbList = array_merge(array(
      html::anchor('', 'Home')
    ), $this->page_breadcrumbs);
    while (current($breadcrumbList))
    {
      // Check if we have reached the last crumb
      if(key($breadcrumbList) < (count($breadcrumbList)-1)) {
        // If we haven't, add a breadcrumb separator
        $breadcrumbHtml .= current($breadcrumbList).' >> ';
      }
      else {
        // If we have, remove the anchor from the breadcrumb and make it bold
        $breadcrumbHtml .= strip_tags(current($breadcrumbList));
      }
      next($breadcrumbList);
    }
    return $breadcrumbHtml;
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
   * Ensures that the extract directory for zip files exists. 
   * @return string The directory path.
   */
  protected function create_zip_extract_dir() {
    $directory = Kohana::config('upload.zip_extract_directory', TRUE);
    // Make sure the directory ends with a slash
    $directory = rtrim($directory, '/').'/';
    if ( ! is_dir($directory) AND Kohana::config('upload.create_directories') === TRUE) {
        // Create the extraction directory
        mkdir($directory, 0777, TRUE);
    }
    if ( ! is_dir($directory) ) {
      $this->setError('Upload file problem', 'Zip extraction directory '.$directory.' does not exist. Please create, or set Indicia upload.create_directories configuration item to true.');
      return false;
    }
    if ( ! is_writable($directory)) {
      $this->setError('Upload file problem', 'Zip extraction directory '.$directory.' is not writable.');
      return false;
    }
    return $directory;
  }

}

