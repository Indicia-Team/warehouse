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
 * @link  http://code.google.com/p/indicia/
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
class mtbqqq {

  /**
   * Returns true if the spatial reference is a recognised German MTBQQQ Grid square.
   *
   * @param $sref string Spatial reference to validate
   */
  public static function is_valid($sref)
  {
    $sref = self::clean($sref);
    return preg_match('/^\d\d\d\d(\/[1-4][1-4]?[1-4]?)?$/', $sref)<>0;
  }

  /**
   * Converts a spatial reference in MTBQQQ notation into the WKT text for the polygon, in
   * OSGB easting northings.
   */
  public static function sref_to_wkt($sref)
  {
    $sref=self::clean($sref);
    if (!self::is_valid($sref))
      throw new InvalidArgumentException('Spatial reference is not a recognisable grid square.');
    // Split the input string main square part into x & y.
    $yy = substr($sref, 0, 2);
    $xx = substr($sref, 2, 2);
    // Top left cell of grid system is 0901 (yy=09, xx=01)
    $yy -= GRIDORIGIN_Y;
    $xx -= GRIDORIGIN_X;
    // Each cell is 6 minutes high, = 0.1 degree. Top of grid is 55.1
    $northEdge = ORIGIN_Y - $yy * SIXMINUTES;
    // Each cell is 10 minutes wide, = 1/6 degrees. Left of grid is 5.833 = 35/6
    $westEdge = ORIGIN_X + $xx * TENMINUTES;
    // we now have the top left of the outer grid square. We need to work out the quadrants.
    // Loop through the quadrant digits.
    for ($i=5; $i<strlen($sref); $i++) {
      $q = substr($sref, $i, 1);
      if ($q > 2) 
        $northEdge = $northEdge - (SIXMINUTES / pow(2, $i-4)); // divide by 2, 4 or 8
      if ($q == 2 || $q == 4) 
        $westEdge = $westEdge + (TENMINUTES / pow(2, $i-4)); // divide by 2, 4 or 8
    }
    // we now have the top left of a grid square. Need to know all the edges. Work out the amount we must
    // divide a full size grid square edge by to find the quadrant size.
    $sizing=1;
    if (strlen($sref)>=6) {
      $sizing = pow(2, strlen($sref)-5); // 2, 4 or 8
    }
    $southEdge = $northEdge - SIXMINUTES / $sizing;
    $eastEdge = $westEdge + TENMINUTES / $sizing;
    return "POLYGON(($westEdge $southEdge,$westEdge $northEdge,".
        "$eastEdge $northEdge,$eastEdge $southEdge,$westEdge $southEdge))";
  }

  /**
   * Converts a WKT polygon for a grid square (easting northing OSGB) into the
   * spatial reference notation. Only accepts POINT & POLYGON WKT at the moment.
   */
  public static function wkt_to_sref($wkt, $precision=null, $output=null, $metresAccuracy=null)
  {
    if (substr($wkt, 0, 7) == 'POLYGON')
      $points = substr($wkt, 9, -2);
    elseif (substr($wkt, 0, 5) == 'POINT') {
      $points = substr($wkt, 6, -1);
      if ($metresAccuracy===null)
        throw new Exception('wkt_to_sref translation for POINTs requires a metres accuracy.');
    }
    else
      throw new Exception('wkt_to_sref translation only works for POINT or POLYGON wkt.');
    
    $points = explode(',',$points);
    $point = explode(' ',$points[0]);
    $easting = $point[0];
    $northing = $point[1];
    /*if ($easting < 0 || $easting > 700000 || $northing < 0 || $northing > 1300000)
      throw new Exception('wkt_to_sref translation is outside range of grid.');*/
    $y    = ((ORIGIN_Y - $northing) / SIXMINUTES) + GRIDORIGIN_Y;
    $yy   = Floor($y);
    $x    = (($easting - ORIGIN_X) * 6) + GRIDORIGIN_X;
    $xx   = Floor($x);
    $y8th = Floor(($y - $yy) * 8) + 1;
    $x8th = Floor(($x - $xx) * 8) + 1;
    // Start on 111
    $q1 = 1;
    $q2 = 1;
    $q3 = 1;
    // Work out each shift according to y8th
    if (in_array($y8th, array(5, 6, 7, 8)))
      $q1 = 3;
    if (in_array($y8th, array(3, 4, 7, 8)))
      $q2 = 3;
    if (in_array($y8th, array(2, 4, 6, 8))) 
      $q3 = 3;
    // Work out each additional shift according to x8th
    if (in_array($x8th, array(5, 6, 7, 8)))
      $q1++;
    if (in_array($x8th, array(3, 4, 7, 8)))
      $q2++;
    if (in_array($x8th, array(2, 4, 6, 8)))
      $q3++;
    if ($yy < 1 || $xx < 1 || $yy > 99 || $xx > 99)
      throw new Exception('Outside bounds for MTB squares.');
    else {
      $StrY = Substr('00'.$yy, -2);
      $StrX = Substr('00'.$xx, -2);
      $ref = sprintf('%s%s/%d%d%d', $StrY, $StrX, $q1, $q2, $q3);
      // assume full accuracy
      $len = 8;
      if ($metresAccuracy) {
        if ($metresAccuracy>16000) {
          $len=4; // e.g. 6402
        } elseif ($metresAccuracy>8000) {
          $len=6; // e.g. 6402/1
        } elseif ($metresAccuracy>4000) {
          $len=7; // e.g. 6402/11
        }
      }
      return substr($ref, 0, $len);
      
    }
  }

  /**
   * Return the underying EPSG code for the datum this notation is based on (Airy 1830)
   */
  public static function get_srid()
  {
    return 4745;//31468; // 4745 (RD83 Bessel)?
  }
  
  /**
   * Ensures that the format of an input sref is consistent.
   */
  private static function clean($sref) {
    // remove whitespace
    $sref = preg_replace('/\s/', '', $sref);
    // ensure slash correct way round
    $sref = str_replace('\\', '/', $sref);
    return $sref;
  }

}
?>
