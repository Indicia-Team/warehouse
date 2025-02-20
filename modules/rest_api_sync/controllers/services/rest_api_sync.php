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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

define('MAX_RECORDS_TO_PROCESS', 2000);

/**
 * Controller for syncing records from another source.
 *
 * Controller class to provide an endpoint for initiating the synchronisation of
 * two warehouses via the REST API.
 */
class Rest_Api_Sync_Controller extends Controller {

  /**
   * Main controller method for the rest_api_sync module.
   *
   * Initiates a synchronisation.
   */
  public function index() {
    kohana::log('debug', 'Initiating REST API Sync');
    echo "<h1>REST API Sync</h1>";
    $servers = Kohana::config('rest_api_sync.servers');
    rest_api_sync_utils::$clientUserId = Kohana::config('rest_api_sync.user_id');
    // For performance, just notify work_queue to update cache entries.
    if (class_exists('cache_builder')) {
      cache_builder::$delayCacheUpdates = TRUE;
    }
    foreach ($servers as $serverId => $server) {
      echo "<h2>$serverId</h2>";
      $server = array_merge([
        'serverType' => 'Indicia',
        'allowUpdateWhenVerified' => TRUE,
        'dontOverwriteExistingRecordVerificationStatus' => FALSE,
      ], $server);
      $helperClass = 'rest_api_sync_remote_' . strtolower($server['serverType']);
      $helperClass::loadControlledTerms($serverId, $server);
      $helperClass::syncServer($serverId, $server);
    }
  }

}
