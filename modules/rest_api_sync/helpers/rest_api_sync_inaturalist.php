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
define('INAT_MAX_PAGES', 10);

/**
 * Helper class for syncing to the RESTful API on iNaturalist.
 */
class rest_api_sync_inaturalist {

  /**
   * Processing state.
   *
   * Current processing state, used to track initial setup.
   *
   * @var string
   */
  private $state;

  /**
   * Date up to which processing has been performed.
   *
   * When a sync run only manages to do part of the job (too many records to
   * process) this defines the limit of the completely processed edit date
   * range.
   *
   * @var string
   */
  private static $processingDateLimit;

  private static $controlledTerms = [];

  /**
   * Synchronise a set of data loaded from the iNat server.
   *
   * @param string $serverId
   *   ID of the server as defined in the configuration.
   * @param array $server
   *   Server configuration.
   */
  public static function syncServer($serverId, $server) {
    // Physical page tracker.
    $page = 1;
    if (isset($_GET['start_page'])) {
      $page = $_GET['start_page'];
    }
    else {
      // If last run not finished all pages, start at last page.
      $page = variable::get("rest_api_sync_{$serverId}_page", $page);
    }
    // Count of pages done in this run.
    $pageCount = 0;
    $timestampAtStart = date('c');
    self::loadControlledTerms($serverId, $server);
    do {
      $syncStatus = self::syncPage($serverId, $server, $page);
      $page++;
      $pageCount++;
      ob_flush();
      variable::set("rest_api_sync_{$serverId}_page", $page);
    } while ($syncStatus['moreToDo'] && $pageCount < INAT_MAX_PAGES);
    if (!$syncStatus['moreToDo']) {
      variable::set("rest_api_sync_{$serverId}_last_run", $timestampAtStart);
      variable::delete("rest_api_sync_{$serverId}_page");
    }
  }

  /**
   * Loads the controlled terms information from iNat.
   *
   * @param string $serverId
   *   ID of the server as defined in the configuration.
   * @param array $server
   *   Server configuration.
   */
  private static function loadControlledTerms($serverId, $server) {
    if (!empty(self::$controlledTerms)) {
      // Already loaded.
      return;
    }
    $data = rest_api_sync::getDataFromRestUrl(
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
  }

  /**
   * Synchronise a single page of data loaded from the iNat server.
   *
   * @param string $serverId
   *   ID of the server as defined in the configuration.
   * @param array $server
   *   Server configuration.
   * @param int $page
   *   Page number.
   *
   * @return array
   *   Status info.
   */
  public static function syncPage($serverId, array $server, $page) {
    $db = Database::instance();
    $fromDateTime = variable::get("rest_api_sync_{$serverId}_last_run", '1600-01-01T00:00:00+00:00', FALSE);
    $pageSize = INAT_PAGE_SIZE;
    $data = rest_api_sync::getDataFromRestUrl(
      "$server[url]/observations?" . http_build_query(array_merge(
        $server['parameters'],
        [
          'updated_since' => $fromDateTime,
          'per_page' => $pageSize,
          'page' => $page,
          'order' => 'asc',
          'order_by' => 'created_at',
        ]
      )),
      $serverId
    );
    $taxon_list_id = Kohana::config('rest_api_sync.taxon_list_id');
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
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
          'recorder' => empty($iNatRecord['user']['name']) ? $iNatRecord['user']['login'] : $iNatRecord['user']['name'],
          'east' => $east,
          'north' => $north,
          'projection' => 'WGS84',
          'precision' => $iNatRecord['positional_accuracy'],
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
        if (!empty($server['attrs']) && !empty($iNatRecord['annotations'])) {
          foreach ($iNatRecord['annotations'] as $annotation) {
            $iNatAttr = "controlled_attribute:$annotation[controlled_attribute_id]";
            if (isset($server['attrs'][$iNatAttr])) {
              $attrTokens = explode(':', $server['attrs'][$iNatAttr]);
              $controlledTermValues = self::$controlledTerms[$annotation['controlled_attribute_id']]['values'];
              $controlledValueId = $annotation['controlled_value_id'];
              $observation[$attrTokens[0] . 's'][$attrTokens[1]] = $controlledTermValues[$controlledValueId];
            }
          }
        }
        $is_new = api_persist::taxonObservation(
          $db,
          $observation,
          $server['website_id'],
          $server['survey_id'],
          $taxon_list_id
        );
        $tracker[$is_new ? 'inserts' : 'updates']++;
        $db->query("UPDATE rest_api_sync_skipped_records SET current=false " .
          "WHERE server_id='$serverId' AND source_id='$iNatRecord[id]' AND dest_table='occurrences'");
      }
      catch (exception $e) {
        $tracker['errors']++;
        rest_api_sync::log('error', "Error occurred submitting an occurrence\n" . $e->getMessage() . "\n" .
            json_encode($iNatRecord), $tracker);
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
    }
    rest_api_sync::log(
      'info',
      "<strong>Observations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]"
    );
    echo "<strong>Observations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]<br/>";
    return [
      'moreToDo' => $data['total_results'] / $pageSize > $page,
      'pageCount' => ceil($data['total_results'] / $pageSize),
      'recordCount' => $data['total_results'],
    ];
  }

}
