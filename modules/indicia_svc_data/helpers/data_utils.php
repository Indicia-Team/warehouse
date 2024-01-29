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

/**
 * Helper class for cache_builder functionality.
 */
class data_utils {

  /**
   * Retrieves the values that must change for each entity after a verification.
   */
  public static function getOccurrenceTableVerificationUpdateValues($db, $userId, $status, $substatus, $decisionSource) {
    $r = [];
    $verifier = self::getVerifierName($db, $userId);
    // Field updates for the occurrences table.
    $r['occurrences'] = [
      'record_status' => $status,
      'verified_by_id' => $userId,
      'verified_on' => date('Y-m-d H:i:s'),
      'updated_by_id' => $userId,
      'updated_on' => date('Y-m-d H:i:s'),
      'record_substatus' => $substatus,
      'record_decision_source' => $decisionSource,
    ];
    // Field updates for the cache_occurrences_functional table.
    $r['cache_occurrences_functional'] = [
      'record_status' => $status,
      'verified_on' => date('Y-m-d H:i:s'),
      'updated_on' => date('Y-m-d H:i:s'),
      'record_substatus' => $substatus,
      'query' => NULL,
    ];
    // Field updates for the cache_occurrences_nonfunctional table.
    $r['cache_occurrences_nonfunctional'] = ['verifier' => $verifier];
    return $r;
  }

  /**
   * Retrieves the values that must change for each entity after a verification.
   */
  public static function getOccurrenceTableRedetUpdateValues($db, $userId, $taxaTaxonListId) {
    // Field updates for the occurrences table.
    $r['occurrences'] = [
      'taxa_taxon_list_id' => $taxaTaxonListId,
      'updated_by_id' => $userId,
      'updated_on' => date('Y-m-d H:i:s'),
      'record_status' => 'C',
      'record_substatus' => NULL,
      'verified_by_id' => NULL,
      'verified_on' => NULL,
      'machine_involvement' => NULL,
      'classification_event_id' => NULL,
    ];
    // Work queue will update the cache tables.
    return $r;
  }

  /**
   * Applies workflow changes to updates to apply to occurrence data.
   *
   * This gives the workflow module (if installed) the chance to alter the
   * saved values on verification events.
   *
   * @param object $db
   *   Database connection.
   * @param int $websiteId
   *   Authenticated website's ID.
   * @param int $userId
   *   ID of the user doing the verification.
   * @param array $idList
   *   Array of occurrence IDs about to be updated.
   * @param array $updates
   *   List of fields and values which are about to be applied to records in
   *   the occurrences table and related cache tables. Keyed by table name,
   *   each entry contains an associative array of field/value pairs.
   */
  public static function applyWorkflowToOccurrenceVerificationUpdates($db, $websiteId, $userId, array $idList, array $updates) {
    $rewinds = [];
    $workflowEvents = [];
    if (in_array(MODPATH . 'workflow', Kohana::config('config.modules'))
        && !empty($updates['occurrences']['record_status'])) {
      // As we are verifying or rejecting, we need to rewind any opposing
      // rejections or verifications.
      $rewinds = workflow::getRewindChangesForRecords($db, 'occurrence', $idList, [
        'V',
        'R',
      ]);
      // Fetch any new events to apply when this record is verified.
      $workflowEvents = workflow::getEventsForRecords(
        $db,
        $websiteId,
        'occurrence',
        $idList,
        [$updates['occurrences']['record_status']]
      );
      foreach ($idList as $id) {
        // If there is either a rewind operation, or a workflow event to apply,
        // need to process this record individually.
        if (isset($rewinds["occurrence.$id"]) || isset($workflowEvents["occurrence.$id"])) {
          // Grab a copy of the update array.
          $thisUpdates = $updates;
          if (isset($rewinds["occurrence.$id"])) {
            $thisRewind = $rewinds["occurrence.$id"];
            self::applyValuesToOccurrenceTableValues($thisRewind, $thisUpdates);
          }
          if (isset($workflowEvents["occurrence.$id"])) {
            $theseEvents = $workflowEvents["occurrence.$id"];
            $state = [];
            foreach ($theseEvents as $thisEvent) {
              $oldRecord = ORM::factory('occurrence', $id);
              $oldRecordVals = $oldRecord->as_array();
              $newRecordVals = array_merge($oldRecordVals, $thisUpdates['occurrences']);
              $needsFilterCheck = !empty($thisEvent->attrs_filter_term) || !empty($thisEvent->location_ids_filter);
              $valuesToApply = workflow::processEvent(
                $thisEvent,
                $needsFilterCheck,
                'occurrence',
                $oldRecordVals,
                $newRecordVals,
                $state
              );
              self::applyValuesToOccurrenceTableValues($valuesToApply, $thisUpdates);
            }
            // Apply the update to the occurrence and cache tables.
            self::applyUpdatesToOccurrences($db, [$id], $thisUpdates);
            // Save these events in workflow_undo.
            foreach ($state as $undoDetails) {
              $db->insert('workflow_undo', [
                'entity' => 'occurrence',
                'entity_id' => $id,
                'event_type' => $undoDetails['event_type'],
                'created_on' => date("Ymd H:i:s"),
                'created_by_id' => $userId,
                'original_values' => json_encode($undoDetails['old_data']),
              ]);
              if ($undoDetails['needs_filter_check']) {
                $q = new WorkQueue();
                $q->enqueue($db, [
                  'task' => 'task_workflow_event_check_filters',
                  'entity' => 'occurrence',
                  'record_id' => $id,
                  'cost_estimate' => 50,
                  'priority' => 2,
                  'params' => json_encode([
                    'workflow_events.id' => $undoDetails['event_id'],
                  ]),
                ]);
              }
            }
          }
          // This record is done, so exclude from the bulk operation.
          unset($idList['$id']);
        }
      }
    }
    // Bulk update any remaining records.
    self::applyUpdatesToOccurrences($db, $idList, $updates);
  }

  /**
   * Takes a set of updates for data to apply to a list of occurrences.
   *
   * Updates the occurrences table and related cache tables.
   *
   * @param object $db
   *   Database connection.
   * @param array $idList
   *   Array of occurrence IDs about to be updated.
   * @param array $updates
   *   List of fields and values which are about to be applied to records in
   *   the occurrences table and related cache tables. Keyed by table name,
   *   each entry contains an associative array of field/value pairs.
   */
  private static function applyUpdatesToOccurrences($db, array $idList, array $updates) {
    $db->from('occurrences')
      ->set($updates['occurrences'])
      ->in('id', $idList)
      ->update();
    // Since we bypass ORM here for performance, update the cache_occurrences_*
    // tables.
    if (isset($updates['cache_occurrences_functional'])) {
      $db->from('cache_occurrences_functional')
        ->set($updates['cache_occurrences_functional'])
        ->in('id', $idList)
        ->update();
    }
    if (isset($updates['cache_occurrences_nonfunctional'])) {
      $db->from('cache_occurrences_nonfunctional')
        ->set($updates['cache_occurrences_nonfunctional'])
        ->in('id', $idList)
        ->update();
    }
  }

  /**
   * Applies a set of field value changes to occurrence tables.
   *
   * @param array $values
   *   Values that are to be applied to the occurrences table as a result of
   *   workflow.
   * @param array $updates
   *   List of fields and values which are about to be applied to records in
   *   the occurrences table and related cache tables. Keyed by table name,
   *   each entry contains an associative array of field/value pairs.
   */
  private static function applyValuesToOccurrenceTableValues(array $values, array &$updates) {
    $updates['occurrences'] = array_merge($values, $updates['occurrences']);
    if (isset($values['confidential'])) {
      $updates['cache_occurrences_functional']['confidential'] = $values['confidential'];
    }
    if (isset($values['sensitivity_precision'])) {
      $updates['cache_occurrences_functional']['sensitive'] = empty($values['sensitivity_precision']) ? 'f' : 't';
      $updates['cache_occurrences_nonfunctional']['sensitivity_precision'] = $values['sensitivity_precision'];
    }
    if (isset($values['release_status'])) {
      $updates['cache_occurrences_functional']['release_status'] = $values['release_status'];
    }
  }

  /**
   * Retrieves the current user's name (the verifier name) for bulk operations.
   *
   * @param object $db
   *   Database connection.
   * @param int $userId
   *   ID of the verifier.
   */
  private static function getVerifierName($db, $userId) {
    $qryVerifiers = $db->select(['p.surname', 'p.first_name'])
      ->from('users as u')
      ->join('people as p', 'p.id', 'u.person_id')
      ->where('u.id', $userId)
      ->get()->result_array(FALSE);
    return $qryVerifiers[0]['surname'] . ', ' . $qryVerifiers[0]['first_name'];
  }

}
