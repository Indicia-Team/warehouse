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

  public static function syncServer($serverId, $server) {
    $db = Database::instance();
    $page = 1;
    $timestampAtStart = date('c');
    do {
      $moreToDo = self::syncPage($page, $db, $serverId, $server);
      $page++;
    } while ($moreToDo);
    variable_set("rest_api_sync_iNaturalist_last_run", $timestampAtStart);
  }

  private static function syncPage($page, $db, $serverId, $server) {
    // @todo use updated_since to filter to recent changes
    // @todo paginate through the response.
    // @todo images
    // @todo licence

    $fromDateTime = variable::get("rest_api_sync_iNaturalist_last_run", '1600-01-01T00:00:00+00:00', FALSE);

    $data = rest_api_sync::getDataFromRestUrl(
      "$server[url]?" . http_build_query(array_merge(
        $server['parameters'],
        [
          'updated_since' => $fromDateTime,
          'per_page' => 100,
          'page' => $page,
        ]
      )),
      $serverId
    );
    echo "$server[url]?" . http_build_query($server['parameters']) . '<br><br/>';
    $taxon_list_id = Kohana::config('rest_api_sync.taxon_list_id');
    $tracker = array('inserts' => 0, 'updates' => 0, 'errors' => 0);
    foreach ($data['results'] as $iNatRecord) {
      echo '<pre>'; var_export($iNatRecord); echo '</pre><br><br/>';
      list($north, $east) = explode(',', $iNatRecord['location']);
      /*$observation = [
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
      try {
        $is_new = api_persist::taxon_observation(
          $db,
          $observation,
          $server['website_id'],
          $server['survey_id'],
          $taxon_list_id
        );
        $tracker[$is_new ? 'inserts' : 'updates']++;
      }
      catch (exception $e) {
        $tracker['errors']++;
        rest_api_sync::log('error', "Error occurred submitting an occurrence\n" . $e->getMessage() . "\n" .
            json_encode($observation), $tracker);
      };*/
    }
    echo "<strong>Observations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]<br/>";
    return $data['total_results'] / 100 > $page;
  }

}
