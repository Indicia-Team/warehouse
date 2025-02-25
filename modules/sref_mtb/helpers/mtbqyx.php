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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  https://github.com/indicia-team/warehouse/
 */

/**
 * Conversion class for MTBQQQ (German grid system) grid references.
 */
class mtbqyx extends mtb {

  /**
   * Returns true if the spatial reference is a recognised German MTBQYX Grid square.
   *
   * @param $sref string Spatial reference to validate
   */
  public static function is_valid($sref)
  {
    $sref = self::clean($sref);
    return preg_match('/^\d\d\d\d(\/[1-4]([1-3][1-5])?)?$/', $sref)<>0;
  }

  /**
   * Converts a spatial reference in MTBQYX notation into the WKT text for the polygon, in
   * OSGB easting northings.
   */
  public static function sref_to_wkt($sref)
  {
    $sref=self::clean($sref);
    if (!self::is_valid($sref))
      throw new InvalidArgumentException('Spatial reference is not a recognisable grid square.', 4001);
    // Split the input string main square part into x & y.
    $gridYTop = substr($sref, 0, 2);
    $gridXLeft = substr($sref, 2, 2);
    // Top left cell of grid system is 0901 (yy=09, xx=01)
    $gridYTop -= GRIDORIGIN_Y;
    $gridXLeft -= GRIDORIGIN_X;
    // Each cell is 6 minutes high, = 0.1 degree. Top of grid is 55.1
    $northEdge = ORIGIN_Y - $gridYTop * SIXMINUTES;
    // Each cell is 10 minutes wide, = 1/6 degrees. Left of grid is 5.833 = 35/6
    $westEdge = ORIGIN_X + $gridXLeft * TENMINUTES;
    // we now have the top left of the outer grid square. We need to work out the quadrant and xy component.
    // default square size compared to a full mtb square
    $xSize = TENMINUTES;
    $ySize = SIXMINUTES;
    // Quadrant first.
    if (strlen($sref) >= 6) {
      $q = substr($sref, 5, 1);
      // change square size to a quadrant
      $xSize = TENMINUTES / 2;
      $ySize = SIXMINUTES / 2;
      // shift edges into correct quadrant
      if ($q > 2)
        $northEdge -= $ySize;
      if ($q == 2 || $q == 4)
        $westEdge += $xSize;
    }
    if (strlen($sref) >= 8) {
      // Now the xy component, each quadrant is a 5x3 grid
      $xSize = TENMINUTES / (2 * 5);
      $ySize = SIXMINUTES / (2 * 3);
      $y = substr($sref, 6, 1);
      $x = substr($sref, 7, 1);
      $northEdge = $northEdge - ($y - 1) * $ySize;
      $westEdge = $westEdge + ($x - 1) * $xSize;
    }
    // calculate the other edges
    $southEdge = $northEdge - $ySize;
    $eastEdge = $westEdge + $xSize;
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
    $gridX    = (($easting - ORIGIN_X) * 6) + GRIDORIGIN_X;
    $gridXLeft   = Floor($gridX);
    $gridY    = ((ORIGIN_Y - $northing) / SIXMINUTES) + GRIDORIGIN_Y;
    $gridYTop   = Floor($gridY);
    // Each quadrant is 5*3, so total 10*6.
    $x10th = Floor(($gridX - $gridXLeft) * 10) + 1;
    $y6th = Floor(($gridY - $gridYTop) * 6) + 1;
    // Find the quadrant
    $q = $x10th > 5 ? 2 : 1;
    $q += $y6th > 3 ? 2 : 0;
    // Find the xy coordinate within the quadrant.
    $x = ($x10th - 1) % 5 + 1;
    $y = ($y6th - 1) % 3 + 1;

    if ($gridYTop < 1 || $gridXLeft < 1 || $gridYTop > 99 || $gridXLeft > 99)
      throw new Exception('Outside bounds for MTB squares.');
    else {
      $StrY = Substr('00' . $gridYTop, -2);
      $StrX = Substr('00' . $gridXLeft, -2);
      $ref = "$StrY$StrX/$q$y$x";
      // assume full accuracy
      $len = 8;
      if ($metresAccuracy) {
        if ($metresAccuracy>16000) {
          $len=4; // e.g. 6402
        } elseif ($metresAccuracy>8000) {
          $len=6; // e.g. 6402/1
        }
      }
      return substr($ref, 0, $len);

    }
  }

}