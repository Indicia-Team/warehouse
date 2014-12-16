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

class Rest_Controller extends Controller {
  private $method='GET';
  private $website_id;
  private $api_major_version=1;
  private $api_minor_version=0;
  private $supported_api_versions = array(
    '1.0'
  );
  private $request;
  private $projects;
  private $entity;
  
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
    'taxon_observations' => array('get'=>array(
      '' => array(
        'params' => array(
          'proj_id' => array(
            'datatype' => 'integer'
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
      ),
      '{taxon-observation ID}' => array(
        'params' => array()
      )
    )),
    'annotations' => array('get' => array(
      '' => array(
        'params' => array(
          'proj_id' => array(
            'datatype' => 'integer'
          )
        )
      ),
      '{annotation ID}' => array(
        'params' => array(
          'proj_id' => array(
            'datatype' => 'integer'
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
  
  public function index() {
    echo <<<'HTML'
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Indicia RESTful API</title>
</head>
<body>
<h1>RESTful API</h1>
HTML;
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
            'client_id' => array(
              'datatype' => 'integer',
              'help' => 'Unique identifier for the client making the webservice call'
            )
          ), $resourceDef['params']);
          echo '<table><caption>Parameters</caption>';
          foreach ($resourceDef['params'] as $name => $paramDef) {
            echo "<tr><th>$name</th>";
            echo "<td>$paramDef[datatype]</td>";
            $help = kohana::lang("rest_api.$resource.$name");
            echo "<td>$help</td>";
            echo "</tr>";
          }
          echo '</table>';
        }
      }
    }
    echo '</html>';
  }
  
  public function __call($name, $arguments) {
    $this->authenticate();
    if (array_key_exists($name, $this->http_methods)) {
      $this->method = $_SERVER['REQUEST_METHOD'];
      if ($this->method==='OPTIONS') {
        // A request for the methods allowed for this resource
        header('allow: '.strtoupper(implode(',', array_keys($this->http_methods[$name]))));
      } else {
        if (!array_key_exists(strtolower($this->method), $this->http_methods[$name])) {
          $this->fail('Method Not Allowed', 405, $this->method . " not allowed for $name");
        }
        if ($this->method==='GET') {
          $this->request = $_GET;
        } elseif ($this->method==='POST') {
          $this->request = $_POST;
        }
        
        $methodName = $name . '_' . strtolower($this->method);
        if (count($arguments) && preg_match('/^v(?P<major>\d+)\.(?P<minor>\d+)$/', $arguments[count($arguments)-1], $matches)) {
          array_pop($arguments);
          // Check not asking for an invalid version
          if (!in_array($matches['major'] . '.' . $matches['minor'], $this->supported_api_versions)) {
            throw new exception('Unsupported API version');
            // @todo: http response
          }
          $this->api_major_version = $matches['major'];
          $this->api_minor_version = $matches['minor'];
        }
      
        $requestForId = null;
        if (count($arguments)>1) {
          throw new exception('Incorrect number of arguments');
          // @todo: http response
        } elseif (count($arguments)===1) {
          // we only allow a single argument to request a single resource by ID
          if (preg_match('/^\d+$/', $arguments[0])) {
            $requestForId = $arguments[0];
          } else {
            throw new exception('Invalid ID requested');
            // @todo: http response
          }
        }
        // apart from requests for a project, we always want a project ID
        if ($name!=='projects' && empty($this->request['proj_id'])) {
          $this->fail('Bad Request', 400, 'Missing proj_id parameter');
        }
        if ($requestForId)
          $methodName .= '_id';
        $this->entity = $name;
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
   */
  public function projects_get() {
    // Add metadata such as href to each project
    foreach ($this->projects as $id => &$project) {
      $this->add_item_metadata($project, 'projects');
      unset($project['filter_id']);
    }
    $this->succeed(array_values($this->projects));
  }
  
  private function load_taxon_observations_report($params) {
    // @todo: rather than use the report engine and its overheads, build the query required directly?
    // Should also return an object to iterate rather than loading the full array
    $this->reportEngine = new ReportEngine(array($this->website_id));
    // load the filter associated with the project ID
    // @todo load filter
    // map the input filter to report parameters
    $report = $this->reportEngine->requestReport('library/occurrences/filterable_nbn_exchange.xml', 'local', 'xml', $params);
    return $report;
  }
  
  public function taxon_observations_get_id($id) {
    $params = array('occurrence_id' => $id);
    $report = $this->load_taxon_observations_report($params);
    if (empty($report['records'])) {
      $this->fail('No Content', 204);
    } elseif (count($report['records'])>1) {
      kohana::log('error', 'Internal error. Request for single record returned multiple');
      $this->fail('Internal Server Error', 500);
    } else {
      $this->add_item_metadata($report['records'][0], 'taxon-observations');
      $this->succeed($report['records'][0]);
    }
  }
  
  public function taxon_observations_get() {
    $this->checkPaginationParams();
    $params = array(
      // limit set to 1 more than we need, so we can ascertain if next page required
      'limit' => $this->request['page_size']+1,
      'offset' => ($this->request['page'] - 1) * $this->request['page_size']
    );
    $filter = $this->load_filter_for_project($this->request['proj_id']);
    // The project's filter acts as a context for the report, meaning it defines the limit of all the 
    // records that are available for this project.
    foreach ($filter as $key=>$value) {
      $params["{$key}_context"] = $value;
    }
    if (!empty($this->request['edited_date_from'])) {
      $this->checkDate($this->request['edited_date_from'], 'edited_date_from');
      $params['edited_date_from'] = $this->request['edited_date_from'];
    }
    if (!empty($this->request['edited_date_to'])) {
      $this->checkDate($this->request['edited_date_to'], 'edited_date_to');
      $params['edited_date_from'] = $this->request['edited_date_to'];
    }
    $report = $this->load_taxon_observations_report($params);
    $this->succeed($this->list_response_structure($report['content']['records'], 'taxon-observations'));
  }
  
  public function annotations_get($id) {
    
  }
  
  /**
   * Adds metadata such as an href back to the resource to any resource object.
   * @param array $item The resource object as an array which will be updated with the metadata
   * @param string $entity The entity name used to access the resouce, e.g. taxon-observations
   */
  private function add_item_metadata(&$item, $entity) {
    $params = $this->request;
    $item['href'] = url::base() . "index.php/services/rest/$entity/$item[id]?client_id=$params[client_id]";
    if (!empty($params['proj_id']))
      $item['href'] .= "&proj_id=$params[proj_id]";
    if (!empty($params['format']))
      $item['href'] .= "&format=$params[format]";
  }
  
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
  
  private function generate_link($replacements=array()) {
    $params = array_merge($_GET, $replacements);
    return url::base() . 'index.php/services/rest/' . $this->entity . '?' . http_build_query($params);
  }
  
  private function load_filter_for_project($id) {
    $filterId = $this->projects[$id]['filter_id'];
    $db = new Database();
    $filters = $db->select('definition')->from('filters')->where(array('id'=>$filterId, 'deleted'=>'f'))->get()->result_array();
    if (count($filters)!==1)
      $this->fail('Internal Server Error', 500, 'Failed to find unique project filter record');
    return json_decode($filters[0]->definition, true);
  }
  
  private function authenticate() {
    // @todo: implement proper hashing test
    if (empty($_REQUEST['client_id'])) {
      $this->fail('Bad request', 400, 'Missing client ID');
    }
    $this->website_id=$_REQUEST['client_id'];
    $projects = kohana::config('rest.projects');
    if (!array_key_exists($this->website_id, $projects)) {
      $this->fail('Unauthorized', 401, 'Client ID not in projects configuration');
    }
    $this->projects = $projects[$this->website_id];
  }
  
  private function checkInteger($value, $param) {
    if (!preg_match('/^\d+$/', $value)) {
      $this->fail('Bad request', 400, "Parameter $param is not an integer");
    }
  }
  
  private function checkDate($value, $param) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
      $this->fail('Bad request', 400, "Parameter $param is not an valid date");
    }
  }
  
  private function fail($msg, $code, $info=NULL) {
    http_response_code($code);
    echo $msg;
    if ($info)
      kohana::log('debug', $info);
    exit;
  }
  
  private function outputArrayAsHtml($array) {
    echo '<table border="1">';
    foreach ($array as $key=>$value) {
      echo "<tr><th>$key</th><td>";
      if (is_array($value))
        $this->outputArrayAsHtml($value);
      else {
        if ($key==='href' || $key==='self' || $key==='next' || $key==='previous')
          $value = "<a href=\"$value\">$value</a>";
        echo "<p>$value</p>";
      }
      echo '</td></tr>';  
    }
    echo '</table>';
  }
  
  private function succeed($data) {
    if (!empty($this->request['format']) && $this->request['format']==='html') {
      $this->outputArrayAsHtml($data);
    } else
      echo json_encode($data);
  }

  /** 
   * Ensures that the request contains a page size and page, defaulting the values if 
   * necessary.
   */
  private function checkPaginationParams() {
    $this->request = array_merge(array(
      'page' => 1,
      'page_size' => 100
    ), $this->request);
    $this->checkInteger($this->request['page'], 'page');
    $this->checkInteger($this->request['page_size'], 'page_size');
  }
}