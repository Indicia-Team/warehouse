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
 * @link http://code.google.com/p/indicia/
 */

define('MAX_RECORDS_TO_PROCESS', 2000);

/**
 * Controller for syncing records from another source.
 *
 * Controller class to provide an endpoint for initiating the synchronisation of
 * two warehouses via the REST API.
 */
class Rest_Api_Sync_Controller extends Indicia_Controller {

  /**
   * Main controller method for the rest_api_sync module.
   *
   * Initiates a synchronisation.
   */
  public function index() {
    $view = new View('rest_api_sync/index');
    $this->template->content = $view;
    $this->template->title = 'REST API sync';
    $servers = Kohana::config('rest_api_sync.servers');
    $view->noServerConfig = empty($servers);
  }

  public function process_batch() {
    $this->auto_render = FALSE;
    rest_api_sync::$client_user_id = Kohana::config('rest_api_sync.user_id');
    $servers = Kohana::config('rest_api_sync.servers');
    $serverIdx = empty($_GET['serverIdx']) ? 1 : $_GET['serverIdx'];
    $page = empty($_GET['page']) ? 1 : $_GET['page'];
    $serverId = array_keys($servers)[$serverIdx - 1];
    $server = $servers[$serverId];
    $serverType = isset($server['serverType']) ? $server['serverType'] : 'indicia';
    $helperClass = 'rest_api_sync_' . strtolower($serverType);
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
    } else {
      echo json_encode([
        'state' => 'in progress',
        'serverIdx' => $serverIdx,
        'page' => $page,
        'pageCount' => $progressInfo['pageCount'],
        'recordCount' => $progressInfo['recordCount'],
        'servers' => array_keys($servers),
        'log' => rest_api_sync::$log,
      ]);
    }
  }

}
