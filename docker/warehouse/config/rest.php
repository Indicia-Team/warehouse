<?php defined('SYSPATH') or die('No direct script access.');

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
 * @package Modules
 * @subpackage Cache builder
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Define the database ID used to identify this system in the network.
 */
$config['user_id'] = 'BRC';


$config['authentication_methods'] = [
  'hmacClient' => [
    // HMAC is a bit safer over https as the authentication secrets are never
    // shared. There are still implications for the data itself though.
    'allow_http',
    'resource_options' => [
      // Grants full access to all reports. Client configs can override this.
      'reports' => [],
    ],
  ],
  'hmacWebsite' => [
    'allow_http',
    'resource_options' => [
      // Featured reports with cached summary data only - highly restricted.
      'reports' => ['featured' => TRUE, 'summary' => TRUE, 'cached' => TRUE],
    ],
  ],
  'directClient' => [
    'resource_options' => [
      // Grants full access to all reports. Client configs can override this.
      'reports' => [],
      'elasticsearch' => [],
    ],
  ],
  'directWebsite' => [
    'allow_http' => TRUE,
    'resource_options' => [
      'elasticsearch' => ['es-occurrences', 'es-samples'],
    ],
  ],
  'jwtUser' => [
    'resource_options' => [
      // Grants full access to all reports. Client configs can override this.
      'reports' => ['featured' => TRUE, 'limit_to_own_data' => TRUE],
      'elasticsearch' => [
        'es-occurrences' => [
          'limit_to_website' => TRUE,
          'limit_to_own_data' => TRUE,
        ],
        'es-samples' => [
          'limit_to_website' => TRUE,
          'limit_to_own_data' => TRUE,
        ],
      ],
    ],
    'allow_cors' => TRUE,
  ],
];

/**
 * If this warehouse is configured to work with an Elasticsearch instance then
 * the REST API can act as a proxy to avoid having to expose all the public
 * APIs. The proxy can point to index aliases to limit the search filter.
 */
$config['elasticsearch_version'] = '8.10';
$config['elasticsearch'] = [
  'es-occurrences' => [
    'index' => 'occurrence_brc1_index',
	  'url' => 'http://192.171.199.233:9200',
	  'allowed' => [
      'get' => [
        '/^_search/' => 'GET requests to the search API (/_search?...)',
    		'/^_mapping\/doc/' => 'GET requests to the mappings API (/_mapping/doc?...)',
      ],
	    'post' => [
        '/^_search/' => 'POST requests to the search API (/_search?...)',
		    '/^doc\/.*\/_update/' => 'POSTed document updates',
		    '/^_update_by_query/' => 'POSTed multi-document updates',
      ],
    ],
  ],
  'es-samples' => [
    'index' => 'sample_brc1_index',
    'url' => 'http://indicia_elastic_1:9200',
    'allowed' => [
      'get' => [
        '/^_search/' => 'GET requests to the search API (/_search?...)',
        '/^_mapping\/doc/' => 'GET requests to the mappings API (/_mapping/doc?...)',
      ],
      'post' => [
        '/^_search/' => 'POST requests to the search API (/_search?...)',
        '/^doc\/.*\/_update/' => 'POSTed document updates',
        '/^_update_by_query/' => 'POSTed multi-document updates',
      ],
    ],
  ],
];

// The following configuration is a temporary definition of the projects
// available for each website.
// @todo Move this configuration into a database table.
$config['clients'] = [
  'BRC' => [
    'shared_secret' => 'password',
    'projects' => [
      'ES_OCC' => [
        // Project for Elastic integration.
        'id' => 'ES_OCC',
        'website_id' => 23,
        'sharing' => 'reporting',
        // All reports, with restricted report access.
        'resource_options' => [
          'reports' => [
            'authorise' => [
              'library/occurrences/list_for_elastic_all.xml',
            ],
          ],
        ],
      ],
      'ES_OCC_DEL' => [
        // Project for tracking deletions for Elastic integration.
        'id' => 'ES_OCC_DEL',
        'website_id' => 23,
        'sharing' => 'reporting',
        'resource_options' => [
          'reports' => [
            'authorise' => [
              'library/occurrences/list_occurrence_deletions_all.xml',
            ],
          ],
        ],
      ],
      'ES_OCC_ASSOC' => [
        'id' => 'ES_OCC_ASSOC',
        'website_id' => 23,
        'title' => 'Occurrence Associations',
        'sharing' => 'reporting',
        'autofeed' => TRUE,
        'resource_options' => [
          'reports' => [
            'authorise' => [
              'library/occurrence_associations/list_for_elastic_all.xml',
            ],
          ],
        ],
      ],
      'ES_SMP' => [
        // Project for Elastic integration with samples.
        'id' => 'ES_SMP',
        'website_id' => 23,
        'sharing' => 'reporting',
        // All reports, with restricted report access.
        'resource_options' => [
          'reports' => [
            'authorise' => [
              'library/samples/list_for_elastic_all.xml',
            ],
          ],
        ],
      ],
      'ES_SMP_DEL' => [
        // Project for Elastic integration with samples.
        'id' => 'ES_SMP_DEL',
        'website_id' => 23,
        'sharing' => 'reporting',
        // All reports, with restricted report access.
        'resource_options' => [
          'reports' => [
            'authorise' => [
              'library/samples/list_sample_deletions_all.xml',
            ],
          ],
        ],
      ],
    ],
    'elasticsearch' => [],
  ],
];
