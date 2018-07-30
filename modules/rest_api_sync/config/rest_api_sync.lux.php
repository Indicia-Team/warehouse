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
$config['user_id'] = 'MHN';
$config['taxon_list_id'] = 13;

$config['servers'] = [
  'NAT' => [
    'website_id' => 11,
    'survey_id' => 43,
    'url' => 'https://api.inaturalist.org/v1',
    'serverType' => 'iNaturalist',
    'parameters' => [
      'quality_grade' => 'research',
      'project_id' => 'neobiota-luxembourg',
    ],
    'attrs' => [
      'controlled_attribute:9' => 'occAttr:88',
    ],
  ],
];
