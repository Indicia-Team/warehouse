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
 * @package Modules
 * @subpackage UTM 30U Grid References
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/** 
 * Conversion class for UTM.
 * @package Modules
 * @subpackage UTM Grid References
 * @author  Indicia Team
 */
class utm30n_ed50 extends utm_grid{

  /**
  * Return the underying EPSG code for the datum this notation is based on.
  */
  public static function get_srid()
  {
    return 23030;
    /* IMPORTANT
     * Because there are many possible datum shifts available for this projection
     * by default PostGIS does none. I have applied the mean shift by updating the
     * spatial_ref_sys table as follows:
     *   update spatial_ref_sys 
     *   set proj4text = '+proj=utm +zone=30 +ellps=intl +units=m +no_defs +towgs84=-87,-98,-121'
     *   where srid = 23030;
     * The datum shift was taken from http://earth-info.nga.mil/GandG/coordsys/onlinedatum/CountryEuropeTable.html
     */
  }


}
?>
