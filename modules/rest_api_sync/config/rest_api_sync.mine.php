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
/*
$config['user_id'] = 'INDICIATEST';
$config['taxon_list_id'] = 1;

// The following configuration is a temporary definition of the projects available for
// each website.
// @todo Move this configuration into a database table.
$config['servers']=array(
  // keyed by server system ID
  'BTO' => array(
    // the local website registration used to store each project
    'website_id' => 6,
    'url' => 'http://blxdev.bto.org/DT-WS/v1.0',
    'shared_secret' => 'open12345',
    'resources' => array('taxon-observations')
  )
);
*/
/*
$config['user_id'] = 'WA';
$config['taxon_list_id'] = 1;
$config['dataset_name_attr_id'] = 35;

// The following configuration is a temporary definition of the projects available for
// each website.
$config['servers']=array(
  // keyed by server system ID
  'WB' => array(
    // the local website registration used to store each project
    'website_id' => 2,
    'url' => 'http://localhost/warehouse2/index.php/services/rest',
    'shared_secret' => 'WBsecret',
    'resources' => array('annotations')
  )
);
*/

$config['user_id'] = 'BGW';
$config['taxon_list_id'] = 1;

$config['servers'] = [
  /*'BRC' => [
    // the local website registration used to store each project
    'website_id' => 7,
    'url' => 'http://devwarehouse.indicia.org.uk/index.php/services/rest',
    'shared_secret' => 'jvb_t3st',
    'resources' => array('taxon-observations', 'annotations'),
  ],*/
  'NAT' => [
    'website_id' => 8,
    'survey_id' => 21,
    'url' => 'https://api.inaturalist.org/v1',
    'serverType' => 'iNaturalist',
    'parameters' => [
      'quality_grade' => 'research',
      'project_id' => 19208,
      /*'d1' => '2017-06-06',
      'd2' => '2017-06-08',
      'place_id' => 6858,*/
    ],
    'attrs' => [
      'controlled_attribute:9' => 'occAttr:22',
    ],
  ],
];
