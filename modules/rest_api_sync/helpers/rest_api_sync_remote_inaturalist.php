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

define('INAT_PAGE_SIZE', 50);
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
    $timestampAtStart = date('c');
    // Initial population or delta of recent changes?
    $mode = variable::get("rest_api_sync_{$serverId}_mode", 'initialPopulate');
    if (!variable::get("rest_api_sync_{$serverId}_last_id")) {
      // Starting a batch, so work from first ID to last. Will later filter by
      // updated date if in delta mode.
      variable::set("rest_api_sync_{$serverId}_last_id", 0);
    }
    if ($mode === 'initialPopulate' && !variable::get("rest_api_sync_{$serverId}_next_run")) {
      // Starting a new full population, so remember when we started to ensure
      // we don't miss any changes when we switch to delta mode.
      variable::set("rest_api_sync_{$serverId}_next_run", $timestampAtStart);
    }
    if ($mode === 'delta' && !variable::get("rest_api_sync_{$serverId}_last_run")) {
      // Starting a batch in delta mode, so pick up from when the last batch
      // started.
      variable::set("rest_api_sync_{$serverId}_last_run", variable::get("rest_api_sync_{$serverId}_next_run"));
      // Save when this batch started for the next run.
      variable::set("rest_api_sync_{$serverId}_next_run", $timestampAtStart);
    }
    // Count of pages done in this run.
    $pageCount = 0;
    // Note that when importing the delta of recent changes, we have to do
    // everything since the last run, as there is no way to do batches based on
    // updated date reliably in the iNat API.
    do {
      $syncStatus = self::syncPage($serverId, $server);
      $pageCount++;
      ob_flush();
    } while ($syncStatus['moreToDo'] && ($pageCount < INAT_MAX_PAGES || $mode === 'delta'));
    if ($mode === 'initialPopulate' && !$syncStatus['moreToDo']) {
      // Initial population done, so switch to delta mode.
      variable::set("rest_api_sync_{$serverId}_mode", 'delta');
    }
    // Batch finished successfully so cleanup.
    variable::delete("rest_api_sync_{$serverId}_last_id");
    variable::delete("rest_api_sync_{$serverId}_last_run");
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
    // FromID will be zero for first page in batch, but tracks the highest
    // record ID we got to as we page through.
    $fromId = variable::get("rest_api_sync_{$serverId}_last_id", 0, FALSE);
    $lastId = $fromId;
    // Initial population or delta of recent changes?
    $mode = variable::get("rest_api_sync_{$serverId}_mode", 'initialPopulate');
    $parameters = [
      'per_page' => INAT_PAGE_SIZE,
      // Paging done by ID.
      'id_above' => $fromId,
      'order' => 'asc',
      'order_by' => 'id',
    ];
    if ($mode === 'delta') {
      // Filter to recently updated records.
      $fromDateTime = variable::get("rest_api_sync_{$serverId}_last_run", '1600-01-01T00:00:00+00:00', FALSE);
      $parameters['updated_since'] = $fromDateTime;
    }
    $data = rest_api_sync_utils::getDataFromRestUrl(
      "$server[url]/observations?" . http_build_query(array_merge(
        $server['parameters'],
        $parameters
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
          "WHERE server_id=? AND source_id=?::varchar AND dest_table='occurrences'", [$serverId, $iNatRecord['id']]);
      }
      catch (exception $e) {
        rest_api_sync_utils::log(
          'error',
          "Error occurred submitting an occurrence with iNaturalist ID $iNatRecord[id]\n" . $e->getMessage(),
          $tracker
        );
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
        $db->query($sql, [$serverId, $iNatRecord['id'], $e->getMessage(), $createdById]);
      };
      $lastId = $iNatRecord['id'];
    }
    // Prevent memory accumulation if log not flushed.
    kohana::log_save();
    variable::set("rest_api_sync_{$serverId}_last_id", $lastId);
    rest_api_sync_utils::log(
      'info',
      "<strong>Observations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]"
    );
    $recordsToGo = $data['total_results'] - count($data['results']);
    $r = [
      'moreToDo' => count($data['results']) === INAT_PAGE_SIZE,
      'pagesToGo' => ceil($recordsToGo / INAT_PAGE_SIZE),
      'recordsToGo' => $recordsToGo,
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