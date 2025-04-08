<?php

/**
 * @file
 * Classes for the importer_2 web-services.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

define('BATCH_ROW_LIMIT', 100);
define('SYSTEM_FIELD_NAMES', [
  '_row_id',
  'errors',
]);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Shared\Date as ImportDate;

/**
 * Exception class for aborting.
 */
class RequestAbort extends Exception {}

/**
 * Exception class for failure to find item in a list.
 */
class NotFoundException extends Exception {}

/**
 * Controller class for import web services.
 */
class Import_2_Controller extends Service_Base_Controller {

  /**
   * Fetches the list of available import plugins.
   *
   * Public controller function so can be called from form config on the
   * client.
   *
   * @param string $entity
   *   Import entity, e.g. occurrence or sample.
   *
   * @return array
   *   A JSON object where the properties are plugin names and descriptions are
   *   in the values.
   */
  function get_plugins() {
    header("Content-Type: application/json");
    $this->authenticate('read');
    // Currently only supports occurrence, but other entities may be added in
    // future.
    if (empty($_GET['entity']) || $_GET['entity'] !== 'occurrence') {
      http_response_code(400);
      echo json_encode([
        'error' => 'Invalid or missing entity parameter.',
        'status' => 'Bad Request',
      ]);
    }
    $plugins = [];
    foreach (Kohana::config('config.modules') as $path) {
      $plugin = basename($path);
      if (file_exists("$path/plugins/$plugin.php")) {
        require_once "$path/plugins/$plugin.php";
        if (function_exists($plugin . '_import_plugins')) {
          $plugins = array_merge($plugins, call_user_func($plugin . '_import_plugins', $_GET['entity']));
        }
      }
    }
    echo json_encode($plugins);
  }

  /**
   * Import globalvalues form service end-point.
   *
   * Controller function that provides a web service
   * services/import/get_globalvalues_form/{entity}. Options for the entity's
   * specific form can be passed in $_GET. Echos JSON parameters form details
   * for this entity, or empty string if no parameters form required.
   *
   * @param string $entity
   *   Singular name of the entity to check.
   */
  public function get_globalvalues_form($entity) {
    header("Content-Type: application/json");
    $this->authenticate('read');
    $model = ORM::factory($entity);
    if (method_exists($model, 'fixedValuesForm')) {
      // Pass URL parameters through to the fixed values form in case there are
      // model specific settings.
      $options = array_merge($_GET);
      unset($options['nonce']);
      unset($options['auth_token']);
      echo json_encode($model->fixedValuesForm($options));
    }
  }

  /**
   * Controller function that returns the list of import fields for an entity.
   *
   * Accepts optional $_GET parameters for the website_id, survey_id,
   * taxon_list_id and use_associations which influences the required fields
   * returned in the result, since custom attributes are only associated with
   * certain website/survey dataset/taxon list combinations.
   *
   * Echoes JSON listing the fields that can be imported.
   *
   * @param string $entity
   *   Singular name of the entity to check.
   */
  public function get_fields($entity) {
    header("Content-Type: application/json");
    $this->authenticate('read');
    // Newer versions of client send the data file, allowing import plugins to
    // be accessed from config.
    $plugins = [];
    if (!empty($_POST['data-file'])) {
      $config = $this->getConfig($_POST['data-file']);
      $plugins = $config['plugins'];
    }
    else {
      // Pre 9.3 client does not handle plugins anyway.
      $plugins = [];
    }
    switch ($entity) {
      case 'sample':
      case 'occurrence':
        $attrTypeFilter = empty($_GET['sample_method_id']) ? NULL : $_GET['sample_method_id'];
        break;

      case 'location':
        $attrTypeFilter = empty($_GET['location_type_id']) ? NULL : $_GET['location_type_id'];
        break;

      default:
        $attrTypeFilter = NULL;
        break;
    }
    $model = ORM::factory($entity);
    // Identify the context of the import.
    $identifiers = [];
    if (!empty($_GET['website_id'])) {
      $identifiers['website_id'] = $_GET['website_id'];
    }
    if (!empty($_GET['survey_id'])) {
      $identifiers['survey_id'] = $_GET['survey_id'];
    }
    if (!empty($_GET['taxon_list_id'])) {
      $identifiers['taxon_list_id'] = $_GET['taxon_list_id'];
    }
    $useAssociations = !empty($_GET['use_associations']) && $_GET['use_associations'] === 'true';
    $fields = $model->getSubmittableFields(TRUE, $identifiers, $attrTypeFilter, $useAssociations);
    $wantRequired = !empty($_GET['required']) && $_GET['required'] === 'true';
    if ($wantRequired) {
      $requiredFields = $model->getRequiredFields(TRUE, $identifiers, $useAssociations);
      // Use the calculated date field for vague date, rather than individual
      // fields.
      foreach ($requiredFields as &$field) {
        $field = preg_replace('/:date_type$/', ':date', $field);
      }
      $fields = array_intersect_key($fields, array_combine($requiredFields, $requiredFields));
    }
    $this->provideFriendlyFieldCaptions($fields);
    // Allow import plugins to modify the list of available fields.
    foreach ($plugins as $plugin => $params) {
      if (method_exists("importPlugin$plugin", 'alterAvailableDbFields')) {
        call_user_func_array("importPlugin$plugin::alterAvailableDbFields", [$params, $wantRequired, &$fields]);
      }
    }
    echo json_encode($fields);
  }

  /**
   * Handle the upload of an import file.
   *
   * Controller action that provides a web service services/import/upload_file
   * and handles uploaded files in the $_FILES array by moving them to the
   * import folder. The current time is prefixed to the name to make it unique.
   * The uploaded file should be in a field called media_upload.
   */
  public function upload_file() {
    header("Content-Type: application/json");
    $this->authenticate('write');
    try {
      $importTools = new ImportTools();
      $uploadedFile = $importTools->uploadFile();
      echo json_encode([
        'status' => 'ok',
        'uploadedFile' => basename($uploadedFile),
      ]);
    }
    catch (Exception $e) {
      kohana::log('debug', 'Error in upload_file: ' . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Extract a Zipped upload data file.
   *
   * Controller action that provides a web service services/import/extract_file
   * and unzips a data file where required. The file should already exist in
   * the /imports folder and the file name given in the POST data uploaded-file
   * value.
   */
  public function extract_file() {
    header("Content-Type: application/json");
    $this->authenticate('write');
    try {
      // Ensure we have write permissions.
      $this->authenticate();
      if (empty($_POST['uploaded-file'])) {
        throw new exception('Parameter uploaded-file is required for file extraction');
      }
      $importTools = new ImportTools();
      $fileName = $importTools->extractFile($_POST['uploaded-file']);
      echo json_encode([
        'status' => 'ok',
        'dataFile' => $fileName,
      ]);
    }
    catch (Exception $e) {
      kohana::log('debug', 'Error in extract_file: ' . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Controller action to initialise the import configuration file.
   */
  public function init_server_config() {
    header("Content-Type: application/json");
    $fileName = $_POST['data-file'];
    if (!file_exists(DOCROOT . "import/$fileName")) {
      throw new exception('Parameter data-file refers to a missing file');
    }
    $config = $this->getConfig($fileName);
    if (!empty($_POST['import_template_id'])) {
      // Merge the template into the config.
      $template = ORM::factory('import_template', $_POST['import_template_id']);
      if ($template->id) {
        // Save the template mappings in the config.
        $config['columns'] = json_decode($template->mappings, TRUE);
        $config['importTemplateId'] = $template->id;
        $config['importTemplateTitle'] = $template->title;
      }
    }
    $config['plugins'] = json_decode($_POST['plugins'] ?? '{}', TRUE);
    $this->saveConfig($fileName, $config);
    echo json_encode([
      'status' => 'ok',
    ]);
  }

  /**
   * Extract a Zipped upload data file.
   *
   * Controller action that provides a web service
   * services/import/load_chunk_to_temp_table service. On first call, prepares
   * a temporary table, then on subsequent calls loads a chunk of records into
   * the temporary table. The file should already exist in the /imports folder,
   * be decompressed and the file name given in the POST data uploaded-file
   * value.
   */
  public function load_chunk_to_temp_table() {
    header("Content-Type: application/json");
    try {
      // Ensure we have write permissions.
      $this->authenticate('write');
      if (empty($_POST['data-file'])) {
        throw new exception('Parameter data-file is required to load the next batch of recrods');
      }
      $fileName = $_POST['data-file'];
      if (!file_exists(DOCROOT . "import/$fileName")) {
        throw new exception('Parameter data-file refers to a missing file');
      }
      $config = $this->getConfig($fileName);
      if ($config['state'] === 'initial') {
        $this->createTempTable($fileName, $config);
        $config['state'] = 'loadingRecords';
      }
      elseif ($config['state'] === 'loadingRecords') {
        $this->loadNextRecordsBatch($fileName, $config);
      }
      $this->saveConfig($fileName, $config);
      echo json_encode([
        'status' => 'ok',
        'progress' => $config['progress'],
        'msgKey' => $config['state'],
      ]);
    }
    catch (Exception $e) {
      error_logger::log_error('Error in load_chunk_to_temp_table', $e);
      kohana::log('debug', 'Error in load_chunk_to_temp_table: ' . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Controller action to find information on lookup fields.
   *
   * Can be called repratedly until the response msgKey is set to
   * findLookupFieldsDone. Returns information on the next found lookup field
   * that has data values that need to be matched.
   */
  public function process_lookup_matching() {
    header("Content-Type: application/json");
    try {
      $this->authenticate('write');
      $fileName = $_POST['data-file'];
      if (!file_exists(DOCROOT . "import/$fileName")) {
        throw new exception('Parameter data-file refers to a missing file');
      }
      $config = $this->getConfig($fileName);
      $db = new Database();
      $r = $this->findNextLookupField($db, $config, (integer) $_POST['index']);
      $this->saveConfig($fileName, $config);
      echo json_encode($r);
    }
    catch (Exception $e) {
      error_logger::log_error('Error in process_lookup_matching', $e);
      kohana::log('debug', 'Error in process_lookup_matching: ' . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Controller action that saves a set of lookup matches.
   *
   * Receives the mappings from imported values to the lookup term IDs and
   * applies them to the temporay import data table's ID columns.
   *
   * Requires write authentication. The POST data must contain a field called
   * matches-info, with a source-field property (name of the field) and a
   * values property containing matched value/id pairs.
   */
  public function save_lookup_matches_group() {
    header("Content-Type: application/json");
    try {
      $this->authenticate('write');
      $fileName = $_POST['data-file'];
      if (!file_exists(DOCROOT . "import/$fileName")) {
        throw new exception('Parameter data-file refers to a missing file');
      }
      $config = $this->getConfig($fileName);
      $db = new Database();
      $matchesInfo = json_decode($_POST['matches-info'], TRUE);
      $sourceColName = pg_escape_identifier($db->getLink(), $matchesInfo['source-field']);
      $sourceIdCol = pg_escape_identifier($db->getLink(), $matchesInfo['source-field'] . '_id');
      $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
      foreach ($matchesInfo['values'] as $value => $termlist_term_id) {
        // Safety check.
        if (!preg_match('/\d+/', $termlist_term_id)) {
          throw new exception('Mapped termlist term ID is not an integer.');
        }
        $sql = <<<SQL
          UPDATE import_temp.$dbIdentifiers[tempTableName]
          SET $sourceIdCol=?
          WHERE trim(lower($sourceColName))=lower(?);
        SQL;
        $db->query($sql, [$termlist_term_id, $value]);
      }
      // Need to check all done.
      $sql = <<<SQL
        SELECT DISTINCT $sourceColName AS value
        FROM import_temp.$dbIdentifiers[tempTableName]
        WHERE $sourceColName<>'' AND $sourceIdCol IS NULL;
      SQL;
      $countCheck = $db->query($sql)->result_array();
      if (count($countCheck) === 0) {
        echo json_encode([
          'status' => 'ok',
        ]);
      }
      else {
        $unmatched = [];
        foreach ($countCheck as $row) {
          $unmatched[] = $row->value;
        }
        echo json_encode([
          'status' => 'incomplete',
          'unmatched' => $unmatched,
        ]);
      }

    }
    catch (Exception $e) {
      error_logger::log_error('Error in save_lookup_matches_group', $e);
      kohana::log('debug', 'Error in save_lookup_matches_group: ' . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Controller action specifically to save the mappings to warehouse fields.
   *
   * Updates the columns config to identify the warehouse field for each
   * mapping.
   */
  public function save_mappings() {
    header("Content-Type: application/json");
    $this->authenticate('write');
    $fileName = $_POST['data-file'];
    $config = $this->getConfig($fileName);
    foreach (json_decode($_POST['mappings']) as $key => $value) {
      try {
        if (!empty($value)) {
          $columnLabel = $this->getColumnLabelForTempDbField($config['columns'], $key);
          $config['columns'][$columnLabel]['warehouseField'] = $value;
        }
      }
      catch (NotFoundException $e) {
        // No column label, as form value not a field mapping.
      }
    }
    // Now we know the mappings, we can disable any plugins that depend on a
    // field that is not selected.
    foreach ($config['plugins'] as $plugin => $params) {
      if (method_exists("importPlugin$plugin", 'isApplicable')) {
        if (!call_user_func_array("importPlugin$plugin::isApplicable", [$params, $config])) {
          unset($config['plugins'][$plugin]);
        }
      }
    }
    $this->saveConfig($fileName, $config);
    echo json_encode([
      'status' => 'ok',
    ]);
  }

  /**
   * Controller action which implements the next preprocessing step.
   *
   * Preprocessing is done once the data are loaded and mappings are done. It
   * can include checking that updates to existing records are valid and cross
   * table validation rules.
   */
  public function preprocess() {
    header("Content-Type: application/json");
    $this->authenticate('write');
    $fileName = $_POST['data-file'];
    $stepIndex = $_POST['index'];
    $config = $this->getConfig($fileName);
    $steps = [];
    $parentTable = inflector::plural($config['parentEntity']);
    if (isset($config['global-values']['config:allowUpdates']) && $config['global-values']['config:allowUpdates'] === '1') {
      foreach ($config['columns'] as $info) {
        if (isset($info['warehouseField'])) {
          if ($info['warehouseField'] === "$config[entity]:id") {
            $steps[] = [
              'checkRecordIdsOwnedByUser',
              'Checking record permissions',
            ];
            $config['pkFieldInTempTable'] = $info['tempDbField'];
          }
          elseif ($info['warehouseField'] === "$config[entity]:external_key") {
            $steps[] = [
              'mapExternalKeyToExistingRecords',
              'Finding existing records',
            ];
            $config['pkFieldInTempTable'] = "_$config[entity]_id";
          }
        }
      }
      if (!empty($config['parentEntity'])) {
        $steps[] = [
          'findOriginalParentEntityIds',
          "Finding existing $parentTable",
        ];
        $steps[] = [
          'clearParentEntityIdsIfNotAllChildrenPresent',
          "Checking links to $parentTable - step 1",
        ];
        $steps[] = [
          'clearNewParentEntityIdsIfNowMultipleSamples',
          "Checking links to $parentTable - step 2",
        ];
      }
    }
    // Allow plugins to extend the list of preprocessing steps.
    foreach ($config['plugins'] as $plugin => $params) {
      if (method_exists("importPlugin$plugin", 'alterPreprocessSteps')) {
        call_user_func_array("importPlugin$plugin::alterPreprocessSteps", [$params, $config, &$steps]);
      }
    }
    if ($stepIndex >= count($steps)) {
      echo json_encode([
        'status' => 'done',
      ]);
    }
    else {
      if (count($steps[$stepIndex]) === 4) {
        // Step added by a plugin.
        $stepOutput = call_user_func_array(
          [$steps[$stepIndex][2], $steps[$stepIndex][0]],
          [$steps[$stepIndex][3], &$config]);
        // Config can be changed by plugin preprocessing.
        $this->saveConfig($fileName, $config);
      }
      else {
        // Step is one of the core list.
        $stepOutput = call_user_func([$this, $steps[$stepIndex][0]], $fileName, $config);
      }
      $r = array_merge([
        'step' => $steps[$stepIndex][0],
        'description' => $steps[$stepIndex][1],
      ], $stepOutput);
      if ($stepIndex < count($steps) - 1) {
        // Another step to do...
        $r['nextStep'] = $steps[$stepIndex + 1][0];
        $r['nextDescription'] = $steps[$stepIndex + 1][1];
      }
      echo json_encode($r);
    }
  }

  /**
   * Allow reversal of imports.
   *
   * At time of writing, only supports occurrence imports (and samples related
   * to the occurrences).
   */
  public function import_reverse() {
    $db = new Database();
    if (!empty($_POST['warehouse_user_id'])) {
      $warehouseUserId = $_POST['warehouse_user_id'];
    }
    else {
      http_response_code(400);
      echo json_encode([
        'status' => 'Bad Request',
        'msg' => 'Missing warehouse_user_id parameter.',
      ]);
      return FALSE;
    }
    $guidToReverse = $_POST['guid_to_reverse'];
    if (!empty($_POST['reverse_mode'])) {
      $reverseMode = $_POST['reverse_mode'];
    }
    else {
      $reverseMode = 'normal';
    }
    /* Reverse occurrences first, otherwise the auto cascade from the sample
    deletions will make it harder to inform the user of which exact occurrence rows
    have been reversed */
    $updatedOccurrences = self::reverseOccurrences($db, $guidToReverse, $warehouseUserId, $reverseMode);
    // Also get rows not reversed, so they can be reported to the user.
    $untouchedOccurrences = self::getUntouchedOccurrences($db, $guidToReverse, $updatedOccurrences);
    // Same for samples.
    $updatedSamples = self::reverseSamples($db, $guidToReverse, $warehouseUserId, $reverseMode);
    $untouchedSamples = self::getUntouchedSamples($db, $guidToReverse, $updatedSamples);

    $importTableSql = <<<SQL
      update imports
      set reversible = false
      where import_guid = ?;
    SQL;

    $db->query($importTableSql, [$guidToReverse]);

    // Create the files users will download when reversal is complete.
    if (!empty($updatedOccurrences)) {
      self::createReversalDetailsFile('occurrence', $updatedOccurrences, $guidToReverse, 'reversed');
    }
    if (!empty($untouchedOccurrences)) {
      self::createReversalDetailsFile('occurrence', $untouchedOccurrences, $guidToReverse, 'untouched');
    }
    if (!empty($updatedSamples)) {
      self::createReversalDetailsFile('sample', $updatedSamples, $guidToReverse, 'reversed');
    }
    if (!empty($untouchedSamples)) {
      self::createReversalDetailsFile('sample', $untouchedSamples, $guidToReverse, 'untouched');
    }
    $response = [
      'status' => 'OK',
      'samplesOutcome' => empty($updatedSamples) ? 'No samples were reversed.' : 'Imported samples were reversed.',
      'samplesDetails' => [],
      'occurrencesOutcome' => empty($updatedOccurrences) ? 'No occurrences were reversed.' : 'Imported occurrences were reversed.',
      'occurrencesDetails' => [],
    ];
    if (empty($updatedSamples)) {
      $response['samplesDetails'][] =
        'This may happen if none of the samples you imported exist in the database anymore. This may also happen if you only selected to reverse unaltered data and no unaltered samples were found.';
    }
    if (!empty($updatedSamples)) {
      $response['samplesDetails'][] = 'Details of reversed samples are <a href="' . url::base() . "upload/reversed_import_rows_samples_$guidToReverse.csv\">here</a>.";
    }
    if (!empty($untouchedSamples)) {
      $response['samplesDetails'][] = 'Details of samples not reversed are <a href="' . url::base() . "upload/untouched_import_rows_samples_$guidToReverse.csv\">here</a></p>";
      $response['samplesDetails'][] = "Samples that were removed from the database before today's reversal will not be shown in the untouched samples download file.";
    }
    if (empty($updatedOccurrences)) {
      $response['occurrencesDetails'][] = 'This may happen if none of the occurrences you imported exist in the database anymore. This may also happen if you only selected to reverse unaltered data and no unaltered occurrences were found.';
    }
    if (!empty($updatedOccurrences)) {
      $response['occurrencesDetails'][] = 'Details of reversed occurrences are <a href="' . url::base() . "upload/reversed_import_rows_occurrences_$guidToReverse.csv\">here</a></p>";
    }
    if (!empty($untouchedOccurrences)) {
      $response['samplesDetails'][] = 'Details of occurrences not reversed are <a href="' . url::base() . "upload/untouched_import_rows_occurrences_$guidToReverse.csv\">here</a></p>";
      $response['occurrencesDetails'][] = "Occurrences that were removed from the database before today's reversal will not be shown in the untouched occurrences download file.";
    }
    echo json_encode($response);
  }

  /**
   * Reverse occurrences.
   *
   * @param object $db
   *   Database object.
   * @param string $guidToReverse
   *   Import unique identifier to reverse.
   * @param int $warehouseUserId
   *   Warehouse User ID of the person doing the reversal.
   * @param string $reverseMode
   *   Reverse all occurrences or ones that haven't been changed since import.
   *
   * @return array
   *   Rows of reversed occurrences.
   */
  private function reverseOccurrences($db, $guidToReverse, int $warehouseUserId, $reverseMode) {
    $guidToReverseEsc = pg_escape_literal($db->getLink(), $guidToReverse);
    $occurrencesUpdateSQL = <<<SQL
      UPDATE occurrences o_update
      SET deleted=true,
      updated_on=now(),
      updated_by_id=$warehouseUserId
      FROM occurrences o\n
    SQL;

    $cacheOccsFunctionalDeletionSQL = <<<SQL
      DELETE
      FROM cache_occurrences_functional
      WHERE id in (
      SELECT o.id
      FROM occurrences o\n
    SQL;

    $cacheOccsNonFunctionalDeletionSQL = <<<SQL
      DELETE
      FROM cache_occurrences_nonfunctional
      WHERE id in (
      SELECT o.id
      FROM occurrences o\n
    SQL;

    $occurrencesJoinSQL = <<<SQL
    JOIN imports i on i.import_guid = o.import_guid
      AND i.import_guid = $guidToReverseEsc
    /* Left Join to samples, as  we don't care if occurrences are attached to a
       a sample that isn't part of import and that has been changed. */
    LEFT JOIN samples s
      ON s.id = o.sample_id
      AND s.import_guid = i.import_guid
      AND s.deleted=false
    WHERE
    o.import_guid = $guidToReverseEsc
    /* We can't easily tell difference between inserted occurrences that are updated,
    and updated occurrences that are updated again. As we are only going to be reversing the former
    (even in "reverse regardless of changes" mode), we need a way to only include these.
    We can do this by getting the most recent occurrences for the import, limited by the imports
    table inserted value, this will exclude updated occurrences that are updated a second time.
    Note that this method would not work if the user did further imports that affected these inserted rows,
    however we already exclude imports like this from the reverser's import select drop-down */
    AND o.id in (
      select id
      FROM occurrences o2
      WHERE
        o2.import_guid = $guidToReverseEsc
      ORDER BY o2.id DESC
      LIMIT i.inserted
    )\n
    SQL;

    $cacheOccsFunctionalDeletionSQL .= $occurrencesJoinSQL;
    $cacheOccsNonFunctionalDeletionSQL .= $occurrencesJoinSQL;
    // The main occurrences SQL is slightly different as doesn't use "in"
    // clause, so needs a bit of extra SQL.
    $occurrencesUpdateSQL .= $occurrencesJoinSQL .
    "AND o.id = o_update.id AND o.deleted=false\n";

    /* Only update occurrences where the created_on date/time is the same
    and the sample from that same import hasn't been updated either. */
    if (!empty($reverseMode) && $reverseMode == 'do_not_reverse_updated') {
      $occurrencesNoChangesSQL = <<<SQL
      AND (s.id IS NULL OR s.created_on = s.updated_on)
      AND o.created_on = o.updated_on\n
      SQL;

      $cacheOccsFunctionalDeletionSQL .= $occurrencesNoChangesSQL;
      $cacheOccsNonFunctionalDeletionSQL .= $occurrencesNoChangesSQL;
      $occurrencesUpdateSQL .= $occurrencesNoChangesSQL;
    }
    // Return the updated rows (in similar way as a select statement would)
    // Can't use "RETURNING *" as that includes columns from the joins
    // and messes up result (e.g the ID comes out wrong as both the occurence
    // and import tables have it).
    $occurrencesUpdateSQL .= <<<SQL
    RETURNING o.id, o.sample_id, o.determiner_id, o.confidential, o.created_on, o.created_by_id,
    o.website_id, o.external_key, o.comment, o.taxa_taxon_list_id, o.record_status, o.verified_by_id,
    o.verified_on, o.zero_abundance, o.last_verification_check_date, o.training, o.sensitivity_precision,
    o.release_status, o.record_substatus, o.record_decision_source, o.import_guid
    SQL;
    // These first two statements need a closing bracket,
    // as they use an "in" clause.
    $db->query($cacheOccsFunctionalDeletionSQL . ')');
    $db->query($cacheOccsNonFunctionalDeletionSQL . ')');
    $updatedOccurrences = $db->query($occurrencesUpdateSQL)->result_array();

    return $updatedOccurrences;
  }

  /**
   * Get occurrences which weren't reversed.
   *
   * @param object $db
   *   Database object.
   * @param string $guidToReverse
   *   Import unique identifier to reverse.
   * @param array $updatedOccurrences
   *   Array of occurrences that have been reversed.
   *
   * @return array
   *   Array of occurrences that have not been reversed.
   */
  private function getUntouchedOccurrences($db, $guidToReverse, array $updatedOccurrences) {
    $updatedOccurrenceIds = [];
    // Get the occurrence ids only, so we can use in_array later.
    foreach ($updatedOccurrences as $updatedOccurrenceRow) {
      $updatedOccurrenceIds[] = $updatedOccurrenceRow->id;
    }
    // Get all occurrences for the import.
    $untouchedOccurrencesSQL = <<<SQL
      SELECT *
      FROM occurrences o
      WHERE o.import_guid = ?
      AND o.deleted=false
    SQL;

    $untouchedOccsFromAllOccs = $db->query($untouchedOccurrencesSQL, [$guidToReverse])->result_array();
    // Go through all occurrences for the import.
    foreach ($untouchedOccsFromAllOccs as $idx => $importOcc) {
      /* Is the occurrence id one that has been reversed.
      If it has been reversed, we know that it shouldn't be in the untouched
      occurrences array */
      if (in_array($importOcc->id, $updatedOccurrenceIds)) {
        unset($untouchedOccsFromAllOccs[$idx]);
      }
    }
    return $untouchedOccsFromAllOccs;
  }

  /**
   * Reverse samples.
   *
   * @param object $db
   *   Database object.
   * @param string $guidToReverse
   *   Import unique identifier to reverse.
   * @param int $warehouseUserId
   *   Warehouse User ID of the person doing the reversal.
   * @param string $reverseMode
   *   Reverse all samples or ones that haven't been changed since import.
   *
   * @return array
   *   Rows of reversed samples.
   */
  private function reverseSamples($db, $guidToReverse, int $warehouseUserId, $reverseMode) {
    $guidToReverseEsc = pg_escape_literal($db->getLink(), $guidToReverse);
    $samplesUpdateSQL = <<<SQL
      UPDATE samples s_update
      SET deleted=true,
      updated_on=now(),
      updated_by_id=$warehouseUserId
      FROM samples as s\n
    SQL;

    $cacheSmpsFunctionalDeletionSQL = <<<SQL
      DELETE
      FROM cache_samples_functional
      where id in (
      SELECT s.id
      FROM samples s\n
    SQL;

    $cacheSmpsNonFunctionalDeletionSQL = <<<SQL
      DELETE
      FROM cache_samples_nonfunctional
      where id in (
      SELECT s.id
      FROM samples s\n
    SQL;

    $samplesJoinSQL = <<<SQL
    LEFT JOIN occurrences o
      ON o.sample_id = s.id
      AND o.deleted=false
    WHERE
      s.import_guid = $guidToReverseEsc
      /* Don't delete a sample if still contains occurrences after occurrence processing */
      AND o.id IS NULL
      AND s.deleted=false\n
    SQL;

    $cacheSmpsFunctionalDeletionSQL .= $samplesJoinSQL;
    $cacheSmpsNonFunctionalDeletionSQL .= $samplesJoinSQL;
    // The main occurrences SQL is slightly different as doesn't use "in"
    // clause, so needs a bit of extra SQL.
    $samplesUpdateSQL .= $samplesJoinSQL .
    "AND s.id = s_update.id\n";

    if (!empty($reverseMode) && $reverseMode == 'do_not_reverse_updated') {
      $samplesNoChangesSQL = <<<SQL
        AND s.created_on = s.updated_on\n
      SQL;

      $cacheSmpsFunctionalDeletionSQL .= $samplesNoChangesSQL;
      $cacheSmpsNonFunctionalDeletionSQL .= $samplesNoChangesSQL;
      $samplesUpdateSQL .= $samplesNoChangesSQL;
    }
    // Return the updated rows (in similar way as a select statement would)
    // Can't use "RETURNING *" as that includes columns from the joins
    // and messes up result (e.g the ID comes out wrong as both the sample
    // and import tables have it).
    $samplesUpdateSQL .= <<<SQL
    RETURNING s.id, s.survey_id, s.location_id,s.date_start,s.date_end,s.date_type,
    s.entered_sref, s.entered_sref_system, s.location_name, s.created_on,
    s.created_by_id, s.comment, s.external_key, s.sample_method_id, s.recorder_names,
    s.parent_id, s.input_form, s.group_id, s.privacy_precision, s.record_status,
    s.verified_by_id, s.verified_on, s.licence_id,s.training, s.import_guid
    SQL;
    // These first two statements need a closing bracket,
    // as they use an "in" clause.
    $db->query($cacheSmpsFunctionalDeletionSQL . ')');
    $db->query($cacheSmpsNonFunctionalDeletionSQL . ')');
    $updatedSamples = $db->query($samplesUpdateSQL)->result_array();

    return $updatedSamples;
  }

  /**
   * Get samples which weren't reversed.
   *
   * @param object $db
   *   Database object.
   * @param string $guidToReverse
   *   Import unique identifier to reverse.
   * @param array $updatedSamples
   *   Array of samples that have been reversed.
   *
   * @return array
   *   Array of samples that have not been reversed.
   */
  private function getUntouchedSamples($db, $guidToReverse, array $updatedSamples) {
    $updatedSampleIds = [];
    // Get ids only so we can use in_array.
    foreach ($updatedSamples as $updatedSampleRow) {
      $updatedSampleIds[] = $updatedSampleRow->id;
    }
    // Get all samples for the import.
    $untouchedSamplesSQL = <<<SQL
      SELECT *
      FROM samples s
      WHERE s.import_guid = ?
      AND s.deleted=false
    SQL;
    $untouchedSmpsFromAllSmps = $db->query($untouchedSamplesSQL, [$guidToReverse])->result_array();
    // Go through all samples for the import.
    foreach ($untouchedSmpsFromAllSmps as $idx => $importSmp) {
      /* Is the sample id one that has been reversed.
      If it has been reversed, we know that it shouldn't be in the untouched
      samples array */
      if (in_array($importSmp->id, $updatedSampleIds)) {
        unset($untouchedSmpsFromAllSmps[$idx]);
      }
    }
    return $untouchedSmpsFromAllSmps;
  }

  /**
   * Create a file containing rows related to the reversal.
   *
   * @param string $entity
   *   Include the type of data in the file name.
   * @param array $rowsToProcess
   *   Rows to put in the file.
   * @param string $guidToReverse
   *   Unique identifier of the import to put in the file name.
   * @param string $type
   *   Reversed row, or untouched row. Used in filename.
   */
  private function createReversalDetailsFile($entity, array $rowsToProcess, $guidToReverse, $type) {
    if (!empty($rowsToProcess)) {
      if ($type === 'reversed') {
        $fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/upload/reversed_import_rows_" . $entity . "s_$guidToReverse.csv","wb");
      }
      else {
        $fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/upload/untouched_import_rows_" . $entity . "s_$guidToReverse.csv","wb");
      }
      // Create header row using the key from first row in array.
      $headerString = '';
      foreach ($rowsToProcess[0] as $header => $value) {
        $headerString = $headerString . $header . ',';
      }
      // Get rid of comma which we don't want on end.
      $headerString = rtrim($headerString, ',');
      // Make sure new line is at end of header row.
      $headerString = $headerString . "\r\n";
      $dataRowsString = '';
      // Now process the values from each row in the array.
      for ($idx = 0; $idx < count($rowsToProcess); $idx++) {
        // For each row, comma separate each value.
        foreach ($rowsToProcess[$idx] as $value) {
          $dataRowsString = $dataRowsString . $value . ',';
        }
        // Again, get rid of comma off end.
        $dataRowsString = rtrim($dataRowsString, ',');
        // Again, make sure new line is at end of data row.
        $dataRowsString = $dataRowsString . "\r\n";
      }
      $completedFile = $headerString . $dataRowsString;
      fwrite($fp, $completedFile);
      fclose($fp);
    }
  }

  /**
   * Preprocessing to check existing record updates limited to user's records.
   */
  private function checkRecordIdsOwnedByUser($fileName, array $config) {
    $db = new Database();
    $columnInfo = $this->getColumnInfoByProperty($config['columns'], 'warehouseField', 'occurrence:id');
    $websiteId = (int) $config['global-values']['website_id'];
    $errorsJson = pg_escape_literal($db->getLink(), json_encode([
      $columnInfo['columnLabel'] => 'You do not have permission to update this record or the record referred to does not exist.',
    ]));
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $tempDbField = pg_escape_identifier($db->getLink(), $columnInfo['tempDbField']);
    // @todo For tables other than occurrences, method of accessing website ID differs.
    $sql = <<<SQL
UPDATE import_temp.$dbIdentifiers[tempTableName] u
SET errors = COALESCE(u.errors, '{}'::jsonb) || $errorsJson::jsonb
FROM import_temp.$dbIdentifiers[tempTableName] t
LEFT JOIN $dbIdentifiers[importDestTable] exist
  ON exist.id=t.$tempDbField::integer
  AND exist.created_by_id=$this->auth_user_id
  AND exist.website_id=$websiteId
WHERE exist.id IS NULL
AND t.$tempDbField ~ '^\d+$'
AND t._row_id=u._row_id;
SQL;
    $updated = $db->query($sql)->count();
    if ($updated > 0) {
      return ['error' => 'The import cannot proceed as you do not have permission to update some of the records or the records referred to do not exist.'];
    }
    $sql = <<<SQL
      SELECT count(DISTINCT $tempDbField) as distinct_count, count(*) as total_count
      FROM import_temp.$dbIdentifiers[tempTableName]
      WHERE $tempDbField<>''
    SQL;
    $counts = $db->query($sql)->current();
    if ($counts->distinct_count !== $counts->total_count) {
      return ['error' => 'The import file appears to refer to the same existing record more than once.'];
    }
    return [
      'message' => [
        "{1} existing {2} found",
        $counts->distinct_count,
        inflector::plural($config['entity']),
      ],
    ];
  }

  /**
   * Preprocessing to map a provided external key to a record ID.
   *
   * Only maps to records from the same website and created by the same user.
   */
  private function mapExternalKeyToExistingRecords($fileName, array $config) {
    $db = new Database();
    $columnInfo = $this->getColumnInfoByProperty($config['columns'], 'warehouseField', 'occurrence:external_key');
    $websiteId = (int) $config['global-values']['website_id'];
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $tempDbField = pg_escape_identifier($db->getLink(), $columnInfo['tempDbField']);
    // @todo For tables other than occurrences, method of accessing website ID
    // differs.
    $sql = <<<SQL
      ALTER TABLE import_temp.$dbIdentifiers[tempTableName]
      ADD COLUMN IF NOT EXISTS $dbIdentifiers[tempDbFkIdField] integer;

      UPDATE import_temp.$dbIdentifiers[tempTableName] u
      SET $dbIdentifiers[tempDbFkIdField]=exist.id
      FROM $dbIdentifiers[importDestTable] exist
      WHERE exist.external_key::text=u.$tempDbField
        AND exist.created_by_id=$this->auth_user_id
        AND exist.website_id=$websiteId
        AND exist.deleted=false;
    SQL;
    $db->query($sql);
    // Remember the mapping we just created.
    $config['systemAddedColumns'][ucfirst($config['entity']) . ' ID'] = [
      'tempDbField' => "_$config[entity]_id",
      'warehouseField' => "$config[entity]:id",
      'skipIfEmpty' => TRUE,
    ];
    $this->saveConfig($fileName, $config);
    $sql = <<<SQL
      SELECT count(DISTINCT $dbIdentifiers[tempDbFkIdField]) as distinct_count, count(*) as total_count
      FROM import_temp.$dbIdentifiers[tempTableName]
      WHERE $dbIdentifiers[tempDbFkIdField] IS NOT NULL
    SQL;
    $counts = $db->query($sql)->current();
    if ($counts->distinct_count !== $counts->total_count) {
      return ['error' => 'The import file appears to refer to the same existing record more than once.'];
    }
    return [
      'message' => [
        "{1} existing {2} found",
        $counts->distinct_count,
        inflector::plural($config['entity']),
      ],
    ];
  }

  /**
   * Preprocessing to capture the original parent IDs for existing records.
   *
   * E.g. when updating existing occurrences, find the previous parent sample
   * IDs for each occurrence.
   */
  private function findOriginalParentEntityIds($fileName, array $config) {
    $db = new Database();
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $sql = <<<SQL
      ALTER TABLE import_temp.$dbIdentifiers[tempTableName]
      ADD COLUMN IF NOT EXISTS $dbIdentifiers[tempDbParentFkIdField] integer;

      UPDATE import_temp.$dbIdentifiers[tempTableName] u
      SET $dbIdentifiers[tempDbParentFkIdField]=exist.$dbIdentifiers[parentEntityFkIdFieldInDestTable]
      FROM $dbIdentifiers[importDestTable] exist
      WHERE u.$dbIdentifiers[pkFieldInTempTable] ~ '^\d+$'
      AND exist.id=u.$dbIdentifiers[pkFieldInTempTable]::integer
      AND exist.deleted=false;
    SQL;
    $db->query($sql);
    $updated = $db->query(<<<SQL
      SELECT count(DISTINCT $dbIdentifiers[tempDbParentFkIdField])
      FROM import_temp.$dbIdentifiers[tempTableName]
      WHERE $dbIdentifiers[tempDbParentFkIdField] IS NOT NULL
    SQL)->current()->count;
    // Remember the mapping we just created.
    $config['systemAddedColumns'][ucfirst($config['parentEntity']) . ' ID'] = [
      'tempDbField' => "_$config[parentEntity]_id",
      'warehouseField' => "$config[parentEntity]:id",
      'skipIfEmpty' => TRUE,
    ];
    $this->saveConfig($fileName, $config);
    return [
      'message' => [
        "{1} existing {2} found",
        $updated,
        inflector::plural($config['parentEntity']),
      ],
    ];
  }

  /**
   * Preprocessing to clear parent IDs when not all children present.
   *
   * If an existing record's parent ID has been identified (e.g. an
   * occurrence's sample ID), then we will only use the existing parent record
   * if all the existing children of that parent record are included in the
   * import. So, if importing a record from an existing sample where the sample
   * has other records that are not in the import, the existing records will be
   * relocated to a new sample so the other records remain unaffected.
   */
  private function clearParentEntityIdsIfNotAllChildrenPresent($fileName, array $config) {
    $db = new Database();
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $sql = <<<SQL
      UPDATE import_temp.$dbIdentifiers[tempTableName] u
      SET $dbIdentifiers[tempDbParentFkIdField]=null
      FROM $dbIdentifiers[importDestParentTable] parent
      JOIN import_temp.$dbIdentifiers[tempTableName] t ON t.$dbIdentifiers[tempDbParentFkIdField]=parent.id
      JOIN $dbIdentifiers[importDestTable] allchildren
        ON allchildren.$dbIdentifiers[parentEntityFkIdFieldInDestTable]=parent.id
        AND allchildren.deleted=false AND allchildren.deleted=false
      LEFT JOIN import_temp.$dbIdentifiers[tempTableName] exist
        ON exist.$dbIdentifiers[pkFieldInTempTable] ~ '^\d+$'
        AND COALESCE(exist.$dbIdentifiers[pkFieldInTempTable], '0')::integer=allchildren.id
      WHERE exist._row_id IS NULL
      AND parent.id=u.$dbIdentifiers[tempDbParentFkIdField]
      AND parent.deleted=false;
    SQL;
    $db->query($sql);
    return [];
  }

  /**
   * Preprocessing to clear parent IDs when data now vary.
   *
   * If several existing records (e.g. occurrences) share the same parent (e.g.
   * sample) before an import, but the import now contains different parent
   * data, then the parent (sample) ID is cleared so that there is no confusion
   * and new parents get created.
   */
  private function clearNewParentEntityIdsIfNowMultipleSamples($fileName, array $config) {
    $db = new Database();
    $parentEntityColumns = $this->findEntityColumns($config['parentEntity'], $config);
    $parentColNames = [];
    foreach ($parentEntityColumns as $info) {
      $parentColNames[] = pg_escape_identifier($db->getLink(), $info['tempDbField']);
    }
    $parentColsList = implode(" || '||' || ", $parentColNames);
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $sql = <<<SQL
    SELECT t.$dbIdentifiers[tempDbParentFkIdField], COUNT(DISTINCT $parentColsList)
    INTO TEMPORARY to_clear
    FROM import_temp.$dbIdentifiers[tempTableName] t
    GROUP BY t.$dbIdentifiers[tempDbParentFkIdField]
    HAVING COUNT(DISTINCT $parentColsList)>1;

    UPDATE import_temp.$dbIdentifiers[tempTableName] u
    SET $dbIdentifiers[tempDbParentFkIdField]=null
    FROM to_clear c
    WHERE c.$dbIdentifiers[tempDbParentFkIdField]=u.$dbIdentifiers[tempDbParentFkIdField];
SQL;
    // @todo Would be nice if these queries reported back the changes they were making.
    $db->query($sql);
    return [];
  }

  /**
   * Controller action that saves config about an import to a temporary file.
   *
   * Implements the services/import/save_config endpoint The config values
   * to save into the file should be in the $_POST data with field names that
   * match the config value to update.
   */
  public function save_config() {
    header("Content-Type: application/json");
    $this->authenticate('write');
    $fileName = $_POST['data-file'];
    $config = $this->getConfig($fileName);
    foreach ($_POST as $key => $value) {
      if ($key !== 'data-file') {
        $config[$key] = json_decode($value);
      }
    }
    $this->saveConfig($fileName, $config);
    echo json_encode([
      'status' => 'ok',
    ]);
  }

  /**
   * Controller action that retrieves the config file for an import.
   */
  public function get_config() {
    header("Content-Type: application/json");
    $this->authenticate('read');
    $fileName = $_GET['data-file'];
    echo json_encode($this->getConfig($fileName));
  }

  /**
   * Controller action that imports the next chunk of records.
   *
   * Imports one batch at a time. Can be called repeatedly until the response
   * status is "done".
   */
  public function import_chunk() {
    header("Content-Type: application/json");
    try {
      $this->authenticate('write');
      ORM::$authorisedWebsiteId = $this->website_id;
      if ($this->in_warehouse) {
        // Refresh session in case on the same page for a while.
        Session::instance();
      }
      // Don't process cache tables immediately to improve performance.
      cache_builder::$delayCacheUpdates = TRUE;
      $fileName = $_POST['data-file'];
      if (!file_exists(DOCROOT . "import/$fileName")) {
        throw new exception('Parameter data-file refers to a missing file');
      }
      $config = $this->getConfig($fileName);
      $isPrecheck = !empty($_POST['precheck']);
      // If request to start again sent, go from beginning.
      if (!empty($_POST['restart'])) {
        $config['rowsProcessed'] = 0;
        $config['parentEntityRowsProcessed'] = 0;
        $config['errorsCount'] = 0;
        $this->saveConfig($fileName, $config);
      }
      $db = new Database();
      if (!empty($_POST['save-import-record'])) {
        $this->saveImportRecord($config, json_decode($_POST['save-import-record']));
      }
      if (!empty($_POST['save-import-template'])) {
        $this->saveImportTemplate($config, json_decode($_POST['save-import-template']));
      }
      // @todo Correctly set parent entity for other entities.
      // @todo Handling for entities without parent entity.
      $parentEntityColumns = $this->findEntityColumns($config['parentEntity'], $config);
      $childEntityColumns = $this->findEntityColumns($config['entity'], $config);
      $parentEntityDataRows = $this->fetchParentEntityData($db, $parentEntityColumns, $config);

      // Check for compound field handling which require presence of a set of
      // fields (e.g. build date from day, month, year).
      $parentEntityCompoundFields = $this->getCompoundFieldsToProcessForEntity($config['parentEntity'], $parentEntityColumns);
      $childEntityCompoundFields = $this->getCompoundFieldsToProcessForEntity($config['entity'], $childEntityColumns);
      kohana::log('debug', var_export($parentEntityDataRows, TRUE));
      foreach ($parentEntityDataRows as $parentEntityDataRow) {
        // @todo Updating existing data.
        // @todo tracking of records that are done in the import table so can restart.
        $parent = ORM::factory($config['parentEntity']);
        $submission = [];
        $this->copyFieldsFromRowToSubmission($parentEntityDataRow, $parentEntityColumns, $config, $submission, $parentEntityCompoundFields);
        $this->applyGlobalValues($config, $config['parentEntity'], $parent->attrs_field_prefix ?? NULL, $submission);
        $identifiers = [
          'website_id' => $config['global-values']['website_id'],
          'survey_id' => $submission['survey_id'],
        ];
        if ($config['parentEntitySupportsImportGuid']) {
          $submission["$config[parentEntity]:import_guid"] = $config['importGuid'];
        }
        $parent->set_submission_data($submission);
        if ($isPrecheck) {
          $parentErrors = $parent->precheck($identifiers);
          // A fake ID to allow check on children.
          $parent->id = 1;
        }
        else {
          $parent->submit();
          $parentErrors = $parent->getAllErrors();
        }
        $childEntityDataRows = $this->fetchChildEntityData($db, $parentEntityColumns, $config, $parentEntityDataRow);
        if (count($parentErrors) > 0) {
          $config['errorsCount'] += count($childEntityDataRows);
          if (!$isPrecheck) {
            // As we won't individually process the occurrences due to error in
            // the sample, add them to the count.
            $config['rowsProcessed'] += count($childEntityDataRows);
          }
          $keyFields = $this->getDestFieldsForColumns($parentEntityColumns);
          $this->saveErrorsToRows($db, $parentEntityDataRow, $keyFields, $parentErrors, $config);
        }
        // If sample saved OK, or we are just prechecking, process the matching
        // occurrences.
        if (count($parentErrors) === 0 || $isPrecheck) {
          foreach ($childEntityDataRows as $childEntityDataRow) {
            $child = ORM::factory($config['entity']);
            $submission = [
              'sample_id' => $parent->id,
            ];
            $this->applyGlobalValues($config, $config['entity'], $child->attrs_field_prefix ?? NULL, $submission);
            $this->copyFieldsFromRowToSubmission($childEntityDataRow, $childEntityColumns, $config, $submission, $childEntityCompoundFields);
            if ($config['entitySupportsImportGuid']) {
              $submission["$config[entity]:import_guid"] = $config['importGuid'];
            }
            $child->set_submission_data($submission);
            if ($isPrecheck) {
              $errors = $child->precheck($identifiers);
            }
            else {
              $child->submit();
              $errors = $child->getAllErrors();
            }
            if (count($errors) > 0) {
              // Register additional error row, but only if not already
              // registered due to error in parent.
              if (count($parentErrors) === 0) {
                $config['errorsCount']++;
              }
              $this->saveErrorsToRows($db, $childEntityDataRow, ['_row_id'], $errors, $config);
            }
            elseif (!$isPrecheck) {
              if (!empty($submission['occurrence:id'])) {
                $config['rowsUpdated']++;
              }
              else {
                $config['rowsInserted']++;
              }
            }
            $config['rowsProcessed']++;
          }
        }
        $config['parentEntityRowsProcessed']++;
      }

      $progress = 100 * $config['rowsProcessed'] / $config['totalRows'];
      if ($progress === 100 && $config['errorsCount'] === 0 && !$isPrecheck) {
        $this->tidyUpAfterImport($db, $config);
      }
      else {
        $this->saveConfig($fileName, $config);
      }
      if (!$isPrecheck) {
        $this->saveImportRecord($config);
      }
      echo json_encode([
        'status' => $config['rowsProcessed'] >= $config['totalRows'] ? 'done' : ($isPrecheck ? 'checking' : 'importing'),
        'progress' => 100 * $config['rowsProcessed'] / $config['totalRows'],
        'rowsProcessed' => $config['rowsProcessed'],
        'totalRows' => $config['totalRows'],
        'errorsCount' => $config['errorsCount'],
      ]);
    }
    catch (Exception $e) {
      if ($e instanceof RequestAbort) {
        // Abort request implies response already sent.
        return;
      }
      // Save config as it tells us how far we got, making diagnosis and
      // continuation easier.
      if (isset($config)) {
        $this->saveConfig($fileName, $config);
        $this->saveImportRecord($config);
      }
      error_logger::log_error('Error in import_chunk', $e);
      kohana::log('debug', 'Error in import_chunk: ' . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Check for model compound fields which need to be applied.
   *
   * E.g. a sample date field is a compound field which can be constructed from
   * day, month and year values in an import. If the day, month and year are
   * mapped then capture the information about the compound field so it can be
   * used later.
   *
   * @param string $entity
   *   Database entity name.
   * @param array $mappedColumns
   *   List of mapped column information.
   *
   * @return array
   *   List of compound field definitions keyed by the field name.
   */
  private function getCompoundFieldsToProcessForEntity($entity, $mappedColumns) {
    $compoundFields = [];
    $model = ORM::factory($entity);
    if (isset($model->compoundImportFieldProcessingDefn)) {
      foreach ($model->compoundImportFieldProcessingDefn as $def) {
        $foundMappedColumns = [];
        foreach ($mappedColumns as $mappedCol) {
          if (in_array($mappedCol['warehouseField'], $def['columns'])) {
            $foundMappedColumns[$mappedCol['warehouseField']] = TRUE;
          }
        }
        if (count($foundMappedColumns) === count($def['columns'])) {
          // Include this compound field as it's required columns are all mapped.
          $compoundFields[] = $def;
        }
      }
    }
    return $compoundFields;
  }

  /**
   * Adds end-point for retrieving errors file.
   *
   * Sends CSV content for rows with errors to the output.
   */
  public function get_errors_file() {
    if (empty($_GET['data-file'])) {
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'msg' => 'A query parameter called data-file must be passed to get_errors_file end-point.',
      ]);
      return;
    }
    header("Content-Description: File Transfer");
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"import-errors.csv\"");
    $fileName = $_GET['data-file'];
    $config = $this->getConfig($fileName);
    $db = new Database();
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $fields = array_merge(array_values($this->getColumnTempDbFieldMappings($db, $config['columns'])), [
      // Excel row starts on 2, our _row_id is a db sequence so starts on 1.
      '_row_id+1',
      'errors',
    ]);
    $fieldSql = implode(', ', $fields);
    $query = <<<SQL
SELECT $fieldSql
FROM import_temp.$dbIdentifiers[tempTableName]
WHERE errors IS NOT NULL
ORDER BY _row_id;
SQL;
    $results = $db->query($query)->result(FALSE);
    $out = fopen('php://output', 'w');
    fputcsv($out, array_merge(array_keys($config['columns']), [
      '[Row no.]',
      '[Errors]',
    ]));
    // Find any date fields than need mapping back from Excel date integers to
    // date strings.
    $dateCols = [];
    if ($config['isExcel']) {
      $idx = 0;
      foreach ($config['columns'] as $colInfo) {
        if (isset($colInfo['warehouseField']) && preg_match('/date(_start|_end)?$/', $colInfo['warehouseField'])) {
          $dateCols[] = $colInfo['tempDbField'];
        }
        $idx++;
      }
    }
    foreach ($results as $row) {
      // Map any date int values back to strings.
      if ($config['isExcel']) {
        foreach ($dateCols as $dateCol) {
          if (preg_match('/^\d+$/', $row[$dateCol])) {
            $date = ImportDate::excelToDateTimeObject($row[$dateCol]);
            $row[$dateCol] = $date->format('d/m/Y');
          }
        }
      }
      fputcsv($out, $row);
    }
    fclose($out);
  }

  /**
   * Finds the label used in the import file that's linked to a temp db field.
   *
   * @param array $columns
   *   Columns config to search.
   * @param string $fieldName
   *   Temp db fieldname to search for.
   *
   * @return string
   *   Column label from the import file.
   */
  private function getColumnLabelForTempDbField(array $columns, $fieldName) {
    foreach ($columns as $columnLabel => $info) {
      if ($info['tempDbField'] === $fieldName || (isset($info['tempDbField']) && $info['tempDbField'] === $fieldName)) {
        return $columnLabel;
      }
    }
    throw new NotFoundException("Field $fieldName not found");
  }

  /**
   * Finds the column info for a column identified by any property value.
   *
   * E.g. find the column info by tempDbField, or warehouseField.
   *
   * @param array $columns
   *   Columns config to search.
   * @param string $property
   *   Property name to search in (tempDbField or warehouseField).
   * @param string $value
   *   Value to search for.
   *
   * @return array
   *   Column info array.
   */
  private function getColumnInfoByProperty(array $columns, $property, $value) {
    foreach ($columns as $columnLabel => $info) {
      if (isset($info[$property]) && $info[$property] === $value) {
        return array_merge($info, ['columnLabel' => $columnLabel]);
      }
    }
    throw new NotFoundException("Property value $property=$value not found");
  }

  /**
   * Find the list of destination fields for a list of column definitions.
   *
   * @param array $columns
   *   List of column definitions.
   *
   * @return array
   *   List of destination field names.
   */
  private function getDestFieldsForColumns(array $columns) {
    $fields = [];
    foreach ($columns as $info) {
      $fields[] = empty($info['isFkField']) ? $info['tempDbField'] : "$info[tempDbField]_id";
    }
    return $fields;
  }

  /**
   * Retrieves column label to temp DB field mappings.
   *
   * Converts the columns config to an associative array of column names and
   * temp db table fields.
   *
   * @param Database $db
   *   Database connection.
   * @param array $columns
   *   Columns config.
   *
   * @return array
   *   Associative array.
   */
  private function getColumnTempDbFieldMappings($db, array $columns) {
    $r = [];
    foreach ($columns as $columnLabel => $info) {
      $r[$columnLabel] = pg_escape_identifier($db->getLink(), $info['tempDbField']);
    }
    return $r;
  }

  /**
   * Ensures all mappable fields have a readable caption.
   *
   * @param array $fields
   *   Field name and available captions as associative array. Will be updated
   *   so all fields have captions.
   */
  private function provideFriendlyFieldCaptions(array &$fields) {
    // Some fields can have pre-set captions.
    $friendlyCaptions = [
      'occurrence:fk_taxa_taxon_list' => 'Species or taxon name',
      'sample:entered_sref' => 'Grid reference or other spatial reference',
      'sample:entered_sref_system' => 'Grid or spatial reference type',
      'sample:fk_group' => 'Group or activity title (lookup in Groups table)',
      'sample:fk_licence' => 'Licence title (lookup in Licences table)',
      'sample:fk_licence:code' => 'Licence code, e.g. "CC0" or "CC BY-NC" (lookup in Licences table)',
    ];
    foreach ($friendlyCaptions as $field => $caption) {
      if (isset($field[$field])) {
        $fields[$field] = $caption;
      }
    }
    // Fill in autogenerated captions for any that remain.
    foreach ($fields as $field => &$caption) {
      if (empty($caption)) {
        $fieldParts = explode(':', $field);
        if (substr($fieldParts[1], 0, 3) === 'fk_') {
          $caption = ucfirst(str_replace('_', ' ', substr($fieldParts[1], 3))) . ' (lookup in database)';
        }
        else {
          $caption = ucfirst(str_replace('_', ' ', $fieldParts[1]));
        }
      }
    }
  }

  /**
   * Saves metadata about the import to the imports table.
   *
   * @param array $config
   *   Import metadata configuration object.
   * @param object $importInfo
   *   Additional field values to save (e.g. description).
   */
  private function saveImportRecord(array $config, $importInfo = NULL) {
    $import = ORM::factory('import', ['import_guid' => $config['importGuid']]);
    $import->set_metadata();
    $import->entity = $config['entity'];
    $import->website_id = $config['global-values']['website_id'];
    $import->inserted = $config['rowsInserted'];
    $import->updated = $config['rowsUpdated'];
    $import->errors = $config['errorsCount'];
    $import->reversible = TRUE;
    $import->mappings = json_encode($config['columns']);
    $import->global_values = json_encode($config['global-values']);
    if ($importInfo) {
      if (!empty($importInfo->description)) {
        // This will only get specified on initial save.
        $import->description = $importInfo->description;
      }
      if (!empty($importInfo->training)) {
        $import->training = 't';
      }

    }
    $import->import_guid = $config['importGuid'];
    $import->save();
    $errors = $import->getAllErrors();
    if (count($errors) > 0) {
      // This should never happen.
      throw new Exception(json_encode($errors, TRUE));
    }
  }

  /**
   * Saves an import configuration template.
   *
   * @param array $config
   *   Import configuration.
   * @param object $importTemplateInfo
   *   Info about the template to save, including title and
   *   forceTemplateOverwrite option.
   */
  private function saveImportTemplate(array $config, $importTemplateInfo) {
    if (empty(trim($importTemplateInfo->title))) {
      return;
    }
    $template = ORM::factory('import_template')->find([
      'title' => $importTemplateInfo->title,
      'created_by_id' => $this->auth_user_id,
    ]);

    if ($template->id && !$importTemplateInfo->forceTemplateOverwrite) {
      // Throw duplicate error, unless duplicates overwrite flag set.
      http_response_code(409);
      echo json_encode([
        'status' => 'conflict',
        'msg' => 'An import template with that title already exists',
      ]);
      throw new RequestAbort();
    }

    $template->set_metadata();
    $template->title = $importTemplateInfo->title;
    $template->entity = $config['entity'];
    $template->website_id = $config['global-values']['website_id'];
    $template->mappings = json_encode($config['columns']);
    $template->global_values = json_encode($config['global-values']);
    $template->save();
    $errors = $template->getAllErrors();
    if (count($errors) > 0) {
      // This should never happen.
      throw new Exception(json_encode($errors, TRUE));
    }
  }

  /**
   * Adds error data to rows identified by a set of key field values.
   *
   * @param object $db
   *   Database connection.
   * @param object $rowData
   *   Current import row's data.
   * @param array $keyFields
   *   List of field names that should be looked up against.
   * @param array $errors
   *   List of errors to attach to the rows.
   * @param array $config
   *   Import configuration metadata object.
   */
  private function saveErrorsToRows($db, $rowData, array $keyFields, array $errors, array $config) {
    $whereList = [];
    foreach ($keyFields as $field) {
      $fieldEsc = pg_escape_identifier($db->getLink(), $field);
      $value = pg_escape_literal($db->getLink(), $rowData->$field ?? '');
      $whereList[] = "$fieldEsc=$value";
    }
    $wheres = implode(' AND ', $whereList);
    $errorsList = [];
    foreach ($errors as $fieldName => $error) {
      list($entity, $field) = explode(':', $fieldName);
      $errorI18n = kohana::lang("form_error_messages.$field.$error");
      $errorStr = $errorI18n === "form_error_messages.$field.$error" ? $error : $errorI18n;
      // A date error might be reported against a vague date component
      // field, but can map back to the calculated date field if separate
      // date fields not being used.
      $field = preg_replace('/date_(start|end|type)$/', 'date', $field);
      try {
        $columnInfo = $this->getColumnInfoByProperty($config['columns'], 'warehouseField', "$entity:$field");
        $errorsList[$columnInfo['columnLabel']] = $errorStr;
      }
      catch (NotFoundException $e) {
        // Shouldn't happen, but means we need better logic from mapping from
        // the errored field to the mapped field name.
        // If geom field causes error, no need to notify if the entered sref
        // has an error.
        if ($field !== 'geom' || !isset($errors['sample:entered_sref'])) {
          $errorsList[$field] = $errorStr;
        }
      }
    }
    $errorsJson = pg_escape_literal($db->getLink(), json_encode($errorsList));
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $sql = <<<SQL
UPDATE import_temp.$dbIdentifiers[tempTableName]
SET errors = COALESCE(errors, '{}'::jsonb) || $errorsJson::jsonb
WHERE $wheres;
SQL;
    $db->query($sql);
  }

  /**
   * Finds mapped database import columns that relate to an entity.
   *
   * @param string $entity
   *   Entity name, e.g. sample, occurrence.
   * @param array $config
   *   Import metadata configuration object.
   *
   * @return array
   *   List of column definitions.
   */
  private function findEntityColumns($entity, array $config) {
    $columns = [];
    $attrPrefix = $this->getAttrPrefix($entity);
    $allColumns = array_merge($config['columns'], $config['systemAddedColumns']);
    foreach ($allColumns as $info) {
      if (isset($info['warehouseField'])) {
        $destFieldParts = explode(':', $info['warehouseField']);
        // If a field targeting the destination entity, or an attribute table
        // linked to the entity, or a media table linked to the table, then
        // include the column.
        if ($destFieldParts[0] === $entity
            || ($attrPrefix && $destFieldParts[0] === $attrPrefix)
            || $destFieldParts[0] === inflector::singular($entity) . '_medium') {
          $columns[] = $info;
        }
      }
    }
    return $columns;
  }

  /**
   * Retreive the prefix for an entity's attribute field names, or null.
   *
   * @param string $entity
   *   Entity name, e.g. occurrence.
   *
   * @return string
   *   Attribute prefix, e.g. smpAttr or occAttr.
   */
  private function getAttrPrefix($entity) {
    $entityPrefixes = [
      'occurrence' => 'occAttr',
      'location' => 'locAttr',
      'sample' => 'smpAttr',
      'survey' => 'srvAttr',
      'taxa_taxon_list' => 'ttlAttr',
      'termlists_term' => 'trmAttr',
      'person' => 'psnAttr',
    ];
    return $entityPrefixes[$entity] ?? NULL;
  }

  /**
   * Retreive the entity name from an attribute field prefix.
   *
   * @param string $prefix
   *   Attribute field prefix, e.g. smpAttr or occAttr.
   *
   * @return string
   *   Matching entity name.
   */
  private function getEntityFromAttrPrefix($prefix) {
    $entitiesByPrefix = [
      'occAttr' => 'occurrence',
      'locAttr' => 'location',
      'smpAttr' => 'sample',
      'srvAttr' => 'survey',
      'ttlAttr' => 'taxa_taxon_list',
      'trmAttr' => 'termlists_term',
      'psnAttr' => 'person',
    ];
    return $entitiesByPrefix[$prefix] ?? NULL;
  }

  /**
   * When importing to a parent entity, need distinct data values.
   *
   * One row will be created per distinct set of field values, so this returns
   * a db result with the required rows.
   *
   * @param object $db
   *   Database connection.
   * @param array $columns
   *   List of column definitions to look for uniqueness in the values of.
   * @param array $config
   *   Import metadata configuration object.
   */
  private function fetchParentEntityData($db, array $columns, array $config) {
    $fields = $this->getDestFieldsForColumns($columns);
    $fields = array_map(function ($s) use ($db) {
      return pg_escape_identifier($db->getLink(), $s);
    }, $fields);
    $fieldsAsCsv = implode(', ', $fields);
    // Batch row limit div by arbitrary 10 to allow for multiple children per
    // parent.
    $batchRowLimit = BATCH_ROW_LIMIT / 10;
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $sql = <<<SQL
SELECT DISTINCT $fieldsAsCsv
FROM import_temp.$dbIdentifiers[tempTableName]
ORDER BY $fieldsAsCsv
LIMIT $batchRowLimit
OFFSET ?;
SQL;
    return $db->query($sql, [$config['parentEntityRowsProcessed']])->result();
  }

  /**
   * Copies a set of fields from a data row into a submission array.
   *
   * @param object $dataRow
   *   Data read from the import file.
   * @param array $columns
   *   List of column definitions to copy the field value for.
   * @param array $config
   *   Import metadata configuration object.
   * @param array $submission
   *   Submission data array that will be updated with the copied values.
   */
  private function copyFieldsFromRowToSubmission($dataRow, array $columns, array $config, array &$submission, array $compoundFields) {
    $skipColumns = [];
    $skippedValues = [];
    foreach ($compoundFields as $def) {
      $skipColumns = $skipColumns + $def['columns'];
    }
    foreach ($columns as $info) {
      $srcFieldName = $info['tempDbField'];
      $destFieldName = $info['warehouseField'];
      // Fk fields need to alter the fake field name to a real one and use the
      // mapped source field.
      if (!empty($info['isFkField'])) {
        $srcFieldName .= '_id';
        $destFieldParts = explode(':', $destFieldName);
        $destFieldName = "$destFieldParts[0]:" .
            // Fieldname without fk_ prefix.
            substr($destFieldParts[1], 3) .
            // Append _id if not a custom attribute lookup.
            (preg_match('/^[a-z]{3}Attr$/', $destFieldParts[0]) ? '' : '_id');
      }
      if (in_array($info['warehouseField'], $skipColumns)) {
        $skippedValues[$info['warehouseField']] = $dataRow->$srcFieldName;
        continue;
      }
      if (empty($dataRow->$srcFieldName)) {
        if (empty($config['global-values'][$destFieldName])) {
          // An empty field shouldn't overwrite a global value.
          continue;
        }
        elseif (!empty($info['skipIfEmpty'])) {
          // Some fields (e.g. existing record ID mappings) should be skipped
          // if empty.
          continue;
        }
      }
      // @todo Look for date fields more intelligently.
      if ($config['isExcel'] && preg_match('/date(_start|_end)?$/', $destFieldName) && preg_match('/^\d+$/', $dataRow->$srcFieldName)) {
        // Date fields are integers when read from Excel.
        $date = ImportDate::excelToDateTimeObject($dataRow->$srcFieldName);
        $submission[$destFieldName] = $date->format('d/m/Y');
      }
      else {
        $submission[$destFieldName] = $dataRow->$srcFieldName;
      }
    }
    foreach ($compoundFields as $def) {
      $submission[$def['destination']] = vsprintf(
        $def['template'],
        array_map(function ($column) use ($skippedValues) {
          return $skippedValues[$column];
        },
        $def['columns'])
      );
    }
  }

  /**
   * Applies global values to a submission array.
   *
   * These are the values provided by the user that apply to every row in the
   * import.
   *
   * @param array $config
   *   Import metadata configuration object.
   * @param string $entity
   *   Name of the entity to copy over values for.
   * @param string $attrPrefix
   *   Attribute fieldname prefix, e.g. smp or occ. Leave empty if not an
   *   attribute table.
   * @param array $submission
   *   Submission data array that will be updated with the global values.
   */
  private function applyGlobalValues(array $config, $entity, $attrPrefix, array &$submission) {
    foreach ($config['global-values'] as $field => $value) {
      if (in_array($field, ['survey_id', 'website_id'])
          || substr($field, 0, strlen($entity) + 1) === "$entity:"
          || ($attrPrefix && substr($field, 0, strlen($attrPrefix) + 1) === "{$attrPrefix}:")) {
        $submission[$field] = $value;
      }
    }
  }

  /**
   * For a parent entity record (e.g. sample), find the child data rows.
   *
   * @param object $db
   *   Database connection.
   * @param array $columns
   *   List of parent columns that will be filtered to find the child data rows.
   * @param array $config
   *   Import metadata configuration object.
   * @param object $parentEntityDataRow
   *   Data row holding the parent record values.
   *
   * @return object
   *   Database result containing child rows.
   */
  private function fetchChildEntityData($db, array $columns, array $config, $parentEntityDataRow) {
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $fields = $this->getDestFieldsForColumns($columns);
    // Build a filter to extract rows for this parent entity.
    $wheresList = [];
    foreach ($fields as $field) {
      $fieldEsc = pg_escape_identifier($db->getLink(), $field);
      $value = pg_escape_literal($db->getLink(), $parentEntityDataRow->$field ?? '');
      $wheresList[] = "COALESCE($fieldEsc::text, '')=$value";
    }
    $wheres = implode("\nAND ", $wheresList);
    // Now retrieve the sub-entity rows.
    $sql = <<<SQL
SELECT *
FROM import_temp.$dbIdentifiers[tempTableName]
WHERE $wheres
ORDER BY _row_id;
SQL;
    return $db->query($sql)->result();
  }

  /**
   * Finds the next mapped field that requires lookup data matching.
   *
   * @param object $db
   *   Database connection.
   * @param array $config
   *   Import metadata configuration. Will be updated to include any found
   *   lookup fields.
   */
  private function findNextLookupField($db, array &$config) {
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    if (!isset($config['lookupFieldsMatched'])) {
      $config['lookupFieldsMatched'] = [];
    }
    // Default response.
    $r = [
      'status' => 'ok',
      'msgKey' => 'findLookupFieldsDone',
    ];
    foreach ($config['columns'] as $columnLabel => &$info) {
      if (isset($info['warehouseField'])) {
        $destFieldParts = explode(':', $info['warehouseField']);
        if (substr($destFieldParts[1], 0, 3) === 'fk_' && !in_array($info['warehouseField'], $config['lookupFieldsMatched'])) {
          $info['isFkField'] = TRUE;
          $config['lookupFieldsMatched'][] = $info['warehouseField'];
          // Add an ID field to the data table.
          $colNameEscaped = pg_escape_identifier($db->getLink(), "$info[tempDbField]_id");
          $sql = <<<SQL
ALTER TABLE import_temp.$dbIdentifiers[tempTableName]
ADD COLUMN IF NOT EXISTS $colNameEscaped integer;
SQL;
          $db->query($sql);
          // Query to fill in ID for all obvious matches.
          if (substr($destFieldParts[0], -4) === 'Attr' and strlen($destFieldParts[0]) === 7) {
            // Attribute lookup values. e.g. occAttr:n.
            $unmatchedInfo = $this->autofillLookupAttrIds($db, $config, $info);
          }
          elseif ($destFieldParts[0] === 'occurrence' && $destFieldParts[1] === 'fk_taxa_taxon_list') {
            $unmatchedInfo = $this->autofillOccurrenceTaxonIds($db, $config, $info);
          }
          elseif ($destFieldParts[0] === 'sample' && $destFieldParts[1] === 'fk_location') {
            $unmatchedInfo = $this->autofillSampleLocationIds($db, $config, $info);
          }
          else {
            $unmatchedInfo = $this->autofillOtherFkIds($db, $config, $info);
          }
          // Respond with values that don't match plus list of matches, or a
          // success message.
          $r = [
            'status' => 'ok',
            'msgKey' => 'lookupFieldFound',
            'columnLabel' => $columnLabel,
            'sourceField' => $info['tempDbField'],
          ];
          if (isset($unmatchedInfo) && count($unmatchedInfo['values']) > 0) {
            $r['unmatchedInfo'] = $unmatchedInfo;
          }
          break;
        }
      }
    }
    return $r;
  }

  /**
   * Autofills the termlist_term ID foreign keys for lookup attr text values.
   *
   * @param object $db
   *   Database connection.
   * @param array $config
   *   Import configuration object.
   * @param array $info
   *   Column info data for the column being autofilled.
   *
   * @return array
   *   Matching info, including a list of match options, a list of unmatched
   *   values that require user input to fix, plus info about the attribute
   *   we are trying to match values for.
   */
  private function autofillLookupAttrIds($db, array $config, array $info) {
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $valueToMapColName = pg_escape_identifier($db->getLink(), $info['tempDbField']);
    $valueToMapIdColName = pg_escape_identifier($db->getLink(), $info['tempDbField'] . '_id');
    $destFieldParts = explode(':', $info['warehouseField']);
    $attrEntity = $this->getEntityFromAttrPrefix($destFieldParts[0]);
    $attrId = (int) str_replace('fk_', '', $destFieldParts[1]);
    $sql = <<<SQL
UPDATE import_temp.$dbIdentifiers[tempTableName] i
SET $valueToMapIdColName=t.id
FROM cache_termlists_terms t
JOIN {$attrEntity}_attributes a ON a.termlist_id=t.termlist_id
AND a.id=$attrId AND a.deleted=false
WHERE trim(lower(i.$valueToMapColName))=lower(t.term);
SQL;
    $db->query($sql);
    $sql = <<<SQL
SELECT DISTINCT trim(lower($valueToMapColName)) as value
FROM import_temp.$dbIdentifiers[tempTableName]
WHERE $valueToMapIdColName IS NULL
AND $valueToMapColName <> ''
ORDER BY trim(lower($valueToMapColName));
SQL;
    $values = [];
    $rows = $db->query($sql)->result();
    foreach ($rows as $row) {
      $values[] = $row->value;
    }
    // Find the available possible options from the termlist.
    $sql = <<<SQL
SELECT t.id, t.term
FROM cache_termlists_terms t
JOIN {$attrEntity}_attributes a ON a.termlist_id=t.termlist_id
AND a.id=$attrId AND a.deleted=false
ORDER BY t.sort_order, t.term
SQL;
    $matchOptions = [];
    $rows = $db->query($sql)->result();
    foreach ($rows as $row) {
      $matchOptions[$row->id] = $row->term;
    }
    return [
      'values' => $values,
      'matchOptions' => $matchOptions,
      'type' => 'customAttribute',
      'attrType' => $destFieldParts[0],
      'attrId' => $attrId,
    ];
  }

  /**
   * Autofills the taxa_taxon_list ID foreign keys for lookup taxon text values.
   *
   * @param object $db
   *   Database connection.
   * @param array $config
   *   Import configuration object.
   * @param array $info
   *   Column info data for the column being autofilled.
   *
   * @return array
   *   Array containing information about the result.
   */
  private function autofillOccurrenceTaxonIds($db, array $config, array $info) {
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $valueToMapColName = pg_escape_identifier($db->getLink(), $info['tempDbField']);
    $valueToMapIdColName = pg_escape_identifier($db->getLink(), "$info[tempDbField]_id");
    $valueToMapIdChoicesColName = pg_escape_identifier($db->getLink(), "$info[tempDbField]_id_choices");
    $destFieldParts = explode(':', $info['warehouseField']);
    $searchField = $destFieldParts[2] ?? 'taxon';
    // Skip matching using the species name epithet field as we'll combine it
    // with the genus when that is done.
    if ($searchField === 'specific') {
      return NULL;
    }
    $filtersList = [];
    $filterForTaxonSearchAPI = [];
    foreach ($config['global-values'] as $fieldDef => $value) {
      if (substr($fieldDef, 0, 36) === 'occurrence:fkFilter:taxa_taxon_list:') {
        $filterField = str_replace('occurrence:fkFilter:taxa_taxon_list:', '', $fieldDef);
        $fieldEsc = pg_escape_identifier($db->getLink(), $filterField);
        $escaped = pg_escape_literal($db->getLink(), $value);
        $filtersList[] = "AND cttl.$fieldEsc=$escaped";
        $filterForTaxonSearchAPI[$filterField] = $value;
      }
    }
    $filters = implode("\n", $filtersList);
    $matchingFieldSql = "i.{$valueToMapColName}";
    if ($searchField === 'genus') {
      // When matching genus, add the species name epithet paired field to the
      // SQL used for matching.
      foreach ($config['columns'] as $colInfo) {
        if (isset($colInfo['warehouseField']) && $colInfo['warehouseField'] = 'occurrence:fk_taxa_taxon_list:specific') {
          $tempDbField = pg_escape_identifier($db->getLink(), $colInfo['tempDbField']);
          $matchingFieldSql = "i.{$valueToMapColName} || ' ' || i.$tempDbField";
        }
      }
      // Match against the whole taxon name.
      $searchField = 'taxon';
    }
    $searchField = pg_escape_identifier($db->getLink(), $searchField);
    // Add a column to capture potential multiple matching taxa.
    $uniq = uniqid(TRUE);
    $sql = <<<SQL
      ALTER TABLE import_temp.$dbIdentifiers[tempTableName]
      ADD COLUMN IF NOT EXISTS {$valueToMapIdChoicesColName} json;

      -- Find possible taxon name matches. If a name is not accepted, but has an
      -- accepted alternative, it gets skipped.
      SELECT trim(lower($matchingFieldSql)) as taxon,
        ARRAY_AGG(DISTINCT cttl.id) AS choices,
        JSON_AGG(DISTINCT jsonb_build_object(
          'id', cttl.id,
          'taxon', cttl.taxon,
          'authority', cttl.authority,
          'language_iso', cttl.language_iso,
          'preferred_taxon', cttl.preferred_taxon,
          'preferred_authority', cttl.preferred_authority,
          'preferred_language_iso', cttl.preferred_language_iso,
          'default_common_name', cttl.default_common_name,
          'taxon_group', cttl.taxon_group,
          'taxon_rank', cttl.taxon_rank,
          'taxon_rank_sort_order', cttl.taxon_rank_sort_order
        )) AS choice_info
      INTO TEMPORARY species_matches_$uniq
      FROM import_temp.$config[tableName] i
      JOIN cache_taxa_taxon_lists cttl
        ON trim(lower($matchingFieldSql))=lower(cttl.$searchField) AND cttl.allow_data_entry=true
        $filters
      -- Drop if accepted name exists which also matches.
      LEFT JOIN cache_taxa_taxon_lists cttlaccepted
        ON trim(lower($matchingFieldSql))=lower(cttlaccepted.$searchField) AND cttlaccepted.id<>cttl.id
        AND cttlaccepted.allow_data_entry=true AND cttlaccepted.preferred=true
        AND cttlaccepted.taxon_meaning_id=cttl.taxon_meaning_id
        AND cttlaccepted.taxon_list_id=cttl.taxon_list_id
      WHERE cttlaccepted.id IS NULL
      GROUP BY $matchingFieldSql;

      UPDATE import_temp.$config[tableName] i
      SET {$valueToMapIdColName}=CASE ARRAY_LENGTH(sm.choices, 1) WHEN 1 THEN ARRAY_TO_STRING(sm.choices, '') ELSE NULL END::integer,
      {$valueToMapIdChoicesColName}=sm.choice_info
      FROM species_matches_$uniq sm
      WHERE trim(lower($matchingFieldSql))=sm.taxon;

      DROP TABLE species_matches_$uniq;
    SQL;
    $db->query($sql);
    $sql = <<<SQL
      SELECT DISTINCT {$valueToMapColName} as value, {$valueToMapIdChoicesColName}::text as choices
      FROM import_temp.$dbIdentifiers[tempTableName]
      WHERE {$valueToMapIdColName} IS NULL
      AND {$valueToMapColName}<>'';
    SQL;
    $rows = $db->query($sql)->result();
    $values = [];
    foreach ($rows as $row) {
      $values[$row->value] = $row->choices;
    }
    return [
      'values' => $values,
      'type' => 'taxon',
      'taxonFilters' => $filterForTaxonSearchAPI,
    ];
  }

  /**
   * Autofills the sample location ID foreign keys for lookup text values.
   *
   * @param object $db
   *   Database connection.
   * @param array $config
   *   Import configuration object.
   * @param array $info
   *   Column info data for the column being autofilled.
   *
   * @return array
   *   Array containing information about the result.
   */
  private function autofillSampleLocationIds($db, array $config, array $info) {
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $valueToMapColName = pg_escape_identifier($db->getLink(), $info['tempDbField']);
    $valueToMapIdColName = pg_escape_identifier($db->getLink(), "$info[tempDbField]_id");
    $valueToMapIdChoicesColName = pg_escape_identifier($db->getLink(), "$info[tempDbField]_id_choices");
    $destFieldParts = explode(':', $info['warehouseField']);
    $mappedLocationTableColName = pg_escape_identifier($db->getLink(), $destFieldParts[2]);
    $websiteId = (int) $config['global-values']['website_id'];
    $matchAll = [
      'l.deleted=false',
      "(lw.website_id=$websiteId OR l.public)",
    ];
    $extraJoins = [];
    $filterForLocationSearchAPI = [];
    foreach ($config['global-values'] as $fieldDef => $value) {
      if (substr($fieldDef, 0, 25) === 'sample:fkFilter:location:') {
        $filterField = str_replace('sample:fkFilter:location:', '', $fieldDef);
        $fieldEsc = pg_escape_identifier($db->getLink(), $filterField);
        $valueEsc = pg_escape_literal($db->getLink(), $value);
        $matchAll[] = "l.$fieldEsc=$valueEsc";
        $filterForLocationSearchAPI[$filterField] = $value;
      }
    }
    // If no filter specified, revert to the defaults.
    if (empty($filtersList)) {
      $matchAny = [
        "l.created_by_id=$this->auth_user_id",
      ];
      // Add locations for the recording group if importing into a group.
      if (!empty($config['global-values']['sample:group_id'])) {
        $groupId = (int) $config['global-values']['sample:group_id'];
        $extraJoins[] = <<<SQL
LEFT JOIN (groups_locations gl
  JOIN groups g ON g.id=gl.group_id AND g.deleted=false AND g.id=$groupId
  JOIN groups_users gu ON gu.group_id=g.id AND gu.user_id=$this->auth_user_id AND gu.pending=false AND gu.deleted=false
) ON gl.location_id=l.id AND gl.deleted=false
SQL;
        $matchAny[] = 'gl.id IS NOT NULL';
        $filterForLocationSearchAPI['group_id'] = $groupId;
      }
      // @todo If psnAttr ID can be passed through from config to here, it
      // could be used as an extra filter possibility (include locations the
      // user is joined to).
      $matchAll[] = '(' . implode(" OR ", $matchAny) . ')';
    }
    $filters = implode("\nAND ", $matchAll);
    $joins = implode("\n", $extraJoins);
    // Add a column to capture potential multiple matching taxa.
    $uniq = uniqid(TRUE);
    $sql = <<<SQL
ALTER TABLE import_temp.$dbIdentifiers[tempTableName]
ADD COLUMN IF NOT EXISTS $valueToMapIdChoicesColName json;

-- Find possible location name matches.
SELECT trim(lower(i.{$valueToMapColName})) as locinfo,
  ARRAY_AGG(DISTINCT l.id) AS choices,
  JSON_AGG(DISTINCT jsonb_build_object(
	  'id', l.id,
    'name', l.name,
    'code', l.code,
    'centroid_sref', l.centroid_sref,
    'type', lt.term,
    'external_key', l.external_key,
    'parent_name', lp.name
  )) AS choice_info
INTO TEMPORARY location_matches_$uniq
FROM import_temp.$dbIdentifiers[tempTableName] i
JOIN locations l
  ON trim(lower(i.{$valueToMapColName}))=lower(l.$mappedLocationTableColName)
LEFT JOIN locations lp ON lp.id=l.parent_id AND lp.deleted=false
LEFT JOIN locations_websites lw ON lw.location_id=l.id AND lw.deleted=false
LEFT JOIN cache_termlists_terms lt ON lt.id=l.location_type_id
$joins
WHERE $filters
GROUP BY i.{$valueToMapColName};

UPDATE import_temp.$dbIdentifiers[tempTableName] i
SET {$valueToMapIdColName}=CASE ARRAY_LENGTH(sm.choices, 1) WHEN 1 THEN ARRAY_TO_STRING(sm.choices, '') ELSE NULL END::integer,
{$valueToMapIdChoicesColName}=sm.choice_info
FROM location_matches_$uniq sm
WHERE trim(lower(i.{$valueToMapColName}))=sm.locinfo;

DROP TABLE location_matches_$uniq;
SQL;
    $db->query($sql);
    // Now find and return the list of values that still need to be matched to
    // a location by the user.
    $sql = <<<SQL
SELECT DISTINCT {$valueToMapColName} as value, {$valueToMapIdChoicesColName}::text as choices
FROM import_temp.$dbIdentifiers[tempTableName]
WHERE {$valueToMapIdColName} IS NULL AND trim($valueToMapColName)<>'';
SQL;
    $rows = $db->query($sql)->result();
    $values = [];
    foreach ($rows as $row) {
      $values[$row->value] = $row->choices;
    }
    return [
      'values' => $values,
      'type' => 'location',
      'locationFilters' => $filterForLocationSearchAPI,
    ];
  }

  /**
   * Auto-fills the foreign keys for any other lookups, e.g. licence.
   *
   * Excluding taxon, location and attribute value lookups which are handled
   * separately.
   *
   * @param object $db
   *   Database connection.
   * @param array $config
   *   Import configuration object.
   * @param array $info
   *   Column info data for the column being autofilled.
   *
   * @return array
   *   Array containing information about the result.
   */
  private function autofillOtherFkIds($db, array $config, array $info) {
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $destFieldParts = explode(':', $info['warehouseField']);
    $entity = ORM::factory($destFieldParts[0], -1);
    $fieldName = preg_replace('/^fk_/', '', $destFieldParts[1]);
    $tempDbField = pg_escape_identifier($db->getLink(), $info['tempDbField']);
    $fkField = pg_escape_identifier($db->getLink(), "$info[tempDbField]_id");
    if (array_key_exists($fieldName, $entity->belongs_to)) {
      $fkEntity = $entity->belongs_to[$fieldName];
    }
    elseif (array_key_exists($fieldName, $entity->has_one)) {
      // This ignores the ones which are just models in list: the key is used
      // to point to another model.
      $fkEntity = $entity->has_one[$fieldName];
    }
    elseif ($entity instanceof ORM_Tree && $fieldName == 'parent') {
      $fkEntity = inflector::singular($entity->getChildren());
    }
    else {
      $fkEntity = $fieldName;
    }
    // Create model without initialising, so we can just check the lookup
    // variables.
    $fkModel = ORM::Factory($fkEntity, -1);
    // Let the model map the lookup against a view if necessary.
    $lookupAgainst = pg_escape_identifier($db->getLink(), inflector::plural($fkModel->lookup_against ?? "list_$fkEntity"));
    // Search field is lookup model default, but if there are 3 tokens in the
    // destination field name then the 3rd token overrides this.
    $searchField = pg_escape_identifier($db->getLink(), $destFieldParts[2] ?? $fkModel->search_field);
    $websiteId = (int) $config['global-values']['website_id'];
    $sql = <<<SQL
      UPDATE import_temp.$dbIdentifiers[tempTableName] i
      SET $fkField=l.id
      FROM $lookupAgainst l
      WHERE trim(lower(i.$tempDbField))=lower(l.$searchField)
      AND l.website_id=$websiteId;
    SQL;
    $db->query($sql);
    $sql = <<<SQL
      SELECT DISTINCT trim(lower($tempDbField)) as value
      FROM import_temp.$dbIdentifiers[tempTableName]
      WHERE $fkField IS NULL
      AND $tempDbField <> ''
      ORDER BY trim(lower($tempDbField));
    SQL;
    $values = [];
    $rows = $db->query($sql)->result();
    foreach ($rows as $row) {
      $values[] = $row->value;
    }
    // Find the available possible options from the fk lookup list.
    $sql = <<<SQL
      SELECT l.id, l.$searchField
      FROM $lookupAgainst l
      ORDER BY l.$searchField
    SQL;
    $matchOptions = [];
    $rows = $db->query($sql)->result();
    foreach ($rows as $row) {
      $matchOptions[$row->id] = $row->{$searchField};
    }
    return [
      'values' => $values,
      'matchOptions' => $matchOptions,
      'type' => 'otherFk',
    ];
  }

  /**
   * Creates a temporary table in the import_temp schema.
   *
   * Provides an area to hold records in whilst processing them.
   *
   * @param string $fileName
   *   Name of the import file.
   * @param array $config
   *   File upload config.
   */
  private function createTempTable($fileName, array &$config) {
    $db = new Database();
    $tableName = 'import_' . date('YmdHi') . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
    $config['tableName'] = $tableName;
    $colsArray = ['_row_id serial'];
    $dbFieldNames = $this->getColumnTempDbFieldMappings($db, $config['columns']);
    foreach ($dbFieldNames as $fieldName) {
      $colsArray[] = "$fieldName varchar";
    }
    $colsArray[] = 'errors jsonb';
    $colsList = implode(",\n", $colsArray);
    $qry = <<<SQL
CREATE TABLE import_temp.$tableName (
  $colsList
);
SQL;
    $db->query($qry);
    $errorCheck = pg_last_error($db->getLink());
    if (!empty($errorCheck)) {
      throw new exception($errorCheck);
    }
  }

  /**
   * Loads a batch of records from the import file to the temp table.
   *
   * @param string $fileName
   *   Name of the import file.
   * @param array $config
   *   File upload config.
   */
  private function loadNextRecordsBatch($fileName, array &$config) {
    $importTools = new ImportTools();
    $file = $importTools->openSpreadsheet($fileName, $config, BATCH_ROW_LIMIT);
    $rowPosInBatch = 0;
    $count = 0;
    $rows = [];
    $db = new Database();
    $foundDataInBatch = FALSE;
    while (($rowPosInBatch < BATCH_ROW_LIMIT)) {
      // Work out current row pos (+ 1 to skip header).
      $rowPosInFile = $rowPosInBatch + $config['rowsRead'] + 1;
      if ($rowPosInFile > $config['totalRows']) {
        // All rows done.
        break;
      }
      $data = $file[$rowPosInFile] ?? [];
      // Nulls need to be empty strings for trim() to work.
      $data = array_map(function ($value) {
        return $value ?? '';
      }, $data);
      // Skip empty rows.
      if (!empty(implode('', $data))) {
        $foundDataInBatch = TRUE;
        // Trim and escape the data, then pad to correct number of columns.
        $data = array_map(function ($s) use ($db) {
          return pg_escape_literal($db->getLink(), $s);
        }, array_pad(array_map('trim', $data), count($config['columns']), ''));
        // Also allow for their being too many columns (wider data row than
        // column titles provided).
        if (count($data) > count($config['columns'])) {
          $data = array_slice($data, 0, count($config['columns']));
        }
        if (implode('', $data) <> '') {
          $rows[] = '(' . implode(', ', $data) . ')';
        }
        $count++;
      }
      else {
        // Skipping empty row so correct the total expected.
        $config['totalRows']--;
      }
      $rowPosInBatch++;
    }
    $config['rowsLoaded'] = $config['rowsLoaded'] + $count;
    $config['rowsRead'] = $config['rowsRead'] + $rowPosInBatch;
    if (count($rows)) {
      $fieldNames = $this->getColumnTempDbFieldMappings($db, $config['columns']);
      $fields = implode(', ', $fieldNames);
      $rowsList = implode("\n,", $rows);
      $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
      $query = <<<SQL
INSERT INTO import_temp.$dbIdentifiers[tempTableName]($fields)
VALUES $rowsList;
SQL;
      $db->query($query);
      $errorCheck = pg_last_error($db->getLink());
      if (!empty($errorCheck)) {
        throw new exception($errorCheck);
      }
    }
    if ($config['totalRows'] === 0) {
      throw new exception('The import file does not contain any data to import.');
    }
    // An entire empty batch causes us to give up. Most likely the user saved a
    // spreadsheet with multiple empty rows at the bottom.
    if (!$foundDataInBatch) {
      $config['totalRows'] = $config['rowsLoaded'];
    }
    $config['progress'] = $config['rowsLoaded'] * 100 / $config['totalRows'];
    if ($config['rowsLoaded'] >= $config['totalRows']) {
      $config['state'] = 'loaded';
    }
  }

  /**
   * Create a unique ID for the import.
   */
  private function createGuid() {
    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
       mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479),
       mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
    );
  }

  /**
   * Retrieves the contents of the JSON config file for this import.
   *
   * Describes columns, mappings etc. Initialises a fresh object if the file is
   * not present.
   *
   * @param string $fileName
   *   Name of the import file.
   *
   * @return array
   *   Config key/value pairs.
   */
  private function getConfig($fileName) {
    $baseName = pathinfo($fileName, PATHINFO_FILENAME);
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $configFile = DOCROOT . "import/$baseName.json";
    if (file_exists($configFile)) {
      $f = fopen($configFile, "r");
      $config = fgets($f);
      fclose($f);
      return json_decode($config, TRUE);
    }
    else {
      // @todo Entity should by dynamic.
      $entity = 'occurrence';
      $model = ORM::Factory($entity);
      $supportsImportGuid = in_array('import_guid', array_keys($model->as_array()));
      $parentEntity = 'sample';
      $model = ORM::Factory($parentEntity);
      $parentSupportsImportGuid = in_array('import_guid', array_keys($model->as_array()));
      $importTools = new ImportTools();
      // Create a new config object.
      return [
        'fileName' => $fileName,
        'tableName' => '',
        'isExcel' => in_array($ext, ['xls', 'xlsx']),
        'entity' => $entity,
        'parentEntity' => $parentEntity,
        'columns' => $this->tidyUpColumnsList($importTools->loadColumnTitlesFromFile($fileName, FALSE)),
        'systemAddedColumns' => [],
        'state' => 'initial',
        // Rows loaded into the temp table (excludes blanks).
        'rowsLoaded' => 0,
        // Rows read from the import file (includes blanks).
        'rowsRead' => 0,
        'progress' => 0,
        'totalRows' => $importTools->getTotalRows($fileName),
        'rowsInserted' => 0,
        'rowsUpdated' => 0,
        'rowsProcessed' => 0,
        'parentEntityRowsProcessed' => 0,
        'errorsCount' => 0,
        'importGuid' => $this->createGuid(),
        'entitySupportsImportGuid' => $supportsImportGuid,
        'parentEntitySupportsImportGuid' => $parentSupportsImportGuid,
      ];
    }
  }

  private function getEscapedDbIdentifiers($db, array $config) {
    return [
      'importDestTable' => pg_escape_identifier($db->getLink(), inflector::plural($config['entity'])),
      'importDestParentTable' => pg_escape_identifier($db->getLink(), inflector::plural($config['parentEntity'])),
      'pkFieldInTempTable' => pg_escape_identifier($db->getLink(), $config['pkFieldInTempTable'] ?? ''),
      'parentEntityFkIdFieldInDestTable' => pg_escape_identifier($db->getLink(), "$config[parentEntity]_id"),
      'tempDbFkIdField' => pg_escape_identifier($db->getLink(), "_$config[entity]_id"),
      'tempDbParentFkIdField' => pg_escape_identifier($db->getLink(), "_$config[parentEntity]_id"),
      'tempTableName' => pg_escape_identifier($db->getLink(), $config['tableName']),
    ];
  }

  /**
   * Saves config to a JSON file, allowing process info to persist.
   *
   * @param string $fileName
   *   Name of the import file.
   * @param array $config
   *   The data to save.
   */
  private function saveConfig($fileName, array $config) {
    $baseName = pathinfo($fileName, PATHINFO_FILENAME);
    $configFile = DOCROOT . "import/$baseName.json";
    $f = fopen($configFile, "w");
    fwrite($f, json_encode($config));
    fclose($f);
  }

  /**
   * If an import completes successfully, remove the temporary table and files.
   *
   * @param object $db
   *   Database connection.
   * @param array $config
   *   Import metadata configuration object.
   */
  private function tidyUpAfterImport($db, array $config) {
    $baseName = pathinfo($config['fileName'], PATHINFO_FILENAME);
    unlink(DOCROOT . "import/$baseName.json");
    unlink(DOCROOT . "import/$config[fileName]");
    $dbIdentifiers = $this->getEscapedDbIdentifiers($db, $config);
    $db->query("DROP TABLE IF EXISTS import_temp.$dbIdentifiers[tempTableName]");
  }

  /**
   * Ensure all columns have a title and sort out db field names.
   *
   * After reading column titles from the import file, any with blank titles
   * are filled in with a default title. Otherwise untitled columns mess up
   * column alignment assumptions later during import.
   *
   * @param array $columns
   *   List of column titles read from the import file.
   *
   * @return array
   *   Associative array, list of column titles with blanks filled in, with
   *   values being the an info array containing the database field name for
   *   the temp table.
   */
  private function tidyUpColumnsList(array $columns) {
    $foundAProperColumn = FALSE;
    // Work backwords in case the spreadsheet contains empty columns on the
    // right.
    for ($i = count($columns) - 1; $i >= 0; $i--) {
      if (!$foundAProperColumn && !empty($columns[$i])) {
        $foundAProperColumn = TRUE;
      }
      elseif ($foundAProperColumn) {
        if (empty($columns[$i])) {
          $columns[$i] = kohana::lang('misc.untitled') . ' - ' . ($i + 1);
        }
      }
      else {
        // Remove columns to the right of the rightmost one with content.
        unset($columns[$i]);
      }
    }
    $colsAndFieldInfo = [];
    // Track to ensure all field names used are unique.
    $uniqueFieldNames = [];
    foreach ($columns as $column) {
      $proposedFieldName = preg_replace('/[^a-z0-9]/', '_', strtolower($column));
      if (in_array($proposedFieldName, $uniqueFieldNames) || in_array($proposedFieldName, SYSTEM_FIELD_NAMES)) {
        $proposedFieldName .= '_' . uniqid();
      }
      $uniqueFieldNames[] = $proposedFieldName;
      $colsAndFieldInfo[$column] = [
        'tempDbField' => $proposedFieldName,
      ];
    }
    return $colsAndFieldInfo;
  }

}
