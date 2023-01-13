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
   * Magic method to allow URLs to be mapped to custom actions defined in
   * configuration and implemented in database functions. Response from the
   * function is output (and therefore returned from the service call).
   *
   * @param string $name
   *   Method name.
   * @param array $arguments
   *   List of arguments from URL.
   */
  public function __call($name, $arguments) {
    $tm = microtime(TRUE);
    try {
      $this->authenticate('write');
      $actions = kohana::config("data_utils.actions");
      if (empty($actions[$name])) {
        throw new Exception('Unrecognised action');
      }
      $action = $actions[$name];
      $db = new Database();
      if (!empty($_POST)) {
        $post = $_POST;
      }
      elseif (!empty(file_get_contents('php://input'))) {
        $post = json_decode(file_get_contents('php://input'));
      }
      // Build the stored procedure params.
      foreach ($action['parameters'] as &$param) {
        if (is_string($param)) {
          // Integer parameters in square brackets or string parameters in braces.
          if (preg_match('/^(?P<bracketType>[\[\{])(?P<paramName>[a-zA-Z0-9\-_]+)[\]\}]$/', $param, $matches)) {
            if (preg_match('/^\d+$/', $matches['paramName']) && isset($arguments[$matches['paramName'] - 1])) {
              // Parameter is numeric so grab the value from the URL segment at
              // the indicated index position.
              $param = $arguments[$matches['paramName'] - 1];
            }
            elseif (!empty($_GET[$matches['paramName']])) {
              // Other parameters should be in URL query parameters.
              $param = $_GET[$matches['paramName']];
            }
            elseif (!empty($post[$matches['paramName']])) {
              // Other parameters should be in URL query parameters.
              $param = $post[$matches['paramName']];
            }
            else {
              throw new Exception('Required parameters not provided.');
            }
            if ($matches['bracketType'] === '{') {
              // Escape string parameters.
              $param = "'" . pg_escape_string($db->getLink(), $param) . "'";
            }
          }
          else {
            // Non-bracketed parameters are fixed strings defined in config.
            $param = "'" . pg_escape_string($db->getLink(), $param) . "'";
          }
        }
      }
      $params = implode(', ', $action['parameters']);
      $r = json_encode($db->query("select $action[stored_procedure]($params);")->result_array(TRUE));
      // Enable JSONP.
      if (array_key_exists('callback', $_REQUEST)) {
        $r = "$_REQUEST[callback]($r)";
        header('Content-Type: application/javascript');
      }
      else {
        header('Content-Type: application/json');
      }
      echo $r;
      if (class_exists('request_logging')) {
        request_logging::log('a', 'data', 'data_utils', $name, $this->website_id, $this->user_id, $tm, $db);
      }
    }
    catch (Exception $e) {
      error_logger::log_error("Exception during custom data_utils action $name", $e);
      $this->handle_error($e);
      if (class_exists('request_logging')) {
        request_logging::log('a', 'data', 'data_utils', $name, $this->website_id, $this->user_id, $tm, NULL, $e->getMessage());
      }
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
   * * dryrun - set to true to return the count of records that would be
   *   updated without performing the update.
   *
   * Verifies all the records returned by the report according to the filter.
   */
  public function bulk_verify() {
    $tm = microtime(TRUE);
    $db = new Database();
    $this->authenticate('write');
    $dryRun = isset($_POST['dryrun']) && $_POST['dryrun'] === 'true';
    $report = $_POST['report'];
    $params = json_decode($_POST['params'], TRUE);
    $params['sharing'] = 'verification';
    $websites = $this->website_id ? [$this->website_id] : NULL;
    $reportEngine = new ReportEngine($websites, $this->user_id);
    try {
      // Load the report used for the verification grid with the same params.
      $data = $reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
      // Now get a list of all the occurrence ids.
      $ids = [];
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
          (!empty($record['pass']) || $_POST['ignore'] === 'true')) {
          $ids[$record['occurrence_id']] = $record['occurrence_id'];
          if (!$dryRun) {
            $db->insert('occurrence_comments', [
              'occurrence_id' => $record['occurrence_id'],
              'comment' => "This record is $status",
              'created_by_id' => $this->user_id,
              'created_on' => date('Y-m-d H:i:s'),
              'updated_by_id' => $this->user_id,
              'updated_on' => date('Y-m-d H:i:s'),
              'record_status' => 'V',
              'record_substatus' => $substatus,
            ]);
          }
        }
      }
      if (!$dryRun) {
        // Field updates for the occurrences table and related cache tables.
        $updates = data_utils::getOccurrenceTableVerificationUpdateValues($db, $this->user_id, 'V', $substatus, 'H');
        // Check for any workflow updates. Any workflow records will need an
        // individual update.
        data_utils::applyWorkflowToOccurrenceUpdates($db, $this->website_id, $this->user_id, array_keys($ids), $updates);
      }
      echo count($ids);
      if (class_exists('request_logging')) {
        request_logging::log('a', 'data', NULL, 'bulk_verify', $this->website_id, $this->user_id, $tm, $db);
      }
    }
    catch (Exception $e) {
      error_logger::log_error('Exception during bulk verify', $e);
      $this->handle_error($e);
      if (class_exists('request_logging')) {
        request_logging::log('a', 'data', NULL, 'bulk_verify', $this->website_id, $this->user_id, $tm, $db, $e->getMessage());
      }
    }
  }

  /**
   * Single record verification service end-point.
   *
   * Provides the services/data_utils/single_verify service. This takes an the
   * following parameters in the POST:
   * * occurrence:id
   * * occurrence:record_status
   * * user_id (the verifier)
   * * occurrence_comment:comment (optional comment).
   * * occurrence:taxa_taxon_list_id (optional ID for redeterminations).
   * Updates the record. This is provided as a more optimised alternative to
   * using the normal data services calls. If occurrence:taxa_taxon_list_id is
   * supplied then a redetermination will get triggered.
   */
  public function single_verify() {
    if (empty($_POST['occurrence:id']) || !preg_match('/^\d+$/', $_POST['occurrence:id'])) {
      echo 'occurrence:id not supplied or invalid';
      return;
    }
    $this->array_verify([$_POST['occurrence:id']]);
  }

  /**
   * List of records verification service end-point.
   *
   * Provides the services/data_utils/list_verify service. This takes an the
   * following parameters in the POST:
   * * occurrence:ids - a comma separated list of IDs.
   * * occurrence:record_status
   * * user_id (the verifier)
   * * occurrence_comment:comment (optional comment).
   * * occurrence:taxa_taxon_list_id (optional ID for redeterminations).
   * Updates the records. This is provided as a more optimised alternative to
   * using the normal data services calls. If occurrence:taxa_taxon_list_id is
   * supplied then a redetermination will get triggered for each record.
   */
  public function list_verify() {
    if (empty($_POST['occurrence:ids'])) {
      echo 'occurrence:ids not supplied';
      kohana::log('debug', 'Invalid occurrence:ids to list_verify: ' . var_export($_POST, TRUE));
      return;
    }
    $this->array_verify(explode(',', $_POST['occurrence:ids']));
  }

  /**
   * Internal method which provides the code for single or list verification.
   *
   * @param array $ids
   *   Array of IDs.
   */
  private function array_verify($ids) {
    if (empty($_POST['occurrence:record_status']) || !preg_match('/^[VRCD]$/', $_POST['occurrence:record_status'])) {
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
        kohana::log('debug', 'in array_verify');
        $tm = microtime(TRUE);
        $db = new Database();
        $this->authenticate('write');
        // Field updates for the occurrences table and related cache tables.
        $updates = data_utils::getOccurrenceTableVerificationUpdateValues(
          $db,
          $this->user_id,
          $_POST['occurrence:record_status'],
          empty($_POST['occurrence:record_substatus']) ? NULL : $_POST['occurrence:record_substatus'],
          empty($_POST['occurrence:record_decision_source']) ? 'H' : $_POST['occurrence:record_decision_source']
        );
        // Give the workflow module a chance to rewind or update the values before updating.
        data_utils::applyWorkflowToOccurrenceUpdates($db, $this->website_id, $this->user_id, $ids, $updates);
        foreach ($ids as $id) {
          if (!empty($_POST['occurrence_comment:comment'])) {
            $action = $_POST['occurrence:record_status'] . (empty($_POST['occurrence:record_substatus']) ? '' : $_POST['occurrence:record_substatus']);
            $db->insert('occurrence_comments', [
              'occurrence_id' => $id,
              'comment' => $this->applyVerificationTemplateReplacements($db, $_POST['occurrence_comment:comment'], $id, $action),
              'created_by_id' => $this->user_id,
              'created_on' => date('Y-m-d H:i:s'),
              'updated_by_id' => $this->user_id,
              'updated_on' => date('Y-m-d H:i:s'),
              'record_status' => $_POST['occurrence:record_status'],
              'record_substatus' => empty($_POST['occurrence:record_substatus']) ? NULL : $_POST['occurrence:record_substatus'],
            ]);
          }
        }
        echo 'OK';
        if (class_exists('request_logging')) {
          request_logging::log('a', 'data', NULL, 'array_verify', $this->website_id, $this->user_id, $tm, $db, NULL, $ids);
        }
      }
      catch (Exception $e) {
        echo $e->getMessage();
        error_logger::log_error('Exception during single record verify', $e);
        if (class_exists('request_logging')) {
          request_logging::log('a', 'data', NULL, 'array_verify', $this->website_id, $this->user_id, $tm, $db, $e->getMessage(), $ids);
        }
      }
    }
  }

  /**
   * Apply token replacements to a verification template.
   *
   * @param Database $db
   *   Database connection.
   * @param string $comment
   *   Comment containing tokens to replace.
   * @param int $occurrenceId
   *   Occurrence ID.
   * @param string $action
   *   Action being performed.
   */
  private function applyVerificationTemplateReplacements($db, $comment, $occurrenceId, $action) {
    if (preg_match('/\{\{ [a-z ]+ \}\}/', $comment)) {
      // Comment contains template tokens, so load the occurrence.
      $sql = <<<SQL
SELECT o.date_start, o.date_end, o.date_type, onf.output_sref, cttl.taxon, cttl.default_common_name, cttl.preferred_taxon, cttl.taxon_rank, o.location_name
FROM cache_occurrences_functional o
JOIN cache_occurrences_nonfunctional onf ON onf.id=o.id
JOIN cache_samples_nonfunctional snf ON snf.id=o.sample_id
JOIN cache_taxa_taxon_lists cttl ON cttl.id=o.taxa_taxon_list_id
WHERE o.id=$occurrenceId;
SQL;
      $occ = $db->query($sql)->current();
      $dateParts = [$occ->date_start, $occ->date_end, $occ->date_type];
      return strtr($comment, [
        '{{ date }}' => vague_date::vague_date_to_string($dateParts),
        '{{ sref }}' => $occ->output_sref,
        '{{ taxon }}' => $occ->taxon,
        '{{ common name }}' => $occ->default_common_name ?? $occ->preferred_taxon ?? $occ->taxon,
        '{{ preferred name }}' => $occ->preferred_taxon ?? $occ->taxon,
        '{{ taxon full name }}' => $this->getTaxonNameLabel($occ),
        '{{ rank }}' => lcfirst($occ->taxon_rank),
        '{{ action }}' => warehouse::recordStatusCodeToTerm($action),
        '{{ location name }}' => $occ->location_name ?? 'unknown',
      ]);
    }
    return $comment;
  }

  /**
   * Returns a full label for a taxon name for an occurrence.
   *
   * Includes the accepted name and common name if there is one.
   *
   * @param object $occ
   *   Occurrence data row loaded from the database.
   *
   * @return string
   *   Taxon names string.
   */
  private function getTaxonNameLabel($occ) {
    $scientific = $occ->preferred_taxon ?? $occ->taxon;
    if (!empty($occ->default_common_name) && $occ->default_common_name !== $scientific) {
      return $scientific . ' (' . $occ->default_common_name . ')';
    }
    return $scientific;
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
        $tm = microtime(TRUE);
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
        if (class_exists('request_logging')) {
          request_logging::log('a', 'data', NULL, 'single_verify_sample', $this->website_id, $this->user_id, $tm, $db);
        }
      }
      catch (Exception $e) {
        echo $e->getMessage();
        error_logger::log_error('Exception during single sample verify', $e);
        if (class_exists('request_logging')) {
          request_logging::log('a', 'data', NULL, 'single_verify_sample', $this->website_id, $this->user_id, $tm, $db, $e->getMessage());
        }
      }
    }
  }

  /**
   * Provides the services/data_utils/bulk_verify_samples service. This takes a report plus params (json object) in the $_POST
   * data and verifies all the samples returned by the report according to the filter.
   */
  public function bulk_verify_samples() {
    $tm = microtime(TRUE);
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
      if (class_exists('request_logging')) {
        request_logging::log('a', 'data', NULL, 'bulk_verify_samples', $this->website_id, $this->user_id, $tm, $db);
      }
    }
    catch (Exception $e) {
      echo $e->getMessage();
      error_logger::log_error('Exception during bulk verify of samples', $e);
      if (class_exists('request_logging')) {
        request_logging::log('a', 'data', NULL, 'bulk_verify_samples', $this->website_id, $this->user_id, $tm, $db, $e->getMessage());
      }
    }
  }

  /**
   * Web service endpoint index.php/services/data_utils/bulk_delete_occurrences.
   *
   * Allows bulk deletion of occurrence data. Currently only supports deletion
   * of entire imports. When calling this service, the following must be
   * provided in the POST data:
   * * write authentication tokens
   * * import_guid - the GUID of the import to delete
   * * user_id - ID of the user. Records are only deleted if they belong to the
   *   user.
   * * trial - optional. Set a value such as 't' to do a trial run.
   *
   * The response is an HTTP response containing the following:
   * * action - either delete or none (for trial runs)
   * * affected - a list of entities with the count of affected records.
   *
   */
  public function bulk_delete_occurrences() {
    header('Content-Type: application/json');
    if (empty($_POST['import_guid'])) {
      $this->fail('Bad request', 400, 'Missing import_guid parameter');
    }
    elseif (empty($_POST['user_id'])) {
      $this->fail('Bad request', 400, 'Missing user_id parameter');
    }
    elseif (!preg_match('/^\d+$/', $_POST['import_guid'])) {
      $this->fail('Bad request', 400, 'Incorrect import_guid format');
    }
    elseif (!preg_match('/^\d+$/', $_POST['user_id'])) {
      $this->fail('Bad request', 400, 'Incorrect user_id format');
    }
    else {
      try {
        $tm = microtime(TRUE);
        $this->authenticate('write');
        $db = new Database();
        $deletionSql = '';
        if (empty($_POST['trial'])) {
          $deletionSql = <<<SQL
UPDATE occurrences SET deleted=true, updated_on=now(), updated_by_id=$_POST[user_id]
WHERE id IN (SELECT id FROM to_delete);

UPDATE samples SET deleted=true, updated_on=now(), updated_by_id=$_POST[user_id]
WHERE id IN (SELECT sample_id FROM to_delete);

DELETE FROM cache_occurrences_functional WHERE id IN (SELECT id FROM to_delete);
DELETE FROM cache_occurrences_nonfunctional WHERE id IN (SELECT id FROM to_delete);
DELETE FROM cache_samples_functional WHERE id IN (SELECT sample_id FROM to_delete);
DELETE FROM cache_samples_nonfunctional WHERE id IN (SELECT sample_id FROM to_delete);
SQL;
      }
      // The following query picks up occurrences from the import, plus the
      // associated samples unless the samples now have other occurrences
      // subsequently added to them.
      $qry = <<<SQL
SELECT o.id, CASE WHEN o2.id IS NULL THEN o.sample_id ELSE NULL END AS sample_id
INTO temporary to_delete
FROM occurrences o
LEFT JOIN occurrences o2 ON coalesce(o2.import_guid, '')<>coalesce(o.import_guid, '') AND o2.sample_id=o.sample_id and o2.deleted=false
WHERE o.import_guid='$_POST[import_guid]' AND o.created_by_id=$_POST[user_id]
AND o.deleted=false
and o.website_id=$this->website_id;

$deletionSql;

SELECT COUNT(distinct id) AS occurrences, COUNT(distinct sample_id) AS samples FROM to_delete;
SQL;
        $db->query($qry);
        $count = $db->select('COUNT(distinct id) AS occurrences, COUNT(distinct sample_id) AS samples')
          ->from('to_delete')
          ->get()->current();
        $response = array(
          'code' => 200,
          'status' => 'OK',
          'action' => empty($_POST['trial']) ? 'delete' : 'none',
          'affected' => [
            'occurrences' => $count->occurrences,
            'samples' => $count->samples,
          ]
        );
        echo json_encode($response);
        if (class_exists('request_logging')) {
          request_logging::log('a', 'data', NULL, 'bulk_delete_occurrences', $this->website_id, $this->user_id, $tm, $db);
        }
      }
      catch (Exception $e) {
        error_logger::log_error('Exception during bulk_delete_occurrences', $e);
        if (class_exists('request_logging')) {
          request_logging::log('a', 'data', NULL, 'bulk_delete_occurrences', $this->website_id, $this->user_id, $tm, $db, $e->getMessage());
        }
      }
    }
  }

  private function fail($message, $code, $text) {
    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
    header($protocol . ' ' . $code . ' ' . $message);
    $response = array(
      'code' => $code,
      'status' => $message,
      'message' => $text,
    );
    echo json_encode($response);
  }

}
