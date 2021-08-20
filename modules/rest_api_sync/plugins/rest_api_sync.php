<?php

/**
 * @file
 * Plugin methods for the rest_api_sync module.
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Create a menu item for the Rest API sync UI.
 */
function rest_api_sync_alter_menu($menu, $auth) {
  if ($auth->logged_in('CoreAdmin')) {
    $menu['Admin']['Rest API sync'] = 'rest_api_sync_skipped_record';
  }
  return $menu;
}

function rest_api_sync_extend_data_services() {
  return array(
    'rest_api_sync_skipped_records' => [],
  );
}

function rest_api_sync_extend_rest_api() {
  return [
    'sync-taxon-observations' => [
      'GET' => [
        'sync-taxon-observations' => [
          'params' => [
            'proj_id' => [
              'datatype' => 'text',
            ],
            'tracking_from' => [
              'datatype' => 'integer',
            ],
          ],
        ],
      ],
    ],
    'sync-annotations' => [
      'GET' => [
        'sync-annotations' => [
          'params' => [
            'proj_id' => [
              'datatype' => 'text',
            ],
            /*************/'tracking_from' => [
              'datatype' => 'integer',
            ],
          ],
        ],
      ],
    ],
  ];
}
