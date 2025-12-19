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

define('SYSTEM_FIELD_NAMES', [
  '_row_id',
  'errors',
  'checked',
  'imported',
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
    // Data file supported for legacy clients, where data file and config ID
    // would always be the same thing.
    if (!empty($_POST['config-id'] ?? $_POST['data-file'])) {
      $configId = $this->getConfigId();
      $config = import2ChunkHandler::getConfig($configId);
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
    $keepFkIds = !empty($_GET['keep_fk_ids']) && $_GET['keep_fk_ids'] === 'true';
    $fields = $model->getSubmittableFields(TRUE, $keepFkIds, $identifiers, $attrTypeFilter, $useAssociations);
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
      kohana::log('error', 'Error in upload_file: ' . $e->getMessage());
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
      kohana::log('error', 'Error in extract_file: ' . $e->getMessage());
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
    $this->authenticate('write');
    if (isset($_POST['data-file'])) {
      // Legacy single file upload.
      $files = [$_POST['data-file']];
    }
    else {
      $files = json_decode($_POST['data-files']);
    }
    foreach ($files as $fileName) {
      if (!file_exists(DOCROOT . "import/$fileName")) {
        throw new exception("Parameter data-files refers to a missing file $fileName");
      }
    }
    try {
      $config = $this->createConfig($files, ($_POST['enable-background-imports'] ?? 'f') === 't');
    }
    catch (RequestAbort) {
      return;
    }
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
    $configId = $this->getConfigId($files[0]);
    import2ChunkHandler::saveConfig($configId, $config);
    echo json_encode([
      'status' => 'ok',
      'configId' => $configId,
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
      $configId = $this->getConfigId();
      $config = import2ChunkHandler::getConfig($configId);
      if ($config['state'] === 'nextFile') {
        $config['state'] = 'loadingRecords';
      }
      if ($config['state'] === 'initial') {
        $this->createTempTable($fileName, $config);
        $config['state'] = 'loadingRecords';
      }
      elseif ($config['state'] === 'loadingRecords') {
        $this->loadNextRecordsBatch($fileName, $config);
      }
      import2ChunkHandler::saveConfig($configId, $config);
      echo json_encode([
        'status' => 'ok',
        'progress' => $config['progress'],
        'msgKey' => $config['state'],
      ]);
    }
    catch (Exception $e) {
      error_logger::log_error('Error in load_chunk_to_temp_table', $e);
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
   * Can be called repeatedly until the response msgKey is set to
   * findLookupFieldsDone. Returns information on the next found lookup field
   * that has data values that need to be matched.
   */
  public function process_lookup_matching() {
    header("Content-Type: application/json");
    try {
      $this->authenticate('write');
      // Data file supported for legacy clients.
      $configId = $this->getConfigId();
      $config = import2ChunkHandler::getConfig($configId);
      $db = new Database();
      $r = $this->findNextLookupField($db, $config);
      import2ChunkHandler::saveConfig($configId, $config);
      echo json_encode($r);
    }
    catch (Exception $e) {
      error_logger::log_error('Error in process_lookup_matching', $e);
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
      // Support data-file for legacy clients.
      $configId = $this->getConfigId();
      $config = import2ChunkHandler::getConfig($configId);
      $db = new Database();
      $matchesInfo = json_decode($_POST['matches-info'], TRUE);
      $sourceColName = pg_escape_identifier($db->getLink(), $matchesInfo['source-field']);
      $sourceIdCol = pg_escape_identifier($db->getLink(), $matchesInfo['source-field'] . '_id');
      $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
      foreach ($matchesInfo['values'] as $value => $termlist_term_id) {
        // Safety check.
        if (!preg_match('/\d+/', $termlist_term_id)) {
          throw new exception('Mapped termlist term ID is not an integer.');
        }
        $sql = <<<SQL
          UPDATE import_temp.$dbIdentifiers[tempTableName]
          SET $sourceIdCol=?
          WHERE trim(lower($sourceColName::text))=lower(?::text);
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
    // Support data-file for legacy clients.
    $configId = $this->getConfigId();
    $config = import2ChunkHandler::getConfig($configId);
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
    import2ChunkHandler::saveConfig($configId, $config);
    $this->addTempDbTableIndexes($configId, $config);
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
    // Data file allowed for legacy clients.
    $configId = $this->getConfigId();
    $stepIndex = $_POST['index'];
    $config = import2ChunkHandler::getConfig($configId);
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
        import2ChunkHandler::saveConfig($configId, $config);
      }
      else {
        // Step is one of the core list.
        $stepOutput = call_user_func([$this, $steps[$stepIndex][0]], $configId, $config);
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
   * API endpoint that retrieves the status of background imports.
   *
   * By default echoes a JSON object containing status information for the
   * current user's active background imports, but can also return the
   * information for a specified import by providing a config-id parameter in
   * the $_POST data.
   */
  public function background_import_status() {
    header("Content-Type: application/json");
    $this->authenticate('read');
    $db = new Database();
    if (!empty($_POST['config-id'])) {
      $filter = "params->>'config-id'=" . pg_escape_literal($db->getLink(), $_POST['config-id']);
    }
    else {
      $filter = "params->>'user_id'=" . pg_escape_literal($db->getLink(), $this->auth_user_id);
    }
    $imports = $db->query(<<<SQL
      SELECT params, created_on, claimed_on, error_detail
      FROM work_queue
      WHERE task='task_import_step'
      AND $filter
      ORDER BY created_on;
    SQL)->result(TRUE);
    $r = [];
    foreach ($imports as $import) {
      $params = json_decode($import->params, TRUE);
      $info = [
        'config-id' => $params['config-id'],
        'created_on' => $import->created_on,
        'claimed_on' => $import->claimed_on,
        'error_detail' => $import->error_detail,
        'state' => !empty($params['precheck']) ? 'prechecking' : 'importing',
      ];
      $config = import2ChunkHandler::getConfig($params['config-id']);

      $info['totalRows'] = $config['totalRows'] ?? 0;
      $info['rowsInserted'] = $config['rowsInserted'] ?? 0;
      $info['rowsUpdated'] = $config['rowsUpdated'] ?? 0;
      if (!empty($params['restart'])) {
        $info['rowsProcessed'] = 0;
      }
      else {
        $info['rowsProcessed'] = $config['rowsProcessed'] ?? 0;
      }
      $info['errorsCount'] = $config['errorsCount'] ?? 0;
      $r[] = $info;
    }
    echo json_encode($r);
  }

  /**
   * Add indexes to the temporary import table for faster lookups later.
   *
   * @param string $configId
   *   Config ID.
   * @param array $config
   *   Config array.
   */
  private function addTempDbTableIndexes($configId, array $config) {
    $db = new Database();
    // Once loaded, add some indexes for easier lookups later.
    $lookupFieldsForParentEntity = $this->getLookupFieldsForParentEntityIndex($db, $config);
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
    $sql = <<<SQL
      CREATE INDEX idx_{$configId}_row_id ON import_temp.$dbIdentifiers[tempTableName] (_row_id);
      CREATE INDEX idx_{$configId}_findsample ON import_temp.$dbIdentifiers[tempTableName] ($lookupFieldsForParentEntity);
    SQL;
    $db->query($sql);
  }

  /**
   * Get the index field definitions for looking up against the parent entity.
   *
   * During import, multiple queries are run against the import table to
   * identify records associated with the parent entity (e.g. sample). So we
   * create indexes on the key fields for the parent entity which are likely to
   * be always populated. The fields returned are coalesced to match the way
   * they will be queried.
   *
   * @param object $db
   *   Database connection.
   * @param array $config
   *   Import config.
   *
   * @return string
   *   Fields to include in the index.
   */
  private function getLookupFieldsForParentEntityIndex($db, array $config) {
    switch ($config['parentEntity']) {
      case 'sample':
        $fields = ['sample:date_start', 'sample:entered_sref'];
        break;

      default:
        throw new exception('Unsupported parent entity for import: ' . $config['parentEntity']);
    }
    $indexFields = [];
    foreach ($fields as &$warehouseField) {
      foreach ($config['columns'] as $info) {
        if (isset($info['warehouseField']) && isset($info['tempDbField']) && $info['warehouseField'] === $warehouseField) {
          $indexFields[] = 'COALESCE(' . pg_escape_identifier($db->getLink(), $info['tempDbField']) . ", '')";
          break;
        }
      }
    }
    return implode(', ', $indexFields);
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
  private function checkRecordIdsOwnedByUser($configId, array $config) {
    $db = new Database();
    $columnInfo = import2ChunkHandler::getColumnInfoByProperty($config['columns'], 'warehouseField', 'occurrence:id');
    $websiteId = (int) $config['global-values']['website_id'];
    $errorsJson = pg_escape_literal($db->getLink(), json_encode([
      $columnInfo['columnLabel'] => 'You do not have permission to update this record or the record referred to does not exist.',
    ]));
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
  private function mapExternalKeyToExistingRecords($configId, array $config) {
    $db = new Database();
    $columnInfo = import2ChunkHandler::getColumnInfoByProperty($config['columns'], 'warehouseField', 'occurrence:external_key');
    $websiteId = (int) $config['global-values']['website_id'];
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
    import2ChunkHandler::saveConfig($configId, $config);
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
  private function findOriginalParentEntityIds($configId, array $config) {
    $db = new Database();
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
    import2ChunkHandler::saveConfig($configId, $config);
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
  private function clearParentEntityIdsIfNotAllChildrenPresent($configId, array $config) {
    $db = new Database();
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
  private function clearNewParentEntityIdsIfNowMultipleSamples($configId, array $config) {
    $db = new Database();
    $parentEntityColumns = import2ChunkHandler::findEntityColumns($config['parentEntity'], $config);
    $parentColNames = [];
    foreach ($parentEntityColumns as $info) {
      $parentColNames[] = pg_escape_identifier($db->getLink(), $info['tempDbField']);
    }
    $parentColsList = implode(" || '||' || ", $parentColNames);
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
    // Using data-file is allowed for legacy code.
    $configId = $this->getConfigId();
    $config = import2ChunkHandler::getConfig($configId);
    foreach ($_POST as $key => $value) {
      if ($key !== 'data-file' && $key !== 'config-id') {
        $config[$key] = json_decode($value);
      }
    }
    import2ChunkHandler::saveConfig($configId, $config);
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
    // Data file allowed for legacy clients.
    $fileName = $_GET['config-id'] ?? $_GET['data-file'];
    echo json_encode(import2ChunkHandler::getConfig($fileName));
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
      $db = new Database();
      $isPrecheck = !empty($_POST['precheck']);
      $configId = $this->getConfigId();
      $config = import2ChunkHandler::getConfig($configId);
      if ($this->in_warehouse) {
        // Refresh session in case on the same page for a while.
        Session::instance();
      }
      if (!empty($_POST['save-import-record'])) {
        import2ChunkHandler::saveImportRecord($config, json_decode($_POST['save-import-record']));
      }
      if (!empty($_POST['save-import-template'])) {
        $this->saveImportTemplate($config, json_decode($_POST['save-import-template']));
      }
      if ($isPrecheck && !empty($_POST['restart']) && $config['processingMode'] === 'background') {
        $q = new WorkQueue();
        $q->enqueue($db, [
          'task' => 'task_import_step',
          'params' => json_encode([
            'config-id' => $configId,
            'precheck' => TRUE,
            'restart' => TRUE,
            'website_id' => $this->website_id,
            'user_id' => $this->auth_user_id,
          ]),
          'cost_estimate' => 100,
          'priority' => 2,
        ]);
        echo json_encode([
          'status' => 'queued',
          'msg' => 'Import queued for background processing due to the number of records.',
        ]);
        return;
      }
      $r = import2ChunkHandler::importChunk($db, [
        // Data file supported for legacy clients.
        'config-id' => $configId,
        'precheck' => $isPrecheck,
        'restart' => !empty($_POST['restart']),
        'website_id' => $this->website_id,
        'user_id' => $this->auth_user_id,
      ]);
      echo json_encode($r);
    } catch (Exception $e) {
      http_response_code(500);
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Adds end-point for retrieving errors file.
   *
   * Sends CSV content for rows with errors to the output.
   */
  public function get_errors_file() {
    // Support data-file for legacy clients.
    if (empty($_GET['config-id'] ?? $_GET['data-file'])) {
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
    $configId = $_GET['config-id'];
    $config = import2ChunkHandler::getConfig($configId);
    $db = new Database();
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
    $fields = array_merge(array_values($this->getColumnTempDbFieldMappings($db, $config['columns'])), [
      // Excel row starts on 2, our _row_id is a db sequence so starts on 1.
      '_row_id+1',
      'errors',
    ]);
    if (!empty($_GET['include-not-imported'])) {
      $fields[] = "CASE imported WHEN true THEN 'yes' ELSE 'no' END as imported";
    }
    $fieldSql = implode(', ', $fields);
    $includeNotImported = !empty($_GET['include-not-imported']) ? 'OR imported=false' : '';
    $query = <<<SQL
      SELECT $fieldSql
      FROM import_temp.$dbIdentifiers[tempTableName]
      WHERE errors IS NOT NULL
      $includeNotImported
      ORDER BY _row_id;
    SQL;
    $results = $db->query($query)->result(FALSE);
    $out = fopen('php://output', 'w');
    fputcsv($out, array_merge(array_keys($config['columns']), [
      '[Row no.]',
      '[Errors]',
      '[Imported]',
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
   * Finds the next mapped field that requires lookup data matching.
   *
   * @param object $db
   *   Database connection.
   * @param array $config
   *   Import metadata configuration. Will be updated to include any found
   *   lookup fields.
   */
  private function findNextLookupField($db, array &$config) {
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
    $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
    $tableName = 'import_' . date('YmdHi') . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $this->getConfigId($fileName));
    $config['tableName'] = $tableName;
    $colsArray = [
      '_row_id serial',
    ];
    $dbFieldNames = $this->getColumnTempDbFieldMappings($db, $config['columns']);
    foreach ($dbFieldNames as $fieldName) {
      $colsArray[] = "$fieldName varchar";
    }
    $colsArray[] = 'errors jsonb';
    $colsArray[] = 'checked boolean default false';
    $colsArray[] = 'imported boolean default false';
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
    // Larger batch size for big imports is more efficient at expensive of progress granularity.
    $batchLimit = max(min(round($config['totalRows'] / 20), 10000), 500);
    $file = $importTools->openSpreadsheet($fileName, $config, $batchLimit);
    $rows = [];
    $rowsDoneInBatch = 0;
    $db = new Database();
    $foundDataInBatch = FALSE;
    while (($rowsDoneInBatch < $batchLimit)) {
      if ($config['files'][$fileName]['rowsRead'] >= $config['files'][$fileName]['rowCount']) {
        // All rows done.
        break;
      }
      // +1 to skip the header.
      $data = $file[$config['files'][$fileName]['rowsRead'] + 1] ?? [];
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
        $config['rowsLoaded']++;
      }
      else {
        // Skipping empty row so correct the total expected.
        $config['totalRows']--;
      }
      $config['files'][$fileName]['rowsRead']++;
      $rowsDoneInBatch++;
    }
    if (count($rows)) {
      $fieldNames = $this->getColumnTempDbFieldMappings($db, $config['columns']);
      $fields = implode(', ', $fieldNames);
      $rowsList = implode("\n,", $rows);
      $dbIdentifiers = import2ChunkHandler::getEscapedDbIdentifiers($db, $config);
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
    // An entire empty batch causes us to stop. Most likely the user saved a
    // spreadsheet with multiple empty rows at the bottom.
    if (!$foundDataInBatch) {
      $config['totalRows'] = $config['rowsLoaded'];
      // If original row count was for mostly empty rows, we may have switched
      // to background processing unnecessarily so check if we can switch back.
      if ($config['totalRows'] <= import2ChunkHandler::BACKGROUND_PROCESSING_THRESHOLD && $config['processingMode'] === 'background') {
        $config['processingMode'] = 'immediate';
      }
    }
    $config['progress'] = $config['rowsLoaded'] * 100 / $config['totalRows'];
    if ($config['files'][$fileName]['rowsRead'] >= $config['files'][$fileName]['rowCount']) {
      $config['state'] = ($fileName === array_key_last($config['files'])) ? 'loaded' : 'nextFile';
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
   * @param string $configFile
   *   Configuration file name
   * @param bool $enableBackgroundImports
   *   Set to TRUE to enable work queue based background imports for large
   *   import files.
   *
   * @return array
   *   Config key/value pairs.
   */
  private function createConfig(array $files, $enableBackgroundImports) {
    $ext = pathinfo($files[0], PATHINFO_EXTENSION);

    // @todo Entity should by dynamic.
    $entity = 'occurrence';
    $model = ORM::Factory($entity);
    $supportsImportGuid = in_array('import_guid', array_keys($model->as_array()));
    $parentEntity = 'sample';
    $model = ORM::Factory($parentEntity);
    $parentSupportsImportGuid = in_array('import_guid', array_keys($model->as_array()));
    $importTools = new ImportTools();

    $fileMetadata = [];
    $totalRows = 0;
    foreach ($files as $idx => $file) {
      // Check the column structure matches if a multi-column import.
      if ($idx === 0) {
        $firstFileCols = $importTools->loadColumnTitlesFromFile($file, TRUE);
      }
      else {
        $thisFileCols = $importTools->loadColumnTitlesFromFile($file, TRUE);
        if ($thisFileCols !== $firstFileCols) {
          http_response_code(400);
          echo json_encode([
            'msg' => 'Multiple files uploaded but the files have differing sets of columns. Import aborted.',
            'status' => 'Bad Request',
          ]);
          kohana::log('alert', 'Aborted due to mismatch in import file column structures');
          throw new RequestAbort('Multiple files uploaded but the files have differing sets of columns. Only matching sets of import files can be imported in one batch. Import aborted.');
        }
      }
      // Capture the file's total row count.
      $fileRowCount = $importTools->getRowCountForFile($file);
      $fileMetadata[$file] = [
        'rowCount' => $fileRowCount,
        // Rows loaded into the temp table for this file (excludes blanks).
        //'rowsLoaded' => 0,
        // Rows read from the import file (includes blanks).
        'rowsRead' => 0,
      ];
      $totalRows += $fileRowCount;
    }
    // Create a new config object.
    return [
      'files' => $fileMetadata,
      'tableName' => '',
      'isExcel' => in_array($ext, ['xls', 'xlsx']),
      'entity' => $entity,
      'parentEntity' => $parentEntity,
      'columns' => $this->tidyUpColumnsList($importTools->loadColumnTitlesFromFile($files[0], FALSE)),
      'systemAddedColumns' => [],
      'state' => 'initial',
      // Rows loaded into the temp table (excludes blanks).
      'rowsLoaded' => 0,
      // Rows read from the import file(s) (includes blanks).
      //'rowsRead' => 0,
      'progress' => 0,
      'totalRows' => $totalRows,
      'rowsInserted' => 0,
      'rowsUpdated' => 0,
      'rowsProcessed' => 0,
      'parentEntityRowsProcessed' => 0,
      'errorsCount' => 0,
      'importGuid' => $this->createGuid(),
      'entitySupportsImportGuid' => $supportsImportGuid,
      'parentEntitySupportsImportGuid' => $parentSupportsImportGuid,
      'processingMode' => ($totalRows > import2ChunkHandler::BACKGROUND_PROCESSING_THRESHOLD) && $enableBackgroundImports ? 'background' : 'immediate',
    ];
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

  /**
   * Find the config ID.
   *
   * Note that if the client is an older version, it will just send the data
   * file which cane be used to obtain the config ID.
   *
   * @param string $configIdOrFileName
   *   Optionally specify the file name to extract the config ID from,
   *   otherwise the value is taken from $_POST.
   *
   * @return string
   *   Config ID.
   */
  private function getConfigId($configIdOrFileName = NULL) {
    $configIdOrFileName = $configIdOrFileName ?? $_POST['config-id'] ?? $_POST['data-file'];
    return preg_replace('/(.csv|.xls|.xlsx|.json)$/i', '', $configIdOrFileName);
  }

}
