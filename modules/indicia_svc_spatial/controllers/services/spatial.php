<?php

class Spatial_Controller extends Service_Base_Controller {

  /**
   * Convert a spatial reference to WKT format.
   *
   * Handle a service request to convert a spatial reference into WKT
   * representing the reference using the internal SRID (normally spherical
   * mercator since it is compatible with Google Maps). Optionally returns an
   * additional WKT in an alternate sref system. The response is in JSON.
   * Provide a callback in the GET request to use JSONP.
   * GET parameters allowed are
   * sref: The spatial reference to convert.
   * system: The sref system code of the spatial reference.
   * mapsystem: (optional) Sref system code for additional WKT in response.
   * callback: For returning JSONP.
   */
  public function sref_to_wkt() {
    try {
      // Test/escape parameters that are passed in to queries to prevent
      // SQL injection.
      // sref is not passed to query
      // system is validated in sref_to_internal_wkt()
      // mapsystem is validated in internal_wkt_to_wkt()
      $r = ['wkt' => spatial_ref::sref_to_internal_wkt($_GET['sref'], $_GET['system'])];
      if (array_key_exists('mapsystem', $_GET)) {
        $r['mapwkt'] = spatial_ref::internal_wkt_to_wkt($r['wkt'], $_GET['mapsystem']);
      }
      $r = json_encode($r);
      // Enable a JSONP request.
      if (array_key_exists('callback', $_GET)) {
        $r = "$_GET[callback]($r)";
        header('Content-Type: application/javascript; charset=utf-8');
      }
      echo $r;
    }
    catch (Exception $e) {
      $this->handle_error($e);
    }
  }

  /**
   * Convert WKT format text to a spatial reference notation.
   *
   * Handle a service request to convert a WKT representing the reference
   * using the internal SRID (normally spherical mercator since it is compatible with Google Maps)
   * into a spatial reference, though this can optionally be overriden by providing a wktsystem.
   * Returns the sref, plus new WKTs representing the returned sref in the internal SRID and an optional map system.
   * Note that if you pass in a point and convert it to a grid square, then the returned
   * wkts will reflect the grid square not the point. GET parameters allowed are
   * wkt: string, The WKT to convert.
   * wktsystem: int, Optional SRID for the WKT if different from internal.
   * system: int/string, The sref system code of the returned sref.
   * precision: int, For systems which define accuracy in a reducing 10*10 grid
   *   (e.g. osgb), the number of digits to return.
   * metresAccuracy: float, Approximate number of metres the point can be
   *   expected to be accurate by. E.g.may be set according to the current zoom
   *   scale of the map. Provided as an alternative to precision.
   * mapsystem: int/string, sref system code for return of optional extra WKT.
   * output: string, Options are DMS, DM, or D for degrees, minutes, seconds,
   *   degrees and minutes, or decimal degrees (default).
   * callback: For returning JSONP.
   */
  public function wkt_to_sref() {
    try {
      if (array_key_exists('precision', $_GET)) {
        $precision = $_GET['precision'];
      }
      else {
        $precision = NULL;
      }
      if (array_key_exists('metresAccuracy', $_GET)) {
        $metresAccuracy = $_GET['metresAccuracy'];
      }
      else {
        $metresAccuracy = NULL;
      }
      if (array_key_exists('output', $_GET)) {
        $output = $_GET['output'];
      }
      else {
        $output = NULL;
      }

      // Test/escape parameters that are passed in to queries to prevent
      // SQL injection.
      // system is validated in internal_wkt_to_sref() and sref_to_internal_wkt()
      // mapsystem is validated in internal_wkt_to_wkt()
      // precision, output and metresAccuracy are not used in queries.
      // $wkt is escaped before running queries.
      $wkt = $_GET['wkt'];

      if (array_key_exists('wktsystem', $_GET)) {
        // Optionally convert WKT from wktsystem.
        $wktsystem = security::checkParam($_GET['wktsystem'], 'int');
        if ($wktsystem === FALSE) {
          Kohana::log('alert', "Invalid parameter, wktsystem, with value '{$_GET['wktsystem']}' in request to spatial/wkt_to_sref service.");
          throw new Exception('Invalid request.');
        }
        $wkt = spatial_ref::wkt_to_internal_wkt($wkt, $wktsystem);
      }

      $sref = spatial_ref::internal_wkt_to_sref($wkt, $_GET['system'], $precision, $output, $metresAccuracy);
      // Note we also need to return the wkt of the actual sref, which may be a square now.
      $wkt = spatial_ref::sref_to_internal_wkt($sref, $_GET['system']);
      $r = ['sref' => $sref,'wkt' => $wkt];

      if (array_key_exists('mapsystem', $_GET)) {
        // Optionally output WKT of sref in mapsystem as well.
        $r['mapwkt'] = spatial_ref::internal_wkt_to_wkt($r['wkt'], $_GET['mapsystem']);
      }

      $r = json_encode($r);
      // Enable a JSONP request.
      if (array_key_exists('callback', $_GET)) {
        $r = "$_GET[callback]($r)";
        header('Content-Type: application/javascript; charset=utf-8');
      }
      echo $r;
    }
    catch (Exception $e) {
      $this->handle_error($e);
    }
  }

  /**
   * Allow a service request to triangulate between 2 systems. GET parameters are
   * from_sref: The spatial reference to convert from.
   * from_system: The sref system code of from_sref.
   * to_system: The sref system code to convert to.
   * precision: (optional) For systems which define accuracy in a reducing 10*10
   *   grid (e.g. osgb), the number of digits to return.
   * metresAccuracy: (optional) Approximate number of metres the point can be
   *   expected to be accurate by. E.g.may be set according to the current zoom
   *   scale of the map. Provided as an alternative to precision.
   */
  public function convert_sref() {
    try {
      // Test/escape parameters that are passed in to queries to prevent
      // SQL injection.
      // from_sref is not passed to query
      // from_system is validated in sref_to_internal_wkt()
      // to_systen us validated in internal_wkt_to_sref()
      // precision and metresAccuracy are not used in queries.
      $wkt = spatial_ref::sref_to_internal_wkt($_GET['from_sref'], $_GET['from_system']);
      if (array_key_exists('precision', $_GET)) {
        $precision = $_GET['precision'];
      }
      else {
        $precision = NULL;
      }
      if (array_key_exists('metresAccuracy', $_GET)) {
        $metresAccuracy = $_GET['metresAccuracy'];
      }
      else {
        $metresAccuracy = NULL;
      }
      echo spatial_ref::internal_wkt_to_sref($wkt, $_GET['to_system'], $precision, NULL, $metresAccuracy);
    }
    catch (Exception $e) {
      $this->handle_error($e);
    }
  }

  /**
   * Service method to buffer a provided wkt.
   *
   * Provide GET parameters for wkt (string) and buffer (a number of metres).
   * Will return the well known text for the buffered geometry.
   * If a callback function name is given in the GET parameters then returns
   * a JSONP response with a json object that has a single response property.
   *
   * Parameters can be provided via GET or POST and include:
   * * wkt - well known text of the polygon.
   * * buffer - buffer size in metres.
   * * segmentsInQuarterCircle - number of segments used to create a quarter
   *   circle. Default is 8 but can be lowered to simplify the polygon.
   * * projection - projection EPSG code used for the calculation. 3857 (web
   *   mercator) is default.
   */
  public function buffer() {
    $params = array_merge($_GET, $_POST);
    if (array_key_exists('wkt', $params) && array_key_exists('buffer', $params)) {
      $params = array_merge([
        'segmentsInQuarterCircle' => 8,
        'projection' => 3857,
      ], $params);
      if ($params['buffer'] === 0) {
        // No need to buffer if width set to zero.
        $r = $params['wkt'];
      }
      else {
        $db = new Database();
        // Test/escape parameters that are passed in to queries to prevent
        // SQL injection.
        $wkt = pg_escape_literal($db->getLink(), $params['wkt']);
        $buffer = security::checkParam($params['buffer'], 'int');
        $params['segmentsInQuarterCircle'] = security::checkParam($params['segmentsInQuarterCircle'], 'int');
        $params['projection'] = security::checkParam($params['projection'], 'int');
        if ($buffer === FALSE) {
          Kohana::log('alert', "Invalid parameter, buffer, with value '{$params['buffer']}' in request to spatial/buffer service.");
          throw new Exception('Invalid request.');
        }
        if ($params['projection'] !== 900913 && $params['projection'] !== 3857) {
          // If specifying the projection we have to transform to and fro.
          $qry = <<<SQL
            SELECT st_astext(
              st_transform(
                st_buffer(
                  st_transform(st_geomfromtext($wkt, 3857), $params[projection]),
                  $buffer,
                  $params[segmentsInQuarterCircle]
                ),
                3857
              )
            ) AS wkt;
          SQL;
        }
        else {
          $qry = <<<SQL
            SELECT st_astext(
              st_buffer(
                st_geomfromtext($wkt),
                $buffer,
                $params[segmentsInQuarterCircle]
              )
            ) AS wkt;
          SQL;
        }
        $result = $db->query($qry)->current();
        $r = $result->wkt;
      }
    } else {
      $r = 'No wkt or buffer to process';
    }
    // Enable a JSONP request.
    if (array_key_exists('callback', $_REQUEST)) {
      $json = json_encode(['response' => $r]);
      $r = "$_REQUEST[callback]($json)";
      header('Content-Type: application/javascript; charset=utf-8');
    }
    echo $r;
  }

}