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

/**
 * Controller for syncing records from another source.
 *
 * Controller class to provide an endpoint for initiating the synchronisation of
 * two warehouses via the REST API.
 */
class Rest_api_sync_skipped_record_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('rest_api_sync_skipped_record');
    $this->columns = array(
      'server_id' => 'Server ID',
      'source_id' => 'Source ID',
      'dest_table' => 'Destination table',
      'error_message' => 'Message',
    );
    $this->pagetitle = "REST API sync";
    $this->session = Session::instance();
  }

  /**
   * No default edit action column for this grid.
   */
  protected function get_action_columns() {
    return array(
    );
  }

}
