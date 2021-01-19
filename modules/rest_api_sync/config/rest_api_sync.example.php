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
 * @link 	https://github.com/indicia-team/warehouse/
 */

/**
 * Define the database ID used to identify this system in the network.
 */
$config['user_id'] = 'ABC';

/**
 * Master species checklist to lookup against.
 */
$config['taxon_list_id'] = 1;

/**
 * Which sample attribute will we use to store the dataset name for records which came from
 * remote systems?
 */
$config['dataset_name_attr_id'] = 99;

// The following configuration is a temporary definition of the projects available for
// each website.
// @todo Move this configuration into a database table.
$config['servers'] = [
  // Keyed by server system ID.
  'XYZ' => [
    // The local website registration used to store each project.
    'website_id' => 5,
    // Remote API URL.
    'url' => 'http://localhost/indicia/index.php/services/rest',
    // Remote platform name, iNaturalist or Indicia.
    'serverType' => 'Indicia',
    // Secret shared with the remote API.
    'shared_secret' => '123password',
    // Optional. Which resources will we try to retrieve from this API?
    'resources' => array('taxon-observations', 'annotations'),
    // Should existing records get overwritten by remote updates when verified?
    // Default true.
    'allowUpdateWhenVerified' => TRUE,
  ],
  'INAT' => [
    'website_id' => 123,
    'survey_id' => 789,
    'url' => 'https://api.inaturalist.org/v1',
    'serverType' => 'iNaturalist',
    // iNaturalist API request query parameters.
    'parameters' => [
      'quality_grade' => 'research',
      'place_id' => 6857,
	    'license' => 'cc-by,cc-by-nc,cc-by-nd,cc-by-sa,cc-by-nc-nd,cc-by-nc-sa,cc0',
    ],
    // iNaturalist field mappings to custom attributes.
    'attrs' => [
      'controlled_attribute:1' => 'occAttr:768',
      'controlled_attribute:9' => 'occAttr:346',
    ],
    'allowUpdateWhenVerified' => FALSE,
  ],
];