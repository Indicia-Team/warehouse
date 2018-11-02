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
 * @package Services
 * @subpackage Data
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/**
 * Class providing miscellaneous data utility web services.
 */
class Data_utils_Controller extends Data_Service_Base_Controller {

  /**
   * Handled configurable data actions.
   *
   * Magic method to allow URLs to be mapped to custom actions defined in configuration and implemented in
   * database functions. Response from the function is output (and therefore returned from the service call).
   *
   * @param string $name
   *   Method name.
   * @param array $arguments
   *   List of arguments from URL.
   */
  public function __call($name, $arguments) {
    try {
      $this->authenticate('write');
      $actions = kohana::config("data_utils.actions");
      if (empty($actions[$name])) {
        throw new Exception('Unrecognised action');
      }
      $action = $actions[$name];
      $db = new Database();
      // Build the stored procedure params.
      foreach ($action['parameters'] as &$param) {
        if (is_string($param)) {
          // Integer parameters load from URL if config defined like [1].
          if (preg_match('/^\[(?P<index>\d+)\]$/', $param, $matches)) {
            if (isset($arguments[$matches['index'] - 1])) {
              if (!preg_match('/^\d+$/', $arguments[$matches['index'] - 1])) {
                throw new exception("Invalid argument at position $matches[index]");
              }
              $param = $arguments[$matches['index'] - 1];
            }
            else {
              throw new Exception('Required arguments not provided');
            }
          }
          // String parameters load from URL if config defined like {1}.
          elseif (preg_match('/^{(?P<index>\d+)}$/', $param, $matches)) {
            if (isset($arguments[$matches['index'] - 1])) {
              $param = "'" . pg_escape_string($arguments[$matches['index'] - 1]) . "'";
            }
            else
              throw new Exception('Required arguments not provided');
          }
          else {
            // Fixed string defined in config.
            $param = "'" . pg_escape_string($param) . "'";
          }
        }
        // Numeric parameters don't need processing or sanitising.
      }
      $params = implode(', ', $action['parameters']);
      echo json_encode($db->query("select $action[stored_procedure]($params);")->result_array(TRUE));
    }
    catch (Exception $e) {
      error_logger::log_error("Exception during custom data_utils action $name", $e);
      $this->handle_error($e);
    }
  }

  /**
   * Bulk verification service end-point.
   *
   * Provides the services/data_utils/bulk_verify service. This takes the
   * following POST parametenrs:
   *
   * * report - path to the report file that lists the occurrences to verify,
   *   excluding the .xml suffix.
   * * params - JSON object containing the report filter parameters to limit
   *   the records to.
   * * record_status - the record status code to set records to.
   * * record_substatus - the record substatus code to set records to.
   * * ignore - set to true to allow this to ignore any verification check rule
   *   failures (use with care!).
   *
   * Verifies all the records returned by the report according to the filter.
   */
  public function bulk_verify() {
    // @todo Integrate this method with workflow.
    $db = new Database();
    $this->authenticate('write');
    $report = $_POST['report'];
    $params = json_decode($_POST['params'], TRUE);
    $params['sharing'] = 'verification';
    $websites = $this->website_id ? array($this->website_id) : NULL;
    $reportEngine = new ReportEngine($websites, $this->user_id);
    try {
      // Load the report used for the verification grid with the same params.
      $data = $reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
      // Now get a list of all the occurrence ids.
      $ids = array();
      // Get some status related stuff ready.
      if (empty($_POST['record_substatus'])) {
        $status = 'accepted';
        $substatus = NULL;
      }
      else {
        $status = $_POST['record_substatus'] == 2 ? 'accepted as considered correct' : 'accepted as correct';
        $substatus = $_POST['record_substatus'];
      }
      foreach ($data['content']['records'] as $record) {
        if (($record['record_status'] !== 'V' || $record['record_substatus'] !== $substatus) &&
          (!empty($record['pass'])||$_POST['ignore'] === 'true')) {
          $ids[$record['occurrence_id']] = $record['occurrence_id'];
          $db->insert('occurrence_comments', array(
              'occurrence_id' => $record['occurrence_id'],
              'comment' => "This record is $status",
              'created_by_id' => $this->user_id,
              'created_on' => date('Y-m-d H:i:s'),
              'updated_by_id' => $this->user_id,
              'updated_on' => date('Y-m-d H:i:s'),
              'record_status' => 'V',
              'record_substatus' => $substatus
          ));
        }
      }
      // Field updates for the occurrences table and related cache tables.
      $updates = $this->getOccurrenceTableVerificationUpdateValues($db, 'V', $substatus, 'H');
      echo count($ids);
      // Check for any workflow updates. Any workflow records will need an individual update.
      $this->applyWorkflowToOccurrenceUpdates($db, array_keys($ids), $updates);
    }
    catch (Exception $e) {
      error_logger::log_error('Exception during bulk verify', $e);
      $this->handle_error($e);
    }
  }

  /**
   * Single record verification service end-point.
   *
   * Provides the services/data_utils/single_verify service. This takes an occurrence:id, occurrence:record_status, user_id (the verifier)
   * and optional occurrence_comment:comment in the $_POST data and updates the record. This is provided as a more optimised
   * alternative to using the normal data services calls. If occurrence:taxa_taxon_list_id is supplied then a redetermination will
   * get triggered.
   */
  public function single_verify() {
    if (empty($_POST['occurrence:id']) || !preg_match('/^\d+$/', $_POST['occurrence:id'])) {
      echo 'occurrence:id not supplied or invalid';
    }
    elseif (empty($_POST['occurrence:record_status']) || !preg_match('/^[VRCD]$/', $_POST['occurrence:record_status'])) {
      echo 'occurrence:record_status not supplied or invalid';
    }
    elseif (!empty($_POST['occurrence:record_substatus']) && !preg_match('/^[1-5]$/', $_POST['occurrence:record_substatus'])) {
      echo 'occurrence:record_substatus invalid';
    }
    elseif (!empty($_POST['occurrence:record_decision_source']) && !preg_match('/^[HM]$/', $_POST['occurrence:record_decision_source'])) {
      echo 'occurrence:record_decision_source invalid';
    }
    else {
      try {
        $db = new Database();
        $this->authenticate('write');
        $verifier = $this->getVerifierName($db);
        // Field updates for the occurrences table and related cache tables.
        $updates = $this->getOccurrenceTableVerificationUpdateValues(
          $db,
          $_POST['occurrence:record_status'],
          empty($_POST['occurrence:record_substatus']) ? NULL : $_POST['occurrence:record_substatus'],
          empty($_POST['occurrence:record_decision_source']) ? 'H' : $_POST['occurrence:record_decision_source']
        );
        // Give the workflow module a chance to rewind or update the values before updating.
        $this->applyWorkflowToOccurrenceUpdates($db, [$_POST['occurrence:id']], $updates);
        if (!empty($_POST['occurrence_comment:comment'])) {
          $db->insert('occurrence_comments', array(
            'occurrence_id' => $_POST['occurrence:id'],
            'comment' => $_POST['occurrence_comment:comment'],
            'created_by_id' => $this->user_id,
            'created_on' => date('Y-m-d H:i:s'),
            'updated_by_id' => $this->user_id,
            'updated_on' => date('Y-m-d H:i:s'),
            'record_status' => $_POST['occurrence:record_status'],
            'record_substatus' => empty($_POST['occurrence:record_substatus'])
              ? NULL : $_POST['occurrence:record_substatus']
            ));
        }
        echo 'OK';
      }
      catch (Exception $e) {
        echo $e->getMessage();
        error_logger::log_error('Exception during single record verify', $e);
      }
    }
  }

  private function getOccurrenceTableVerificationUpdateValues($db, $status, $substatus, $decisionSource) {
    $r = [];
    $verifier = $this->getVerifierName($db);
    // Field updates for the occurrences table.
    $r['occurrences'] = array(
      'record_status' => $status,
      'verified_by_id' => $this->user_id,
      'verified_on' => date('Y-m-d H:i:s'),
      'updated_by_id' => $this->user_id,
      'updated_on' => date('Y-m-d H:i:s'),
      'record_substatus' => $substatus,
      'record_decision_source' => $decisionSource,
    );
    // Field updates for the cache_occurrences_functional table.
    $r['cache_occurrences_functional'] = array(
      'record_status' => $status,
      'verified_on' => date('Y-m-d H:i:s'),
      'updated_on' => date('Y-m-d H:i:s'),
      'record_substatus' => $substatus,
      'query' => NULL
    );
    // Field updates for the cache_occurrences_nonfunctional table.
    $r['cache_occurrences_nonfunctional'] = array('verifier' => $verifier);
    return $r;
  }

  /**
   * Provides the services/data_utils/single_verify_sample service. This takes a sample:id, sample:record_status, user_id (the verifier)
   * and optional sample_comment:comment in the $_POST data and updates the sample. This is provided as a more optimised
   * alternative to using the normal data services calls.
   */
  public function single_verify_sample() {
    if (empty($_POST['sample:id']) || !preg_match('/^\d+$/', $_POST['sample:id'])) {
      echo 'sample:id not supplied or invalid';
    }
    elseif (empty($_POST['sample:record_status']) || !preg_match('/^[VRCD]$/', $_POST['sample:record_status'])) {
      echo 'sample:record_status not supplied or invalid';
    }
    elseif (!empty($_POST['sample:record_substatus']) && !preg_match('/^[1-5]$/', $_POST['sample:record_substatus'])) {
      echo 'sample:record_substatus invalid';
    }
    else {
      try {
        $db = new Database();
        $this->authenticate('write');
        $updates = array(
          'record_status' => $_POST['sample:record_status'],
          'verified_by_id' => $this->user_id,
          'verified_on' => date('Y-m-d H:i:s'),
          'updated_by_id' => $this->user_id,
          'updated_on' => date('Y-m-d H:i:s'),
        );
        $db->from('samples')
          ->set($updates)
          ->where('id', $_POST['sample:id'])
          ->update();
        // Since we bypass ORM here for performance, update the cache_samples_* table.
        $updates = array(
          'record_status' => $_POST['sample:record_status'],
          'verified_on' => date('Y-m-d H:i:s'),
          'updated_on' => date('Y-m-d H:i:s'),
        );
        $db->from('cache_samples_functional')
          ->set($updates)
          ->where('id', $_POST['sample:id'])
          ->update();

        if (!empty($_POST['sample_comment:comment'])) {
          $db->insert('sample_comments', array(
            'sample_id' => $_POST['sample:id'],
            'comment' => $_POST['sample_comment:comment'],
            'created_by_id' => $this->user_id,
            'created_on' => date('Y-m-d H:i:s'),
            'updated_by_id' => $this->user_id,
            'updated_on' => date('Y-m-d H:i:s'),
            'record_status' => $_POST['sample:record_status']
          ));
        }
        echo 'OK';
      }
      catch (Exception $e) {
        echo $e->getMessage();
        error_logger::log_error('Exception during single sample verify', $e);
      }
    }
  }

  /**
   * Provides the services/data_utils/bulk_verify_samples service. This takes a report plus params (json object) in the $_POST
   * data and verifies all the samples returned by the report according to the filter.
   */
  public function bulk_verify_samples() {
    $db = new Database();
    $this->authenticate('write');
    $report = $_POST['report'];
    $params = json_decode($_POST['params'], TRUE);
    $params['sharing'] = 'verification';
    $websites = $this->website_id ? array($this->website_id) : NULL;
    $reportEngine = new ReportEngine($websites, $this->user_id);
    try {
      // Load the report used for the verification grid with the same params.
      $data = $reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
      // Now get a list of all the occurrence ids.
      $ids = array();
      foreach ($data['content']['records'] as $record) {
        if ($record['record_status'] !== 'V') {
          $ids[$record['sample_id']] = $record['sample_id'];
          $db->insert('sample_comments', array(
            'sample_id' => $record['sample_id'],
            'comment' => "This sample is accepted",
            'created_by_id' => $this->user_id,
            'created_on' => date('Y-m-d H:i:s'),
            'updated_by_id' => $this->user_id,
            'updated_on' => date('Y-m-d H:i:s'),
            'record_status' => 'V'
          ));
        }
      }
      $updates = array(
        'record_status' => 'V',
        'verified_by_id' => $this->user_id,
        'verified_on' => date('Y-m-d H:i:s'),
        'updated_by_id' => $this->user_id,
        'updated_on' => date('Y-m-d H:i:s'),
      );
      $db->from('samples')->set($updates)->in('id', array_keys($ids))->update();
      $updates = array(
        'record_status' => 'V',
        'verified_on' => date('Y-m-d H:i:s'),
        'updated_on' => date('Y-m-d H:i:s'),
      );
      $db->from('cache_samples_functional')->set($updates)->in('id', array_keys($ids))->update();
      echo count($ids);
    }
    catch (Exception $e) {
      echo $e->getMessage();
      error_logger::log_error('Exception during bulk verify of samples', $e);
    }
  }

  /**
   * Retrieves the current user's name (the verifier name) for bulk verify operations.
   *
   * @param object $db
   *   Database connection.
   */
  private function getVerifierName($db) {
    $qryVerifiers = $db->select(array("p.surname", "p.first_name"))
      ->from('users as u')
      ->join('people as p', 'p.id', 'u.person_id')
      ->where('u.id', $this->user_id)
      ->get()->result_array(FALSE);
    return $qryVerifiers[0]['surname'] . ', ' . $qryVerifiers[0]['first_name'];
  }

  /**
   * Applies workflow changes to updates that are about to be applied to occurrence data.
   *
   * This gives the workflow module (if installed) the chance to alter the saved values on verification events.
   *
   * @param object $db
   *   Database connection.
   * @param array $idList
   *   Array of occurrence IDs about to be updated.
   * @param array $updates
   *   List of fields and values which are about to be applied to records in the occurrences table and
   *   related cache tables. Keyed by table name, each entry contains an associative array of field/value pairs.
   */
  private function applyWorkflowToOccurrenceUpdates($db, array $idList, array $updates) {
    $rewinds = [];
    $workflowEvents = [];
    if (in_array(MODPATH . 'workflow', Kohana::config('config.modules'))
        && !empty($updates['occurrences']['record_status'])) {
      // As we are verifying or rejecting, we need to rewind any opposing rejections or verifications.
      $rewinds = workflow::getRewindChangesForRecords($db, 'occurrence', $idList, ['V', 'R']);
      // Fetch any new events to apply when this record is verified.
      $workflowEvents = workflow::getEventsForRecords(
        $db,
        $this->website_id,
        'occurrence',
        $idList,
        [$updates['occurrences']['record_status']]
      );
      foreach ($idList as $id) {
        // If there is either a rewind operation, or a workflow event to apply, need to process this record individually.
        if (isset($rewinds["occurrence.$id"]) || isset($workflowEvents["occurrence.$id"])) {
          // Grab a copy of the update array
          $thisUpdates = $updates;
          if (isset($rewinds["occurrence.$id"])) {
            $thisRewind = $rewinds["occurrence.$id"];
            $this->applyValuesToOccurrenceTableValues($thisRewind, $thisUpdates);
          }
          if (isset($workflowEvents["occurrence.$id"])) {
            $theseEvents = $workflowEvents["occurrence.$id"];
            $state = [];
            foreach ($theseEvents as $thisEvent) {
              $oldRecord = ORM::factory('occurrence', $id);
              $oldRecordVals = $oldRecord->as_array();
              $newRecordVals = array_merge($oldRecordVals, $thisUpdates['occurrences']);
              $valuesToApply = workflow::processEvent(
                $thisEvent,
                'occurrence',
                $oldRecordVals,
                $newRecordVals,
                $state
              );
              $this->applyValuesToOccurrenceTableValues($valuesToApply, $thisUpdates);
            }
            // Apply the update to the occurrence and cache tables.
            $this->applyUpdatesToOccurrences($db, [$id], $thisUpdates);
            // Save these events in workflow_undo.
            $userId = security::getUserId();
            foreach ($state as $undoDetails) {
              $db->insert('workflow_undo', array(
                'entity' => 'occurrence',
                'entity_id' => $id,
                'event_type' => $undoDetails['event_type'],
                'created_on' => date("Ymd H:i:s"),
                'created_by_id' => $userId,
                'original_values' => json_encode($undoDetails['old_data'])
              ));
            }
          }
          // This record is done, so exclude from the bulk operation.
          unset($idList['$id']);
        }
      }
    }
    // Bulk update any remaining records.
    $this->applyUpdatesToOccurrences($db, $idList, $updates);
  }

  /**
   * Takes a set of updates for occurrence data and applies them to a list of occurrences.
   *
   * Updates the occurrences table and related cache tables.
   *
   * @param object $db
   *   Database connection.
   * @param array $idList
   *   Array of occurrence IDs about to be updated.
   * @param array $updates
   *   List of fields and values which are about to be applied to records in the occurrences table and
   *   related cache tables. Keyed by table name, each entry contains an associative array of field/value pairs.
   */
  private function applyUpdatesToOccurrences($db, $idList, $updates) {
    $db->from('occurrences')
      ->set($updates['occurrences'])
      ->in('id', $idList)
      ->update();
    // Since we bypass ORM here for performance, update the cache_occurrences_* tables.
    $db->from('cache_occurrences_functional')
      ->set($updates['cache_occurrences_functional'])
      ->in('id', $idList)
      ->update();
    $db->from('cache_occurrences_nonfunctional')
      ->set($updates['cache_occurrences_nonfunctional'])
      ->in('id', $idList)
      ->update();
  }

  /**
   * Applies a set of field value changes to the arrays used to update occurrences and related cache tables.
   *
   * @param array $values
   *   Values that are to be applied to the occurrences table as a result of workflow.
   * @param array $updates
   *   List of fields and values which are about to be applied to records in the occurrences table and
   *   related cache tables. Keyed by table name, each entry contains an associative array of field/value pairs.
   */
  private function applyValuesToOccurrenceTableValues(array $values, array &$updates) {
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

}
