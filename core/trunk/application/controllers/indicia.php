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

  public function __construct()
  {
    // AJAX requests don't need an outer template
    if (request::is_ajax()) {
      $this->template = 'templates/blank';
    }
    parent::__construct();

    // assign view array with system information
    //
    $this->template->system = Kohana::config('indicia.system', false, false);

    $this->db = Database::instance();
    $this->auth = new Auth;
    $this->session = new Session;

    // upgrade check
    $this->check_for_upgrade();

    if($this->auth->logged_in())
    {
      $menu = array
      (
        'Home' => array(),
        'Lookup Lists' => array
        (
          'Species Lists'=>'taxon_list',
          'Taxon Groups'=>'taxon_group',
          'Term Lists'=>'termlist',
          'Locations'=>'location',
          'Surveys'=>'survey',
          'People'=>'person'
        ),
        'Custom Attributes' => array
        (
          'Occurrence Attributes'=>'occurrence_attribute',
          'Sample Attributes'=>'sample_attribute',
          'Location Attributes'=>'location_attribute'
        ),
        'Entered Data' => array
        (
          'Occurrences' => 'occurrence',
          'Samples' => 'sample',
          'Reports' => 'report'
        ),
        'Admin' => array
        (
          'Users'=>'user',
          'Websites'=>'website',
          'Languages'=>'language',
          'Titles'=>'title'
        ),
        'Logged in as '.$_SESSION['auth_user']->username => array
        (
          'Set New Password' => 'new_password',
          'Logout'=>'logout'
        )
      );
      if(!$this->auth->logged_in('CoreAdmin'))
        unset($menu['Admin']);
      $this->template->menu = $menu;
    } else
      $this->template->menu = array();
  }

  /**
  * Retrieve a suitable title for the edit page, depending on whether it is a new record
  * or an existing one.
  */
  protected function GetEditPageTitle($model, $name)
  {
    if ($model->id)
    return "Edit $name ".$model->caption();
    else
      return "New $name";
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
    // on error rest on the website_edit page
    // errors are now embedded in the model
    $view                    = new View( $name );
    $view->metadata          = $this->GetMetadataView(  $this->model );
    $this->template->title   = $this->GetEditPageTitle( $this->model, $pagetitle );
    $view->model             = $this->model;

    foreach ($viewArgs as $arg => $val) {
      $view->set($arg, $val);
    }
    $this->template->content = $view;
  }

  /**
  * Wraps a standard $_POST type array into a save array suitable for use in saving
  * records.
  *
  * @param array $array Array to wrap
  * @param bool $fkLink=false Link foreign keys?
  *
  * @return array Wrapped array
  */
  protected function wrap( $array, $fkLink = false, $id = null)
  {
    if ($id == null) $id = $this->model->object_name;
    // Initialise the wrapped array
    $sa = array
    (
      'id' => $id,
      'fields' => array(),
      'fkFields' => array(),
      'superModels' => array(),
      'subModels' => array()
    );

    // Iterate through the array
    foreach ($array as $a => $b)
    {
      $b = trim($b);
      // Check whether this is a fk placeholder
      if (substr($a,0,3) == 'fk_' && $fkLink)
      {
        $m = ORM::factory($id);
        $fieldName = substr($a,3);
        if (array_key_exists($fieldName, $m->belongs_to)) {
          $fkTable = $m->belongs_to[$fieldName];
        } elseif ($m instanceof ORM_Tree && $fieldName == 'parent') {
          $fkTable = inflector::singular($model->getChildren());
        } else {
           $fkTable = $fieldName;
        }
        // Generate a foreign key instance
        $sa['fkFields'][$a] = array
        (
          // Foreign key id field is table_id
          'fkIdField' => substr($a,3)."_id",
          'fkTable' => $fkTable,
          'fkSearchField' => ORM::factory($fkTable)->get_search_field(),
          'fkSearchValue' => $b
        );
      }
      else
      {
        // This should be a field in the model.
        // Add a new field to the save array
        $sa['fields'][$a] = array(
          // Set the value
          'value' => $b
        );
      }
    }
    return $sa;
  }

  /**
  * Sets the model submission, saves the submission array.
  */
  protected function submit($submission)
  {
    $this->model->submission = $submission;
    if (($id = $this->model->submit()) != null)
    {
      // Record has saved correctly
      $this->submit_succ($id);
    } else {
      // Record has errors - now embedded in model
      $this->submit_fail();
    }
  }

  /**
  * Returns to the index view for this controller.
  */
  protected function submit_succ($id)
  {
    Kohana::log("info", "Submitted record ".$id." successfully.");
    url::redirect($this->get_return_page());
  }

  /**
  * Returns to the edit page to correct errors - now embedded in the model
  */
  protected function submit_fail()
  {
    $mn = $this->model->object_name;
    $this->setView($mn."/".$mn."_edit", ucfirst($mn));
  }


  /**
  * Saves the post array by wrapping it and then submitting it.
  */
  public function save()
  {
    if (! empty($_POST['id']))
    {
      $this->model = ORM::factory($this->model->object_name, $_POST['id']);
    }

    /**
    * Were we instructed to delete the post?
    */
    if ($_POST['submit'] == 'Delete')
    {
      $_POST['deleted'] = 't';
    }
    else
    {
      $_POST['deleted'] = 'f';
    }

    // Wrap the post object and then submit it
    $this->submit($this->wrap($_POST));

  }

  /**
  * Check version of the php scripts against the database version
  */
  private function check_for_upgrade()
  {
    // system file which is distributed with every indicia version
    //
    $new_system = Kohana::config('indicia_dist.system');

    // get system info with the version number of the database
    $db_system = new System_Model;

    // compare the script version against the database version
    // if both arent equal start the upgrade process
    //
    if(0 != version_compare($db_system->getVersion(), $new_system['version'] ))
    {
      $upgrade = new Upgrade_Model;

      // upgrade to version $new_system['version']
      //
      if(true !== ($result = $upgrade->run($db_system->getVersion(), $new_system)))
      {
        // fatal error: the system stops here
        //
        if( false === Kohana::config('core.display_errors'))
      {
        die( Kohana::lang('setup.error_upgrade_for_end_user') );
      }
      else
      {
        die( 'UPGRADE ERROR: <pre>' . nl2br($result) . '</pre>' );
      }
      }

      // if successful, reload the system
      //
      url::redirect();
    }
  }

  protected function setError($title, $message)
  {
    $this->template->title   = $title;
    $this->template->content = new View('templates/error_message');
    $this->template->content->message = $message;
  }

  protected function access_denied($level = 'records.')
  {
    $this->setError('Access Denied', 'You do not have sufficient permissions to access the '.$this->model->table_name.' '.$level);
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
      echo '<pre>'.print_r($e, TRUE).'</pre>';
      throw $e;
    }

    // Fetch the output and close the buffer
    return ob_get_clean();
  }

  /**
   * Return the page to redirect to after a submission. Normally the same as the model name
   * (i.e. the controller's index page) but can be forced elsewhere by overriding this method.
   */
  protected function get_return_page() {
    return $this->model->object_name;
  }

  /**
   * Returns a set of terms for a termlist, which can be used to populate a termlist drop down.
   *
   * @param string $termlist Name of the termlist, from the termlist's external_key field.
   */
  protected function get_termlist_terms($termlist) {
    $arr=array();
    $sample_method_termlist = ORM::factory('termlist')->where('external_key', $termlist)->find();
    $terms = ORM::factory('termlists_term')->where(array('termlist_id' => $sample_method_termlist, 'deleted' => 'f'))->find_all();
    foreach ($terms as $term) {
      $arr[$term->id] = $term->term->term;
    }
    return $arr;
  }

}
