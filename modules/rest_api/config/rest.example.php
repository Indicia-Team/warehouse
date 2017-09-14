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
 * @package	Modules
 * @subpackage Cache builder
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Define the database ID used to identify this system in the network.
 */
$config['user_id'] = 'BRC';

/**
 * Which sample attribute will we use to store the dataset name for records which came from
 * remote systems?
 */
$config['dataset_name_attr_id'] = 99;

/**
 * Authentication methods allowed. Default options exclude direct passing of id
 * and password which should be enabled on development servers only.
 * * oauth2User - for authenticating warehouse user accounts to access their own records
 *   via oauth, or with a filter_id to define a wider set of records.
 * * hmacClient - authorise a client in the list below using HMAC in the http header
 * * hmacWebsite - authorise as a website registered on the warehouse using HMAC in the http header
 * * directUser - allow the user ID and password to be passed directly.
 * * directClient - allow the client system ID and shared secret to be passed directly.
 * * directWebsite - allow the website ID and password to be passed directly.
 * Note that hmacUser is not supported as the password is hashed on the server so a
 * hmac cannot be generated. Each key points to an array of options:
 * * allow_http - this must be set if access over http (rather than https) is going to be enabled. Use with caution in
 *   production environments.
 * * resource_options - pass the name of a resource (e.g. reports) and an array of flags to pass to the resource. Flags
 *   depend on the resource.
 */
$config['authentication_methods'] = array(
  'hmacClient' => array(
    // HMAC is a bit safer over https as the authentication secrets are never shared. There are still implications for
    // the data itself though.
    'allow_http',
    'resource_options' => array(
      // grants full access to all reports. Client configs can override this.
      'reports' => array()
    )
  ),
  'hmacWebsite' => array('allow_http',
    'resource_options' => array(
      // featured reports with cached summary data only - highly restricted
      'reports' => array('featured' => true, 'summary' => true, 'cached' => true)
    )
  ),
  'directClient' => array(
    'resource_options' => array(
      // grants full access to all reports. Client configs can override this.
      'reports' => array()
    )
  ),
  'oauth2User' => array('resource_options' => array(
    // grants full access to all reports. Client configs can override this.
    'reports' => array('featured' => true, 'limit_to_own_data' => true)
  ))
);

/**
 * Should authorisation tokens be allowed in the query parameters rather than the
 * authorisation header? Recommended for development servers only.
 */
$config['allow_auth_tokens_in_url'] = FALSE;

// The following configuration defines a list of clients for the REST API (other than the intrinsic website
// registrations and warehouse user clients). Each client has access to a number of projects which provide filtered
// access to the records of a given website registration.
// @todo Move this configuration into a database table.
$config['clients']=array(
  // keyed by client ID
  'BTO' => array(
    'shared_secret' => 'password',
    'projects' => array(
      // list of available projects keyed by project ID
      'BRC1' => array(
        'id' => 'BRC1',
        'website_id' => 1,
        'title'=>'BRC birds',
        'description'=>'Bird records entered onto the BRC warehouse made available for verification on iRecord.',
        // Optional filter ID
        'filter_id' => 53,
        'sharing' => 'verification',
        // optional, which resources are available? Default is all.
        'resources' => array('taxon-observations', 'annotations', 'reports'),
        'resource_options' => array(
          'reports' => array('raw_data', 'featured')
        )
      )
    )
  )
);