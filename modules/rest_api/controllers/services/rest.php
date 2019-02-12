<?php

/**
 * @file
 * Controller class for the REST API.
 *
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

define("REST_API_DEFAULT_PAGE_SIZE", 100);
define("AUTOFEED_DEFAULT_PAGE_SIZE", 10000);

if (!function_exists('apache_request_headers')) {
  Kohana::log('debug', 'PHP apache_request_headers() function does not exist. Replacement function used.');

  /**
   * Polyfill for apache_request_headers function if not available.
   */
  function apache_request_headers() {
    $arh = array();
    $rx_http = '/\AHTTP_/';
    foreach ($_SERVER as $key => $val) {
      if (preg_match($rx_http, $key)) {
        $arh_key = preg_replace($rx_http, '', $key);
        // Do some nasty string manipulations to restore the original letter
        // case. This should work in most cases.
        $rx_matches = explode('_', $arh_key);
        if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
          foreach ($rx_matches as $ak_key => $ak_val) {
            $rx_matches[$ak_key] = ucfirst(strtolower($ak_val));
          }
          $arh_key = implode('-', $rx_matches);
        }
        $arh[$arh_key] = $val;
      }
    }
    return ($arh);
  }

}

/**
 * Exception class for aborting.
 */
class RestApiAbort extends Exception {}

/**
 * Controller class for the RESTful API.
 *
 * Implements handlers for the various resource URIs.
 *
 * Visit index.php/services/rest for a help page.
 */
class Rest_Controller extends Controller {

  /**
   * Set sensible defaults for the authentication methods available.
   *
   * @var array
   */
  private $defaultAuthenticationMethods = [
    'hmacClient' => [
      // HMAC is a bit safer over https as the authentication secrets are never
      // shared. There are still implications for the data itself though.
      'allow_http' => TRUE,
      'resource_options' => [
        // Grants full access to all reports. Client configs can override this.
        'reports' => [],
      ],
    ],
    'hmacWebsite' => [
      'allow_http' => TRUE,
      'resource_options' => [
        // Featured reports with cached summary data only - highly restricted.
        'reports' => ['featured' => TRUE, 'summary' => TRUE, 'cached' => TRUE],
      ],
    ],
    'directClient' => [
      'resource_options' => [
        // Grants full access to all reports. Client configs can override this.
        'reports' => [],
      ],
    ],
    'oauth2User' => [
      'resource_options' => [
        // Grants full access to all reports. Client configs can override this.
        'reports' => ['featured' => TRUE, 'limit_to_own_data' => TRUE],
      ],
    ],
  ];

  /**
   * RestApiResponse class instance.
   *
   * @var RestApiResponse
   */
  private $apiResponse;

  /**
   * The request method (GET, POST etc).
   *
   * @var string
   */
  private $method;

  /**
   * Set to true for https.
   *
   * @var bool
   */
  private $isHttps;

  /**
   * Has the request been authenticated?
   *
   * @var bool
   */
  private $authenticated = FALSE;

  /**
   * Config settings relating to the selected auth method.
   *
   * @var array
   */
  private $authConfig;

  /**
   * Config settings relating to the authenticated client if any.
   *
   * @var array
   */
  private $clientConfig;

  /**
   * Resource options.
   *
   * Flags and options passed to the resource which can be set by the chosen
   * authorisation method or project config. For example flags to control
   * access to featured vs all reports in the library.
   *
   * @var array
   */
  private $resourceOptions;

  /**
   * Elastic proxy configuration key.
   *
   * Set to the key of the configuration section, if using Elasticsearch.
   *
   * @var bool|string
   */
  private $elasticProxy = FALSE;

  /**
   * List of authentication methods that are allowed.
   *
   * If the called resource only supports certain types of authentication, then
   * an array of the methods is set here allowing other methods to be blocked;
   *
   * @var bool|array
   */
  private $restrictToAuthenticationMethods = FALSE;

  /**
   * The server's user ID (i.e. this REST API).
   *
   * @var string
   */
  private $serverUserId;

  /**
   * The client's system ID (i.e. the caller).
   *
   * Set if authenticated against the list of configured clients.
   *
   * @var string
   */
  private $clientSystemId;

  /**
   * The client's website ID (i.e. the caller).
   *
   * Only set if authenticated against the websites table.
   *
   * @var string
   */
  private $clientWebsiteId;

  /**
   * The client's user ID (i.e. the caller)
   *
   * Only set if authenticated against the users table.
   *
   * @var string
   */
  private $clientUserId;

  /**
   * The latest API major version number. Unversioned calls will map to this.
   *
   * @var int
   */
  private $apiMajorVersion = 1;

  /**
   * The latest API minor version number. Unversioned calls will map to this.
   *
   * @var int
   */
  private $apiMinorVersion = 0;

  /**
   * List of API versions that this code base will support.
   *
   * @var array
   */
  private $supportedApiVersions = [
    '1.0',
  ];

  /**
   * Holds the request parameters (e.g. from GET or POST data).
   *
   * @var array
   */
  private $request;

  /**
   * List of project definitions that are available to the authorised client.
   *
   * @var array
   */
  private $projects;

  /**
   * The name of the resource being accessed.
   *
   * @var string
   */
  private $resourceName;

  /**
   * Resource configuration.
   *
   * Define the list of HTTP methods that will be supported by each resource
   * endpoint.
   *
   * @var array
   */
  private $resourceConfig = [
    'projects' => [
      'get' => [
        'subresources' => [
          '' => [
            'params' => [],
          ],
          '{project ID}' => [
            'params' => [],
          ],
        ],
      ],
    ],
    'taxon-observations' => [
      'get' => [
        'subresources' => [
          '' => [
            'params' => [
              'proj_id' => [
                'datatype' => 'text',
              ],
              'filter_id' => [
                'datatype' => 'integer',
              ],
              'page' => [
                'datatype' => 'integer',
              ],
              'page_size' => [
                'datatype' => 'integer',
              ],
              'edited_date_from' => [
                'datatype' => 'date',
                'required' => TRUE,
              ],
              'edited_date_to' => [
                'datatype' => 'date',
              ],
            ],
          ],
          '{taxon-observation ID}' => [
            'params' => [
              'proj_id' => [
                'datatype' => 'text',
                'required' => TRUE,
              ],
              'filter_id' => [
                'datatype' => 'integer',
              ],
            ],
          ],
        ],
      ],
      'post' => [
        'subresources' => [
          '' => [
            'params' => [],
          ],
        ],
      ],
    ],
    'annotations' => [
      'get' => [
        'subresources' => [
          '' => [
            'params' => [
              'proj_id' => [
                'datatype' => 'text',
              ],
              'filter_id' => [
                'datatype' => 'integer',
              ],
              'page' => [
                'datatype' => 'integer',
              ],
              'page_size' => [
                'datatype' => 'integer',
              ],
              'edited_date_from' => [
                'datatype' => 'date',
              ],
              'edited_date_to' => [
                'datatype' => 'date',
              ],
            ],
          ],
          '{annotation ID}' => [
            'params' => [
              'proj_id' => [
                'datatype' => 'text',
                'required' => TRUE,
              ],
              'filter_id' => [
                'datatype' => 'integer',
              ],
            ],
          ],
        ],
      ],
    ],
    'taxa' => [
      'get' => [
        'options' => [
          'segments' => TRUE,
        ],
        'subresources' => [
          '' => [
            'params' => [],
          ],
          'search' => [
            'params' => [
              'taxon_list_id' => [
                'datatype' => 'integer[]',
                'required' => TRUE,
              ],
              'searchQuery' => [
                'datatype' => 'text',
              ],
              'taxon_group_id' => [
                'datatype' => 'integer[]',
              ],
              'taxon_group' => [
                'datatype' => 'text[]',
              ],
              'taxon_meaning_id' => [
                'datatype' => 'integer[]',
              ],
              'taxa_taxon_list_id' => [
                'datatype' => 'integer[]',
              ],
              'preferred_taxa_taxon_list_id' => [
                'datatype' => 'integer[]',
              ],
              'preferred_taxon' => [
                'datatype' => 'text[]',
              ],
              'external_key' => [
                'datatype' => 'text[]',
              ],
              'parent_id' => [
                'datatype' => 'integer[]',
              ],
              'language' => [
                'datatype' => 'text[]',
              ],
              'preferred' => [
                'datatype' => 'boolean',
              ],
              'commonNames' => [
                'datatype' => 'boolean',
              ],
              'synonyms' => [
                'datatype' => 'boolean',
              ],
              'abbreviations' => [
                'datatype' => 'boolean',
              ],
              'marine_flag' => [
                'datatype' => 'boolean',
              ],
              'searchAuthors' => [
                'datatype' => 'boolean',
              ],
              'wholeWords' => [
                'datatype' => 'boolean',
              ],
              'min_taxon_rank_sort_order' => [
                'datatype' => 'integer',
              ],
              'max_taxon_rank_sort_order' => [
                'datatype' => 'integer',
              ],
              'limit' => [
                'datatype' => 'integer',
              ],
              'offset' => [
                'datatype' => 'integer',
              ],
              'include' => [
                'datatype' => 'text[]',
                'options' => ['data', 'count', 'paging', 'columns'],
              ],
            ],
          ],
        ],
      ],
    ],
    'reports' => [
      'get' => [
        'options' => [
          'segments' => TRUE,
        ],
        'subresources' => [
          '' => [
            'params' => [],
          ],
          '{report_path}.xml' => [
            'params' => [
              'filter_id' => [
                'datatype' => 'integer',
              ],
              'limit' => [
                'datatype' => 'integer',
              ],
              'offset' => [
                'datatype' => 'integer',
              ],
              'sortby' => [
                'datatype' => 'text',
              ],
              'sortdir' => [
                'datatype' => 'text',
              ],
              'columns' => [
                'datatype' => 'text',
              ],
              'cached' => [
                'datatype' => 'boolean',
              ],
            ],
          ],
          '{report_path}.xml/params' => [
            'params' => [],
          ],
          '{report_path}.xml/columns' => [
            'params' => [],
          ],
        ],
      ],
    ],
  ];

  /**
   * Rest_Controller constructor.
   */
  public function __construct() {
    // Ensure we have a db instance and response object ready.
    $this->db = new Database();
    $this->apiResponse = new RestApiResponse();
    parent::__construct();
  }

  /**
   * Controller for the default page for the /rest path.
   *
   * Outputs help text to describe the available API resources.
   */
  public function index() {
    // A temporary array to simulate the arguments, which we can use to check
    // for versioning.
    $arguments = [$this->uri->last_segment()];
    $this->checkVersion($arguments);
    $this->apiResponse->index($this->resourceConfig);
  }

  /**
   * Implement the oAuth2 token endpoint for password grant flow.
   *
   * @todo Also implement the client_credentials grant type for website level
   *   access and client system level access.
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
      $matchField = strpos($_POST['username'], '@') === FALSE ? 'u.username' : 'email_address';
      $websiteId = preg_replace('/^website_id:/', '', $_POST['client_id']);
      // @todo Test for is the user a member of this website?
      $users = $this->db->select('u.id, u.password, u.core_role_id, uw.site_role_id')
        ->from('users as u')
        ->join('people as p', 'p.id', 'u.person_id')
        ->join('users_websites as uw', 'uw.user_id', 'u.id', 'LEFT')
        ->where(array(
          $matchField => $_POST['username'],
          'u.deleted' => 'f',
          'p.deleted' => 'f',
        ))
        ->get()->result_array(FALSE);
      if (count($users) !== 1) {
        $this->apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
      }
      if ($users[0]['site_role_id'] === NULL && $users[0]['core_role_id'] === NULL) {
        $this->apiResponse->fail('Unauthorized', 401, 'User does not have access to website.');
      }
      $auth = new Auth();
      if (!$auth->checkPasswordAgainstHash($_POST['password'], $users[0]['password'])) {
        $this->apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
      }
      if (substr($_POST['client_id'], 0, 11) !== 'website_id:') {
        $this->apiResponse->fail('Unauthorized', 401, 'Invalid client_id format. ' . var_export($_POST, TRUE));
      }
      $accessToken = $this->getToken();
      $cache = new Cache();
      $uid = $users[0]['id'];
      $data = "USER_ID:$uid:WEBSITE_ID:$websiteId";
      $cache->set($accessToken, $data, 'oAuthUserAccessToken', Kohana::config('indicia.nonce_life'));
      $this->apiResponse->succeed(array(
        'access_token' => $accessToken,
        'token_type' => 'bearer',
        'expires_in' => Kohana::config('indicia.nonce_life'),
      ));
    }
    catch (RestApiAbort $e) {
      // No action if a proper abort.
    }
  }

  /**
   * Magic method to handle calls to the various resource end-points.
   *
   * Maps the call to a method name defined by the resource and the request
   * method.
   *
   * @param string $name
   *   Resource name as defined by the segment of the URI called.
   *   Note that this resource name has already passed through the router and
   *   had hyphens converted to underscores.
   * @param array $arguments
   *   Additional arguments, for example the ID of a resource being requested.
   *
   * @throws exception
   */
  public function __call($name, $arguments) {
    try {
      // Undo router's conversion of hyphens and underscores.
      $this->resourceName = str_replace('_', '-', $name);
      // Projects are a concept of client system based authentication, not
      // websites or users.
      if ($this->resourceName === 'projects') {
        $this->restrictToAuthenticationMethods = [
          'hmacClient' => '',
          'directClient' => '',
        ];
      }
      $this->authenticate();
      if (!isset($this->resourceOptions)
          && isset($this->authConfig['resource_options'])
          && isset($this->authConfig['resource_options'][$this->resourceName])) {
        $this->resourceOptions = $this->authConfig['resource_options'][$this->resourceName];
      }
      if (!isset($this->resourceOptions)) {
        $this->resourceOptions = array();
      }
      // Caching can be enabled via a query string parameter if not already
      // forced by the authorisation config.
      if (!empty($_GET['cached']) && $_GET['cached'] === 'true') {
        $this->resourceOptions['cached'] = TRUE;
      }
      if ($this->elasticProxy) {
        $this->elasticRequest();
      }
      elseif (array_key_exists($this->resourceName, $this->resourceConfig)) {
        $resourceConfig = $this->resourceConfig[$this->resourceName];
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method === 'OPTIONS') {
          // A request for the methods allowed for this resource.
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
          $methodName = lcfirst(str_replace('_', '', ucwords($name, '_'))) . ucfirst(strtolower($this->method));
          $this->checkVersion($arguments);

          $requestForId = NULL;

          if (!$allowSegments && count($arguments) > 1) {
            $this->apiResponse->fail('Bad request', 400, 'Incorrect number of arguments');
          }
          elseif (!$allowSegments && count($arguments) === 1) {
            // We only allow a single argument to request a single resource by
            // ID.
            if (preg_match('/^[A-Z]{3}\d+$/', $arguments[0])) {
              $requestForId = $arguments[0];
            }
            else {
              $this->apiResponse->fail('Bad request', 400, 'Invalid ID requested ' . $arguments[0]);
            }
          }
          // When using a client system ID, we also want a project ID if
          // accessing taxon observations or annotations.
          if (isset($this->clientSystemId) && ($name === 'taxon_observations' || $name === 'annotations')) {
            if (empty($this->request['proj_id'])) {
              // Should not have got this far - just in case.
              $this->apiResponse->fail('Bad request', 400, 'Missing proj_id parameter');
            }
            else {
              $this->checkAllowedResource($this->request['proj_id'], $this->resourceName);
            }
          }
          if ($requestForId) {
            $methodName .= 'Id';
          }
          $this->validateParameters($this->resourceName, strtolower($this->method), $requestForId);
          if (isset($this->clientSystemId) &&
              ($name === 'taxon_observations' || $name === 'annotations')) {
            $this->checkAllowedResource($this->request['proj_id'], $this->resourceName);
          }
          call_user_func(array($this, $methodName), $requestForId);
        }
      }
      else {
        $this->apiResponse->fail('Not Found', 404, "Resource $name not known");
      }
    }
    catch (RestApiAbort $e) {
      // No action if a proper abort.
    }
  }

  /**
   * Check if resource allowed for project.
   *
   * A project can include a configuration of the resources it exposes, for
   * example it might only expose annotations if the top copy of a record is
   * elsewhere. This method checks that the requested resource is available for
   * the project and aborts with 204 No Content if not.
   *
   * @param int $proj_id
   *   The project ID.
   * @param string $resourceName
   *   The resource being requested, e.g. taxon-observations.
   *
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
   * Proxies the current request to a provided URL.
   *
   * Eg. used when proxying to an Elasticsearch instance.
   *
   * @param string $url
   *   URL to proxy to.
   */
  private function proxyTo($url) {
    $session = curl_init($url);
    // Set the POST options.
    $httpHeader = array();
    $postData = file_get_contents('php://input');
    if (empty($postData)) {
      $postData = $_POST;
    }
    else {
      // Post body contains a raw XML document?
      if (is_string($postData) && substr($postData, 0, 1) === '<') {
        $httpHeader[] = 'Content-Type: text/xml';
      }
      else {
        $httpHeader[] = 'Content-Type: application/json';
      }
    }
    if (!empty($postData)) {
      curl_setopt($session, CURLOPT_POST, 1);
      curl_setopt($session, CURLOPT_POSTFIELDS, $postData);
    }
    if (count($httpHeader) > 0) {
      curl_setopt($session, CURLOPT_HTTPHEADER, $httpHeader);
    }

    curl_setopt($session, CURLOPT_HEADER, TRUE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Do the POST and then close the session.
    $response = curl_exec($session);
    $headers = curl_getinfo($session);
    if (array_key_exists('charset', $headers)) {
      $headers['content_type'] .= '; ' . $headers['charset'];
    }
    header('Content-type: ' . $headers['content_type']);
    // Last part of response is the actual data.
    $arr = explode("\r\n\r\n", $response);
    echo array_pop($arr);
    curl_close($session);
  }

  /**
   * Handles a request to Elasticsearch via a proxy.
   */
  private function elasticRequest() {
    $esConfig = kohana::config('rest.elasticsearch');
    $thisProxyCfg = $esConfig[$this->elasticProxy];
    $resource = str_replace("$_SERVER[SCRIPT_NAME]/services/rest/$this->elasticProxy/", '', $_SERVER['PHP_SELF']);
    if (isset($thisProxyCfg['allowed'])) {
      $allowed = FALSE;
      if (isset($thisProxyCfg['allowed'][strtolower($_SERVER['REQUEST_METHOD'])])) {
        foreach ($thisProxyCfg['allowed'][strtolower($_SERVER['REQUEST_METHOD'])] as $regex => $description) {
          if (preg_match($regex, $resource)) {
            $allowed = TRUE;
          }
        }
      }
      if (!$allowed) {
        $this->apiResponse->fail('Bad request', 400, 'Elasticsearch request not allowed.');
      }
    }
    $url = "$thisProxyCfg[url]/$thisProxyCfg[index]/$resource";
    if (!empty($_GET)) {
      // Don't pass on the auth tokens.
      unset($_GET['user']);
      unset($_GET['website_id']);
      unset($_GET['secret']);
      $url .= '?' . http_build_query($_GET);
    }
    $this->proxyTo($url);
  }

  /**
   * GET handler for the  projects/n resource.
   *
   * Outputs a single project's details.
   *
   * @param string $id
   *   Unique ID for the project to output.
   */
  private function projectsGetId($id) {
    if (!array_key_exists($id, $this->projects)) {
      $this->apiResponse->fail('No Content', 204);
    }
    $this->apiResponse->succeed($this->projects[$id], array(
      'columnsToUnset' => ['filter_id', 'website_id', 'sharing', 'resources'],
      'attachHref' => ['projects', 'id'],
    ));
  }

  /**
   * GET handler for the projects resource. Outputs a list of project details.
   *
   * @todo Projects are currently hard coded in the config file, so pagination
   * etc is just stub code.
   */
  private function projectsGet() {
    $this->apiResponse->succeed([
      'data' => array_values($this->projects),
      'paging' => [
        'self' => $this->generateLink(['page' => 1]),
      ],
    ],
    [
      'columnsToUnset' => ['filter_id', 'website_id', 'sharing', 'resources'],
      'attachHref' => ['projects', 'id'],
    ]);
  }

  /**
   * GET handler for the taxon-observations/n resource.
   *
   * Outputs a single taxon observations's details.
   *
   * @param string $id
   *   Unique ID for the taxon-observations to output.
   */
  private function taxonObservationsGetId($id) {
    if (substr($id, 0, strlen(kohana::config('rest.user_id'))) === kohana::config('rest.user_id')) {
      $occurrence_id = substr($id, strlen(kohana::config('rest.user_id')));
      $params = array('occurrence_id' => $occurrence_id);
    }
    else {
      // @todo What happens if system not recognised?
      $params = array('external_key' => $id);
    }
    $params['dataset_name_attr_id'] = kohana::config('rest.dataset_name_attr_id');

    $report = $this->loadReport('rest_api/filterable_taxon_observations', $params);
    if (empty($report['content']['records'])) {
      $this->apiResponse->fail('No Content', 204);
    }
    elseif (count($report['content']['records']) > 1) {
      kohana::log('error', 'Internal error. Request for single record returned multiple');
      $this->apiResponse->fail('Internal Server Error', 500);
    }
    else {
      $this->apiResponse->succeed(
        $report['content']['records'][0],
        array(
          'attachHref' => array('taxon-observations', 'id'),
          'columns' => $report['content']['columns'],
        )
      );
    }
  }

  /**
   * GET handler for the taxon-observations resource.
   *
   * Outputs a list of taxon observation details.
   *
   * @todo Ensure delete information is output.
   */
  private function taxonObservationsGet() {
    $this->checkPaginationParams();
    $params = [
      // Limit set to 1 more than we need, so we can ascertain if next page
      // required.
      'limit' => $this->request['page_size'] + 1,
      'offset' => ($this->request['page'] - 1) * $this->request['page_size'],
    ];
    $this->checkDate($this->request['edited_date_from'], 'edited_date_from');
    $params['edited_date_from'] = $this->request['edited_date_from'];
    if (!empty($this->request['edited_date_to'])) {
      $this->checkDate($this->request['edited_date_to'], 'edited_date_to');
      $params['edited_date_to'] = $this->request['edited_date_to'];
    }
    $params['dataset_name_attr_id'] = kohana::config('rest.dataset_name_attr_id');
    $report = $this->loadReport('rest_api/filterable_taxon_observations', $params);
    $this->apiResponse->succeed(
      $this->listResponseStructure($report['content']['records']),
      [
        'attachHref' => array('taxon-observations', 'id'),
        'columns' => $report['content']['columns'],
      ]
    );
  }

  /**
   * GET handler for the annotations/n resource.
   *
   * Outputs a single annotations's details.
   *
   * @param string $id
   *   Unique ID for the annotations to output.
   */
  private function annotationsGetId($id) {
    $params = array('id' => $id);
    $report = $this->loadReport('rest_api/filterable_annotations', $params);
    if (empty($report['content']['records'])) {
      $this->apiResponse->fail('No Content', 204);
    }
    elseif (count($report['content']['records']) > 1) {
      kohana::log('error', 'Internal error. Request for single annotation returned multiple');
      $this->apiResponse->fail('Internal Server Error', 500);
    }
    else {
      $record = $report['content']['records'][0];
      $record['taxonObservation'] = array(
        'id' => $record['taxon_observation_id'],
        // @todo href
      );
      $this->apiResponse->succeed($record, array(
        'attachHref' => [
          'annotations',
          'id',
        ],
        'attachFkLink' => [
          'taxonObservation',
          'taxon_observation_id',
          'taxon-observations',
        ],
        'columns' => $report['content']['columns'],
      ));
    }
  }

  /**
   * GET handler for the annotations resource.
   *
   * Outputs a list of annotation details.
   */
  private function annotationsGet() {
    // @todo Integrate determinations in the output
    // @todo handle taxonVersionKey properly
    // @todo handle unansweredQuestion
    $this->checkPaginationParams();
    $params = [
      // Limit set to 1 more than we need, so we can ascertain if next page
      // required.
      'limit' => $this->request['page_size'] + 1,
      'offset' => ($this->request['page'] - 1) * $this->request['page_size'],
    ];
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
    $this->apiResponse->succeed(
      $this->listResponseStructure($records),
      [
        'attachHref' => [
          'annotations',
          'id',
        ],
        'attachFkLink' => [
          'taxonObservation',
          'taxon_observation_id',
          'taxon-observations',
        ],
        'columns' => $report['content']['columns'],
      ]
    );
  }

  /**
   * GET handler for the taxa/search resource.
   *
   * Returns search results on taxon names.
   *
   * @todo Reports can control output elements in same way
   * @todo option to limit columns in results
   * @todo caching option
   */
  private function taxaGet() {
    $segments = $this->uri->segment_array();
    if (count($segments) !== 4 || $segments[4] !== 'search') {
      $this->apiResponse->fail('Bad request', 404, "Resource taxa not known, try taxa/search");
    }
    $params = array_merge(array(
      'limit' => REST_API_DEFAULT_PAGE_SIZE,
      'include' => ['data', 'count', 'paging', 'columns'],
    ), $this->request);
    try {
      $params['count'] = FALSE;
      $query = postgreSQL::taxonSearchQuery($params);
    }
    catch (Exception $e) {
      $this->apiResponse->fail('Bad request', 400, $e->getMessage());
      error_logger::log_error('REST Api exception during build of taxon search query', $e);
    }
    $db = new Database();
    $result = [];
    if (in_array('data', $params['include'])) {
      $result['data'] = $db->query($query);
    }
    if (in_array('count', $params['include']) || in_array('paging', $params['include'])) {
      if (isset($params['known_count'])) {
        $count = $params['known_count'];
      }
      else {
        $params['count'] = TRUE;
        $countQuery = postgreSQL::taxonSearchQuery($params);
        $countData = $db->query($countQuery)->current();
        $count = $countData->count;
      }
      if (in_array('count', $params['include'])) {
        $result['count'] = $count;
      }
      if (in_array('paging', $params['include'])) {
        $result['paging'] = $this->getPagination($count);
      }
    }
    $columns = array(
      'taxa_taxon_list_id' => array('caption' => 'Taxa taxon list ID'),
      'searchterm' => array('caption' => 'Search term'),
      'highlighted' => array('caption' => 'Highlighted'),
      'taxon' => array('caption' => 'Taxon'),
      'authority' => array('caption' => 'Authority'),
      'language_iso' => array('caption' => 'Language'),
      'preferred_taxon' => array('caption' => 'Preferred name'),
      'preferred_authority' => array('caption' => 'Preferred name authority'),
      'default_common_name' => array('caption' => 'Common name'),
      'taxon_group' => array('caption' => 'Taxon group'),
      'preferred' => array('caption' => 'Preferred'),
      'preferred_taxa_taxon_list_id' => array('caption' => 'Preferred taxa taxon list ID'),
      'taxon_meaning_id' => array('caption' => 'Taxon meaning ID'),
      'external_key' => array('caption' => 'External Key'),
      'taxon_group_id' => array('caption' => 'Taxon group ID'),
      'parent_id' => array('caption' => 'Parent taxa taxon list ID'),
      'identification_difficulty' => array('caption' => 'Ident. difficulty'),
      'id_diff_verification_rule_id' => array('caption' => 'Ident. difficulty verification rule ID'),
    );
    if (in_array('columns', $params['include'])) {
      $result['columns'] = $columns;
    }
    $resultOptions = array('columns' => $columns);
    $this->apiResponse->succeed(
      $result,
      $resultOptions
    );
  }

  /**
   * Handler for GET requests to the reports resource.
   *
   * Can return one of the following:
   * * A level of the report hierarchy (defined by the folder path in the
   *   segments after /reports/ in the url, e.g.
   *   /reports/library/occurrences.
   * * The output of a report defined by the file path in the segments after
   *   /reports/ in the url, e.g.
   *   /reports/library/occurrences/filterable_explore_list.xml
   * * A list of parameters for a report, if /params is added to the end of the
   *   file path in the URL segments, e.g.
   *   /reports/library/occurrences/filterable_explore_list.xml/params.
   * * A list of columns for a report, if /params is added to the end of the
   *   file path in the URL segments, e.g.
   *   /reports/library/occurrences/filterable_explore_list.xml/columns.
   *
   * The reports GET request supports the following resource_options defined in
   * the API's configuration file, either set for each authentication method,
   * or each client project:
   * * featured - set to true if only reports with the featured attribute set
   *   to true should be allowed. This restricts API usage to reports which
   *   have been vetted and are known to be "well-behaved".
   * * summary - set to true if only reports with the summary attribute set to
   *   true should be allowed. This restricts API usage to reports which show
   *   summary data only.
   * * cached - set to true if report output should be cached for performance
   *   at the cost of the data being slightly out of date.
   * * limit_to_own_data - set to true to ensure that only a users own records
   *   are included in report output. Applies when authenticating as a
   *   warehouse user only.
   */
  private function reportsGet() {
    $this->apiResponse->trackTime();
    $segments = $this->uri->segment_array();
    // Remove services/rest/reports from the URL segments.
    array_shift($segments);
    array_shift($segments);
    array_shift($segments);

    if (count($segments) && preg_match('/\.xml$/', $segments[count($segments) - 1])) {
      $this->getReportOutput($segments);
    }
    elseif (count($segments) > 1 && preg_match('/\.xml$/', $segments[count($segments) - 2])) {
      // Passing a sub-action to a report, e.g. /params.
      if ($segments[count($segments) - 1] === 'params') {
        $this->getReportParams($segments);
      }
      if ($segments[count($segments) - 1] === 'columns') {
        $this->getReportColumns($segments);
      }
    }
    else {
      $this->getReportHierarchy($segments);
    }
  }

  /**
   * Converts the segments in the URL to a full report path.
   *
   * Report path is then suitable for passing to the report engine.
   *
   * @param array $segments
   *   URL segments.
   *
   * @return string
   *   Report path.
   */
  private function getReportFileNameFromSegments(array $segments) {
    // Report file specified. Don't need the .xml suffix.
    $fileName = array_pop($segments);
    $fileName = substr($fileName, 0, strlen($fileName) - 4);
    $segments[] = $fileName;
    return implode('/', $segments);
  }

  /**
   * Returns a pagination structure for inclusion in the response.
   *
   * @param int $count
   *   Known count of query results.
   *
   * @return array
   *   Pagination structure.
   */
  private function getPagination($count) {
    $urlPrefix = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $parts = explode('?', $_SERVER['REQUEST_URI']);
    $url = $parts[0];
    if (count($parts) > 1) {
      parse_str($parts[1], $params);
    }
    else {
      $params = array();
    }
    $params['known_count'] = $count;
    $pagination = array(
      'self' => "$urlPrefix$url?" . http_build_query($params),
    );
    $limit = empty($params['limit']) ? REST_API_DEFAULT_PAGE_SIZE : $params['limit'];
    $offset = empty($params['offset']) ? 0 : $params['offset'];
    if ($offset > 0) {
      $params['offset'] = max($offset - $limit, 0);
      $pagination['previous'] = "$urlPrefix$url?" . http_build_query($params);
    }
    if ($offset + $limit < $count) {
      $params['offset'] = $offset + $limit;
      $pagination['next'] = "$urlPrefix$url?" . http_build_query($params);
    }
    return $pagination;
  }

  /**
   * Returns true if the selected project is in Autofeed mode.
   *
   * @return bool
   *   Autofeed mode setting.
   */
  private function getAutofeedMode() {
    return
      // Using a configured project which specified autofeed mode.
      (!empty($this->request['proj_id']) && !empty($this->projects[$this->request['proj_id']]['autofeed']))
      // Or the request explicitly sets autofeed mode.
      || (isset($_GET['autofeed']) && $_GET['autofeed'] === 't');
  }

  /**
   * Gets the output for a report.
   *
   * Uses the segments in the URL to find a report file and run it, with the
   * expectation of producing report data output.
   *
   * @param array $segments
   *   URL segments.
   *
   * @throws \RestApiAbort
   */
  private function getReportOutput(array $segments) {
    if ($this->getAutofeedMode()) {
      // Check the semaphore to ensure we don't run the same autofeed query
      // twice at one time. Could happen if a query runs slowly.
      if (variable::get("rest-autofeed-$_GET[proj_id]-running") === TRUE) {
        $this->apiResponse->fail('Service still processing prior request for feed.', 503, "Service unavailable");
        throw new RestApiAbort("Autofeed for $_GET[proj_id] already running");
      }
      // Set a semaphore so we know this feed is querying.
      variable::set("rest-autofeed-$_GET[proj_id]-running", TRUE);
    }
    try {
      $reportFile = $this->getReportFileNameFromSegments($segments);
      $report = $this->loadReport($reportFile, $_GET);
      if (isset($report['content']['records'])) {
        if ($this->getAutofeedMode()) {
          // Autofeed mode - no need for pagination info.
          $this->apiResponse->succeed([
            'data' => $report['content']['records'],
          ], [], TRUE);
        }
        else {
          $pagination = $this->getPagination($report['count']);
          $this->apiResponse->succeed(
            [
              'count' => $report['count'],
              'paging' => $pagination,
              'data' => $report['content']['records'],
            ],
            [
              'columns' => $report['content']['columns'],
            ]
          );
        }
      }
      elseif (isset($report['content']['parameterRequest'])) {
        // @todo: handle param requests
        $this->apiResponse->fail('Bad request (parameters missing)', 400, "Missing parameters");
      }
    }
    finally {
      if ($this->getAutofeedMode()) {
        // Remove the semaphore as no longer querying.
        variable::delete("rest-autofeed-$_GET[proj_id]-running");
      }
    }
  }

  /**
   * Output either the columns list or params list for a report.
   *
   * @param array $segments
   *   URL segmens allowing the report path to be built.
   * @param string $item
   *   Type of metadata - either parameters or columns.
   * @param string $description
   *   Description to include in the response metadata (for HTML only)
   */
  private function getReportMetadataItem(array $segments, $item, $description) {
    $this->apiResponse->includeEmptyValues = FALSE;
    // The last segment is the /params or /columns action.
    array_pop($segments);
    $reportFile = $this->getReportFileNameFromSegments($segments);
    $this->loadReportEngine();
    $metadata = $this->reportEngine->requestMetadata("$reportFile.xml", TRUE);
    $list = $metadata[$item];
    if ($item === 'parameters') {
      // Columns with a datatype can also be used as a parameter.
      foreach ($metadata['columns'] as $name => $columnDef) {
        if (!empty($columnDef['datatype']) && !isset($list[$name])) {
          $def = [
            'description' => 'Column available for use as a parameter',
          ];
          if (!empty($columnDef['display'])) {
            $def['display'] = $columnDef['display'];
          }
          if (!empty($columnDef['datatype'])) {
            $def['datatype'] = $columnDef['datatype'];
          }
          $list[$name] = $def;
        }
      }
    }
    $this->apiResponse->responseTitle = ucfirst("$item for $reportFile");
    $this->apiResponse->wantIndex = TRUE;
    $this->apiResponse->succeed(array('data' => $list), array('metadata' => array('description' => $description)));
  }

  /**
   * Get's report parameters.
   *
   * Uses the segments in the URL to find a report file and retrieve the
   * parameters metadata for it.
   *
   * @param array $segments
   *   URL segments.
   */
  private function getReportParams(array $segments) {
    return $this->getReportMetadataItem($segments, 'parameters',
      'A list of parameters available for filtering this report.');
  }

  /**
   * Gets report columns.
   *
   * Uses the segments in the URL to find a report file and retrieve the
   * parameters metadata for it.
   *
   * @param array $segments
   *   URL segments.
   */
  private function getReportColumns(array $segments) {
    return $this->getReportMetadataItem($segments, 'columns',
      'A list of columns provided in the output of this report.');
  }

  /**
   * Return the report hierarchy.
   *
   * Retrieves a list of folders and report files at a single location in the
   * report hierarchy.
   *
   * @param array $segments
   *   URL segments.
   */
  private function getReportHierarchy(array $segments) {
    $this->loadReportEngine();
    // @todo Cache this
    $reportHierarchy = $this->reportEngine->reportList();
    $response = array();
    $folderReadme = '';
    $featuredFolder = (count($segments) === 1 && $segments[0] === 'featured');
    if ($featuredFolder) {
      $folderReadme = kohana::lang("rest_api.reports.featured_folder_description");
    }
    else {
      // Iterate down the report hierarchy to the level we want to show
      // according to the request.
      foreach ($segments as $idx => $segment) {
        if ($idx === count($segments) - 1) {
          // If the final folder, then grab any readme text to add to the
          // metadata.
          $folderReadme = empty($reportHierarchy[$segment]['description']) ?
            '' : $reportHierarchy[$segment]['description'];
        }
        $reportHierarchy = $reportHierarchy[$segment]['content'];
      }
    }
    $this->applyReportRestrictions($reportHierarchy);
    $relativePath = implode('/', $segments);
    // If at the top level of the hierarchy, add a virtual featured folder
    // unless we are only showing featured reports.
    if (empty($segments) && empty($this->resourceOptions['featured'])) {
      $reportHierarchy = array(
        'featured' => array(
          'type' => 'folder',
          'description' => kohana::lang("rest_api.reports.featured_folder_description"),
        ),
      ) + $reportHierarchy;
    }
    if ($featuredFolder) {
      $response = array();
      $this->getFeaturedReports($reportHierarchy, $response);
    }
    else {
      foreach ($reportHierarchy as $key => $metadata) {
        unset($metadata['content']);
        if ($metadata['type'] === 'report') {
          $this->addReportLinks($metadata);
        }
        else {
          $path = empty($relativePath) ? $key : "$relativePath/$key";
          $metadata['href'] = $this->apiResponse->getUrlWithCurrentParams("reports/$path");
        }
        $response[$key] = $metadata;
      }
    }
    // Build a description. A generic statement about the path, plus anything
    // included in the folder's readme file.
    $relativePath = '/reports/' . ($relativePath ? "$relativePath/" : '');
    $description = 'A list of reports and report folders stored on the warehouse under ' .
      "the folder <em>$relativePath</em>. $folderReadme";
    $this->apiResponse->succeed($response, array('metadata' => array('description' => $description)));
  }

  /**
   * Limit the available reports.
   *
   * Applies limitations to the available reports depending on the
   * configuration. For example, it may be appropriate to limit user based
   * authentication methods to featured reports only to be sure they don't
   * access a report which does not apply the user filter, or summary reports
   * which don't include raw data.
   *
   * @param array $reportHierarchy
   *   Reports hierarchy structure.
   */
  private function applyReportRestrictions(array &$reportHierarchy) {
    if (in_array('featured', $this->resourceOptions) || in_array('summary', $this->resourceOptions)) {
      foreach ($reportHierarchy as $item => &$cfg) {
        if ($cfg['type'] === 'report' && (
          (!empty($this->resourceOptions['featured']) && (!isset($cfg['featured']) || $cfg['featured'] !== 'true')) ||
          (!empty($this->resourceOptions['summary']) && (!isset($cfg['summary']) || $cfg['summary'] !== 'true'))
          )) {
          unset($reportHierarchy[$item]);
        }
        elseif ($cfg['type'] === 'folder') {
          // Recurse into folders.
          $this->applyReportRestrictions($cfg['content']);
          // Folders may be left empty if no featured reports in them.
          if (empty($cfg['content'])) {
            unset($reportHierarchy[$item]);
          }
        }
      }
    }
  }

  /**
   * Adds links to the metadata for a report.
   *
   * Adds additional links to the metadata for a report - including to the
   * report itself, plus to the columns and params subresources and a help link
   * if standard params are supported.
   *
   * @param array $metadata
   *   Report metadata about to be output.
   */
  private function addReportLinks(array &$metadata) {
    $metadata['href'] = $this->apiResponse->getUrlWithCurrentParams("reports/$metadata[path].xml");
    $metadata['params'] = [
      'href' => $this->apiResponse->getUrlWithCurrentParams("reports/$metadata[path].xml/params"),
    ];
    if (!empty($metadata['standard_params'])) {
      // Reformat the info that the report supports standard paramenters into
      // REST structure.
      $metadata['params']['info'] =
        'Supports the standard set of parameters for ' . $metadata['standard_params'];
      $metadata['params']['helpLink'] =
        'https://indicia-docs.readthedocs.io/en/latest/developing/reporting/standard-parameters.html';
      unset($metadata['standard_params']);
    }
    $metadata['columns'] = array(
      'href' => $this->apiResponse->getUrlWithCurrentParams("reports/$metadata[path].xml/columns"),
    );
  }

  /**
   * Finds all featured reports in the hierarchy.
   *
   * @param array $reportHierarchy
   *   Structure of the report hierachy.
   * @param array $reports
   *   Array which will be populated with a list of the reports.
   */
  private function getFeaturedReports(array $reportHierarchy, array &$reports) {
    foreach ($reportHierarchy as $key => $metadata) {
      if ($metadata['type'] === 'report' && !empty($metadata['featured'])) {
        $this->addReportLinks($metadata);
        $reports[$metadata['path']] = $metadata;
      }
      elseif ($metadata['type'] === 'folder') {
        $this->getFeaturedReports($metadata['content'], $reports);
      }
    }
  }

  /**
   * Check the datatype of a parameter.
   *
   * Examines the value of a parameter in the request and check's its datatype
   * against the parameter config. Also checks any paremeter values are within
   * the list of options defined for controlled list parameters. Boolean
   * parameter values are converted from strings into bool datatypes.
   *
   * @param string $paramName
   *   Parameter name.
   * @param string $value
   *   Parameter value.
   * @param array $paramDef
   *   Parameter definition.
   */
  private function checkParamDatatype($paramName, &$value, array $paramDef) {
    $datatype = preg_replace('/\[\]$/', '', $paramDef['datatype']);
    $trimmed = trim($value);
    if ($datatype === 'integer' && !preg_match('/^\d+$/', $trimmed)) {
      $this->apiResponse->fail('Bad request', 400, "Invalid integer format for $paramName parameter");
    }
    elseif ($datatype === 'date') {
      if (strpos($value, 'T') === FALSE) {
        $dt = DateTime::createFromFormat("Y-m-d", $trimmed);
      }
      else {
        $dt = DateTime::createFromFormat("Y-m-d\TH:i:s", $trimmed);
      }
      if ($dt === FALSE || array_sum($dt->getLastErrors())) {
        $this->apiResponse->fail('Bad request', 400, "Invalid date for $paramName parameter");
      }
    }
    elseif ($datatype === 'boolean') {
      if (!preg_match('/^(true|false)$/', $trimmed)) {
        $this->apiResponse->fail('Bad request', 400,
            "Invalid boolean for $paramName parameter, value should be true or false");
      }
      // Set the value to a real bool.
      $value = $trimmed === 'true';
    }
    // If a limited options set available then check the value is in the list.
    if (!empty($paramDef['options']) && !in_array($trimmed, $paramDef['options'])) {
      $this->apiResponse->fail('Bad request', 400, "Invalid value for $paramName parameter");
    }
  }

  /**
   * Validates request parameters.
   *
   * Validates that the request parameters provided fullful the requirements of
   * the method being called.
   *
   * @param string $resourceName
   *   Name of the resource.
   * @param string $method
   *   Method name, e.g. GET or POST.
   * @param bool $requestForId
   *   ID of resource being requested if any.
   */
  private function validateParameters($resourceName, $method, $requestForId) {
    $info = $this->resourceConfig[$resourceName][$method]['subresources'];
    // If requesting a list, then use the entry keyed '', else use the named
    // entry.
    if ($requestForId) {
      foreach ($info as $key => $method) {
        if ($key !== '') {
          $thisMethod = $method;
          break;
        }
      }
    }
    else {
      if (!empty($this->resourceConfig[$resourceName][$method]['options']['segments'])) {
        $segments = $this->uri->segment_array();
        if (count($segments) === 4 && isset($info[$segments[4]])) {
          // Path indicates a subresource.
          $thisMethod = $info[$segments[4]];
        }
      }
      if (!isset($thisMethod)) {
        // Use the default subresource.
        $thisMethod = $info[''];
      }
    }
    // Check through the known list of parameters to ensure data formats are
    // correct and required parameters are provided.
    foreach ($thisMethod['params'] as $paramName => $paramDef) {
      if (!empty($paramDef['required']) && empty($this->request[$paramName])) {
        $this->apiResponse->fail('Bad request', 400, "Missing $paramName parameter");
      }
      if (!empty($this->request[$paramName])) {
        $datatype = $paramDef['datatype'];
        // If an array datatype, attempt to decode the JSON array parameter. If
        // not JSON, convert parameter value to the only element in an array.
        if (preg_match('/\[\]$/', $paramDef['datatype'])) {
          $decoded = json_decode($this->request[$paramName]);
          $this->request[$paramName] = $decoded && is_array($decoded) ? $decoded : [$this->request[$paramName]];
          foreach ($this->request[$paramName] as &$value) {
            $this->checkParamDatatype($paramName, $value, $paramDef);
          }
        }
        else {
          $this->checkParamDatatype($paramName, $this->request[$paramName], $paramDef);
        }
      }
    }
  }

  /**
   * Response struture for lists of items.
   *
   * Converts an array list of items loaded from the database into the
   * structure ready for returning as the result from an API call. Adds
   * pagination information as well as hrefs for contained objects.
   *
   * @param object $list
   *   List of records from the database as a database object.
   *
   * @return array
   *   Restructured version of the input list, with pagination and hrefs added.
   */
  private function listResponseStructure($list) {
    $pagination = [
      'self' => $this->generateLink(['page' => $this->request['page']]),
    ];
    if ($this->request['page'] > 1) {
      $pagination['previous'] = $this->generateLink(['page' => $this->request['page'] - 1]);
    }
    // Set a flag to indicate another page required.
    if (count($list) > $this->request['page_size']) {
      $pagination['next'] = $this->generateLink(['page' => $this->request['page'] + 1]);
    }
    return [
      'paging' => $pagination,
      'data' => $list,
    ];
  }

  /**
   * Loads a single instance of the report engine.
   */
  private function loadReportEngine() {
    // Should also return an object to iterate rather than loading the full
    // array.
    if (!isset($this->reportEngine)) {
      $this->reportEngine = new ReportEngine(array($this->clientWebsiteId));
      // Resource configuration can provide a list of restricted reports that
      // are allowed for this client.
      if (isset($this->resourceOptions['authorise'])) {
        $this->reportEngine->setAuthorisedReports($this->resourceOptions['authorise']);
      }
    }
  }

  /**
   * Load the output of a report.
   *
   * Method to load the output of a report being used to construct an API call
   * GET response. This method uses the cache where relevant and calls
   * loadReportFromDb only when a database hit is required.
   *
   * @param string $report
   *   Report name (excluding .xml extension).
   * @param array $params
   *   Report parameters in an associative array.
   *
   * @return array
   *   Report response structure.
   */
  private function loadReport($report, array $params) {
    // Don't need to count records when autofeeding.
    if ($this->getAutofeedMode()) {
      // Fudge to prevent the overhead of a count query.
      $_REQUEST['wantCount'] = '0';
      // Set max number of records to process.
      $params['limit'] = AUTOFEED_DEFAULT_PAGE_SIZE;
      // Find our state data for this feed.
      $afSettings = (array) variable::get("rest-autofeed-$_GET[proj_id]", ['mode' => 'notStarted'], FALSE);
      if ($afSettings['mode'] === 'notStarted') {
        // First use of this autofeed, so we need to store the timepoint to
        // ensure we capture all changes after the initial sweep up of records
        // is done. Switch state to initial loading.
        $afSettings = [
          'mode' => 'initialLoad',
          'last_date' => date('c'),
          'last_id' => 0,
        ];
        variable::set("rest-autofeed-$_GET[proj_id]", $afSettings);
      }
      elseif ($afSettings['mode'] === 'initialLoad') {
        // Part way through initial loading. Use the last loaded ID as a start
        // point for next block of records.
        $params['last_id'] = $afSettings['last_id'];
      }
      elseif ($afSettings['mode'] === 'updates') {
        // Doing updates of changes only as initial load done.
        $params['last_date'] = $afSettings['last_date'];
        $params['orderby'] = 'updated_on';
      }
    }
    if (!empty($this->resourceOptions['cached'])) {
      $cache = new Cache();
      $keys = array_merge($params);
      unset($keys['format']);
      unset($keys['secret']);
      unset($keys['proj_id']);
      unset($keys['cached']);
      ksort($keys);
      $reportGuid = $report . ':' . http_build_query($keys);
      $cacheId = md5($reportGuid);
      if ($cached = $cache->get($cacheId)) {
        // The first element of the cache data is the report plus parameters -
        // check it is the same (in case the md5 filename clashed).
        if ($cached[0] === $reportGuid) {
          array_shift($cached);
          return $cached;
        }
      }
    }
    $output = $this->loadReportFromDb($report, $params);
    if (!empty($this->resourceOptions['cached'])) {
      // Temporarily store the identifier for our request in the output, cache
      // it, then remove the identifier.
      array_unshift($output, $reportGuid);
      $cache->set($cacheId, $output, 'reportOutput', Kohana::config('indicia.nonce_life'));
      array_shift($output);
    }
    return $output;
  }

  /**
   * Loads the data for a report from the database, without using caching.
   *
   * @param string $report
   *   Report name.
   * @param array $params
   *   Report parameters.
   *
   * @return mixed
   *   Report output.
   */
  private function loadReportFromDb($report, array $params) {
    $this->loadReportEngine();
    // @todo Apply permissions for user or website & write tests
    // load the filter associated with the project ID
    if (isset($this->clientSystemId)) {
      $filter = $this->loadFilterForProject($this->request['proj_id']);
    }
    elseif (isset($this->clientUserId)) {
      // When authenticating a user, you can use one of the permissions filters
      // for the user to gain access to a wider pool of records, e.g. for a
      // verifier to access all records they have rights to.
      if (!empty($_GET['filter_id'])) {
        $filter = $this->getPermissionsFilterDefinition();
      }
      else {
        // Default filter - the user's records for this website only.
        $filter = array(
          'website_list' => $this->clientWebsiteId,
          'created_by_id' => $this->clientUserId,
        );
      }
    }
    else {
      if (!isset($this->clientWebsiteId)) {
        $this->apiResponse->fail('Internal server error', 500, 'Minimal filter on website ID not provided.');
      }
      $filter = array(
        'website_list' => $this->clientWebsiteId,
      );
    }
    // The project's filter acts as a context for the report, meaning it
    // defines the limit of all the records that are available for this project.
    foreach ($filter as $key => $value) {
      $params["{$key}_context"] = $value;
    }
    $params['system_user_id'] = $this->serverUserId;
    if (isset($this->clientSystemId)) {
      // For client systems, the project defines how records are allowed to be
      // shared with this client.
      $params['sharing'] = $this->projects[$this->request['proj_id']]['sharing'];
    }
    $params = array_merge(
      array('limit' => REST_API_DEFAULT_PAGE_SIZE),
      $params
    );
    // Get the output, setting the option to load a pg result object rather
    // than populated array unless we are going to cache the result in which
    // case we need it all.
    $output = $this->reportEngine->requestReport("$report.xml", 'local', 'xml',
      $params, !empty($this->resourceOptions['cached']));
    // Include count query results if not already known from a previous
    // request.
    $output['count'] = empty($_GET['known_count']) ? $this->reportEngine->recordCount() : $_GET['known_count'];
    return $output;
  }

  /**
   * Regenerates the current GET URI link.
   *
   * Replacing one or more paraneters with a new value, e.g. a new page ID.
   *
   * @param array $replacements
   *   List of parameters and values to replace.
   *
   * @return string
   *   The reconstructed URL.
   */
  private function generateLink(array $replacements = []) {
    $params = array_merge($_GET, $replacements);
    return url::base() . 'index.php/services/rest/' . $this->resourceName . '?' . http_build_query($params);
  }

  /**
   * Returns the filter definition associated with a given project ID.
   *
   * @param string $id
   *   Project ID.
   *
   * @return array
   *   Filter definition.
   */
  private function loadFilterForProject($id) {
    if (!isset($this->projects[$id])) {
      $this->apiResponse->fail('Bad request', 400, "Invalid project requested");
    }
    if (isset($this->projects[$id]['filter_id'])) {
      $filterId = $this->projects[$id]['filter_id'];
      $filters = $this->db->select('definition')->from('filters')->where(array('id' => $filterId, 'deleted' => 'f'))
        ->get()->result_array();
      if (count($filters) !== 1) {
        $this->apiResponse->fail('Internal Server Error', 500, 'Failed to find unique project filter record');
      }
      return json_decode($filters[0]->definition, TRUE);
    }
    else {
      return array();
    }
  }

  /**
   * Apply permissions filter passed in the URL parameters.
   *
   * If a filter ID is being passed in the URL to override the default
   * limitation when authenticating as a user of only being able to see your
   * own records, checks that the ID in the query params points to a filter
   * belonging to the user which grants them additional permissions and if so,
   * returns the definition of the filter.
   *
   * @return array
   *   Filter definition or empty array.
   */
  private function getPermissionsFilterDefinition() {
    $filters = $this->db->select('definition')
      ->from('filters')
      ->join('filters_users', array(
        'filters_users.filter_id' => 'filters.id',
      ))
      ->where(array(
        'filters.id' => $_GET['filter_id'],
        'filters.deleted' => 'f',
        'filters.defines_permissions' => 't',
        'filters_users.user_id' => $this->clientUserId,
        'filters_users.deleted' => 'f',
      ))
      ->get()->result_array();
    if (count($filters) !== 1) {
      $this->apiResponse->fail('Bad request', 400, 'Filter ID missing or not a permissions filter for the user');
    }
    return json_decode($filters[0]->definition, TRUE);
  }

  /**
   * Check API version.
   *
   * Checks the API version provided in the URI (if any) to ensure that the
   * version is supported. Returns a 400 Bad request if not supported.
   *
   * @param array $arguments
   *   Additional URI segments.
   */
  private function checkVersion(array &$arguments) {
    if (count($arguments)
        && preg_match('/^v(?P<major>\d+)\.(?P<minor>\d+)$/', $arguments[count($arguments) - 1], $matches)) {
      array_pop($arguments);
      // Check not asking for an invalid version.
      if (!in_array($matches['major'] . '.' . $matches['minor'], $this->supportedApiVersions)) {
        $this->apiResponse->fail('Bad request', 400, 'Unsupported API version');
      }
      $this->apiMajorVersion = $matches['major'];
      $this->apiMinorVersion = $matches['minor'];
    }
  }

  /**
   * Ensures that the request contains a page size and page.
   *
   * Defaults the values if necessary. Will return an HTTP error response if
   * either parameter is not an integer.
   */
  private function checkPaginationParams() {
    $this->request = array_merge(array(
      'page' => 1,
      'page_size' => REST_API_DEFAULT_PAGE_SIZE,
    ), $this->request);
    $this->checkInteger($this->request['page'], 'page');
    $this->checkInteger($this->request['page_size'], 'page_size');
  }

  /**
   * Checks if the current request is to an Elasticsearch proxy end-point.
   *
   * If so and the end-point is open access, authenticates the request.
   */
  private function checkElasticsearchRequest() {
    $resource = $this->uri->segment(3);
    $esConfig = Kohana::config('rest.elasticsearch');
    if ($resource && $esConfig && array_key_exists($resource, $esConfig)) {
      $this->elasticProxy = $resource;
      if (array_key_exists('open', $esConfig[$resource]) && $esConfig[$resource]['open'] === TRUE) {
        $this->authenticated = TRUE;
      }
    }
  }

  /**
   * Checks that the request is authentic.
   */
  private function authenticate() {
    $this->isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $this->serverUserId = Kohana::config('rest.user_id');
    $methods = Kohana::config('rest.authentication_methods');
    $this->authenticated = FALSE;
    $this->checkElasticsearchRequest();
    if ($this->authenticated) {
      kohana::log('debug', "Open elasticsearch request");
      return;
    }
    // Provide a default if not configured.
    if (!$methods) {
      $methods = $this->defaultAuthenticationMethods;
    }
    if ($this->restrictToAuthenticationMethods !== FALSE) {
      $methods = array_intersect_key($methods, $this->restrictToAuthenticationMethods);
    }
    foreach ($methods as $method => $cfg) {
      // Skip methods if http and method requires https.
      if ($this->isHttps || array_key_exists('allow_http', $cfg) || in_array('allow_http', $cfg)) {
        $method = ucfirst($method);
        // Try this authentication method.
        call_user_func(array($this, "authenticateUsing$method"));
        if ($this->authenticated) {
          // Double checking required for Elasticsearch proxy.
          if ($this->elasticProxy) {
            if (empty($cfg['resource_options']['elasticsearch']) || !in_array($this->elasticProxy, $cfg['resource_options']['elasticsearch'])) {
              kohana::log('debug', "Elasticsearch request to $this->elasticProxy not enabled for $method");
              $this->apiResponse->fail('Unauthorized', 401, 'Unable to authorise');
            }
            if (!empty($this->clientConfig) && empty($this->clientConfig['elasticsearch']) ||
                !in_array($this->elasticProxy, $this->clientConfig['elasticsearch'])) {
              kohana::log('debug', "Elasticsearch request to $this->elasticProxy not enabled for client");
              $this->apiResponse->fail('Unauthorized', 401, 'Unable to authorise');
            }
          }
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

  /**
   * Attempts to authenticate using the oAuth2 protocal.
   */
  private function authenticateUsingOauth2User() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization']) && strpos($headers['Authorization'], 'Bearer ') === 0) {
      $suppliedToken = str_replace('Bearer ', '', $headers['Authorization']);
      $this->cache = new Cache();
      // Get all cache entries that match this nonce.
      $paths = $this->cache->exists($suppliedToken);
      foreach ($paths as $path) {
        // Find the parts of each file name, which is the cache entry ID, then
        // the mode.
        $tokens = explode('~', basename($path));
        if ($tokens[1] === 'oAuthUserAccessToken') {
          $data = $this->cache->get($tokens[0]);
          if (preg_match('/^USER_ID:(?P<user_id>\d+):WEBSITE_ID:(?P<website_id>\d+)$/', $data, $matches)) {
            $this->clientWebsiteId = $matches['website_id'];
            // If option limit_to_own_data set, then only allow access to own
            // records.
            if (!empty($this->resourceOptions['limit_to_own_data'])) {
              $this->clientUserId = $matches['user_id'];
            }
            $this->authenticated = TRUE;
          }
        }
      }
    }
  }

  /**
   * Attempts to authenticate using the HMAC client protocal.
   */
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
          $this->clientConfig = $config[$clientSystemId];
          if (!empty($_REQUEST['proj_id'])) {
            $this->clientWebsiteId = $this->projects[$_REQUEST['proj_id']]['website_id'];
          }
          // Apart from the projects resource, other end-points will need a
          // proj_id if using client system based authorisation.
          if (($this->resourceName === 'taxon-observations' || $this->resourceName === 'annotations') &&
              (empty($_REQUEST['proj_id']) || empty($this->projects[$_REQUEST['proj_id']]))) {
            $this->apiResponse->fail('Bad request', 400, 'Project ID missing or invalid.');
          }
          $this->authenticated = TRUE;
        }
      }
    }
  }

  /**
   * Attempts to authenticate using the HMAC website protocal.
   */
  private function authenticateUsingHmacWebsite() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization']) && substr_count($headers['Authorization'], ':') === 3) {
      list($u, $websiteId, $h, $supplied_hmac) = explode(':', $headers['Authorization']);
      if ($u === 'WEBSITE_ID' && $h === 'HMAC') {
        // Input validation.
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

  /**
   * Attempts to authenticate using the direct user protocal.
   */
  private function authenticateUsingDirectUser() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization']) &&
        substr_count($headers['Authorization'], ':') === 5) {
      // 6 parts to authorisation required for user ID, website ID and password
      // pairs.
      list($u, $userId, $w, $websiteId, $h, $password) = explode(':', $headers['Authorization']);
      if ($u !== 'USER_ID' || $w !== 'WEBSITE_ID' || $h !== 'SECRET') {
        return;
      }
    }
    elseif (kohana::config('rest.allow_auth_tokens_in_url') === TRUE &&
          !empty($_GET['user_id']) && !empty($_GET['secret'])) {
      $userId = $_GET['user_id'];
      $websiteId = $_GET['website_id'];
      $password = $_GET['secret'];
    }
    else {
      return;
    }
    // Input validation.
    if (!preg_match('/^\d+$/', $userId) || !preg_match('/^\d+$/', $websiteId)) {
      $this->apiResponse->fail('Unauthorized', 401, 'User ID or website ID incorrect format.');
    }
    $users = $this->db->select('password')
      ->from('users')
      ->where(array('id' => $userId))
      ->get()->result_array(FALSE);
    if (count($users) !== 1) {
      $this->apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
    }
    $auth = new Auth();
    if ($auth->checkPasswordAgainstHash($password, $users[0]['password'])) {
      // If option limit_to_own_data set, then only allow access to own records.
      if (!empty($this->resourceOptions['limit_to_own_data'])) {
        $this->clientUserId = $userId;
      }
      $this->clientWebsiteId = $websiteId;
      // @todo Is this user a member of the website?
      $this->authenticated = TRUE;
    }
    else {
      $this->apiResponse->fail('Unauthorized', 401, 'Incorrect password for user.');
    }
    // @todo Apply user ID limit to data, limit to filterable reports
  }

  /**
   * Attempts to authenticate using the direct client protocal.
   */
  private function authenticateUsingDirectClient() {
    $headers = apache_request_headers();
    $config = Kohana::config('rest.clients');
    if (isset($headers['Authorization']) && substr_count($headers['Authorization'], ':') === 3) {
      list($u, $clientSystemId, $h, $secret) = explode(':', $headers['Authorization']);
      if ($u !== 'USER' || $h !== 'SECRET') {
        return;
      }
    }
    elseif (kohana::config('rest.allow_auth_tokens_in_url') === TRUE &&
          !empty($_GET['user']) && !empty($_GET['secret'])) {
      $clientSystemId = $_GET['user'];
      $secret = $_GET['secret'];
    }
    else {
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
    $this->clientConfig = $config[$clientSystemId];
    // Taxon observations and annotations resource end-points will need a
    // proj_id if using client system based authorisation.
    if (($this->resourceName === 'taxon-observations' || $this->resourceName === 'annotations') &&
        (empty($_REQUEST['proj_id']) || empty($this->projects[$_REQUEST['proj_id']]))) {
      $this->apiResponse->fail('Bad request', 400, 'Project ID missing or invalid.');
    }
    if (!empty($_REQUEST['proj_id'])) {
      $projectConfig = $this->projects[$_REQUEST['proj_id']];
      $this->clientWebsiteId = $projectConfig['website_id'];
      // The client project config can override the resource options, e.g.
      // access to summary or featured reports.
      if (isset($projectConfig['resource_options']) &&
          isset($projectConfig['resource_options'][$this->resourceName])) {
        $this->resourceOptions = $projectConfig['resource_options'][$this->resourceName];
      }
    }
    $this->authenticated = TRUE;
  }

  /**
   * Attempts to authenticate using the direct website protocal.
   */
  private function authenticateUsingDirectWebsite() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization']) && substr_count($headers['Authorization'], ':') === 3) {
      list($u, $websiteId, $h, $password) = explode(':', $headers['Authorization']);
      if ($u !== 'WEBSITE_ID' || $h !== 'SECRET') {
        return;
      }
    }
    elseif (kohana::config('rest.allow_auth_tokens_in_url') === TRUE &&
        !empty($_GET['website_id']) && !empty($_GET['secret'])) {
      $websiteId = $_GET['website_id'];
      $password = $_GET['secret'];
    }
    else {
      return;
    }
    // Input validation.
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
    $this->authenticated = TRUE;
    // @todo Apply website ID limit to data
  }

  /**
   * Checks a parameter passed to a request is a valid integer.
   *
   * Returns an HTTP error response if not valid.
   *
   * @param string $value
   *   Parameter to check.
   * @param string $param
   *   Name of the parameter being checked.
   */
  private function checkInteger($value, $param) {
    if (!preg_match('/^\d+$/', $value)) {
      $this->apiResponse->fail('Bad request', 400, "Parameter $param is not an integer");
    }
  }

  /**
   * Checks a parameter passed to a request is a valid date.
   *
   * Returns an HTTP error response if not valid.
   *
   * @param string $value
   *   Parameter to check.
   * @param string $param
   *   Name of the parameter being checked.
   */
  private function checkDate($value, $param) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
      $this->apiResponse->fail('Bad request', 400, "Parameter $param is not an valid date");
    }
  }

  /**
   * Generates a unique token, e.g. for oAuth2.
   *
   * @return string
   *   Token.
   */
  private function getToken() {
    return sha1(time() . ':' . rand() . $_SERVER['REMOTE_ADDR'] . ':' . kohana::config('indicia.private_key'));
  }

}
