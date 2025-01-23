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

/**
 * Helper class for syncing to the JSON annotations API of another server.
 *
 * Could be an Indicia warehouse, or another server implementing the same
 * standard.
 */
class rest_api_sync_remote_json_annotations {

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
    $nextPage = variable::get("rest_api_sync_{$serverId}_next_page", [], FALSE);
    $data = rest_api_sync_utils::getDataFromRestUrl(
      "$server[url]?" . http_build_query($nextPage),
      $serverId
    );
    $tracker = ['inserts' => 0, 'updates' => 0, 'errors' => 0];
    foreach ($data['data'] as $record) {
      // @todo Make sure all fields in specification are handled.
      try {
        $annotation = [
          'id' => $record['annotationID'],
          'occurrenceID' => $record['occurrenceID'],
          'comment' => empty($record['comment']) ? 'No comment provided' : $record['comment'],
          'identificationVerificationStatus' => empty($record['identificationVerificationStatus']) ? NULL : $record['identificationVerificationStatus'],
          'question' => empty($record['question']) ? NULL : $record['question'],
          'authorName' => empty($record['authorName']) ? 'Unknown' : $record['authorName'],
          'dateTime' => $record['dateTime'],
        ];

        $is_new = api_persist::annotation(
          $db,
          $annotation,
          $server['survey_id']
        );
        if ($is_new !== NULL) {
          $tracker[$is_new ? 'inserts' : 'updates']++;
        }
        $db->query(<<<SQL
          UPDATE rest_api_sync_skipped_records SET current=false
          WHERE server_id=? AND source_id=? AND dest_table='occurrence_comments'
        SQL, [$serverId, $annotation['id']]);
      }
      catch (exception $e) {
        rest_api_sync_utils::log(
          'error',
          "Error occurred submitting an annotation with ID $annotation[id]\n" . $e->getMessage(),
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
  'occurrence_comments',
  ?,
  true,
  now(),
  ?
)
QRY;
        $db->query($sql, [$serverId, $annotation['id'], $msg, $createdById]);
      }
    }
    variable::set("rest_api_sync_{$serverId}_next_page", $data['paging']['next']);
    rest_api_sync_utils::log(
      'info',
      "<strong>Annotations</strong><br/>Inserts: $tracker[inserts]. Updates: $tracker[updates]. Errors: $tracker[errors]"
    );
    $r = [
      'moreToDo' => count($data['data']) > 0,
      // No way of determining the following.
      'pagesToGo' => NULL,
      'recordsToGo' => NULL,
    ];
    return $r;
  }

}
