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
 * @package Services
 * @subpackage Data
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link    http://code.google.com/p/indicia/
 */


class Data_Controller extends Data_Service_Base_Controller {
  protected $model;
  protected $entity;
  protected $viewname;
  protected $foreign_keys;
  protected $view_columns;
  protected $db;
  // following are used to store the response till all finished, so we don't output anything
  // if there is an error
  protected $response;
  protected $content_type;

  // Read/Write Access to entities: there are several options:
  // 1) Standard: Restricted read and write access dependant on website id.
  //    There is a public function with the name of the entity in this file, and the entity appears in $allow_updates.
  //    The view list_<plural_entity> must exist and have a website_id column on it. If the website_id is null then
  //    the record may be accessed by all websites.
  // 2) Standard Read Only: Restricted read access dependant on website id. No write Access.
  //    There is a public function with the name of the entity in this file, and the entity does not appear in $allow_updates.
  //    The view list_<plural_entity> must exist and have a website_id column on it. If the website_id is null then
  //    the record may be accessed by all websites.
  // 3) Unrestricted Access: All records may be read and updated.
  //    There is a public function with the name of the entity in this file, and the entity appears in $allow_updates.
  //    Either the view list_<plural_entity> exists and has a website_id column on it which is forced to null, OR
  //    the entity appears in $allow_full_access.
  // 4) Unrestricted Read Only: All records may be read. No write Access.
  //    There is a public function with the name of the entity in this file, and the entity does not appear in $allow_updates.
  //    Either the view list_<plural_entity> exists and has a website_id column on it which is forced to null, OR
  //    the entity appears in $allow_full_access.
  // 5) Unrestricted Read, Restricted Write:
  //    Not currently implemented.
  // 6) No Access:
  //    There is no public function with the name of the entity in this file
  //
  // default to no updates allowed - must explicity allow updates.
  protected $allow_updates = array(
      'location',
      'occurrence',
      'occurrence_comment',
      'occurrence_image',
      'determination',
      'person',
      'sample',
      'sample_comment',
      'survey',
      'user',
      'taxa_taxon_list',
      'taxon_relation',
      'taxon_group'
  );

  // Standard functionality is to use the list_<plural_entity> views to provide a mapping between entity id
  // and website_id, so that we can work out whether access to a particular record is allowed.
  // There is a potential issues with this: We may want everyone to have complete access to a particular dataset
  // So if we wish total access to a given dataset, the entity must appear in the following list.
  protected $allow_full_access = array(
      'taxa_taxon_list',
      'taxon_relation',
      'taxon_group'
  );
  
  // List of tables that do not use views to expose their data.
  protected $tables_without_views = array(
    'index_websites_website_agreements'
  );
  
  /**
  * Provides the /services/data/index_websites_website_agreements service.
  * Retrieves details of a single language.
  */
  public function index_websites_website_agreement()
  {
    $this->handle_call('index_websites_website_agreement');
  }

  /**
  * Provides the /services/data/language service.
  * Retrieves details of a single language.
  */
  public function language()
  {
    $this->handle_call('language');
  }

  /**
  * Provides the /services/data/location service.
  * Retrieves details of a single survey.
  */
  public function location()
  {
    $this->handle_call('location');
  }

  /**
  * Provides the /service/data/location_attribute service.
  * Retrieves details of location attributes.
  */
  public function location_attribute()
  {
    $this->handle_call('location_attribute');
  }

  /**
  * Provides the /service/data/location_attribute_value service.
  * Retrieves details of location attribute values.
  */
  public function location_attribute_value()
  {
    $this->handle_call('location_attribute_value');
  }

  /**
  * Provides the /service/data/location_image service.
  * Retrieves details of location images.
  */
  public function location_image()
  {
    $this->handle_call('location_image');
  }

 /**
  * Provides the /service/data/sample_image service.
  * Retrieves details of sample images.
  */
  public function sample_image()
  {
    $this->handle_call('sample_image');
  }

  /**
  * Provides the /services/data/occurrence service.
  * Retrieves details of occurrences.
  */
  public function occurrence()
  {
    $this->handle_call('occurrence');
  }

  /**
  * Provides the /service/data/occurrence_attribute service.
  * Retrieves details of occurrence attributes.
  */
  public function occurrence_attribute()
  {
  $this->handle_call('occurrence_attribute');
  }

  /**
  * Provides the /service/data/occurrence_attribute_value service.
  * Retrieves details of occurrence attribute values.
  */
  public function occurrence_attribute_value()
  {
  $this->handle_call('occurrence_attribute_value');
  }

  /**
  * Provides the /service/data/occurrence_image service.
  * Retrieves details of occurrence images.
  */
  public function occurrence_image()
  {
  $this->handle_call('occurrence_image');
  }

  /**
  * Provides the /service/data/determination service.
  * Retrieves details of occurrence attributes.
  */
  public function determination()
  {
  $this->handle_call('determination');
  }

  /**
  * Provides the /services/data/person service.
  * Retrieves details of a single person.
  */
  public function person()
  {
    $this->handle_call('person');
  }
  
  
  /**
  * Provides the /service/data/person_attribute service.
  * Retrieves details of person attributes.
  */
  public function person_attribute()
  {
  $this->handle_call('person_attribute');
  }
  
  
  /**
  * Provides the /service/data/person_attribute_value service.
  * Retrieves details of occurrence attributes.
  */
  public function person_attribute_value()
  {
  $this->handle_call('person_attribute_value');
  }

  /**
  * Provides the /services/data/sample service.
  * Retrieves details of a sample.
  */
  public function sample()
  {
    $this->handle_call('sample');
  }

  /**
  * Provides the /service/data/sample_attribute service.
  * Retrieves details of sample attributes.
  */
  public function sample_attribute()
  {
  $this->handle_call('sample_attribute');
  }

  /**
  * Provides the /service/data/sample_attribute_value service.
  * Retrieves details of sample attribute values.
  */
  public function sample_attribute_value()
  {
  $this->handle_call('sample_attribute_value');
  }


  /**
  * Provides the /services/data/survey service.
  * Retrieves details of a single survey.
  */
  public function survey()
  {
  $this->handle_call('survey');
  }

  /**
  * Provides the /services/data/taxon_group service.
  * Retrieves details of a single taxon_group.
  */
  public function taxon_group()
  {
  $this->handle_call('taxon_group');
  }

 /**
  * Provides the /service/data/taxon_image service.
  * Retrieves details of location images.
  */
  public function taxon_image()
  {
    $this->handle_call('taxon_image');
  }


  /**
  * Provides the /services/data/taxon_list service.
  * Provides access to taxon_lists.
  */
  public function taxon_list()
  {
    $this->handle_call('taxon_list');
  }

  /**
  * Provides the /services/data/taxon_relation_type service.
  * Provides access to taxon_relation_types.
  */
  public function taxon_relation_type()
  {
    $this->handle_call('taxon_relation_type');
  }

  /**
  * Provides the /services/data/taxa_taxon_list service.
  * Retrieves details of taxa on a taxon_list.
  */
  public function taxa_taxon_list()
  {
    $this->handle_call('taxa_taxon_list');
  }
  
  /**
  * Provides the /service/data/occurrence_attribute service.
  * Retrieves details of occurrence attributes.
  */
  public function taxa_taxon_list_attribute()
  {
  $this->handle_call('taxa_taxon_list_attribute');
  }

  /**
  * Provides the /service/data/taxa_taxon_list_attribute_value service.
  * Retrieves details of occurrence attribute values.
  */
  public function taxa_taxon_list_attribute_value()
  {
  $this->handle_call('taxa_taxon_list_attribute_value');
  }

  /**
  * Provides the /services/data/taxa_relation service.
  * Retrieves details of taxon_relations.
  */
  public function taxon_relation()
  {
  $this->handle_call('taxon_relation');
  }

  /**
  * Provides the /services/data/term service.
  * Retrieves details of a single term.
  */
  public function term()
  {
    $this->handle_call('term');
  }

  /**
  * Provides the /services/data/termlist service.
  * Retrieves details of a single termlist.
  */
  public function termlist()
  {
    $this->handle_call('termlist');
  }

  /**
  * Provides the /services/data/termlists_term service.
  * Retrieves details of a single termlists_term.
  */
  public function termlists_term()
  {
    $this->handle_call('termlists_term');
  }

  /**
  * Provides the /services/data/title service.
  * Retrieves details of titles.
  */
  public function title()
  {
    $this->handle_call('title');
  }

  /**
  * Provides the /services/data/user service.
  * Retrieves details of a single user.
  */
  public function user()
  {
    $this->handle_call('user');
  }
  
  /**
  * Provides the /services/data/user service.
  * Retrieves details of a single user.
  */
  public function user_identifier()
  {
    $this->handle_call('user_identifier');
  }

  /**
  * Provides the /services/data/website service.
  * Retrieves details of a single website.
  */
  public function website()
  {
    $this->handle_call('website');
  }
  
  /**
  * Provides the /services/data/website_agreement service.
  * Retrieves details of a single website.
  */
  public function website_agreement()
  {
    $this->handle_call('website_agreement');
  }
  
  /**
  * Provides the /services/data/websites_website_agreement service.
  * Retrieves details of a single website.
  */
  public function websites_website_agreement()
  {
    $this->handle_call('websites_website_agreement');
  }

  /**
  * Provides the /services/data/trigger service.
  * Retrieves details of a single trigger.
  */
  public function trigger()
  {
    $this->handle_call('trigger');
  }

  /**
  * Provides the /services/data/occurrence_comments service.
  */
  public function occurrence_comment()
  {
    $this->handle_call('occurrence_comment');
  }

  /**
  * Provides the /services/data/occurrence_comments service.
  */
  public function sample_comment()
  {
    $this->handle_call('sample_comment');
  }

  /**
   * Magic method which accepts data service calls for non-core entities that are
   * handled by plugins. Checks to see if the any plugins expose a model which
   * matches the requested entity and checks if the model is only read only
   * then only read requests are accepted.
   * Plugins can use the extend_data_services hook to declare their models to expose
   * via data services.
   * @link http://code.google.com/p/indicia/wiki/WarehousePluginArchitecture
   * @param <type> $name
   * @param <type> $arguments
   */
  public function __call($name, $arguments) {
    // use caching, so things don't slow down if there are lots of plugins
    $cacheId = 'extend-data-services';
    $cache = Cache::instance();
    $extensions = $cache->get($cacheId);
    if (!$extensions) {
      $extensions = array();
      // now look for modules which plugin to add a data service extension.
      foreach (Kohana::config('config.modules') as $path) {
        $plugin = basename($path);
        if (file_exists("$path/plugins/$plugin.php")) {
          require_once("$path/plugins/$plugin.php");
          if (function_exists($plugin.'_extend_data_services')) {
            $moduleExtensions = call_user_func($plugin.'_extend_data_services');
            $extensions = array_merge($extensions, $moduleExtensions);
          }
        }
      }
      $cache->set($cacheId, $extensions);
    }
    if (array_key_exists(inflector::plural($name), $extensions)) {
      $this->extensionOpts = $extensions[inflector::plural($name)];
      $this->handle_call($name);
    } else {
      echo "Unrecognised entity $name";
    }
  }

  /**
  * Internal method to handle calls - decides if it's a request for data or a submission.
  * @todo include exception getTrace() in the error response?
  */
  protected function handle_call($entity)
  {
    try {
      $this->entity = $entity;

      if (array_key_exists('submission', $_POST))
      {
        $this->handle_submit();
      }
      else
      {
        $this->handle_request();
      }
      kohana::log('debug', 'Sending reponse size '.count($this->response));
      $this->send_response();
    }
    catch (Exception $e)
    {
      $this->handle_error($e);
    }
  }

  /**
  * Internal method for handling a generic submission to a particular model.
  */
  protected function handle_submit()
  {
    $this->authenticate();
    $mode = $this->get_input_mode();
    switch ($mode)
    {
      case 'json':
        $s = json_decode($_POST['submission'], true);
    }

    if (array_key_exists('submission', $s))
    {
      $id = $this->submit($s);
      // TODO: proper handling of result checking
      $result = TRUE;
    }
    else
    {
      $this->check_update_access($this->entity, $s);
      $model = ORM::factory($this->entity);
      $model->submission = $s;
      $result = $model->submit();
      $id = $model->id;
    }
    if ($result)
    {
      $this->response=json_encode(array('success'=>$id));
      $this->delete_nonce();
    }
    else if (isset($model) && is_array($model->getAllErrors()))
      Throw new ArrayException('Error occurred on model submission', $model->getAllErrors());
    else
      Throw new Exception('Unknown error on submission of the model');

  }

  /**
   * Retrieve the records for a read request. Also sets the list of columns into $this->columns.
   *
   * @return Array Query results array.
   */
  protected function read_data() {
    // Store the entity in class member, so less recursion overhead when building XML
    $this->viewname = $this->get_view_name();
    $this->db = new Database();
    $this->view_columns=$this->db->list_fields($this->viewname);
    $result=$this->build_query_results();
    kohana::log('debug', 'Query ran for service call: '.$this->db->last_query());
    return array('records'=>$result);
  }

  /**
   * Handle uploaded files in the $_FILES array by moving them to the upload folder. Images
   * get resized and duplicated as specified in the indicia config file.
   * If the $_POST array contains name_is_guid=true, then the image file will not be renamed as the name
   * should already be globally unique. Otherwise the current time is prefixed to the name to make it unique.
   */
  public function handle_media()
  {
    try
    {
      // Ensure we have write permissions.
      $this->authenticate();
      // We will be using a POST array to send data, and presumably a FILES array for the
      // media.
      // Upload size
      $ups = Kohana::config('indicia.maxUploadSize');
      $_FILES = Validation::factory($_FILES)->add_rules(
        'media_upload', 'upload::valid', 'upload::required',
        'upload::type[png,gif,jpg,jpeg]', "upload::size[$ups]"
      );
      if ($_FILES->validate())
      {
        if (array_key_exists('name_is_guid', $_POST) && $_POST['name_is_guid']=='true')
          $finalName = strtolower($_FILES['media_upload']['name']);
        else
          $finalName = time().strtolower($_FILES['media_upload']['name']);
        $fTmp = upload::save('media_upload', $finalName);
        Image::create_image_files(dirname($fTmp), basename($fTmp));
        $this->response=basename($fTmp);
        $this->send_response();
        kohana::log('debug', 'Successfully uploaded media to '. basename($fTmp));
      }
      else
      {
        kohana::log('info', 'Validation errors uploading media '. $_FILES['media_upload']['name']);
        Throw new ArrayException('Validation error', $_FILES->errors('form_error_messages'));
      }
    }
    catch (Exception $e)
    {
      $this->handle_error($e);
    }
  }

  /**
  * Builds a query to extract data from the requested entity, and also
  * include relationships to foreign key tables and the caption fields from those tables.
  * @param boolean $count if set to true then just returns a record count.
  * @todo Review this code for SQL Injection attack!
  * @todo Basic website filter done, but not clever enough.
  */
  protected function build_query_results($count=false)
  {
    $this->foreign_keys = array();
    $this->db->from($this->viewname);
    // Select all the table columns from the view
    if (!$count) {
      $fields = array_keys($this->db->list_fields($this->viewname));
      foreach($fields as &$field) 
        $field = $this->viewname.'.'.$field;
      $select = implode(', ', $fields);
      $this->db->select($select);
    }
    // If not in the warehouse, then the entity must explicitly allow full access, or contain a website ID to filter on.
    if (!$this->in_warehouse && !array_key_exists ('website_id', $this->view_columns) && 
        !array_key_exists ('from_website_id', $this->view_columns) && !in_array($this->entity, $this->allow_full_access)) {
      // If access is from remote website, then either table allows full access or exposes a website ID to filter on.
      Kohana::log('info', $this->viewname.' does not have a website_id - access denied');
      throw new ServiceError('No access to entity '.$this->entity.' allowed through view '.$this->viewname);
    }
    if (array_key_exists ('website_id', $this->view_columns))
      $websiteFilterField = 'website_id';
    elseif (array_key_exists ('from_website_id', $this->view_columns))
      $websiteFilterField = 'from_website_id';
    // Loading a list of records (no record ID argument)
    if (isset($websiteFilterField)) {
      // we have a filter on website_id to apply
      if ($this->website_id) {
        // check if a request for shared data is being made. Also check this is valid to prevent injection.
        if (isset($_REQUEST['sharing']) && preg_match('/[reporting|peer_review|verification|data_flow|moderation]/', $_REQUEST['sharing'])) {
          // request specifies the sharing mode (i.e. the task being performed, such as verification, moderation). So 
          // we can use this to work out access to other website data.
          $this->db->join('index_websites_website_agreements as iwwa', array(
              'iwwa.from_website_id'=>$this->viewname.'.'.$websiteFilterField,
              'iwwa.provide_for_'.$_REQUEST['sharing']."='t'"=>''
          ), NULL, 'LEFT');
          $this->db->where('(' . $this->viewname.'.'.$websiteFilterField.' IS NULL OR iwwa.to_website_id=' . $this->website_id . ')');
        } else {
          $this->db->in($this->viewname.'.'.$websiteFilterField, array(null, $this->website_id));
        }
      } elseif ($this->in_warehouse && !$this->user_is_core_admin) {
        // User is on Warehouse, but not core admin, so do a filter to all their websites.
        $allowedWebsiteValues = array_merge($this->user_websites);
        $allowedWebsiteValues[] = null;
        $this->db->in('website_id', $allowedWebsiteValues);
      }
    }
    if ($this->uri->total_arguments()==0) {
      // filter the list according to the parameters in the call
      $this->apply_get_parameters_to_db($count);
    }
    else {
      $this->db->where($this->viewname.'.id', $this->uri->argument(1));
    }
    try {
      if ($count)
        return $this->db->count_records();
      else {
        $r = $this->db->get()->result_array(FALSE);
        // If we got no record but asked for a specific one, check if this was a permissions issue?
        if (!count($r) && $this->uri->total_arguments()!==0 && !$this->check_record_access($this->entity, $this->uri->argument(1), $this->website_id, isset($_REQUEST['sharing']) ? $_REQUEST['sharing'] : false)) {
          Kohana::log('info', 'Attempt to access existing record failed - website_id '.$this->website_id.' does not match website for '.$this->entity.' id '.$this->uri->argument(1));
          throw new ServiceError('Attempt to access existing record failed - website_id '.$this->website_id.' does not match website for '.$this->entity.' id '.$this->uri->argument(1));
        }
        return $r;
      }
    }
    catch (Exception $e) {
      kohana::log('error', 'Error occurred running the following query from a service request:');
      kohana::log('error', $e->getMessage());
      kohana::log('error', $this->db->last_query());
      kohana::log('error', 'Request detail:');
      kohana::log('error', $this->uri->string());
      kohana::log('error', kohana::debug($_REQUEST));
      throw $e;
    }
  }

  /**
  * Returns the name of the view for the request. This is a view
  * associated with the entity, but prefixed by either list, gv or max depending
  * on the GET view parameter, or as is if the table has no views.
  */
  protected function get_view_name()
  {
    $table = inflector::plural($this->entity);
    if (in_array($table, $this->tables_without_views)) {
      return $table;
    }
    $prefix='';
    if (array_key_exists('view', $_REQUEST))
    {
      $prefix = $_REQUEST['view'];
    }
    // Check for allowed view prefixes, and use 'list' as the default
    if ($prefix!='gv' && $prefix!='detail' && $prefix!='cache')
      $prefix='list';
    return $prefix.'_'.$table;
  }


  /**
  * Works out what filter and other options to set on the db object according to the
  * $_REQUEST parameters currently available, when retrieving a list of items.
  * @param boolean $count set to true when doing a count query, so the limit and offset are skipped
  */
  protected function apply_get_parameters_to_db($count=false)
  {
    $sortdir='ASC';
    $orderby='';
    $like=array();
    $where=array();
    foreach ($_REQUEST as $param => $value)
    {
      $value = urldecode($value);
      switch ($param)
      {
        case 'sortdir':
          if ($count) break;
          $sortdir=strtoupper($value);
          if ($sortdir != 'ASC' && $sortdir != 'DESC')
          {
            $sortdir='ASC';
          }
          break;
        case 'orderby':
          if ($count) break;
          if (array_key_exists(strtolower($value), $this->view_columns))
            $orderby=strtolower($value);
          break;
        case 'limit':
          if ($count) break;
          if (is_numeric($value))
          $this->db->limit($value);
          break;
        case 'offset':
          if ($count) break;
          if (is_numeric($value))
          $this->db->offset($value);
          break;
        case 'qfield':
          if (array_key_exists(strtolower($value), $this->view_columns))
          {
            $qfield = strtolower($value);
          }
          break;
        case 'q':
          $q = $value;
          break;
        case 'attrs':
          // Check that we're dealing with 'occurrence', 'location' or 'sample' here
          // TODO check this works - looks like it does nothing...
          switch($this->entity)
          {
            case 'sample':
              Kohana::log('debug', "Fetching attributes $value for sample");
              $attrs = explode(',', $value);
              break;
            case 'occurrence':
              Kohana::log('debug', "Fetching attributes $value for occurrence");
              $attrs = explode(',', $value);
              break;
            case 'location':
              Kohana::log('debug', "Fetching attributes $value for location");
              $attrs = explode(',', $value);
              break;
            default:
              Kohana::log('debug', 'Trying to fetch attributes for non sample/occurrence/location table. Ignoring.');
          }
          break;
        case 'query':
          $this->apply_query_def_to_db($value);
          break;
      default:
        if (array_key_exists(strtolower($param), $this->view_columns)) {
          // A parameter has been supplied which specifies the field name of a filter field
          if ($value == 'NULL')
            $value = NULL;
          // Build a where for ints, bools or if there is no * in the search string.
          if ($this->view_columns[$param]['type']=='int' || $this->view_columns[$param]['type']=='bool' ||
              strpos($value, '*')===false) {
            $where[$param]=$value;
          } else {
            $like[$param]=str_replace('*', '%', $value);
          }

        }
      }
    }
    if (isset($qfield) && isset($q))
    {
      if ($this->view_columns[$qfield]['type']=='int' || $this->view_columns[$qfield]['type']=='bool')
      {
        $where[$qfield]=$q;
      }
      else
      {
        // When using qfield and q parameters, it is from an AJAX call for an autocomplete, so append a wildcard and
        // also switch any service wildcards (*) for sql wildcards (%).
        $like[$qfield]=str_replace('*', '%', $q).'%';
      }
    }
    if ($orderby)
      $this->db->orderby($orderby, $sortdir);
    if (count($like)) {
      foreach ($like as $field => $value) {
        $this->db->like($field, $value, false);
      }
    }
    if (count($where))
      $this->db->where($where);
  }

  /**
   * Takes the value of a query parameter passed to the data service, and processes it to apply the filter conditions
   * defined in the JSON to $this->db, ready for when the service query is run.
   * @param string $value The value of the parameter called query, which should contain a JSON object.
   * @link http://code.google.com/p/indicia/wiki/DataServices#Using_the_query_parameter
   */
  protected function apply_query_def_to_db($value) {
    $query = json_decode($value, true);
    foreach ($query as $cmd=>$params) {
      switch(strtolower($cmd)) {
        case 'in':
        case 'notin':
          unset($foundfield);
          unset($foundvalue);
          foreach($params as $key=>$value) {
            if (is_int($key)) {
              if ($key===0) $foundfield = $value;
              elseif ($key===1) $foundvalue = $value;
              else throw new Exception("In clause statement for $key is not of the correct structure");
            } elseif (is_array($value)) {
              $this->db->$cmd($key,$value);
            } else {
              throw new Exception("In clause statement for $key is not of the correct structure");
            }
          }
          // if param was supplied in form "cmd = array(field, values)" then foundfield and foundvalue would be set.
          if (isset($foundfield) && isset($foundvalue))
            $this->db->$cmd($foundfield,$foundvalue);
          break;
        case 'where':
        case 'orwhere':
        case 'like':
        case 'orlike':
          unset($foundfield);
          unset($foundvalue);
          foreach($params as $key=>$value) {
            if (is_int($key)) {
              if ($key===0) $foundfield = $value;
              elseif ($key===1) $foundvalue = $value;
              else throw new Exception("In clause statement for $key is not of the correct structure");
            } elseif (!is_array($value)) {
              // id fields must be queried by Where clause not Like.
              $this_cmd = ($key=='id') ? str_replace('like', 'where', $cmd) : $cmd;
              // Apply the filter command. if we are switching a like to a where clause, but no value is provided, then don't filter because
              // like '%%' matches anything, but where x='' would break on an int field.
              if ($this_cmd == $cmd || !empty($value)) $this->db->$this_cmd($key,$value);
            } else {
              throw new Exception("$cmd clause statement for $key is not of the correct structure. ".print_r($params, true));
            }
          }
          // if param was supplied in form "cmd = array(field, value)" then foundfield and foundvalue would be set.
          if (isset($foundfield) && isset($foundvalue)) {
            // id fields must be queried by Where clause not Like.
            if ($foundfield=='id') $this_cmd = str_replace('like', 'where', $cmd);
            if ($this_cmd == $cmd || !empty($foundvalue)) $this->db->$this_cmd($foundfield,$foundvalue);
          } elseif (isset($foundfield) && ($cmd==='where' || $cmd==='orwhere')) {
            // with just 1 parameter passed through, a where can contain something more complex such as an OR in brackets.
            $this->db->$cmd($foundfield);
          }
          break;
        default:
          kohana::log('error',"Unsupported query command $cmd");
      }
    }
  }

  /**
  * Accepts a submission from POST data and attempts to save to the database.
  */
  public function save()
  {
    try
    {
      $this->authenticate();
      if (array_key_exists('submission', $_POST))
      {
        $mode = $this->get_input_mode();
        switch ($mode)
        {
          case 'json':
            $s = json_decode($_POST['submission'], true);
        }
        // if the request included a user ID, put it in the global var so all ORM saves can use it
        if ($this->user_id) {
          global $remoteUserId;
          $remoteUserId = $this->user_id;
        }
        $response = $this->submit($s);
        // return a success message plus the id of the topmost record, e.g. the sample created, plus a summary structure of any other records created.
        $response = array('success'=>'multiple records', 'outer_table'=>$s['id'], 'outer_id'=>$response['id'], 'struct'=>$response['struct']);
        // if the saved form contained a transaction Id, return it.
        if (isset($s['fields']['transaction_id']['value']))
          $response['transaction_id'] = $s['fields']['transaction_id']['value'];
        echo json_encode($response);
      }
      $this->delete_nonce();
    }
    catch (Exception $e)
    {
      $this->handle_error($e);
    }
  }

  /**
  * Takes a submission array and attempts to save to the database. The submission array
  * can either contain a submission list or a single submission.
  */
  protected function submit($s)
  {
    kohana::log('info', 'submit');
    if (array_key_exists('submission_list',$s)) {
      foreach ($s['submission_list']['entries'] as $m)
      {
        $r = $this->submit_single($m);
      }
    } else {
      $r = $this->submit_single($s);
    }
    return $r;
  }

  /**
  * Takes a single submission entry and attempts to save to the database.
  */
  protected function submit_single($item) {
    $model = ORM::factory($item['id']); // id is the entity.
    $this->check_update_access($item['id'], $item);
    $model->submission = $item;
    $result = $model->submit();
    if (!$result)
    {
      Throw new ArrayException('Validation error', $model->getAllErrors());
    }
    // return the outermost model's id
    return array('id'=>$model->id, 'struct'=>$model->get_submitted_ids());
  }

 /**
  * Checks that we have update access to a given entity for a given submission array.
  * The submission array is checked to see if there is a primary key ('id').
  * Returns true if access OK, otherwise throws an exception.
  */
  protected function check_update_access($entity, $s)
  {
    if (!in_array($entity, $this->allow_updates) ||
        (isset($extensionOpts['readOnly']) && $extensionsOpts['readOnly']===true)) {
      Kohana::log('info', 'Attempt to write to entity '.$entity.' by website '.$this->website_id.': no write access allowed through services.');
      throw new ServiceError('Attempt to write to entity '.$entity.' failed: no write access allowed through services.');
    }

      if(array_key_exists('id', $s['fields']))
        if (is_numeric($s['fields']['id']['value']))
          // there is an numeric id field so modifying an existing record
          if (!$this->check_record_access($entity, $s['fields']['id']['value'], $this->website_id))
          {
            Kohana::log('info', 'Attempt to update existing record failed - website_id '.$this->website_id.' does not match website for '.$entity.' id '.$s['fields']['id']['value']);
            throw new ServiceError('Attempt to update existing record failed - website_id '.$this->website_id.' does not match website for '.$entity.' id '.$s['fields']['id']['value']);
          }
    return true;
  }

  protected function check_record_access($entity, $id, $website_id, $sharing=false)
  {
    // if $id is null, then we have a new record, so no need to check if we have access to the record
    if (is_null($id))
      return true;
    $table = inflector::plural($entity);
    $viewname='list_'.$table;
    $db = new Database;
    $fields=$db->list_fields($viewname);
    if(empty($fields)) {
      Kohana::log('info', $viewname.' not present - access denied');
      throw new ServiceError('Access to entity '.$entity.' denied.');
    }
    $db->from("$viewname as record");
    $db->where(array('record.id' => $id));

    if(!in_array ($entity, $this->allow_full_access)) {
      if(array_key_exists ('website_id', $fields)) {
        // check if a request for shared data is being made. Also check this is valid to prevent injection.
        if ($sharing && preg_match('/[reporting|peer_review|verification|data_flow|moderation]/', $sharing)) {
          // request specifies the sharing mode (i.e. the task being performed, such as verification, moderation). So 
          // we can use this to work out access to other website data.
          $db->join('index_websites_website_agreements as iwwa', array(
              'iwwa.from_website_id'=>'record.website_id',
              'iwwa.receive_for_'.$sharing."='t'"=>''
          ), NULL, 'LEFT');
          $db->where('record.website_id IS NULL');
          $db->orwhere('iwwa.to_website_id', $this->website_id);
        } else {
          $db->where('record.website_id', $this->website_id);
        }
      } elseif (!$this->in_warehouse) {
        Kohana::log('info', $viewname.' does not have a website_id - access denied');
        throw new ServiceError('No access to entity '.$entity.' allowed.');
      }
    }
    $number_rec = $db->count_records();
    return ($number_rec > 0 ? true : false);
  }
  
  /**
   * Get the record count of the full grid result.
   */
  protected function record_count() {
    return $this->build_query_results(true);
  }
  
}

?>
