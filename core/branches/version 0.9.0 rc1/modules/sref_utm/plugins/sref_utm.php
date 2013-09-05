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
 * @package	Modules
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Declare a handler for UTM georeferences.
 * @return array Spatial system metadata
 */
function sref_utm_sref_systems() {
  return array(
    'utm30ed50' => array(
      'title' => 'UTM 30N (ED50)',
      /* IMPORTANT
       * Because there are many possible datum shifts available for this projection
       * by default PostGIS does none. I have applied the mean shift by updating the
       * spatial_ref_sys table as follows:
       *   update spatial_ref_sys 
       *   set proj4text = '+proj=utm +zone=30 +ellps=intl +units=m +no_defs +towgs84=-87,-98,-121'
       *   where srid = 23030;
       * The datum shift was taken from http://earth-info.nga.mil/GandG/coordsys/onlinedatum/CountryEuropeTable.html
       */
      'srid' => 23030,
      'treat_srid_as_x_y_metres' => true
    ), 'utm30wgs84' => array(
      'title' => 'UTM 30N (WGS84)',
      'srid' => 32630,
      'treat_srid_as_x_y_metres' => true
    )
  );
}