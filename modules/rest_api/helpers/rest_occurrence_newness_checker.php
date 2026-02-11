<?php

/**
 * @file
 * Helper class for checking record newness badges via the REST API.
 *
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Helper class for checking record newness.
 *
 * Provides methods to determine if a wildlife record is new to the species,
 * new to a specific grid square, new to the current year, or new within a
 * specific group.
 */
class rest_occurrence_newness_checker {

  /**
   * The Elasticsearch configuration for the proxy.
   *
   * @var array
   */
  private static $esConfig;

  /**
   * Check if a record is new based on various criteria.
   *
   * @param string $externalKey
   *   External key (taxon.accepted_taxon_id) for the species.
   * @param float $lat
   *   Latitude of the record (WGS84).
   * @param float $lon
   *   Longitude of the record (WGS84).
   * @param string $gridSquareSize
   *   Grid square size: '1km', '2km', or '10km'. Optional.
   * @param int $year
   *   Year to check for newness. Optional.
   * @param int $groupId
   *   Group ID to filter by. Optional.
   *
   * @return array
   *   Associative array with badges returned conditionally:
   *   - is_new_global: bool (always included)
   *   - is_new_for_year: bool (only if $year provided)
   *   - is_new_for_grid: bool (only if $gridSquareSize and lat/lon provided)
   *   - is_new_for_group: bool (only if $groupId provided)
   *   - grid_square: string (only if grid square was calculated)
   *
   * @throws Exception
   */
  public static function checkNewness($externalKey, $lat = NULL, $lon = NULL,
      $gridSquareSize = NULL, $year = NULL, $groupId = NULL) {
    self::$esConfig = kohana::config('rest.elasticsearch');

    // Validate parameters.
    self::validateParameters($externalKey, $lat, $lon, $gridSquareSize);

    $response = [
      'is_new_global' => self::checkGlobalNewness($externalKey),
    ];

    if (!empty($year)) {
      $response['is_new_for_year'] = self::checkYearNewness($externalKey, $year);
    }

    if (!empty($gridSquareSize) && $lat !== NULL && $lon !== NULL) {
      $gridSquare = self::getGridSquare($lat, $lon, $gridSquareSize);
      $response['is_new_for_grid'] = self::checkGridSquareNewness($externalKey, $gridSquare, $gridSquareSize);
    }

    if (!empty($groupId)) {
      $response['is_new_for_group'] = self::checkGroupNewness($externalKey, $groupId);
    }

    return $response;
  }

  /**
   * Validate the input parameters.
   *
   * @param string $externalKey
   *   External key for the species.
   * @param float $lat
   *   Latitude (optional).
   * @param float $lon
   *   Longitude (optional).
   * @param string $gridSquareSize
   *   Grid square size (optional).
   *
   * @throws Exception
   */
  private static function validateParameters($externalKey, $lat, $lon, $gridSquareSize) {
    if (empty($externalKey)) {
      throw new Exception('external_key parameter is required.', 400);
    }

    // If gridSquareSize provided, both lat and lon must be provided.
    if (!empty($gridSquareSize) && ($lat === NULL || $lon === NULL)) {
      throw new Exception('Both lat and lon parameters must be provided when grid_square_size is specified.', 400);
    }

    // If lat/lon provided, grid_square_size must also be provided or neither should be provided.
    if (($lat !== NULL || $lon !== NULL) && empty($gridSquareSize)) {
      throw new Exception('grid_square_size parameter must be provided when lat and lon are specified.', 400);
    }

    if (!empty($lat) && (!is_numeric($lat) || $lat < -90 || $lat > 90)) {
      throw new Exception('Latitude must be a number between -90 and 90.', 400);
    }

    if (!empty($lon) && (!is_numeric($lon) || $lon < -180 || $lon > 180)) {
      throw new Exception('Longitude must be a number between -180 and 180.', 400);
    }

    if (!empty($gridSquareSize) && !in_array($gridSquareSize, ['1km', '2km', '10km'])) {
      throw new Exception('grid_square_size must be one of: 1km, 2km, 10km.', 400);
    }
  }

  /**
   * Check if the species has been recorded globally.
   *
   * @param string $externalKey
   *   External key (taxon.accepted_taxon_id).
   *
   * @return bool
   *   TRUE if the species has not been recorded (new).
   */
  private static function checkGlobalNewness($externalKey) {
    $esQuery = self::buildEsQuery($externalKey);
    return !self::checkEsRecordExists($esQuery);
  }

  /**
   * Check if the species was recorded in the specified year.
   *
   * @param string $externalKey
   *   External key (taxon.accepted_taxon_id).
   * @param int $year
   *   Year to check.
   *
   * @return bool
   *   TRUE if the species has not been recorded in that year (new).
   */
  private static function checkYearNewness($externalKey, $year) {
    $esQuery = self::buildEsQuery($externalKey);
    // Add year filter.
    $esQuery['query']['bool']['must'][] = [
      'term' => [
        'event.year' => $year
      ],
    ];
    return !self::checkEsRecordExists($esQuery);
  }

  /**
   * Check if the species was recorded in the specified grid square.
   *
   * @param string $externalKey
   *   External key (taxon.accepted_taxon_id).
   * @param string $gridSquare
   *   Grid square centre point in WKT format "POINT(lon lat)".
   * @param string $gridSquareSize
   *   Grid square size: '1km', '2km', or '10km'.
   *
   * @return bool
   *   TRUE if the species has not been recorded in that grid square (new).
   */
  private static function checkGridSquareNewness($externalKey, $gridSquare, $gridSquareSize) {
    $esQuery = self::buildEsQuery($externalKey);
    // Add grid square filter. Extract point from WKT.
    preg_match('/POINT\(([-\d.]+)\s+([-\d.]+)\)/', $gridSquare, $matches);
    if (!empty($matches)) {
      $lon = $matches[1];
      $lat = $matches[2];
      // Query the grid square field based on size, with an arbitrary small
      // buffer of 0.001 degrees.
      $esQuery['query']['bool']['must'][] = [
        'term' => [
          "location.grid_square.$gridSquareSize.centre" => "$lon $lat",
        ],
      ];
    }
    kohana::log('debug', json_encode($esQuery));
    return !self::checkEsRecordExists($esQuery);
  }

  /**
   * Check if the species was recorded within a group.
   *
   * @param string $externalKey
   *   External key (taxon.accepted_taxon_id).
   * @param int $groupId
   *   Group ID.
   *
   * @return bool
   *   TRUE if the species has not been recorded in that group (new).
   */
  private static function checkGroupNewness($externalKey, $groupId) {
    $esQuery = self::buildEsQuery($externalKey);
    // Add group filter.
    $esQuery['query']['bool']['must'][] = [
      'term' => [
        'metadata.group.id' => $groupId
      ],
    ];
    return !self::checkEsRecordExists($esQuery);
  }

  /**
   * Build a base Elasticsearch query for counting records.
   *
   * @param string $externalKey
   *   External key (taxon.accepted_taxon_id).
   *
   * @return array
   *   Elasticsearch query structure.
   */
  private static function buildEsQuery($externalKey) {
    $mustClauses = [
      [
        'term' => [
          'taxon.accepted_taxon_id' => $externalKey,
        ],
      ],
      [
        'term' => [
          'metadata.website.id' => RestObjects::$clientWebsiteId,
        ],
      ],
    ];

    return [
      'size' => 0,
      'track_total_hits' => TRUE,
      'query' => [
        'bool' => [
          'must' => $mustClauses,
        ],
      ],
    ];
  }

  /**
   * Check if any record exists for the given Elasticsearch query.
   *
   * @param array $esQuery
   *   Elasticsearch query structure.
   *
   * @return bool
   *   TRUE if at least one record exists, FALSE otherwise.
   */
  private static function checkEsRecordExists(array $esQuery) {
    try {
      // Get the Elasticsearch configuration.
      $esConfig = self::$esConfig;
      if (empty($esConfig)) {
        throw new Exception('Elasticsearch is not configured.');
      }

      $endpoint = key($esConfig);
      $config = $esConfig[$endpoint];

      if (empty($config['url']) || empty($config['index'])) {
        throw new Exception('Elasticsearch endpoint not properly configured.');
      }

      // Build the URL.
      $url = $config['url'] . '/' . $config['index'] . '/_search';

      // Prepare the query as JSON.
      $queryJson = json_encode($esQuery);

      // Use curl to make the request.
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_POST, 1);
      curl_setopt($session, CURLOPT_POSTFIELDS, $queryJson);
      curl_setopt($session, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      curl_setopt($session, CURLOPT_HEADER, FALSE);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);

      $response = curl_exec($session);
      $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
      curl_close($session);

      if ($httpCode !== 200) {
        $responseDecoded = json_decode($response, TRUE);
        $error = $responseDecoded['error']['root_cause'][0]['reason'] ?? 'Unknown error';
        kohana::log('error', 'Elasticsearch query failed: ' . $error);
        throw new Exception('Elasticsearch query failed.');
      }

      // Parse the response.
      $response = json_decode($response, TRUE);

      // Check if any hits exist.
      if (isset($response['hits']['total'])) {
        $total = is_array($response['hits']['total'])
          ? $response['hits']['total']['value']
          : $response['hits']['total'];
        return $total > 0;
      }

      return FALSE;
    }
    catch (Exception $e) {
      // Log the error and re-throw.
      kohana::log('error', 'Elasticsearch query failed in rest_occurrence_newness_checker: ' . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Convert a point to its grid square centre using PostGIS.
   *
   * @param float $lat
   *   Latitude (WGS84).
   * @param float $lon
   *   Longitude (WGS84).
   * @param string $gridSquareSize
   *   Grid square size: '1km', '2km', or '10km'.
   *
   * @return string
   *   Grid square centre point as WKT "POINT(lon lat)".
   */
  private static function getGridSquare($lat, $lon, $gridSquareSize) {
    $precision = match($gridSquareSize) {
      '1km' => 1000,
      '2km' => 2000,
      '10km' => 10000,
      default => 1000,
    };

    $sql = <<<SQL
      SELECT TRIM(leading 'POINT(' FROM TRIM(trailing ')' FROM st_astext(
        st_centroid(
          st_transform(
            reduce_precision(
              st_transform(st_geomfromtext(?, 4326), 900913),
              false,
              ?
            ),
            4326
          )
        )
      ))) as grid_point
    SQL;

    $wktPoint = "POINT($lon $lat)";
    $result = RestObjects::$db->query($sql, [$wktPoint, $precision])->current();

    if (empty($result)) {
      throw new Exception('Failed to calculate grid square.');
    }

    return 'POINT(' . $result->grid_point . ')';
  }

}
