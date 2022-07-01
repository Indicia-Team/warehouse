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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Controller for service calls relating to the use of user identifiers.
 *
 * User identifiers relate to associated client website users with warehouse
 * user accounts. An example usage of this is to use a twitter account
 * identifier to identify a single user across multiple client websites.
 *
 * @link https://indicia-docs.readthedocs.io/en/latest/developing/web-services/user-identifiers.html
 */
class User_Identifier_Controller extends Service_Base_Controller {

  public function get_user_id() {
    $tm = microtime(TRUE);
    try {
      // don't use $_REQUEST as it can do funny things escaping quotes etc.
      $request = array_merge($_GET, $_POST);
      // Authenticate requesting website for this service. This can create a
      // user, so need write permission.
      $this->authenticate('write');
      $r = user_identifier::get_user_id($request, $this->website_id);
      echo json_encode($r);
      $userId = isset($r['userId']) ? $r['userId'] : NULL;
      if (class_exists('request_logging')) {
        request_logging::log('a', 'security', 'get_user_id', 'user',
          $this->website_id, $userId, $tm);
      }
    }
    catch (Exception $e) {
      if (class_exists('request_logging')) {
        request_logging::log('a', 'security', 'get_user_id', 'user',
          $this->website_id, NULL, $tm, NULL, $e->getMessage());
      }
      $this->handle_error($e);
    }
  }

  /**
   * Controller for the client site delete_user service call to the Warehouse.
   */
  public function delete_user() {
    $tm = microtime(TRUE);
    try {
      // Don't use $_REQUEST as it can do funny things escaping quotes etc.
      $this->authenticate('write');
      $userId = $_POST['warehouse_user_id'];
      $websiteId = $_POST['website_id_for_user_deletion'];
      // Fail if website ID doesn't match the one in authentication.
      if ($websiteId !== $this->website_id) {
        throw new Exception('Requested website ID does not match the authorised one.');
      }
      if (!preg_match('/^\d+$/', $userId) || !preg_match('/^\d+$/', $websiteId)) {
        throw new Exception('Parameters must be valid integers.');
      }
      user_identifier::delete_user($userId, $websiteId);
      if (class_exists('request_logging')) {
        request_logging::log('a', 'security', 'delete_user', 'user',
          $websiteId, $userId, $tm);
      }
    }
    catch (Exception $e) {
      if (class_exists('request_logging')) {
        request_logging::log('a', 'security', 'delete_user', 'user',
          $websiteId, NULL, $tm, NULL, $e->getMessage());
      }
      $this->handle_error($e);
    }
  }

}
