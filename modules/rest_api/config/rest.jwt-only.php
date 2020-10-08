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
 * Authentication methods allowed.
 */
$config['authentication_methods'] = [
  'jwtUser' => [
    // TRUE to allow CORS from any domain, or provide an array of domain regexes.
    'allow_cors' => TRUE,
    'resource_options' => [
      // Grants full access to all reports. Client configs can override this.
      'reports' => ['featured' => TRUE, 'limit_to_own_data' => TRUE],
      // Grant access to Elasticsearch but in this case, apply website and user ID filters.
      'elasticsearch' => ['es' => ['limit_to_website' => TRUE, 'limit_to_own_data' => TRUE]],
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
  'es' => [
    'open' => FALSE,
    'index' => 'my-index',
    'url' => 'http://my.elastic.url:9200',
    'allowed' => [
      'get' => [
        '/^_search/' => 'GET requests to the search API (/_search?...)',
        '/^_mapping/' => 'GET requests to the mappings API (/_mapping?...)',
      ],
      'post' => [
        '/^_search/' => 'POST requests to the search API (/_search?...)',
        '/^doc\/.*\/_update/' => 'POSTed document updates',
      ],
    ],
  ],
];
