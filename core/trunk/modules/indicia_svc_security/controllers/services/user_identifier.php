<?php defined('SYSPATH') or die('No direct script access.');
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
 * @package Services
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/**
 * Controller for service calls relating to the use of user identifiers to associated
 * client website users with warehouse user accounts. An example usage of this is
 * to use a twitter account identifier to identify a single user across multiple client
 * websites.
 * @link http://code.google.com/p/indicia/wiki/WebServicesUserIdentifiers
 */
class User_Identifier_Controller extends Service_Base_Controller {
  public function get_user_id() { 
    try {
      // don't use $_REQUEST as it can do funny things escaping quotes etc.
      $request=array_merge($_GET, $_POST);
      // authenticate requesting website for this service. This can create a user, so need write
      // permission.
      $this->authenticate('write');
      echo json_encode(user_identifier::get_user_id($request, $this->website_id));
    }
    catch (Exception $e) {
      $this->handle_error($e);
    }
  }
}