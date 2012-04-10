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
 * @subpackage Channel Islands Grid References
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/** 
 * Conversion class for Jersey grid references EPSG:3109.
 * @package Modules
 * @subpackage Channel Islands Grid References
 * @author  Indicia Team
 */
class jersey extends island_grid{

  /**
  * Return the underying EPSG code for the datum this notation is based on.
  */
  public static function get_srid()
  {
    return 3109;
  }


}
?>
