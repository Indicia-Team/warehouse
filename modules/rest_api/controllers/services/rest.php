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

use \Firebase\JWT;

define("REST_API_DEFAULT_PAGE_SIZE", 100);
define("AUTOFEED_DEFAULT_PAGE_SIZE", 10000);
// Max load from ES, keep fairly low to avoid PHP memory overload.
define('MAX_ES_SCROLL_SIZE', 5000);
define('SCROLL_TIMEOUT', '5m');

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
 * Simple object to keep globally useful stuff in.
 */
class RestObjects {
  public static $db;
  public static $apiResponse;
  public static $clientWebsiteId;
  public static $clientUserId;
  public static $handlerModule;
}

/**
 * Controller class for the RESTful API.
 *
 * Implements handlers for the various resource URIs.
 *
 * Visit index.php/services/rest for a help page.
 */
class Rest_Controller extends Controller {

  /**
   * Defines which ES CSV column download template to use.
   *
   * Only supports "default" or empty string currently.
   *
   * @var string
   */
  private $esCsvTemplate = 'default';

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
    'jwtUser' => [
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
   * Name of the authentication method.
   *
   * @var string
   */
  private $authMethod;

  /**
   * Config settings relating to the selected auth method.
   *
   * @var array
   */
  private $authConfig;

  /**
   * Allow override of default ES filters on record created_by_id
   *
   * When using user based auth (jwtUser or oAuth2User), configuration can
   * included limit_to_own_data which applies an automatic user filter unless
   * the request access token includes a claim that alldata access is allowed.
   *
   * @var bool
   */
  private $allowAllData = FALSE;

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
   * For ES paged downloads, holds the mode (scroll or composite).
   *
   * @var string
   */
  private $pagingMode = 'off';

  /**
   * For ES paged downloads, holds the current request state (initial or nextPage).
   *
   * @var string
   */
  private $pagingModeState;

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
    'annotations' => [
      'GET' => [
        'annotations' => [
          'deprecated' => TRUE,
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
        'annotations/{id}' => [
          'deprecated' => TRUE,
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
    'media-queue' => [
      'POST' => [
        'media-queue' => [],
      ],
    ],
    'locations' => [
      'GET' => [
        'locations' => [
          'params' => [
            'verbose' => [
              'datatype' => 'integer',
            ],
          ],
        ],
        'locations/{id}' => [
          'params' => [
            'verbose' => [
              'datatype' => 'integer',
            ],
          ],
        ],
      ],
      'POST' => [
        'locations' => [],
      ],
      'PUT' => [
        'locations/{id}' => [],
      ],
      'DELETE' => [
        'locations/{id}' => [],
      ],
    ],
    'occurrence-attributes' => [
      'GET' => [
        'occurrence-attributes' => [],
        'occurrence-attributes/{id}' => [],
      ],
      'POST' => [
        'occurrence-attributes' => [],
      ],
      'PUT' => [
        'occurrence-attributes/{id}' => [],
      ],
      'DELETE' => [
        'occurrence-attributes/{id}' => [],
      ],
    ],
    'occurrence-attributes-websites' => [
      'GET' => [
        'occurrence-attributes-websites' => [],
        'occurrence-attributes-websites/{id}' => [],
      ],
      'POST' => [
        'occurrence-attributes-websites' => [],
      ],
      'PUT' => [
        'occurrence-attributes-websites/{id}' => [],
      ],
      'DELETE' => [
        'occurrence-attributes-websites/{id}' => [],
      ],
    ],
    'occurrences' => [
      'GET' => [
        'occurrences' => [
          'params' => [
            'verbose' => [
              'datatype' => 'integer',
            ],
          ],
        ],
        'occurrences/{id}' => [
          'params' => [
            'verbose' => [
              'datatype' => 'integer',
            ],
          ],
        ],
      ],
      'POST' => [
        'occurrences' => [],
        'occurrences/list' => [],
      ],
      'PUT' => [
        'occurrences/{id}' => [],
      ],
      'DELETE' => [
        'occurrences/{id}' => [],
      ],
    ],
    'projects' => [
      'GET' => [
        'projects' => ['deprecated' => TRUE],
        'projects/{id}' => ['deprecated' => TRUE],
      ],
    ],
    'reports' => [
      'GET' => [
        'reports' => [],
        'reports/featured' => [],
        'reports/{path}' => [],
        'reports/{path}/{file.xml}' => [
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
        'reports/{path}/{file.xml}/params' => [],
        'reports/{path}/{file.xml}/columns' => [],
      ]
    ],
    'sample-attributes' => [
      'GET' => [
        'sample-attributes' => [],
        'sample-attributes/{id}' => [],
      ],
      'POST' => [
        'sample-attributes' => [],
      ],
      'PUT' => [
        'sample-attributes/{id}' => [],
      ],
      'DELETE' => [
        'sample-attributes/{id}' => [],
      ],
    ],
    'sample-attributes-websites' => [
      'GET' => [
        'sample-attributes-websites' => [],
        'sample-attributes-websites/{id}' => [],
      ],
      'POST' => [
        'sample-attributes-websites' => [],
      ],
      'PUT' => [
        'sample-attributes-websites/{id}' => [],
      ],
      'DELETE' => [
        'sample-attributes-websites/{id}' => [],
      ],
    ],
    'samples' => [
      'GET' => [
        'samples' => [
          'params' => [
            'verbose' => [
              'datatype' => 'integer',
            ],
          ],
        ],
        'samples/{id}' => [
          'params' => [
            'verbose' => [
              'datatype' => 'integer',
            ],
          ],
        ],
      ],
      'POST' => [
        'samples' => [],
        'samples/list' => [],
      ],
      'PUT' => [
        'samples/{id}' => [],
      ],
      'DELETE' => [
        'samples/{id}' => [],
      ],
    ],
    'surveys' => [
      'GET' => [
        'surveys' => [
          'params' => [
            'verbose' => [
              'datatype' => 'integer',
            ],
          ],
        ],
        'surveys/{id}' => [
          'params' => [
            'verbose' => [
              'datatype' => 'integer',
            ],
          ],
        ],
      ],
      'POST' => [
        'surveys' => [],
      ],
      'PUT' => [
        'surveys/{id}' => [],
      ],
      'DELETE' => [
        'surveys/{id}' => [],
      ],
    ],
    'taxa' => [
      'GET' => [
        'taxa/search' => [
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
            'freshwater_flag' => [
              'datatype' => 'boolean',
            ],
            'terrestrial_flag' => [
              'datatype' => 'boolean',
            ],
            'non_native_flag' => [
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
    'taxon-observations' => [
      'GET' => [
        'taxon-observations' => [
          'deprecated' => TRUE,
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
        'taxon-observations/{id}' => [
          'deprecated' => TRUE,
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
      'POST' => [
        'taxon-observations' => ['deprecated' => TRUE],
      ],
    ],
  ];

  /**
   * Rest_Controller constructor.
   */
  public function __construct() {
    // Ensure we have a db instance and response object ready.
    RestObjects::$db = new Database();
    RestObjects::$apiResponse = new RestApiResponse();
    parent::__construct();
  }

  /**
   * Controller for the default page for the /rest path.
   *
   * Outputs help text to describe the available API resources.
   */
  public function index() {
    try {
      if (!file_exists(MODPATH . 'rest_api/config/rest.php')) {
        RestObjects::$apiResponse->fail('Internal Server Error', 500,
          'Missing config file. See https://indicia-docs.readthedocs.io/en/latest/administrating/warehouse/modules/rest-api.html for more info.');
      }
      // A temporary array to simulate the arguments, which we can use to check
      // for versioning.
      $arguments = [$this->uri->last_segment()];
      $this->checkVersion($arguments);
      RestObjects::$apiResponse->index($this->resourceConfig);
    }
    catch (RestApiAbort $e) {
      // No action if a proper abort.
    }
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
        RestObjects::$apiResponse->fail('Bad request', 400, 'Missing required parameters');
      }
      if ($_POST['grant_type'] !== 'password') {
        RestObjects::$apiResponse->fail('Not implemented', 501, 'Grant type not implemented: ' . $_POST['grant_type']);
      }
      $matchField = strpos($_POST['username'], '@') === FALSE ? 'u.username' : 'email_address';
      $websiteId = preg_replace('/^website_id:/', '', $_POST['client_id']);
      // @todo Test for is the user a member of this website?
      $users = RestObjects::$db->select('u.id, u.password, u.core_role_id, uw.site_role_id')
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
        RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
      }
      if ($users[0]['site_role_id'] === NULL && $users[0]['core_role_id'] === NULL) {
        RestObjects::$apiResponse->fail('Unauthorized', 401, 'User does not have access to website.');
      }
      $auth = new Auth();
      if (!$auth->checkPasswordAgainstHash($_POST['password'], $users[0]['password'])) {
        RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
      }
      if (substr($_POST['client_id'], 0, 11) !== 'website_id:') {
        RestObjects::$apiResponse->fail('Unauthorized', 401, 'Invalid client_id format. ' . var_export($_POST, TRUE));
      }
      $accessToken = $this->getToken();
      $cache = new Cache();
      $uid = $users[0]['id'];
      $data = "USER_ID:$uid:WEBSITE_ID:$websiteId";
      $cache->set($accessToken, $data, 'oAuthUserAccessToken', Kohana::config('indicia.nonce_life'));
      RestObjects::$apiResponse->succeed(array(
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
    if (!file_exists(MODPATH . 'rest_api/config/rest.php')) {
      $this->fail('Internal Server Error', 500, 'Missing config file.');
    }
    $tm = microtime(TRUE);
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
      $this->applyCorsHeader();
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
      else {
        $resourceConfig = $this->findResourceConfig($this->resourceName);
        if (!$resourceConfig) {
          RestObjects::$apiResponse->fail('Not Found', 404, "Resource $this->resourceName not known");
        }
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method === 'OPTIONS') {
          // A request for the methods allowed for this resource.
          header('Allow: ' . strtoupper(implode(', ', array_merge(array_keys($resourceConfig), ['OPTIONS']))));
        }
        else {
          if (!array_key_exists($this->method, $resourceConfig)) {
            RestObjects::$apiResponse->fail('Method Not Allowed', 405, $this->method . " not allowed for $name");
          }
          $this->request = $this->method === 'GET' ? $_GET : $_POST;
          $this->checkVersion($arguments);
          $methodConfig = $resourceConfig[$this->method];
          $pathConfigPattern = $this->getPathConfigPatternMatch($methodConfig, $arguments);
          $pathConfig = $methodConfig[$pathConfigPattern];
          $methodName = $this->getMethodName($arguments, strpos($pathConfigPattern, '{path}') !== FALSE);
          $requestForId = NULL;
          $ids = preg_grep('/^([A-Z]{3})?\d+$/', $arguments);
          if (count($ids) > 0) {
            $requestForId = $ids[0];
          }
          // When using a client system ID, we also want a project ID if
          // accessing taxon observations or annotations.
          if (isset($this->clientSystemId) && ($name === 'taxon_observations' || $name === 'annotations')) {
            if (empty($this->request['proj_id'])) {
              // Should not have got this far - just in case.
              RestObjects::$apiResponse->fail('Bad request', 400, 'Missing proj_id parameter');
            }
            else {
              $this->checkAllowedResource($this->request['proj_id'], $this->resourceName);
            }
          }
          $this->validateParameters($pathConfig);
          if (RestObjects::$handlerModule === 'rest_api' && method_exists($this, $methodName)) {
            $class = $this;
          }
          elseif (RestObjects::$handlerModule !== 'rest_api' && method_exists(RestObjects::$handlerModule . '_rest', $methodName)) {
            // Expect any modules extending the API to implement a helper class <module name>_rest.
            $class = RestObjects::$handlerModule . '_rest';
          }
          else {
            RestObjects::$apiResponse->fail('Not Found', 404, "Resource $name not known for method $this->method");
          }
          call_user_func([$class, $methodName], $requestForId);
        }
      }
    }
    catch (RestApiAbort $e) {
      // No action if a proper abort.
    }
    if (class_exists('request_logging')) {
      $io = in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE']) ? 'i' : 'o';
      $websiteId = isset(RestObjects::$clientWebsiteId) ? RestObjects::$clientWebsiteId : 0;
      $userId = isset(RestObjects::$clientUserId) ? RestObjects::$clientUserId : 0;
      $subTask = implode('/', $arguments);
      request_logging::log($io, 'rest', $subTask, $name, $websiteId, $userId, $tm, RestObjects::$db);
    }
  }

  /**
   * Finds the configuration for a named resource.
   *
   * For core entities, the resources are listed in $this->resourceConfig.
   * Resources may also be exposed by warehouse modules implementing the
   * `<module>_extend_rest_api` plugin function in which case the config is
   * provided by the module.
   *
   * @param string $resourceName
   *   Resource name to look up in the configuration.
   *
   * @return array
   *   Configuration for this resource.
   */
  private function findResourceConfig($resourceName) {
    if (array_key_exists($this->resourceName, $this->resourceConfig)) {
      RestObjects::$handlerModule = 'rest_api';
      return $this->resourceConfig[$this->resourceName];
    }
    else {
      // load from plugin or fail
      $pluginConfigs = $this->getRestPluginConfigs();
      if (array_key_exists($this->resourceName, $pluginConfigs)) {
        RestObjects::$handlerModule = $pluginConfigs[$this->resourceName]['module'];
        return $pluginConfigs[$this->resourceName];
      }
    }
    return FALSE;
  }

  /**
   * Retrieves the resource configuration exposed by other warehouse modules.
   *
   * Warehouse modules implementing the`<module>_extend_rest_api` plugin
   * function can provide configuration for additional entities.
   *
   * @return array
   *   List of resource configuration keyed by resource name. See
   *   $this->resourceConfig for an example of the structure.
   */
  private function getRestPluginConfigs() {
    $cacheId = 'rest-plugin-config';
    $cache = Cache::instance();
    // Get list of plugins which integrate with the REST API. Use cache so
    // we avoid loading all module files unnecessarily.
    if (!($pluginConfig = $cache->get($cacheId))) {
      $pluginConfig = [];
      foreach (Kohana::config('config.modules') as $path) {
        $plugin = basename($path);
        if (file_exists("$path/plugins/$plugin.php")) {
          require_once "$path/plugins/$plugin.php";
          if (function_exists($plugin . '_extend_rest_api')) {
            $config = call_user_func($plugin . '_extend_rest_api');
            // Attach the module name to the config, so easy to find functions and entity data.
            foreach ($config as &$entityConfig) {
              $entityConfig['module'] = $plugin;
            }
            $pluginConfig = array_merge($pluginConfig, $config);
          }
        }
      }
      $cache->set($cacheId, $pluginConfig);
    }
    return $pluginConfig;
  }

  /**
   * Finds the key in the method config which matches the path.
   *
   * In $resourceConfig, an endpoint contains a list of different HTTP methods,
   * e.g. GET, POST. Within these are configurations for each path pattern
   * possible for that endpoint. Matches the current URL arguments to locate
   * the correct path pattern to apply. E.g. "occurrences/123" would map to
   * path pattern "occurrences/{id}" or "reports/library/occurrences" would map
   * to "reports/{path}".
   *
   * @param array $methodConfig
   *   Current resource endpoint and HTTP method combination's resource config.
   * @param array $arguments
   *   URL path sections as an array.
   *
   * @return string
   *   Name of the key in the current HTTP method's config to use.
   */
  private function getPathConfigPatternMatch($methodConfig, $arguments) {
    // Build an array to help locate the correct bit of configuration for
    // this path inside the resource config's method key.
    $searchArr = array_merge($arguments);
    $searchArr = preg_replace('/^([A-Z]{3})?\d+$/', '{id}', $searchArr);
    $searchArr = preg_replace('/^[a-zA-Z0-9_-]+\.xml$/', '{file.xml}', $searchArr);
    $searchStr = implode('/', array_merge([$this->resourceName], $searchArr));
    if (!isset($methodConfig[$searchStr])) {
      // Failed to find the configuration for this path. Segments that
      // aren't in braces can all be replaced by {path} as an
      // alternative.
      $searchStr = "$this->resourceName/" . preg_replace('/^[^{]*(?!{.+})/', '{path}', implode('/', $searchArr));
      if (!isset($methodConfig[$searchStr])) {
        RestObjects::$apiResponse->fail('Bad request', 404, 'Resource path ' . implode('/', $arguments) . ' not found');
      }
    }
    return $searchStr;
  }

  /**
   * Works out the class method to call which matches the current request.
   *
   * @param array $arguments
   *   URL path sections as an array.
   * @param bool $usingPath
   *   TRUE if the current URL includes a group of path parts that maps to
   *   {path} in the configuration key.
   *
   * @return string
   *   Method name.
   */
  private function getMethodName($arguments, $usingPath) {
    $methodNamePartsArr = array_merge($arguments);
    $methodNamePartsArr = preg_replace('/^([A-Z]{3})?\d+$/', 'id', $methodNamePartsArr);
    $methodNamePartsArr = preg_replace('/^[a-zA-Z0-9_-]+\.xml$/', 'file', $methodNamePartsArr);
    array_map(function ($item) { return ucFirst($item); }, $methodNamePartsArr);
    $methodName = implode('', $methodNamePartsArr);
    if ($usingPath) {
      $methodName = preg_replace('/^[^{]*(?!{.+})/', 'Path', $methodName);
    }
    // Add the resource and method prefix to the method name.
    $methodName = lcfirst(str_replace('-', '', ucwords($this->resourceName, '-'))) . ucfirst(strtolower($this->method)) . $methodName;
    return $methodName;
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
        RestObjects::$apiResponse->fail('No Content', 204);
      }
    }
  }

  /**
   * Templates for ES CSV output.
   *
   * @var array
   */
  private $esCsvTemplates = [
    "default" => [
      ['caption' => 'Record ID', 'field' => 'id'],
      ['caption' => 'RecordKey', 'field' => '_id'],
      ['caption' => 'Sample ID', 'field' => 'event.event_id'],
      ['caption' => 'Date interpreted', 'field' => '#event_date#'],
      ['caption' => 'Date start', 'field' => 'event.date_start'],
      ['caption' => 'Date end', 'field' => 'event.date_end'],
      ['caption' => 'Recorded by', 'field' => 'event.recorded_by'],
      ['caption' => 'Determined by', 'field' => 'identification.identified_by'],
      ['caption' => 'Grid reference', 'field' => 'location.output_sref'],
      ['caption' => 'System', 'field' => 'location.output_sref_system'],
      ['caption' => 'Coordinate uncertainty (m)', 'field' => 'location.coordinate_uncertainty_in_meters'],
      ['caption' => 'Lat/Long', 'field' => 'location.point'],
      ['caption' => 'Location name', 'field' => 'location.verbatim_locality'],
      ['caption' => 'Higher geography', 'field' => '#higher_geography::name#'],
      ['caption' => 'Vice County', 'field' => '#higher_geography:Vice County:name#'],
      ['caption' => 'Vice County number', 'field' => '#higher_geography:Vice County:code#'],
      ['caption' => 'Identified by', 'field' => 'identification.identified_by'],
      ['caption' => 'Taxon accepted name', 'field' => 'taxon.accepted_name'],
      ['caption' => 'Taxon recorded name', 'field' => 'taxon.taxon_name'],
      ['caption' => 'Taxon common name', 'field' => 'taxon.vernacular_name'],
      ['caption' => 'Taxon group', 'field' => 'taxon.group'],
      ['caption' => 'Kindom', 'field' => 'taxon.kingdom'],
      ['caption' => 'Phylum', 'field' => 'taxon.phylum'],
      ['caption' => 'Order', 'field' => 'taxon.order'],
      ['caption' => 'Family', 'field' => 'taxon.family'],
      ['caption' => 'Genus', 'field' => 'taxon.genus'],
      ['caption' => 'Taxon Version Key', 'field' => 'taxon.taxon_id'],
      ['caption' => 'Accepted Taxon Version Key', 'field' => 'taxon.accepted_taxon_id'],
      ['caption' => 'Sex', 'field' => 'occurrence.sex'],
      ['caption' => 'Stage', 'field' => 'occurrence.life_stage'],
      ['caption' => 'Quantity', 'field' => 'occurrence.organism_quantity'],
      ['caption' => 'Zero abundance', 'field' => 'occurrence.zero_abundance'],
      ['caption' => 'Sensitive', 'field' => 'metadata.sensitive'],
      ['caption' => 'Record status', 'field' => 'identification.verification_status'],
      ['caption' => 'Record substatus', 'field' => '#null_if_zero:identification.verification_substatus#'],
      ['caption' => 'Query status', 'field' => 'identification.query'],
      ['caption' => 'Verifier', 'field' => 'identification.verifier.name'],
      ['caption' => 'Verified on', 'field' => 'identification.verified_on'],
      ['caption' => 'Website', 'field' => 'metadata.website.title'],
      ['caption' => 'Survey dataset', 'field' => 'metadata.survey.title'],
      ['caption' => 'Media', 'field' => '#occurrence_media#'],
    ],
    "easy-download" => [
      ['caption' => 'ID', 'field' => 'id'],
      ['caption' => 'RecordKey', 'field' => '_id'],
      ['caption' => 'External key', 'field' => 'occurrence_external_key'],
      ['caption' => 'Source', 'field' => '#datasource_code:<wt> | <st> {|} <gt>#'],
      ['caption' => 'Species', 'field' => 'taxon.accepted_name'],
      ['caption' => 'Common name', 'field' => 'taxon.vernacular_name'],
      ['caption' => 'Taxon group', 'field' => 'taxon.group'],
      ['caption' => 'Kingdom', 'field' => 'taxon.kingdom'],
      ['caption' => 'Order', 'field' => 'taxon.order'],
      ['caption' => 'Family', 'field' => 'taxon.family'],
      ['caption' => 'TaxonVersionKey', 'field' => 'taxon.taxon_id'],
      ['caption' => 'Site name', 'field' => 'location.verbatim_locality'],
      ['caption' => 'Original map ref', 'field' => 'location.input_sref'],
      ['caption' => 'Latitude', 'field' => '#lat:decimal#'],
      ['caption' => 'Longitude', 'field' => '#lon:decimal#'],
      ['caption' => 'Projection', 'field' => '#sref_system:location.input_sref_system:alphanumeric#'],
      ['caption' => 'Precision', 'field' => 'location.coordinate_uncertainty_in_meters'],
      ['caption' => 'Output map ref', 'field' => 'location.output_sref'],
      ['caption' => 'Projection', 'field' => '#sref_system:location.output_sref_system:alphanumeric#'],
      ['caption' => 'Biotope', 'field' => 'event.habitat'],
      ['caption' => 'VC number', 'field' => '#higher_geography:Vice County:code#'],
      ['caption' => 'Vice County', 'field' => '#higher_geography:Vice County:name#'],
      ['caption' => 'Date interpreted', 'field' => '#event_date#'],
      ['caption' => 'Date from', 'field' => 'event.date_start'],
      ['caption' => 'Date to', 'field' => 'event.date_end'],
      ['caption' => 'Date type', 'field' => 'event.date_type'], 
      ['caption' => 'Sample method', 'field' => 'event.sampling_protocol'],
      ['caption' => 'Recorder', 'field' => 'event.recorded_by'],
      ['caption' => 'Determiner', 'field' => 'identification.identified_by'],
      ['caption' => 'Recorder certainty', 'field' => 'identification.recorder_certainty'],
      ['caption' => 'Sex', 'field' => 'occurrence.sex'],
      ['caption' => 'Stage', 'field' => 'occurrence.life_stage'],
      ['caption' => 'Count of sex or stage', 'field' => 'occurrence.organism_quantity'],
      ['caption' => 'Zero abundance', 'field' => 'occurrence.zero_abundance'], 
      ['caption' => 'Comment', 'field' => 'occurrence.occurrence_remarks'],
      ['caption' => 'Sample comment', 'field' => 'event.event_remarks'],
      ['caption' => 'Images', 'field' => '#occurrence_media#'],
      ['caption' => 'Input on date', 'field' => '#datetime:metadata.created_on:d/m/Y H\:i#'],
      ['caption' => 'Last edited on date', 'field' => '#datetime:metadata.updated_on:d/m/Y H\:i#'],
      ['caption' => 'Verification status 1', 'field' => '#verification_status:astext#'],
      ['caption' => 'Verification status 2', 'field' => '#verification_substatus:astext#'],
      ['caption' => 'Query', 'field' => '#query:astext#'],
      ['caption' => 'Verifier', 'field' => 'identification.verifier.name'],
      ['caption' => 'Verified on', 'field' => '#datetime:identification.verified_on:d/m/Y H\:i#'],
      ['caption' => 'Licence', 'field' => 'metadata.licence_code'],
      ['caption' => 'Automated checks', 'field' => 'identification.auto_checks.result'], 
    ],
    "mapmate" => [
      ['caption' => 'Taxon', 'field' => 'taxon.accepted_name'],
      ['caption' => 'Site', 'field' => 'location.verbatim_locality'],
      ['caption' => 'Gridref', 'field' => 'location.output_sref'],
      ['caption' => 'VC', 'field' => '#higher_geography:Vice County:code:mapmate#'],
      ['caption' => 'Recorder', 'field' => 'event.recorded_by'],
      ['caption' => 'Determiner', 'field' => 'identification.identified_by'],
      ['caption' => 'Date', 'field' => '#event_date:mapmate#'],
      ['caption' => 'Quantity', 'field' => '#organism_quantity:mapmate#'],
      ['caption' => 'Method', 'field' => 'event.sampling_protocol'],
      ['caption' => 'Sex', 'field' => '#sex:mapmate#'],
      ['caption' => 'Stage', 'field' => '#life_stage:mapmate#'],
      ['caption' => 'Status', 'field' => '#blank#'],
      ['caption' => 'Comment', 'field' => '#sample_occurrence_comment#'],
      ['caption' => 'ID', 'field' => 'id'],
      ['caption' => 'RecordKey', 'field' => '_id'],
      ['caption' => 'NonNumericQuantity', 'field' => '#organism_quantity:exclude_integer#'],
      ['caption' => 'Habitat', 'field' => 'event.habitat'],
      ['caption' => 'Input on date', 'field' => '#datetime:metadata.created_on:d/m/Y H\:i\:s#'],
      ['caption' => 'Last edited on date', 'field' => '#datetime:metadata.updated_on:d/m/Y G\:i\:s#'],
      ['caption' => 'Verification status 1', 'field' => '#verification_status:astext#'],
      ['caption' => 'Verification status 2', 'field' => '#verification_substatus:astext#'],
      ['caption' => 'Query', 'field' => '#query:astext#'],
      ['caption' => 'Licence', 'field' => 'metadata.licence_code'],
    ]
  ];

  /**
   * Works out the list of columns for an ES CSV download.
   *
   * @param obj $postObj
   *   Request object.
   */
  private function getColumnsTemplate(&$postObj) {
    // Params for configuring an ES CSV download template get extracted and not
    // sent to ES.
    if (isset($postObj->columnsTemplate)) {
      $this->esCsvTemplate = $postObj->columnsTemplate;
      unset($postObj->columnsTemplate);
    }
    if (isset($postObj->addColumns)) {
      // Columns converted to associative array.
      $this->esCsvTemplateAddColumns = json_decode(json_encode($postObj->addColumns), TRUE);
      unset($postObj->addColumns);
    }
    if (isset($postObj->removeColumns)) {
      $this->esCsvTemplateRemoveColumns = (array) $postObj->removeColumns;
      unset($postObj->removeColumns);
    }
  }

   /**
   * A cached lookup of the websites that are available for a sharing mode.
   *
   * @param integer $websiteId
   *   ID of the website that is receiving the shared data.
   *
   * @return array
   *   List of website IDs that will share their data.
   */
  private function getSharedWebsiteList($websiteId, $sharing = 'reporting') {
    $tag = "website-shares-$websiteId";
    $cacheId = "$tag-$sharing";
    $cache = Cache::instance();
    if ($cached = $cache->get($cacheId)) {
      return explode(',', $cached);
    }
    $qry = $this->db->select('to_website_id')
      ->from('index_websites_website_agreements')
      ->where([
        "receive_for_$sharing" => 't',
        'from_website_id' => $websiteId
      ])
      ->get()->result();
    $ids = array();
    foreach ($qry as $row) {
      $ids[] = $row->to_website_id;
    }
    // Tag all cache entries for this website so they can be cleared together
    // when changes are saved. Also note the cached entry is an imploded string
    // so we benefit from sharing cache hits with the reporting engine.
    $cache->set($cacheId, implode(',', $ids), $tag);
    return $ids;
  }

  /**
   * Adds permissions filters to ES search, based on website ID and user ID.
   *
   * If the authentication method configuration (e.g. jwtUser) includes the
   * option limit_to_website in the settings for the Elasticsearch endpoint,
   * then automatically adds a terms filter on metadata.website.id. Also,
   * if the settings include limit_to_own_data for the endpoint, then adds a
   * terms filter on metadata.created_by_id. This can be overridden by
   * including the claim http://indicia.org.uk/alldata in the JWT access token.
   */
  private function applyEsPermissionsQuery(&$postObj) {
    $filters = [];
    if (!empty($this->esConfig['limit_to_own_data']) && !$this->allowAllData && RestObjects::$clientUserId) {
      $filters[] = ['term' => ['metadata.created_by_id' => RestObjects::$clientUserId]];
    }
    if (!empty($this->esConfig['limit_to_website']) && RestObjects::$clientWebsiteId) {
      // @todo Support for other sharing modes in JWT claims.
      $filters[] = ['terms' => ['metadata.website.id' => $this->getSharedWebsiteList(RestObjects::$clientWebsiteId)]];
    }
    if (count($filters) > 0) {
      if (!isset($postObj->query)) {
        $postObj->query = new stdClass();
      }
      if (!isset($postObj->query->bool)) {
        $postObj->query->bool = new stdClass();
      }
      if (!isset($postObj->query->bool->must)) {
        $postObj->query->bool->must = [];
      }
      $postObj->query->bool->must = array_merge($postObj->query->bool->must, $filters);
    }
  }

  /**
   * Calculate the data to post to an Elasticsearch search.
   *
   * @param obj $postObj
   *   Request object.
   * @param string $format
   *   Format identifier. If CSV then we can use this to do source filtering
   *   to lower memory consumption.
   * @param array|NULL $file
   *   Cached info about the file if paging.
   *
   * @return string
   *   Data to post.
   */
  private function getEsPostData($postObj, $format, $file, $isSearch) {
    if ($this->pagingMode === 'scroll' && $this->pagingModeState === 'nextPage') {
      // A subsequent hit on a scrolled request.
      $postObj = [
        'scroll_id' => $_GET['scroll_id'],
        'scroll' => SCROLL_TIMEOUT,
      ];
      return json_encode($postObj);
    }
    // Either unscrolled, or the first call to a scroll. So post the query.
    if ($this->pagingMode === 'scroll') {
      $postObj->size = MAX_ES_SCROLL_SIZE;
    }
    elseif ($this->pagingMode === 'composite' && isset($file['after_key'])) {
      $postObj->aggs->_rows->composite->after = $file['after_key'];
    }
    if ($isSearch) {
      $this->applyEsPermissionsQuery($postObj);
    }
    if ($format === 'csv') {
      $csvTemplate = $this->getEsCsvTemplate();
      $fields = [];
      // Check for special fields - may need to force the underlying raw fields
      // into the list of requested fields.
      foreach ($csvTemplate as $column) {
        $field = $column['field'];
        if (strpos($field, '_') === 0) {
          // Fields starting _ are not inside _source object.
          continue;
        }
        if (preg_match('/^[a-z_]+(\.[a-z_]+)*$/', $field)) {
          $fields[] = $field;
        }
        elseif (preg_match('/^#higher_geography(.*)#$/', $field)) {
          $fields[] = 'location.higher_geography.*';
        }
        elseif ($field === '#data_cleaner_icons#') {
          $fields[] = 'identification.auto_checks';
        }
        elseif (preg_match('/^#datasource_code(.*)#$/', $field)) {
          $fields[] = 'metadata.website';
          $fields[] = 'metadata.survey';
          $fields[] = 'metadata.group';
        }
        elseif (preg_match('/^#event_date(.*)#$/', $field)) {
          $fields[] = 'event.date_start';
          $fields[] = 'event.date_end';
        }
        elseif (preg_match('/^#vc(.*)#$/', $field)) {
          $fields[] = 'location.higher_geography';
        }
        elseif (preg_match('/^#sex(.*)#$/', $field)) {
          $fields[] = 'occurrence.sex';
        }
        elseif (preg_match('/^#life_stage(.*)#$/', $field)) {
          $fields[] = 'occurrence.life_stage';
        }
        elseif ($field ==='#sample_occurrence_comment#') {
          $fields[] = 'event.event_remarks';
          $fields[] = 'occurrence.occurrence_remarks';
        }
        elseif (preg_match('/^#organism_quantity(.*)#$/', $field)) {
          $fields[] = 'occurrence.organism_quantity';
          $fields[] = 'occurrence.zero_abundance';
        }
        elseif (preg_match('/^#(lat_lon|lat|lon)#$/', $field) || preg_match('/^#(lat|lon):(.*)#$/', $field)) {
          $fields[] = 'location.point';
        }
        elseif ($field === '#locality#') {
          $fields[] = 'location.verbatim_locality';
          $fields[] = 'location.higher_geography';
        }
        elseif ($field === '#occurrence_media#') {
          $fields[] = 'occurrence.media';
        }
        elseif ($field === '#status_icons#') {
          $fields[] = 'metadata';
          $fields[] = 'identification';
          $fields[] = 'occurrence.zero_abundance';
        }
        elseif (preg_match('/^#verification_status(.*)#$/', $field)) {
          $fields[] = 'identification.verification_status';
        }
        elseif (preg_match('/^#verification_substatus(.*)#$/', $field)) {
          $fields[] = 'identification.verification_substatus';
        }
        elseif (preg_match('/^#query(.*)#$/', $field)) {
          $fields[] = 'identification.query';
        }
        elseif (preg_match('/^#attr_value:(event|sample|parent_event|occurrence):(\d+)#$/', $field, $matches)) {
          $key = $matches[1] === 'parent_event' ? 'parent_attributes' : 'attributes';
          // Tolerate sample or event for entity parameter.
          $entity = in_array($matches[1], ['sample', 'event', 'parent_event']) ? 'event' : 'occurrence';
          $fields[] = "$entity.$key";
        }
        elseif (preg_match('/^#null_if_zero:([a-z_]+(\.[a-z_]+)*)#$/', $field, $matches)) {
          $fields[] = $matches[1];
        }
        elseif (preg_match('/^#datetime:([a-z_]+(\.[a-z_]+)*):.*#$/', $field, $matches)) {
          $fields[] = $matches[1];
        }
        elseif (preg_match('/^#sref_system:([a-z_]+(\.[a-z_]+)*):.*#$/', $field, $matches)) {
          $fields[] = $matches[1];
        }
      }
      $postObj->_source = array_values(array_unique($fields));
    }
    $r = json_encode($postObj, JSON_UNESCAPED_SLASHES);
    return str_replace(['"#emptyobj#"', '"#emptyarray#"'], ['{}', '[]'], $r);
  }

  /**
   * Works out the actual URL required for an Elasticsearch request.
   *
   * Caters for fact that the URL is different when scrolling to the next page
   * of a scrolled request. For unscrolled URLs adds the GET parameters to the
   * request if appropriate.
   *
   * @param string $url
   *   Elasticsearch alias URL.
   *
   * @return string
   *   Revised URL.
   */
  private function getEsActualUrl($url) {
    if ($this->pagingMode === 'scroll' && $this->pagingModeState === 'nextPage') {
      // On subsequent hits to a scrolled request, the URL is different.
      return preg_replace('/[a-z0-9_-]*\/_search$/', '_search/scroll', $url);
    }
    else {
      if (!empty($_GET)) {
        $params = array_merge($_GET);
        // Don't pass on the auth tokens.
        unset($params['user']);
        unset($params['website_id']);
        unset($params['secret']);
        unset($params['format']);
        unset($params['scroll']);
        unset($params['scroll_id']);
        unset($params['callback']);
        unset($params['aggregation_type']);
        unset($params['state']);
        unset($params['uniq_id']);
        unset($params['_']);
        if ($this->pagingMode === 'scroll' && $this->pagingModeState === 'initial') {
          $params['scroll'] = SCROLL_TIMEOUT;
        }
        $url .= '?' . http_build_query($params);
      }
      return $url;
    }
  }

  /**
   * Builds the header for the top of a scrolled Elasticsearch output.
   *
   * For example, adds the CSV row.
   *
   * @param string $format
   *   Data format, either json or csv.
   *
   * @return string
   *   Header content.
   */
  private function getEsOutputHeader($format) {
    if ($format === 'csv') {
      $csvTemplate = $this->getEsCsvTemplate();
      $row = array_map(function ($column) {
        // Cells containing a quote, a comma or a new line will need to be
        // contained in double quotes.
        if (preg_match('/["\n,]/', $column['caption'])) {
          // Double quotes within cells need to be escaped.
          return '"' . preg_replace('/"/', '""', $column['caption']) . '"';
        }
        return $column['caption'];
      }, array_values($csvTemplate));
      return chr(0xEF) . chr(0xBB) . chr(0xBF) . implode(',', $row) . "\n";
    }
    return '';
  }

  /**
   * Determines the columns template for an ES download.
   *
   * @return array
   *   List of column definitions to download.
   */
  private function getEsCsvTemplate() {
    // Start with the template columns set, or an empty array.
    if (array_key_exists($this->esCsvTemplate, $this->esCsvTemplates)) {
      $csvTemplate = $this->esCsvTemplates[$this->esCsvTemplate];
    } else {
      $csvTemplate = [];
    }
    // Append extra columns.
    if (!empty($this->esCsvTemplateAddColumns)) {
      if (isset($this->esCsvTemplateAddColumns[0])) {
        // New format, v4+.
        $csvTemplate = array_merge($csvTemplate, $this->esCsvTemplateAddColumns);
      }
      else {
        // Old format <v4.
        kohana::log('alert', 'Deprecated Elasticsearch download addColumns format detected.');
        foreach ($this->esCsvTemplateAddColumns as $caption => $field) {
          $csvTemplate[] = ['caption' => $caption, 'field' => $field];
        }
      }
    }
    // Remove any that need to be removed.
    if (!empty($this->esCsvTemplateRemoveColumns)) {
      foreach ($this->esCsvTemplateRemoveColumns as $col) {
        unset($csvTemplate[$col]);
      }
    }
    return $csvTemplate;
  }

  /**
   * Builds an empty CSV file ready to received a paged ES request.
   *
   * @param string $format
   *   Data format, either json or csv.
   *
   * @return array
   *   File array containing the name and handle.
   */
  private function preparePagingFile($format) {
    rest_utils::purgeOldFiles('download', 3600);
    $uniqId = uniqid('', TRUE);
    $filename = "download-$uniqId.$format";
    // Reopen file for appending.
    $handle = fopen(DOCROOT . "download/$filename", "w");
    fwrite($handle, $this->getEsOutputHeader($format));
    return [
      'uniq_id' => $uniqId,
      'filename' => $filename,
      'handle' => $handle,
      'done' => 0,
    ];
  }

  /**
   * Create a temporary file that will be used to build an ES download.
   *
   * @param string $format
   *   Data format, either json or csv.
   *
   * @return array
   *   File details, array containing filename and handle.
   */
  private function openPagingFile($format) {
    $uniqId = isset($_GET['uniq_id']) ? $_GET['uniq_id'] : $_GET['scroll_id'];
    $cache = Cache::instance();
    $info = $cache->get("es-paging-$uniqId");
    if ($info === NULL) {
      RestObjects::$apiResponse->fail('Bad request', 400, 'Invalid scroll_id or uniq_id parameter.');
    }
    $info['handle'] = fopen(DOCROOT . "download/$info[filename]", 'a');
    return $info;
  }

  /**
   * Works out the mode of paging for chunked downloads.
   *
   * Supports Elasticsearch scroll or composite aggregations for paging. Mode
   * is stored in $this->pagingMode.
   */
  private function getPagingMode($format) {
    $this->pagingModeState = empty($_GET['state']) ? 'initial' : $_GET['state'];
    if (isset($_GET['aggregation_type']) && $_GET['aggregation_type'] === 'composite') {
      $this->pagingMode = 'composite';
    }
    elseif ($format === 'csv') {
      $this->pagingMode = 'scroll';
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
  private function proxyToEs($url) {
    $format = isset($_GET['format']) && $_GET['format'] === 'csv' ? 'csv' : 'json';
    $postData = file_get_contents('php://input');
    $postObj = empty($postData) ? [] : json_decode($postData);
    $this->getPagingMode($format);
    $this->getColumnsTemplate($postObj);
    $file = NULL;
    if ($this->pagingModeState === 'initial') {
      // First iteration of a scrolled request, so prepare an output file.
      $file = $this->preparePagingFile($format);
    }
    elseif ($this->pagingModeState === 'nextPage') {
      $file = $this->openPagingFile($format);
    }
    else {
      echo $this->getEsOutputHeader($format);
    }
    $postData = $this->getEsPostData($postObj, $format, $file, preg_match('/\/_search/', $url));
    $actualUrl = $this->getEsActualUrl($url);
    $session = curl_init($actualUrl);
    if (!empty($postData) && $postData !== '[]') {
      curl_setopt($session, CURLOPT_POST, 1);
      curl_setopt($session, CURLOPT_POSTFIELDS, $postData);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      curl_setopt($session, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
    }
    curl_setopt($session, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
    // Do the POST and then close the session.
    $response = curl_exec($session);
    $headers = curl_getinfo($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
      $error = curl_error($session);
      kohana::log('error', 'ES proxy request failed: ' . $error . ': ' . json_encode($error));
      kohana::log('error', 'URL: ' . $actualUrl);
      kohana::log('error', 'Query: ' . $postData);
      kohana::log('error', 'Response: ' . $response);
      RestObjects::$apiResponse->fail('Internal server error', 500, json_encode($error));
    }
    curl_close($session);
    // Will need decoded data for processing CSV.
    if ($format === 'csv') {
      $data = json_decode($response, TRUE);
      if (!empty($data['error'])) {
        kohana::log('error', 'Bad ES Rest query response: ' . json_encode($data['error']));
        kohana::log('error', 'Query: ' . $postData);
        RestObjects::$apiResponse->fail('Bad request', 400, json_encode($data['error']));
      }
      // Find the list of documents or aggregation output to add to the CSV.
      $itemList = $this->pagingMode === 'composite'
        ? $data['aggregations']['_rows']['buckets']
        : $data['hits']['hits'];
      if ($this->pagingMode === 'composite' && !empty($data['aggregations']['_count'])) {
        $file['total'] = $data['aggregations']['_count']['value'];
      }
    }
    // First response from a scroll, need to grab the scroll ID.
    if ($this->pagingMode === 'scroll' && $this->pagingModeState === 'initial') {
      $file['scroll_id'] = $data['_scroll_id'];
      // ES6/7 tolerance.
      $file['total'] = isset($data['hits']['total']['value']) ? $data['hits']['total']['value'] : $data['hits']['total'];
    }
    elseif ($this->pagingMode === 'scroll' && $this->pagingModeState === 'nextPage') {
      $file['scroll_id'] = $_GET['scroll_id'];
    }

    if ($this->pagingMode === 'off') {
      switch ($format) {
        case 'csv':
          header('Content-type: text/csv');
          $out = fopen('php://output', 'w');
          $this->esToCsv($itemList, $out);
          fclose($out);
          break;

        case 'json':
          if (array_key_exists('charset', $headers)) {
            $headers['content_type'] .= '; ' . $headers['charset'];
          }
          header('Content-type: ' . $headers['content_type']);
          echo $response;
          break;

        default:
          throw new exception("Invalid format $format");
      }
    }
    else {
      switch ($format) {
        case 'csv':
          $this->esToCsv($itemList, $file['handle']);
          break;

        case 'json':
          // Append a separator to the output file.
          fwrite($file['handle'], "\n~~~\n");
          break;

        default:
          throw new exception("Invalid format $format");
      }
      fclose($file['handle']);
      unset($file['handle']);
      $done = FALSE;
      if ($this->pagingMode === 'scroll') {
        $file['done'] = min($file['total'], $file['done'] + MAX_ES_SCROLL_SIZE);
        $done = $file['done'] >= $file['total'];
      }
      elseif ($this->pagingMode === 'composite') {
        if ($format === 'csv') {
          $file['done'] = $file['done'] + count($itemList);
        }
        // Composite aggregation has to run till we get an empty response.
        $data = json_decode($response, TRUE);
        $list = $data['aggregations']['_rows']['buckets'];
        $done = count($list) === 0;
        if (empty($data['aggregations']['_rows']['after_key'])) {
          unset($file['after_key']);
        }
        else {
          $file['after_key'] = $data['aggregations']['_rows']['after_key'];
        }
      }
      $file['state'] = $done ? 'done' : 'nextPage';
      $cache = Cache::instance();
      if ($done) {
        $cache->delete("es-paging-$file[uniq_id]", $file);
        unset($file['scroll_id']);
        $this->zip($file);
      }
      else {
        $cache->set("es-paging-$file[uniq_id]", $file);
      }
      $file['filename'] = url::base() . 'download/' . $file['filename'];
      header('Content-type: application/json');
      // Allow for a JSONP cross-site request.
      if (array_key_exists('callback', $_GET)) {
        echo $_GET['callback'] . "(" . json_encode($file) . ")";
      }
      else {
        echo json_encode($file);
      }
    }
  }

  /**
   * Zip the CSV file ready for download.
   *
   * @param array $file
   *   File details. The filename will be modified to reflect the zip file name.
   */
  private function zip(array &$file) {
    $zip = new ZipArchive();
    $zipFile = DOCROOT . 'download/' . basename($file['filename'], '.csv') . '.zip';
    if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
      throw new exception("Cannot create zip file $zipFile.");
    }
    $zip->addFile(DOCROOT . 'download/' . $file['filename'], $file['filename']);
    $zip->close();
    unlink(DOCROOT . 'download/' . $file['filename']);
    $file['filename'] = basename($file['filename'], '.csv') . '.zip';
  }

  /**
   * Converts an Elasticsearch response to a chunk of CSV data.
   *
   * @param string $itemList
   *   Decoded list of data from an Elasticsearch search.
   * @param int $handle
   *   File or output buffer handle.
   */
  private function esToCsv($itemList, $handle) {
    if (empty($itemList)) {
      return;
    }
    $esCsvTemplate = $this->getEsCsvTemplate();
    foreach ($itemList as $item) {
      $row = [];
      foreach ($esCsvTemplate as $source) {
        $this->copyIntoCsvRow($item, $source['field'], $row);
      }
      fputcsv($handle, $row);
    };
  }

  /**
   * Special field handler returns an empty string. This is useful
   * where the output CSV must contain a column to which no
   * useful data can be mapped.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Empty string.
   */
  private function esGetSpecialFieldBlank(array $doc) {
    return '';
  }

  /**
   * Special field handler for Elasticsearch to combine
   * the sample and occurrence comment.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Combined comment string.
   */
  private function esGetSpecialFieldSampleOccurrenceComment(array $doc) {
    $oComment = isset($doc['occurrence']['occurrence_remarks']) ? $doc['occurrence']['occurrence_remarks'] : '';
    $sComment = isset($doc['event']['event_remarks']) ? $doc['event']['event_remarks'] : '';
    if ($oComment !== '' && $sComment !== '') {
      return "Record comment: $oComment Sample comment: $sComment";
    }
    elseif ($oComment !== '') {
      return "Record comment: $oComment";
    }
    elseif ($sComment !== '') {
      return "Sample comment: $sComment";
    }
    else {
      return '';
    }
  }

  /**
   * Special field handler ES datetime fields to output with provided format.
   *
   * Return the datetime as a string formatted as specified.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters: 
   *   1. ES field
   *   2. datetime format
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldDatetime(array $doc, array $params) {
    if (count($params) !== 2) {
      return 'Incorrect params for Datetime field';
    }
    $dtvalue = $this->getRawEsFieldValue($doc, $params[0]);
    $dt = DateTime::createFromFormat('Y-m-d G:i:s.u', $dtvalue);
    if ($dt === FALSE) {
      return  $dtvalue;
    } else {
      return $dt->format($params[1]);
    }
  }

  /**
   * Special field handler for ES spatial ref system fields.
   *
   * Return the spatial ref system formatted as specified.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters: 
   *   1. ES field
   *   2. format identifier
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldSrefSystem(array $doc, array $params) {
    if (count($params) !== 2) {
      return 'Incorrect params for sref system field';
    }
    $value = strval($this->getRawEsFieldValue($doc, $params[0]));
    if ($params[1] === 'alphanumeric') {
      // Ensure that EPSG codes are converted to alphanumeric string.
      // Provides backward compatibility with pre-ES downloads.
      if ($value === '4326') {
        return 'WGS84';
      }
      else if ($value === '27700') {
        return 'OSGB36';
      }
      else {
        return strtoupper($value);
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler for ES verification status.
   *
   * Return the verification status formatted as specified.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldVerificationStatus(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for verification status field';
    }
    $value = $this->getRawEsFieldValue($doc, 'identification.verification_status');
    if ($params[0] === 'astext') {
      // Provides backward compatibility with pre-ES downloads.
      if($value === 'V'){
        return 'Accepted';
      }
      elseif ($value === 'C'){
        return 'Unconfirmed';
      }
      elseif ($value === 'R'){
        return 'Rejected';
      }
      elseif ($value === 'I'){
        return 'Input still in progress';
      }
      elseif ($value === 'D'){
        return 'Queried';
      }
      elseif ($value === 'S'){
        return 'Awaiting check';
      }
      else {
        return $value;
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler for ES verification status.
   *
   * Return the verification status formatted as specified.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldVerificationSubstatus(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for verification substatus field';
    }
    $status = $this->getRawEsFieldValue($doc, 'identification.verification_status');
    $value = $this->getRawEsFieldValue($doc, 'identification.verification_substatus');
    if ($params[0] === 'astext') {
      // Provides backward compatibility with pre-ES downloads.
      if($status === 'V'){
        if ($value === '1') {
          return 'Correct';
        }
        elseif ($value === '2') {
          return 'Considered correct';
        }
        else {
          return NULL;
        }
      }
      elseif ($status === 'C'){
        if ($value === '3') {
          return 'Plausible';
        }
        else {
          return 'Not reviewed';
        }
      }
      elseif ($status === 'R'){
        if ($value === '4') {
          return 'Unable to verify';
        }
        elseif ($value === '5') {
          return 'Incorrect';
        }
        else {
          return NULL;
        }
      }
      else {
        return NULL;
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler for ES identification query status.
   *
   * Return the identification query status formatted as specified.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldQuery(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for query field';
    }
    $value = $this->getRawEsFieldValue($doc, 'identification.query');
    if ($params[0] === 'astext') {
      // Provides backward compatibility with pre-ES downloads.
      if($value === 'A'){
        return 'Answered';
      }
      elseif ($value === 'Q'){
        return 'Queried';
      }
      else {
        return $value;
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler for the associations data.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldAssociations(array $doc) {
    $output = [];
    if (isset($doc['occurrence']['associations'])) {
      foreach ($doc['occurrence']['associations'] as $assoc) {
        $label = $assoc->accepted_name;
        if (!empty($assoc->vernacular_name)) {
          $label = $assoc->vernacular_name + " ($label)";
        }
      }
    }
    return implode('; ', $output);
  }

  /**
   * Special field handler for Elasticsearch custom attribute values.
   *
   * Concatenates values to a semi-colon separated string. The parameters
   * should be:
   * * 0 - the entity (event|occurrence)
   * * 1 - the attribute ID.
   * Multiple attribute values are returned joined by semi-colons.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldAttrValue(array $doc, array $params) {
    $r = [];
    if (in_array($params[0], ['occurrence', 'sample', 'event', 'parent_event'])) {
      // Tolerate sample or event for sample attributes.
      $key = $params[0] === 'parent_event' ? 'parent_attributes' : 'attributes';
      $entity = in_array($params[0], ['sample', 'event', 'parent_event']) ? 'event' : 'occurrence';
      if (isset($doc[$entity][$key])) {
        foreach ($doc[$entity][$key] as $attr) {
          if ($attr['id'] == $params[1]) {
            $r[] = $attr['value'];
          }
        }
      }
    }
    return implode('; ', $r);
  }

  /**
   * Special field handler for the datacleaner icons field.
   *
   * Text representation of icons for download.
   */
  private function esGetSpecialFieldDataCleanerIcons(array $doc) {
    $autoChecks = $doc['identification']['auto_checks'];
    $output = [];
    if ($autoChecks['enabled'] === 'false') {
      $output[] = 'Automatic rule checks will not be applied to records in this dataset.';
    }
    elseif (isset($autoChecks['result'])) {
      if ($autoChecks['result'] === 'true') {
        $output[] = 'All automatic rule checks passed.';
      }
      elseif ($autoChecks['result'] === 'false') {
        if (count($autoChecks['output']) > 0) {
          // Add an icon for each rule violation.
          foreach($autoChecks['output'] as $violation) {
            $output[] = $violation['message'];
          }
        }
        else {
          $output[] = 'Automatic rule checks flagged issues with this record';
        }
      }
    } else {
      // Not yet checked.
      $output[] = 'Record not yet checked against rules.';
    }
    return implode('; ', $output);
  }

  /**
   * Special field handler for datasource codes.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value including website, survey dataset and,
   *   optionally, group info.
   */
  private function esGetSpecialFieldDatasourceCode(array $doc, $params) {
    if (count($params) > 1) {
      return 'Incorrect params for datasource code field (must be 0 or 1)';
    }
    $w = $doc['metadata']['website'];
    $s = $doc['metadata']['survey'];
    if (isset($doc['metadata']['group'])) {
      $g = $doc['metadata']['group'];
    }
    else {
      $g = array('title' => '', 'id' => '');
    }
    if (count($params)) {
      $pattern = $params[0];
    }
    else {
      $pattern = '<wi> (<wt>) | <si> (<st>)';
    }
    $regpatterns = array('/<wi>/', '/<wt>/', '/<si>/' , '/<st>/', '/<gi>/', '/<gt>/');
    $replacements = array($w['id'], $w['title'], $s['id'], $s['title'], $g['id'] , $g['title']);
    $output = preg_replace($regpatterns, $replacements, $pattern);
    // Disregarding whitespace, if the output string ends in something in curly braces,
    // then remove it. Allows us to conditionally remove a separator if there's no group.
    $output = preg_replace('/\s*{.*}\s*$/', '', $output);
    // Remvove curly braces from output.
    $output = preg_replace('/({|})/', '', $output);
    return $output;
  }

  /**
   * Special field handler for Elasticsearch event dates.
   *
   * Converts event.date_from and event.date_to to a readable date string, e.g.
   * for inclusion in CSV output. Also handles date fields when prefixed by
   * `key`, e.g. when used in composite aggregation sources.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters in field definition.
   *   Can be empty or a string to specify a format.
   *
   * @return string
   *   Formatted readable date.
   */
  private function esGetSpecialFieldEventDate(array $doc, array $params) {
    if (count($params) > 1) {
      return 'Incorrect params for formatted date (must be zero or one)';
    }
    if (count($params)) {
      $format = $params[0];
    } 
    else {
      $format = "";
    }
    // Check in case fields are in composite agg key.
    $root = isset($doc['key']) ? $doc['key'] : $doc['event'];
    $start = isset($root['date_start']) ? $root['date_start'] :
      (isset($root['event-date_start']) ? $root['event-date_start'] : '');
    $end = isset($root['date_end']) ? $root['date_end'] :
      (isset($root['event-date_end']) ? $root['event-date_end'] : '');
    if (preg_match('/^\-?\d+$/', $start)) {
      $start = date('d/m/Y', $start / 1000);
    }
    if (preg_match('/^\-?\d+$/', $end)) {
      $end = date('d/m/Y', $end / 1000);
    }
    if (preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $start, $matches)) {
      $start = $matches[3] . '/' . $matches[2] . '/' . $matches[1];
    }
    if (preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $end, $matches)) {
      $end = $matches[3] . '/' . $matches[2] . '/' . $matches[1];
    }
    if (empty($start) && empty($end)) {
      if ($format === 'mapmate') {
        return '';
      } else {
        return 'Unknown';
      }
    }
    elseif (empty($end)) {
      if ($format === 'mapmate') {
        // Mapmate can't deal with unbound ranges
        // - replace with date of known bound.
        return $start;
      } else {
        return "After $start";
      }
    }
    elseif (empty($start)) {
      if ($format === 'mapmate') {
        // Mapmate can't deal with unbound ranges
        // - replace with date of known bound.
        return $end;
      } else {
        return "Before $end";
      }
    }
    elseif ($start === $end) {
      return $start;
    }
    else {
      if ($format === 'mapmate') {
        return $start . '-' . $end;
      } else {
        return "$start to $end";
      }
    }
  }

  /**
   * Special field handler for Elasticsearch higher geography.
   *
   * Converts location.higher_geography to a string, e.g. for inclusion in CSV
   * output. Configurable output by passing parameters:
   * * type - limit output to this type.
   * * field - limit output to content of this field (name, id, type or code).
   * * format - can be left empty or set to either json or mapmate.
   * E.g. pass type=Country, field=name, text=true to convert to a plaintext
   * Country name.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters in field definition.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldHigherGeography(array $doc, array $params) {
    if (isset($doc['location']) && isset($doc['location']['higher_geography'])) {
      if (empty($params) || empty($params[0])) {
        $r = $doc['location']['higher_geography'];
      }
      else {
        $r = [];
        foreach ($doc['location']['higher_geography'] as $loc) {
          if (strcasecmp($loc['type'], $params[0]) === 0) {
            if (!empty($params[1])) {
              $r[] = $loc[$params[1]];
            }
            else {
              $r[] = $loc;
            }
          }
        }
      }
      if (isset($params[2]) && $params[2] === 'json') {
        return json_encode($r);
      }
      else {
        $outputList = [];
        foreach ($r as $outputItem) {
          $outputList[] = is_array($outputItem) ? implode('; ', $outputItem) : $outputItem;
        }
        if (isset($params[2]) && $params[2] === 'mapmate') {
          if (count($outputList) === 1) {
            return $outputList[0];
          }
          else {
            return 0;
          }
        }
        else {
          return implode(' | ', $outputList);
        }
      }
    }
    else {
      if (isset($params[2]) && $params[2] === 'mapmate') {
        return 0;
      }
      else {
        return '';
      }
    }
  }

  /**
   * Special field handler for Elasticsearch sex with format options.
   *
   * Converts occurrence.sex to values as specified in format option.
   * 
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted sex.
   */
  private function esGetSpecialFieldSex(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for formatted sex';
    }
    $value = isset($doc['occurrence']['sex']) ? strtolower($doc['occurrence']['sex']) : '';
    if ($params[0] === 'mapmate') {
      // Provides compatibility for import to MapMate
      switch($value) {
        case 'female':
          return 'f';

        case 'male':
          return 'm';

        case 'mixed':
          return 'g';

        case 'queen':
          return 'q';

        case 'not recorded':
        case 'not known':
        case 'unknown':
        case 'unsexed:':
          return 'u';

        default:
          return $value;
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler for Elasticsearch life stage with format options.
   *
   * Converts occurrence.life_stage to values as specified in format option.
   * 
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted life stage.
   */
  private function esGetSpecialFieldLifeStage(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for formatted life stage';
    }
    $value = isset($doc['occurrence']['life_stage']) ? strtolower($doc['occurrence']['life_stage']) : '';
    if ($params[0] === 'mapmate') {
      // Provides compatibility for import to MapMate
      switch($value) {
        case 'adult':
        case 'adults':
        case 'adult female':
        case 'adult male':
          return 'Adult';

        case 'larva':
          return 'Larval';

        case 'not recorded':
          return 'Not recorded';

        case 'pre-adult':
          return 'Subadult';

        case 'In flower':
          return 'Flowering';

        default:
          return $value;
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler for Elasticsearch organism quantity.
   *
   * Allows organism quanities to be filtered/formatted as directed by params.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters in field definition.
   *
   * @return string
   *   A quantity formatted/filtered as indicated by passed parameter.
   */
  private function esGetSpecialFieldOrganismQuantity(array $doc, array $params) {
    $format = !empty($params) ? $params[0] : '';
    $quantity = !empty($doc['occurrence']['organism_quantity']) ? $doc['occurrence']['organism_quantity'] : '';
    if (!empty($doc['occurrence']['zero_abundance']) && $doc['occurrence']['zero_abundance'] !== 'false') {
      $zero = True;
    } 
    else {
      $zero = False;
    }
    switch($format) {
      case 'mapmate':
        // Mapmate will only accept integer values and uses a value 
        // of -7 to indicate a negative record. MapMate interprets
        // a quantity of 0 to mean 'present'.
        if ($zero || $quantity === '0') {
          return -7;
        }
        elseif(preg_match('/^\d+$/', $quantity)) {
          return (int)$quantity;
        }
        else {
          return '';
        }

      case 'integer':
        // Only return the value if it is an integer.
        if(preg_match('/^\d+$/', $quantity)) {
          return $quantity;
        }
        else {
          return '';
        }

      case 'exclude_integer':
        // Only return the value if it is not an integer.
        if(!preg_match('/^\d+$/', $quantity)) {
          return $quantity;
        }
        else {
          return '';
        }

      default:
        return $quantity;
    }
  }

  /**
   * Special field handler for latitude data.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldLat(array $doc, array $params) {
    // Check in case fields are in composite agg key.
    $root = isset($doc['key']) ? $doc['key'] : $doc['location'];
    if (empty($root['point'])) {
      return 'n/a';
    }
    $coords = explode(',', $root['point']);
    $format = !empty($params) ? $params[0] : '';
    switch($format) {
      case 'decimal':
        return $coords[0];

      case 'nssuffix': // Implemented as the default.
      default:
        $ns = $coords[0] >= 0 ? 'N' : 'S';
        $lat = number_format(abs($coords[0]), 3);
        return "$lat$ns";
    }
  }

  /**
   * Special field handler for lat/lon data.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldLatLon(array $doc) {
    // Check in case fields are in composite agg key.
    $root = isset($doc['key']) ? $doc['key'] : $doc['location'];
    if (empty($root['point'])) {
      return 'n/a';
    }
    $coords = explode(',', $root['point']);
    $ns = $coords[0] >= 0 ? 'N' : 'S';
    $ew = $coords[1] >= 0 ? 'E' : 'W';
    $lat = number_format(abs($coords[0]), 3);
    $lon = number_format(abs($coords[1]), 3);
    return "$lat$ns $lon$ew";
  }

  /**
   * Special field handler for longitude data.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldLon(array $doc, array $params) {
    // Check in case fields are in composite agg key.
    $root = isset($doc['key']) ? $doc['key'] : $doc['location'];
    if (empty($root['point'])) {
      return 'n/a';
    }
    $coords = explode(',', $root['point']);
    $format = !empty($params) ? $params[0] : "";
    switch($format) {
      case "decimal":
        return $coords[1];

      case "ewsuffix": // Implemented as the default.
      default:
        $ew = $coords[1] >= 0 ? 'E' : 'W';
        $lon = number_format(abs($coords[1]), 3);
        return "$lon$ew";
    }
  }

  /**
   * Special field handler for locality data.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value containing a list of location names associated with the
   *   record.
   */
  private function esGetSpecialFieldLocality(array $doc) {
    $info = [];
    if (!empty($doc['location']['verbatim_locality'])) {
      $info[] = $doc['location']['verbatim_locality'];
      if (!empty($doc['location']['higher_geography'])) {
        foreach ($doc['location']['higher_geography'] as $loc) {
          $info[] = "$loc[type]: $loc[name]";
        }
      }
    }
    return implode('; ', $info);
  }

  /**
   * Special field handler for occurrence media data.
   *
   * Concatenates media to a string.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldOccurrenceMedia(array $doc) {
    if (!empty($doc['occurrence']['media'])) {
      $items = [];
      foreach ($doc['occurrence']['media'] as $m) {
        $item = [
          $m['path'],
          empty($m['caption']) ? '' : $m['caption'],
          empty($m['licence']) ? '' : $m['licence'],
        ];
        $items[] = implode('; ', $item);
      }
      return implode(' | ', $items);
    }
    return '';
  }

  /**
   * Special field handler for status data.
   *
   * Returns text instead of icons (for download purposes).
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldStatusIcons(array $doc) {
    $terms = [];
    $recordStatusLookup = [
      'V' => 'Accepted',
      'V1' => 'Accepted as correct',
      'V2' => 'Accepted as considered correct',
      'C' => 'Not reviewed',
      'C3' => 'Plausible',
      'R' => 'Not accepted',
      'R4' => 'Not accepted as unable to verify',
      'R5' => 'Not accepted as incorrect',
    ];
    if (!empty($doc['identification'])) {
      $status = $doc['identification']['verification_status'];
      if (!empty($doc['identification']['verification_substatus']) && $doc['identification']['verification_substatus'] !== 0) {
        $status .= $doc['identification']['verification_substatus'];
      }
      if (isset($recordStatusLookup[$status])) {
        $terms[] = $recordStatusLookup[$status];
      }
      if (!empty($doc['identification']['query'])) {
        $terms[] = $doc['identification']['query'] === 'A' ? 'Answered' : 'Queried';
      }
    }
    if (!empty($doc['metadata'])) {
      if (!empty($doc['metadata']['sensitive']) && $doc['metadata']['sensitive'] !== 'false') {
        $terms[] = 'Sensitive';
      }
      if (!empty($doc['metadata']['confidential']) && $doc['metadata']['confidential'] !== 'false') {
        $terms[] = 'Confidential';
      }
      if (!empty($doc['metadata']['created_by_id']) && $doc['metadata']['created_by_id'] === '1') {
        $terms[] = 'Anonymous user';
      }
    }
    if (!empty($doc['occurrence'])) {
      if (!empty($doc['occurrence']['zero_abundance']) && $doc['occurrence']['zero_abundance'] !== 'false') {
        $terms[] = 'Zero abundance';
      }
    }
    return implode('; ', $terms);
  }

  /**
   * Special field handler for ES fields that should treat zero as null.
   *
   * If the field value (fieldname in params) is zero, then return null, else
   * return the original value.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldNullIfZero(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for Null If Zero field';
    }
    $value = $this->getRawEsFieldValue($doc, $params[0]);
    return ($value === '0' || $value === 0) ? NULL : $value;
  }

  /**
   * Function to find the value stored in an ES doc for a field.
   *
   * @param array $doc
   *   Document extracted from Elasticsearch.
   * @param string $source
   *   Fieldname, with nested segments separated by a period, e.g.
   *   identification.verification_status.
   *
   * @return mixed
   *   Data value or empty string if not found.
   */
  private function getRawEsFieldValue(array $doc, $source) {
    $search = explode('.', $source);
    $data = $doc;
    $failed = FALSE;
    foreach ($search as $field) {
      if (isset($data[$field])) {
        $data = $data[$field];
      }
      else {
        $failed = TRUE;
        break;
      }
    }
    if (isset($data['value_as_string'])) {
      // A formatted aggregation response stored in value property.
      return $data['value_as_string'];
    }
    elseif (isset($data['value'])) {
      // An aggregation response stored in value property.
      return $data['value'];
    }
    return $failed ? '' : $data;
  }

  /**
   * Copies a source field from an Elasticsearch document into a CSV row.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param string $sourceField
   *   Source field name or special field name.
   * @param array $row
   *   Output row array, will be update with the value to output.
   */
  private function copyIntoCsvRow(array $doc, $sourceField, array &$row) {
    // Fields starting '_' are special fields in the root of the doc. Others
    // are in the _source element.
    $docSource = strpos($sourceField, '_') === 0 || !isset($doc['_source']) ? $doc : $doc['_source'];
    if (preg_match('/^#(?P<sourceType>[a-z_]*):?(?<params>.*)?#$/', $sourceField, $matches)) {
      $fn = 'esGetSpecialField' .
        str_replace('_', '', ucwords($matches['sourceType']));

      // Split $matches['params'] into an array using colon as a separator.
      // First replace escaped colons ('\:') with another marker ('EscapedColon')
      // converting back to colons in the resulting array elements.
      $params = empty($matches['params']) ? [] : explode(':', str_replace('\:', 'EscapedColon', $matches['params']));
      foreach ($params as &$param) {
        $param = str_replace('EscapedColon', ':', $param);
      }
      if ($matches['sourceType'] === 'id') {
        // Resets docSource to root if special function to format doc ID.
        $docSource = $doc;
      }
      if (method_exists($this, $fn)) {
        $row[] = $this->$fn($docSource, $params);
      }
      else {
        $row[] = "Invalid field $sourceField";
      }
    }
    else {
      if (!preg_match('/^[a-z0-9_\-]+(\.[a-z0-9_\-]+)*$/', $sourceField)) {
        $row[] = "Invalid field $sourceField";
      }
      else {
        $row[] = $this->getRawEsFieldValue($docSource, $sourceField);
      }
    }
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
        RestObjects::$apiResponse->fail('Bad request', 400,
          "Elasticsearch request $resource ($_SERVER[REQUEST_METHOD]) disallowed by Warehouse REST API proxy configuration.");
      }
    }
    $url = "$thisProxyCfg[url]/$thisProxyCfg[index]/$resource";
    $this->proxyToEs($url);
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
      RestObjects::$apiResponse->fail('No Content', 204);
    }
    RestObjects::$apiResponse->succeed($this->projects[$id], array(
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
    RestObjects::$apiResponse->succeed([
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
   * GET handler for the taxon-observations resource.
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
      RestObjects::$apiResponse->fail('No Content', 204);
    }
    elseif (count($report['content']['records']) > 1) {
      kohana::log('error', 'Internal error. Request for single record returned multiple');
      RestObjects::$apiResponse->fail('Internal Server Error', 500);
    }
    else {
      RestObjects::$apiResponse->succeed(
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
    RestObjects::$apiResponse->succeed(
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
      RestObjects::$apiResponse->fail('No Content', 204);
    }
    elseif (count($report['content']['records']) > 1) {
      kohana::log('error', 'Internal error. Request for single annotation returned multiple');
      RestObjects::$apiResponse->fail('Internal Server Error', 500);
    }
    else {
      $record = $report['content']['records'][0];
      $record['taxonObservation'] = array(
        'id' => $record['taxon_observation_id'],
        // @todo href
      );
      RestObjects::$apiResponse->succeed($record, array(
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
    RestObjects::$apiResponse->succeed(
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
  private function taxaGetSearch() {
    $params = array_merge(array(
      'limit' => REST_API_DEFAULT_PAGE_SIZE,
      'include' => ['data', 'count', 'paging', 'columns'],
    ), $this->request);
    try {
      $params['count'] = FALSE;
      $query = postgreSQL::taxonSearchQuery($params);
    }
    catch (Exception $e) {
      RestObjects::$apiResponse->fail('Bad request', 400, $e->getMessage());
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
    RestObjects::$apiResponse->succeed(
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
  private function reportsGet($featured = FALSE) {
    RestObjects::$apiResponse->trackTime();
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
      $this->getReportHierarchy($segments, $featured);
    }
  }

  private function reportsGetFeatured() {
    $this->reportsGet(TRUE);
  }

  private function reportsGetPath() {
    $this->reportsGet();
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
        RestObjects::$apiResponse->fail('Service still processing prior request for feed.', 503, "Service unavailable");
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
          RestObjects::$apiResponse->succeed([
            'data' => $report['content']['records'],
          ], [], TRUE);
        }
        else {
          $pagination = $this->getPagination($report['count']);
          RestObjects::$apiResponse->succeed(
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
        RestObjects::$apiResponse->fail('Bad request (parameters missing)', 400,
          "Missing parameters: " . implode(', ', array_keys($report['content']['parameterRequest'])));
      }
      else {
        kohana::log('error', 'Rest API getReportOutput method retrieved invalid report response: ' .
          var_export($report, true));
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
    RestObjects::$apiResponse->includeEmptyValues = FALSE;
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
    RestObjects::$apiResponse->responseTitle = ucfirst("$item for $reportFile");
    RestObjects::$apiResponse->wantIndex = TRUE;
    RestObjects::$apiResponse->succeed(array('data' => $list), array('metadata' => array('description' => $description)));
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
  private function getReportHierarchy(array $segments, $featured) {
    $this->loadReportEngine();
    // @todo Cache this
    $reportHierarchy = $this->reportEngine->reportList();
    $response = array();
    $folderReadme = '';
    if ($featured) {
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
    if ($featured) {
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
          $metadata['href'] = RestObjects::$apiResponse->getUrlWithCurrentParams("reports/$path");
        }
        $response[$key] = $metadata;
      }
    }
    // Build a description. A generic statement about the path, plus anything
    // included in the folder's readme file.
    $relativePath = '/reports/' . ($relativePath ? "$relativePath/" : '');
    $description = 'A list of reports and report folders stored on the warehouse under ' .
      "the folder <em>$relativePath</em>. $folderReadme";
    RestObjects::$apiResponse->succeed($response, array('metadata' => array('description' => $description)));
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
    $metadata['href'] = RestObjects::$apiResponse->getUrlWithCurrentParams("reports/$metadata[path].xml");
    $metadata['params'] = [
      'href' => RestObjects::$apiResponse->getUrlWithCurrentParams("reports/$metadata[path].xml/params"),
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
      'href' => RestObjects::$apiResponse->getUrlWithCurrentParams("reports/$metadata[path].xml/columns"),
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
      RestObjects::$apiResponse->fail('Bad request', 400, "Invalid integer format for $paramName parameter");
    }
    elseif ($datatype === 'date') {
      if (strpos($value, 'T') === FALSE) {
        $dt = DateTime::createFromFormat("Y-m-d", $trimmed);
      }
      else {
        $dt = DateTime::createFromFormat("Y-m-d\TH:i:s", $trimmed);
      }
      if ($dt === FALSE || array_sum($dt->getLastErrors())) {
        RestObjects::$apiResponse->fail('Bad request', 400, "Invalid date for $paramName parameter");
      }
    }
    elseif ($datatype === 'boolean') {
      if (!preg_match('/^(true|false)$/', $trimmed)) {
        RestObjects::$apiResponse->fail('Bad request', 400,
            "Invalid boolean for $paramName parameter, value should be true or false");
      }
      // Set the value to a real bool.
      $value = $trimmed === 'true';
    }
    // If a limited options set available then check the value is in the list.
    if (!empty($paramDef['options']) && !in_array($trimmed, $paramDef['options'])) {
      RestObjects::$apiResponse->fail('Bad request', 400, "Invalid value for $paramName parameter");
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
  private function validateParameters($methodConfig) {
    // Check through the known list of parameters to ensure data formats are
    // correct and required parameters are provided.
    if (isset($methodConfig['params'])) {
      foreach ($methodConfig['params'] as $paramName => $paramDef) {
        if (!empty($paramDef['required']) && empty($this->request[$paramName])) {
          RestObjects::$apiResponse->fail('Bad request', 400, "Missing $paramName parameter");
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
      $this->reportEngine = new ReportEngine(array(RestObjects::$clientWebsiteId));
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
    if ($this->getAutofeedMode()) {
      // Fudge to prevent the overhead of a count query.
      $_REQUEST['wantCount'] = '0';
      // Set max number of records to process.
      $params['limit'] = AUTOFEED_DEFAULT_PAGE_SIZE;
      // Find our state data for this feed.
      $afSettings = (array) variable::get("rest-autofeed-$_GET[proj_id]", ['mode' => 'notStarted'], FALSE);
      if ($afSettings['mode'] === 'notStarted') {
        // First use of this autofeed, so we need to store the tracking point to
        // ensure we capture all changes after the initial sweep up of records
        // is done. Switch state to initial loading.
        $lastTrackingInfo = RestObjects::$db
          ->query('SELECT max(tracking) as max_tracking FROM cache_occurrences_functional')
          ->current();
        $afSettings = [
          'mode' => 'initialLoad',
          'last_tracking_id' => $lastTrackingInfo->max_tracking,
          'last_tracking_date' => date('c'),
          'last_id' => 0,
        ];
        $params['last_id'] = 0;
        variable::set("rest-autofeed-$_GET[proj_id]", $afSettings);
      }
      elseif ($afSettings['mode'] === 'initialLoad') {
        // Part way through initial loading. Use the last loaded ID as a start
        // point for next block of records.
        $params['last_id'] = $afSettings['last_id'];
      }
      elseif ($afSettings['mode'] === 'updates') {
        // Doing updates of changes only as initial load done.
        // Start at one record after the last one we retrieved, or use the
        // tracking date if the report does not support a tracking ID field.
        // Pass appropriate parameters depending on whether the report is
        // tracked on tracking ID or a date field.
        if (isset($afSettings['last_tracking_id'])) {
          $params['autofeed_tracking_from'] = $afSettings['last_tracking_id'] + 1;

        }
        if (isset($afSettings['last_tracking_date'])) {
          $params['autofeed_tracking_date_from'] = $afSettings['last_tracking_date'];
        }
        $params['orderby'] = 'tracking';
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
    $filter = [];
    // @todo Apply permissions for user or website & write tests
    // load the filter associated with the project ID
    if (isset($this->clientSystemId)) {
      $filter = $this->loadFilterForProject($this->request['proj_id']);
    }
    elseif (isset(RestObjects::$clientUserId)) {
      // When authenticating a user, you can use one of the permissions filters
      // for the user to gain access to a wider pool of records, e.g. for a
      // verifier to access all records they have rights to.
      if (!empty($_GET['filter_id'])) {
        $filter = $this->getPermissionsFilterDefinition();
      }
      elseif (!empty($this->resourceOptions['limit_to_own_data'])) {
        // Default filter - the user's records for this website only.
        $filter = array(
          'website_list' => RestObjects::$clientWebsiteId,
          'created_by_id' => RestObjects::$clientUserId,
        );
      }
    }
    else {
      if (!isset(RestObjects::$clientWebsiteId)) {
        RestObjects::$apiResponse->fail('Internal server error', 500, 'Minimal filter on website ID not provided.');
      }
      $filter = array(
        'website_list' => RestObjects::$clientWebsiteId,
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
    try {
      $output = $this->reportEngine->requestReport("$report.xml", 'local', 'xml',
        $params, !empty($this->resourceOptions['cached']));
    }
    catch (Exception $e) {
      $code = (substr($e->getMessage(), 0, 21) === 'Unable to find report') ? 404 : 500;
      $status = ($code === 404) ? 'Not Found' : 'Internal Server Error';
      RestObjects::$apiResponse->fail($status, $code, $e->getMessage());
    }
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
      RestObjects::$apiResponse->fail('Bad request', 400, "Invalid project requested");
    }
    if (isset($this->projects[$id]['filter_id'])) {
      $filterId = $this->projects[$id]['filter_id'];
      $filters = RestObjects::$db->select('definition')->from('filters')->where(array('id' => $filterId, 'deleted' => 'f'))
        ->get()->result_array();
      if (count($filters) !== 1) {
        RestObjects::$apiResponse->fail('Internal Server Error', 500, 'Failed to find unique project filter record');
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
    $filters = RestObjects::$db->select('definition')
      ->from('filters')
      ->join('filters_users', array(
        'filters_users.filter_id' => 'filters.id',
      ))
      ->where(array(
        'filters.id' => $_GET['filter_id'],
        'filters.deleted' => 'f',
        'filters.defines_permissions' => 't',
        'filters_users.user_id' => RestObjects::$clientUserId,
        'filters_users.deleted' => 'f',
      ))
      ->get()->result_array();
    if (count($filters) !== 1) {
      RestObjects::$apiResponse->fail('Bad request', 400, 'Filter ID missing or not a permissions filter for the user');
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
        RestObjects::$apiResponse->fail('Bad request', 400, 'Unsupported API version');
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
   * If allow_cors set in the auth method options, apply access control header.
   */
  private function applyCorsHeader() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS' ||
        (isset($this->authConfig) && array_key_exists('allow_cors', $this->authConfig))) {
      if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS' || $this->authConfig['allow_cors'] === TRUE) {
        $corsSetting = '*';
      } elseif (is_array($this->authConfig['allow_cors'])) {
        // If list of domain patterns specified, allow only if a match.
        foreach ($this->authConfig['allow_cors'] as $domainRegex) {
          if (preg_match("/$domainRegex/", $_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            break;
          }
        }
      }
      if ($corsSetting) {
        header("Access-Control-Allow-Origin: $corsSetting");
        header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
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
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      // No need to authenticate OPTIONS request.
      return;
    }
    $this->checkElasticsearchRequest();
    if ($this->authenticated) {
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
          $this->authMethod = $method;
          // Double checking required for Elasticsearch proxy.
          if ($this->elasticProxy) {
            if (empty($cfg['resource_options']['elasticsearch'])) {
              kohana::log('debug', "Elasticsearch request to $this->elasticProxy not enabled for $method");
              RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unable to authorise');
            }
            if (in_array($this->elasticProxy, $cfg['resource_options']['elasticsearch'])) {
              // Simple array of ES endpoints with no config.
              $this->esConfig = [];
            }
            elseif (array_key_exists($this->elasticProxy, $cfg['resource_options']['elasticsearch'])) {
              // Endpoints are keys with array values holding config.
              $this->esConfig = $cfg['resource_options']['elasticsearch'][$this->elasticProxy];
            }
            else {
              kohana::log('debug', "Elasticsearch request to $this->elasticProxy not enabled for $method");
              RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unable to authorise');
            }
            if (!empty($this->clientConfig) && (empty($this->clientConfig['elasticsearch']) ||
                !in_array($this->elasticProxy, $this->clientConfig['elasticsearch']))) {
              kohana::log('debug', "Elasticsearch request to $this->elasticProxy not enabled for client");
              RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unable to authorise');
            }
          }
          kohana::log('debug', "authenticated via $method");
          $this->authConfig = $cfg;
          break;
        }
      }
    }
    if (!$this->authenticated) {
      // Either the authentication wrong, or using HTTP instead of HTTPS.
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unable to authorise');
    }
  }

  /**
   * Retrieves the authorization header (case insensitive).
   *
   * @return string
   *   Authorization header or empty string if not present.
   */
  private function getAuthHeader() {
    $headers = array_change_key_case(apache_request_headers());
    if (array_key_exists('authorization', $headers)) {
      return $headers['authorization'];
    }
    return '';
  }

  /**
   * Retrieves the Bearer access token from the Authoriztion header.
   *
   * @param bool $wantJwt
   *   Set to TRUE to retrieve JWT format or FALSE for oAuth.
   *
   * @return string
   *   Auth token or empty string.
   */
  private function getBearerAuthToken($wantJwt = FALSE) {
    $authHeader = $this->getAuthHeader();
    if (stripos($authHeader, 'Bearer ') === 0) {
      $token = substr($authHeader, 7);
      $isJwt = substr_count($token, '.') === 2;
      if ($isJwt === $wantJwt) {
        return $token;
      }
    }
    return '';
  }

  /**
   * Attempts to authenticate using the oAuth2 protocal.
   */
  private function authenticateUsingOauth2User() {
    $suppliedToken = $this->getBearerAuthToken();
    if ($suppliedToken) {
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
            RestObjects::$clientWebsiteId = $matches['website_id'];
            RestObjects::$clientUserId = $matches['user_id'];
            // Pass through to ORM.
            global $remoteUserId;
            $remoteUserId = RestObjects::$clientUserId;
            $this->authenticated = TRUE;
          }
        }
      }
    }
  }

  /**
   * Finds the ID and public key for a website using the URL.
   *
   * Allows the iss in a JWT payload to be mapped to the relevant website
   * details.
   */
  private function getWebsiteByUrl($url) {
    $cache = Cache::instance();
    $cacheKey = 'website-by-url-' . preg_replace('/[^0-9a-zA-Z]/', '', $url);
    $website = $cache->get($cacheKey);
    if (!$website) {
      $db = new Database();
      $website = $db
        ->select('id, public_key')
        ->from('websites')
        ->where('url', $url)
        ->get()->current();
      $cache->set($cacheKey, $website);
    }
    return $website;
  }

  private function checkWebsiteUser($websiteId, $userId) {
    $cache = Cache::instance();
    $cacheKey = "website-user-$websiteId-$userId";
    $websiteUser = $cache->get($cacheKey);
    if (!$websiteUser) {
      $db = new Database();
      $websiteUser = $db
        ->select('site_role_id')
        ->from('users_websites')
        ->where([
          'website_id' => $websiteId,
          'user_id' => $userId,
          'banned' => FALSE,
        ])
        ->get()->current();
      if ($websiteUser) {
        $cache->set($cacheKey, $websiteUser);
      }
    }
    if (!$websiteUser) {
      RestObjects::$apiResponse->fail('Unauthorized', 401);
    }
  }

  /**
   * Attempts to authenticate as a user using a JWT access token.
   */
  private function authenticateUsingJwtUser() {
    require_once 'vendor/autoload.php';
    $suppliedToken = $this->getBearerAuthToken(TRUE);
    if ($suppliedToken) {
      list($jwtHeader, $jwtPayload, $jwtSignature) = explode('.', $suppliedToken);
      $payload = base64_decode($jwtPayload);
      if (!$payload) {
        RestObjects::$apiResponse->fail('Bad request', 400);
      }
      $payloadValues = json_decode($payload, TRUE);
      if (!$payloadValues) {
        RestObjects::$apiResponse->fail('Bad request', 400);
      }
      if (empty($payloadValues['iss']) || empty($payloadValues['http://indicia.org.uk/user:id'])) {
        RestObjects::$apiResponse->fail('Bad request', 400);
      }
      // Check for claim that stops ES filtering to just user's own records.
      if (!empty($payloadValues['http://indicia.org.uk/alldata'])) {
        $this->allowAllData = TRUE;
      }
      $website = $this->getWebsiteByUrl($payloadValues['iss']);
      if (!$website || empty($website->public_key)) {
        kohana::log('debug', 'Website not found or has no public key: ' . $payloadValues['iss']);
        RestObjects::$apiResponse->fail('Unauthorized', 401);
      }
      // Allow for minor clock sync problems.
      JWT\JWT::$leeway = 60;
      try {
        $decoded = JWT\JWT::decode($suppliedToken, $website->public_key, ['RS256']);
      }
      catch (JWT\SignatureInvalidException $e) {
        kohana::log('debug', 'Token decode failed');
        RestObjects::$apiResponse->fail('Unauthorized', 401);
      }
      catch (JWT\ExpiredException $e) {
        kohana::log('debug', 'Token expired');
        RestObjects::$apiResponse->fail('Unauthorized', 401);
      }
      catch (ErrorException $e) {
        if (substr($e->getMessage(), 0, 16) === 'openssl_verify()') {
          kohana::log('debug', 'Public key format incorrect.');
          RestObjects::$apiResponse->fail('Internal Server Error', 500);
        }
        // Fallback.
        throw $e;
      }
      if (isset($payloadValues['email_verified']) && !$payloadValues['email_verified']) {
        kohana::log('debug', 'Payload email unverified');
        RestObjects::$apiResponse->fail('Unauthorized', 401);
      }
      if (!isset($payloadValues['http://indicia.org.uk/user:id'])) {
        RestObjects::$apiResponse->fail('Bad request', 400);
      }
      $this->checkWebsiteUser($website->id, $payloadValues['http://indicia.org.uk/user:id']);
      RestObjects::$clientWebsiteId = $website->id;
      RestObjects::$clientUserId = $payloadValues['http://indicia.org.uk/user:id'];
      global $remoteUserId;
      $remoteUserId = RestObjects::$clientUserId;
      $this->authenticated = TRUE;
    }
  }

  /**
   * Attempts to authenticate using the HMAC client protocal.
   */
  private function authenticateUsingHmacClient() {
    $authHeader = $this->getAuthHeader();
    if (substr_count($authHeader, ':') === 3) {
      list($u, $clientSystemId, $h, $supplied_hmac) = explode(':', $authHeader);
      $config = Kohana::config('rest.clients');
      if ($u === 'USER' && $h === 'HMAC' && array_key_exists($clientSystemId, $config)) {
        $protocol = $this->isHttps ? 'https' : 'http';
        $request_url = "$protocol://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $correct_hmac = hash_hmac("sha1", $request_url, $config[$clientSystemId]['shared_secret'], $raw_output = FALSE);
        if ($supplied_hmac === $correct_hmac) {
          $this->clientSystemId = $clientSystemId;
          $this->projects = $config[$clientSystemId]['projects'];
          $this->clientConfig = $config[$clientSystemId];
          if (!empty($_REQUEST['proj_id'])) {
            RestObjects::$clientWebsiteId = $this->projects[$_REQUEST['proj_id']]['website_id'];
          }
          // Apart from the projects resource, other end-points will need a
          // proj_id if using client system based authorisation.
          if (($this->resourceName === 'taxon-observations' || $this->resourceName === 'annotations') &&
              (empty($_REQUEST['proj_id']) || empty($this->projects[$_REQUEST['proj_id']]))) {
            RestObjects::$apiResponse->fail('Bad request', 400, 'Project ID missing or invalid.');
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
    $authHeader = $this->getAuthHeader();
    if (substr_count($authHeader, ':') === 3) {
      list($u, $websiteId, $h, $supplied_hmac) = explode(':', $authHeader);
      if ($u === 'WEBSITE_ID' && $h === 'HMAC') {
        // Input validation.
        if (!preg_match('/^\d+$/', $websiteId)) {
          RestObjects::$apiResponse->fail('Unauthorized', 401, 'Website ID incorrect format.');
        }
        $websites = RestObjects::$db->select('password')
          ->from('websites')
          ->where(array('id' => $websiteId))
          ->get()->result_array();
        if (count($websites) === 1) {
          $protocol = $this->isHttps ? 'https' : 'http';
          $request_url = "$protocol://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
          $correct_hmac = hash_hmac("sha1", $request_url, $websites[0]->password, $raw_output = FALSE);
          if ($supplied_hmac === $correct_hmac) {
            RestObjects::$clientWebsiteId = $websiteId;
            $this->authenticated = TRUE;
          }
          else {
            RestObjects::$apiResponse->fail('Unauthorized', 401, 'Supplied HMAC authorization incorrect.');
          }
        }
        else {
          RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unrecognised website ID.');
        }
      }
    }
  }

  /**
   * Attempts to authenticate using the direct user protocal.
   */
  private function authenticateUsingDirectUser() {
    $authHeader = $this->getAuthHeader();
    if (substr_count($authHeader, ':') === 5) {
      // 6 parts to authorisation required for user ID, website ID and password
      // pairs.
      list($u, $userId, $w, $websiteId, $h, $password) = explode(':', $authHeader);
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
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'User ID or website ID incorrect format.');
    }
    $users = RestObjects::$db->select('password')
      ->from('users')
      ->where(array('id' => $userId))
      ->get()->result_array(FALSE);
    if (count($users) !== 1) {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
    }
    $auth = new Auth();
    if ($auth->checkPasswordAgainstHash($password, $users[0]['password'])) {
      RestObjects::$clientWebsiteId = $websiteId;
      RestObjects::$clientUserId = $userId;
      // Pass through to ORM.
      global $remoteUserId;
      $remoteUserId = $userId;
      // @todo Is this user a member of the website?
      $this->authenticated = TRUE;
    }
    else {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Incorrect password for user.');
    }
    // @todo Apply user ID limit to data, limit to filterable reports
  }

  /**
   * Attempts to authenticate using the direct client protocal.
   */
  private function authenticateUsingDirectClient() {
    $config = Kohana::config('rest.clients');
    $authHeader = $this->getAuthHeader();
    if ($authHeader && substr_count($authHeader, ':') === 3) {
      list($u, $clientSystemId, $h, $secret) = explode(':', $authHeader);
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
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Invalid client system ID');
    }
    if ($secret !== $config[$clientSystemId]['shared_secret']) {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Incorrect secret');
    }
    $this->clientSystemId = $clientSystemId;
    $this->projects = isset($config[$clientSystemId]['projects']) ? $config[$clientSystemId]['projects'] : [];
    $this->clientConfig = $config[$clientSystemId];
    // Taxon observations and annotations resource end-points will need a
    // proj_id if using client system based authorisation.
    if (($this->resourceName === 'taxon-observations' || $this->resourceName === 'annotations') &&
        (empty($_REQUEST['proj_id']) || empty($this->projects[$_REQUEST['proj_id']]))) {
      RestObjects::$apiResponse->fail('Bad request', 400, 'Project ID missing or invalid.');
    }
    if (!empty($_REQUEST['proj_id'])) {
      $projectConfig = $this->projects[$_REQUEST['proj_id']];
      RestObjects::$clientWebsiteId = $projectConfig['website_id'];
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
    $authHeader = $this->getAuthHeader();
    if (substr_count($authHeader, ':') === 3) {
      list($u, $websiteId, $h, $password) = explode(':', $authHeader);
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
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'User ID or website ID incorrect format.');
    }
    $password = pg_escape_string($password);
    $websites = RestObjects::$db->select('id')
      ->from('websites')
      ->where(array('id' => $websiteId, 'password' => $password))
      ->get()->result_array();
    if (count($websites) !== 1) {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unrecognised website ID or password.');
    }
    RestObjects::$clientWebsiteId = $websiteId;
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
      RestObjects::$apiResponse->fail('Bad request', 400, "Parameter $param is not an integer");
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
      RestObjects::$apiResponse->fail('Bad request', 400, "Parameter $param is not an valid date");
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

  /**
   * Converts $_FILES to a simple array.
   *
   * Accounts for several files being posted as an array or different fields.
   *
   * @return array
   *   List of files.
   */
  private function getFiles() {
    $files = [];
    foreach ($_FILES as $input => $infoArr) {
      $filesByInput = [];
      foreach ($infoArr as $key => $valueArr) {
        if (is_array($valueArr)) {
          // File input by array field.
          foreach ($valueArr as $i => $value) {
            $filesByInput[$input . "[$i]"][$key] = $value;
          }
        }
        else {
          // File input by single fields.
          $filesByInput[$input] = $infoArr;
          break;
        }
      }
      $files = array_merge($files, $filesByInput);
    }
    return $files;
  }

  public function mediaQueuePost() {
    // Upload size.
    $ups = Kohana::config('indicia.maxUploadSize');
    // Get comma separated list of allowed file types.
    $config = kohana::config('indicia.upload_file_type');
    if (!$config) {
      // Default list if no entry in config.
      $types = 'png,gif,jpg,jpeg,mp3,wav,pdf';
    }
    else {
      // Implode array of arrays.
      $types = implode(',', array_map(function($a){
        return implode(',', $a);
      }, $config));
    }
    $fileList = $this->getFiles();
    $files = Validation::factory($fileList);
    foreach (array_keys($fileList) as $fileKey) {
      $files->add_rules(
        $fileKey, 'upload::valid', 'upload::required',
        "upload::type[$types]", "upload::size[$ups]"
      );
    }
    if (!$files->validate()) {
      $errors = $files->errors();
      // Need to translate error messages manually due to file (field) names being variable.
      foreach ($errors as &$error) {
        $error = Kohana::lang("form_error_messages.media_upload.$error");
      }
      RestObjects::$apiResponse->fail('Bad Request', 400, json_encode($errors));
    }
    foreach ($files as $key => $file) {
      kohana::log('debug', var_export($file, TRUE));
      $typeParts = explode('/', $file['type']);
      $fileName = uniqid('', TRUE) . '.' . $typeParts[1];
      upload::save($file, $fileName, 'upload-queue');
      $response[$key] = ['name' => $fileName, 'tempPath' => url::base() . "upload-queue/$fileName"];
    }
    RestObjects::$apiResponse->succeed($response);
  }

  /**
   * End-point to GET an occurrence by ID.
   *
   * @param int $id
   *   Occurrence ID.
   */
  public function occurrencesGetId($id) {
    rest_crud::read('occurrence', $id);
  }

  /**
   * Validates an occurrence values array before saving.
   *
   * * Assigns the website ID.
   * * Checks the sample is valid and belongs to the user.
   */
  private function checkOccurrenceBeforeSave(&$values) {
    // Autofill website ID.
    $values['website_id'] = RestObjects::$clientWebsiteId;
    if (!empty($values['sample_id'])) {
      // Sample must be for same user.
      $sampleCheck = RestObjects::$db->query('select count(*) from samples ' .
        "where id='" . $values['sample_id'] .
        "' and deleted=false and created_by_id=" . RestObjects::$clientUserId)
        ->current()->count;
      if ($sampleCheck !== '1') {
        RestObjects::$apiResponse->fail('Bad Request', 400, ['occurrence:sample_id' => 'Attempt to create occurrence in invalid sample.']);
      }
    }
  }

  /**
   * API end-point to POST an occurrence to create within existing sample.
   */
  public function occurrencesPost() {
    $post = file_get_contents('php://input');
    $postArray = json_decode($post, TRUE);
    $this->checkOccurrenceBeforeSave($postArray['values']);
    $r = rest_crud::create('occurrence', $postArray);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to POST an occurrence to create within existing sample.
   */
  public function occurrencesPostList() {
    $post = file_get_contents('php://input');
    $postArray = json_decode($post, TRUE);
    $r = [];
    foreach ($postArray as $key => $item) {
      $this->checkOccurrenceBeforeSave($item['values']);
      $r[$key] = rest_crud::create('occurrence', $item);
    }
    echo json_encode($r);
    http_response_code(201);
  }

  /**
   * API end-point to PUT to an existing occurrence to update.
   *
   * @param int $id
   *   Occurrence ID.
   */
  public function occurrencesPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    return rest_crud::update('occurrence', $id, $putArray);
  }

  /**
   * API end-point to DELETE an occurrence.
   *
   * Will only be deleted if the occurrence was created by the current user.
   *
   * @param int $id
   *   Occurrence ID to delete.
   */
  public function occurrencesDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    // Delete as long as created by this user.
    rest_crud::delete('occurrence', $id, ['created_by_id' => RestObjects::$clientUserId]);
  }

  /**
   * API end-point to retrieve a location by ID.
   *
   * @param integer $id
   *   ID of the location.
   */
  public function locationsGetId($id) {
    rest_crud::read('location', $id);
  }

  /**
   * API end-point to POST a location to create.
   */
  public function locationsPost() {
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    $r = rest_crud::create('location', $item);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to PUT an existing location to update.
   */
  public function locationsPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    return rest_crud::update('location', $id, $putArray);
  }

  /**
   * API end-point to DELETE a location.
   *
   * Will only be deleted if the location was created by the current user.
   *
   * @param int $id
   *   Location ID to delete.
   */
  public function locationsDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    // Delete as long as created by this user.
    rest_crud::delete('location', $id, ['created_by_id' => RestObjects::$clientUserId]);
  }

  /**
   * End-point to GET a sample by ID.
   *
   * @param int $id
   *   Sample ID.
   */
  public function samplesGetId($id) {
    rest_crud::read('sample', $id);
  }

  /**
   * API end-point to POST a sample to create.
   */
  public function samplesPost() {
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    $r = rest_crud::create('sample', $item);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to POST a sample to create.
   */
  public function samplesPostList() {
    $post = file_get_contents('php://input');
    $list = json_decode($post, TRUE);
    $r = [];
    foreach ($list as $key => $item) {
      $r[$key] = rest_crud::create('sample', $item);
    }
    echo json_encode($r);
    http_response_code(201);
  }

  /**
   * API end-point to PUT to an existing sample to update.
   *
   * @todo Safety check it's from the correct website.
   */
  public function samplesPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    return rest_crud::update('sample', $id, $putArray);
  }

  /**
   * API end-point to DELETE a sample.
   *
   * Will only be deleted if the sample was created by the current user.
   *
   * @param int $id
   *   Sample ID to delete.
   *
   * @todo Safety check it's from the correct website.
   */
  public function samplesDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    // Delete as long as created by this user.
    rest_crud::delete('sample', $id, ['created_by_id' => RestObjects::$clientUserId]);
  }

  /**
   * Check that authenticated user has admin access to the authenticated website.
   *
   * E.g. before CRUD operation on a privileged resource.
   */
  private function assertUserHasWebsiteAdminAccess() {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown.');
    }
    $websiteId = RestObjects::$clientWebsiteId;
    $userId = RestObjects::$clientUserId;
    $sql = <<<SQL
SELECT u.id, u.core_role_id, uw.site_role_id
FROM users u
LEFT JOIN users_websites uw ON uw.user_id=u.id AND uw.website_id=$websiteId and uw.site_role_id=3
WHERE u.id=$userId;
SQL;
    $user = RestObjects::$db->query($sql)->current();
    if (!$user) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user not found.');
    }
    if (empty($user->site_role_id) && empty($user->core_role_id)) {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'User does not have access to website.');
    }
  }

  /**
   * Simple check that a record's website ID is the authenticated one.
   *
   * E.g. before an UPDATE or DELETE.
   *
   * @param string $table
   *   Table name.
   * @param int $id
   *   Record ID.
   * @param string $sql
   *   Pass SQL for check, only required if not standard.
   */
  private function assertRecordFromCurrentWebsite($table, $id, $sql = NULL) {
    $websiteId = RestObjects::$clientWebsiteId;
    if (!$sql) {
      $sql = <<<SQL
SELECT COUNT(*) FROM $table WHERE deleted=false AND id=$id AND website_id=$websiteId;
SQL;
    }
    $check = RestObjects::$db->query($sql)->current();
    if ($check->count === '0') {
      // Determine if record missing or permissions so we can return correct
      // error.
      $sql = <<<SQL
SELECT count(*) FROM $table WHERE deleted=false AND id=$id
SQL;
      $check = RestObjects::$db->query($sql)->current();
      if ($check->count === '0') {
        RestObjects::$apiResponse->fail('Not Found', 404, 'Attempt to DELETE missing or already deleted record.');
      }
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Attempt to PUT or DELETE record from another website.');
    }
  }

  /**
   * End-point to GET a list of available surveys.
   */
  public function surveysGet() {
    rest_crud::readList('survey', 'AND t1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * End-point to GET a survey by ID.
   *
   * @param int $id
   *   Survey ID.
   */
  public function surveysGetId($id) {
    rest_crud::read('survey', $id, 'AND t1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * API end-point to POST a survey to create.
   */
  public function surveysPost() {
    $this->assertUserHasWebsiteAdminAccess();
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    // Autofill website ID.
    if (isset($item['values'])) {
      $item['values']['website_id'] = RestObjects::$clientWebsiteId;
    }
    $r = rest_crud::create('survey', $item);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to PUT to an existing survey to update.
   */
  public function surveysPutId($id) {
    $this->assertUserHasWebsiteAdminAccess();
    $this->assertRecordFromCurrentWebsite('surveys', $id);
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    return rest_crud::update('survey', $id, $putArray);
  }

  /**
   * API end-point to DELETE a survey.
   *
   * Will only be deleted if the survey was created by the current user.
   *
   * @param int $id
   *   Survey ID to delete.
   */
  public function surveysDeleteId($id) {
    $this->assertUserHasWebsiteAdminAccess();
    $this->assertRecordFromCurrentWebsite('surveys', $id);
    // Delete - no need to check user as admin of website.
    rest_crud::delete('survey', $id);
  }

  /**
   * Assert that a sample or occurrence attribute is from the current website.
   *
   * Also checks the attribute is not public. Therefore access only granted if
   * the attribute is unique to the authorised website.
   *
   * @param string $table
   *   Table name.
   * @param int $id
   *   Record ID.
   */
  private function assertAttributeFromCurrentWebsite($table, $id) {
    $entity = inflector::singular($table);
    $websiteId = RestObjects::$clientWebsiteId;
    $checkSql = <<<SQL
SELECT COUNT(a.*) FROM $table a
JOIN {$table}_websites aw ON aw.{$entity}_id=a.id AND aw.website_id=$websiteId AND aw.deleted=false
WHERE a.deleted=false AND a.id=$id AND a.public=false;
SQL;
    $this->assertRecordFromCurrentWebsite($table, $id, $checkSql);
  }

  /**
   * Assert that an attribute being deleted or updated has no values.
   *
   * @param string $table
   *   Table name.
   * @param int $id
   *   Record ID.
   */
  private function assertAttributeHasNoValues($table, $id) {
    $entity = inflector::singular($table);
    $checkSql = <<<SQL
SELECT id FROM {$entity}_values WHERE {$entity}_id=$id AND deleted=false LIMIT 1;
SQL;
    if (RestObjects::$db->query($checkSql)->current()) {
      RestObjects::$apiResponse->fail('Forbidden', 403, 'Attempt to DELETE attribute with values.');
    }
  }

  /**
   * Check if a PUT attribute alters the type.
   *
   * If so we want to disallow if any existing data.
   *
   * @todo TEST
   */
  private function attributeTypeChanging($table, $id, $putArray) {
    if (!empty($putArray['values']['data_type'])) {
      $newType = $putArray['values']['data_type'];
      $checkSql = <<<SQL
SELECT id FROM {$table} WHERE id=$id AND data_type<>'$newType';
SQL;
      return !empty(RestObjects::$db->query($checkSql)->current());
    }
    return FALSE;
  }

  /**
   * End-point to GET a list of available sample attributes.
   */
  public function sampleAttributesGet() {
    rest_crud::readList('sample_attribute', 'AND t2.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * End-point to GET a sample attribute by ID.
   *
   * @param int $id
   *   Sample attribute ID.
   */
  public function sampleAttributesGetId($id) {
    rest_crud::read('sample_attribute', $id, 'AND t2.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * API end-point to POST a sample_attribute to create.
   */
  public function sampleAttributesPost() {
    $this->assertUserHasWebsiteAdminAccess();
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    // Autofill website ID.
    if (isset($item['values'])) {
      $item['values']['website_id'] = RestObjects::$clientWebsiteId;
    }
    $r = rest_crud::create('sample_attribute', $item);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to PUT to an existing sample attribute to update.
   */
  public function sampleAttributesPutId($id) {
    $this->assertUserHasWebsiteAdminAccess();
    $this->assertAttributeFromCurrentWebsite('sample_attributes', $id);
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    if ($this->attributeTypeChanging('sample_attributes', $id, $putArray)) {
      $this->assertAttributeHasNoValues('sample_attributes', $id);
    }
    return rest_crud::update('sample_attribute', $id, $putArray);
  }

  /**
   * API end-point to DELETE a sample attribute.
   *
   * Will only be deleted if the survey was created by the current user.
   *
   * @param int $id
   *   Survey ID to delete.
   */
  public function sampleAttributesDeleteId($id) {
    $this->assertUserHasWebsiteAdminAccess();
    $this->assertAttributeFromCurrentWebsite('sample_attributes', $id);
    $this->assertAttributeHasNoValues('sample_attributes', $id);
    // Delete - no need to check user as admin of website.
    rest_crud::delete('sample_attribute', $id);
    // Also delete links.
    RestObjects::$db->query("UPDATE sample_attributes_websites SET deleted=TRUE WHERE sample_attribute_id=$id");
  }

  /**
   * End-point to GET a list of available sample attributes websites.
   */
  public function sampleAttributesWebsitesGet() {
    rest_crud::readList('sample_attributes_website', 'AND t1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * End-point to GET a sample attribute by ID.
   *
   * @param int $id
   *   Sample attribute ID.
   */
  public function sampleAttributesWebsitesGetId($id) {
    rest_crud::read('sample_attributes_website', $id, 'AND t1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * API end-point to POST a sample attributes website to create.
   */
  public function sampleAttributesWebsitesPost() {
    $this->assertUserHasWebsiteAdminAccess();
    $post = file_get_contents('php://input');
    $postArray = json_decode($post, TRUE);
    // Autofill website ID.
    if (isset($postArray['values'])) {
      $postArray['values']['website_id'] = RestObjects::$clientWebsiteId;
    }
    // Duplicate check.
    $existing = RestObjects::$db->select('aw.id')
        ->from('sample_attributes_websites aw')
        ->where([
          'website_id' => $postArray['values']['website_id'],
          'sample_attribute_id' => $postArray['values']['sample_attribute_id'],
          'restrict_to_survey_id' => $postArray['values']['restrict_to_survey_id'],
        ])
        ->get()->current();
      if ($existing) {
        RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
      }
    rest_crud::create('sample_attributes_website', $postArray);
  }

  /**
   * API end-point to PUT to an existing sample attributes website to update.
   */
  public function sampleAttributesWebsitesPutId($id) {
    $this->assertUserHasWebsiteAdminAccess();
    $this->assertRecordFromCurrentWebsite('sample_attributes_website', $id);
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    rest_crud::update('sample_attributes_website', $id, $putArray);
  }

  /**
   * API end-point to DELETE a sample attribute.
   *
   * Will only be deleted if the survey was created by the current user.
   *
   * @param int $id
   *   Survey ID to delete.
   */
  public function sampleAttributesWebsitesDeleteId($id) {
    $this->assertUserHasWebsiteAdminAccess();
    $this->assertRecordFromCurrentWebsite('sample_attributes_website', $id);
    rest_crud::delete('sample_attributes_website', $id);
  }

  /**
   * End-point to GET a list of available occurrence attributes.
   */
  public function occurrenceAttributesGet() {
    rest_crud::readList('occurrence_attribute', 'AND t2.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * End-point to GET an occurrence attribute by ID.
   *
   * @param int $id
   *   Occurrence attribute ID.
   */
  public function occurrenceAttributesGetId($id) {
    rest_crud::read('occurrence_attribute', $id, 'AND t2.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * API end-point to POST a occurrence_attribute to create.
   */
  public function occurrenceAttributesPost() {
    $this->assertUserHasWebsiteAdminAccess();
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    // Autofill website ID.
    if (isset($item['values'])) {
      $item['values']['website_id'] = RestObjects::$clientWebsiteId;
    }
    $r = rest_crud::create('occurrence_attribute', $item);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to PUT to an existing occurrence attribute to update.
   */
  public function occurrenceAttributesPutId($id) {
    $this->assertUserHasWebsiteAdminAccess();
    $this->assertAttributeFromCurrentWebsite('occurrence_attributes', $id);
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    if ($this->attributeTypeChanging('occurrence_attributes', $id, $putArray)) {
      $this->assertAttributeHasNoValues('occurrence_attributes', $id);
    }
    return rest_crud::update('occurrence_attribute', $id, $putArray);
  }

  /**
   * API end-point to DELETE an occurrence attribute.
   *
   * Will only be deleted if the survey was created by the current user.
   *
   * @param int $id
   *   Survey ID to delete.
   */
  public function occurrenceAttributesDeleteId($id) {
    $this->assertUserHasWebsiteAdminAccess();
    $this->assertAttributeFromCurrentWebsite('occurrence_attributes', $id);
    $this->assertAttributeHasNoValues('occurrence_attributes', $id);
    // Delete - no need to check user as admin of website.
    rest_crud::delete('occurrence_attribute', $id);
    // Also delete links.
    RestObjects::$db->query("UPDATE occurrence_attributes_websites SET deleted=TRUE WHERE occurrence_attribute_id=$id");
  }

  /**
   * End-point to GET a list of available occurrence attributes websites.
   */
  public function occurrenceAttributesWebsitesGet() {
    rest_crud::readList('occurrence_attributes_website', 'AND t1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * End-point to GET a occurrence attribute by ID.
   *
   * @param int $id
   *   Occurrence attribute ID.
   */
  public function occurrenceAttributesWebsitesGetId($id) {
    rest_crud::read('occurrence_attributes_website', $id, 'AND t1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * API end-point to POST a occurrence attributes website to create.
   */
  public function occurrenceAttributesWebsitesPost() {
    $this->assertUserHasWebsiteAdminAccess();
    $post = file_get_contents('php://input');
    $postArray = json_decode($post, TRUE);
    // Autofill website ID.
    if (isset($postArray['values'])) {
      $postArray['values']['website_id'] = RestObjects::$clientWebsiteId;
    }
    // Duplicate check.
    $existing = RestObjects::$db->select('aw.id')
        ->from('occurrence_attributes_websites aw')
        ->where([
          'website_id' => $postArray['values']['website_id'],
          'occurrence_attribute_id' => $postArray['values']['occurrence_attribute_id'],
          'restrict_to_survey_id' => $postArray['values']['restrict_to_survey_id'],
        ])
        ->get()->current();
      if ($existing) {
        RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
      }
    rest_crud::create('occurrence_attributes_website', $postArray);
  }

  /**
   * API end-point to PUT to an existing occurrence attributes website to update.
   */
  public function occurrenceAttributesWebsitesPutId($id) {
    $this->assertUserHasWebsiteAdminAccess();
    $this->assertRecordFromCurrentWebsite('occurrence_attributes_website', $id);
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    rest_crud::update('occurrence_attributes_website', $id, $putArray);
  }

  /**
   * API end-point to DELETE a occurrence attribute.
   *
   * Will only be deleted if the survey was created by the current user.
   *
   * @param int $id
   *   Survey ID to delete.
   */
  public function occurrenceAttributesWebsitesDeleteId($id) {
    $this->assertUserHasWebsiteAdminAccess();
    $this->assertRecordFromCurrentWebsite('occurrence_attributes_website', $id);
    rest_crud::delete('occurrence_attributes_website', $id);
  }

}
