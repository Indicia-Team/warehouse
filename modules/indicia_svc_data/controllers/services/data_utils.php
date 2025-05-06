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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/warehouse
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
              // the indicated index position in the config.
              $param = $arguments[$matches['paramName'] - 1];
            }
            elseif (!empty($_GET[$matches['paramName']])) {
              // Other parameters should be in URL query parameters.
              $param = $_GET[$matches['paramName']];
            }
            elseif (!empty($post[$matches['paramName']])) {
              // Or in the POST data.
              $param = $post[$matches['paramName']];
            }
            else {
              throw new Exception('Required parameters not provided.');
            }
            if ($matches['bracketType'] === '{') {
              // Escape string parameters.
              $param = pg_escape_literal($db->getLink(), $param);
            }
            elseif ($matches['bracketType'] === '[') {
              // Square bracket parameters must be ints.
              $param = (int) $param;
            }
          }
          else {
            // Non-bracketed parameters are fixed strings defined in config.
            $param = "'" . pg_escape_string($db->getLink(), $param) . "'";
          }
        }
      }
      $params = implode(', ', $action['parameters']);
      // Escape the stored procedure name, note that it may include a schema
      // prefix so need to escape each identifier separately.
      $proc = implode('.', array_map(function($v) use ($db) {
        return pg_escape_identifier($db->getLink(), $v);
      }, explode('.', $action['stored_procedure'])));
      $r = json_encode($db->query("select $proc($params);")->result_array(TRUE));
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
        data_utils::applyWorkflowToOccurrenceVerificationUpdates($db, $this->website_id, $this->user_id, array_keys($ids), $updates);
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
   * Updates the records. This is provided as a more optimised alternative to
   * using the normal data services calls.
   */
  public function list_verify() {
    if (empty($_POST['occurrence:ids'])) {
      echo 'occurrence:ids not supplied';
      kohana::log('debug', 'Invalid occurrence:ids to list_verify: ' . var_export($_POST, TRUE));
      return;
    }
    if (!preg_match('/^\d+(,\d+)*$/', $_POST['occurrence:ids'])) {
      $this->fail('Bad request', 400, 'Invalid format for occurrence:ids parameter.');
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
        $tm = microtime(TRUE);
        $db = new Database();
        $this->authenticate('write');
        if (count($ids) === 1 && !empty($_POST['applyDecisionToParentSample'])) {
          $ids = $this->findSameTaxonInParentSample($db, $ids[0]);
        }
        // Field updates for the occurrences table and related cache tables.
        $updates = data_utils::getOccurrenceTableVerificationUpdateValues(
          $db,
          $this->user_id,
          $_POST['occurrence:record_status'],
          empty($_POST['occurrence:record_substatus']) ? NULL : $_POST['occurrence:record_substatus'],
          empty($_POST['occurrence:record_decision_source']) ? 'H' : $_POST['occurrence:record_decision_source']
        );
        // Give the workflow module a chance to rewind or update the values
        // before updating.
        data_utils::applyWorkflowToOccurrenceVerificationUpdates($db, $this->website_id, $this->user_id, $ids, $updates);
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
              'redet_taxa_taxon_list_id' => $_POST['occurrence:taxa_taxon_list_id'] ?? NULL,
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
   * Expand the list of IDs to include same taxon in the wider sample.
   *
   * If verifying transects or timed counts, there is an option to verify all
   * records of the same taxon in the parent sample. This function expands the
   * selected record to the full list, excluding records that have already been
   * verified.
   *
   * @param Database $db
   *   Database connection.
   * @param int $id
   *   Selected record ID.
   *
   * @return array
   *   List of IDs.
   */
  private function findSameTaxonInParentSample(Database $db, int $id) {
    $sql = <<<SQL
SELECT string_agg(o2.id::text, ',') as expanded
FROM cache_occurrences_functional o1
JOIN cache_occurrences_functional o2 ON o2.parent_sample_id=o1.parent_sample_id
  AND (
    o2.preferred_taxa_taxon_list_id=o1.preferred_taxa_taxon_list_id
    OR o2.external_key=o1.external_key
  )
WHERE o1.id=$id
-- Always include the selected record. Include other records only if unverified.
AND (o2.id=$id OR (o2.record_status='C' AND o2.record_substatus IS NULL));
SQL;
    return explode(',', $db->query($sql)->current()->expanded);
  }

  /**
   * List of records redetermination service end-point.
   *
   * Provides the services/data_utils/list_redet service. This takes an the
   * following parameters in the POST:
   * * occurrence:ids - a comma separated list of IDs.
   * * occurrence:taxa_taxon_list_id - the new identification.
   * * user_id (the verifier)
   * * occurrence_comment:comment (optional comment).
   * This is provided as a more optimised alternative to using the normal data
   * services calls.
   */
  public function list_redet() {
    if (empty($_POST['occurrence:ids'])) {
      echo 'occurrence:ids not supplied';
      kohana::log('debug', 'Invalid occurrence:ids to list_redet: ' . var_export($_POST, TRUE));
      return;
    }
    if (!preg_match('/^\d+(,\d+)*$/', $_POST['occurrence:ids'])) {
      $this->fail('Bad request', 400, 'Invalid format for occurrence:ids parameter.');
    }
    $this->array_redet(explode(',', $_POST['occurrence:ids']));
  }

  /**
   * Internal method which provides the code for single or list redet.
   *
   * @param array $ids
   *   Array of IDs.
   */
  private function array_redet($ids) {
    if (empty($_POST['occurrence:taxa_taxon_list_id']) || !preg_match('/^\d+$/', $_POST['occurrence:taxa_taxon_list_id'])) {
      echo 'occurrence:taxa_taxon_list_id not supplied or invalid';
    }
    else {
      try {
        $tm = microtime(TRUE);
        $db = new Database();
        $this->authenticate('write');
        // Field updates for the occurrences table and related cache tables.
        $updates = data_utils::getOccurrenceTableRedetUpdateValues(
          $db,
          $this->user_id,
          $_POST['occurrence:taxa_taxon_list_id'],
        );
        $this->redeterminationDbProcessing($db, $ids, $this->user_id, $_POST['occurrence:determiner_id'] ?? NULL);
        // Give the workflow module a chance to rewind or update the values
        // before updating.
        data_utils::applyWorkflowToOccurrenceVerificationUpdates($db, $this->website_id, $this->user_id, $ids, $updates);
        $q = new WorkQueue();
        foreach ($ids as $id) {
          $q->enqueue($db, [
            'task' => 'task_cache_builder_update',
            'entity' => 'occurrence',
            'record_id' => $id,
            'cost_estimate' => 50,
            'priority' => 1,
          ]);
          if (!empty($_POST['occurrence_comment:comment'])) {
            $db->insert('occurrence_comments', [
              'occurrence_id' => $id,
              'comment' => $this->applyVerificationTemplateReplacements($db, $_POST['occurrence_comment:comment'], $id, 'DT'),
              'created_by_id' => $this->user_id,
              'created_on' => date('Y-m-d H:i:s'),
              'updated_by_id' => $this->user_id,
              'updated_on' => date('Y-m-d H:i:s'),
              'redet_taxa_taxon_list_id' => $_POST['occurrence:taxa_taxon_list_id'],
            ]);
          }
        }
        echo 'OK';
        if (class_exists('request_logging')) {
          request_logging::log('a', 'data', NULL, 'array_redet', $this->website_id, $this->user_id, $tm, $db, NULL, $ids);
        }
      }
      catch (Exception $e) {
        echo $e->getMessage();
        error_logger::log_error('Exception during redet', $e);
        if (class_exists('request_logging')) {
          request_logging::log('a', 'data', NULL, 'array_redet', $this->website_id, $this->user_id, $tm, $db, $e->getMessage(), $ids);
        }
      }
    }
  }

  /**
   * Database processing for a redetermination.
   *
   * Inserts records into the determinations table.
   * Updates any determination attributes with name of a redeterminer.
   *
   * When applying a re-determination, the new determiner's name is used to
   * overwrite the existing custom attribute values (e.g. Identified By).
   *
   * @param Database $db
   *   Database connection.
   * @param array $occurrenceIds
   *   List of occurrences IDs to check.
   * @param int $userId
   *   Current user's ID.
   * @param ?int $redetPersonId
   *   Person ID being allocated as the redeterminer in the data. If set to -1
   *   then the redeterminer is not updated. Null means use the current user.
   */
  private function redeterminationDbProcessing(Database $db, array $occurrenceIds, int $userId, ?int $redetPersonId) {
    // Stringify null for the SQL.
    $redetPersonId = $redetPersonId ?? 'null';
    $idCsv = implode(',', $occurrenceIds);
    warehouse::validateIntCsvListParam($idCsv);
    $logDeterminations = kohana::config('indicia.auto_log_determinations') === TRUE ? 'true' : 'false';
    $sql = "SELECT f_handle_determination(ARRAY[$idCsv], $userId, $redetPersonId, $logDeterminations, true);";
    $db->query($sql);
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
  private function applyVerificationTemplateReplacements($db, $comment, int $occurrenceId, $action) {
    if (preg_match('/\{\{ [a-z ]+ \}\}/', $comment)) {
      // Comment contains template tokens, so load the occurrence.
      $sql = <<<SQL
SELECT o.date_start, o.date_end, o.date_type, onf.output_sref, cttl.taxon, cttl.default_common_name, cttl.preferred_taxon, cttl.taxon_rank, o.location_name
FROM cache_occurrences_functional o
JOIN cache_occurrences_nonfunctional onf ON onf.id=o.id
JOIN cache_samples_nonfunctional snf ON snf.id=o.sample_id
JOIN cache_taxa_taxon_lists cttl ON cttl.id=o.taxa_taxon_list_id
WHERE o.id=?;
SQL;
      $occ = $db->query($sql, [$occurrenceId])->current();
      $dateParts = [$occ->date_start, $occ->date_end, $occ->date_type];
      $replacements = [
        '{{ date }}' => vague_date::vague_date_to_string($dateParts),
        '{{ sref }}' => $occ->output_sref,
        '{{ taxon }}' => $occ->taxon,
        '{{ common name }}' => $occ->default_common_name ?? $occ->preferred_taxon ?? $occ->taxon,
        '{{ preferred name }}' => $occ->preferred_taxon ?? $occ->taxon,
        '{{ taxon full name }}' => $this->getTaxonNameLabel($occ),
        '{{ rank }}' => empty($occ->taxon_rank) ? 'unknown rank' : lcfirst($occ->taxon_rank),
        '{{ action }}' => warehouse::recordStatusCodeToTerm($action),
        '{{ location name }}' => $occ->location_name ?? 'unknown',
      ];
      if (!empty($_POST['occurrence:taxa_taxon_list_id']) && preg_match('/\{\{ new [a-z ]+ \}\}/', $comment)) {
        // A redet so additional fields required for the new taxon.
        $ttlId = $_POST['occurrence:taxa_taxon_list_id'];
        $sql = <<<SQL
          SELECT taxon, default_common_name, preferred_taxon, taxon_rank
          FROM cache_taxa_taxon_lists
          WHERE id=?;
        SQL;
        $taxonDetails = $db->query($sql, [$ttlId])->current();
        $replacements['{{ new taxon }}'] = $taxonDetails->taxon;
        $replacements['{{ new common name }}'] = $taxonDetails->default_common_name ?? $taxonDetails->preferred_taxon ?? $taxonDetails->taxon;
        $replacements['{{ new preferred name }}'] = $taxonDetails->preferred_taxon ?? $taxonDetails->taxon;
        $replacements['{{ new taxon full name }}'] = $this->getTaxonNameLabel($taxonDetails);
        $replacements['{{ new rank }}'] = empty($taxonDetails->taxon_rank) ? 'unknown rank' : lcfirst($taxonDetails->taxon_rank);
      }
      return strtr($comment, $replacements);
    }
    return $comment;
  }

  /**
   * Returns a full label for a taxon name for an occurrence.
   *
   * Includes the accepted name and common name if there is one.
   *
   * @param object $data
   *   Data row loaded from the database.
   *
   * @return string
   *   Taxon names string.
   */
  private function getTaxonNameLabel($data) {
    $scientific = $data->preferred_taxon ?? $data->taxon;
    if (!empty($data->default_common_name) && $data->default_common_name !== $scientific) {
      return $scientific . ' (' . $data->default_common_name . ')';
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
            'record_status' => $_POST['sample:record_status'],
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
        $userId = (int) $_POST['user_id'];
        $deletionSql = '';
        if (empty($_POST['trial'])) {
          $deletionSql = <<<SQL
            UPDATE occurrences SET deleted=true, updated_on=now(), updated_by_id=$userId
            WHERE id IN (SELECT id FROM to_delete);

            UPDATE samples SET deleted=true, updated_on=now(), updated_by_id=$userId
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
        $importGuid = pg_escape_literal($db->getLink(), $_POST['import_guid']);
        $qry = <<<SQL
          SELECT o.id, CASE WHEN o2.id IS NULL THEN o.sample_id ELSE NULL END AS sample_id
          INTO temporary to_delete
          FROM occurrences o
          LEFT JOIN occurrences o2 ON coalesce(o2.import_guid, '')<>coalesce(o.import_guid, '') AND o2.sample_id=o.sample_id and o2.deleted=false
          WHERE o.import_guid=$importGuid AND o.created_by_id=$userId
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

  /**
   * Emits a failure HTTP response.
   *
   * @param string $status
   *   Response status, e.g. Unauthorized or Bad Request.
   * @param int $code
   *   Response code, e.g. 401.
   * @param string $text
   *   Text to include in response.
   * @param string $errorCode
   *   An error code that defines the specific problem so that the client may resolve it.
   * @param array $errorData
   *   Any extra data that can be used by the client in problem resolution.
   */
  private function fail($status, $code, $text, $errorCode = NULL, array $errorData = NULL) {
    $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
    header($protocol . ' ' . $code . ' ' . $status);
    $response = [
      'code' => $code,
      'status' => $status,
      'message' => $text,
    ];
    if ($errorCode) {
      $response['errorCode'] = $errorCode;
    }
    if ($errorData) {
      $response['errorData'] = $errorData;
    }
    kohana::log('alert', 'Data utils fail called: ' . $text);
    echo json_encode($response);
  }

  /**
   * Check affected samples.
   *
   * Ensures that occurrence lists for edit/move don't affect other occurrences
   * in the samples which shouldn't be affected.
   *
   * @param object $db
   *   Database connection.
   * @param string $occurrenceIdList
   *   CSV format string of occurrence IDs to check.
   *
   * @return object
   *   Database row of an example sample that shouldn't be affected. If result
   *   is empty then safe to proceed.
   */
  private static function checkAffectedSamplesDontContainOtherOccurrences($db, $occurrenceIdList) {
    $qry = <<<SQL
      SELECT o2.id as excluded_id, o.id as included_id, o2.sample_id
      FROM occurrences o
      JOIN occurrences o2 ON o2.sample_id=o.sample_id AND o2.deleted=false
      WHERE o.id IN ($occurrenceIdList)
      AND o2.id NOT IN ($occurrenceIdList)
      AND o.deleted=false
      LIMIT 1;
SQL;
   return $db->query($qry)->current();
  }

  /**
   * Splits samples to separate occurrences from those not in the list.
   *
   * Before a bulk edit, if occurrences belong to samples containing other
   * occurrences that are not in the list, split the samples so the other
   * occurrences aren't affected.
   *
   * @param object $db
   *   Database connection.
   * @param string $occurrenceIdList
   *   CSV format string of occurrence IDs to process.
   */
  private static function splitSamplesFromOtherOccurrences($db, $occurrenceIdList) {
    // First find a list of sample IDs that need to be duplicated.
    $qry = <<<SQL
      SELECT DISTINCT o2.sample_id AS old_sample_id, nextval('samples_id_seq'::regclass) as new_sample_id
      INTO TEMPORARY samples_to_clone
      FROM occurrences o
      JOIN occurrences o2 ON o2.sample_id=o.sample_id AND o2.deleted=false
      WHERE o.id IN ($occurrenceIdList)
      AND o2.id NOT IN ($occurrenceIdList)
      AND o.deleted=false;

      INSERT INTO samples(id, survey_id, location_id, date_start, date_end, date_type, entered_sref, entered_sref_system,
        location_name, created_on, created_by_id, updated_on, updated_by_id, comment, external_key, sample_method_id, deleted,
        geom, recorder_names, parent_id, input_form, group_id, privacy_precision, record_status, verified_by_id, verified_on,
        licence_id, training)
      SELECT stc.new_sample_id, s.survey_id, s.location_id, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system,
        s.location_name, now(), s.created_by_id, now(), s.updated_by_id, s.comment, s.external_key, s.sample_method_id, s.deleted,
        s.geom, s.recorder_names, s.parent_id, s.input_form, s.group_id, s.privacy_precision, s.record_status, s.verified_by_id, s.verified_on,
        s.licence_id, s.training
      FROM samples_to_clone stc
      JOIN samples s ON s.id=stc.old_sample_id;

      INSERT INTO sample_attribute_values(sample_id, sample_attribute_id, text_value, float_value, int_value, date_start_value,
        date_end_value, date_type_value, created_on, created_by_id, updated_on, updated_by_id, deleted, source_id, upper_value)
      SELECT stc.new_sample_id, v.sample_attribute_id, v.text_value, v.float_value, v.int_value, v.date_start_value,
        v.date_end_value, v.date_type_value, now(), v.created_by_id, now(), v.updated_by_id, v.deleted, v.source_id, v.upper_value
      FROM samples_to_clone stc
      JOIN sample_attribute_values v ON v.sample_id=stc.old_sample_id;

      UPDATE occurrences o
      SET sample_id=stc.new_sample_id
      FROM samples_to_clone stc
      WHERE o.sample_id=stc.old_sample_id
      AND o.id in ($occurrenceIdList);
SQL;
    $db->query($qry);
  }

  /**
   * Checks that the POST data contains the required parameters for a bulk move.
   *
   * Also checks the auth_user_id is available from the authentication tokens.
   *
   * @return bool
   *   TRUE if required parameters available in $_POST.
   */
  private function checkBulkMovePostParams() {
    if (empty($this->auth_user_id) || $this->auth_user_id === -1) {
      $this->fail('Unauthorized', 401, 'User ID not specified in authentication tokens. Requires an upgraded iform module.');
      return FALSE;
    }
    if (!isset($_POST['occurrence:ids'])) {
      $this->fail('Bad Request', 400, 'The occurrence:ids parameter was not provided.');
      return FALSE;
    }
    if (empty($_POST['occurrence:ids'])) {
      $this->fail('Bad Request', 400, 'There are no occurrences to move.');
      return FALSE;
    }
    if (!preg_match('/^\d+(,\d+)*/', $_POST['occurrence:ids'])) {
      $this->fail('Bad Request', 400, 'Incorrect format for occurrence:ids parameter.');
      return FALSE;
    }
    if (empty($_POST['datasetMappings'])) {
      $this->fail('Bad Request', 400, 'The datasetMappings parameter was not provided.');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Checks that the proposed list of occurrence IDs is OK to move.
   *
   * Before a bulk move, checks:
   * * Occurrences are all in surveys that the request wants to move records
   *   out of.
   * * None of the occurrences are confidential.
   * * All occurrences belong to the authorised user.
   *
   * Emits a failure response if appropriate.
   *
   * @param Database $db
   *   Database connection.
   * @param string $occurrenceIdList
   *   CSV List of occurrences to check.
   * @param array $validSrcSurveyIds
   *   CSV list of valid survey IDs to move occurrences from.
   * @param object $result
   *   Results of the database count query will be returned in this parameter.
   *
   * @return bool
   *   True if OK, else false.
   */
  private function precheckBulkMoveOccurrences($db, $occurrenceIdList, $validSrcSurveyIds, &$result) {
    $validSrcSurveyList = implode(',', $validSrcSurveyIds);
    $qry = <<<SQL
SELECT SUM(CASE WHEN s.survey_id IN ($validSrcSurveyList) THEN 0 ELSE 1 END) as invalid_survey_count,
  SUM(CASE WHEN o.confidential THEN 1 ELSE 0 END) as confidential_count,
  SUM(CASE WHEN o.created_by_id<>$this->auth_user_id THEN 1 ELSE 0 END) as other_user_count,
  COUNT(o.id) as occurrence_count,
  COUNT(s.id) as sample_count
FROM occurrences o
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
WHERE o.id IN ($occurrenceIdList)
AND o.deleted=false
SQL;
    $result = $db->query($qry)->current();
    $invalidSurveyCount = $result->invalid_survey_count;
    if ($invalidSurveyCount > 0) {
      $this->fail('Bad Request', 400, 'Attempt to move occurrences that are not in the correct survey dataset.');
      return FALSE;
    }
    $confidentialCount = $result->confidential_count;
    if ($confidentialCount > 0) {
      $this->fail('Bad Request', 400, 'Attempt to move occurrences that are confidential is disallowed.');
      return FALSE;
    }
    $otherUserCount = $result->other_user_count;
    if ($otherUserCount > 0) {
      $this->fail('Bad Request', 400, 'Attempt to move occurrences that were input by other users is disallowed.');
      return FALSE;
    }
    $results = $this->checkAffectedSamplesDontContainOtherOccurrences($db, $occurrenceIdList);
    if ($results) {
      $this->fail('Bad Request', 400, 'Cannot move occurrences if other occurrences within the same sample are not being moved. ' .
        "For example, sample $results->sample_id for occurrence $results->included_id also contains occurrence $results->excluded_id which is not in the list of records to move.");
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Ensure edit rights available to all websites impacted by a bulk move.
   */
  private function checkWebsitesAuthorisedForMove($db, $impactedWebsiteIds) {
    $impactedWebsiteList = implode(',', $impactedWebsiteIds);
    $qry = <<<SQL
SELECT count(*)
FROM websites w
WHERE w.id IN ($impactedWebsiteList)
AND w.id NOT IN (SELECT from_website_id FROM index_websites_website_agreements WHERE provide_for_editing=true AND to_website_id=$this->website_id);
SQL;
    if ($db->query($qry)->current()->count > 0) {
      $this->fail('Unauthorized', 401, 'Request to move occurrences from websites that don\'t provide editing rights.');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Controller action for the bulk move end-point.
   *
   * Moves a list of records from one website/survey to another. Limited to a
   * user's own records and allowed combinations of source/dest surveys and
   * websites only.
   *
   * Batches of IDs submitted must be in sample ID order, with all records for
   * a sample within a batch, as it is not possible to move some records and
   * not others because this action affects the both the sample and occurrence
   * tables.
   *
   * POST parameters:
   * * occurrence:ids - CSV List of occurrences.
   * * datasetMappings - requested mapping in JSON format, containing
   *   properties called src and dest. Each contains a property for the
   *   website_id and survey_id.
   * * precheck - must be set to 'f'' to actually take any action, otherwise a
   *   precheck is performed to validate that the selection of records to move
   *   is allowed.   *
   */
  public function bulk_move() {
    header('Content-Type: application/json');
    $tm = microtime(TRUE);
    $this->authenticate('write');
    if (!$this->checkBulkMovePostParams()) {
      kohana::log('debug', 'Bulk move post params failed');
      return;
    }
    // @todo Check move from and to websites are authorised for editing according to authenticated website's sharing.
    $datasetMappings = json_decode($_POST['datasetMappings']);
    if (empty($datasetMappings)) {
      $this->fail('Bad Request', 400, 'The datasetMappings parameter was not valid JSON.');
      kohana::log('debug', 'Bulk move mappings contain invalid JSON: ' . $_POST['datasetMappings']);
      return;
    }
    // Validate that list of website/survey mappings in the request all match
    // one in the configured list of allowed valid pairings.
    $allowedMappings = kohana::config('data_utils.bulk_move_allowed_mappings');
    $validSrcSurveyIds = [];
    $impactedWebsiteIds = [];
    // Snippets of SQL that can be used in SQL CASE statement to update IDs.
    $websiteMappingSqlSnippets = [];
    $surveyMappingSqlSnippets = [];
    // Check and process each requested mapping.
    foreach ($datasetMappings as $mapping) {
      $allowed = FALSE;
      foreach ($allowedMappings as $allowedMapping) {
        if ($allowedMapping['src']['survey_id'] === $mapping->src->survey_id
            && $allowedMapping['src']['website_id'] === $mapping->src->website_id
            && $allowedMapping['dest']['survey_id'] === $mapping->dest->survey_id
            && $allowedMapping['dest']['website_id'] === $mapping->dest->website_id) {
          $allowed = TRUE;
          break;
        }
      }
      // Requesting a mapping that wasn't in the configured list of allowed
      // mappings.
      if (!$allowed) {
        $this->fail('Bad Request', 400, 'The datasetMappings parameter contains a disallowed website/survey combination.');
        return;
      }
      else {
        $validSrcSurveyIds[] = (int) $mapping->src->survey_id;
        $impactedWebsiteIds[] = (int) $mapping->src->website_id;
        $impactedWebsiteIds[] = (int) $mapping->dest->website_id;
        $websiteMappingSqlSnippets[] = 'WHEN ' . (int) $mapping->src->website_id . ' THEN ' . (int) $mapping->dest->website_id;
        $surveyMappingSqlSnippets[] = 'WHEN ' . (int) $mapping->src->survey_id . ' THEN ' . (int) $mapping->dest->survey_id;
      }
    }
    $db = new Database();
    $occurrenceIds = $_POST['occurrence:ids'];
    if (!preg_match('/^\d+(,\d+)*$/', $_POST['occurrence:ids'])) {
      $this->fail('Bad request', 400, 'Invalid format for occurrence:ids parameter.');
    }
    if (!$this->checkWebsitesAuthorisedForMove($db, $impactedWebsiteIds)) {
      return;
    }
    // Checks the requested list of occurrences are actually OK to move.
    if (!$this->precheckBulkMoveOccurrences($db, $occurrenceIds, $validSrcSurveyIds, $countStats)) {
      return;
    }
    if ($_POST['precheck'] === 'f') {
      $websiteMappingSqlSnippetList = implode(' ', $websiteMappingSqlSnippets);
      $surveyMappingSqlSnippetList = implode(' ', $surveyMappingSqlSnippets);
      // Change IDs in occurrences and samples table.
      $sql = <<<SQL
UPDATE occurrences
SET website_id=CASE website_id $websiteMappingSqlSnippetList END
WHERE id IN ($occurrenceIds)
AND deleted=false;

UPDATE samples
SET survey_id=CASE survey_id $surveyMappingSqlSnippetList END
WHERE id IN (SELECT DISTINCT sample_id FROM occurrences WHERE id IN ($occurrenceIds))
AND deleted=false;

INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
SELECT 'task_cache_builder_post_move', 'occurrence', o.id, 100, 2, now()
FROM occurrences o
LEFT JOIN work_queue q ON q.record_id=o.id AND q.task='task_cache_builder_post_move' AND q.entity='occurrence'
WHERE o.id IN ($occurrenceIds)
AND o.deleted=false
AND q.id IS NULL;
SQL;
      $db->query($sql);
    }
    $response = [
      'code' => 200,
      'status' => 'OK',
      'action' => !empty($_POST['precheck']) && $_POST['precheck'] === 'f' ? 'records moved' : 'none',
      'affected' => [
        'occurrences' => $countStats->occurrence_count,
        'samples' => $countStats->sample_count,
      ]
    ];
    echo json_encode($response);
    if (class_exists('request_logging')) {
      request_logging::log('a', 'data', NULL, 'bulk_move', $this->website_id, $this->auth_user_id, $tm, $db);
    }
  }

  /**
   * Ensure that values provided for a bulk update are valid.
   *
   * @param object $updates
   *   Decoded update values.
   *
   * @param bool
   *   False if any rule fails, in which case an error response is sent.
   */
  private function validateBulkEditUpdateValues($updates) {
    if (!empty($updates->date)) {
      // Date format check.
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $updates->date)) {
        $this->fail('Bad request', 400, 'Date format incorrect, should be yyyy-mm-dd');
        return FALSE;
      }
      // Date in future check.
      if (strtotime($updates->date) > time()) {
        $this->fail('Bad request', 400, 'Date cannot be in the future.');
        return FALSE;
      }
    }
    if (!empty($updates->sref)) {
      // Validate spatial reference.
      if (empty($updates->sref_system)) {
        $this->fail('Bad request', 400, 'Bulk update of a spatial reference (sref) requires a system.');
        return FALSE;
      }
      if (!spatial_ref::is_valid($updates->sref, $updates->sref_system)) {
        $this->fail('Bad request', 400, 'Spatial reference supplied for bulk update is not recognised.');
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Controller action for the bulk edit endpoint.
   *
   * @todo Website restrictions
   * @todo restrictToOwnData -
   * @todo restrictToOwnData if not set, then ensure current user has site admin rights
   */
  public function bulk_edit() {
    header('Content-Type: application/json');
    $tm = microtime(TRUE);
    $this->authenticate('write');
    $updates = json_decode($_POST['updates']);
    if (!preg_match('/^\d+(,\d+)*$/', $_POST['occurrence:ids'])) {
      $this->fail('Bad request', 400, 'Invalid format for occurrence:ids parameter.');
    }
    $occurrenceIds = $_POST['occurrence:ids'];
    $options = json_decode($_POST['options'] ?? '{}');
    if (!$this->validateBulkEditUpdateValues($updates)) {
      return;
    }
    $db = new Database();
    $results = $this->checkAffectedSamplesDontContainOtherOccurrences($db, $occurrenceIds);
    if ($results) {
      if (!empty($options->allowSampleSplits)) {
        $this->splitSamplesFromOtherOccurrences($db, $occurrenceIds);
      }
      else {
        $message = 'Samples require splitting';
        //'The list of occurrences being edited belong to samples which contain other occurrences which are not being edited. ' .
        //  "For example, sample $results->sample_id for occurrence $results->included_id also contains occurrence $results->excluded_id which is not in the list of records to edit.";
        $this->fail('Conflict', 409, $message, 'SAMPLES_CONTAIN_OTHER_OCCURRENCES', [
          'sample_id' => $results->sample_id,
          'included_occurrence_id' => $results->included_id,
          'excluded_occurrence_id' => $results->excluded_id,
        ]);
        return FALSE;
      }
    }
    $sampleIds = $db->query("SELECT string_agg(distinct sample_id::text, ',') FROM occurrences WHERE id IN ($occurrenceIds) AND deleted=false")->current()->string_agg;
    if (!$this->checkSamplesAllBelongToUser($db, $sampleIds)) {
      $this->fail('Unauthorized', 404, 'You cannot edit samples belonging to other users.');
      return FALSE;
    }
    $sampleFieldUpdates = $this->getSampleFieldUpdates($db, $updates);
    $sampleFieldUpdateSql = empty($sampleFieldUpdates) ? '' : implode(',', $sampleFieldUpdates) . ', ';
    $sampleFieldChangedCheckSql = empty($sampleFieldUpdates) ? 'false' : 'NOT (s.' . implode(' AND s.', $sampleFieldUpdates) . ')';
    $recorderNameFieldChangedCheckSql = empty($updates->recorder_name) ? '' : 'OR snf.recorders<>' . pg_escape_literal($db->getLink(), $updates->recorder_name);
    $langRecheck = pg_escape_literal($db->getLink(), kohana::lang('misc.recheck_verification'));
    $userId = (int) $this->user_id;
    $qry = <<<SQL
      SELECT s.id
      INTO TEMPORARY changing_samples
      FROM samples s
      JOIN cache_samples_nonfunctional snf ON snf.id=s.id
      WHERE ($sampleFieldChangedCheckSql
      $recorderNameFieldChangedCheckSql)
      AND s.deleted=false
      AND s.id IN ($sampleIds)
      -- Ensure only bulk update own samples.
      AND s.created_by_id=$userId;

      UPDATE samples s
      SET $sampleFieldUpdateSql
        updated_on=now(),
        updated_by_id=$userId,
        record_status='C',
        verified_by_id=null,
        verified_on=null
      FROM changing_samples cs
      WHERE cs.id=s.id;

      -- Also reset verification status on changed occurrences.
      INSERT INTO occurrence_comments (occurrence_id, comment, auto_generated, created_on, created_by_id, updated_on, updated_by_id)
      SELECT o.id, $langRecheck, 't', now(), $userId, now(), $userId
      FROM changing_samples cs
      JOIN occurrences o ON o.sample_id=cs.id
      AND o.deleted=false
      AND (o.record_status<>'C' OR o.record_substatus IS NOT NULL);

      UPDATE occurrences o
      SET updated_on=now(),
        updated_by_id=$userId,
        record_status='C',
        record_substatus=null,
        verified_by_id=null,
        verified_on=null
      FROM changing_samples cs
      WHERE cs.id=o.sample_id
      AND o.deleted=false;

SQL;
    $db->query($qry);
    if (!empty($updates->recorder_name)) {
      // Recorder name a little different as it might be a custom attribute.
      $this->bulkEditRecorderNames($db, $sampleIds, $updates->recorder_name);
    }

    // Update the cache_* data using the work queue.
    $qry = <<<SQL
      INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
      SELECT DISTINCT 'task_cache_builder_update', 'occurrence', o.id, 50, 2, now()
      FROM occurrences o
      LEFT JOIN work_queue q ON q.record_id=o.id AND q.task='task_cache_builder_update' AND q.entity='occurrence'
      WHERE o.id IN ($occurrenceIds)
      AND o.deleted=false
      AND q.id IS NULL;

      INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
      SELECT DISTINCT 'task_cache_builder_update', 'sample', s.id, 50, 2, now()
      FROM samples s
      LEFT JOIN work_queue q ON q.record_id=s.id AND q.task='task_cache_builder_update' AND q.entity='sample'
      WHERE s.id IN ($sampleIds)
      AND s.deleted=false
      AND q.id IS NULL;
SQL;
    $db->query($qry);
    $response = [
      'code' => 200,
      'status' => 'OK',
      'action' => 'records edited',
      'affected' => [
        'samples' => count(explode(',', $sampleIds)),
        'occurrences' => count(explode(',', $occurrenceIds)),
      ],
    ];
    echo json_encode($response);
    if (class_exists('request_logging')) {
      request_logging::log('a', 'data', NULL, 'bulk_edit', $this->website_id, $this->auth_user_id, $tm, $db);
    }
  }

  /**
   * Retrieve a list of field=value updates to apply to the sample table.
   *
   * @param object $db
   *   Database connection.
   * @param object $updates
   *   Object containing the values to update.
   *
   * @return string[]
   *   List of field=value pairs, suitable for use in an SQL statement.
   */
  private function getSampleFieldUpdates($db, $updates) {
    $sampleFieldUpdates = [];
    if (!empty($updates->date)) {
      $date = pg_escape_literal($db->getLink(), $updates->date);
      $sampleFieldUpdates[] = "date_start=$date::date";
      $sampleFieldUpdates[] = "date_end=$date::date";
      $sampleFieldUpdates[] = "date_type='D'";
    }
    if (!empty($updates->location_name)) {
      $locationName = pg_escape_literal($db->getLink(), $updates->location_name);
      $sampleFieldUpdates[] = "location_name=$locationName";
    }
    if (!empty($updates->sref)) {
      $sref = pg_escape_literal($db->getLink(), spatial_ref::sref_format_tidy($updates->sref, $updates->sref_system));
      $sampleFieldUpdates[] = "entered_sref=$sref";
      $system = pg_escape_literal($db->getLink(), $updates->sref_system);
      $sampleFieldUpdates[] = "entered_sref_system=$system";
      $geom = pg_escape_literal($db->getLink(), spatial_ref::sref_to_internal_wkt($updates->sref, $updates->sref_system));
      $sampleFieldUpdates[] = "geom=st_geomfromtext($geom, 900913)";
    }
    return $sampleFieldUpdates;
  }

  /**
   * Confirms that a list of sample IDs are all created by the current user.
   *
   * @param object $db
   *   Database connection.
   * @param string $sampleIds
   *   CSV format list of sample IDs.
   *
   * @return bool
   *   True if all samples in the list belong to the current user.
   */
  private function checkSamplesAllBelongToUser($db, $sampleIds) {
    $qry = "SELECT count(*) FROM samples WHERE id in ($sampleIds) AND deleted=false AND created_by_id<>?";
    return $db->query($qry, [$this->user_id])->current()->count === '0';
  }

  /**
   * Handle the bulk edit of recorder names.
   *
   * Complex due to optional custom attributes vs samples.recorder_names field.
   *
   * @param object $db
   *   Database connection.
   * @param string $sampleIds
   *   CSV format list of sample IDs.
   * @param string $recorderName
   *   Recorder name to set.
   */
  private function bulkEditRecorderNames($db, $sampleIds, $recorderName) {
    $userId = (int) $this->user_id;
    $qry = <<<SQL
-- Update existing custom attributes values.
UPDATE sample_attribute_values v
SET text_value='$recorderName', updated_on=now(), updated_by_id=$userId
FROM sample_attributes a
WHERE a.id=v.sample_attribute_id
AND a.deleted=false
AND a.system_function='full_name'
AND v.deleted=false
AND v.sample_id in ($sampleIds);

-- Insert new custom attribute values if linked to the samples survey and an
-- attribute value not already present.
INSERT INTO sample_attribute_values(sample_id, sample_attribute_id, text_value, created_on, created_by_id, updated_on, updated_by_id)
SELECT s.id, a.id, '$recorderName', now(), $userId, now(), $userId
FROM samples s
LEFT JOIN (sample_attribute_values vexist
  JOIN sample_attributes aexist ON aexist.deleted=false AND aexist.system_function='full_name' AND aexist.id=vexist.sample_attribute_id
) ON vexist.sample_id=s.id AND vexist.deleted=false
JOIN sample_attributes_websites aw ON aw.restrict_to_survey_id=s.survey_id
JOIN sample_attributes a ON a.id=aw.sample_attribute_id AND a.deleted=false AND a.system_function='full_name'
WHERE s.id IN ($sampleIds)
AND vexist.id IS NULL;

-- For any samples that don't have an appropriate attribute in their survey,
-- set the recorder_names field.
UPDATE samples s
SET recorder_names='$recorderName'
FROM samples s2
LEFT JOIN (sample_attribute_values vexist
  JOIN sample_attributes aexist ON aexist.deleted=false AND aexist.system_function='full_name' AND aexist.id=vexist.sample_attribute_id
) ON vexist.sample_id=s2.id AND vexist.deleted=false
WHERE s2.id=s.id
AND vexist.id IS NULL
AND s2.id in ($sampleIds)
AND s2.deleted=false;
SQL;
    $db->query($qry);
  }

}
