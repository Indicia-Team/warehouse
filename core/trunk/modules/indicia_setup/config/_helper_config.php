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

class helper_config {
  static $base_url='*base_url*';
  static $upload_path = './upload/';
  static $geoserver_url = '*geoserver_url*';
  // The following only need to be configured if using the data_entry_helper::map method, as it autogenerates the script links for you.
  static $geoplanet_api_key='*geoplanet_api_key*';
  static $google_search_api_key='*google_search_api_key*';
  static $google_api_key='*google_api_key*';
  static $multimap_api_key='*multimap_api_key*';
  // end of items required by data_entry_helper::map
}

?>
