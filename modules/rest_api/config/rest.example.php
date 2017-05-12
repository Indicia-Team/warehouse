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
 * hmac cannot be generated.
 */
$config['authentication_methods'] = array(
  'hmacClient' => array('allow_http'),
  'hmacWebsite' => array('allow_http', 'allow_all_report_access'),
  'directClient' => array(),
  'oauth2User' => array()
);

/**
 * Should authorisation tokens be allowed in the query parameters rather than the
 * authorisation header? Recommended for development servers only.
 */
$config['allow_auth_tokens_in_url'] = FALSE;

// The following configuration is a temporary definition of the projects available for 
// each website.
// @todo Move this configuration into a database table.
$config['clients']=array(
  // keyed by client system ID
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
        'resources' => array('taxon-observations', 'annotations')
      )
    )
  )
);