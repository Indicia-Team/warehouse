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
   * ISO datetime that the sync was last run.
   *
   * Used to filter requests for records to only get changes.
   *
   * @var string
   */
  private static $fromDateTime;

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

  private static $db;

  public static function syncServer($db, $serverId, $server) {
    self::$db = $db;
    $data = rest_api_sync::getDataFromRestUrl(
      "$server[url]?d1=2017-12-03&d2=2017-12-09&place_id=6858&verifiable=true&quality_grade=research",
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
        'recorder' => $iNatRecord['user']['login'],
        'east' => $east,
        'north' => $north,
        'projection' => 'WGS84',
        'precision' => $iNatRecord['positional_accuracy'],
        'siteName' => $iNatRecord['place_guess'],
        'href' => $iNatRecord['uri'],
      ];
      try {
        $is_new = api_persist::taxon_observation(
          self::$db,
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
      };
    }
    echo "<strong>Observations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]<br/>";
  }

}
