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

/*
 * The indicia upload path should mirror the value for directory in the upload
 * config file. The value in this file is relative to the base_url, and
 * indicates the upload directory on the warehouse. The upload_path is relative
 * to the website install.
 */
class helper_config {
  public static $base_url = '*base_url*';
  public static $geoserver_url = '';
  public static $geoplanet_api_key = '';
  public static $bing_api_key = '';
  // Max image upload size. Should match setting on the Warehouse
  // config/indicia.php file.
  public static $maxUploadSize = '4MB';

}
