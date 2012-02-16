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
 * @package	Core
 * @subpackage Helpers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct script access.');

class spatial_ref {

  /**
   * Provides a wrapper for dynamic calls to a spatial reference module's validate
   * method.
   */
  public static function is_valid($sref, $sref_system)
  {
    $system = strtolower($sref_system);
    if (is_numeric($system)) {
      // EPSG code
      // first check if this $system expects Lat/Long format, rather than x,y
      if(array_key_exists($system, kohana::config('sref_notations.lat_long_systems'))) {
        // validate the notation by calling the lat/long validator
        return (bool) self::is_valid_lat_long($sref);
      } else {
        // else check this is just a pair of numbers with a list separator
        $locale=localeconv();
        return (bool) preg_match(
                '/^[-+]?[0-9]*\\'.$locale['decimal_point'].'?[0-9]+'.
                Kohana::lang('misc.x_y_separator').
                '[ ]*[-+]?[0-9]*\\'.$locale['decimal_point'].'?[0-9]+$/D', $sref);
      }
    } else {
      // validate the notation by calling the module which translates it for us
      self::validateSystemClass($system);
      if (method_exists($system, 'is_valid'))
        return (bool) call_user_func("$system::is_valid", $sref);
      else 
        throw new Exception("The spatial reference system $sref is not recognised.");
    }
  }
	
  /** 
   * Throw an exception if the class name provided for spatial reference translation is not recognisable.
   */
  private static function validateSystemClass($system) {
    // Note, do not use method_exists here as it can cause crashes in FastCGI servers.
    if (!is_callable(array($system, 'is_valid')) ||
        !is_callable(array($system, 'get_srid')) ||
        !is_callable(array($system, 'sref_to_wkt')) ||
        !is_callable(array($system, 'wkt_to_sref'))) 
      throw new Exception("The spatial reference system $system is not recognised.");
  }

  /**
   * Returns true if a spatial reference system is recognisable as a notation
   * or EPSG code.
   */
  public static function is_valid_system($system)
  {
    $db = new Database();
    if (is_numeric($system)) {
      $found = $db->count_records('spatial_ref_sys', array('auth_srid' => $system));
    } else {
      $found = array_key_exists(strtolower($system), kohana::config('sref_notations.sref_notations'));
    }
    return $found>0;
  }

  /**
   * Provides a wrapper for dynamic calls to a spatial reference module's
   * spatial_ref_to_wkt method (produces well known text from an sref).
   *
   * @return string Well Known Text for the point or polygon described by the sref, in
   * the WGS84 datum.
   */
  public static function sref_to_internal_wkt($sref, $sref_system)
  {
    $system = strtolower($sref_system);
    $sref = strtoupper($sref);
    if (is_numeric($system)) {
      // EPSG code
      // first check if this $system expects Lat/Long format, rather than x,y
      if(array_key_exists($system, kohana::config('sref_notations.lat_long_systems'))) {
        $wkt = self::lat_long_to_wkt($sref);
      } else {
        // else this is just a pair of numbers with a list separator
        $coords = explode(kohana::lang('misc.x_y_separator'), $sref);
        $wkt = 'POINT('.$coords[0].' '.$coords[1].')';
      }
      $srid = $system;
    } else {
      self::validateSystemClass($system);
      $wkt = call_user_func("$system::sref_to_wkt", $sref);
      $srid = call_user_func("$system::get_srid");
    }
    return self::wkt_to_internal_wkt($wkt, $srid);
  }

  /**
   * Converts WKT text in a known SRID, to WKT in internally stored srid.
   *
   * @todo Consider moving PostGIS specific code into a driver.
   */
  protected static function wkt_to_internal_wkt($wkt, $srid)
  {
    return postgreSQL::transformWkt($wkt, $srid, kohana::config('sref_notations.internal_srid'));
  }

  /*
   * Converts a internal WKT value to any output sref - either a notation, or a transformed WKT
   * @param string $wkt Well-known text string.
   * @param string $sref_system Spatial reference system code to convert to.
   * @param int $precision For systems which define accuracy in a reducing 10*10 grid (e.g. osgb), the number of digits to return.
   * @param string $output
   * @param float $metresAccuracy Approximate number of metres the point can be expected to be accurate by. E.g.
   * may be set according to the current zoom scale of the map. Provided as an alternative to $precision.
   */
  public static function internal_wkt_to_sref($wkt, $sref_system, $precision=null, $output=null, $metresAccuracy=null)
  {
    $system = strtolower($sref_system);
    if (is_numeric($system))
      $srid = $system;
    else {
      self::validateSystemClass($system);
      $srid = call_user_func("$system::get_srid");
	}
    
    $transformedWkt = postgreSQL::transformWkt($wkt, kohana::config('sref_notations.internal_srid'), $srid);
    if (is_numeric($system)) {
      // NB the handed in precision is ignored, and the rounding is determined by the system in use
      if(array_key_exists($system, kohana::config('sref_notations.lat_long_systems')))
        return self::point_to_lat_long($transformedWkt, $system, $output);
      else
        return self::point_to_x_y($transformedWkt, $system);
    } else
      return call_user_func("$system::wkt_to_sref", $transformedWkt, $precision, $output, $metresAccuracy);
  }

  /**
   * Convert a point wkt into a x, y representation.
   * @param int $system The SRID of the system, used to determine the rounding that should be applied to the x,y values.
   */
  protected static function point_to_x_y($wkt, $system)
  {
    $locale=localeconv();
    if ((bool) preg_match('/^POINT\([-+]?[0-9]*\\'.$locale['decimal_point'].'?[0-9]+[ ]*[-+]?[0-9]*\\'.$locale['decimal_point'].'?[0-9]+\)$/D', $wkt)) {
      $coords = explode(' ', substr($wkt, 6, strlen($wkt)-7));
      $roundings = kohana::config('sref_notations.roundings');
      if (array_key_exists($system, $roundings))
        $round = $roundings[$system];
      else
        $round = 0;
      return round($coords[0],$round).Kohana::lang('misc.x_y_separator')." ".round($coords[1],$round);
    } else {
      throw new Exception('point_to_x_y passed invalid wkt - '.$wkt);
    }
  }

  protected static function process_lat_long($sref)
  {
    //due to 3 different basic formats for each of lat and long, and then the various different positions of +-NSEW, process each individually
    $latitude = strtok($sref, ' '.Kohana::lang('misc.x_y_separator'));
    $longitude = strtok(' '.Kohana::lang('misc.x_y_separator'));
    $results = array();
    $locale=localeconv();
    if (strtok(' '.Kohana::lang('misc.x_y_separator')) != false)
      return false;
    if ((bool) preg_match('/^([-+NS]?)([0-9]*\\'.$locale['decimal_point'].'?[0-9]+)([NS]?)$/D', $latitude, $matches)) {
      if (($matches[1] != '' and $matches[3] != '') or $matches[2] > 90)
        return false;
      $results['lat'] = $matches[2];
      if ($matches[1] == '-' or $matches[1] == 'S' or $matches[3] == 'S')
        $results['lat'] *= -1;
    } else if ((bool) preg_match('/^([-+NS]?)([0-9]*)\\'.Kohana::lang('misc.d_m_s_separator').'([0-9]*\\'.$locale['decimal_point'].'?[0-9]+)([NS]?)$/D', $latitude, $matches)) {
      if (($matches[1] != '' and $matches[4] != '') or $matches[2] > 90 or $matches[3] > 60)
        return false;
      $results['lat'] = $matches[2]+($matches[3]/60);
      if ($matches[1] == '-' or $matches[1] == 'S' or $matches[4] == 'S')
        $results['lat'] *= -1;
    } else if ((bool) preg_match('/^([-+NS]?)([0-9]*)\\'.Kohana::lang('misc.d_m_s_separator').'([0-9]*)'.Kohana::lang('misc.d_m_s_separator').'([0-9]*\\'.$locale['decimal_point'].'?[0-9]+)([NS]?)$/D', $latitude, $matches)) {
      if (($matches[1] != '' and $matches[5] != '') or $matches[2] > 90 or $matches[3] > 60 or $matches[4] > 60)
        return false;
      $results['lat'] = $matches[2]+($matches[3]/60)+($matches[4]/3600);
      if ($matches[1] == '-' or $matches[1] == 'S' or $matches[5] == 'S')
        $results['lat'] *= -1;
    } else return false;

    if ((bool) preg_match('/^([-+EW]?)([0-9]*\\'.$locale['decimal_point'].'?[0-9]+)([EW]?)$/D', $longitude, $matches)) {
      if (($matches[1] != '' and $matches[3] != '') or $matches[2] > 180)
        return false;
      $results['long'] = $matches[2];
      if ($matches[1] == '-' or $matches[1] == 'W' or $matches[3] == 'W')
        $results['long'] *= -1;
    } else if ((bool) preg_match('/^([-+EW]?)([0-9]*)\\'.Kohana::lang('misc.d_m_s_separator').'([0-9]*\\'.$locale['decimal_point'].'?[0-9]+)([EW]?)$/D', $longitude, $matches)) {
      if (($matches[1] != '' and $matches[4] != '') or $matches[2] > 180 or $matches[3] > 60)
        return false;
      $results['long'] = $matches[2]+($matches[3]/60);
      if ($matches[1] == '-' or $matches[1] == 'W' or $matches[4] == 'W')
        $results['long'] *= -1;
    } else if ((bool) preg_match('/^([-+EW]?)([0-9]*)\\'.Kohana::lang('misc.d_m_s_separator').'([0-9]*)'.Kohana::lang('misc.d_m_s_separator').'([0-9]*\\'.$locale['decimal_point'].'?[0-9]+)([EW]?)$/D', $longitude, $matches)) {
      if (($matches[1] != '' and $matches[5] != '') or $matches[2] > 180 or $matches[3] > 60 or $matches[4] > 60)
        return false;
      $results['long'] = $matches[2]+($matches[3]/60)+($matches[4]/3600);
      if ($matches[1] == '-' or $matches[1] == 'W' or $matches[5] == 'W')
        $results['long'] *= -1;
    } else return false;

    return $results;
  }

  protected static function is_valid_lat_long($sref)
  {
    return ((bool)self::process_lat_long($sref));
  }

  protected static function lat_long_to_wkt($sref)
  {
    $results = self::process_lat_long($sref);
    if ($results === false)
      throw new Exception('lat_long_to_wkt passed invalid latitude/longitude - '.$sref);
    $wkt = 'POINT('.$results['long'].' '.$results['lat'].')';
    return $wkt;
  }

  /**
   * Convert a lat/long point WKT to a latitude and longitude display representation. 
   * @param string $wkt The well known text for a lat long point.
   * @param string $system The latitude and longitude system for both the input and output, normally 4326 for GPS/WGS84 lat longs.
   * @param string $output The optional output format if overriding the default for this system. Options are DMS, DM, or D for degrees, minutes, seconds,
   * degrees and minutes, or decimal degrees (default). 
   */
  protected static function point_to_lat_long($wkt, $system, $output=null)
  {
    $locale=localeconv();
    if ((bool) preg_match(
          '/^POINT\([-+]?[0-9]*\\'.$locale['decimal_point'].'?[0-9]+[ ]*[-+]?[0-9]*\\'.$locale['decimal_point'].'?[0-9]+\)$/D', $wkt)) {
      $lat_res = '';
      $long_res = '';
      $coords = explode(' ', substr($wkt, 6, strlen($wkt)-7));
      $long = abs($coords[0]);
      $lat = abs($coords[1]);
      $lat_long_details = kohana::config('sref_notations.lat_long_systems');
      $lat_long_detail = $lat_long_details[$system];
      if ($output==null) {
        if (array_key_exists('default_output', $lat_long_detail))
          $output = $lat_long_detail['default_output'];
        else
          $output = 'D';
      }
      if (array_key_exists('indicator', $lat_long_detail))
        $indicator = $lat_long_detail['indicator'];
      else
        $indicator = 'Prefix_NSEW';
      $roundings = kohana::config('sref_notations.roundings');
      if (array_key_exists($system, $roundings))
        $round = $roundings[$system];
      else
        $round = 0;
      if ($indicator == 'PlusMinus') {
        $long_res .= ($coords[0] < 0 ? '-' : '+');
        $lat_res .= ($coords[1] < 0 ? '-' : '+');
      } else if ($indicator == 'Minus') {
        $long_res .= ($coords[0] < 0 ? '-' : '');
        $lat_res .= ($coords[1] < 0 ? '-' : '');
      } else if ($indicator == 'Prefix_NSEW') {
        $long_res .= ($coords[0] < 0 ? 'W' : 'E');
        $lat_res .= ($coords[1] < 0 ? 'S' : 'N');
      }
      // when rounding for DMS & DM, the accuracy is reduced unless we increase the number of digits by one. 
      // This is because rounding in base 60 gives less accuracy than in base 100. To do this we show both minutes when rounding is 1, etc.
      if ($output == 'DMS') {
        $long_deg = floor($long);
        $long_min = ($round == 0 ? 0 : floor(($long-$long_deg)*60));
        $long_sec = ($round < 1 ? 0 : round((3600*($long-$long_deg)-$long_min*60), $round <= 3 ? 0 : $round - 3));
        $long_res .= $long_deg.(Kohana::lang('misc.d_m_s_separator')).$long_min.(Kohana::lang('misc.d_m_s_separator')).$long_sec;
        $lat_deg = floor($lat);
        $lat_min = ($round == 0 ? 0 : floor(($lat-$lat_deg)*60));
        $lat_sec = ($round < 1 ? 0 : round((3600*($lat-$lat_deg)-$lat_min*60), $round <= 3 ? 0 : $round - 3));
        $lat_res .= $lat_deg.(Kohana::lang('misc.d_m_s_separator')).$lat_min.(Kohana::lang('misc.d_m_s_separator')).$lat_sec;
      } else if ($output == 'DM') {
        $long_deg = floor($long);    
        $long_min = ($round == 0 ? 0 : round(($long-$long_deg)*60, $round <= 1 ? 0 : $round - 1));
        $long_res .= $long_deg.Kohana::lang('misc.d_m_s_separator').$long_min;
        $lat_deg = floor($lat);    
        $lat_min = ($round == 0 ? 0 : round(($lat-$lat_deg)*60, $round <= 1 ? 0 : $round - 1));
        $lat_res .= $lat_deg.Kohana::lang('misc.d_m_s_separator').$lat_min;
      }else {
        $long_res .= round($long,$round);
        $lat_res .= round($lat,$round);
      }
      if ($indicator == 'Postfix_NSEW') {
        $long_res .= ($coords[0] < 0 ? 'W' : 'E');
        $lat_res .= ($coords[1] < 0 ? 'S' : 'N');
      }
      return $lat_res.' '.$long_res;
    } else {
      throw new Exception('point_to_lat_long passed invalid wkt - '.$wkt);
    }
  }
}

?>
