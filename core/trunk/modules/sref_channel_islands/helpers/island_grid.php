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
 * Conversion class for channel island grid references.
  * @package Modules
 * @subpackage Channel Island Grid References
 * @author  Indicia Team
 */
class island_grid {

  /**
  * Returns true if the spatial reference is a recognised grid square.
  *
  * @param $sref string Spatial reference to validate
  */
  public static function is_valid($sref)
  {
    // ignore any spaces in the grid ref
    $sref = str_replace(' ','',$sref);
    // 2 cases - either chars must be all numeric and an equal number, up to 10 digits
    // OR for DINTY Tetrads, 2 numbers followed by a letter (Excluding O, including I)
    if ((!preg_match('/^[0-9]*$/', $sref) || strlen($sref) % 2 != 0 || strlen($sref)>10) AND
                    (!preg_match('/^[0-9][0-9][A-NP-Z]$/', $sref)))
      return FALSE;
    return TRUE;
  }

  /**
  * Converts a grid reference into the WKT text for the polygon, in
  * easting and northings from the zero reference. Supports DINTY tetrads.
  *
  * @param string $sref The grid reference
  * @return string String containing the well known text.
  */
  public static function sref_to_wkt($sref)
  {
    // ignore any spaces in the grid ref
    $sref = str_replace(' ','',$sref);
    if (!self::is_valid($sref))
      throw new Exception('Spatial reference is not a recognisable grid square.');
    if (strlen($sref)==3) {
      // Assume DINTY Tetrad format 2km squares
      // extract the easting and northing
      $east  = substr($sref, 0, 1);
      $north = substr($sref, 1, 1);
      $sq_code_letter_ord = ord(substr($sref, 2, 1));
      if ($sq_code_letter_ord > 79) $sq_code_letter_ord--; // Adjust for no O
      $sq_size = 2000;
      $east = $east * 10000 + floor(($sq_code_letter_ord - 65) / 5) * 2000;
      $north = $north * 10000 + (($sq_code_letter_ord - 65) % 5) * 2000;
    } else {
      // Normal Numeric Format
      $coordLen = strlen($sref)/2;
      // extract the easting and northing
      $east  = substr($sref, 0, $coordLen);
      $north = substr($sref, $coordLen);
      // if < 10 figure the easting and northing need to be multiplied up to the power of 10
      $sq_size = pow(10, 5-$coordLen);
      $east = $east * $sq_size;
      $north = $north * $sq_size;
    }
    $westEdge=$east;
    $southEdge=$north;
    $eastEdge=$westEdge+$sq_size;
    $northEdge=$southEdge+$sq_size;
    return 	"POLYGON(($westEdge $southEdge,$westEdge $northEdge,".
             "$eastEdge $northEdge,$eastEdge $southEdge,$westEdge $southEdge))";
  }

  /**
  * Converts a WKT to a grid square in the grid
  * reference notation. Only accepts POINT & POLYGON WKT at the moment.
  *
  * @param string $wkt The well known text
  * @param integer $precision The number of digits to include in the return value.
  * For a polygon, omit the parameter and the precision is inferred from the
  * size of the polygon. To return a grid reference in tetrad form, set this to 3.
  * @return string String containing grid reference.
  */
  public static function wkt_to_sref($wkt, $precision=null)
  {
    if (substr($wkt, 0, 7) == 'POLYGON')
      $points = substr($wkt, 9, -2);
    elseif (substr($wkt, 0, 5) == 'POINT') {
      $points = substr($wkt, 6, -1);
      if ($precision===null)
        throw new Exception('wkt_to_sref translation for POINTs requires an accuracy.');
    }
    else
      throw new Exception('wkt_to_sref translation only works for POINT or POLYGON wkt.');

    $points = explode(',',$points);
    // use the first point to do the conversion
    $point = explode(' ',$points[0]);
    $easting = $point[0];
    $northing = $point[1];
    // ensure the point is within the range of the grid
    if ($easting < 0 || $easting > 99999 || $northing < 0 || $northing > 99999)
      throw new Exception('wkt_to_sref translation is outside range of grid.');
    if ($precision===null) {
      // find the distance in metres from point 2 to point 1 (assuming a square is passed).
      // This is the accuracy of the polygon.
      $point_2 = explode(' ',$points[1]);
      $accuracy = abs(($point_2[0]-$point[0]) + ($point_2[1]-$point[1]));
      $precision = 12 - strlen($accuracy)*2;
    } else if ($precision==3) {
      // DINTY TETRADS
      // no action as all fixed.
    } else
      $accuracy = pow(10, (10-$precision)/2);

    if ($precision == 3) {
      // DINTY TETRADS
      // 2 numbers at start equivalent to precision = 2
      $e = floor($easting / 10000);
      $n = floor($northing / 10000);
      $letter = 65 + floor(($northing - ($n * 10000)) / 2000) + 5 * floor(($easting - ($e * 10000)) / 2000);
      if ($letter >= 79) $letter++; // Adjust for no O
      return $e.$n.chr($letter);
    }
    $e = floor($easting / $accuracy);
    $n = floor($northing / $accuracy);
    return str_pad($e, $precision/2, '0', STR_PAD_LEFT).str_pad($n, $precision/2, '0', STR_PAD_LEFT);
  }

}
?>
