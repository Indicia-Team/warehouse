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
 * @subpackage REST API
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link    http://code.google.com/p/indicia/
 */
 
 if (!function_exists('http_response_code')) {
  function http_response_code($code = NULL) {
    if ($code !== NULL) {
      switch ($code) {
        case 100: $text = 'Continue'; break;
        case 101: $text = 'Switching Protocols'; break;
        case 200: $text = 'OK'; break;
        case 201: $text = 'Created'; break;
        case 202: $text = 'Accepted'; break;
        case 203: $text = 'Non-Authoritative Information'; break;
        case 204: $text = 'No Content'; break;
        case 205: $text = 'Reset Content'; break;
        case 206: $text = 'Partial Content'; break;
        case 300: $text = 'Multiple Choices'; break;
        case 301: $text = 'Moved Permanently'; break;
        case 302: $text = 'Moved Temporarily'; break;
        case 303: $text = 'See Other'; break;
        case 304: $text = 'Not Modified'; break;
        case 305: $text = 'Use Proxy'; break;
        case 400: $text = 'Bad Request'; break;
        case 401: $text = 'Unauthorized'; break;
        case 402: $text = 'Payment Required'; break;
        case 403: $text = 'Forbidden'; break;
        case 404: $text = 'Not Found'; break;
        case 405: $text = 'Method Not Allowed'; break;
        case 406: $text = 'Not Acceptable'; break;
        case 407: $text = 'Proxy Authentication Required'; break;
        case 408: $text = 'Request Time-out'; break;
        case 409: $text = 'Conflict'; break;
        case 410: $text = 'Gone'; break;
        case 411: $text = 'Length Required'; break;
        case 412: $text = 'Precondition Failed'; break;
        case 413: $text = 'Request Entity Too Large'; break;
        case 414: $text = 'Request-URI Too Large'; break;
        case 415: $text = 'Unsupported Media Type'; break;
        case 500: $text = 'Internal Server Error'; break;
        case 501: $text = 'Not Implemented'; break;
        case 502: $text = 'Bad Gateway'; break;
        case 503: $text = 'Service Unavailable'; break;
        case 504: $text = 'Gateway Time-out'; break;
        case 505: $text = 'HTTP Version not supported'; break;
        default:
          exit('Unknown http status code "' . htmlentities($code) . '"');
        break;
      }
      $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
      header($protocol . ' ' . $code . ' ' . $text);
      $GLOBALS['http_response_code'] = $code;
    } else {
      $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
    }
    return $code;
  }
}

/**
 * Controller class for the RESTful API. Implements handlers for the variuos resource
 * URIs.
 * 
 * Visit index.php/services/rest for a help page.
 */
class Rest_Controller extends Controller {
  
  /**
   * The request method (GET, POST etc).
   * @var string
   */
  private $method;
  
  /**
   * The client's website ID (should point to a record in the websites table).
   * @var integer
   */
  private $website_id;
  
  /**
   * The latest API major version number. Unversioned calls will map to this.
   * @var integer
   */
  private $api_major_version=1;
  
  /**
   * The latest API minor version number. Unversioned calls will map to this.
   * @var integer
   */
  private $api_minor_version=0;
  
  /**
   * List of API versions that this code base will support.
   * @var array
   */
  private $supported_api_versions = array(
    '1.0'
  );
  
  /**
   * Holds the request parameters (e.g. from GET or POST data).
   * @var array
   */
  private $request;
  
  /** 
   * List of project definitions that are available to the authorised client.
   * @var array
   */
  private $projects;
  
  /**
   * The name of the resource being accessed.
   * @var string
   */
  private $resourceName;
  
  /**
   * A template to define the header of any HTML pages output. Replace {css} with the
   * path to the CSS file to load.
   * @var string
   */
  private $html_header = <<<'HTML'
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Indicia RESTful API</title>
  <link href="{css}" rel="stylesheet" type="text/css" />
</head>
<body>
HTML;
  
  /** 
   * Define the list of HTTP methods that will be supported by each resource endpoint.
   * @var type array
   */
  private $http_methods = array(
    // @todo: move all the help texts into an i18n config file. Don't load them on normal service calls, just
    // on the help service call.
    'projects' => array('get'=>array(
      '' => array(
        'params' => array()
      ),
      '{project ID}' => array(
        'params' => array()
      )
    )),
    'taxon-observations' => array('get'=>array(
      '' => array(
        'params' => array(
          'proj_id' => array(
            'datatype' => 'text',
            'required' => TRUE
          ),
          'page' => array(
            'datatype' => 'integer'
          ),
          'page_size' => array(
            'datatype' => 'integer'
          ),
          'edited_date_from' => array(
            'datatype' => 'date',
            'required' => TRUE
          ),
          'edited_date_to' => array(
            'datatype' => 'date'
          )
        )
      ),
      '{taxon-observation ID}' => array(
        'params' => array(
          'proj_id' => array(
            'datatype' => 'text'
          )
        )
      )
    )),
    'annotations' => array('get' => array(
      '' => array(
        'params' => array(
          'proj_id' => array(
            'datatype' => 'text'
          )
        )
      ),
      '{annotation ID}' => array(
        'params' => array(
          'proj_id' => array(
            'datatype' => 'text'
          ),
          'page' => array(
            'datatype' => 'integer'
          ),
          'page_size' => array(
            'datatype' => 'integer'
          ),
          'edited_date_from' => array(
            'datatype' => 'date'
          ),
          'edited_date_to' => array(
            'datatype' => 'date'
          )
        )
      )
    )),
  );
  
  /**
   * Controller for the default page for the /rest path. Outputs help text to describe
   * the available API resources.
   */
  public function index() {
    // A temporary array to simulate the arguments, which we can use to check for versioning.
    $arguments = array($this->uri->last_segment());
    $this->check_version($arguments);
    // Output an HTML page header
    $css = url::base() . "modules/rest_api/media/css/rest_api.css";
    echo str_replace('{css}', $css, $this->html_header);
    echo '<h1>RESTful API</h1>';
    // Loop the resource names and output each of the available methods.
    foreach($this->http_methods as $resource => $methods) {
      echo "<h2>$resource</h2>";
      foreach ($methods as $method => $listOrID) {
        foreach ($listOrID as $urlSuffix => $resourceDef) {
          echo '<h3>' . strtoupper($method) . ' ' . url::base() . "index.php/services/rest/$resource";
          if ($urlSuffix)
            echo "/$urlSuffix";
          echo '</h3>';
          $extra = $urlSuffix ? "/$urlSuffix" : '';
          $help = kohana::lang("rest_api.resources.$resource$extra");
          echo "<p>$help</p>";
          // output the documentation for parameters. All requests require a client ID...
          $resourceDef['params'] = array_merge(array(
            'system_id' => array(
              'datatype' => 'integer',
              'required' => TRUE,
              'help' => 'Unique identifier for the client system making the webservice call'
            )
          ), $resourceDef['params']);
          echo '<table><caption>Parameters</caption>';
          echo '<thead><th scope="col">Name</th><th scope="col">Data type</th><th scope="col">Description</th></thead>';
          echo '<tbody>';
          foreach ($resourceDef['params'] as $name => $paramDef) {
            echo "<tr><th scope=\"row\">$name</th>";
            echo "<td>$paramDef[datatype]</td>";
            $help = kohana::lang("rest_api.$resource.$name");
            if (!empty($paramDef['required']))
              $help .= ' <strong>' . kohana::lang('Required.') .'</strong>';
            echo "<td>$help</td>";
            echo "</tr>";
          }
          echo '</tbody></table>';
        }
      }
    }
    echo '</body></html>';
  }
  
  /**
   * Magic method to handle calls to the various resource end-points. Maps the call
   * to a method name defined by the resource and the request method.
   * 
   * @param string $name Resource name as defined by the segment of the URI called. 
   * Note that this resource name has already passed through the router and had hyphens
   * converted to underscores.
   * @param array $arguments Additional arguments, for example the ID of a resource being requested.
   * @throws exception
   */
  public function __call($name, $arguments) {
    $resourceName = str_replace('_', '-', $name);
    $this->authenticate();
    if (array_key_exists($resourceName, $this->http_methods)) {
      $this->method = $_SERVER['REQUEST_METHOD'];
      if ($this->method==='OPTIONS') {
        // A request for the methods allowed for this resource
        header('allow: '.strtoupper(implode(',', array_keys($this->http_methods[$resourceName]))));
      } else {
        if (!array_key_exists(strtolower($this->method), $this->http_methods[$resourceName])) {
          $this->fail('Method Not Allowed', 405, $this->method . " not allowed for $name");
        }
        if ($this->method==='GET') {
          $this->request = $_GET;
        } elseif ($this->method==='POST') {
          $this->request = $_POST;
        }
        
        $methodName = $name . '_' . strtolower($this->method);
        $this->check_version($arguments);
      
        $requestForId = null;
        if (count($arguments)>1) {
          $this->fail('Bad request', 400, 'Incorrect number of arguments');
          // @todo: http response
        } elseif (count($arguments)===1) {
          // we only allow a single argument to request a single resource by ID
          if (preg_match('/^[A-Z]{3}\d+$/', $arguments[0])) {
            $requestForId = $arguments[0];
          } else {
            $this->fail('Bad request', 400, 'Invalid ID requested');
          }
        }
        // apart from requests for a project, we always want a project ID
        if ($name!=='projects' && empty($this->request['proj_id'])) {
          $this->fail('Bad Request', 400, 'Missing proj_id parameter');
        }
        if ($requestForId)
          $methodName .= '_id';
        $this->resourceName = $name;
        $this->validateParameters($resourceName, strtolower($this->method), $requestForId);
        call_user_func(array($this, $methodName), $requestForId);
      }
    } else {
      $this->fail('Not Found', 404, "Resource $name not known");
    }
  }
  
  /**
   * GET handler for the projects/n resource. Outputs a single project's details.
   * 
   * @param type $id Unique ID for the project to output
   */
  public function projects_get_id($id) {
    if (!array_key_exists($id, $this->projects)) {
      $this->fail('No Content', 204);
    }
    $this->add_item_metadata($this->projects[$id], 'projects');
    $this->succeed($this->projects[$id]);
  }
     
  /**
   * GET handler for the projects resource. Outputs a list of project details.
   * @todo Projecst are currently hard coded in the config file, so pagination etc
   * is just stub code.
   */
  public function projects_get() {
    // Add metadata such as href to each project
    foreach ($this->projects as $id => &$project) {
      // Add metadata such as href to each project
      $this->add_item_metadata($project, 'projects');
      // remove fields from the project that are for internal use only
      unset($project['filter_id']);
      unset($project['sharing']);
    }
    $this->succeed(array(
      'data' => array_values($this->projects),
      'paging' => array(
        'self' => $this->generate_link(array('page'=>1))
      )
    ));
  }
  
  /**
   * GET handler for the taxon-observations/n resource. Outputs a single taxon observations's details.
   * 
   * @param type $id Unique ID for the taxon-observations to output
   */
  public function taxon_observations_get_id($id) {
    $params = array('occurrence_id' => $id);
    $report = $this->load_report('filterable_nbn_exchange', $params);
    if (empty($report['content']['records'])) {
      $this->fail('No Content', 204);
    } elseif (count($report['content']['records'])>1) {
      kohana::log('error', 'Internal error. Request for single record returned multiple');
      $this->fail('Internal Server Error', 500);
    } else {
      $this->add_item_metadata($report['content']['records'][0], 'taxon-observations');
      $this->succeed($report['content']['records'][0]);
    }
  }
  
  /**
   * GET handler for the taxon-observations resource. Outputs a list of taxon observation details.
   */
  public function taxon_observations_get() {
    $this->checkPaginationParams();
    $params = array(
      // limit set to 1 more than we need, so we can ascertain if next page required
      'limit' => $this->request['page_size']+1,
      'offset' => ($this->request['page'] - 1) * $this->request['page_size']
    );
    if (!empty($this->request['edited_date_from'])) {
      $this->checkDate($this->request['edited_date_from'], 'edited_date_from');
      $params['edited_date_from'] = $this->request['edited_date_from'];
    }
    if (!empty($this->request['edited_date_to'])) {
      $this->checkDate($this->request['edited_date_to'], 'edited_date_to');
      $params['edited_date_to'] = $this->request['edited_date_to'];
    }
    $report = $this->load_report('filterable_taxon_observations', $params);
    $this->succeed($this->list_response_structure($report['content']['records'], 'taxon-observations'));
  }

  /**
   * GET handler for the annotations/n resource. Outputs a single annotations's details.
   * 
   * @param type $id Unique ID for the annotations to output
   */
  public function annotations_get_id($id) {
    $params = array('id' => $id);
    $report = $this->load_report('filterable_annotations', $params);
    if (empty($report['content']['records'])) {
      $this->fail('No Content', 204);
    } elseif (count($report['content']['records'])>1) {
      kohana::log('error', 'Internal error. Request for single annotation returned multiple');
      $this->fail('Internal Server Error', 500);
    } else {
      $record = $report['content']['records'][0];
      $record['taxonObservation'] = array(
        'id' => $record['taxon_observation_id'],
      );
      $this->add_item_metadata($record['taxonObservation'], 'taxon-observations');
      $this->add_item_metadata($record, 'annotations');
      $this->succeed($record);
    }
  }
  
  /**
   * GET handler for the annotations resource. Outputs a list of annotation details.
   */
  public function annotations_get() {
    // @todo Integrate determinations in the output
    // @todo handle taxonversionkey properly
    // @todo handle unansweredQuestion
    $this->checkPaginationParams();
    $params = array(
      // limit set to 1 more than we need, so we can ascertain if next page required
      'limit' => $this->request['page_size']+1,
      'offset' => ($this->request['page'] - 1) * $this->request['page_size']
    );
    if (!empty($this->request['edited_date_from'])) {
      $this->checkDate($this->request['edited_date_from'], 'edited_date_from');
      $params['comment_edited_date_from'] = $this->request['edited_date_from'];
    }
    if (!empty($this->request['edited_date_to'])) {
      $this->checkDate($this->request['edited_date_to'], 'edited_date_to');
      $params['comment_edited_date_to'] = $this->request['edited_date_to'];
    }
    $report = $this->load_report('filterable_annotations', $params);
    $records = $report['content']['records'];
    // for each record, restructure the taxon observations sub-object
    foreach ($records as &$record) {
      $record['taxonObservation'] = array(
        'id' => $record['taxon_observation_id'],
      );
      $this->add_item_metadata($record['taxonObservation'], 'taxon-observations');
      unset($record['taxon_observation_id']);
    }
    $this->succeed($this->list_response_structure($records, 'annotations'));
  }
  
  /**
   * Validates that the request parameters provided fullful the requirements of the method being called.
   * @param string $resourceName
   * @param string $method Method name, e.g. GET or POST. 
   */
  private function validateParameters($resourceName, $method, $requestForId) {
    $info = $this->http_methods[$resourceName][$method];
    // if requesting a list, then use the entry keyed '', else use the named entry
    if ($requestForId) {
      foreach ($info as $key => $method) {
        if ($key !== '') {
          $thisMethod = $method;
          break;
        }
      }
    } else {
      $thisMethod = $info[''];
    }
    // Check through the known list of parameters to ensure data formats are correct and required parameters are provided.
    foreach ($thisMethod['params'] as $paramName => $paramDef) {
      if (!empty($paramDef['required']) && empty($this->request[$paramName])) {
        $this->fail('Bad request', 400, "Missing $paramName parameter");
      }
      if (!empty($this->request[$paramName])) {
        if ($paramDef['datatype']==='integer' && !preg_match('/^\d+$/', trim($this->request[$paramName]))) {
          $this->fail('Bad request', 400, "Invalid format for $paramName parameter");
        }
        if ($paramDef['datatype']==='date') {
          $dt = DateTime::createFromFormat("Y-m-d", trim($this->request[$paramName]));
          if ($dt === false || array_sum($dt->getLastErrors()))
            $this->fail('Bad request', 400, "Invalid date for $paramName parameter");
        }
      }
    }
  }
  
  /**
   * Adds metadata such as an href back to the resource to any resource object.
   * @param array $item The resource object as an array which will be updated with the metadata
   * @param string $entity The entity name used to access the resouce, e.g. taxon-observations
   */
  private function add_item_metadata(&$item, $entity) {
    $params = $this->request;
    $item['href'] = url::base() . "index.php/services/rest/$entity/$item[id]?system_id=$params[system_id]";
    if (!empty($params['proj_id']))
      $item['href'] .= "&proj_id=$params[proj_id]";
    if (!empty($params['format']))
      $item['href'] .= "&format=$params[format]";
    // strip nulls and empty strings
    $item = array_filter($item, 'strlen');
  }
  
  /**
   * Converts an array list of items loaded from the database into the structure ready for returning
   * as the result from an API call. Adds pagination information as well as hrefs for contained objects.
   * 
   * @param type $list Array of records from the database
   * @param type $entity Resource name that is being accessed.
   * @return array Restructured version of the input list, with pagination and hrefs added.
   */
  private function list_response_structure($list, $entity) {
    foreach ($list as &$item) {
      $this->add_item_metadata($item, $entity);
    }
    $pagination = array(
      'self'=>$this->generate_link(array('page'=>$this->request['page'])),
    );
    if ($this->request['page']>1)
      $pagination['previous'] = $this->generate_link(array('page'=>$this->request['page']-1));
    // list needs to grab 1 extra, then lop it off and set a flag to indicate another page required
    if (count($list)>$this->request['page_size']) {
      array_pop($list);
      $pagination['next'] = $this->generate_link(array('page'=>$this->request['page']+1));
    }
    return array(
      'pagination' => $pagination,
      'data' => $list
    );
  }
  
  /**
   * Method to load the output of a report being used to construct an API call GET response.
   * 
   * @param string $report Report name (excluding .xml extension)
   * @param array $params Report parameters in an associative array
   * @return array Report response structure
   */
  private function load_report($report, $params) {
    // @todo: rather than use the report engine and its overheads, build the query required directly?
    // Should also return an object to iterate rather than loading the full array
    $this->reportEngine = new ReportEngine(array($this->website_id));
    // load the filter associated with the project ID
    $filter = $this->load_filter_for_project($this->request['proj_id']);
    // The project's filter acts as a context for the report, meaning it defines the limit of all the 
    // records that are available for this project.
    foreach ($filter as $key=>$value) {
      $params["{$key}_context"] = $value;
    }
    // the project defines how records are allowed to be shared with this client
    $params['sharing'] = $this->projects[$this->request['proj_id']]['sharing'];
    $params['system_id'] = kohana::config('rest.system_id');
    $report = $this->reportEngine->requestReport("rest_api/$report.xml", 'local', 'xml', $params);
    return $report;
  }
  
  /**
   * Regenerates the current GET URI link, but replacing one or more paraneters with a new value,
   * e.g. a new page ID.
   * 
   * @param array $replacements List of parameters and values to replace
   * @return string The reconstructed URL.
   */
  private function generate_link($replacements=array()) {
    $params = array_merge($_GET, $replacements);
    return url::base() . 'index.php/services/rest/' . $this->resourceName . '?' . http_build_query($params);
  }
  
  private function load_filter_for_project($id) {
    $filterId = $this->projects[$id]['filter_id'];
    $db = new Database();
    $filters = $db->select('definition')->from('filters')->where(array('id'=>$filterId, 'deleted'=>'f'))->get()->result_array();
    if (count($filters)!==1)
      $this->fail('Internal Server Error', 500, 'Failed to find unique project filter record');
    return json_decode($filters[0]->definition, true);
  }
  
  /**
   * Checks the API version provided in the URI (if any) to ensure that the version is supported.
   * Returns a 400 Bad request if not supported.
   * @param array $arguments Additional URI segments
   */  
  private function check_version(&$arguments) {
    if (count($arguments) && preg_match('/^v(?P<major>\d+)\.(?P<minor>\d+)$/', $arguments[count($arguments)-1], $matches)) {
      array_pop($arguments);
      // Check not asking for an invalid version
      if (!in_array($matches['major'] . '.' . $matches['minor'], $this->supported_api_versions)) {
        $this->fail('Bad request', 400, 'Unsupported API version');
      }
      $this->api_major_version = $matches['major'];
      $this->api_minor_version = $matches['minor'];
    }
  }
  
  /** 
   * Ensures that the request contains a page size and page, defaulting the values if 
   * necessary. 
   * Will return an HTTP error response if either parameter is not an integer.
   */
  private function checkPaginationParams() {
    $this->request = array_merge(array(
      'page' => 1,
      'page_size' => 100
    ), $this->request);
    $this->checkInteger($this->request['page'], 'page');
    $this->checkInteger($this->request['page_size'], 'page_size');
  }
  
  /**
   * Checks that the request's system_id and proj_id are valid.
   * @todo Implement hash based authentication
   */
  private function authenticate() {
    // @todo: implement proper hashing test
    if (empty($_REQUEST['system_id'])) {
      $this->fail('Bad request', 400, 'Missing system ID');
    }
    $config = kohana::config('rest.clients');
    if (!array_key_exists($_REQUEST['system_id'], $config)) {
      $this->fail('Unauthorized', 401, 'System ID not in projects configuration');
    }
    $this->website_id=$config[$_REQUEST['system_id']]['website_id'];
    $this->projects = $config[$_REQUEST['system_id']]['projects'];
  }
  
  /**
   * Checks a parameter passed to a request is a valid integer.
   * Returns an HTTP error response if not valid.
   * @param string $value Parameter to check
   * @param type $param Name of the parameter being checked.
   */
  private function checkInteger($value, $param) {
    if (!preg_match('/^\d+$/', $value)) {
      $this->fail('Bad request', 400, "Parameter $param is not an integer");
    }
  }
  
  /**
   * Checks a parameter passed to a request is a valid date.
   * Returns an HTTP error response if not valid.
   * @param string $value Parameter to check
   * @param type $param Name of the parameter being checked.
   */
  private function checkDate($value, $param) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
      $this->fail('Bad request', 400, "Parameter $param is not an valid date");
    }
  }
  
    
  /**
   * Dumps out a nested array as a nested HTML table. Used to output response data when the 
   * format type requested is HTML.
   * 
   * @param array $array Data to output
   */
  private function output_array_as_html($array) {
    if (count($array)) {
      echo '<table border="1">';
      $keys = array_keys($array);
      $col1 = is_integer($keys[0]) ? 'Row' : 'Field';
      $col2 = is_integer($keys[0]) ? 'Record' : 'Value';
      echo "<thead><th scope=\"col\">$col1</th><th scope=\"col\">$col2</th></thead>";
      echo '<tbody>';
      foreach ($array as $key=>$value) {
        echo "<tr><th scope=\"row\">$key</th><td>";
        if (is_array($value))
          $this->output_array_as_html($value);
        else {
          if ($key==='href' || $key==='self' || $key==='next' || $key==='previous')
            $value = "<a href=\"$value\">$value</a>";
          echo "<p>$value</p>";
        }
        echo '</td></tr>';  
      }
      echo '</tbody></table>';
    }
  }
  
  /** 
   * Returns an HTML error response code, logs a message and aborts the script.
   * 
   * @param string $msg HTTP error message
   * @param integer $code HTTP error code
   * @param string $info Message to log
   */
  private function fail($msg, $code, $info=NULL) {
    http_response_code($code);
    echo $msg;
    if ($info)
      kohana::log('debug', $info);
    exit;
  }

  /**
   * Outputs a data object as JSON (or chosen alternative format), in the case of successful operation.
   * 
   * @param array $data Response data to output.
   */
  private function succeed($data) {
    if (!empty($this->request['format']) && $this->request['format']==='html') {
      $css = url::base() . "modules/rest_api/media/css/rest_api.css";
      echo str_replace('{css}', $css, $this->html_header);
      $this->output_array_as_html($data);
      echo '</body></html>';
    } else
      echo json_encode($data);
  }

}