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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Define the database ID used to identify this system in the network.
 */
$config['user_id'] = 'BRC';

/**
 * Dataset name attribute ID.
 *
 * Which sample attribute will we use to store the dataset name for records
 * which came from remote systems?
 */
$config['dataset_name_attr_id'] = 99;

/**
 * Authentication methods allowed.
 *
 * Default options exclude direct passing of id and password which should be
 * enabled on development servers only.
 * * oauth2User - for authenticating warehouse user accounts to access their
 *   own records via oauth, or with a filter_id to define a wider set of
 *   records.
 * * hmacClient - authorise a client in the list below using HMAC in the http
 *   header
 * * hmacWebsite - authorise as a website registered on the warehouse using
 *   HMAC in the http header
 * * directUser - allow the user ID and password to be passed directly.
 * * directClient - allow the client system ID and shared secret to be passed
 *   directly.
 * * directWebsite - allow the website ID and password to be passed directly.
 *
 * Note that hmacUser is not supported as the password is hashed on the server
 * so a hmac cannot be generated. Each key points to an array of options:
 * * allow_http - this must be set if access over http (rather than https) is
 *   going to be enabled. Use with caution in production environments.
 * * resource_options - pass the name of a resource (e.g. reports) and an array
 *   of flags to pass to the resource. Flags depend on the resource.
 */
$config['authentication_methods'] = [
  'hmacClient' => [
    // HMAC is a bit safer over https as the authentication secrets are never
    // shared. There are still implications for the data itself though.
    'allow_http' => TRUE,
    'resource_options' => [
      // Grants full access to all reports. Client configs can override this.
      'reports' => [],
      // Grant access to elasticsearch. Provide empty array to enable all
      // end-points. Configure the clients which can access each index in
      // the clients config entry.
      'elasticsearch' => ['es'],
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
 * Should authorisation tokens be allowed in the query parameters rather than the
 * authorisation header? Recommended for development servers only.
 */
$config['allow_auth_tokens_in_url'] = FALSE;

/**
 * If this warehouse is configured to work with an Elasticsearch instance then
 * the REST API can act as a proxy to avoid having to expose all the public
 * APIs. The proxy can point to index aliases to limit the search filter.
 */
$config['elasticsearch'] = [
  // Name of the end-point, e.g. /index.php/services/rest/es.
  'es' => [
    // Set open = TRUE if this end-point is available without authentication.
    // Otherwise it must be attached to a configured client.
    'open' => TRUE,
    // Name of the elasticsearch index or alias this end-point points to.
    'index' => 'occurrence',
    // URL of the Elasticsearch index.
    'url' => 'http://my.elastic.url:9200',
    // If specified, limit the access to the following operations. List of
    // HTTP request types (e.g. get, post, put, delete) each containing a
    // list of regular expressions for allowed requests, along with the
    // description of what that allows.
    // So, this example allows the following call:
    // http://mywarehouse.com/index.php/services/rest/es/_search?q=taxon.name:quercus
    // which proxies to
    // http://my.elastic.url:9200/occurrence/_search?q=taxon.name:quercus
    'allowed' => [
      'get' => [
        '/^_search/' => 'GET requests to the search API (/_search?...)',
        '/^_mapping\/doc/' => 'GET requests to the mappings API (/_mapping/doc?...)',
      ],
      'post' => [
        '/^_search/' => 'POST requests to the search API (/_search?...)',
        '/^doc\/.*\/_update/' => 'POSTed document updates',
      ],
    ],
  ],
];

// The following configuration defines a list of clients for the REST API
// (other than the intrinsic website registrations and warehouse user clients).
// Each client has access to a number of projects which provide filtered access
// to the records of a given website registration.
// @todo Move this configuration into a database table.
$config['clients'] = [
  // Client list keyed by client ID.
  'BTO' => [
    'shared_secret' => 'password',
    'projects' => [
      // List of available projects keyed by project ID.
      'BRC1' => [
        'id' => 'BRC1',
        'website_id' => 1,
        'title' => 'BRC birds',
        'description' => 'Bird records entered onto the BRC warehouse made available for verification on iRecord.',
        // Optional filter ID.
        'filter_id' => 53,
        'sharing' => 'verification',
        // Optional, which resources are available? Default is all.
        'resources' => ['taxon-observations', 'annotations', 'reports'],
        'resource_options' => [
          'reports' => [
            'raw_data',
            'featured',
            'authorise' => [
              // Authorise a normally restricted report for this project.
              'library/occurrences/list_for_elastic_all.xml',
            ],
          ],
        ],
        // Set the following to TRUE for Indicia to automatically feed through
        // pages of data. Useful when the client is a dumb poller for the data
        // such as the Elastic Stack's Logstash.
        'autofeed' => FALSE,
      ],
    ],
    // This client can access the es elasticsearch proxy end-point.
    'elasticsearch' => ['es'],
  ],
];
