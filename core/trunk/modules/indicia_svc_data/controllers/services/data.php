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
  * Provides the /service/data/occurrence_attribute service.
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
  * Provides the /services/data/website service.
  * Retrieves details of a single website.
  */
  public function website()
  {
    $this->handle_call('website');
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
  protected function read_records() {
    // Store the entity in class member, so less recursion overhead when building XML
    $this->viewname = $this->get_view_name();
    $this->db = new Database();
    $this->view_columns=$this->db->list_fields($this->viewname);
    $result=$this->build_query_results();
    kohana::log('debug', 'Query ran for service call: '.$this->db->last_query());
    return $result;
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
  * Returns some information about the table - at least list of columns and
  * number of records. 
  */
  public function info_table($tablename)
  {
    $this->authenticate('read'); // populates $this->website_id
    $this->entity = $tablename;
    $this->db = new Database();
    $this->viewname = $this->get_view_name();
    $this->view_columns = $this->db->list_fields($this->viewname);
    $mode = $this->get_output_mode();
    if(!in_array ($this->entity, $this->allow_full_access)) {
      if(array_key_exists ('website_id', $this->view_columns))
      {
        if ($this->website_id != 0) {
          $this->db->in('website_id', array(null, $this->website_id));
        }
      } else {
        Kohana::log('info', $this->viewname.' does not have a website_id - access denied to table info');
        throw new ServiceError('No access to '.$this->viewname.' allowed.');
      }
    }

    $return = Array(
      'record_count' => $this->db->count_records($this->viewname),
      'columns' => array_keys($this->db->list_fields($this->viewname))
    );
    switch ($mode)
    {
      case 'json':
        $a = json_encode($return);
        if (array_key_exists('callback', $_GET))
        {
          $a = $_GET['callback']."(".$a.")";
        }
        echo $a;
        break;
      default:
        echo json_encode($return);
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
      $select = implode(', ', array_keys($this->db->list_fields($this->viewname)));
      $this->db->select($select);
    }
    if (array_key_exists ('website_id', $this->view_columns)) {
      if ($this->website_id) {
        $this->db->in('website_id', array(null, $this->website_id));
      } elseif ($this->in_warehouse && !$this->user_is_core_admin) {
        // User is on Warehouse, but not core admin, so do a filter to all their websites.
        $allowedWebsiteValues = array_merge($this->user_websites);
        $allowedWebsiteValues[] = null;
        $this->db->in('website_id', $allowedWebsiteValues);
      }
    } elseif (!$this->in_warehouse && !in_array($this->entity, $this->allow_full_access)) {
      // If access is from remote website, then either table allows full access or exposes a website ID to filter on.
      Kohana::log('info', $this->viewname.' does not have a website_id - access denied');
      throw new ServiceError('No access to entity '.$this->entity.' allowed through view '.$this->viewname);
    }
    // if requesting a single item in the segment, filter for it, otherwise use GET parameters to control the list returned
    if ($this->uri->total_arguments()==0)
      $this->apply_get_parameters_to_db($count);
    else {
     if (!$this->check_record_access($this->entity, $this->uri->argument(1), $this->website_id))
      {
      Kohana::log('info', 'Attempt to access existing record failed - website_id '.$this->website_id.' does not match website for '.$this->entity.' id '.$this->uri->argument(1));
          throw new ServiceError('Attempt to access existing record failed - website_id '.$this->website_id.' does not match website for '.$this->entity.' id '.$this->uri->argument(1));
      }
      $this->db->where($this->viewname.'.id', $this->uri->argument(1));
    }
    try {
      if ($count)
        return $this->db->count_records();
      else
        return $this->db->get()->result_array(FALSE);
    }
    catch (Exception $e) {
      kohana::log('error', 'Error occurred running the following query from a service request:');
      kohana::log('error', $this->db->last_query());
      kohana::log('error', 'Request detail:');
      kohana::log('error', $this->uri->string());
      kohana::log('error', kohana::debug($_GET));
      throw $e;
    }
  }

  /**
  * Returns the name of the view for the request. This is a view
  * associated with the entity, but prefixed by either list, gv or max depending
  * on the GET view parameter.
  */
  protected function get_view_name()
  {
    $table = inflector::plural($this->entity);
    $prefix='';
    if (array_key_exists('view', $_GET))
    {
      $prefix = $_GET['view'];
    }
    // Check for allowed view prefixes, and use 'list' as the default
    if ($prefix!='gv' && $prefix!='detail')
      $prefix='list';
    return $prefix.'_'.$table;
  }


  /**
  * Works out what filter and other options to set on the db object according to the
  * $_GET parameters currently available, when retrieving a list of items.
  * @param boolean $count set to true when doing a count query, so the limit and offset are skipped
  */
  protected function apply_get_parameters_to_db($count=false)
  {
    $sortdir='ASC';
    $orderby='';
    $like=array();
    $where=array();
    foreach ($_GET as $param => $value)
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
            if ($this_cmd == $cmd || !empty($foundvalue)) $this->db->$this_cmd($infield,$foundvalue);
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

  protected function check_record_access($entity, $id, $website_id)
  {
    // if $id is null, then we have a new record, so no need to check if we have access to the record
    if (is_null($id))
      return true;
    $table = inflector::plural($entity);
      $viewname='list_'.$table;
      $db = new Database;
      $fields=$db->list_fields($viewname);
//      Kohana::log('info', $viewname.' : '.$this->entity.' '.$entity);
      if(empty($fields)) {
         Kohana::log('info', $viewname.' not present - access denied');
         throw new ServiceError('Access to entity '.$entity.' denied.');
      }
      $db->from($viewname);
      $db->where(array('id' => $id));

      if(!in_array ($entity, $this->allow_full_access)) {
          if(array_key_exists ('website_id', $fields))
            {
                $db->in('website_id', array(null, $this->website_id));
            } else {
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
