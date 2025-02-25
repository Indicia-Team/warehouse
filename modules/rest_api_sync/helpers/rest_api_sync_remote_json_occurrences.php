<?php

/**
 * @file
 * Helper class for synchronising records from an iNaturalist server.
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

defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class for syncing to the JSON occurrences API of another server.
 *
 * Could be an Indicia warehouse, or another server implementing the same
 * standard.
 */
class rest_api_sync_remote_json_occurrences {

  /**
   * Synchronise a set of data loaded from the other server.
   *
   * @param string $serverId
   *   ID of the server as defined in the configuration.
   * @param array $server
   *   Server configuration.
   */
  public static function syncServer($serverId, array $server) {
    // Count of pages done in this run.
    $pageCount = 0;
    // If last run still going, not on first page.
    $firstPage = !variable::get("rest_api_sync_{$serverId}_next_run");
    if ($firstPage) {
      // Track when we started this run, so the next run can pick up all
      // changes.
      $timestampAtStart = date('c');
      variable::set("rest_api_sync_{$serverId}_next_run", $timestampAtStart);
    }
    do {
      $syncStatus = self::syncPage($serverId, $server);
      $pageCount++;
      ob_flush();
    } while ($syncStatus['moreToDo'] && $pageCount < MAX_PAGES);
    if (!$syncStatus['moreToDo']) {
      variable::set("rest_api_sync_{$serverId}_last_run", variable::get("rest_api_sync_{$serverId}_next_run"));
      variable::delete("rest_api_sync_{$serverId}_next_run");
      variable::delete("rest_api_sync_{$serverId}_last_id");
    }
  }

  public static function loadControlledTerms() {
  }

  /**
   * Synchronise a single page of data loaded from the server.
   *
   * @param string $serverId
   *   ID of the server as defined in the configuration.
   * @param array $server
   *   Server configuration.
   *
   * @return array
   *   Status info.
   */
  public static function syncPage($serverId, array $server) {
    $db = Database::instance();
    api_persist::initDwcAttributes($db, $server['survey_id']);
    $nextPage = variable::get("rest_api_sync_{$serverId}_next_page", [], FALSE);
    $data = rest_api_sync_utils::getDataFromRestUrl(
      "$server[url]?" . http_build_query($nextPage),
      $serverId
    );
    $taxon_list_id = Kohana::config('rest_api_sync.taxon_list_id');
    $tracker = ['inserts' => 0, 'updates' => 0, 'errors' => 0];
    foreach ($data['data'] as $record) {
      // @todo Make sure all fields in specification are handled
      // @todo occurrence.associated_media
      // @todo occurrence.occurrence_status
      // @todo occurrence.organism_quantity
      // @todo occurrence.organism_quantity_type
      // @todo occurrence.otherCatalogNumbers
      // @todo event.samplingProtocol
      // @todo location.geodeticDatum
      $parsedDate = self::parseDates($record['event']['eventDate']);
      try {
        $observation = [
          'licenceCode' => empty($record['record-level']['license']) ? NULL : $record['record-level']['license'],
          'collectionCode' => empty($record['record-level']['collectionCode']) ? NULL : $record['record-level']['collectionCode'],
          'id' => $record['occurrence']['occurrenceID'],
          'individualCount' => empty($record['occurrence']['individualCount']) ? NULL : $record['occurrence']['individualCount'],
          'lifeStage' => empty($record['occurrence']['lifeStage']) ? NULL : $record['occurrence']['lifeStage'],
          'recordedBy' => $record['occurrence']['recordedBy'],
          'occurrenceRemarks' => empty($record['occurrence']['occurrenceRemarks']) ? NULL : $record['occurrence']['occurrenceRemarks'],
          'reproductiveCondition' => empty($record['occurrence']['reproductiveCondition']) ? NULL : $record['occurrence']['reproductiveCondition'],
          'sex' => empty($record['occurrence']['sex']) ? NULL : $record['occurrence']['sex'],
          'sensitivityPrecision' => empty($record['occurrence']['sensitivityBlur']) ? NULL : $record['occurrence']['sensitivityBlur'],
          'organismKey' => $record['taxon']['taxonID'],
          'taxonVersionKey' => empty($record['taxon']['taxonNameID']) ? NULL : $record['taxon']['taxonNameID'],
          'eventId' => empty($record['event']['eventID']) ? NULL : $record['event']['eventID'],
          'startDate' => $parsedDate['start'],
          'endDate' => $parsedDate['end'],
          'dateType' => $parsedDate['type'],
          'samplingProtocol' => empty($record['event']['samplingProtocol']) ? NULL : $record['event']['samplingProtocol'],
          'coordinateUncertaintyInMeters' => empty($record['location']['coordinateUncertaintyInMeters']) ? NULL : $record['location']['coordinateUncertaintyInMeters'],
          'siteName' => empty($record['location']['locality']) ? NULL : $record['location']['locality'],
          'identifiedBy' => empty($record['identification']['identifiedBy']) ? NULL : $record['identification']['identifiedBy'],
          'identificationVerificationStatus' => empty($record['identification']['identificationVerificationStatus']) ? NULL : $record['identification']['identificationVerificationStatus'],
          'identificationRemarks' => empty($record['identification']['identificationRemarks']) ? NULL : $record['identification']['identificationRemarks'],
        ];
        if (!empty($record['location']['decimalLongitude']) && !empty($record['location']['decimalLatitude'])) {
          // Json_decode() converts some floats to scientific notation, so
          // reverse that.
          $observation['east'] = rtrim(number_format($record['location']['decimalLongitude'], 12), 0);
          $observation['north'] = rtrim(number_format($record['location']['decimalLatitude'], 12), 0);
          $observation['projection'] = 'WGS84';
        }
        elseif (!empty($record['location']['gridReference'])) {
          $observation['gridReference'] = strtoupper(str_replace(' ', '', $record['location']['gridReference']));
          if (preg_match('/^I?[A-Z]\d*[A-NP-Z]?$/', $observation['gridReference'])) {
            $observation['projection'] = 'OSI';
            $observation['gridReference'] = preg_replace('/^I/', '', $observation['gridReference']);
          }
          elseif (preg_match('/^[A-Z][A-Z]\d*[A-NP-Z]?$/', $observation['gridReference'])) {
            $observation['projection'] = 'OSGB';
          }
          else {
            throw new exception('Invalid grid reference format: ' . $record['location']['gridReference']);
          }
        }
        if (!empty($record['record-level']['dynamicProperties']) && !empty($record['record-level']['dynamicProperties']['verifierOnlyData'])) {
          $observation['verifierOnlyData'] = json_encode($record['record-level']['dynamicProperties']['verifierOnlyData']);
          unset($record['record-level']['dynamicProperties']['verifierOnlyData']);
        }
        self::processOtherFields($server, $record, $observation);
        // Allow custom handler functions to alter the record, or reject it.
        if (!empty($server['customHandlers'])) {
          foreach ($server['customHandlers'] as $handler) {
            if (self::$handler($db, $server, $record, $observation) === FALSE) {
              // Go to next record (2 = skip parent level of foreach).
              continue 2;
            }
          }
        }
        $is_new = api_persist::taxonObservation(
          $db,
          $observation,
          $server['website_id'],
          $server['survey_id'],
          $taxon_list_id,
          $server['allowUpdateWhenVerified'],
          $server['dontOverwriteExistingRecordVerificationStatus']
        );
        if ($is_new !== NULL) {
          $tracker[$is_new ? 'inserts' : 'updates']++;
        }
        $db->query(<<<SQL
          UPDATE rest_api_sync_skipped_records SET current=false
          WHERE server_id=? AND source_id=? AND dest_table='occurrences'
        SQL, [$serverId, $record['occurrence']['occurrenceID']]);
      }
      catch (exception $e) {
        rest_api_sync_utils::log(
          'error',
          "Error occurred submitting an occurrence with ID {$record['occurrence']['occurrenceID']}\n" . $e->getMessage(),
          $tracker
        );
        $msg = pg_escape_string($db->getLink(), $e->getMessage());
        $createdById = isset($_SESSION['auth_user']) ? $_SESSION['auth_user']->id : 1;
        $sql = <<<QRY
INSERT INTO rest_api_sync_skipped_records (
  server_id,
  source_id,
  dest_table,
  error_message,
  current,
  created_on,
  created_by_id
)
VALUES (
  ?,
  ?,
  'occurrences',
  ?,
  true,
  now(),
  ?
)
QRY;
        $db->query($sql, [$serverId, $record['occurrence']['occurrenceID'], $msg, $createdById]);
      }
    }
    variable::set("rest_api_sync_{$serverId}_next_page", $data['paging']['next']);
    rest_api_sync_utils::log(
      'info',
      "<strong>Observations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]"
    );
    $r = [
      'moreToDo' => count($data['data']) > 0,
      // No way of determining the following.
      'pagesToGo' => NULL,
      'recordsToGo' => NULL,
    ];
    return $r;
  }

  /**
   * Custom handling for BTO Odonata data.
   *
   * * Rejects Odonata data if grid reference not at least 1km precision.
   * * Rejects Odonata data below a certain ID as these records already sent to
   *   the recording scheme.
   * * Annotates the record if the grid ref is <= 1km but coordinate
   *   uncertainty > 1000m.
   *
   * @param object $db
   *   Connection.
   * @param array $server
   *   Configuration for the remote server.
   * @param array $record
   *   Record data read from the database.
   * @param array $observation
   *   Observation details array that will be updated with results of this custom handling.
   *
   * @return bool
   *   True if the record is to be included, false if rejected.
   */
  private static function btoCheckOdonata($db, array $server, array $record, array &$observation) {
    // BTO automatic verifications are ignored.
    $observation['identificationVerificationStatus'] = 'unconfirmed';
    $sql = <<<SQL
      SELECT count(ctp.*)
      FROM taxa t
      JOIN cache_taxa_taxon_lists cttl on cttl.taxon_id=t.id
      JOIN cache_taxon_paths ctp ON ctp.taxon_meaning_id=cttl.taxon_meaning_id AND ctp.path && ARRAY[?]
      WHERE t.organism_key=?
      AND t.deleted=false
    SQL;
    $isOdonataCheck = $db->query($sql, [$server['odonataTaxonMeaningId'], $observation['organismKey']])->current()->count > 0;
    if ($isOdonataCheck) {
      // Skip records already provided to BTO.
      $numericId = (integer) str_replace(['BTO', 'OBS'], '', $observation['id']);
      if ($numericId <= 290186151) {
        return FALSE;
      }
      $hasPinpoint = FALSE;
      if (!empty($record['record-level']['dynamicProperties']) && !empty($record['record-level']['dynamicProperties']['pinpoints'])) {
        // Pinpoint provided so use the first pinpoint given.
        $proposedInputGridRef = $record['record-level']['dynamicProperties']['pinpoints'][0]['gridref'];
        if (preg_match('/^I?[A-Z]\d*[A-NP-Z]?$/', $proposedInputGridRef)) {
          $proposedInputSystem = 'OSI';
          $proposedInputGridRef = preg_replace('/^I/', '', $proposedInputGridRef);
          $hasPinpoint = TRUE;
        }
        elseif (preg_match('/^[A-Z][A-Z]\d*[A-NP-Z]?$/', $proposedInputGridRef)) {
          $proposedInputSystem = 'OSGB';
          $hasPinpoint = TRUE;
        }
        if ($hasPinpoint) {
          $proposedLocationAccuracy = NULL;
          $proposedBtoOriginalCoord = api_persist::formatLatLong($observation['north'], $observation['east']);
          $proposedBtoCoordinateUncertainty = empty($observation['coordinateUncertaintyInMeters']) ? 'unknown' : $observation['coordinateUncertaintyInMeters'];
          $proposedComment = 'First pinpoint grid reference used for record location.';
        }
      }
      // If we failed to find a pinpoint, or it wasn't in a recognisable format
      // then look at the site or record coordinates for the spatial info.
      if (!$hasPinpoint) {
        if (empty($observation['coordinateUncertaintyInMeters']) || $observation['coordinateUncertaintyInMeters'] > 500) {
          // No coordinate uncertainty supplied, or it is > 500, so the
          // coordinate is a site centroid. Therefore we can use a 1km square
          // covering the point.
          $info = self::getLocationSridAndWkt($db, $observation);
          // Create a 1km square that contains the point.
          if ($info->srid == 27700) {
            $proposedInputGridRef = osgb::wkt_to_sref($info->wkt, 4);
            $proposedInputSystem = 'OSGB';
          }
          elseif ($info->srid == 29903) {
            $proposedInputGridRef = osie::wkt_to_sref($info->wkt, 4);
            $proposedInputSystem = 'OSI';
          }
          elseif ($info->srid == 23030) {
            $proposedInputGridRef = utm30ed50::wkt_to_sref($info->wkt, 4);
            $proposedInputSystem = 'utm30ed50';
          }
          $proposedLocationAccuracy = NULL;
          $proposedBtoOriginalCoord = api_persist::formatLatLong($observation['north'], $observation['east']);
          $proposedBtoCoordinateUncertainty = empty($observation['coordinateUncertaintyInMeters']) ? 'unknown' : $observation['coordinateUncertaintyInMeters'];
          $proposedComment = 'Grid reference set to a 1km square covering the provided point.';
          // 1km square accurracy for comparison with site grid ref.
          $accuracyOfProposal = 500;
        }
        else {
          // There is a coordinate uncertainty and it is <= 500 (i.e. 1km or
          // better). In this instance we just set the input grid ref to the
          // supplied point, allowing the standard processes to calculate the
          // output grid ref as it will be 1km or better anyway.
          $proposedInputGridRef = api_persist::formatLatLong($observation['north'], $observation['east']);
          $proposedInputSystem = 'WGS84';
          $proposedLocationAccuracy = $observation['coordinateUncertaintyInMeters'];
          $proposedBtoOriginalCoord = NULL;
          $proposedBtoCoordinateUncertainty = NULL;
          $proposedComment = NULL;
          // Given accurracy used for comparison with site grid ref.
          $accuracyOfProposal = $proposedLocationAccuracy;
        }
        // If there is a site grid ref which has greater precision than the
        // proposed input grid ref, use the site grid ref, unless using a
        // pinpoint.
        if (!$hasPinpoint && !empty($record['record-level']['dynamicProperties']) && !empty($record['record-level']['dynamicProperties']['siteGridRef'])
            && preg_match('/^(?P<bigsquare>[A-Z][A-Z]?)(?P<square>\d*)(?P<suffix>[A-Z]?)$/', $record['record-level']['dynamicProperties']['siteGridRef'], $gridRefParts)) {
          $coordLen = strlen($gridRefParts['square']) / 2;
          $sqSize = pow(10, 5 - $coordLen);
          if ($coordLen === 1 && strlen($gridRefParts['dinty']) === 1) {
            $sqSize /= 5;
          }
          $siteGridRefPrecision = $sqSize / 2;
          if ($siteGridRefPrecision < $accuracyOfProposal) {
            kohana::log('debug', 'Replacing with siteGridRef for better accuracy');
            $proposedInputGridRef = $record['record-level']['dynamicProperties']['siteGridRef'];
            $proposedInputSystem = preg_match('/^[A-Z][A-Z]/', $proposedInputGridRef) ? 'OSGB' : 'OSI';
            $proposedLocationAccuracy = NULL;
            $proposedBtoOriginalCoord = api_persist::formatLatLong($observation['north'], $observation['east']);
            $proposedBtoCoordinateUncertainty = $observation['coordinateUncertaintyInMeters'];
            $proposedComment = 'BTO site grid reference used.';
          }
        }
        if ($hasPinpoint) {
          echo '<pre>';
          var_export($record); echo "\n";
          var_export($observation); echo "\n";
          echo '</pre>';
        }
      }
      // Set the values into the record to save.
      if ($proposedInputSystem === 'WGS84') {
        unset($observation['gridReference']);
      }
      else {
        $observation['gridReference'] = $proposedInputGridRef;
      }
      $observation['projection'] = $proposedInputSystem;
      $observation['coordinateUncertaintyInMeters'] = $proposedLocationAccuracy;
      $observation['smpAttrs'] = [];
      if (!empty($server['btoCoordinateUncertaintyAttrId']) && !empty($proposedBtoCoordinateUncertainty)) {
        $observation['smpAttrs'][$server['btoCoordinateUncertaintyAttrId']] = $proposedBtoCoordinateUncertainty;
      }
      if (!empty($server['btoOriginalCoordinateAttrId']) && !empty($proposedBtoOriginalCoord)) {
        $observation['smpAttrs'][$server['btoOriginalCoordinateAttrId']] = $proposedBtoOriginalCoord;
      }
      if (!empty($proposedComment)) {
        if (!empty($observation['occurrenceRemarks'])) {
          $observation['occurrenceRemarks'] .= "\n";
        }
        $observation['occurrenceRemarks'] .= $proposedComment;
      }
    }

    return TRUE;
  }

  /**
   * Retrieve location info for a BTO observation.
   *
   * @param object $db
   *   Connection.
   * @param array $observation
   *   BTO observation data.
   *
   * @return object
   *   Object containing the preferred SRID for the observation's locality and
   *   also the WKT of the observation point in Web Mercator.
   */
  private static function getLocationSridAndWkt($db, array $observation) {
    $query = <<<SQL
      SELECT get_output_srid(st_transform(st_geomfromtext('POINT($observation[east] $observation[north])', 4326), 900913)) as srid,
          st_astext(st_transform(st_geomfromtext('POINT($observation[east] $observation[north])', 4326),
            get_output_srid(st_transform(st_geomfromtext('POINT($observation[east] $observation[north])', 4326), 900913))
          )) as wkt
SQL;
    return $db->query($query)->current();
  }

  /**
   * Process any other field mappings defined by the server config.
   *
   * @param array $server
   *   Server configuration.
   * @param array $record
   *   Record structure supplied by the remote server.
   * @param array $observation
   *   Observation values to store in Indicia. Will be updated as appropriate.
   */
  private static function processOtherFields(array $server, array $record, array &$observation) {
    if (!empty($server['otherFields'])) {
      foreach ($server['otherFields'] as $src => $dest) {
        $path = explode('.', $src);
        $posInDoc = $record;
        $found = TRUE;
        foreach ($path as $node) {
          if (!isset($posInDoc[$node])) {
            $found = FALSE;
            break;
          }
          $posInDoc = $posInDoc[$node];
        }
        if ($found) {
          // @todo Check multi-value/array handling.
          $attrTokens = explode(':', $dest);
          if (is_object($posInDoc) || is_array($posInDoc)) {
            $posInDoc = json_encode($posInDoc);
          }
          $observation[$attrTokens[0] . 's'][$attrTokens[1]] = $posInDoc;
        }
      }
    }
  }

  /**
   * Parses a date string into the start, end and type.
   *
   * @param string $dateString
   *   Single date (yyyy-mm-dd), range of dates (yyyy-mm-dd/yyyy-mm-dd), month
   *   (yyyy-mm) or year (yyyy).
   *
   * @return array
   *   Array containing vague date parts, start, end and type.
   */
  private static function parseDates($dateString) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
      return [
        'start' => $dateString,
        'end' => $dateString,
        'type' => 'D',
      ];
    }
    elseif (preg_match('/^(?P<start>\d{4}-\d{2}-\d{2})\|(?P<end>\d{4}-\d{2}-\d{2})$/', $dateString, $matches)) {
      return [
        'start' => $matches['start'],
        'end' => $matches['end'],
        'type' => 'DD',
      ];
    }
    elseif (preg_match('/^(?P<year>\d{4})-(?P<month>\d{2})$/', $dateString)) {
      return [
        'start' => "$dateString-01",
        'end' => "$dateString-" . cal_days_in_month(CAL_GREGORIAN, $matches['month'], $matches['year']),
        'type' => 'O',
      ];
    }
    elseif (preg_match('/^(?P<year>\d{4})$/', $dateString)) {
      return [
        'start' => "$dateString-01-01",
        'end' => "$dateString-12-31",
        'type' => 'Y',
      ];
    }

  }

}
