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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */
 
 /**
  * Handles authentication for the Flickr integration
  */
  require_once("data_entry_helper.php");

  $api_key                 = helper_config::$flickr_api_key;
  $api_secret              = helper_config::$flickr_api_secret;
  $default_redirect        = "/";
  $permissions             = "read";
  $path_to_phpFlickr_class = "./phpFlickr/";

  ob_start();
  require_once($path_to_phpFlickr_class . "phpFlickr.php");

  unset($_SESSION['phpFlickr_auth_token']);

  if (!empty($_GET['extra'])) {
    $redirect = $_GET['extra'];
  }

  $f = new phpFlickr($api_key, $api_secret);

  if (empty($_GET['frob'])) {
    $f->auth($permissions, false);
  } else {
    $f->auth_getToken($_GET['frob']);
  }
  if (empty($redirect)) {
    header("Location: " . $default_redirect);
  } else {
    header("Location: " . $redirect);
  }

?>