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
 * @subpackage Config
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
/*
 * The indicia upload path should mirror the value for directory in the upload config file. The value in
 * this file is relative to the base_url, and indicates the upload directory on the warehouse.
 * The upload_path is relative to the website install.
 */
class helper_config {
  static $base_url='*base_url*';
  static $interim_image_folder = 'upload/';  // relative to the client_helpers folder. Code referring to this should use upload/ as default if missing
  static $final_image_folder = 'warehouse'; // if set to a folder path, submitted images are moved to this folder rather than moved to the Warehouse.
  static $cache_folder = 'cache/';  // relative to the client_helpers folder. Code referring to this should use cache/ as default if missing
  static $indicia_upload_path = 'upload/';  // this is relative to the indicia installation directory
  static $geoserver_url = '*geoserver_url*';
  static $geoplanet_api_key='*geoplanet_api_key*';
  static $google_search_api_key='*google_search_api_key*';
  static $bing_api_key='*bing_api_key*';
  static $multimap_api_key='*multimap_api_key*';
  static $flickr_api_key='*flickr_api_key*';
  static $flickr_api_secret='*flickr_api_secret*';
  static $maxUploadSize='1MB'; // max image upload size. Should match setting on the Warehouse config/indicia.php file.
}

?>
