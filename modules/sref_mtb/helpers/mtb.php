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
 * @subpackage MTBQQQ Grid References
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  https://github.com/indicia-team/warehouse/
 */

define("GRIDORIGIN_X",1);
define("GRIDORIGIN_Y",9);
define("SIXMINUTES",1/10);
define("TENMINUTES",1/6);
define("ORIGIN_X", 35/6);
define("ORIGIN_Y", 55.1);

/**
 * Conversion class for MTBQQQ (German grid system) grid references.
 * @package Modules
 * @subpackage MTBQQQ Grid References
 * @author  Indicia Team
 */
class mtb {

  /**
   * Ensures that the format of an input sref is consistent.
   */
  protected static function clean($sref) {
    // remove whitespace
    $sref = preg_replace('/\s/', '', $sref);
    // ensure slash correct way round
    $sref = str_replace('\\', '/', $sref);
    return $sref;
  }

}