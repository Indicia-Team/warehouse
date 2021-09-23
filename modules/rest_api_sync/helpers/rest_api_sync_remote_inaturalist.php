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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

define('INAT_PAGE_SIZE', 100);
define('INAT_MAX_PAGES', 5);

/**
 * Helper class for syncing to the RESTful API on iNaturalist.
 */
class rest_api_sync_remote_inaturalist {

  /**
   * Terms loaded from iNat.
   *
   * @var array
   */
  private static $controlledTerms = [];

  /**
   * Synchronise a set of data loaded from the iNat server.
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
    } while ($syncStatus['moreToDo'] && $pageCount < INAT_MAX_PAGES);
    if (!$syncStatus['moreToDo']) {
      variable::set("rest_api_sync_{$serverId}_last_run", variable::get("rest_api_sync_{$serverId}_next_run"));
      variable::delete("rest_api_sync_{$serverId}_next_run");
      variable::delete("rest_api_sync_{$serverId}_last_id");
    }
  }

  /**
   * Synchronise a single page of data loaded from the iNat server.
   *
   * For this sync, we don't use the provided $page parameter as pagination
   * is not possible on large iNat datasets. Instead we keep our own last_id
   * variable to chunk through the data..
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
    $fromDateTime = variable::get("rest_api_sync_{$serverId}_last_run", '1600-01-01T00:00:00+00:00', FALSE);
    $fromId = variable::get("rest_api_sync_{$serverId}_last_id", 0, FALSE);
    $lastId = $fromId;
    $data = rest_api_sync_utils::getDataFromRestUrl(
      "$server[url]/observations?" . http_build_query(array_merge(
        $server['parameters'],
        [
          'updated_since' => $fromDateTime,
          'per_page' => INAT_PAGE_SIZE,
          'id_above' => $fromId,
          'order' => 'asc',
          'order_by' => 'id',
        ]
      )),
      $serverId
    );
    $taxon_list_id = Kohana::config('rest_api_sync.taxon_list_id');
    $tracker = ['inserts' => 0, 'updates' => 0, 'errors' => 0];
    foreach ($data['results'] as $iNatRecord) {
      try {
        if (empty($iNatRecord['taxon']['name'])) {
          // Skip names with no identification.
          throw new exception("iNat record $iNatRecord[id] skipped as no identification.");
        }
        elseif (empty($iNatRecord['location'])) {
          // Skip names with no identification.
          throw new exception("iNat record $iNatRecord[id] skipped as the location is private.");
        }
        list($north, $east) = explode(',', $iNatRecord['location']);
        $observation = [
          'id' => "iNat:$iNatRecord[id]",
          'taxonName' => $iNatRecord['taxon']['name'],
          'startDate' => $iNatRecord['observed_on'],
          'endDate' => $iNatRecord['observed_on'],
          'dateType' => 'D',
          'recordedBy' => empty($iNatRecord['user']['name']) ? $iNatRecord['user']['login'] : $iNatRecord['user']['name'],
          'east' => $east,
          'north' => $north,
          'projection' => 'WGS84',
          'coordinateUncertaintyInMeters' => $iNatRecord['public_positional_accuracy'],
          'siteName' => $iNatRecord['place_guess'],
          'href' => $iNatRecord['uri'],
          // American English in iNat field name - sic.
          'licenceCode' => $iNatRecord['license_code'],
        ];
        if (!empty($iNatRecord['photos'])) {
          $observation['media'] = [];
          foreach ($iNatRecord['photos'] as $iNatPhoto) {
            // Don't import unlicensed photos.
            if (!empty($iNatPhoto['license_code'])) {
              $observation['media'][] = [
                'path' => $iNatPhoto['url'],
                'caption' => $iNatPhoto['attribution'],
                'mediaType' => 'Image:iNaturalist',
                'licenceCode' => $iNatPhoto['license_code'],
              ];
            }
          }
        }
        if (!empty($server['annotationAttrs']) && !empty($iNatRecord['annotations'])) {
          foreach ($iNatRecord['annotations'] as $annotation) {
            $iNatAttr = "controlled_attribute:$annotation[controlled_attribute_id]";
            if (isset($server['annotationAttrs'][$iNatAttr])) {
              $attrTokens = explode(':', $server['annotationAttrs'][$iNatAttr]);
              if (isset(self::$controlledTerms[$annotation['controlled_attribute_id']])) {
                $controlledTermValues = self::$controlledTerms[$annotation['controlled_attribute_id']]['values'];
                $controlledValueId = $annotation['controlled_value_id'];
                $observation[$attrTokens[0] . 's'][$attrTokens[1]] = $controlledTermValues[$controlledValueId];
              }
            }
          }
        }
        if (!empty($server['otherFields'])) {
          foreach ($server['otherFields'] as $src => $dest) {
            if (!empty($iNatRecord[$src])) {
              // @todo Check multi-value/array handling.
              $attrTokens = explode(':', $dest);
              $observation[$attrTokens[0] . 's'][$attrTokens[1]] = $iNatRecord[$src];
            }
          }
        }
        $is_new = api_persist::taxonObservation(
          $db,
          $observation,
          $server['website_id'],
          $server['survey_id'],
          $taxon_list_id,
          $server['allowUpdateWhenVerified']
        );
        if ($is_new !== NULL) {
          $tracker[$is_new ? 'inserts' : 'updates']++;
        }
        $db->query("UPDATE rest_api_sync_skipped_records SET current=false " .
          "WHERE server_id='$serverId' AND source_id='$iNatRecord[id]' AND dest_table='occurrences'");
      }
      catch (exception $e) {
        rest_api_sync_utils::log(
          'error',
          "Error occurred submitting an occurrence with iNaturalist ID $iNatRecord[id]\n" . $e->getMessage(),
          $tracker
        );
        $msg = pg_escape_string($e->getMessage());
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
  '$serverId',
  '$iNatRecord[id]',
  'occurrences',
  '$msg',
  true,
  now(),
  $createdById
)
QRY;
        $db->query($sql);
      };
      $lastId = $iNatRecord['id'];
    }
    variable::set("rest_api_sync_{$serverId}_last_id", $lastId);
    rest_api_sync_utils::log(
      'info',
      "<strong>Observations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]"
    );
    $r = [
      'moreToDo' => count($data['results']) === INAT_PAGE_SIZE,
      'pagesToGo' => ceil($data['total_results'] / INAT_PAGE_SIZE),
      'recordsToGo' => $data['total_results'],
    ];
    return $r;
  }

  /**
   * Loads the controlled terms information from iNat.
   *
   * @param string $serverId
   *   ID of the server as defined in the configuration.
   * @param array $server
   *   Server configuration.
   */
  public static function loadControlledTerms($serverId, array $server) {
    if (!empty(self::$controlledTerms)) {
      // Already loaded.
      return;
    }
    $cache = Cache::instance();
    self::$controlledTerms = $cache->get('inaturalist-controlled-terms');
    if (!self::$controlledTerms) {
      $data = rest_api_sync_utils::getDataFromRestUrl(
        "$server[url]/controlled_terms",
        $serverId
      );
      foreach ($data['results'] as $iNatControlledTerm) {
        $termLookup = [];
        foreach ($iNatControlledTerm['values'] as $iNatValue) {
          $termLookup[$iNatValue['id']] = $iNatValue['label'];
        }
        self::$controlledTerms[$iNatControlledTerm['id']] = [
          'label' => $iNatControlledTerm['label'],
          'values' => $termLookup,
        ];
      }
      $cache->set('inaturalist-controlled-terms', self::$controlledTerms);
    }
  }

}
