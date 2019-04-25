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
 * Controller for syncing records from another source.
 *
 * Controller class to provide an endpoint for initiating the synchronisation of
 * two warehouses via the REST API.
 */
class Rest_api_sync_Controller extends Indicia_Controller {

  /**
   * On initiation of sync processing, return the list of servers.
   *
   * This allows the JS client to process earch server's sync in trun.
   */
  public function start() {
    $this->auto_render = FALSE;
    $servers = Kohana::config('rest_api_sync.servers');
    echo json_encode([
      'servers' => array_keys($servers),
      'startTime' => date('c'),
    ]);
  }

  /**
   * On completion of sync processing, update variables.
   *
   * Allows next sync run to start where this left off.
   */
  public function end() {
    $this->auto_render = FALSE;
    $servers = Kohana::config('rest_api_sync.servers');
    foreach (array_keys($servers) as $serverId) {
      variable::set("rest_api_sync_{$serverId}_last_run", $_GET['startTime']);
      // Clean up possible page tracking data.
      variable::delete("rest_api_sync_{$serverId}_last_id");
      variable::delete("rest_api_sync_{$serverId}_page");
    }
  }

  /**
   * Endpoint for the JS AJAX call to process the next page.
   *
   * Echos back JSON progress info.
   */
  public function process_batch() {
    $this->auto_render = FALSE;
    rest_api_sync::$clientUserId = Kohana::config('rest_api_sync.user_id');
    $servers = Kohana::config('rest_api_sync.servers');
    $serverIdx = empty($_GET['serverIdx']) ? 1 : $_GET['serverIdx'];
    $page = empty($_GET['page']) ? 1 : $_GET['page'];
    $serverId = array_keys($servers)[$serverIdx - 1];
    $server = $servers[$serverId];
    $serverType = isset($server['serverType']) ? $server['serverType'] : 'indicia';
    $helperClass = 'rest_api_sync_' . strtolower($serverType);
    $helperClass::loadControlledTerms($serverId, $server);
    $progressInfo = $helperClass::syncPage($serverId, $server, $page);
    if ($progressInfo['moreToDo']) {
      $page++;
    }
    else {
      $page = 1;
      $serverIdx++;
    }
    if ($serverIdx > count($servers)) {
      echo json_encode([
        'state' => 'done',
        'log' => rest_api_sync::$log,
      ]);
    }
    else {
      $r = [
        'state' => 'in progress',
        'serverIdx' => $serverIdx,
        'page' => $page,
        'log' => rest_api_sync::$log,
      ];
      // The following might only be calculated on the first page load, so are
      // optional.
      if (array_key_exists('pageCount', $progressInfo)) {
        $r['pageCount'] = $progressInfo['pageCount'];
      }
      if (array_key_exists('recordCount', $progressInfo)) {
        $r['recordCount'] = $progressInfo['recordCount'];
      }
      echo json_encode($r);
    }
  }

}
