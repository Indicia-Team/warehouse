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

if( !function_exists('apache_request_headers') ) {
  Kohana::log('debug', 'PHP apache_request_headers() function does not exist. Replacement function used.');
  function apache_request_headers() {
    $arh = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
          foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst(strtolower($ak_val));
          $arh_key = implode('-', $rx_matches);
        }
        $arh[$arh_key] = $val;
      }
    }
    return( $arh );
  }
}

class RestApiAbort extends Exception {}

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
   * Set to true for https.
   * @var bool
   */
  private $isHttps;

  /**
   * Has the request been authenticated?
   * @var bool
   */
  private $authenticated = FALSE;

  /**
   * If the called resource only supports certain types of authentication, then
   * an array of the methods is set here allowing other methods to be blocked;
   * @var bool|array
   */
  private $restrictToAuthenticationMethods = FALSE;

  /**
   * The server's user ID (i.e. this REST API)
   * @var string
   */
  private $serverUserId;

  /**
   * The client's system ID (i.e. the caller) if authenticated against the list of
   * configured clients.
   * @var string
   */
  private $clientSystemId;

  /**
   * The client's website ID (i.e. the caller) if authenticated against the websites table
   * @var string
   */
  private $clientWebsiteId;

  /**
   * The client's user ID (i.e. the caller) if authenticated against the users table
   * @var string
   */
  private $clientUserId;

  /**
   * The client's filter ID (i.e. the records available to the called) if
   * authenticated against the users table and a permissions filter provided
   * in the auth data.
   * @var string
   * @todo Implement usage of this
   */
  private $clientFilterId;

  /**
   * The latest API major version number. Unversioned calls will map to this.
   * @var integer
   */
  private $apiMajorVersion=1;

  /**
   * The latest API minor version number. Unversioned calls will map to this.
   * @var integer
   */
  private $apiMinorVersion=0;

  /**
   * List of API versions that this code base will support.
   * @var array
   */
  private $supportedApiVersions = array(
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

  private $includeEmptyValues = true;

  /*
   * HTML built dynamically for the page output index.
   * @var string
   */
  private $index = '';

  /**
   * Is an index table required for this response when output as HTML?
   * @var bool
   */
  private $wantIndex = false;

  /**
   * When outputting HTML this contains the title for the page.
   * @var string
   */
  private $responseTitle = '';

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
    'projects' => array(
      'get'=>array(
        'subresources' => array(
          '' => array(
            'params' => array()
          ),
          '{project ID}' => array(
            'params' => array()
          )
        )
      )
    ),
    'taxon-observations' => array(
      'get'=>array(
        'subresources' => array(
          '' => array(
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
        )
      ),
      'post' => array(
        'subresources' => array(
          '' => array(
            'params' => array()
          )
        )
      )
    ),
    'annotations' => array(
      'get' => array(
        'subresources' => array(
          '' => array(
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
          ),
          '{annotation ID}' => array(
            'params' => array(
              'proj_id' => array(
                'datatype' => 'text'
              )
            )
          )
        )
      )
    ),
    'reports' => array(
      'get' => array(
        'options' => array(
          'segments' => TRUE
        ),
        'subresources' => array(
          '' => array(
            'params' => array(
              'limit' => array(
                'datatype' => 'integer'
              ),
              'offset' => array(
                'datatype' => 'integer'
              ),
              'sortby' => array(
                'datatype' => 'text'
              ),
              'sortdir' => array(
                'datatype' => 'text'
              )
            )
          )
        )
      )
    )
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
      foreach ($methods as $method => $methodConfig) {
        foreach ($methodConfig['subresources'] as $urlSuffix => $resourceDef) {
          echo '<h3>' . strtoupper($method) . ' ' . url::base() . "index.php/services/rest/$resource";
          if ($urlSuffix)
            echo "/$urlSuffix";
          echo '</h3>';
          $extra = $urlSuffix ? "/$urlSuffix" : '';
          $help = kohana::lang("rest_api.resources.$resource$extra");
          echo "<p>$help</p>";
          if (count($resourceDef['params'])) {
            // output the documentation for parameters.
            echo '<table><caption>Parameters</caption>';
            echo '<thead><th scope="col">Name</th><th scope="col">Data type</th><th scope="col">Description</th></thead>';
            echo '<tbody>';
            foreach ($resourceDef['params'] as $name => $paramDef) {
              echo "<tr><th scope=\"row\">$name</th>";
              echo "<td>$paramDef[datatype]</td>";
              $help = kohana::lang("rest_api.$resource.$name");
              if (!empty($paramDef['required'])) {
                $help .= ' <strong>' . kohana::lang('Required.') . '</strong>';
              }
              echo "<td>$help</td>";
              echo "</tr>";
            }
            echo '</tbody></table>';
          } else {
            echo '<p><em>There are no parameters for this endpoint.</em></p>';
          }
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
    kohana::log('debug', 'GET: ' . var_export($_GET, true));
    try {
      // undo router's conversion of hyphens and underscores
      $this->resourceName = str_replace('_', '-', $name);
      // Projects are a concept of client system based authentication, not websites or users.
      if ($this->resourceName === 'projects') {
        $this->restrictToAuthenticationMethods = array(
          'hmacClient',
          'directClient'
        );
        kohana::log('debug', 'limit to ' . var_export($this->restrictToAuthenticationMethods, true));
      }
      $this->authenticate();
      if (array_key_exists($this->resourceName, $this->http_methods)) {
        $resourceConfig = $this->http_methods[$this->resourceName];
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method === 'OPTIONS') {
          // A request for the methods allowed for this resource
          header('allow: ' . strtoupper(implode(',', array_keys($resourceConfig))));
        }
        else {
          if (!array_key_exists(strtolower($this->method), $resourceConfig)) {
            $this->fail('Method Not Allowed', 405, $this->method . " not allowed for $name");
          }
          $methodConfig = $resourceConfig[strtolower($this->method)];
          // If segments allowed, the URL can be .../resource/x/y/z etc.
          $allowSegments = isset($methodConfig['options']) &&
            !empty($methodConfig['options']['segments']);
          if ($this->method === 'GET') {
            $this->request = $_GET;
          }
          elseif ($this->method === 'POST') {
            $this->request = $_POST;
          }

          $methodName = $name . '_' . strtolower($this->method);
          $this->check_version($arguments);

          $requestForId = NULL;

          if (!$allowSegments && count($arguments) > 1) {
            $this->fail('Bad request', 400, 'Incorrect number of arguments');
            // @todo: http response
          }
          elseif (!$allowSegments && count($arguments) === 1) {
            // we only allow a single argument to request a single resource by ID
            if (preg_match('/^[A-Z]{3}\d+$/', $arguments[0])) {
              $requestForId = $arguments[0];
            }
            else {
              $this->fail('Bad request', 400, 'Invalid ID requested '.$arguments[0]);
            }
          }
          // apart from requests for a project, we always want a project ID
          if (isset($this->clientSystemId) && $name !== 'projects') {
            if (empty($this->request['proj_id']))
              // Should not have got this far - just in case
              $this->fail('Bad Request', 400, 'Missing proj_id parameter');
            else
              $this->checkAllowedResource($this->request['proj_id'], $this->resourceName);
          }
          if ($requestForId) {
            $methodName .= '_id';
          }
          $this->validateParameters($this->resourceName, strtolower($this->method), $requestForId);
          call_user_func(array($this, $methodName), $requestForId);
        }
      }
      else {
        $this->fail('Not Found', 404, "Resource $name not known");
      }
    }
    catch (RestApiAbort $e) {
      // no action if a proper abort
    }
  }

  /**
   * A project can include a configuration of the resources it exposes, for example
   * it might only expose annotations if the top copy of a record is elsewhere. This
   * method checks that the requested resource is available for the project and
   * aborts with 204 No Content if not.
   * @param integer $proj_id The project ID
   * @param string $resourceName The resource being requested, e.g. taxon-observations.
   * @throws \RestApiAbort
   */
  private function checkAllowedResource($proj_id, $resourceName) {
    if (isset($this->projects[$proj_id]['resources'])) {
      if (!in_array($resourceName, $this->projects[$proj_id]['resources'])) {
        kohana::log('debug', "Disallowed resource $resourceName for $proj_id");
        $this->fail('No Content', 204);
      }
    }
  }

  /**
   * GET handler for the  projects/n resource. Outputs a single project's details.
   *
   * @param type $id Unique ID for the project to output
   */
  private function projects_get_id($id) {
    if (!array_key_exists($id, $this->projects)) {
      $this->fail('No Content', 204);
    }
    $this->addItemMetadata($this->projects[$id], 'projects');
    // remove fields from the project that are for internal use only
    unset($this->projects[$id]['filter_id']);
    unset($this->projects[$id]['website_id']);
    unset($this->projects[$id]['sharing']);
    unset($this->projects[$id]['resources']);
    $this->succeed($this->projects[$id]);
  }

  /**
   * GET handler for the projects resource. Outputs a list of project details.
   * @todo Projects are currently hard coded in the config file, so pagination etc
   * is just stub code.
   */
  private function projects_get() {
    // Add metadata such as href to each project
    foreach ($this->projects as $id => &$project) {
      // Add metadata such as href to each project
      $this->addItemMetadata($project, 'projects');
      // remove fields from the project that are for internal use only
      unset($project['filter_id']);
      unset($project['website_id']);
      unset($project['sharing']);
      unset($project['resources']);
    }
    $this->succeed(array(
      'data' => array_values($this->projects),
      'paging' => array(
        'self' => $this->generateLink(array('page'=>1))
      )
    ));
  }

  /**
   * GET handler for the taxon-observations/n resource. Outputs a single taxon observations's details.
   *
   * @param type $id Unique ID for the taxon-observations to output
   */
  private function taxon_observations_get_id($id) {
    if (substr($id, 0, strlen(kohana::config('rest.user_id')))===kohana::config('rest.user_id')) {
      $occurrence_id = substr($id, strlen(kohana::config('rest.user_id')));
      $params = array('occurrence_id' => $occurrence_id);
    } else {
      // @todo What happens if system not recognised?
      $params = array('external_key' => $id);
    }
    $params['dataset_name_attr_id'] = kohana::config('rest.dataset_name_attr_id');

    $report = $this->loadReport('rest_api/filterable_taxon_observations', $params);
    if (empty($report['content']['records'])) {
      $this->fail('No Content', 204);
    } elseif (count($report['content']['records'])>1) {
      kohana::log('error', 'Internal error. Request for single record returned multiple');
      $this->fail('Internal Server Error', 500);
    } else {
      $this->addItemMetadata($report['content']['records'][0], 'taxon-observations');
      $this->succeed($report['content']['records'][0]);
    }
  }

  /**
   * GET handler for the taxon-observations resource. Outputs a list of taxon observation details.
   * @todo Ensure delete information is output.
   */
  private function taxon_observations_get() {
    $this->checkPaginationParams();
    $params = array(
      // limit set to 1 more than we need, so we can ascertain if next page required
      'limit' => $this->request['page_size'] + 1,
      'offset' => ($this->request['page'] - 1) * $this->request['page_size']
    );
    $this->checkDate($this->request['edited_date_from'], 'edited_date_from');
    $params['edited_date_from'] = $this->request['edited_date_from'];
    if (!empty($this->request['edited_date_to'])) {
      $this->checkDate($this->request['edited_date_to'], 'edited_date_to');
      $params['edited_date_to'] = $this->request['edited_date_to'];
    }
    $params['dataset_name_attr_id'] = kohana::config('rest.dataset_name_attr_id');
    $report = $this->loadReport('rest_api/filterable_taxon_observations', $params);
    $this->succeed($this->listResponseStructure($report['content']['records'], 'taxon-observations'));
  }

  /**
   * GET handler for the annotations/n resource. Outputs a single annotations's details.
   *
   * @param type $id Unique ID for the annotations to output
   */
  private function annotations_get_id($id) {
    $params = array('id' => $id);
    $report = $this->loadReport('rest_api/filterable_annotations', $params);
    if (empty($report['content']['records'])) {
      $this->fail('No Content', 204);
    } elseif (count($report['content']['records'])>1) {
      kohana::log('error', 'Internal error. Request for single annotation returned multiple');
      $this->fail('Internal Server Error', 500);
    } else {
      $record = $report['content']['records'][0];
      $record['taxonObservation'] = array(
        'id' => $record['taxon_observation_id'],
        // @todo href
      );
      $this->addItemMetadata($record['taxonObservation'], 'taxon-observations');
      $this->addItemMetadata($record, 'annotations');
      $this->succeed($record);
    }
  }

  /**
   * GET handler for the annotations resource. Outputs a list of annotation details.
   */
  private function annotations_get() {
    // @todo Integrate determinations in the output
    // @todo handle taxonVersionKey properly
    // @todo handle unansweredQuestion
    $this->checkPaginationParams();
    $params = array(
      // limit set to 1 more than we need, so we can ascertain if next page required
      'limit' => $this->request['page_size'] + 1,
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
    $report = $this->loadReport('rest_api/filterable_annotations', $params);
    $records = $report['content']['records'];
    // for each record, restructure the taxon observations sub-object
    foreach ($records as &$record) {
      $record['taxonObservation'] = array(
        'id' => $record['taxon_observation_id'],
      );
      $this->addItemMetadata($record['taxonObservation'], 'taxon-observations');
      unset($record['taxon_observation_id']);
    }
    $this->succeed($this->listResponseStructure($records, 'annotations'));
  }

  /**
   * Converts the segments in the URL to a full report path suitable for passing
   * to the report engine.
   * @param array $segments
   * @return string
   */
  private function getReportFileNameFromSegments($segments) {
    // report file specified. Don't need the .xml suffix.
    $fileName = array_pop($segments);
    $fileName = substr($fileName, 0, strlen($fileName) - 4);
    $segments[] = $fileName;
    return implode('/', $segments);
  }

  private function reports_get() {
    $segments = $this->uri->segment_array();
    array_shift($segments);
    array_shift($segments);
    array_shift($segments);
    if (count($segments) && preg_match('/\.xml$/', $segments[count($segments)-1])) {
      $this->getReportOutput($segments);
    } elseif (count($segments)>1 && preg_match('/\.xml$/', $segments[count($segments)-2])) {
      // Passing a sub-action to a report, e.g. /params
      if ($segments[count($segments)-1] === 'params') {
        $this->getReportParams($segments);
      }
      if ($segments[count($segments)-1] === 'columns') {
        $this->getReportColumns($segments);
      }
    } else {
      $this->getReportHierarchy($segments);
    }
  }

  /**
   * Uses the segments in the URL to find a report file and run it, with the
   * expectation of producing report data output.
   * @param array $segments
   */
  private function getReportOutput($segments) {
    $reportFile = $this->getReportFileNameFromSegments($segments);
    $report = $this->loadReport($reportFile, $_GET);
    if (isset($report['content']['records'])) {
      // @todo: implement pagination
      $this->succeed(array('data' => $report['content']['records']));
    } elseif (isset($report['content']['parameterRequest'])) {
      // @todo: handle param requests
      $this->fail('Bad request (parameters missing)', 400, "Missing parameters");
    }
  }

  /**
   *
   */
  private function getReportMetadataItem($segments, $item, $description) {
    $this->includeEmptyValues = false;
    // the last segment is the /params action.
    array_pop($segments);
    $reportFile = $this->getReportFileNameFromSegments($segments);
    $this->loadReportEngine();
    $metadata = $this->reportEngine->requestMetadata("$reportFile.xml", true);
    $this->responseTitle = ucfirst("$item for $reportFile");
    $this->wantIndex = true;
    $this->succeed(array('data' => $metadata[$item]),
      array('description' => $description));
  }

  /**
   * Uses the segments in the URL to find a report file and retrieve the parameters
   * metadata for it.
   * @param array $segments
   */
  private function getReportParams($segments) {
    return $this->getReportMetadataItem($segments, 'parameters',
      'A list of parameters available for filtering this report.');
  }

  /**
   * Uses the segments in the URL to find a report file and retrieve the parameters
   * metadata for it.
   * @param array $segments
   */
  private function getReportColumns($segments) {
    return $this->getReportMetadataItem($segments, 'columns',
      'A list of columns provided in the output of this report.');
  }

  /**
   * Retrieves a list of folders and report files at a single location in the report
   * hierarchy.
   * @param $segments
   */
  private function getReportHierarchy($segments) {
    $this->loadReportEngine();
    // @todo Cache this
    $reportHierarchy = $this->reportEngine->reportList();
    $response = array();
    $folderReadme = '';
    $featuredFolder = (count($segments) === 1 && $segments[0] === 'featured');
    if ($featuredFolder) {
      $folderReadme = kohana::lang("rest_api.reports.featured_folder_description");
    } else {
      // Iterate down the report hierarchy to the level we want to show according to the request.
      foreach ($segments as $idx => $segment) {
        if ($idx === count($segments) - 1) {
          // If the final folder, then grab any readme text to add to the metadata.
          $folderReadme = empty($reportHierarchy[$segment]['description']) ?
            '' : $reportHierarchy[$segment]['description'];
        }
        $reportHierarchy = $reportHierarchy[$segment]['content'];
      }
    }
    $relativePath = implode('/', $segments);
    if (empty($segments)) {
      // top level, so splice in a virtual folder for all featured reports.
      $reportHierarchy = array(
          'featured' => array(
            'type' => 'folder',
            'description' => kohana::lang("rest_api.reports.featured_folder_description")
          )
        ) + $reportHierarchy;
    }
    if ($featuredFolder) {
      $response = array();
      $this->getFeaturedReports($reportHierarchy, $response);
    } else {
      foreach ($reportHierarchy as $key => $metadata) {
        unset($metadata['content']);
        if ($metadata['type'] === 'report') {
          $this->addReportLinks($metadata);
        }
        else {
          $path = empty($relativePath) ? $key : "$relativePath/$key";
          $metadata['href'] = $this->getUrlWithCurrentParams("reports/$path");
        }
        $response[$key] = $metadata;
      }
    }
    // Build a description. A generic statement about the path, plus anything
    // included in the folder's readme file.
    $relativePath = '/reports/' . ($relativePath ? "$relativePath/" : '');
    $description = 'A list of reports and report folders stored on the warehouse under ' .
      "the folder <em>$relativePath</em>. $folderReadme";
    $this->succeed($response, array('description' => $description));
  }

  private function addReportLinks(&$metadata) {
    $metadata['href'] = $this->getUrlWithCurrentParams("reports/$metadata[path].xml");
    $metadata['params'] = array(
      'href' => $this->getUrlWithCurrentParams("reports/$metadata[path].xml/params")
    );
    if (!empty($metadata['standard_params'])) {
      // reformat the info that the report supports standard paramenters into REST structure
      $metadata['params']['info'] =
        'Supports the standard set of parameters for ' . $metadata['standard_params'];
      $metadata['params']['helpLink'] = 'http://indicia-docs.readthedocs.io/en/latest/' .
        'developing/reporting/report-file-format.html?highlight=quality#standard-report-parameters';
      unset($metadata['standard_params']);
    }
    $metadata['columns'] = array(
      'href' => $this->getUrlWithCurrentParams("reports/$metadata[path].xml/columns")
    );
  }

  private function getFeaturedReports($reportHierarchy, &$reports) {
    foreach ($reportHierarchy as $key => $metadata) {
      if ($metadata['type'] === 'report' && !empty($metadata['featured'])) {
        $this->addReportLinks($metadata);
        $reports[$metadata['path']] = $metadata;
      } elseif ($metadata['type'] === 'folder') {
        $this->getFeaturedReports($metadata['content'], $reports);
      }
    }
  }

  /**
   * Validates that the request parameters provided fullful the requirements of the method being called.
   * @param string $resourceName
   * @param string $method Method name, e.g. GET or POST.
   */
  private function validateParameters($resourceName, $method, $requestForId) {
    $info = $this->http_methods[$resourceName][$method]['subresources'];
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
          if (strpos($this->request[$paramName], 'T')===false)
            $dt = DateTime::createFromFormat("Y-m-d", trim($this->request[$paramName]));
          else
            $dt = DateTime::createFromFormat("Y-m-d\TH:i:s", trim($this->request[$paramName]));
          if ($dt === false || array_sum($dt->getLastErrors()))
            $this->fail('Bad request', 400, "Invalid date for $paramName parameter");
        }
      }
    }
  }

  private function notEmpty($value) {
    return !empty($value);
  }

  /**
   * Adds metadata such as an href back to the resource to any resource object.
   * @param array $item The resource object as an array which will be updated with the metadata
   * @param string $entity The entity name used to access the resouce, e.g. taxon-observations
   */
  private function addItemMetadata(&$item, $entity) {
    $item['href'] = "$entity/$item[id]";
    $item['href'] = $this->getUrlWithCurrentParams($item['href']);
    // strip nulls and empty strings
    $item = array_filter($item, array($this, 'notEmpty'));
  }

  /**
   * Takes a URL and adds the current metadata parameters from the request and
   * adds them to the URL.
   */
  private function getUrlWithCurrentParams($url) {
    $url = url::base() . "index.php/services/rest/$url";
    $query = array();
    $params = $this->request;
    if (!empty($params['proj_id']))
      $query['proj_id'] = $params['proj_id'];
    if (!empty($params['format']))
      $query['format'] = $params['format'];
    if (!empty($params['user']))
      $query['user'] = $params['user'];
    if (!empty($params['secret']))
      $query['secret'] = $params['secret'];
    if (!empty($query))
      return $url . '?' . http_build_query($query);
    else
      return $url;
  }

  /**
   * Converts an array list of items loaded from the database into the structure ready for returning
   * as the result from an API call. Adds pagination information as well as hrefs for contained objects.
   *
   * @param array $list Array of records from the database
   * @param string $entity Resource name that is being accessed.
   * @return array Restructured version of the input list, with pagination and hrefs added.
   */
  private function listResponseStructure($list, $entity) {
    foreach ($list as &$item) {
      $this->addItemMetadata($item, $entity);
    }
    $pagination = array(
      'self'=>$this->generateLink(array('page'=>$this->request['page'])),
    );
    if ($this->request['page']>1)
      $pagination['previous'] = $this->generateLink(array('page'=>$this->request['page']-1));
    // list needs to grab 1 extra, then lop it off and set a flag to indicate another page required
    if (count($list)>$this->request['page_size']) {
      array_pop($list);
      $pagination['next'] = $this->generateLink(array('page'=>$this->request['page']+1));
    }
    return array(
      'paging' => $pagination,
      'data' => $list
    );
  }

  private function loadReportEngine() {
    // Should also return an object to iterate rather than loading the full array
    if (!isset($this->reportEngine)) {
      $this->reportEngine = new ReportEngine(array($this->clientWebsiteId));
    }
  }

  /**
   * Method to load the output of a report being used to construct an API call GET response.
   *
   * @param string $report Report name (excluding .xml extension)
   * @param array $params Report parameters in an associative array
   * @return array Report response structure
   */
  private function loadReport($report, $params) {
    $this->loadReportEngine();
    // @todo Apply permissions for user or website & write tests
    // load the filter associated with the project ID
    if (isset($this->clientSystemId)) {
      $filter = $this->loadFilterForProject($this->request['proj_id']);
    } elseif (isset($this->clientUserId)) {
      if (isset($this->clientFilterId)) {
        $filter = $this->getPermissionsFilterDefinition();
      } else {
        // default filter - the user's records for this website only
        $filter = array(
          'website_list' => $this->clientWebsiteId,
          // @todo Document created_by_id parameter
          'created_by_id' => $this->clientUserId
        );
      }
    } else {
      if (!isset($this->clientWebsiteId)) {
        $this->fail('Internal server error', 500, 'Minimal filter on website ID not provided.');
      }
      $filter = array(
        'website_list' => $this->clientWebsiteId
      );
    }
    // The project's filter acts as a context for the report, meaning it defines the limit of all the
    // records that are available for this project.
    foreach ($filter as $key => $value) {
      $params["{$key}_context"] = $value;
    }
    $params['system_user_id'] = $this->serverUserId;
    if (isset($this->clientSystemId)) {
      // For client systems, the project defines how records are allowed to be shared with this client
      $params['sharing'] = $this->projects[$this->request['proj_id']]['sharing'];
    }
    $report = $this->reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
    return $report;
  }

  /**
   * Regenerates the current GET URI link, but replacing one or more paraneters with a new value,
   * e.g. a new page ID.
   *
   * @param array $replacements List of parameters and values to replace
   * @return string The reconstructed URL.
   */
  private function generateLink($replacements=array()) {
    $params = array_merge($_GET, $replacements);
    return url::base() . 'index.php/services/rest/' . $this->resourceName . '?' . http_build_query($params);
  }

  private function loadFilterForProject($id) {
    if (!isset($this->projects[$id]))
      $this->fail('Bad request', 400, 'Invalid project requested');
    if (isset($this->projects[$id]['filter_id'])) {
      $filterId = $this->projects[$id]['filter_id'];
      $filters = $this->db->select('definition')->from('filters')->where(array('id'=>$filterId, 'deleted'=>'f'))
        ->get()->result_array();
      if (count($filters)!==1)
        $this->fail('Internal Server Error', 500, 'Failed to find unique project filter record');
      return json_decode($filters[0]->definition, true);
    }
    else {
      return array();
    }
  }

  private function getPermissionsFilterDefinition() {
    $filters = $this->db->select('definition')
      ->from('filters')
      ->join('filters_users', array(
        'filters_users.filter_id' => 'filters.id'
      ))
      ->where(array(
        'filters.id'=>$this->clientFilterId,
        'filters.deleted'=>'f',
        'filters.defines_permissions' => 't',
        'filters_users.user_id' => $this->clientUserId,
        'filters_users.deleted' => 'f'
      ))
      ->get()->result_array();
    if (count($filters)!==1)
      $this->fail('Bad request', 400, 'Filter ID missing or not a permissions filter for the user');
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
      if (!in_array($matches['major'] . '.' . $matches['minor'], $this->supportedApiVersions)) {
        $this->fail('Bad request', 400, 'Unsupported API version');
      }
      $this->apiMajorVersion = $matches['major'];
      $this->apiMinorVersion = $matches['minor'];
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
   * Checks that the request is authentic.
   */
  private function authenticate() {
    $this->db = new Database();
    $this->isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $this->serverUserId = Kohana::config('rest.user_id');
    $methods = Kohana::config('rest.authentication_methods');
    $httpMethods = Kohana::config('rest.http_authentication_methods');
    if (!$methods) {
      $methods = array('hmacClient', 'hmacWebsite', 'oauth2User');
    }
    if ($this->restrictToAuthenticationMethods !== FALSE) {
      $methods = array_intersect($methods, $this->restrictToAuthenticationMethods);
    }
    kohana::log('debug', 'Methods: ' . var_export($methods, true));
    $this->authenticated = FALSE;
    foreach ($methods as $method) {
      // Skip methods if http and method requires https
      if ($this->isHttps || in_array($method, $httpMethods)) {
        $method = ucfirst($method);
        // try this authentication method
        kohana::log('debug', "trying $method");
        call_user_func(array($this, "authenticateUsing$method"));
        if ($this->authenticated) {
          kohana::log('debug', "authenticated via $method");
          break;
        }
      }
    }
    if (!$this->authenticated) {
      $this->fail('Unauthorized', 401, 'Unable to authorise');
    }
  }

  private function authenticateUsingOauth2User() {
    // @todo Implement this method
  }

  private function authenticateUsingHmacClient() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
      list($u, $clientSystemId, $h, $supplied_hmac) = explode(':', $headers['Authorization']);
      $config = Kohana::config('rest.clients');
      // @todo Should this be CLIENT not USER?
      if ($u === 'USER' && $h === 'HMAC' && array_key_exists($clientSystemId, $config)) {
        $protocol = $this->isHttps ? 'https' : 'http';
        $request_url = "$protocol://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $correct_hmac = hash_hmac("sha1", $request_url, $config[$clientSystemId]['shared_secret'], $raw_output = FALSE);
        if ($supplied_hmac === $correct_hmac) {
          $this->clientSystemId = $clientSystemId;
          $this->projects = $config[$clientSystemId]['projects'];
          if (!empty($_REQUEST['proj_id']))
            $this->clientWebsiteId = $this->projects[$_REQUEST['proj_id']]['website_id'];
          // Apart from the projects resource, other end-points will need a proj_id
          // if using client system based authorisation.
          if ($this->resourceName !== 'projects' &&
              (empty($_REQUEST['proj_id']) || empty($this->projects[$_REQUEST['proj_id']]))) {
            $this->fail('Bad request', 400, 'Project ID missing or invalid.');
          }
          $this->authenticated = TRUE;
        }
      }
    }
  }

  private function authenticateUsingHmacWebsite() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
      list($u, $websiteId, $h, $supplied_hmac) = explode(':', $headers['Authorization']);
      if ($u === 'WEBSITE_ID' && $h === 'HMAC') {
        // input validation
        if (!preg_match('/^\d+$/', $websiteId)) {
          $this->fail('Unauthorized', 401, 'Website ID incorrect format.');
        }
        $websites = $this->db->select('password')
          ->from('websites')
          ->where(array('id' => $websiteId))
          ->get()->result_array();
        if (count($websites) === 1) {
          $protocol = $this->isHttps ? 'https' : 'http';
          $request_url = "$protocol://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
          $correct_hmac = hash_hmac("sha1", $request_url, $websites[0]->password, $raw_output = FALSE);
          if ($supplied_hmac === $correct_hmac) {
            $this->clientWebsiteId = $websiteId;
            $this->authenticated = TRUE;
          }
          else {
            $this->fail('Unauthorized', 401, 'Supplied HMAC authorization incorrect.');
          }
        }
        else {
          $this->fail('Unauthorized', 401, 'Unrecognised website ID.');
        }
      }
    }
  }

  private function authenticateUsingDirectUser() {
    $headers = apache_request_headers();
    kohana::log('debug', $headers['Authorization']);
    if (isset($headers['Authorization']) &&
        substr_count($headers['Authorization'], ':') === 5) {
      // 6 parts to authorisation required for user ID, website ID and password pairs
      list($u, $userId, $w, $websiteId, $h, $password) = explode(':', $headers['Authorization']);
      if ($u !== 'USER_ID' || $w !== 'WEBSITE_ID' || $h !== 'SECRET') {
        return;
      }
    } elseif (isset($headers['Authorization']) &&
      substr_count($headers['Authorization'], ':') === 7) {
      // 8 parts to authorisation required for user ID, website ID, filter ID and password pairs
      list($u, $userId, $w, $websiteId, $f, $filterId, $h, $password) = explode(':', $headers['Authorization']);
      if ($u !== 'USER_ID' || $w !== 'WEBSITE_ID' || $f !== 'FILTER_ID' || $h !== 'SECRET') {
        return;
      }
    } elseif (kohana::config('rest.allow_auth_tokens_in_url') === TRUE &&
          !empty($_GET['user_id']) && !empty($_GET['secret'])) {
      $userId = $_GET['user_id'];
      $websiteId = $_GET['website_id'];
      $password = $_GET['secret'];
    } else {
      return;
    }
    // input validation
    if (!preg_match('/^\d+$/', $userId) || !preg_match('/^\d+$/', $websiteId)) {
      $this->fail('Unauthorized', 401, 'User ID or website ID incorrect format.');
    }
    $users = $this->db->select('password')
      ->from('users')
      ->where(array('id' => $userId))
      ->get()->result_array(false);
    if (count($users) !== 1) {
      $this->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
    }
    $auth = new Auth;
    if ($auth->checkPasswordAgainstHash($password, $users[0]['password'])) {
      $this->clientUserId = $userId;
      $this->clientWebsiteId = $websiteId;
      if (isset($filterId)) {
        $this->clientFilterId = $filterId;
      }
      // @todo Is this user a member of the website?
      $this->authenticated = TRUE;
    } else {
      $this->fail('Unauthorized', 401, 'Incorrect password for user.');
    }
    // @todo Apply user ID limit to data, limit to filterable reports
  }

  private function authenticateUsingDirectClient() {
    $headers = apache_request_headers();
    $config = Kohana::config('rest.clients');
    if (isset($headers['Authorization'])) {
      list($u, $clientSystemId, $h, $secret) = explode(':', $headers['Authorization']);
      kohana::log('debug', 'authorisation: ' . $headers['Authorization']);
      if ($u !== 'USER' || $h !== 'SECRET') {
        return;
      }
    } elseif (kohana::config('rest.allow_auth_tokens_in_url') === TRUE &&
          !empty($_GET['user']) && !empty($_GET['secret'])) {
      $clientSystemId = $_GET['user'];
      $secret = $_GET['secret'];
    } else {
      return;
    }
    if (!array_key_exists($clientSystemId, $config)) {
      $this->fail('Unauthorized', 401, 'Invalid client system ID');
    }
    if ($secret !== $config[$clientSystemId]['shared_secret']) {
      $this->fail('Unauthorized', 401, 'Incorrect secret');
    }
    $this->clientSystemId = $clientSystemId;
    $this->projects = $config[$clientSystemId]['projects'];
    // Apart from the projects resource, other end-points will need a proj_id
    // if using client system based authorisation.
    if ($this->resourceName !== 'projects' &&
        (empty($_REQUEST['proj_id']) || empty($this->projects[$_REQUEST['proj_id']]))) {
      $this->fail('Bad request', 400, 'Project ID missing or invalid.');
    }
    if (!empty($_REQUEST['proj_id'])) {
      $this->clientWebsiteId = $this->projects[$_REQUEST['proj_id']]['website_id'];
    }
    $this->authenticated = TRUE;
  }

  private function authenticateUsingDirectWebsite() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
      list($u, $websiteId, $h, $password) = explode(':', $headers['Authorization']);
      if ($u !== 'WEBSITE_ID' || $h !== 'SECRET') {
        return;
      }
    } elseif (kohana::config('rest.allow_auth_tokens_in_url') === TRUE &&
        !empty($_GET['website_id']) && !empty($_GET['secret'])) {
      $websiteId = $_GET['website_id'];
      $password = $_GET['secret'];
    } else {
      return;
    }
    // input validation
    if (!preg_match('/^\d+$/', $websiteId)) {
      $this->fail('Unauthorized', 401, 'User ID or website ID incorrect format.');
    }
    $password = pg_escape_string($password);
    $websites = $this->db->select('id')
      ->from('websites')
      ->where(array('id' => $websiteId, 'password' => $password))
      ->get()->result_array();
    if (count($websites) !== 1) {
      $this->fail('Unauthorized', 401, 'Unrecognised website ID or password.');
    }
    $this->clientWebsiteId = $websiteId;
    $this->authenticated = true;
    // @todo Apply website ID limit to data
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
   * @param string $label Label to be used when linking to this array in the index.
   */
  private function getArrayAsHtml($array, $label) {
    $r = '';
    if (count($array)) {
      $r .= '<table border="1">';
      $legendValues = array_intersect_key($array, array('title' => '', 'display' => ''));
      if (count($legendValues)>0 && !is_array($array[array_keys($legendValues)[0]])) {
        $legendFieldName = array_keys($legendValues)[0];
        $legendFieldValue = $array[array_keys($legendValues)[0]];
        $legendFieldDescription = empty($array['description']) ? '' : $array['description'];
        $id = preg_replace('/[^a-z0-9]/', '-', strtolower("$legendFieldName-$legendFieldValue"));
        $r .= "<caption id=\"$id\">$legendFieldName: $legendFieldValue</caption>";
        $this->index .= <<<ROW
<tr>
  <th scope="row"><a href="#$id">$label</a></th>
  <td>$legendFieldValue</td>
  <td>$legendFieldDescription</td>
</tr>
ROW;
      }
      $keys = array_keys($array);
      $col1 = is_integer($keys[0]) ? 'Row' : 'Field';
      $col2 = is_integer($keys[0]) ? 'Record' : 'Value';
      $r .= "<thead><th scope=\"col\">$col1</th><th scope=\"col\">$col2</th></thead>";
      $r .= '<tbody>';
      foreach ($array as $key=>$value) {
        if (empty($value) && !$this->includeEmptyValues)
          continue;
        $class = !empty($value['type']) ? " class=\"type-$value[type]\"" : '';
        $r .= "<tr><th scope=\"row\"$class>$key</th><td>";
        if (is_array($value))
          $r .= $this->getArrayAsHtml($value, $key);
        else {
          if (preg_match('/http(s)?:\/\//', $value)) {
            $parts = explode('?', $value);
            $displayUrl = $parts[0];
            if (count($parts)>1) {
              parse_str($parts[1], $params);
              unset($params['user']);
              unset($params['secret']);
              if (count($params)) {
                $displayUrl .= '?' . http_build_query($params);
              }
            }
            $value = "<a href=\"$value\">$displayUrl</a>";
          }
          $r .= "<p>$value</p>";
        }
        $r .= '</td></tr>';
      }
      $r .= '</tbody></table>';
    }
    return $r;
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
    if ($info) {
      kohana::log('debug', "HTTP code: $code. $info");
      kohana::log_save();
    }
    throw new RestApiAbort($msg);
  }

  /**
   * Outputs a data object as JSON (or chosen alternative format), in the case of successful operation.
   *
   * @param array $data Response data to output.
   */
  private function succeed($data, $metadata = null) {
    if (!empty($this->request['format']) && $this->request['format']==='html') {
      header('Content-Type: text/html');
      $css = url::base() . "modules/rest_api/media/css/rest_api.css";
      echo str_replace('{css}', $css, $this->html_header);
      if (!empty($this->responseTitle))
        echo '<h1>' . $this->responseTitle . '</h1>';
      if ($metadata) {
        echo '<h2>Metadata</h2>';
        echo $this->getArrayAsHtml($metadata, 'metadata');
      }
      // build the output HTML and the page index
      $output = $this->getArrayAsHtml($data, 'data');
      // output an index table if present for this output
      if ($this->wantIndex && !empty($this->index))
        echo '<table><caption>Index</caption>' . $this->index . '</table>';
      // output the main response body
      if ($metadata || !empty($this->responseTitle))
        echo '<h2>Response</h2>';
      echo $output;
      echo '</body></html>';
    } else {
      header('Content-Type: application/json');
      echo json_encode($data);
    }
  }

}