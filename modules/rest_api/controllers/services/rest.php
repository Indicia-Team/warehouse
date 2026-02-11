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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

use Firebase\JWT;

define("REST_API_DEFAULT_PAGE_SIZE", 100);
define("AUTOFEED_DEFAULT_PAGE_SIZE", 3000);
const ALLOWED_SCOPES = [
  'reporting',
  'peer_review',
  'verification',
  'data_flow',
  'moderation',
  'editing',
  'user',
  'userWithinWebsite',
];

if (!function_exists('apache_request_headers')) {
  Kohana::log('debug', 'PHP apache_request_headers() function does not exist. Replacement function used.');

  /**
   * Polyfill for apache_request_headers function if not available.
   */
  function apache_request_headers() {
    $arh = [];
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
 * Exception class for aborting whilst notifying the client.
 */
class RestApiNotifyClient extends RestApiAbort {}

/**
 * Simple object to keep globally useful stuff in.
 */
class RestObjects {

  /**
   * Database connection.
   *
   * @var object
   */
  public static $db;

  /**
   * Website ID the client is authenticated as.
   *
   * @var int
   */
  public static int $clientWebsiteId;

  /**
   * Public key for the website the client is authenticated as.
   *
   * @var int
   */
  public static $clientWebsitePublicKey;

  /**
   * Public key for the website the client is authenticated as.
   *
   * @var array
   */
  public static array $jwtPayloadValues;

  /**
   * User ID the client is authenticated as.
   *
   * @var int
   */
  public static int $clientUserId;

  /**
   * When using JwtUser auth, the user's role ID in the website.
   *
   * @var int
   */
  public static int $clientUserWebsiteRole;

  /**
   * Name of the warehouse module handling the request.
   *
   * Might not be rest_api if the REST services extended by a modules's plugin
   * file.
   *
   * @var string
   */
  public static $handlerModule;

  /**
   * RestApiResponse class instance.
   *
   * @var RestApiResponse
   */
  public static $apiResponse;

  /**
   * Name of the authentication method.
   *
   * @var string
   */
  public static $authMethod;

  /**
   * The client's system ID (i.e. the caller).
   *
   * Set if authenticated against the list of configured clients.
   *
   * @var string
   */
  public static $clientSystemId;

  /**
   * Current request's scope (sharing mode), e.g. reporting.
   *
   * @var string
   */
  public static $scope = 'reporting';

  /**
   * Elasticsearch config from the config file.
   *
   * @var array
   */
  public static $esConfig = [];

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
   * Report generation class instance.
   *
   * @var obj
   */
  private $reportEngine;

  /**
   * If limited, then the list of reports allowed for the connection.
   *
   * @var array
   */
  private $limitToReports = [];

  /**
   * If limited, then the list of data resources allowed for the connection.
   *
   * @var array
   */
  private $limitToDataResources = [];

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
    'jwtUser' => [
      'resource_options' => [
        // Grants full access to all reports. Client configs can override this.
        'reports' => ['featured' => TRUE],
      ],
    ],
  ];

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
   * Config settings relating to elasticsearch.
   *
   * An empty array if $authConfig['resource_options']['elasticsearch'] is a
   * simple array of ES endpoints with no config. Otherwise holds config from
   * $authConfig['resource_options']['elasticsearch'][<es-endpoint>]
   *
   * @var array
   */
  private $esConfig;

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
    'custom-verification-rulesets' => [
      'POST' => [
        'custom-verification-rulesets/{id}/run-request' => [],
        'custom-verification-rulesets/clear-flags' => [],
      ],
    ],
    'groups' => [
      'GET' => [
        'groups' => [
          'params' => [
            'page' => [
              'datatype' => 'text',
            ],
            'verbose' => [
              'datatype' => 'integer',
            ],
            'view' => [
              'datatype' => 'text',
              'options' => ['member', 'joinable', 'all_available', 'pending'],
            ],
          ],
        ],
        'groups/{id}' => [
          'params' => [
            'verbose' => [
              'datatype' => 'integer',
            ],
          ],
        ],
        'groups/{id}/locations' => [],
        'groups/{id}/users' => [],
      ],
      'POST' => [
        'groups/{id}/locations' => [],
        'groups/{id}/users' => [],
      ],
      'DELETE' => [
        'groups/{id}/users/{id}' => [],
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
    'location-media' => [
      'GET' => [
        'location-media' => [],
        'location-media/{id}' => [],
      ],
      'POST' => [
        'location-media' => [],
      ],
      'PUT' => [
        'location-media/{id}' => [],
      ],
      'DELETE' => [
        'location-media/{id}' => [],
      ],
    ],
    'notifications' => [
      'GET' => [
        'notifications' => [],
        'notifications/{id}' => [],
      ],
      'PUT' => [
        'notifications/{id}' => [],
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
    'occurrence-comments' => [
      'GET' => [
        'occurrence-comments' => [],
        'occurrence-comments/{id}' => [],
      ],
      'POST' => [
        'occurrence-comments' => [],
      ],
      'PUT' => [
        'occurrence-comments/{id}' => [],
      ],
      'DELETE' => [
        'occurrence-comments/{id}' => [],
      ],
    ],
    'occurrence-media' => [
      'GET' => [
        'occurrence-media' => [],
        'occurrence-media/{id}' => [],
      ],
      'POST' => [
        'occurrence-media' => [],
      ],
      'PUT' => [
        'occurrence-media/{id}' => [],
      ],
      'DELETE' => [
        'occurrence-media/{id}' => [],
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
        'occurrences/check-newness' => [
          'params' => [
            'external_key' => [
              'datatype' => 'string',
              'required' => TRUE,
            ],
            'lat' => [
              'datatype' => 'float',
            ],
            'lon' => [
              'datatype' => 'float',
            ],
            'grid_square_size' => [
              'datatype' => 'string',
            ],
            'year' => [
              'datatype' => 'integer',
            ],
            'group_id' => [
              'datatype' => 'integer',
            ],
          ],
        ],
      ],
      'POST' => [
        'occurrences' => [],
        'occurrences/list' => [],
        'occurrences/verify-spreadsheet' => [],
      ],
      'PUT' => [
        'occurrences/{id}' => [],
      ],
      'DELETE' => [
        'occurrences/{id}' => [],
      ],
    ],
    'dna-occurrences' => [
      'GET' => [
        'dna-occurrences' => [],
        'dna-occurrences/{id}' => [],
      ],
      'POST' => [
        'dna-occurrences' => [],
      ],
      'PUT' => [
        'dna-occurrences/{id}' => [],
      ],
      'DELETE' => [
        'dna-occurrences/{id}' => [],
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
            'autofeed' => [
              'datatype' => 'boolean',
            ],
            'filter_id' => [
              'datatype' => 'integer',
            ],
            'limit' => [
              'datatype' => 'integer',
            ],
            'max_time' => [
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
    'sample-comments' => [
      'GET' => [
        'sample-comments' => [],
        'sample-comments/{id}' => [],
      ],
      'POST' => [
        'sample-comments' => [],
      ],
      'PUT' => [
        'sample-comments/{id}' => [],
      ],
      'DELETE' => [
        'sample-comments/{id}' => [],
      ],
    ],
    'sample-media' => [
      'GET' => [
        'sample-media' => [],
        'sample-media/{id}' => [],
      ],
      'POST' => [
        'sample-media' => [],
      ],
      'PUT' => [
        'sample-media/{id}' => [],
      ],
      'DELETE' => [
        'sample-media/{id}' => [],
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
            'scratchpad_list_id' => [
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
            'search_code' => [
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
   * No longer implemented due to being not-recommended best practice.
   */
  public function token() {
    RestObjects::$apiResponse->fail('Not Implemented', 501, 'oAuth 2.0 Password authentication not supported');
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
      if (!isset($this->resourceOptions) && isset($this->authConfig['resource_options'])) {
        // Resource options may be at the top level of the config (e.g. reports).
        if (isset($this->authConfig['resource_options'][$this->resourceName])) {
          $this->resourceOptions = $this->authConfig['resource_options'][$this->resourceName];
        }
        elseif ($this->elasticProxy && isset($this->authConfig['resource_options']['elasticsearch'])) {
          $this->resourceOptions = $this->authConfig['resource_options']['elasticsearch'][$this->resourceName] ?? [];
        }
      }
      // Caching can be enabled via a query string parameter if not already
      // forced by the authorisation config.
      if (!empty($_GET['cached']) && $_GET['cached'] === 'true') {
        $this->resourceOptions['cached'] = TRUE;
      }
      $this->method = $_SERVER['REQUEST_METHOD'];
      if ($this->elasticProxy) {
        $es = new RestApiElasticsearch($this->elasticProxy, $this->resourceOptions ?? []);
        $es->checkResourceAllowed();
        if ($this->method === 'OPTIONS') {
          // A request for the methods allowed for this resource.
          header('Allow: ' . strtoupper(implode(', ', ['GET', 'POST', 'OPTIONS'])));
        }
        else {
          $postRaw = file_get_contents('php://input');
          $postObj = empty($postRaw) ? (object) [] : json_decode($postRaw);
          $format = (isset($_GET['format']) && $_GET['format'] === 'csv') ? 'csv' : 'json';
          $es->elasticRequest($postObj, $format);
        }
      }
      else {
        $resourceConfig = $this->findResourceConfig();
        if (!$resourceConfig) {
          RestObjects::$apiResponse->fail('Not Found', 404, "Resource $this->resourceName not known");
        }
        if ($this->method === 'OPTIONS') {
          // A request for the methods allowed for this resource.
          header('Allow: ' . strtoupper(implode(', ', array_merge(array_keys($resourceConfig), ['OPTIONS']))));
        }
        else {
          if (!array_key_exists($this->method, $resourceConfig)) {
            RestObjects::$apiResponse->fail('Method Not Allowed', 405, $this->method . " not allowed for $name");
          }
          if (!empty($this->limitToDataResources)) {
            if (!in_array(strtolower($this->resourceName), $this->limitToDataResources)) {
              RestObjects::$apiResponse->fail('Forbidden', 403, "Unauthorised data resource $this->resourceName requested - limited to " . implode(', ', $this->limitToDataResources));
            }
          }
          $this->request = $this->method === 'GET' ? $_GET : $_POST;
          $this->checkVersion($arguments);
          $methodConfig = $resourceConfig[$this->method];
          $pathConfigPattern = $this->getPathConfigPatternMatch($methodConfig, $arguments);
          $pathConfig = $methodConfig[$pathConfigPattern];
          $methodName = $this->getMethodName($arguments, strpos($pathConfigPattern, '{path}') !== FALSE);
          $requestForId = NULL;
          $ids = preg_grep('/^([A-Z]{3})?\d+$/', $arguments);
          $projectId = NULL;
          if (count($ids) > 0) {
            $requestForId = array_values($ids)[0];
          }
          // When using a client system ID, we also want a project ID in most
          // cases.
          if (isset(RestObjects::$clientSystemId)
              && !in_array($name, [
                'projects',
                'taxa',
                'custom_verification_rulesets',
              ])) {
            if (empty($_REQUEST['proj_id'])) {
              // Should not have got this far - just in case.
              RestObjects::$apiResponse->fail('Bad request', 400, 'Missing proj_id parameter.');
            }
            else {
              $this->checkAllowedResource($_REQUEST['proj_id'], $this->resourceName);
              $projectId = $_REQUEST['proj_id'];
            }
          }
          $this->validateParameters($pathConfig);
          if (RestObjects::$handlerModule === 'rest_api' && method_exists($this, $methodName)) {
            $class = $this;
          }
          elseif (RestObjects::$handlerModule !== 'rest_api' && method_exists(RestObjects::$handlerModule . '_rest_endpoints', $methodName)) {
            // Expect any modules extending the API to implement a helper class
            // <module name>_rest_endpoints.
            $class = RestObjects::$handlerModule . '_rest_endpoints';
          }
          else {
            RestObjects::$apiResponse->fail('Not Found', 404, "Resource $name not known for method $this->method ($methodName)");
          }
          call_user_func([$class, $methodName], $requestForId, $this->clientConfig, $projectId);
        }
      }
    }
    catch (RestApiAbort $e) {
      // No action if a proper abort.
    }
    catch (Exception $e) {
      if (class_exists('request_logging')) {
        $io = in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE']) ? 'i' : 'o';
        $websiteId = RestObjects::$clientWebsiteId ?? 0;
        $userId = RestObjects::$clientUserId ?? 0;
        $subTask = implode('/', $arguments);
        request_logging::log($io, 'rest', $subTask, $name, $websiteId, $userId, $tm, RestObjects::$db, $e->getMessage());
      }
      error_logger::log_error('Error in Rest API report request', $e);
      throw $e;
    }
    if (class_exists('request_logging')) {
      $io = in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE']) ? 'i' : 'o';
      $websiteId = RestObjects::$clientWebsiteId ?? 0;
      $userId = RestObjects::$clientUserId ?? 0;
      $subTask = implode('/', $arguments);
      request_logging::log($io, 'rest', $subTask, $name, $websiteId, $userId, $tm, RestObjects::$db, RestObjects::$apiResponse->responseFailMessage);
    }
  }

  /**
   * Finds the configuration for the current resource.
   *
   * For core entities, the resources are listed in $this->resourceConfig.
   * Resources may also be exposed by warehouse modules implementing the
   * `<module>_extend_rest_api` plugin function in which case the config is
   * provided by the module.
   *
   * @return array|bool
   *   Configuration for this resource or FALSE if not found.
   */
  private function findResourceConfig() {
    if (array_key_exists($this->resourceName, $this->resourceConfig)) {
      RestObjects::$handlerModule = 'rest_api';
      return $this->resourceConfig[$this->resourceName];
    }
    else {
      // Load from plugin or fail.
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
  private function getPathConfigPatternMatch(array $methodConfig, array $arguments) {
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
  private function getMethodName(array $arguments, $usingPath) {
    $methodNamePartsArr = array_merge($arguments);
    $methodNamePartsArr = preg_replace('/^([A-Z]{3})?\d+$/', 'id', $methodNamePartsArr);
    $methodNamePartsArr = preg_replace('/^[a-zA-Z0-9_-]+\.xml$/', 'file', $methodNamePartsArr);
    $methodNamePartsArr = array_map(function ($item) {
      return str_replace(' ', '', ucwords(str_replace('-', ' ', $item)));
    }, $methodNamePartsArr);
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
    RestObjects::$apiResponse->succeed($this->projects[$id], [
      'columnsToUnset' => ['filter_id', 'website_id', 'sharing', 'resources'],
      'attachHref' => ['projects', 'id'],
    ]);
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
   *
   * @deprecated
   *   Deprecated in version 6.3 and may be removed in future. Use the
   *   sync-taxon-observations end-point provided by the rest_api_sync module
   *   instead.
   */
  private function taxonObservationsGetId($id) {
    if (substr($id, 0, strlen(kohana::config('rest.user_id'))) === kohana::config('rest.user_id')) {
      $occurrence_id = substr($id, strlen(kohana::config('rest.user_id')));
      $params = ['occurrence_id' => $occurrence_id];
    }
    else {
      // @todo What happens if system not recognised?
      $params = ['external_key' => $id];
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
        [
          'attachHref' => ['taxon-observations', 'id'],
          'columns' => $report['content']['columns'],
        ]
      );
    }
  }

  /**
   * GET handler for the taxon-observations resource.
   *
   * Outputs a list of taxon observation details.
   *
   * @deprecated
   *   Deprecated in version 6.3 and may be removed in future. Use the
   *   sync-taxon-observations end-point provided by the rest_api_sync module
   *   instead.
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
        'attachHref' => ['taxon-observations', 'id'],
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
    $params = ['id' => $id];
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
      $record['taxonObservation'] = [
        'id' => $record['taxon_observation_id'],
        // @todo href
      ];
      RestObjects::$apiResponse->succeed($record, [
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
      ]);
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
    $params = array_merge([
      'limit' => REST_API_DEFAULT_PAGE_SIZE,
      'include' => ['data', 'count', 'paging', 'columns'],
    ], $this->request);
    $db = new Database();
    try {
      $params['count'] = FALSE;
      $query = postgreSQL::taxonSearchQuery($db, $params);
    }
    catch (Exception $e) {
      RestObjects::$apiResponse->fail('Bad request', 400, $e->getMessage());
      error_logger::log_error('REST Api exception during build of taxon search query', $e);
    }
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
        $countQuery = postgreSQL::taxonSearchQuery($db, $params);
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
    $columns = [
      'taxa_taxon_list_id' => ['caption' => 'Taxa taxon list ID'],
      'searchterm' => ['caption' => 'Search term'],
      'highlighted' => ['caption' => 'Highlighted'],
      'taxon' => ['caption' => 'Taxon'],
      'authority' => ['caption' => 'Authority'],
      'language_iso' => ['caption' => 'Language'],
      'preferred_taxon' => ['caption' => 'Preferred name'],
      'preferred_authority' => ['caption' => 'Preferred name authority'],
      'default_common_name' => ['caption' => 'Common name'],
      'taxon_group' => ['caption' => 'Taxon group'],
      'preferred' => ['caption' => 'Preferred'],
      'preferred_taxa_taxon_list_id' => ['caption' => 'Preferred taxa taxon list ID'],
      'taxon_meaning_id' => ['caption' => 'Taxon meaning ID'],
      'external_key' => ['caption' => 'External Key'],
      'search_code' => ['caption' => 'Search Code'],
      'taxon_group_id' => ['caption' => 'Taxon group ID'],
      'parent_id' => ['caption' => 'Parent taxa taxon list ID'],
      'identification_difficulty' => ['caption' => 'Ident. difficulty'],
      'id_diff_verification_rule_id' => ['caption' => 'Ident. difficulty verification rule ID'],
    ];
    if (in_array('columns', $params['include'])) {
      $result['columns'] = $columns;
    }
    $resultOptions = ['columns' => $columns];
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

  /**
   * Handler for GET reports/featured.
   *
   * Returns a list of the reports that have the attribute featured="true".
   */
  private function reportsGetFeatured() {
    $this->reportsGet(TRUE);
  }

  /**
   * Handler for GET reports/path.
   *
   * Returns a list of the reports found within a folder path.
   */
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
      $params = [];
    }
    $params['known_count'] = $count;
    $pagination = [
      'self' => "$urlPrefix$url?" . http_build_query($params),
    ];
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
      // Ensure we don't run the same autofeed query twice at one time, e.g. if
      // the previous request ran slowly.
      warehouse::lockProcess('rest-autofeed');
    }
    try {
      $reportFile = $this->getReportFileNameFromSegments($segments);
      if (!empty($this->limitToReports)) {
        if (!in_array(strtolower("$reportFile.xml"), $this->limitToReports)) {
          RestObjects::$apiResponse->fail('Forbidden', 403, 'Report requested is not allowed');
        }
        if (!empty($this->resourceOptions['featured']) || !empty($this->resourceOptions['summary'])) {
          // Need to load the report file XML to determine if featured or
          // summary due to limits in auth method config.
          $metadata = XMLReportReader::loadMetadata("$reportFile");
          if (!empty($this->resourceOptions['featured']) && empty($metadata['featured'])) {
            RestObjects::$apiResponse->fail('Unauthorized', 403, 'Report requested is not allowed');
          }
          if (!empty($this->resourceOptions['summary']) && empty($metadata['summary'])) {
            RestObjects::$apiResponse->fail('Unauthorized', 403, 'Report requested is not allowed');
          }
        }
      }
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
          var_export($report, TRUE));
      }
    }
    finally {
      if ($this->getAutofeedMode()) {
        // Unlock the process as no longer querying.
        warehouse::unlockProcess('rest-autofeed');
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
    RestObjects::$apiResponse->succeed(['data' => $list], ['metadata' => ['description' => $description]]);
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
   * @param bool $featured
   *   True if returning a list of the featured reports.
   */
  private function getReportHierarchy(array $segments, $featured) {
    $this->loadReportEngine();
    // @todo Cache this
    $reportHierarchy = $this->reportEngine->reportList();
    $response = [];
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
      $reportHierarchy = [
        'featured' => [
          'type' => 'folder',
          'description' => kohana::lang("rest_api.reports.featured_folder_description"),
        ],
      ] + $reportHierarchy;
    }
    if ($featured) {
      $response = [];
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
    RestObjects::$apiResponse->succeed($response, ['metadata' => ['description' => $description]]);
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
    $metadata['columns'] = [
      'href' => RestObjects::$apiResponse->getUrlWithCurrentParams("reports/$metadata[path].xml/columns"),
    ];
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
      $dateErrors = $dt->getLastErrors();
      if ($dt === FALSE || !empty($dateErrors['warning_count']) || !empty($dateErrors['error_count'])) {
        RestObjects::$apiResponse->fail('Bad request', 400, "Invalid date for $paramName parameter");
      }
    }
    elseif ($datatype === 'boolean') {
      if (!preg_match('/^(true|false|t|f)$/', $trimmed)) {
        RestObjects::$apiResponse->fail('Bad request', 400,
            "Invalid boolean for $paramName parameter, value should be true or false");
      }
      // Set the value to a real bool.
      $value = $trimmed === 'true' || $trimmed === 't';
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
   * @param array $methodConfig
   *  Configuration for the request.
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
      $this->reportEngine = new ReportEngine([RestObjects::$clientWebsiteId]);
      if (!empty($this->limitToReports)) {
        // Connection details on table rest_api_client_connections can set a
        // limited list of reports.
        $this->reportEngine->setAuthorisedReports($this->limitToReports);
      }
      elseif (isset($this->resourceOptions['authorise'])) {
        // Resource configuration in config/rest.php can provide a list of
        // restricted reports that are allowed for this client.
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
   * Is a filter on user's own data required?
   *
   * @return bool
   *   True if a filter on created_by_id required.
   */
  private function needToFilterToUser() {
    return !empty($this->resourceOptions['limit_to_own_data'])
      || RestObjects::$scope === 'userWithinWebsite'
      || RestObjects::$scope === 'user';
  }

  /**
   * Is a filter on website required?
   *
   * @return bool
   *   True if a filter on website_id required.
   */
  private function needToFilterToWebsite() {
    return RestObjects::$scope === 'userWithinWebsite';
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
    // @todo Apply permissions for user or website & write tests.
    if (!empty($this->resourceOptions['filter_id'])) {
      // Limit records to a filter specified in rest_api_client_connections
      // table.
      $filter = $this->loadFilter($this->resourceOptions['filter_id']);
    }
    elseif (isset(RestObjects::$clientSystemId)) {
      // Load the filter associated with the project ID.
      $filter = $this->loadFilterForProject($this->request['proj_id']);
    }
    elseif (isset(RestObjects::$clientUserId)) {
      // When authenticating a user, you can use one of the permissions filters
      // for the user to gain access to a wider pool of records, e.g. for a
      // verifier to access all records they have rights to.
      if (!empty($_GET['filter_id'])) {
        $filter = $this->getPermissionsFilterDefinition();
      }
      elseif (!empty($this->resourceOptions['limit_to_own_data']) || RestObjects::$scope === 'userWithinWebsite') {
        if ($this->needToFilterToUser()) {
          $filter['created_by_id'] = RestObjects::$clientUserId;
        }
        if ($this->needToFilterToWebsite()) {
          $filter['website_list'] = RestObjects::$clientWebsiteId;
        }
      }
    }
    else {
      if (!isset(RestObjects::$clientWebsiteId)) {
        RestObjects::$apiResponse->fail('Internal server error', 500, 'Minimal filter on website ID not provided.');
      }
      $filter = [
        'website_list' => RestObjects::$clientWebsiteId,
      ];
    }
    // Apply limits defined in rest_api_client_connections table.
    if (isset($this->resourceOptions['allow_confidential'])) {
      $filter['confidential'] = $this->resourceOptions['allow_confidential'] ? 'all' : 'f';
    }
    if (isset($this->resourceOptions['allow_sensitive']) && !$this->resourceOptions['allow_sensitive']) {
      $filter['exclude_sensitive'] = TRUE;
    }
    if (isset($this->resourceOptions['allow_unreleased']) && $this->resourceOptions['allow_unreleased']) {
      $filter['release_status'] = 'A';
    }
    // The project's filter acts as a context for the report, meaning it
    // defines the limit of all the records that are available for this project.
    foreach ($filter as $key => $value) {
      $params["{$key}_context"] = $value;
    }
    $params['system_user_id'] = $this->serverUserId;
    if (substr(RestObjects::$scope, 0, 4) !== 'user') {
      // User based scope handled above - others map to sharing mode.
      $params['sharing'] = RestObjects::$scope;
    }
    $params = array_merge(
      ['limit' => REST_API_DEFAULT_PAGE_SIZE],
      $params
    );
    // Get the output, setting the option to load a pg result object rather
    // than populated array unless we are going to cache the result in which
    // case we need it all.
    try {
      kohana::log('debug', 'Params: ' . var_export($params, TRUE));
      $output = $this->reportEngine->requestReport("$report.xml", 'local', 'xml',
        $params, !empty($this->resourceOptions['cached']));
    }
    catch (Exception $e) {
      $code = $e->getCode();
      $status = ($code === 404) ? 'Not Found' : 'Internal Server Error';
      RestObjects::$apiResponse->fail($status, $code === 0 ? 500 : $code, $e->getMessage());
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
   * Load the filter definition for a filter ID.
   *
   * @param int $id
   *   Filter ID.
   *
   * @return array
   *   Filter definition.
   */
  private function loadFilter($id) {
    $filters = RestObjects::$db->select('definition')
      ->from('filters')
      ->where(['id' => $id, 'deleted' => 'f'])
      ->get()->result_array();
    if (count($filters) !== 1) {
      RestObjects::$apiResponse->fail('Internal Server Error', 500, 'Failed to find unique project filter record');
    }
    return json_decode($filters[0]->definition, TRUE);
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
      return $this->loadFilter($filterId);
    }
    else {
      return [];
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
      ->join('filters_users', [
        'filters_users.filter_id' => 'filters.id',
      ])
      ->where([
        'filters.id' => $_GET['filter_id'],
        'filters.deleted' => 'f',
        'filters.defines_permissions' => 't',
        'filters_users.user_id' => RestObjects::$clientUserId,
        'filters_users.deleted' => 'f',
      ])
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
    $this->request = array_merge([
      'page' => 1,
      'page_size' => REST_API_DEFAULT_PAGE_SIZE,
    ], $this->request);
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
      if (isset($this->authConfig) && array_key_exists('allow_cors', $this->authConfig) && $this->authConfig['allow_cors'] === FALSE) {
        return;
      }
      if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS' || $this->authConfig['allow_cors'] === TRUE) {
        $corsSetting = '*';
      }
      elseif (is_array($this->authConfig['allow_cors'])) {
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
    $this->checkElasticsearchRequest();
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      // No need to authenticate OPTIONS request.
      return;
    }
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
        if (method_exists($this, "authenticateUsing$method")) {
          call_user_func([$this, "authenticateUsing$method"]);
        }
        if ($this->authenticated) {
          RestObjects::$authMethod = $method;
          if (!empty(RestObjects::$clientUserId)) {
            // Pass through to ORM.
            global $remoteUserId;
            $remoteUserId = RestObjects::$clientUserId;
          }
          // Double checking required for Elasticsearch proxy.
          if ($this->elasticProxy) {
            if (empty($cfg['resource_options']['elasticsearch'])) {
              kohana::log('debug', "Elasticsearch not enabled for $method authentication");
              RestObjects::$apiResponse->fail('Forbidden', 403, "Elasticsearch not enabled for $method authentication");
            }
            if (in_array($this->elasticProxy, $cfg['resource_options']['elasticsearch'])) {
              // Simple array of ES endpoints with no config.
              RestObjects::$esConfig = [];
            }
            elseif (array_key_exists($this->elasticProxy, $cfg['resource_options']['elasticsearch'])) {
              // Endpoints are keys with array values holding config.
              RestObjects::$esConfig = $cfg['resource_options']['elasticsearch'][$this->elasticProxy];
            }
            else {
              kohana::log('debug', "Elasticsearch request to $this->elasticProxy not enabled for $method");
              RestObjects::$apiResponse->fail('Forbidden', 403, "Elasticsearch request to $this->elasticProxy not enabled for $method authentication");
            }
            if (!empty($this->clientConfig) && (empty($this->clientConfig['elasticsearch']) ||
                !in_array($this->elasticProxy, $this->clientConfig['elasticsearch']))) {
              kohana::log('debug', "Elasticsearch request to $this->elasticProxy not enabled for client");
              RestObjects::$apiResponse->fail('Forbidden', 403, "Elasticsearch request to $this->elasticProxy not enabled for client");
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
      kohana::log('debug', "REST API request did not meet criteria for any valid authentication method");
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
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
      // Sometimes on Apache, necessary to redirect the Auth header into the
      // $_SERVER superglobal.
      // See https://stackoverflow.com/questions/26475885/authorization-header-missing-in-php-post-request.
      return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    return '';
  }

  /**
   * Retrieves the Bearer access token from the Authorization header.
   *
   * @return string
   *   Auth token or empty string.
   */
  private function getBearerAuthToken() {
    $authHeader = $this->getAuthHeader();
    if (stripos($authHeader, 'Bearer ') === 0) {
      return substr($authHeader, 7);
    }
    return '';
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
        ->orderby('public_key', 'ASC')
        ->get()->current();
      if (!$website) {
        // Main URL check failed, so check any staging URLs.
        $urlEscaped = pg_escape_literal($db->getLink(), $url);
        $qry = <<<SQL
          SELECT id, public_key
          FROM websites
          WHERE staging_urls && ARRAY[$urlEscaped::varchar]
        SQL;
        $website = $db->query($qry)->current();
      }
      $cache->set($cacheKey, $website);
    }
    return $website;
  }

  /**
   * Checks that the current user has website permissions.
   *
   * Fails if unauthorized.
   *
   * @param int $websiteId
   *   Website ID to test against.
   * @param int $userId
   *   Warehouse user ID to test.
   */
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
      kohana::log('debug', 'rest_api: Unauthorised - user has no role in website.');
      RestObjects::$apiResponse->fail('Forbidden', 403);
    }
    RestObjects::$clientUserWebsiteRole = $websiteUser->site_role_id;
  }

  /**
   * Implement base64-url decode support for JWT tokens.
   *
   * @param string $str
   *   String to decode.
   *
   * @return string
   *   Decoded response.
   */
  private function base64urlDecode($str) {
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $str));
  }

  /**
   * Checks a JWT token decodes against a public key.
   *
   * Exceptions thrown if not.
   *
   * @param string $token
   *   JWT token.
   * @param string $publicKey
   *   Key to check against.
   */
  private function checkDecodeJwt($token, $publicKey) {
    // Allow for minor clock sync problems.
    JWT\JWT::$leeway = 60;
    try {
      JWT\JWT::decode($token, new JWT\Key($publicKey, 'RS256'));
    }
    catch (JWT\SignatureInvalidException $e) {
      kohana::log('debug', 'Token decode failed');
      kohana::log('debug', $e->getMessage());
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
  }

  /**
   * Extracts the website and payload from a JWT in request header.
   *
   * @return bool
   *   True if successful
   */
  private function decodeJwtPayload() {
    if (isset(RestObjects::$jwtPayloadValues)) {
      return TRUE;
    }
    require_once 'vendor/autoload.php';
    $suppliedToken = $this->getBearerAuthToken();
    if ($suppliedToken && substr_count($suppliedToken, '.') === 2) {
      [$jwtHeader, $jwtPayload, $jwtSignature] = explode('.', $suppliedToken);
      $payload = $this->base64urlDecode($jwtPayload);
      if (!$payload) {
        RestObjects::$apiResponse->fail('Bad request', 400);
      }
      RestObjects::$jwtPayloadValues = json_decode($payload, TRUE);
      if (!RestObjects::$jwtPayloadValues) {
        RestObjects::$apiResponse->fail('Bad request', 400);
      }
      if (empty(RestObjects::$jwtPayloadValues['iss'])) {
        RestObjects::$apiResponse->fail('Bad request', 400);
      }
      $website = $this->getWebsiteByUrl(RestObjects::$jwtPayloadValues['iss']);
      if (!$website) {
        kohana::log('debug', 'Website not found: ' . RestObjects::$jwtPayloadValues['iss']);
        RestObjects::$apiResponse->fail('Unauthorized', 401);
      }
      RestObjects::$clientWebsitePublicKey = $website->public_key;
      if (isset(RestObjects::$jwtPayloadValues['email_verified']) && !RestObjects::$jwtPayloadValues['email_verified']) {
        kohana::log('debug', 'Payload email unverified');
        RestObjects::$apiResponse->fail('Unauthorized', 401);
      }
      RestObjects::$clientWebsiteId = $website->id;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Attempts to authenticate as a user using a JWT access token.
   */
  private function authenticateUsingJwtUser() {
    if ($this->decodeJwtPayload() && !isset(RestObjects::$jwtPayloadValues['http://indicia.org.uk/client:username'])
        && !empty(RestObjects::$clientWebsitePublicKey)) {
      // Need a valid website public key.
      $this->checkDecodeJwt($this->getBearerAuthToken(), RestObjects::$clientWebsitePublicKey);
      if (isset(RestObjects::$jwtPayloadValues['http://indicia.org.uk/user:id'])) {
        $this->checkWebsiteUser(RestObjects::$clientWebsiteId, RestObjects::$jwtPayloadValues['http://indicia.org.uk/user:id']);
        RestObjects::$clientUserId = RestObjects::$jwtPayloadValues['http://indicia.org.uk/user:id'];
        // If authenticated as a user, change default scope. Note that an admin
        // user of the website can access other records.
        RestObjects::$scope = RestObjects::$clientUserWebsiteRole == 3 || $this->resourceName === 'reports' ? 'userWithinWebsite' : 'website';
      }
      // Allow URL parameter to override default reporting scope as long as
      // this scope has been claimed by the token. We allow scope as a standard
      // claim or custom claim and as an array or space separated string for
      // wider compatibility with auth servers.
      if (isset(RestObjects::$jwtPayloadValues['http://indicia.org.uk/scope']) && !isset(RestObjects::$jwtPayloadValues['scope'])) {
        RestObjects::$jwtPayloadValues['scope'] = RestObjects::$jwtPayloadValues['http://indicia.org.uk/scope'];
      }
      if (!empty(RestObjects::$jwtPayloadValues['scope'])) {
        $allowedScopes = is_array(RestObjects::$jwtPayloadValues['scope']) ? RestObjects::$jwtPayloadValues['scope'] : explode(' ', RestObjects::$jwtPayloadValues['scope']);
        if (!empty($_GET['scope'])) {
          if (!in_array($_GET['scope'], $allowedScopes)) {
            RestObjects::$apiResponse->fail('Forbidden', 403, 'Attempt to access disallowed scope');
          }
          RestObjects::$scope = $_GET['scope'];
        }
        else {
          // The first specified scope in the token is default if not specified
          // in query parameters. Any non-recognised scope will just map to a
          // user request within the website (the most restrictive).
          RestObjects::$scope = in_array($allowedScopes[0], ALLOWED_SCOPES) ? $allowedScopes[0] : 'userWithinWebsite';
        }
      }
      $this->authenticated = TRUE;
    }
  }

  /**
   * Attempts to authenticate as a user using a JWT access token.
   *
   * @todo Allow claims for full precision data.
   */
  private function authenticateUsingJwtClient() {
    if ($this->decodeJwtPayload() && isset(RestObjects::$jwtPayloadValues['http://indicia.org.uk/client:username']) && !empty($_REQUEST['proj_id'])) {
      $clientSystemId = RestObjects::$jwtPayloadValues['http://indicia.org.uk/client:username'];
      $r = $this->getRestConnectionFromDb($clientSystemId, $_REQUEST['proj_id']);
      // Validate the JWT.
      $this->checkDecodeJwt($this->getBearerAuthToken(), $r->public_key);
      if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
        RestObjects::$apiResponse->fail('Method Not Allowed', 405, 'Connection is read only.');
      }
      $this->applyConnectionSettingsFromRestConnectionInDb($r);
      $this->authenticated = TRUE;
    }
  }

  /**
   * Attempts to authenticate using the HMAC client protocol.
   */
  private function authenticateUsingHmacClient() {
    $authHeader = $this->getAuthHeader();
    if (substr_count($authHeader, ':') === 3) {
      [$u, $clientSystemId, $h, $supplied_hmac] = explode(':', $authHeader);
      $config = Kohana::config('rest.clients');
      if ($u === 'USER' && $h === 'HMAC' && array_key_exists($clientSystemId, $config)) {
        $protocol = $this->isHttps ? 'https' : 'http';
        $request_url = "$protocol://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $correct_hmac = hash_hmac("sha1", $request_url, $config[$clientSystemId]['shared_secret'], $raw_output = FALSE);
        if ($supplied_hmac === $correct_hmac) {
          RestObjects::$clientSystemId = $clientSystemId;
          $this->projects = $config[$clientSystemId]['projects'];
          $this->clientConfig = $config[$clientSystemId];
          unset($this->clientConfig['shared_secret']);
          if (!empty($_REQUEST['proj_id'])) {
            RestObjects::$clientWebsiteId = $this->projects[$_REQUEST['proj_id']]['website_id'];
            // For client systems, the project defines how records are allowed to be
            // shared with this client.
            RestObjects::$scope = $this->projects[$_REQUEST['proj_id']]['sharing'];
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
   * Attempts to authenticate using the HMAC website protocol.
   */
  private function authenticateUsingHmacWebsite() {
    $authHeader = $this->getAuthHeader();
    if (substr_count($authHeader, ':') === 3) {
      [$u, $websiteId, $h, $supplied_hmac] = explode(':', $authHeader);
      if ($u === 'WEBSITE_ID' && $h === 'HMAC') {
        // Input validation.
        if (!preg_match('/^\d+$/', $websiteId)) {
          RestObjects::$apiResponse->fail('Unauthorized', 401, 'Website ID incorrect format.');
        }
        $websites = RestObjects::$db->select('password')
          ->from('websites')
          ->where(['id' => $websiteId])
          ->get()->result_array();
        if (count($websites) === 1) {
          $protocol = $this->isHttps ? 'https' : 'http';
          $request_url = "$protocol://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
          $correct_hmac = hash_hmac("sha1", $request_url, $websites[0]->password, $raw_output = FALSE);
          if ($supplied_hmac === $correct_hmac) {
            RestObjects::$clientWebsiteId = $websiteId;
            // Scope mode and user_id allowed as URL parameters, as if
            // intercepted and changed the HMAC breaks.
            if (!empty($_GET['scope'])) {
              RestObjects::$scope = $_GET['scope'];
            }
            if (!empty($_GET['user_id'])) {
              RestObjects::$clientUserId = $_GET['user_id'];
            }
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
   * Attempts to authenticate using the direct user protocol.
   */
  private function authenticateUsingDirectUser() {
    $authHeader = $this->getAuthHeader();
    // 6 or 8 colon separated tokens possible in auth header.
    $tokens = explode(':', $authHeader);
    if (in_array(count($tokens), [6, 8])) {
      if ($tokens[0] !== 'USER_ID' || $tokens[2] !== 'WEBSITE_ID' || $tokens[4] !== 'SECRET' || (count($tokens) === 8 && $tokens[6] !== 'SCOPE')) {
        // Not a valid header for this auth method.
        return;
      }
      $userId = $tokens[1];
      $websiteId = $tokens[3];
      $password = $tokens[5];
      $scope = count($tokens) === 8 ? $tokens[7] : NULL;
    }
    elseif (kohana::config('rest.allow_auth_tokens_in_url') === TRUE &&
          !empty($_GET['user_id']) && !empty($_GET['website_id']) && !empty($_GET['secret'])) {
      $userId = $_GET['user_id'];
      $websiteId = $_GET['website_id'];
      $password = $_GET['secret'];
      $scope = !empty($_GET['scope']) ? $_GET['scope'] : NULL;
    }
    else {
      return;
    }
    // Input validation.
    if (!preg_match('/^\d+$/', $userId) || !preg_match('/^\d+$/', $websiteId)) {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'User ID or website ID incorrect format.');
    }
    $users = RestObjects::$db->select('password, site_role_id')
      ->from('users')
      ->join('users_websites', 'users_websites.user_id', 'users.id')
      ->where([
        'users.id' => $userId,
        'users_websites.website_id' => $websiteId,
        'users.deleted' => 'f',
        'users_websites.banned' => 'f',
      ])
      ->orderby('users_websites.site_role_id', 'ASC')
      ->limit(1)
      ->get()->result_array(FALSE);
    if (count($users) !== 1) {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unrecognised user ID or password.');
    }
    $auth = new Auth();
    if ($auth->checkPasswordAgainstHash($password, $users[0]['password'])) {
      RestObjects::$clientWebsiteId = $websiteId;
      RestObjects::$clientUserId = $userId;
      RestObjects::$clientUserWebsiteRole = $users['0']['site_role_id'];
      RestObjects::$scope = $scope ?? (RestObjects::$clientUserWebsiteRole == 3 ? 'userWithinWebsite' : 'website');
      $this->authenticated = TRUE;
    }
    else {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Incorrect password for user.');
    }
  }

  /**
   * Attempts to authenticate using the direct client protocol.
   */
  private function authenticateUsingDirectClient() {
    $config = Kohana::config('rest.clients');
    $authHeader = $this->getAuthHeader();
    if ($authHeader && substr_count($authHeader, ':') === 3) {
      [$u, $clientSystemId, $h, $secret] = explode(':', $authHeader);
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
    // Configuration allowed either in config file or database. Will eventually
    // move to DB only.
    if (array_key_exists($clientSystemId, $config)) {
      $this->authenticateUsingDirectClientInConfigFile($clientSystemId, $secret);
    }
    else {
      $this->authenticateUsingDirectClientInDb($clientSystemId, $secret);
    }
  }

  /**
   * Authenticate using a client and project specified in the rest config file.
   *
   * @param string $clientSystemId
   *   Name of client given in the config file.
   * @param string $secret
   *   Provided secret to check.
   */
  private function authenticateUsingDirectClientInConfigFile($clientSystemId, $secret) {
    $config = Kohana::config('rest.clients');
    if ($secret !== $config[$clientSystemId]['shared_secret']) {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Incorrect secret');
    }
    RestObjects::$clientSystemId = $clientSystemId;
    $this->projects = $config[$clientSystemId]['projects'] ?? [];
    $this->clientConfig = $config[$clientSystemId];
    unset($this->clientConfig['shared_secret']);
    // Taxon observations and annotations resource end-points will need a
    // proj_id if using client system based authorisation.
    if (($this->resourceName === 'taxon-observations' || $this->resourceName === 'annotations') &&
        (empty($_REQUEST['proj_id']))) {
      RestObjects::$apiResponse->fail('Bad request', 400, 'Missing proj_id parameter.');
    }
    if (!empty($_REQUEST['proj_id'])) {
      if (empty($this->projects[$_REQUEST['proj_id']])) {
        RestObjects::$apiResponse->fail('Bad request', 400, 'Invalid proj_id parameter.');
      }
      $projectConfig = $this->projects[$_REQUEST['proj_id']];
      RestObjects::$clientWebsiteId = $projectConfig['website_id'];
      // The client project config can override the resource options, e.g.
      // access to summary or featured reports.
      if (isset($projectConfig['resource_options']) &&
          isset($projectConfig['resource_options'][$this->resourceName])) {
        $this->resourceOptions = $projectConfig['resource_options'][$this->resourceName];
      }
      // For client systems, the project defines how records are allowed to be
      // shared with this client.
      RestObjects::$scope = $projectConfig['sharing'];
    }
    $this->authenticated = TRUE;
  }

  /**
   * Authenticate using a client and project specified in the database.
   *
   * @param string $clientSystemId
   *   Name of client given in the config file.
   * @param string $secret
   *   Provided secret to check.
   */
  private function authenticateUsingDirectClientInDb($clientSystemId, $secret) {
    if (empty($_REQUEST['proj_id'])) {
      // Can't authenticate without proj_id.
      return;
    }
    $r = $this->getRestConnectionFromDb($clientSystemId, $_REQUEST['proj_id']);
    // Stored password is hashed, so check the hash.
    if (!password_verify($secret, $r->secret)) {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Incorrect secret');
    }
    // This type of connection is read only as we don't have a user ID.
    // @todo A default user ID could be an option in the connection metadata.
    $blockedMethods = ['PUT', 'DELETE'];
    if (!$this->elasticProxy) {
      $blockedMethods[] = 'POST';
    }
    if (in_array($_SERVER['REQUEST_METHOD'], $blockedMethods)) {
      RestObjects::$apiResponse->fail('Method Not Allowed', 405, 'Connection is read only.');
    }
    $this->applyConnectionSettingsFromRestConnectionInDb($r);
    $this->authenticated = TRUE;
  }

  /**
   * Load details of a REST connection from the DB.
   *
   * @param string $clientSystemId
   *   Client name.
   * @param string $projId
   *   Proj_id parameter.
   *
   * @return object
   *   Object containing properties for details of the connection and it's
   *   privileges.
   */
  private function getRestConnectionFromDb($clientSystemId, $projId) {
    $projIdParam = pg_escape_literal(RestObjects::$db->getLink(), $projId);
    $usernameParam = pg_escape_literal(RestObjects::$db->getLink(), $clientSystemId);
    $sql = <<<SQL
SELECT c.website_id,
  c.username,
  c.secret,
  c.public_key,
  cc.proj_id,
  cc.allow_reports,
  cc.limit_to_reports,
  cc.allow_data_resources,
  cc.limit_to_data_resources,
  cc.sharing,
  cc.allow_confidential,
  cc.allow_sensitive,
  cc.allow_unreleased,
  cc.full_precision_sensitive_records,
  cc.filter_id
FROM rest_api_clients c
JOIN rest_api_client_connections cc ON cc.rest_api_client_id=c.id AND cc.deleted=false
  AND cc.proj_id=$projIdParam
WHERE c.deleted=false
AND c.username=$usernameParam
SQL;
    $r = RestObjects::$db->query($sql)->current();
    if (!$r) {
      // No matching authentication in db.
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Invalid client system ID');
    }
    return $r;
  }

  /**
   * Handle the connection options loaded from the database.
   *
   * @param object $r
   *   Connection data loaded from database.
   */
  private function applyConnectionSettingsFromRestConnectionInDb($r) {
    RestObjects::$clientWebsiteId = $r->website_id;
    RestObjects::$clientSystemId = $r->username;
    // Setup $this->resourceOptions to define what is allowed.
    $this->resourceOptions = [
      'allow_confidential' => $r->allow_confidential === 't',
      'allow_sensitive' => $r->allow_sensitive === 't',
      'allow_unreleased' => $r->allow_unreleased === 't',
      'full_precision_sensitive_records' => $r->full_precision_sensitive_records === 't',
      'filter_id' => $r->filter_id,
    ];
    // Map connection sharing to scope.
    RestObjects::$scope = warehouse::sharingCodeToTerm($r->sharing);
    if ($this->resourceName === 'reports') {
      // Reports API requested.
      if ($r->allow_reports === 'f') {
        RestObjects::$apiResponse->fail('Unauthorized', 401, 'Access to reports unauthorised');
      }
      if ($r->limit_to_reports) {
        // Convert from pg array format.
        $this->limitToReports = explode(',', strtolower(trim($r->limit_to_reports, '{}')));
      }
    }
    elseif (!$this->elasticProxy) {
      // Not reports and not Elastic, so a data resource.
      if ($r->allow_data_resources === 'f') {
        RestObjects::$apiResponse->fail('Unauthorized', 401, 'Access to data resources unauthorised');
      }
      if ($r->limit_to_data_resources) {
        // Convert from pg array format.
        $this->limitToDataResources = explode(',', strtolower(trim($r->limit_to_data_resources, '{}')));
      }
    }
    $this->projects = [
      $r->proj_id => [],
    ];
  }

  /**
   * Attempts to authenticate using the direct website protocol.
   */
  private function authenticateUsingDirectWebsite() {
    $authHeader = $this->getAuthHeader();
    // 4, 6 or 8 colon separated tokens possible in auth header.
    $tokens = explode(':', $authHeader);
    if (in_array(count($tokens), [4, 6, 8])) {
      if ($tokens[0] !== 'WEBSITE_ID' || $tokens[2] !== 'SECRET' || (count($tokens) >= 6 && $tokens[4] !== 'SCOPE') || (count($tokens) === 8 && $tokens[6] !== 'USER_ID')) {
        // Not a valid header for this auth method.
        return;
      }
      $websiteId = $tokens[1];
      $password = $tokens[3];
      $scope = count($tokens) >= 6 ? $tokens[5] : NULL;
      $userId = count($tokens) === 8 ? $tokens[7] : NULL;
    }
    elseif (kohana::config('rest.allow_auth_tokens_in_url') === TRUE &&
          !empty($_GET['website_id']) && !empty($_GET['secret'])) {
      $websiteId = $_GET['website_id'];
      $password = $_GET['secret'];
      $scope = !empty($_GET['scope']) ? $_GET['scope'] : NULL;
      $userId = !empty($_GET['user_id']) ? $_GET['user_id'] : NULL;
    }
    else {
      return;
    }
    // Input validation.
    if (($userId !== NULL && !preg_match('/^\d+$/', $userId)) || !preg_match('/^\d+$/', $websiteId)) {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'User ID or website ID incorrect format.');
    }
    $password = pg_escape_string(RestObjects::$db->getLink(), $password);
    $websites = RestObjects::$db->select('id')
      ->from('websites')
      ->where(['id' => $websiteId, 'password' => $password])
      ->get()->result_array();
    if (count($websites) === 1) {
      RestObjects::$clientWebsiteId = $websiteId;
      if ($userId !== NULL) {
        // @todo Is this user a member of the website?
        RestObjects::$clientUserId = $userId;
      }
      RestObjects::$scope = $scope === NULL ? 'reporting' : $scope;
      $this->authenticated = TRUE;
    }
    else {
      RestObjects::$apiResponse->fail('Unauthorized', 401, 'Unrecognised website ID or password.');
    }
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

  /**
   * Request handler for POST /custom-verification-rulesets/{id}/run-request.
   *
   * Requests a run of a custom verification ruleset, using the filter supplied
   * in the POST body.
   */
  public function customVerificationRulesetsPostIdRunRequest() {
    $rulesetId = $this->uri->segment(4);
    $postRaw = file_get_contents('php://input');
    $postObj = empty($postRaw) ? [] : json_decode($postRaw, TRUE);
    $query = $postObj['query'] ?? [];
    // User ID may be as authenticated, or less ideally, from a query
    // parameter.
    $userId = RestObjects::$clientUserId ?? $_GET['user_id'];
    try {
      $es = new RestApiElasticsearch($_GET['alias']);
      $requestBody = customVerificationRules::buildCustomRuleRequest($rulesetId, $query, $userId, $es->getMajorVersion());
      $es->elasticRequest($requestBody, 'json', FALSE, '_update_by_query', TRUE);
    }
    catch (Exception $e) {
      error_logger::log_error('Exception whilst attempting to run a custom verification ruleset.', $e);
      if ($e instanceof RestApiNotifyClient) {
        RestObjects::$apiResponse->fail('Bad Request', 400, $e->getMessage());
      }
      elseif (!$e instanceof RestApiAbort) {
        RestObjects::$apiResponse->fail('Internal server error', 500, $e->getMessage());
      }
    }
  }

  /**
   * Request handler for POST /custom-verification-rulesets/clear-flags.
   *
   * Clears a user's custom verification rule check flags from the filter
   * supplied in the POST body.
   */
  public function customVerificationRulesetsPostClearFlags() {
    $postRaw = file_get_contents('php://input');
    $postObj = empty($postRaw) ? [] : json_decode($postRaw, TRUE);
    $query = $postObj['query'] ?? [];
    // User ID may be as authenticated, or less ideally, from a query
    // parameter.
    $userId = RestObjects::$clientUserId ?? $_GET['user_id'];
    try {
      $requestBody = customVerificationRules::buildClearFlagsRequest($query, $userId);
      $es = new RestApiElasticsearch($_GET['alias']);
      $es->elasticRequest($requestBody, 'json', FALSE, '_update_by_query', TRUE);
    }
    catch (Exception $e) {
      error_logger::log_error('Exception whilst attempting to clear custom verification rule flags.', $e);
      if (!$e instanceof RestApiAbort) {
        RestObjects::$apiResponse->fail('Internal server error', 500, $e->getMessage());
      }
    }
  }

  /**
   * Request handler for POST /rest/media-queue.
   *
   * Allows media to be cached on the server prior to submitting the data the
   * media should be attached to.
   */
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
      $types = implode(',', array_map(function ($a) {
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
      $typeParts = explode('/', $file['type']);
      $fileName = uniqid('', TRUE) . '.' . $typeParts[1];
      $subdir = $this->getMediaSubdir();
      $dest = DOCROOT . "upload-queue/$subdir";
      if (!is_dir($dest)) {
        mkdir($dest, 0755, TRUE);
      }
      upload::save($file, $subdir . $fileName, 'upload-queue');
      $response[$key] = [
        'name' => "$subdir$fileName",
        'tempPath' => url::base() . "upload-queue/$subdir$fileName",
      ];
    }
    RestObjects::$apiResponse->succeed($response);
  }

  /**
   * Works out a sub-directory structure for a new queued media file.
   *
   * Based on the current timestamp.
   *
   * @return string
   *   Sub-folder structure, e.g. '60/20/15/', including trailing slash.
   */
  private function getMediaSubdir() {
    $subdir = '';
    // $levels = Kohana::config('upload.use_sub_directory_levels');
    $levels = 3;
    $ts = time();
    for ($i = 0; $i < $levels; $i++) {
      $dirname = substr($ts, 0, 2);
      if (strlen($dirname)) {
        $subdir .= $dirname . '/';
        $ts = substr($ts, 2);
      }
    }
    return $subdir;
  }

  /**
   * End-point to GET an list of occurrence_media.
   */
  public function occurrenceMediaGet() {
    rest_crud::readList('occurrence_medium', 't3.website_id=' . (int) RestObjects::$clientWebsiteId, $this->needToFilterToUser());
  }

  /**
   * End-point to GET a occurrence_media by ID.
   *
   * @param int $id
   *   Occurrence media ID.
   */
  public function occurrenceMediaGetId($id) {
    rest_crud::read(
      'occurrence_medium',
      $id,
      't3.website_id=' . RestObjects::$clientWebsiteId,
      $this->needToFilterToUser());
  }

  /**
   * API end-point to POST an occurrence_media item to create.
   */
  public function occurrenceMediaPost() {
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    $r = rest_crud::create('occurrence_medium', $item);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to PUT to an existing occurrence_medium to update.
   */
  public function occurrenceMediaPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    // Update only allowed on this website.
    $preconditions = ['occurrence.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if ($this->needToFilterToUser()) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    $r = rest_crud::update('occurrence_medium', $id, $putArray, $preconditions);
    echo json_encode($r);
  }

  /**
   * API end-point to DELETE an occurrence_medium item.
   *
   * Will only be deleted if the occurrence_medium was created by the current
   * user.
   *
   * @param int $id
   *   Occurrence medium ID to delete.
   */
  public function occurrenceMediaDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    // Update only allowed on this website.
    $preconditions = ['occurrence.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    // Delete as long as created by this user.
    rest_crud::delete('occurrence_medium', $id, $preconditions);
  }

  /**
   * Apply filters to query when accessing occurrences.
   */
  private function getExtraFiltersForOccurrences() {
    $extraFilters = [
      't1.website_id=' . RestObjects::$clientWebsiteId,
    ];
    // Read disallowed on confidential unless specifically allowed.
    if (empty($this->resourceOptions['allow_confidential'])) {
      $extraFilters[] = 't1.confidential=false';
    }
    // Read allowed on sensitive unless specifically blocked.
    if (isset($this->resourceOptions['allow_sensitive']) && $this->resourceOptions['allow_sensitive'] === FALSE) {
      $extraFilters[] = 't1.sensitivity_precision IS NULL';
    }
    // Read allowed on unreleased unless specifically blocked.
    if (isset($this->resourceOptions['allow_unreleased']) && $this->resourceOptions['allow_unreleased'] === FALSE) {
      $extraFilters[] = "t1.release_status='R'";
    }
    return implode(' AND ', $extraFilters);
  }

  /**
   * Ensure resource options honoured when updating an occurrence.
   *
   * @return array
   *   Field check key value pairs.
   */
  private function getFieldChecksForOccurrencesPut() {
    $fieldChecks = [
      'website_id' => (int) RestObjects::$clientWebsiteId,
    ];
    // Update disallowed on confidential unless specifically allowed.
    if (empty($this->resourceOptions['allow_confidential'])) {
      $fieldChecks['confidential'] = 'f';
    }
    // Update allowed on sensitive unless specifically blocked.
    if (isset($this->resourceOptions['allow_sensitive']) && $this->resourceOptions['allow_sensitive'] === FALSE) {
      $fieldChecks['sensitivity_precision'] = NULL;
    }
    // Update allowed on unreleased unless specifically blocked.
    if (isset($this->resourceOptions['allow_unreleased']) && $this->resourceOptions['allow_unreleased'] === FALSE) {
      $fieldChecks['release_status'] = 'R';
    }
    if ($this->needToFilterToUser()) {
      $fieldChecks['created_by_id'] = (int) RestObjects::$clientUserId;
    }
    return $fieldChecks;
  }

  /**
   * End-point to GET an list of occurrences.
   */
  public function occurrencesGet() {
    rest_crud::readList('occurrence', $this->getExtraFiltersForOccurrences(), $this->needToFilterToUser());
  }

  /**
   * End-point to GET an occurrence by ID.
   *
   * @param int $id
   *   Occurrence ID.
   */
  public function occurrencesGetId($id) {
    rest_crud::read('occurrence', $id, $this->getExtraFiltersForOccurrences(), $this->needToFilterToUser());
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
      $sampleCheck = RestObjects::$db->query(<<<SQL
        SELECT count(*)
        FROM samples
        WHERE id=?
        AND deleted=false
        AND created_by_id=?
      SQL,
        [$values['sample_id'], RestObjects::$clientUserId]
      )->current()->count;
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
   * @todo Website ID precondition could respect editing sharing mode.
   *
   * @param int $id
   *   Occurrence ID.
   */
  public function occurrencesPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    $r = rest_crud::update('occurrence', $id, $putArray, $this->getFieldChecksForOccurrencesPut());
    echo json_encode($r);
  }

  /**
   * API end-point to DELETE an occurrence.
   *
   * Will only be deleted if the occurrence was created by the current user.
   *
   * @todo Website ID precondition could respect editing sharing mode.
   *
   * @param int $id
   *   Occurrence ID to delete.
   */
  public function occurrencesDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    // Delete only allowed on this website.
    $preconditions = ['website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    rest_crud::delete('occurrence', $id, $preconditions);
  }

  /**
   * End-point to GET record newness badges.
   *
   * Checks if a wildlife record is new to the species, new to a specific
   * grid square, new in the current year, or new within a specific group.
   *
   * Parameters:
   * - external_key (required): The taxon.accepted_taxon_id
   * - lat (optional): Latitude of the record (WGS84)
   * - lon (optional): Longitude of the record (WGS84)
   * - grid_square_size (optional): '1km', '2km', or '10km' (requires lat/lon)
   * - year (optional): Year to check for newness
   * - group_id (optional): Group ID to filter by
   */
  public function occurrencesGetCheckNewness() {
    try {
      // Extract parameters from GET request.
      $externalKey = $_GET['external_key'] ?? NULL;
      $lat = isset($_GET['lat']) ? (float) $_GET['lat'] : NULL;
      $lon = isset($_GET['lon']) ? (float) $_GET['lon'] : NULL;
      $gridSquareSize = $_GET['grid_square_size'] ?? NULL;
      $year = isset($_GET['year']) ? (int) $_GET['year'] : NULL;
      $groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : NULL;

      // Check newness and get badges.
      $badges = rest_occurrence_newness_checker::checkNewness(
        $externalKey,
        $lat,
        $lon,
        $gridSquareSize,
        $year,
        $groupId
      );

      // Return the response.
      RestObjects::$apiResponse->succeed($badges);
    }
    catch (Exception $e) {
      $httpCode = $e->getCode() ?: 400;
      RestObjects::$apiResponse->fail(
        $httpCode === 400 ? 'Bad Request' : 'Internal Server Error',
        $httpCode,
        $e->getMessage()
      );
    }
  }

  /**
   * Covert groups GET view parameter to a filter.
   *
   * @param string $view
   *   View parameter, one of member, joinable, all_available, pending.
   *
   * @return string
   *   SQL filter.
   */
  private function getGroupsViewParameterFilter($view) {
    $filters = [];
    if (in_array($view, ['member', 'all_available'])) {
      $filters[] = 't1.id IN (SELECT group_id FROM groups_users gu WHERE gu.user_id=' . RestObjects::$clientUserId . ' AND gu.deleted=false AND gu.pending=false)';
    }
    if ($view == 'all_available') {
      $filters[] = "t1.joining_method IN ('P', 'R')";
    }
    elseif ($view === 'joinable') {
      $filters[] = "t1.joining_method IN ('P', 'R') AND t1.id NOT IN (SELECT group_id FROM groups_users gu WHERE gu.user_id=" . RestObjects::$clientUserId . ' AND gu.deleted=false)';
    }
    elseif ($view === 'pending') {
      $filters[] = 't1.id IN (SELECT group_id FROM groups_users gu WHERE gu.user_id=' . RestObjects::$clientUserId . ' AND gu.deleted=false AND gu.pending=true)';
    }
    return 't1.website_id=' . RestObjects::$clientWebsiteId . ' AND (' . implode(' OR ', $filters) . ')';
  }

  /**
   * API endpoint to GET a list of groups.
   *
   * Set the view parameter to control the list returned from the following
   * options:
   * * member - those the current user is a member of (default).
   * * joinable - those the current user is not a member of but can join.
   * * all_available - those the user is either a member of or can join.
   */
  public function groupsGet() {
    $view = $_GET['view'] ?? 'member';
    rest_crud::readList('group', $this->getGroupsViewParameterFilter($view), FALSE);
  }

  /**
   * API endpoint to GET a group by ID.
   *
   * @param int
   *   Group ID.
   */
  public function groupsGetId(int $id) {
    // Can fetch any group you are a member of, or if it is publicly visible.
    rest_crud::read('group', $id, $this->getGroupsViewParameterFilter('all_available'), FALSE);
  }

  /**
   * API endpoint to retrieve the list of recording sites for a group.
   *
   * @param int $id
   *   Group ID.
   */
  public function groupsGetIdLocations(int $id) {
    // Can only fetch locations for a group you are a member of, or if it is
    // publicly visible.
    $filters = [
      "t2.id=$id",
      't2.website_id=' . RestObjects::$clientWebsiteId,
      "(t2.joining_method IN ('P', 'R') OR t2.id IN (SELECT group_id FROM groups_users gu WHERE gu.user_id=" . RestObjects::$clientUserId . ' AND gu.deleted=false))',
    ];
    $extraFilter = implode(' AND ', $filters);
    rest_crud::readList('groups_location', $extraFilter, FALSE);
  }

  /**
   * API endpoint to post a location which will be linked to a group.
   *
   * @param int $id
   *   Group ID.
   */
  public function groupsPostIdLocations(int $id) {
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    $existingUser = RestObjects::$db->query("SELECT id FROM groups_users WHERE group_id=? AND deleted=false AND pending=false AND user_id=?", [$id, RestObjects::$clientUserId])->current();
    if (!$existingUser) {
      RestObjects::$apiResponse->fail('Forbidden', 403, 'Cannot post locations into a group you are not a member of.');
    }
    if (isset($item['values']) && !empty($item['values']['id'])) {
      // Payload contains an existing location ID, so just join it to the group.
      $joinItem = [
        'values' => [
          'group_id' => $id,
          'location_id' => $item['values']['id'],
        ],
      ];
      $r = rest_crud::create('groups_location', $joinItem);
    }
    else {
      // Add sub-model for the linked group.
      $item['groups_locations'] = [
        ['values' => ['group_id' => $id]],
      ];
      $r = rest_crud::create('location', $item);
    }
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API endpoint to retrieve the list of member users for a group.
   *
   * @param int $id
   *   Group ID.
   */
  public function groupsGetIdUsers($id) {
    // Can only fetch users for a group you are an admin of, or the list is
    // limited to just yourself.
    $filters = [
      "t2.id=$id",
      't2.website_id=' . RestObjects::$clientWebsiteId,
      "(t2.id IN (SELECT group_id FROM groups_users gu WHERE gu.user_id=" . RestObjects::$clientUserId . ' AND gu.deleted=false AND gu.administrator=true) OR t1.user_id=' . RestObjects::$clientUserId . ')',
    ];
    $extraFilter = implode(' AND ', $filters);
    rest_crud::readList('groups_user', $extraFilter, FALSE);
  }

  /**
   * API endpoint to post users into a group.
   *
   * @param int $id
   *   Group ID.
   */
  public function groupsPostIdUsers($id) {
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    if (!isset($item['values']['id']) || !preg_match('/^\d+$/', $item['values']['id'])) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Invalid or missing user ID');
    }
    $addingUserId = $item['values']['id'];
    $authUserId = RestObjects::$clientUserId;
    $qry = <<<SQL
SELECT gu1.user_id as existing_user_id, gu2.administrator as auth_user_is_admin, g.created_by_id, g.joining_method
FROM groups g
LEFT JOIN groups_users gu1 ON gu1.group_id=g.id AND gu1.deleted=false AND gu1.pending=false AND gu1.user_id=?
LEFT JOIN groups_users gu2 ON gu2.group_id=g.id AND gu2.deleted=false AND gu2.pending=false AND gu2.user_id=?
WHERE g.id=$id AND g.deleted=false
SQL;
    $groupUserInfo = RestObjects::$db->query($qry, [$addingUserId, $authUserId])->current();
    kohana::log('debug', 'guInfo: ' . var_export($groupUserInfo, TRUE));
    if ($groupUserInfo->existing_user_id) {
      // Existing user. Can't re-add themselves.
      if ($addingUserId == $authUserId) {
        RestObjects::$apiResponse->fail('Conflict', 409, 'You are already a user of this group.');
      }
      // Only admin can add others.
      if ($groupUserInfo->auth_user_is_admin === 'f') {
        RestObjects::$apiResponse->fail('Forbidden', 403, 'You cannot add other users to a group you are not admin of');
      }
      // Default administrator=false but allow override.
      $defaults = [
        'administrator' => 'f',
        // If joining is by request, default pending=true but allow override.
        'pending' => $groupUserInfo->joining_method === 'R' ? 't' : 'f',
      ];
      $item['values'] = array_merge($defaults, $item['values']);
    }
    elseif ($groupUserInfo->auth_user_is_admin === 't') {
      // Group admin can add other users without restriction.
    }
    else {
      // New user can only add themself.
      if ($item['values']['id'] != RestObjects::$clientUserId) {
        RestObjects::$apiResponse->fail('Forbidden', 403, 'You cannot add other users to a group you are not a member of');
      }
      $requestToAddAdminUser = isset($item['values']['administrator']) && $item['values']['administrator'] !== 'f';
      // Cannot add self as admin, unless also the group creator.
      if ($requestToAddAdminUser && $groupUserInfo->created_by_id != RestObjects::$clientUserId) {
        RestObjects::$apiResponse->fail('Forbidden', 403, 'You cannot add yourself as an admin user to a group.');
      }
      // If not admin, then group must be public or by request.
      if (!$requestToAddAdminUser && in_array($groupUserInfo->joining_method, ['I', 'A'])) {
        RestObjects::$apiResponse->fail('Forbidden', 403, 'You cannot add yourself as to an invite only or admin managed (private) group.');
      }
      if (!$requestToAddAdminUser && in_array($groupUserInfo->joining_method, ['I', 'R'])) {
        $item['values']['pending'] = 't';
      }
    }
    if (isset($item['values']) && !empty($item['values']['id'])) {
      // Payload contains an existing location ID, so just join it to the group.
      $joinItem = [
        'values' => [
          'group_id' => $id,
          'user_id' => $item['values']['id'],
          'pending' => $item['values']['pending'] ?? 'f',
          'administrator' => $item['values']['administrator'] ?? 'f',
        ],
      ];
      $r = rest_crud::create('groups_user', $joinItem);
      echo json_encode($r);
      http_response_code(201);
      header("Location: $r[href]");
    }
  }

  /**
   * API endpoint to delete users from a group.
   *
   * @param int $id
   *   Group ID.
   */
  public function groupsDeleteIdUsersId($id) {
    $userId = $this->uri->last_segment();
    // User ID must be same as logged in user, or logged in user must be group
    // admin.
    if ($userId != RestObjects::$clientUserId) {
      $authUserId = RestObjects::$clientUserId;
      $authUserIsAdmin = RestObjects::$db->query(
        "SELECT id FROM groups_users WHERE group_id=? AND user_id=? AND deleted=false AND administrator=true",
        [$id, $authUserId]
      )->current();
      if (!$authUserIsAdmin) {
        RestObjects::$apiResponse->fail('Forbidden', 403, 'You cannot add users to a group you do not administer.');
      }
    }
    // Select the record.
    $guId = RestObjects::$db->query(
      "SELECT id FROM groups_users WHERE group_id=? AND user_id=? AND deleted=false",
      [$id, $userId]
    )->current();
    if (!$guId) {
      RestObjects::$apiResponse->fail('Not found', 404, 'User is not a member of the group.');
    }
    kohana::log('debug', "Deleting user $userId (groups_user_id $guId->id)");
    rest_crud::delete('groups_user', $guId->id);
  }

  /**
   * End-point to GET a list of locations.
   *
   * Returns locations for user of website plus public locations.
   * Use parameters in the query string to limit returned values. E.g.
   * ?public=true&location_type_id=<nnn> to get public locations of type <nnn>
   * ?public=false to only get locations for user of website.
   */
  public function locationsGet() {
    // Make a filter for locations for this website and user (if available).
    $websiteFilter = 't2.website_id=' . RestObjects::$clientWebsiteId;
    if ($this->needToFilterToUser()) {
      $userFilter = 't1.created_by_id=' . RestObjects::$clientUserId;
      $webUserFilter = "($websiteFilter AND $userFilter)";
    }
    else {
      // Not user limited, so allow website's locations.
      $webUserFilter = $websiteFilter;
    }
    // Make a filter for public locations available to all users and websites.
    $publicFilter = 't1.public=true';
    // Allow both types of location.
    $extraFilter = "($webUserFilter OR $publicFilter)";
    rest_crud::readList('location', $extraFilter, FALSE);
  }

  /**
   * API end-point to retrieve a location by ID.
   *
   * Only return locations for user of website or public locations.
   *
   * @param int $id
   *   ID of the location.
   */
  public function locationsGetId($id) {
    $clientWebsiteId = RestObjects::$clientWebsiteId;
    $clientUserId = RestObjects::$clientUserId;
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      // Normal users can access public locations, or their own locations in
      // the current website.
      $filter = <<<SQL
        (t1.public=true
          OR (t2.website_id=$clientWebsiteId AND t1.created_by_id=$clientUserId)
          OR (t2.website_id=$clientWebsiteId AND t1.id IN (
            SELECT gl.location_id
            FROM groups_locations gl
            JOIN groups_users gu ON gu.group_id=gl.group_id AND gu.user_id=$clientUserId AND gu.deleted=false AND gu.pending=false
            WHERE gl.deleted=false
          ))
        )
      SQL;
    }
    else {
      // Site editor or admin users can access public locations, or any
      // location in the current website.
      $filter = "(t1.public=true OR t2.website_id=$clientWebsiteId)";
    }
    // Call read() with userFilter = FALSE as public locations may be
    // created by another user.
    rest_crud::read('location', $id, $filter, FALSE);
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
    // Update only allowed on this website.
    $preconditions = ['websites[].id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if ($this->needToFilterToUser()) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    $r = rest_crud::update('location', $id, $putArray, $preconditions);
    echo json_encode($r);
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
    // Delete only allowed on this website.
    $preconditions = ['websites[].id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    rest_crud::delete('location', $id, $preconditions);
  }

  /**
   * End-point to GET an list of sample_media.
   */
  public function locationMediaGet() {
    rest_crud::readList('location_medium');
  }

  /**
   * End-point to GET a location_media by ID.
   *
   * @param int $id
   *   location media ID.
   */
  public function locationMediaGetId($id) {
    rest_crud::read(
      'location_medium',
      $id,
      't3.website_id=' . RestObjects::$clientWebsiteId,
      // If user website site role known, allow access if admin or site editor,
      // else must belong to user.
      !isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2
    );
  }

  /**
   * API end-point to POST a location_media to create.
   */
  public function locationMediaPost() {
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    // Create only allowed on this website.
    $preconditions = ['location.websites[].id' => RestObjects::$clientWebsiteId];
    $r = rest_crud::create('location_medium', $item, $preconditions);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to PUT to an existing location_medium to update.
   */
  public function locationMediaPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    // Update only allowed on this website.
    $preconditions = ['location.websites[].id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    $r = rest_crud::update('location_medium', $id, $putArray, $preconditions);
    echo json_encode($r);
  }

  /**
   * API end-point to DELETE a location_medium.
   *
   * Will only be deleted if the location_medium was created by the current user.
   *
   * @param int $id
   *   location medium ID to delete.
   */
  public function locationMediaDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    // Update only allowed on this website.
    $preconditions = ['location.websites[].id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    // Delete as long as created by this user.
    rest_crud::delete('location_medium', $id, $preconditions);
  }

  /**
   * End-point to GET an list of notifications.
   */
  public function notificationsGet() {
    $filters = [
      't2.website_id=' . RestObjects::$clientWebsiteId,
      't1.user_id=' . RestObjects::$clientUserId,
    ];
    // Add filter on Acknowledged if not in the request parameters.
    if (empty($_GET['acknowledged'])) {
      $filters[] = 't1.acknowledged=false';
    }
    $filterStr = implode(' AND ', $filters);
    rest_crud::readList(
      'notification',
      $filterStr,
      FALSE
    );
  }

  /**
   * End-point to GET a notification by ID.
   *
   * @param int $id
   *   Notification ID.
   */
  public function notificationsGetId($id) {
    $filters = [
      't2.website_id=' . RestObjects::$clientWebsiteId,
      't1.user_id=' . RestObjects::$clientUserId,
    ];
    $filterStr = implode(' AND ', $filters);
    rest_crud::read(
      'notification',
      $id,
      $filterStr,
      FALSE
    );
  }

  /**
   * API end-point to PUT to an existing notification to update.
   *
   * The only update currently allowed is to set the 'acknowledged' flag.
   */
  public function notificationsPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    // Only allowed to update 'acknowledged' flag.
    $allowedKeys = ['acknowledged'];
    $extraKeys = array_diff(array_keys($putArray['values']), $allowedKeys);
    if (!empty($extraKeys)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Only the "acknowledged" field can be updated.');
    }
    // Limit to user's own data.
    $preconditions = ['user_id' => RestObjects::$clientUserId];
    $r = rest_crud::update('notification', $id, $putArray, $preconditions);
    echo json_encode($r);
  }

  /**
   * End-point to GET an list of sample_media.
   */
  public function sampleMediaGet() {
    rest_crud::readList('sample_medium', NULL, $this->needToFilterToUser());
  }

  /**
   * End-point to GET a sample_media by ID.
   *
   * @param int $id
   *   Sample media ID.
   */
  public function sampleMediaGetId($id) {
    rest_crud::read(
      'sample_medium',
      $id,
      't4.website_id=' . RestObjects::$clientWebsiteId,
      // If user website site role known, allow access if admin or site editor,
      // else must belong to user.
      $this->needToFilterToUser()
    );
  }

  /**
   * API end-point to POST a sample_media to create.
   */
  public function sampleMediaPost() {
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    $r = rest_crud::create('sample_medium', $item);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to PUT to an existing sample_medium to update.
   *
   * @todo Safety check it's from the correct website.
   */
  public function sampleMediaPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    // Update only allowed on this website.
    $preconditions = ['sample.survey.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if ($this->needToFilterToUser()) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    $r = rest_crud::update('sample_medium', $id, $putArray, $preconditions);
    echo json_encode($r);
  }

  /**
   * API end-point to DELETE a sample_medium.
   *
   * Will only be deleted if the sample_medium was created by the current user.
   *
   * @param int $id
   *   Sample medium ID to delete.
   *
   * @todo Safety check it's from the correct website.
   */
  public function sampleMediaDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    $preconditions = ['sample.survey.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    // Delete as long as created by this user.
    rest_crud::delete('sample_medium', $id, $preconditions);
  }

  /**
   * End-point to GET an list of samples.
   */
  public function samplesGet() {
    // @todo Website filters on this request and similar may need to respect
    // JWT scope.
    rest_crud::readList('sample', 't2.website_id=' . RestObjects::$clientWebsiteId, $this->needToFilterToUser());
  }

  /**
   * End-point to GET a sample by ID.
   *
   * @param int $id
   *   Sample ID.
   */
  public function samplesGetId($id) {
    rest_crud::read('sample', $id, 't2.website_id=' . RestObjects::$clientWebsiteId, $this->needToFilterToUser());
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
    // Update only allowed on this website.
    $preconditions = ['survey.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if ($this->needToFilterToUser()) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    $r = rest_crud::update(
      'sample',
      $id,
      $putArray,
      $preconditions
    );
    echo json_encode($r);
  }

  /**
   * API end-point to DELETE a sample.
   *
   * Will only be deleted if the sample was created by the current user.
   *
   * @param int $id
   *   Sample ID to delete.
   */
  public function samplesDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    // Delete only allowed on this website.
    $preconditions = ['survey.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    rest_crud::delete('sample', $id, $preconditions);
  }

  /**
   * Website permissions check.
   *
   * Check that authenticated user has admin or edit access to the
   * authenticated website, e.g. before CRUD operation on a privileged
   * resource.
   *
   * @param int $level
   *   Level required (1 = admin, 2 = editor, 3 = user). Default 2.
   */
  private function assertUserHasWebsiteAccess(int $level = 2) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown.');
    }
    $websiteId = RestObjects::$clientWebsiteId;
    $userId = RestObjects::$clientUserId;
    $sql = <<<SQL
SELECT u.id, u.core_role_id, uw.site_role_id
FROM users u
LEFT JOIN users_websites uw ON uw.user_id=u.id AND uw.website_id=? and uw.site_role_id<=?
WHERE u.id=?;
SQL;
    $user = RestObjects::$db->query($sql, [$websiteId, $level, $userId])->current();
    if (!$user) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user not found.');
    }
    if (empty($user->site_role_id) && empty($user->core_role_id)) {
      RestObjects::$apiResponse->fail('Forbidden', 403, 'User does not have required level of access to this website.');
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
SELECT COUNT(*) FROM $table WHERE deleted=false AND id=? AND website_id=?;
SQL;
    }
    $check = RestObjects::$db->query($sql, [$id, $websiteId])->current();
    if ($check->count === '0') {
      // Determine if record missing or permissions so we can return correct
      // error.
      $sql = <<<SQL
SELECT count(*) FROM $table WHERE deleted=false AND id=?
SQL;
      $check = RestObjects::$db->query($sql, [$id])->current();
      if ($check->count === '0') {
        RestObjects::$apiResponse->fail('Not Found', 404, 'Attempt to update or delete a missing or already deleted record.');
      }
      RestObjects::$apiResponse->fail('Forbidden', 403, 'Attempt to update or delete a record from another website.');
    }
  }

  /**
   * End-point to GET a list of available surveys.
   */
  public function surveysGet() {
    rest_crud::readList('survey', 't1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * End-point to GET a survey by ID.
   *
   * @param int $id
   *   Survey ID.
   */
  public function surveysGetId($id) {
    rest_crud::read('survey', $id, 't1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * API end-point to POST a survey to create.
   */
  public function surveysPost() {
    $this->assertUserHasWebsiteAccess();
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
    $this->assertUserHasWebsiteAccess();
    $this->assertRecordFromCurrentWebsite('surveys', $id);
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    $r = rest_crud::update('survey', $id, $putArray);
    echo json_encode($r);
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
    $this->assertUserHasWebsiteAccess();
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
    $valuesTableEsc = pg_escape_identifier(RestObjects::$db->getLink(), "{$entity}_values");
    $idFieldEsc = pg_escape_identifier(RestObjects::$db->getLink(), "{$entity}_id");
    $checkSql = <<<SQL
SELECT id FROM $valuesTableEsc WHERE $idFieldEsc=? AND deleted=false LIMIT 1;
SQL;
    if (RestObjects::$db->query($checkSql, [$id])->current()) {
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
  private function attributeTypeChanging($table, int $id, $putArray) {
    if (!empty($putArray['values']['data_type'])) {
      $tableEsc = pg_escape_identifier(RestObjects::$db->getLink(), $table);
      $newType = $putArray['values']['data_type'];
      $checkSql = <<<SQL
SELECT id FROM {$tableEsc} WHERE id=$id AND data_type<>'$newType';
SQL;
      return !empty(RestObjects::$db->query($checkSql)->current());
    }
    return FALSE;
  }

  /**
   * End-point to GET a list of available sample attributes.
   */
  public function sampleAttributesGet() {
    rest_crud::readList('sample_attribute', 't2.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * End-point to GET a sample attribute by ID.
   *
   * @param int $id
   *   Sample attribute ID.
   */
  public function sampleAttributesGetId($id) {
    rest_crud::read('sample_attribute', $id, 't2.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * API end-point to POST a sample_attribute to create.
   */
  public function sampleAttributesPost() {
    $this->assertUserHasWebsiteAccess();
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    // Autofill website ID.
    if (isset($item['values'])) {
      $item['values']['website_id'] = RestObjects::$clientWebsiteId;
      if ($item['values']['data_type'] === 'L' && empty($item['values']['termlist_id']) && !empty($item['terms'])) {
        $this->createAttributeTermlist($item);
      }
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
    $this->assertUserHasWebsiteAccess();
    $this->assertAttributeFromCurrentWebsite('sample_attributes', $id);
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    if ($this->attributeTypeChanging('sample_attributes', $id, $putArray)) {
      $this->assertAttributeHasNoValues('sample_attributes', $id);
    }
    if (isset($putArray['values']) && isset($putArray['values']['data_type'])
        && $putArray['values']['data_type'] === 'L' && !empty($putArray['terms'])) {
      $this->updateAttributeTermlist($putArray);
    }
    $r = rest_crud::update('sample_attribute', $id, $putArray);
    echo json_encode($r);
  }

  /**
   * End-point to GET an list of sample_comments.
   */
  public function sampleCommentsGet() {
    $extraFilterString = $this->getSampleCommentExtraFilter();
    rest_crud::readList('sample_comment', $extraFilterString, $this->needToFilterToUser());
  }

  /**
   * End-point to GET a sample_comment by ID.
   *
   * @param int $id
   *   Sample comment ID.
   */
  public function sampleCommentsGetId($id) {
    $extraFilterString = $this->getSampleCommentExtraFilter();
    rest_crud::read(
      'sample_comment',
      $id,
      $extraFilterString,
      $this->needToFilterToUser());
  }

  /**
   * API end-point to POST an sample_comment to create.
   */
  public function sampleCommentsPost() {
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    $r = rest_crud::create('sample_comment', $item);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to PUT to an existing sample_comment to update.
   */
  public function sampleCommentsPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    // Update only allowed on this website.
    $preconditions = ['sample.survey.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if ($this->needToFilterToUser()) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    $r = rest_crud::update('sample_comment', $id, $putArray, $preconditions);
    echo json_encode($r);
  }

  /**
   * API end-point to DELETE an sample_comment.
   *
   * Will only be deleted if the sample_comment was created by the current
   * user.
   *
   * @param int $id
   *   Sample comment ID to delete.
   */
  public function sampleCommentsDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    // Update only allowed on this website.
    $preconditions = ['sample.survey.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    // Delete as long as created by this user.
    rest_crud::delete('sample_comment', $id, $preconditions);
  }

  private function getSampleCommentExtraFilter() {
    return 't3.website_id=' . (int) RestObjects::$clientWebsiteId;
  }

  /**
   * When creating an attribute, create a termlist if it has terms.
   *
   * @param array $item
   *   Attribute submission including values and optional terms elements.
   */
  private function createAttributeTermlist(array &$item) {
    // Create a new termlist.
    $termlist = ORM::factory('termlist');
    $termlist->set_submission_data([
      'title' => 'Termlist for ' . $item['values']['caption'],
      'description' => 'Termlist created by the REST API for attribute ' . $item['values']['caption'],
      'website_id' => RestObjects::$clientWebsiteId,
      'deleted' => 'f',
    ]);
    if (!$termlist->submit()) {
      RestObjects::$apiResponse->fail('Internal Server Error', 500,
            'Error occurred creating new termlist: ' . implode("\n", $termlist->getAllErrors()));
    }
    $item['values']['termlist_id'] = $termlist->id;
    // Also add the terms.
    $this->updateAttributeTermlist($item);
  }

  /**
   * If a lookup attribute has child terms, make sure the termlist is updated.
   *
   * Performs the following tasks:
   * * Marks existing terms as not for data entry if missing from new list
   * * Adds new terms.
   * * Updates sort order of existing terms.
   *
   * @param array $item
   *   Attribute submission including values and optional terms elements.
   */
  private function updateAttributeTermlist(array $item) {
    $existing = [];
    $existingRows = RestObjects::$db
      ->select('tlt.id, t.term, tlt.sort_order')
      ->from('termlists_terms AS tlt')
      ->join('terms AS t', 't.id', 'tlt.term_id')
      ->where([
        'tlt.deleted' => 'f',
        't.deleted' => 'f',
        'tlt.termlist_id' => $item['values']['termlist_id'],
      ])
      ->orderby(['tlt.sort_order' => 'ASC', 't.term' => 'ASC'])
      ->get()->result();
    foreach ($existingRows as $row) {
      $existing[$row->term] = $row;
    }
    // Tidy submitted terms.
    foreach ($item['terms'] as &$term) {
      $term = trim($term);
    }
    // Don't leave reference to last term hanging.
    unset($term);
    foreach ($item['terms'] as $idx => $term) {
      if (array_key_exists($term, $existing)) {
        if ($existing[$term]->sort_order != $idx + 1) {
          // Update existing term sort order.
          RestObjects::$db->update(
            'termlists_terms',
            ['sort_order' => $idx + 1],
            ['id' => $existing[$term]->id]
          );
        }
      }
      else {
        // Create new term.
        $termlists_term = ORM::factory('termlists_term');
        $termlists_term->set_submission_data([
          'term:term' => $term,
          'term:fk_language:iso' => kohana::config('indicia.default_lang'),
          'sort_order' => $idx + 1,
          'termlist_id' => $item['values']['termlist_id'],
          'preferred' => 't',
        ]);
        if (!$termlists_term->submit()) {
          RestObjects::$apiResponse->fail('Internal Server Error', 500,
            'Error occurred creating new term: ' . implode("\n", $termlists_term->getAllErrors()));
        };
      }
    }
    foreach ($existing as $row) {
      if (!in_array($row->term, $item['terms'])) {
        // Remove existing term by marking as not for data entry.
        RestObjects::$db->update(
          'termlists_terms', [
            'allow_data_entry' => 'f',
            'updated_on' => "'" . date('c', time()) . "'",
            'updated_by_id' => RestObjects::$clientUserId,
          ],
          ['id' => $row->id]
        );
      }
    }
  }

  /**
   * API end-point to DELETE a sample attribute.
   *
   * Will only be deleted if the current user has edit rights to the website
   * the attribute is used by and the attribute has no values.
   *
   * @param int $id
   *   Survey ID to delete.
   */
  public function sampleAttributesDeleteId(int $id) {
    $this->assertUserHasWebsiteAccess();
    $this->assertAttributeFromCurrentWebsite('sample_attributes', $id);
    $this->assertAttributeHasNoValues('sample_attributes', $id);
    // Delete - no need to check user as admin of website.
    rest_crud::delete('sample_attribute', $id);
    // Also delete links.
    RestObjects::$db->query("UPDATE sample_attributes_websites SET deleted=TRUE WHERE sample_attribute_id=?", [$id]);
  }

  /**
   * End-point to GET a list of available sample attributes websites.
   */
  public function sampleAttributesWebsitesGet() {
    rest_crud::readList('sample_attributes_website', 't1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * End-point to GET a sample attribute by ID.
   *
   * @param int $id
   *   Sample attribute ID.
   */
  public function sampleAttributesWebsitesGetId($id) {
    rest_crud::read('sample_attributes_website', $id, 't1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * API end-point to POST a sample attributes website to create.
   */
  public function sampleAttributesWebsitesPost() {
    $this->assertUserHasWebsiteAccess();
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
        'deleted' => 'f',
      ])
      ->get()->current();
    if ($existing) {
      $r = rest_crud::update('sample_attributes_website', $existing->id, $postArray);
      echo json_encode($r);
    }
    else {
      $r = rest_crud::create('sample_attributes_website', $postArray);
      echo json_encode($r);
      http_response_code(201);
      header("Location: $r[href]");
    }
  }

  /**
   * API end-point to PUT to an existing sample attributes website to update.
   */
  public function sampleAttributesWebsitesPutId($id) {
    $this->assertUserHasWebsiteAccess();
    $this->assertRecordFromCurrentWebsite('sample_attributes_websites', $id);
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    $r = rest_crud::update('sample_attributes_website', $id, $putArray);
    echo json_encode($r);
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
    $this->assertUserHasWebsiteAccess();
    $this->assertRecordFromCurrentWebsite('sample_attributes_websites', $id);
    rest_crud::delete('sample_attributes_website', $id);
  }

  /**
   * End-point to GET a list of available occurrence attributes.
   */
  public function occurrenceAttributesGet() {
    rest_crud::readList('occurrence_attribute', 't2.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * End-point to GET an occurrence attribute by ID.
   *
   * @param int $id
   *   Occurrence attribute ID.
   */
  public function occurrenceAttributesGetId($id) {
    rest_crud::read('occurrence_attribute', $id, 't2.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * API end-point to POST a occurrence_attribute to create.
   */
  public function occurrenceAttributesPost() {
    $this->assertUserHasWebsiteAccess();
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    // Autofill website ID.
    if (isset($item['values'])) {
      $item['values']['website_id'] = RestObjects::$clientWebsiteId;
      if ($item['values']['data_type'] === 'L' && empty($item['values']['termlist_id']) && !empty($item['terms'])) {
        $this->createAttributeTermlist($item);
      }
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
    $this->assertUserHasWebsiteAccess();
    $this->assertAttributeFromCurrentWebsite('occurrence_attributes', $id);
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    if ($this->attributeTypeChanging('occurrence_attributes', $id, $putArray)) {
      $this->assertAttributeHasNoValues('occurrence_attributes', $id);
    }
    if (isset($putArray['values']) && isset($putArray['values']['data_type'])
        && $putArray['values']['data_type'] === 'L' && !empty($putArray['terms'])) {
      $this->updateAttributeTermlist($putArray);
    }
    $r = rest_crud::update('occurrence_attribute', $id, $putArray);
    echo json_encode($r);
  }

  /**
   * API end-point to DELETE an occurrence attribute.
   *
   * Will only be deleted if the current user has edit rights to the website
   * the attribute is used by and the attribute has no values.
   *
   * @param int $id
   *   Survey ID to delete.
   */
  public function occurrenceAttributesDeleteId(int $id) {
    $this->assertUserHasWebsiteAccess();
    $this->assertAttributeFromCurrentWebsite('occurrence_attributes', $id);
    $this->assertAttributeHasNoValues('occurrence_attributes', $id);
    // Delete - no need to check user as admin of website.
    rest_crud::delete('occurrence_attribute', $id);
    // Also delete links.
    RestObjects::$db->query("UPDATE occurrence_attributes_websites SET deleted=TRUE WHERE occurrence_attribute_id=?", [$id]);
  }

  /**
   * End-point to GET a list of available occurrence attributes websites.
   */
  public function occurrenceAttributesWebsitesGet() {
    rest_crud::readList('occurrence_attributes_website', 't1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * End-point to GET a occurrence attribute by ID.
   *
   * @param int $id
   *   Occurrence attribute ID.
   */
  public function occurrenceAttributesWebsitesGetId($id) {
    rest_crud::read('occurrence_attributes_website', $id, 't1.website_id=' . RestObjects::$clientWebsiteId, FALSE);
  }

  /**
   * API end-point to POST a occurrence attributes website to create.
   */
  public function occurrenceAttributesWebsitesPost() {
    $this->assertUserHasWebsiteAccess();
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
        'deleted' => 'f',
      ])
      ->get()->current();
    if ($existing) {
      $r = rest_crud::update('occurrence_attributes_website', $existing->id, $postArray);
      echo json_encode($r);
    }
    else {
      $r = rest_crud::create('occurrence_attributes_website', $postArray);
      echo json_encode($r);
      http_response_code(201);
      header("Location: $r[href]");
    }
  }

  /**
   * End-point to PUT to an existing occurrence attributes website to update.
   */
  public function occurrenceAttributesWebsitesPutId($id) {
    $this->assertUserHasWebsiteAccess();
    $this->assertRecordFromCurrentWebsite('occurrence_attributes_websites', $id);
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    $r = rest_crud::update('occurrence_attributes_website', $id, $putArray, []);
    echo json_encode($r);
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
    $this->assertUserHasWebsiteAccess();
    $this->assertRecordFromCurrentWebsite('occurrence_attributes_websites', $id);
    rest_crud::delete('occurrence_attributes_website', $id);
  }

  /**
   * End-point to GET an list of dna_occurrences.
   */
  public function dnaOccurrencesGet() {
    $extraFilterString = $this->getDnaOccurrenceExtraFilter();
    rest_crud::readList('dna_occurrence', $extraFilterString, $this->needToFilterToUser());
  }

  /**
   * End-point to GET a dna_occurrence by ID.
   *
   * @param int $id
   *   Occurrence comment ID.
   */
  public function dnaOccurrencesGetId($id) {
    $extraFilterString = $this->getDnaOccurrenceExtraFilter();
    rest_crud::read(
      'dna_occurrence',
      $id,
      $extraFilterString,
      $this->needToFilterToUser());
  }

  /**
   * API end-point to POST a dna_occurrence to create.
   */
  public function dnaOccurrencesPost() {
    $post = file_get_contents('php://input');
    $item = json_decode($post, associative: TRUE);
    try {
      $r = rest_crud::create('dna_occurrence', $item);
    }
    catch (Kohana_Database_Exception $e) {
      kohana::log('debug', 'Message: ' . $e->getMessage());
      kohana::log('debug', 'Strpos: ' . var_export(strpos($e->getMessage(), 'A record with the same dna occurrences occurrence id already exists.'), TRUE));
      if (strpos($e->getMessage(), 'A record with the same dna occurrences occurrence id already exists.') !== FALSE) {
        RestObjects::$apiResponse->fail('Conflict', 409, 'Trying to attach new DNA occurrence data to an occurrence which already has DNA occurrence data.');
      }
      else {
        throw $e;
      }
    }
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to PUT to an existing dna_occurrence to update.
   */
  public function dnaOccurrencesPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    // Update only allowed on this website.
    $preconditions = ['occurrence.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if ($this->needToFilterToUser()) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    $r = rest_crud::update('dna_occurrence', $id, $putArray, $preconditions);
    echo json_encode($r);
  }

  /**
   * API end-point to DELETE an occurrence.
   *
   * Will only be deleted if the occurrence was created by the current user.
   *
   * @todo Website ID precondition could respect editing sharing mode.
   *
   * @param int $id
   *   Occurrence ID to delete.
   */
  public function dnaOccurrencesDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    // Delete only allowed on this website.
    $preconditions = ['occurrence.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    rest_crud::delete('dna_occurrence', $id, $preconditions);
  }

  /**
   * Filter DNA occurrence data.
   *
   * Adds a website ID filter on the attached occurrence, plus optionally a
   * confidential occurrence filter.
   *
   * @return string
   */
  private function getDnaOccurrenceExtraFilter() {
    $extraFilters = [
      't2.website_id=' . (int) RestObjects::$clientWebsiteId
    ];
    // Read disallowed on confidential unless specifically allowed.
    if (empty($this->resourceOptions['allow_confidential'])) {
      $extraFilters[] = 't2.confidential=false';
    }
    return implode(' AND ', $extraFilters);
  }

  /**
   * End-point to GET an list of occurrence_comments.
   */
  public function occurrenceCommentsGet() {
    $extraFilterString = $this->getOccurrenceCommentExtraFilter();
    rest_crud::readList('occurrence_comment', $extraFilterString, $this->needToFilterToUser());
  }

  /**
   * End-point to GET a occurrence_comment by ID.
   *
   * @param int $id
   *   Occurrence comment ID.
   */
  public function occurrenceCommentsGetId($id) {
    $extraFilterString = $this->getOccurrenceCommentExtraFilter();
    rest_crud::read(
      'occurrence_comment',
      $id,
      $extraFilterString,
      $this->needToFilterToUser());
  }

  /**
   * API end-point to POST an occurrence_comment to create.
   */
  public function occurrenceCommentsPost() {
    $post = file_get_contents('php://input');
    $item = json_decode($post, TRUE);
    $r = rest_crud::create('occurrence_comment', $item);
    echo json_encode($r);
    http_response_code(201);
    header("Location: $r[href]");
  }

  /**
   * API end-point to PUT to an existing occurrence_comment to update.
   */
  public function occurrenceCommentsPutId($id) {
    $put = file_get_contents('php://input');
    $putArray = json_decode($put, TRUE);
    // Update only allowed on this website.
    $preconditions = ['occurrence.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if ($this->needToFilterToUser()) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    $r = rest_crud::update('occurrence_comment', $id, $putArray, $preconditions);
    echo json_encode($r);
  }

  /**
   * API end-point to DELETE an occurrence_comment.
   *
   * Will only be deleted if the occurrence_comment was created by the current
   * user.
   *
   * @param int $id
   *   Occurrence comment ID to delete.
   */
  public function occurrenceCommentsDeleteId($id) {
    if (empty(RestObjects::$clientUserId)) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Authenticated user unknown so cannot delete.');
    }
    // Update only allowed on this website.
    $preconditions = ['occurrence.website_id' => RestObjects::$clientWebsiteId];
    // Also limit to user's own data unless site admin or editor.
    if (!isset(RestObjects::$clientUserWebsiteRole) || RestObjects::$clientUserWebsiteRole > 2) {
      $preconditions['created_by_id'] = RestObjects::$clientUserId;
    }
    // Delete as long as created by this user.
    rest_crud::delete('occurrence_comment', $id, $preconditions);
  }

  private function getOccurrenceCommentExtraFilter() {
    $extraFilters = [
      't2.website_id=' . (int) RestObjects::$clientWebsiteId
    ];
    // Read disallowed on confidential unless specifically allowed.
    if (empty($this->resourceOptions['allow_confidential'])) {
      $extraFilters[] = 't1.confidential=false';
    }
    return implode(' AND ', $extraFilters);
  }

  /**
   * Controller method for the verify_spreadsheet end-point.
   */
  public function occurrencesPostVerifySpreadsheet() {
    $this->authenticate();
    try {
      $metadata = rest_spreadsheet_verify::verifySpreadsheet();
      header('Content-type: application/json');
      echo json_encode($metadata);
    }
    catch (RestApiAbort $e) {
      // No action if a proper abort.
    }
  }

}
