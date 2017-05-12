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

DEFINE("REST_API_DEFAULT_PAGE_SIZE", 100);

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

  private $apiResponse;

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
   * Config settings relating to the selected auth method.
   * @var array
   */
  private $authConfig;

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

  /**
   * Define the list of HTTP methods that will be supported by each resource endpoint.
   * @var type array
   */
  private $resourceConfig = array(
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
              'filter_id' => array(
                'datatype' => 'integer'
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
              ),
              'filter_id' => array(
                'datatype' => 'integer'
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
              'filter_id' => array(
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
          '{annotation ID}' => array(
            'params' => array(
              'proj_id' => array(
                'datatype' => 'text'
              ),
              'filter_id' => array(
                'datatype' => 'integer'
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
            'params' => array()
          ),
          '{report_path}.xml' => array(
            'params' => array(
              'filter_id' => array(
                'datatype' => 'integer'
              ),
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
              ),
              'columns' => array(
                'datatype' => 'text'
              )
            )
          ),
          '{report_path}.xml/params' => array(
            'params' => array(
            )
          ),
          '{report_path}.xml/columns' => array(
            'params' => array(
            )
          ),
        )
      )
    )
  );

  /**
   * Rest_Controller constructor.
   */
  public function __construct() {
    // Ensure we have a db instance ready.
    $this->db = new Database();
    $this->apiResponse = new RestApiResponse();
    parent::__construct();
  }

  /**
   * Controller for the default page for the /rest path. Outputs help text to describe
   * the available API resources.
   */
  public function index() {
    // A temporary array to simulate the arguments, which we can use to check for versioning.
    $arguments = array($this->uri->last_segment());
    $this->check_version($arguments);
    $this->apiResponse->index($this->resourceConfig);
  }

  /**
   * Implement the oAuth2 token endpoint for password grant flow.
   * @todo Also implement the client_credentials grant type for website level access
   *       and client system level access.
   */
  public function token() {
    try {
      if (empty($_POST['grant_type']) || empty($_POST['username']) ||
        empty($_POST['password']) || empty($_POST['client_id'])
      ) {
        $this->apiResponse->fail('Bad request', 400, 'Missing required parameters');
      }
      if ($_POST['grant_type'] !== 'password') {
        $this->apiResponse->fail('Not implemented', 501, 'Grant type not implemented: ' . $_POST['grant_type']);
      }
      $matchField = strpos($_POST['username'], '@') === false ? 'u.username' : 'email_address';
      $websiteId = preg_replace('/^website_id:/', '', $_POST['client_id']);
      // @todo Test for is the user a member of this website?
      $users = $this->db->select('u.id, u.password, u.core_role_id, uw.site_role_id')
        ->from('users as u')
        ->join('people as p', 'p.id', 'u.person_id')
        ->join('users_websites as uw', 'uw.user_id', 'u.id', 'LEFT')
        ->where(array(
          $matchField => $_POST['username'],
          'u.deleted' => 'f',
          'p.deleted' => 'f'
        ))
        ->get()->result_array(false);
      if (count($users) !== 1) {
        $this->apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
      }
      if ($users[0]['site_role_id'] === NULL && $users[0]['core_role_id'] === NULL) {
        $this->apiResponse->fail('Unauthorized', 401, 'User does not have access to website.');
      }
      $auth = new Auth;
      if (!$auth->checkPasswordAgainstHash($_POST['password'], $users[0]['password'])) {
        $this->apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
      }
      if (substr($_POST['client_id'], 0, 11) !== 'website_id:') {
        $this->apiResponse->fail('Unauthorized', 401, 'Invalid client_id format. ' . var_export($_POST, true));
      }
      $accessToken = $this->getToken();
      $cache = new Cache();
      $uid = $users[0]['id'];
      $data = "USER_ID:$uid:WEBSITE_ID:$websiteId";
      $cache->set($accessToken, $data, 'oAuthUserAccessToken', Kohana::config('indicia.nonce_life'));
      $this->apiResponse->succeed(array(
        'access_token' => $accessToken,
        'token_type' => 'bearer',
        'expires_in' => Kohana::config('indicia.nonce_life')
      ));
    }
    catch (RestApiAbort $e) {
      // no action if a proper abort
    }
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
    try {
      // undo router's conversion of hyphens and underscores
      $this->resourceName = str_replace('_', '-', $name);
      // Projects are a concept of client system based authentication, not websites or users.
      if ($this->resourceName === 'projects') {
        $this->restrictToAuthenticationMethods = array(
          'hmacClient' => '',
          'directClient' => ''
        );
      }
      $this->authenticate();
      if (array_key_exists($this->resourceName, $this->resourceConfig)) {
        $resourceConfig = $this->resourceConfig[$this->resourceName];
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method === 'OPTIONS') {
          // A request for the methods allowed for this resource
          header('allow: ' . strtoupper(implode(',', array_keys($resourceConfig))));
        }
        else {
          if (!array_key_exists(strtolower($this->method), $resourceConfig)) {
            $this->apiResponse->fail('Method Not Allowed', 405, $this->method . " not allowed for $name");
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
            $this->apiResponse->fail('Bad request', 400, 'Incorrect number of arguments');
          }
          elseif (!$allowSegments && count($arguments) === 1) {
            // we only allow a single argument to request a single resource by ID
            if (preg_match('/^[A-Z]{3}\d+$/', $arguments[0])) {
              $requestForId = $arguments[0];
            }
            else {
              $this->apiResponse->fail('Bad request', 400, 'Invalid ID requested '.$arguments[0]);
            }
          }
          // apart from requests for a project, we always want a project ID
          if (isset($this->clientSystemId) && $name !== 'projects') {
            if (empty($this->request['proj_id']))
              // Should not have got this far - just in case
              $this->apiResponse->fail('Bad Request', 400, 'Missing proj_id parameter');
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
        $this->apiResponse->fail('Not Found', 404, "Resource $name not known");
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
        $this->apiResponse->fail('No Content', 204);
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
      $this->apiResponse->fail('No Content', 204);
    }
    $this->addItemMetadata($this->projects[$id], 'projects');
    // remove fields from the project that are for internal use only
    unset($this->projects[$id]['filter_id']);
    unset($this->projects[$id]['website_id']);
    unset($this->projects[$id]['sharing']);
    unset($this->projects[$id]['resources']);
    $this->apiResponse->succeed($this->projects[$id]);
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
    $this->apiResponse->succeed(array(
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
      $this->apiResponse->fail('No Content', 204);
    } elseif (count($report['content']['records'])>1) {
      kohana::log('error', 'Internal error. Request for single record returned multiple');
      $this->apiResponse->fail('Internal Server Error', 500);
    } else {
      $this->addItemMetadata($report['content']['records'][0], 'taxon-observations');
      $this->apiResponse->succeed($report['content']['records'][0]);
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
    $this->apiResponse->succeed($this->listResponseStructure($report['content']['records'], 'taxon-observations'));
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
      $this->apiResponse->fail('No Content', 204);
    } elseif (count($report['content']['records'])>1) {
      kohana::log('error', 'Internal error. Request for single annotation returned multiple');
      $this->apiResponse->fail('Internal Server Error', 500);
    } else {
      $record = $report['content']['records'][0];
      $record['taxonObservation'] = array(
        'id' => $record['taxon_observation_id'],
        // @todo href
      );
      $this->addItemMetadata($record['taxonObservation'], 'taxon-observations');
      $this->addItemMetadata($record, 'annotations');
      $this->apiResponse->succeed($record);
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
    $this->apiResponse->succeed($this->listResponseStructure($records, 'annotations'));
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
      $urlPrefix = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
      $parts = explode('?', $_SERVER['REQUEST_URI']);
      $url = $parts[0];
      if (count($parts)>1) {
        parse_str($parts[1], $params);
      } else {
        $params = array();
      }
      $params['known_count'] = $report['count'];
      $pagination = array(
        'self' => "$urlPrefix$url?" . http_build_query($params)
      );
      $limit = empty($params['limit']) ? REST_API_DEFAULT_PAGE_SIZE : $params['limit'];
      $offset = empty($params['offset']) ? 0 : $params['offset'];
      if ($offset > 0) {
        $params['offset'] = max($offset - $limit, 0);
        $pagination['previous'] = "$urlPrefix$url?" . http_build_query($params);
      }
      if ($offset + $limit < $report['count']) {
        $params['offset'] = $offset + $limit;
        $pagination['next'] = "$urlPrefix$url?" . http_build_query($params);
      }
      $this->apiResponse->succeed(array(
        'count' => $report['count'],
        'paging' => $pagination,
        'data' => $report['content']['records']
      ));
    } elseif (isset($report['content']['parameterRequest'])) {
      // @todo: handle param requests
      $this->apiResponse->fail('Bad request (parameters missing)', 400, "Missing parameters");
    }
  }

  /**
   *
   */
  private function getReportMetadataItem($segments, $item, $description) {
    $this->apiResponse->includeEmptyValues = false;
    // the last segment is the /params action.
    array_pop($segments);
    $reportFile = $this->getReportFileNameFromSegments($segments);
    $this->loadReportEngine();
    $metadata = $this->reportEngine->requestMetadata("$reportFile.xml", true);
    $list = $metadata[$item];
    if ($item === 'parameters') {
      // Columns with a datatype can also be used as a parameter
      foreach ($metadata['columns'] as $name => $columnDef) {
        if (!empty($columnDef['datatype']) && !isset($list[$name])) {
          $def = array(
            'description' => 'Column available for use as a parameter'
          );
          if (!empty($columnDef['display']))
            $def['display'] = $columnDef['display'];
          if (!empty($columnDef['datatype']))
            $def['datatype'] = $columnDef['datatype'];
          $list[$name] = $def;
        }
      }
    }
    $this->apiResponse->responseTitle = ucfirst("$item for $reportFile");
    $this->apiResponse->wantIndex = true;
    $this->apiResponse->succeed(array('data' => $list),
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
    $this->applyReportRestrictions($reportHierarchy);
    $relativePath = implode('/', $segments);
    if (empty($segments) && in_array('allow_all_report_access', $this->authConfig)) {
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
    $this->apiResponse->succeed($response, array('description' => $description));
  }

  /**
   * Applies limitations to the available reports depending on the configuration.
   * For example, it may be appropriate to limit user based authentication methods
   * to featured reports only, to be sure they don't access a report which does not
   * apply the user filter.
   * @param $reportHierarchy
   */
  private function applyReportRestrictions(&$reportHierarchy) {
    if (!in_array('allow_all_report_access', $this->authConfig)) {
      foreach ($reportHierarchy as $item => &$cfg) {
        if ($cfg['type'] === 'report' && (!isset($cfg['featured']) || $cfg['featured'] !== 'true')) {
          unset($reportHierarchy[$item]);
        } elseif ($cfg['type'] === 'folder') {
          // recurse into folders
          $this->applyReportRestrictions($cfg['content']);
          // folders may be left empty if no featured reports in them
          if (empty($cfg['content']))
            unset($reportHierarchy[$item]);
        }
      }
    }
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
    $info = $this->resourceConfig[$resourceName][$method]['subresources'];
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
    // Check through the known list of parameters to ensure data formats are correct and required parameters are
    // provided.
    foreach ($thisMethod['params'] as $paramName => $paramDef) {
      if (!empty($paramDef['required']) && empty($this->request[$paramName])) {
        $this->apiResponse->fail('Bad request', 400, "Missing $paramName parameter");
      }
      if (!empty($this->request[$paramName])) {
        if ($paramDef['datatype']==='integer' && !preg_match('/^\d+$/', trim($this->request[$paramName]))) {
          $this->apiResponse->fail('Bad request', 400, "Invalid format for $paramName parameter");
        }
        if ($paramDef['datatype']==='date') {
          if (strpos($this->request[$paramName], 'T')===false)
            $dt = DateTime::createFromFormat("Y-m-d", trim($this->request[$paramName]));
          else
            $dt = DateTime::createFromFormat("Y-m-d\TH:i:s", trim($this->request[$paramName]));
          if ($dt === false || array_sum($dt->getLastErrors()))
            $this->apiResponse->fail('Bad request', 400, "Invalid date for $paramName parameter");
        }
      }
    }
  }

  /**
   * Utility method for filtering empty values from an array.
   * @param $value
   * @return bool
   */
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
      // When authenticating a user, you can use one of the permissions filters for the
      // user to gain access to a wider pool of records, e.g. for a verifier to access
      // all records they have rights to.
      if (!empty($_GET['filter_id'])) {
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
        $this->apiResponse->fail('Internal server error', 500, 'Minimal filter on website ID not provided.');
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
    $params = array_merge(
      array('limit' => REST_API_DEFAULT_PAGE_SIZE),
      $params
    );
    // Include count query results if not already known from a previous request
    // @todo Don't run report query if count or limit are zero.
    $report = $this->reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
    $report['count'] =  empty($_GET['known_count']) ? $this->reportEngine->record_count() : $_GET['known_count'];;
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
      $this->apiResponse->fail('Bad request', 400, 'Invalid project requested');
    if (isset($this->projects[$id]['filter_id'])) {
      $filterId = $this->projects[$id]['filter_id'];
      $filters = $this->db->select('definition')->from('filters')->where(array('id'=>$filterId, 'deleted'=>'f'))
        ->get()->result_array();
      if (count($filters)!==1)
        $this->apiResponse->fail('Internal Server Error', 500, 'Failed to find unique project filter record');
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
        'filters.id'=>$_GET['filter_id'],
        'filters.deleted'=>'f',
        'filters.defines_permissions' => 't',
        'filters_users.user_id' => $this->clientUserId,
        'filters_users.deleted' => 'f'
      ))
      ->get()->result_array();
    if (count($filters)!==1)
      $this->apiResponse->fail('Bad request', 400, 'Filter ID missing or not a permissions filter for the user');
    return json_decode($filters[0]->definition, true);
  }

  /**
   * Checks the API version provided in the URI (if any) to ensure that the version is supported.
   * Returns a 400 Bad request if not supported.
   * @param array $arguments Additional URI segments
   */
  private function check_version(&$arguments) {
    if (count($arguments)
        && preg_match('/^v(?P<major>\d+)\.(?P<minor>\d+)$/', $arguments[count($arguments)-1], $matches)) {
      array_pop($arguments);
      // Check not asking for an invalid version
      if (!in_array($matches['major'] . '.' . $matches['minor'], $this->supportedApiVersions)) {
        $this->apiResponse->fail('Bad request', 400, 'Unsupported API version');
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
      'page_size' => REST_API_DEFAULT_PAGE_SIZE
    ), $this->request);
    $this->checkInteger($this->request['page'], 'page');
    $this->checkInteger($this->request['page_size'], 'page_size');
  }

  /**
   * Checks that the request is authentic.
   */
  private function authenticate() {
    $this->isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $this->serverUserId = Kohana::config('rest.user_id');
    $methods = Kohana::config('rest.authentication_methods');
    // Provide a default if not configured
    if (!$methods) {
      $methods = array(
        'hmacClient' => array('allow_http'),
        'hmacWebsite' => array('allow_http', 'allow_all_report_access'),
        'directClient' => array(),
        'oauth2User' => array()
      );
    }
    if ($this->restrictToAuthenticationMethods !== FALSE) {
      $methods = array_intersect_key($methods, $this->restrictToAuthenticationMethods);
    }
    $this->authenticated = FALSE;
    foreach ($methods as $method => $cfg) {
      // Skip methods if http and method requires https
      if ($this->isHttps || in_array('allow_http', $cfg)) {
        $method = ucfirst($method);
        // try this authentication method
        call_user_func(array($this, "authenticateUsing$method"));
        if ($this->authenticated) {
          kohana::log('debug', "authenticated via $method");
          $this->authConfig = $cfg;
          break;
        }
      }
    }
    if (!$this->authenticated) {
      $this->apiResponse->fail('Unauthorized', 401, 'Unable to authorise');
    }
  }

  private function authenticateUsingOauth2User() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization']) && strpos($headers['Authorization'], 'Bearer ') === 0) {
      $suppliedToken = str_replace('Bearer ', '', $headers['Authorization']);
      $this->cache = new Cache;
      // get all cache entries that match this nonce
      $paths = $this->cache->exists($suppliedToken);
      foreach($paths as $path) {
        // Find the parts of each file name, which is the cache entry ID, then the mode.
        $tokens = explode('~', basename($path));
        if ($tokens[1] === 'oAuthUserAccessToken') {
          $data = $this->cache->get($tokens[0]);
          kohana::log('debug', 'Data: ' . var_export($data, true));
          if (preg_match('/^USER_ID:(?P<user_id>\d+):WEBSITE_ID:(?P<website_id>\d+)$/', $data, $matches)) {
            $this->clientWebsiteId = $matches['website_id'];
            $this->clientUserId = $matches['user_id'];
            $this->authenticated = TRUE;
          }
        }
      }
    }
  }

  private function authenticateUsingHmacClient() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization']) &&  substr_count($headers['Authorization'], ':') === 3) {
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
            $this->apiResponse->fail('Bad request', 400, 'Project ID missing or invalid.');
          }
          $this->authenticated = TRUE;
        }
      }
    }
  }

  private function authenticateUsingHmacWebsite() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization']) && substr_count($headers['Authorization'], ':') === 3) {
      list($u, $websiteId, $h, $supplied_hmac) = explode(':', $headers['Authorization']);
      if ($u === 'WEBSITE_ID' && $h === 'HMAC') {
        // input validation
        if (!preg_match('/^\d+$/', $websiteId)) {
          $this->apiResponse->fail('Unauthorized', 401, 'Website ID incorrect format.');
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
            $this->apiResponse->fail('Unauthorized', 401, 'Supplied HMAC authorization incorrect.');
          }
        }
        else {
          $this->apiResponse->fail('Unauthorized', 401, 'Unrecognised website ID.');
        }
      }
    }
  }

  private function authenticateUsingDirectUser() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization']) &&
        substr_count($headers['Authorization'], ':') === 5) {
      // 6 parts to authorisation required for user ID, website ID and password pairs
      list($u, $userId, $w, $websiteId, $h, $password) = explode(':', $headers['Authorization']);
      if ($u !== 'USER_ID' || $w !== 'WEBSITE_ID' || $h !== 'SECRET') {
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
      $this->apiResponse->fail('Unauthorized', 401, 'User ID or website ID incorrect format.');
    }
    $users = $this->db->select('password')
      ->from('users')
      ->where(array('id' => $userId))
      ->get()->result_array(false);
    if (count($users) !== 1) {
      $this->apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
    }
    $auth = new Auth;
    if ($auth->checkPasswordAgainstHash($password, $users[0]['password'])) {
      $this->clientUserId = $userId;
      $this->clientWebsiteId = $websiteId;
      // @todo Is this user a member of the website?
      $this->authenticated = TRUE;
    } else {
      $this->apiResponse->fail('Unauthorized', 401, 'Incorrect password for user.');
    }
    // @todo Apply user ID limit to data, limit to filterable reports
  }

  private function authenticateUsingDirectClient() {
    $headers = apache_request_headers();
    $config = Kohana::config('rest.clients');
    if (isset($headers['Authorization']) && substr_count($headers['Authorization'], ':') === 3) {
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
      $this->apiResponse->fail('Unauthorized', 401, 'Invalid client system ID');
    }
    if ($secret !== $config[$clientSystemId]['shared_secret']) {
      $this->apiResponse->fail('Unauthorized', 401, 'Incorrect secret');
    }
    $this->clientSystemId = $clientSystemId;
    $this->projects = $config[$clientSystemId]['projects'];
    // Apart from the projects resource, other end-points will need a proj_id
    // if using client system based authorisation.
    if ($this->resourceName !== 'projects' &&
        (empty($_REQUEST['proj_id']) || empty($this->projects[$_REQUEST['proj_id']]))) {
      $this->apiResponse->fail('Bad request', 400, 'Project ID missing or invalid.');
    }
    if (!empty($_REQUEST['proj_id'])) {
      $this->clientWebsiteId = $this->projects[$_REQUEST['proj_id']]['website_id'];
    }
    $this->authenticated = TRUE;
  }

  private function authenticateUsingDirectWebsite() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization']) && substr_count($headers['Authorization'], ':') === 3) {
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
      $this->apiResponse->fail('Unauthorized', 401, 'User ID or website ID incorrect format.');
    }
    $password = pg_escape_string($password);
    $websites = $this->db->select('id')
      ->from('websites')
      ->where(array('id' => $websiteId, 'password' => $password))
      ->get()->result_array();
    if (count($websites) !== 1) {
      $this->apiResponse->fail('Unauthorized', 401, 'Unrecognised website ID or password.');
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
      $this->apiResponse->fail('Bad request', 400, "Parameter $param is not an integer");
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
      $this->apiResponse->fail('Bad request', 400, "Parameter $param is not an valid date");
    }
  }

  /**
   * Generates a unique token, e.g. for oAuth2
   * @return string
   */
  private function getToken() {
    return sha1(time() . ':' . rand() . $_SERVER['REMOTE_ADDR'] . ':' . kohana::config('indicia.private_key'));
  }
}