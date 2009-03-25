<?php

class Indicia_Controller extends Template_Controller {
  // Template view name
  public $template = 'templates/template';

  public function __construct()
  {
    parent::__construct();

    // assign view array with system informations
    //
    $this->template->system = Kohana::config('indicia.system', false, false);

    $this->db = Database::instance();
    $this->auth = new Auth;
    $this->session = new Session;

    // upgrade check
    //
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
      // Check whether this is a fk placeholder
      if (substr($a,0,3) == 'fk_'
	&& $fkLink)
      {
	// Generate a foreign key instance
	$sa['fkFields'][$a] = array
	(
	// Foreign key id field is table_id
	'fkIdField' => substr($a,3)."_id",
	 'fkTable' => substr($a,3),
	 'fkSearchField' =>
	 ORM::factory(substr($a,3))->get_search_field(),
	 'fkSearchValue' => $b
	 );
	 // Determine the foreign table name
	 $m = ORM::factory($id);
	 if (array_key_exists(substr($a,3), $m->belongs_to))
	 {
	   $sa['fkFields'][$a]['fkTable'] = $m->belongs_to[substr($a,3)];
	 } else if ($m instanceof ORM_Tree && substr($a,3) == 'parent') {
	   $sa['fkFields'][$a]['fkTable'] = $id;
      }
    }
    else
    {
      // This should be a field in the model.
      // Add a new field to the save array
      $sa['fields'][$a] = array(
      // Set the value
      'value' => $b);
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
  url::redirect($this->model->object_name);
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
*
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

}
