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
 * @link http://code.google.com/p/indicia/
 */

 defined('SYSPATH') or die('No direct script access.');

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

  /**
   * Synchronise a set of data loaded from the iNat server.
   *
   * @param string $serverId
   *   ID of the server as defined in the configuration.
   * @param array $server
   *   Server configuration.
   */
  public static function syncServer($serverId, $server) {
    $page = 1;
    $timestampAtStart = date('c');
    do {
      $syncStatus = self::syncPage($serverId, $server, $page);
      $page++;
    } while ($syncStatus['moreToDo']);
    variable::set("rest_api_sync_{$serverId}_last_run", $timestampAtStart);
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
    // @todo images
    // @todo licence
    $db = Database::instance();
    $fromDateTime = variable::get("rest_api_sync_{$serverId}_last_run", '1600-01-01T00:00:00+00:00', FALSE);
    $pageSize = 30;
    $data = rest_api_sync::getDataFromRestUrl(
      "$server[url]?" . http_build_query(array_merge(
        $server['parameters'],
        [
          'updated_since' => $fromDateTime,
          'per_page' => $pageSize,
          'page' => $page,
        ]
      )),
      $serverId
    );
    $taxon_list_id = Kohana::config('rest_api_sync.taxon_list_id');
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
    foreach ($data['results'] as $iNatRecord) {
      list($north, $east) = explode(',', $iNatRecord['location']);
      $observation = [
        'id' => $iNatRecord['id'],
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
      ];
      if (!empty($server['attrs']) && !empty($iNatRecord['annotations'])) {
        foreach ($iNatRecord['annotations'] as $annotation) {
          $iNatAttr = "controlled_attribute:$annotation[controlled_attribute_id]";
          if (isset($server['attrs'][$iNatAttr])) {
            $attrTokens = explode(':', $server['attrs'][$iNatAttr]);
            $observation[$attrTokens[0] . 's'][$attrTokens[1]] = $annotation['controlled_value']['label'];
          }
        }
      }
      try {
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
            json_encode($observation), $tracker);
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
    return [
      'moreToDo' => $data['total_results'] / $pageSize > $page,
      'pageCount' => ceil($data['total_results'] / $pageSize),
      'recordCount' => $data['total_results'],
    ];
  }

}
