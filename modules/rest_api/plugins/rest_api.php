<?php

/**
 * @file
 * Warehouse plugins for the REST API.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Scheduled plugin task to purge old queued media.
 */
function rest_api_scheduled_task() {
  // Anything older than 1 day can be purged.
  rest_utils::purgeOldFiles('upload-queue', 3600 * 24);
}

/**
 * Create a menu item for the list of REST API clients.
 */
function rest_api_alter_menu($menu, $auth) {
  if ($auth->logged_in('CoreAdmin') || $auth->has_any_website_access('admin')) {
    $menu['Admin']['REST API clients'] = 'rest_api_client';
  }
  return $menu;
}

function rest_api_extend_data_services() {
  return [
    'rest_api_clients' => [],
    'rest_api_client_connections' => [],
  ];
}
