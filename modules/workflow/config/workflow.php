<?php

/**
 * @file
 * Configuration for the workflow module.
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
 * @package Modules
 * @subpackage Workflow
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/
 */

$config['entities'] = array(
  'occurrence' => array(
    'title' => 'Occurrence',
    'event_types' => array(
      array('code' => 'S', 'title' => 'Initially set as workflow record'),
      array('code' => 'V', 'title' => 'Verification'),
      array('code' => 'R', 'title' => 'Rejection'),
      array('code' => 'U', 'title' => 'Unreleased'),
      array('code' => 'P', 'title' => 'Pending review'),
      array('code' => 'F', 'title' => 'Fully released'),
    ),
    'keys' => array(
      array(
        'title' => 'Taxon External Key',
        'db_store_value' => 'taxa_taxon_list_external_key',
        'column' => 'external_key',
        'table' => 'cache_taxa_taxon_lists',
      ),
    ),
    'extraData' => array(
      array(
        'table' => 'cache_taxa_taxon_lists',
        'originating_table_column' => 'taxa_taxon_list_id',
        'target_table_column' => 'id',
      ),
    ),
    'setableColumns' => array(
      'confidential' => ['t', 'f'],
      'sensitivity_precision' => ['100', '1000', '2000', '10000', '100000'],
      'release_status' => ['U', 'R', 'P'],
    ),
    'defaults' => array(
      'confidential' => 'f',
      'sensitivity_precision' => NULL,
      'release_status' => 'R',
    ),
  ),
);
