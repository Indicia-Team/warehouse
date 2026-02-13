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
   * @param array $externalKeys
   *   External keys (taxon.accepted_taxon_id) for the species to check at this
   *   location.
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
  public static function checkNewness($externalKeys, $lat = NULL, $lon = NULL,
      $gridSquareSize = NULL, $year = NULL, $groupId = NULL) {
    self::$esConfig = kohana::config('rest.elasticsearch');

    // This helper expects an array of external keys (comma-separated list
    // parsed by the controller). Do not support single-key external_key.
    if (!is_array($externalKeys)) {
      throw new Exception('external_keys must be provided as an array.', 400);
    }
    return self::checkNewnessBulk($externalKeys, $lat, $lon, $gridSquareSize, $year, $groupId);
  }

  /**
   * Bulk check for multiple external keys.
   *
   * @param array $externalKeys
   *   Array of external_key strings.
   * @param float $lat
   * @param float $lon
   * @param string $gridSquareSize
   * @param int $year
   * @param int $groupId
   *
   * @return array
   *   Array of result objects, one per external_key.
   */
  private static function checkNewnessBulk(array $externalKeys, ?float $lat = NULL, ?float $lon = NULL, $gridSquareSize = NULL, ?int $year = NULL, ?int $groupId = NULL) {
    self::validateParameters($externalKeys, $lat, $lon, $gridSquareSize);

    // Prepare initial result objects.
    $results = [];
    foreach ($externalKeys as $k) {
      $res = ['external_key' => $k, 'is_new_global' => TRUE];
      if (!empty($year)) {
        $res['is_new_for_year'] = TRUE;
      }
      if (!empty($gridSquareSize)) {
        $res['is_new_for_grid'] = TRUE;
      }
      if (!empty($groupId)) {
        $res['is_new_for_group'] = TRUE;
      }
      $results[$k] = $res;
    }

    // Build base query with terms filter for all keys.
    $baseQuery = self::buildEsQuery($externalKeys);

    // 1) Global existence aggregation.
    $globalQuery = $baseQuery;
    $globalBuckets = self::executeEsAggregation($globalQuery);
    self::applyAggOutputToResults($globalBuckets, 'is_new_global', $results);

    // 2) Year check.
    if (!empty($year)) {
      $yearQuery = $baseQuery;
      $yearQuery['query']['bool']['must'][] = [
        'term' => ['event.year' => (int) $year],
      ];
      $yearBuckets = self::executeEsAggregation($yearQuery);
      self::applyAggOutputToResults($yearBuckets, 'is_new_for_year', $results);
    }

    // 3) Grid square check.
    if (!empty($gridSquareSize)) {
      // Find the centre point of the containing grid square which we can filter against.
      $gridSquare = self::getGridSquare($lat, $lon, $gridSquareSize);
      // ES stores the centre as a string like "<lon> <lat>" in the
      // location.grid_square.<size>.centre field; compare exact string.
      $gridField = 'location.grid_square.' . $gridSquareSize . '.centre';

      $gridQuery = $baseQuery;
      $gridQuery['query']['bool']['must'][] = [
        'term' => [ $gridField => $gridSquare ],
      ];
      $gridBuckets = self::executeEsAggregation($gridQuery);
      self::applyAggOutputToResults($gridBuckets, 'is_new_for_grid', $results);
    }

    // 4) Group check.
    if (!empty($groupId)) {
      $groupQuery = $baseQuery;
      $groupQuery['query']['bool']['must'][] = [
        'term' => ['metadata.group.id' => $groupId],
      ];
      $groupBuckets = self::executeEsAggregation($groupQuery);
      self::applyAggOutputToResults($groupBuckets, 'is_new_for_group', $results);
    }

    // Return results as array of objects in same order as keys provided.
    $out = [];
    foreach ($externalKeys as $k) {
      $out[] = $results[$k];
    }
    return $out;
  }

  /**
   * Merge the output of a bucket aggregation into the results.
   *
   * Find the results entry for each taxon in the aggregation output taxon key,
   * then update the appropriate newness flag according to whether there are
   * prior records.
   *
   * @param array $buckets
   *   Buckets array returned by an appropriately filter ES request.
   * @param mixed $flagName
   *   Name of the flag to update, e.g. is_new_for_year.
   * @param array $results
   *   Results array which will be updated. Should be keyed by the taxon
   *   accepted ID.
   */
  private static function applyAggOutputToResults(array $buckets, $flagName, array &$results) {
    foreach ($buckets as $bucket) {
      $key = $bucket['key'];
      if (isset($results[$key])) {
        $results[$key][$flagName] = ($bucket['doc_count'] === 0) ? TRUE : FALSE;
      }
    }
  }

  /**
   * Validate the input parameters.
   *
   * @param array $externalKeys
   *   External keys for the species.
   * @param float $lat
   *   Latitude (optional).
   * @param float $lon
   *   Longitude (optional).
   * @param string $gridSquareSize
   *   Grid square size (optional).
   *
   * @throws Exception
   */
  private static function validateParameters(array $externalKeys, $lat, $lon, $gridSquareSize) {
    if (empty($externalKeys)) {
      throw new Exception('external_keys parameter is required.', 400);
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
   * Build ES base query for multiple external keys (terms filter).
   *
   * @param array $externalKeys
   * @return array
   */
  private static function buildEsQuery(array $externalKeys) {
    $mustClauses = [
      [
        'terms' => [
          'taxon.accepted_taxon_id' => $externalKeys,
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
      'aggs' => [
        'by_taxon' => [
          'terms' => [
            'field' => 'taxon.accepted_taxon_id',
            'size' => count($externalKeys),
          ],
        ],
      ],
    ];
  }

  /**
   * Execute an ES search with aggregations and return the buckets for 'by_taxon'.
   *
   * @param array $esQuery
   *   Elasticsearch query to execute.
   *
   * @return array
   *   Array of buckets with keys 'key' and 'doc_count'.
   */
  private static function executeEsAggregation(array $esQuery) {
    try {
      $esConfig = self::$esConfig;
      if (empty($esConfig)) {
        throw new Exception('Elasticsearch is not configured.');
      }
      $endpoint = key($esConfig);
      $config = $esConfig[$endpoint];
      if (empty($config['url']) || empty($config['index'])) {
        throw new Exception('Elasticsearch endpoint not properly configured.');
      }
      $url = $config['url'] . '/' . $config['index'] . '/_search';
      $queryJson = json_encode($esQuery);

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
        kohana::log('error', 'Elasticsearch aggregation failed: ' . $error);
        throw new Exception('Elasticsearch aggregation failed.');
      }

      $response = json_decode($response, TRUE);
      $buckets = $response['aggregations']['by_taxon']['buckets'] ?? [];
      return $buckets;
    }
    catch (Exception $e) {
      kohana::log('error', 'Elasticsearch aggregation error: ' . $e->getMessage());
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
   *   Grid square centre point string, format "lon lat".
   */
  private static function getGridSquare($lat, $lon, $gridSquareSize): string {
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

    return $result->grid_point;
  }

}
