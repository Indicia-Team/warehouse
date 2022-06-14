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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

define('BATCH_ROW_LIMIT', 5);
define('SYSTEM_FIELD_NAMES', [
  'id',
  'errors',
]);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ImportDate;

/**
 * PHPSpreadsheet filter for reading the header row.
 */
class FirstRowReadFilter implements IReadFilter {

  /**
   * Enable reading of row 1 only.
   *
   * @param int $column
   *   Column number - ignored.
   * @param int $row
   *   Row number.
   * @param string $worksheetName
   *   Worksheet name - ignored.
   */
  public function readCell($column, $row, $worksheetName = '') {
    return $row == 1;
  }

}

/**
 * PHPSpreadsheet filter for reading a range of data rows.
 */
class RangeReadFilter implements IReadFilter {

  /**
   * Start row to read from.
   *
   * @var int
   */
  private $offset;

  /**
   * Number of rows to read.
   *
   * @var int
   */
  private $limit;

  /**
   * Object constructor, sets offset and limit.
   */
  public function __construct($offset, $limit) {
    $this->offset = $offset;
    $this->limit = $limit;
  }

  /**
   * Enable reading of only the rows that are in range.
   *
   * @param int $column
   *   Column number - ignored.
   * @param int $row
   *   Row number.
   * @param string $worksheetName
   *   Worksheet name - ignored.
   */
  public function readCell($column, $row, $worksheetName = '') {
    return $row >= $this->offset && $row < $this->offset + $this->limit;
  }

}

/**
 * Controller class for import web services.
 */
class Import_2_Controller extends Service_Base_Controller {

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
   * Accepts optional $_GET parameters for the website_id and survey_id, which
   * limit the available custom attribute fields as appropriate. Echoes JSON
   * listing the fields that can be imported.
   *
   * @param string $entity
   *   Singular name of the entity to check.
   */
  public function get_fields($entity) {
    header("Content-Type: application/json");
    $this->authenticate('read');
    switch ($entity) {
      case 'sample':
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
    $this->provideFriendlyFieldCaptions($fields);
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
      // Ensure we have write permissions.
      $this->authenticate();
      // We will be using a POST array to send data, and presumably a FILES
      // array for the media.
      // Upload size.
      $ups = Kohana::config('indicia.maxUploadSize');
      $validatedFiles = Validation::factory($_FILES)->add_rules(
        'media_upload',
        'upload::valid',
        'upload::required',
        'upload::type[csv,xls,xlsx,zip]',
        "upload::size[$ups]"
      );
      if (count($validatedFiles) === 0) {
        echo json_encode([
          'error' => 'No file was uploaded.',
        ]);
      }
      elseif ($validatedFiles->validate()) {
        $safeFileName = str_replace(' ', '_', strtolower($validatedFiles['media_upload']['name']));
        if (array_key_exists('name_is_guid', $_POST) && $_POST['name_is_guid'] == 'true') {
          $finalName = $safeFileName;
        }
        else {
          $finalName = uniqid() . '_' . $safeFileName;
        }
        $fTmp = upload::save('media_upload', $finalName, DOCROOT . 'import');
        echo json_encode([
          'status' => 'ok',
          'uploadedFile' => basename($fTmp),
        ]);
      }
      else {
        kohana::log('error', 'Validation errors uploading file ' . $validatedFiles['media_upload']['name']);
        kohana::log('error', print_r($validatedFiles->errors('form_error_messages'), TRUE));
        foreach ($validatedFiles as $file) {
          if (!empty($file['error'])) {
            kohana::log('error', 'PHP reports file upload error: ' . $this->fileValidationcodeToMessage($file['error']));
          }
        }
        http_response_code(400);
        echo json_encode([
          'error' => $validatedFiles->errors('form_error_messages'),
        ]);
      }
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
      if (!file_exists(DOCROOT . 'import/' . $_POST['uploaded-file'])) {
        throw new exception('Parameter uploaded-file refers to a missing file');
      }
      $zip = new ZipArchive();
      $res = $zip->open(DOCROOT . 'import/' . $_POST['uploaded-file']);
      if ($res !== TRUE) {
        throw new exception('The Zip archive could not be opened.');
      }
      if ($zip->count() !== 1) {
        throw new exception('The Zip archive must contain only one file.');
      }
      $ext = pathinfo($zip->getNameIndex(0), PATHINFO_EXTENSION);
      if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
        throw new exception('The Zip archive contains a file type that cannot be imported.');
      }
      $fileName = uniqid(TRUE) . ".$ext";
      if (!$zip->renameIndex(0, $fileName)) {
        throw new exception('Unable to rename the file in the Zip archive.');
      }
      if (!$zip->extractTo(DOCROOT . "import", $fileName)) {
        throw new exception('Unable to unzip the Zip archive.');
      };
      $zip->close();
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
    kohana::log('debug', "In process_lookup_matching");
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
      $sourceColName = $matchesInfo['source-field'];
      foreach ($matchesInfo['values'] as $value => $termlist_term_id) {
        // Safety check.
        if (!preg_match('/\d+/', $termlist_term_id)) {
          throw new exception('Mapped termlist term ID is not an integer.');
        }
        $literal = pg_escape_literal($value);
        $sql = <<<SQL
UPDATE import_temp.$config[tableName]
SET {$sourceColName}_id=$termlist_term_id
WHERE trim(lower({$sourceColName}))=lower($literal);
SQL;
        $db->query($sql);
      }
      // Need to check all done.
      $sql = <<<SQL
SELECT count(*) FROM import_temp.$config[tableName]
WHERE {$sourceColName}<>'' AND {$sourceColName}_id IS NULL;
SQL;
      $countCheck = $db->query($sql)->result()->current()->count;
      if ($countCheck === '0') {
        echo json_encode([
          'status' => 'ok',
        ]);
      }
      else {
        echo json_encode([
          'status' => 'incomplete',
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
      // Don't process cache tables immediately to improve performance.
      cache_builder::$delayCacheUpdates = TRUE;
      $fileName = $_POST['data-file'];
      if (!file_exists(DOCROOT . "import/$fileName")) {
        throw new exception('Parameter data-file refers to a missing file');
      }
      $config = $this->getConfig($fileName);
      $db = new Database();
      if (!empty($_POST['save-import-record'])) {
        $this->saveImportRecord($config, json_decode($_POST['save-import-record']));
      }
      // @todo Correctly set parent entity for other entities.
      // @todo Handling for entities without parent entity.
      $parentEntityFields = $this->findEntityFields($config['parentEntity'], $config);
      $childEntityFields = $this->findEntityFields($config['entity'], $config);
      $parentEntityDataRows = $this->fetchParentEntityData($db, $parentEntityFields, $config);
      foreach ($parentEntityDataRows as $parentEntityDataRow) {
        // @todo Updating existing data.
        // @todo tracking of records that are done in the import table so can restart.
        $parent = ORM::factory($config['parentEntity']);
        $submission = [];
        $this->copyFieldsFromRowToSubmission($parentEntityDataRow, $parentEntityFields, $config, $submission);
        $this->applyGlobalValues($config, $config['parentEntity'], $submission);
        if ($config['parentEntitySupportsImportGuid']) {
          $submission["$config[parentEntity]:import_guid"] = $config['importGuid'];
        }
        $parent->set_submission_data($submission);
        $parent->submit();
        $childEntityDataRows = $this->fetchChildEntityData($db, $parentEntityFields, $config, $parentEntityDataRow);
        if (count($parent->getAllErrors()) > 0) {
          $config['errorsCount'] += count($childEntityDataRows);
          $config['rowsProcessed'] += count($childEntityDataRows);
          $this->saveErrorsToRows($db, $parentEntityDataRow, $parentEntityFields, $parent->getAllErrors(), $config);
        }
        else {
          foreach ($childEntityDataRows as $childEntityDataRow) {
            $child = ORM::factory($config['entity']);
            $submission = [
              'sample_id' => $parent->id,
            ];
            $this->copyFieldsFromRowToSubmission($childEntityDataRow, $childEntityFields, $config, $submission);
            $this->applyGlobalValues($config, $config['entity'], $submission);
            if ($config['entitySupportsImportGuid']) {
              $submission["$config[entity]:import_guid"] = $config['importGuid'];
            }
            $child->set_submission_data($submission);
            $child->submit();
            if (count($child->getAllErrors()) > 0) {
              $config['importErrors']++;
              $this->saveErrorsToRows($db, $parentEntityDataRow, $parentEntityFields, $child->getAllErrors(), $config);
            }
            $config['rowsInserted']++;
            $config['rowsProcessed']++;
          }
        }
        $config['parentEntityRowsInserted']++;
      }

      $progress = 100 * $config['rowsProcessed'] / $config['totalRows'];
      if ($progress === 100 && $config['errorsCount'] === 0) {
        $this->tidyUpAfterImport($db, $config);
      }
      else {
        $this->saveConfig($fileName, $config);
      }
      $this->saveImportRecord($config);
      echo json_encode([
        'status' => $config['rowsProcessed'] >= $config['totalRows'] ? 'done' : 'importing',
        'progress' => 100 * $config['rowsProcessed'] / $config['totalRows'],
        'errorsCount' => $config['errorsCount'],
      ]);
    }
    catch (Exception $e) {
      // Save config as it tells us how far we got, making diagnosis and
      // continuation easier.
      $this->saveConfig($fileName, $config);
      $this->saveImportRecord($config);
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
    ];
    foreach ($friendlyCaptions as $field => $caption) {
      if (isset($friendlyCaptions[$field])) {
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
    $import->inserted = $config['rowsInserted'];
    $import->updated = $config['rowsUpdated'];
    $import->errors = $config['errorsCount'];
    $import->mappings = json_encode($config['mappings']);
    $import->global_values = json_encode($config['global-values']);
    if ($importInfo && !empty($importInfo->description)) {
      // This will only get specified on initial save.
      $import->description = $importInfo->description;
    }
    $import->import_guid = $config['importGuid'];
    $import->save();
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
      $whereList[] = "$field='{$rowData->$field}'";
    }
    $wheres = implode(' AND ', $whereList);
    $errorsList = [];
    foreach ($errors as $field => $error) {
      $fieldName = $field;
      // Find the temp table field name for the error.
      $dbFieldInTempTable = array_search($fieldName, $config['mappings']);
      if ($dbFieldInTempTable) {
        // Map back to find the import file's name for the column.
        $fieldName = array_search($dbFieldInTempTable, $config['columns']);
      }
      $errorsList[$fieldName] = $error;
    }
    $errorsJson = pg_escape_literal(json_encode($errorsList));
    $sql = <<<SQL
UPDATE import_temp.$config[tableName]
SET errors = COALESCE(errors, '{}'::jsonb) || $errorsJson::jsonb
WHERE $wheres;
SQL;
    $db->query($sql);
  }

  /**
   * Finds mapped database fields that relate to an entity.
   *
   * @param string $entity
   *   Entity name, e.g. sample, occurrence.
   * @param array $config
   *   Import metadata configuration object.
   */
  private function findEntityFields($entity, array $config) {
    $fields = [];
    // @todo Also include attribute columns where appropriate.
    foreach ($config['mappings'] as $dbFieldInTempTable => $dest) {
      $dest = $config['mappings'][$dbFieldInTempTable];
      $destFieldParts = explode(':', $dest);
      $attrPrefix = $this->getAttrPrefix($entity);
      if ($destFieldParts[0] === $entity || ($attrPrefix && $destFieldParts[0] === $attrPrefix)) {
        $fields[] = $dbFieldInTempTable;
      }
    }
    return $fields;
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
    return isset($entityPrefixes[$entity]) ? $entityPrefixes[$entity] : NULL;
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
    return isset($entitiesByPrefix[$prefix]) ? $entitiesByPrefix[$prefix] : NULL;
  }

  /**
   * When importing to a parent entity, need distinct data values.
   *
   * One row will be created per distinct set of field values, so this returns
   * a db result with the required rows.
   *
   * @param object $db
   *   Database connection.
   * @param array $fields
   *   List of field names to look for uniqueness in the values of.
   * @param array $config
   *   Import metadata configuration object.
   */
  private function fetchParentEntityData($db, array $fields, array $config) {
    $fieldsAsCsv = implode(', ', $fields);
    $batchRowLimit = BATCH_ROW_LIMIT;
    $sql = <<<SQL
SELECT DISTINCT $fieldsAsCsv
FROM import_temp.$config[tableName]
ORDER BY $fieldsAsCsv
LIMIT $batchRowLimit
OFFSET $config[parentEntityRowsInserted];
SQL;
    return $db->query($sql)->result();
  }

  /**
   * Copies a set of fields from a data row into a submission array.
   *
   * @param object $dataRow
   *   Data read from the import file.
   * @param array $fields
   *   List of field names to copy.
   * @param array $config
   *   Import metadata configuration object.
   * @param array $submission
   *   Submission data array that will be updated with the copied values.
   */
  private function copyFieldsFromRowToSubmission($dataRow, array $fields, array $config, array &$submission) {
    foreach ($fields as $field) {
      $targetField = $config['mappings'][$field];
      // @todo Look for date fields more intelligently.
      if ($config['isExcel'] && preg_match('/date$/', $targetField) && preg_match('/\d+/', $dataRow->$field)) {
        // Date fields are integers when read from Excel.
        $date = ImportDate::excelToDateTimeObject($dataRow->$field);
        $submission[$targetField] = $date->format('d/m/Y');
      }
      else {
        $submission[$targetField] = $dataRow->$field;
      }
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
   * @param array $submission
   *   Submission data array that will be updated with the global values.
   */
  private function applyGlobalValues(array $config, $entity, array &$submission) {
    foreach ($config['global-values'] as $field => $value) {
      if (in_array($field, ['survey_id', 'website_id'])
          || substr($field, 0, strlen($entity) + 1) === "$entity:") {
        $submission[$field] = $value;
      }
    }
  }

  /**
   * For a parent entity record (e.g. sample), find the child data rows.
   *
   * @param object $db
   *   Database connection.
   * @param array $fields
   *   List of parent field names that will be filtered to find the child data
   *   rows.
   * @param array $config
   *   Import metadata configuration object.
   * @param object $parentEntityDataRow
   *   Data row holding the parent record values.
   *
   * @return object
   *   Database result containing child rows.
   */
  private function fetchChildEntityData($db, array $fields, array $config, $parentEntityDataRow) {
    // Build a filter to extract rows for this parent entity.
    $wheresList = [];
    foreach ($fields as $field) {
      $wheresList[] = "$field='" . $parentEntityDataRow->$field . "'";
    }
    $wheres = implode("\nAND ", $wheresList);
    // Now retrieve the sub-entity rows.
    $sql = <<<SQL
SELECT *
FROM import_temp.$config[tableName]
WHERE $wheres
ORDER BY id;
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
    if (!isset($config['lookupFields'])) {
      $config['lookupFields'] = [];
    }
    $foundOne = FALSE;
    foreach ($config['mappings'] as $src => $dest) {
      if ($dest) {
        $destFieldParts = explode(':', $dest);
        if (substr($destFieldParts[1], 0, 3) === 'fk_' && !in_array($dest, $config['lookupFields'])) {
          $foundOne = TRUE;
          $colTitle = array_search($src, $config['columns']);
          $valueToMapColName = $src;
          $thisSrcField = $src;
          $config['lookupFields'][] = $dest;
          // Add an ID field to the data table.
          $sql = <<<SQL
ALTER TABLE import_temp.$config[tableName]
ADD COLUMN IF NOT EXISTS {$valueToMapColName}_id integer;
SQL;
          $db->query($sql);
          // Replace the mapping.
          unset($config['mappings'][$valueToMapColName]);
          $config['mappings']["{$valueToMapColName}_id"] =
            "$destFieldParts[0]:" .
            // Fieldname without fk_ prefix.
            substr($destFieldParts[1], 3) .
            // Append _id if not a custom attribute lookup.
            (preg_match('/^[a-z]{3}Attr$/', $destFieldParts[0]) ? '' : '_id');

          // Query to fill in ID for all obvious matches.
          if (substr($destFieldParts[0], -4) === 'Attr' and strlen($destFieldParts[0]) === 7) {
            $unmatchedInfo = $this->autofillLookupAttrIds($db, $config, $destFieldParts, $valueToMapColName);
          }
          elseif ($dest === 'occurrence:fk_taxa_taxon_list') {
            $unmatchedInfo = $this->autofillTaxonIds($db, $config, $valueToMapColName);
          }
          // Respond with values that don't match plus list of matches, or a
          // success message.
          break;
        }
      }
    }
    $r = [
      'status' => 'ok',
      'msgKey' => $foundOne ? 'lookupFieldFound' : 'findLookupFieldsDone',
    ];
    if (isset($colTitle)) {
      $r['columnTitle'] = $colTitle;
    }
    if (isset($thisSrcField)) {
      $r['sourceField'] = $thisSrcField;
    }
    if (isset($unmatchedInfo) && count($unmatchedInfo['values']) > 0) {
      $r['unmatchedInfo'] = $unmatchedInfo;
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
   * @param array $destFieldParts
   *   Database entity and fieldname that the column's values are destined for.
   * @param string $valueToMapColName
   *   Name of the column containing the term to lookup. The ID column that
   *   gets populated will have the same name, with _id appended.
   *
   * @return array
   *   Matching info, including a list of match options, a list of unmatched
   *   values that require user input to fix, plus info about the attribute
   *   we are trying to match values for.
   */
  private function autofillLookupAttrIds($db, array $config, array $destFieldParts, $valueToMapColName) {
    $attrEntity = $this->getEntityFromAttrPrefix($destFieldParts[0]);
    $attrId = str_replace('fk_', '', $destFieldParts[1]);
    $sql = <<<SQL
UPDATE import_temp.$config[tableName] i
SET {$valueToMapColName}_id=t.id
FROM cache_termlists_terms t
JOIN {$attrEntity}_attributes a ON a.termlist_id=t.termlist_id
AND a.id=$attrId AND a.deleted=false
WHERE trim(lower(i.$valueToMapColName))=lower(t.term);
SQL;
    $db->query($sql);
    $sql = <<<SQL
SELECT DISTINCT trim(lower($valueToMapColName)) as value
FROM import_temp.$config[tableName]
WHERE {$valueToMapColName}_id IS NULL
AND {$valueToMapColName} <> ''
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
   * @param string $valueToMapColName
   *   Name of the column containing the taxon to lookup. The ID column that
   *   gets populated will have the same name, with _id appended.
   */
  private function autofillTaxonIds($db, array $config, $valueToMapColName) {
    $filtersList = [];
    $filterForTaxonSearchAPI = [];
    foreach ($config['global-values'] as $fieldDef => $value) {
      if (substr($fieldDef, 0, 36) === 'occurrence:fkFilter:taxa_taxon_list:') {
        $filterField = str_replace('occurrence:fkFilter:taxa_taxon_list:', '', $fieldDef);
        $escaped = pg_escape_literal($value);
        $filtersList[] = "AND cttl.$filterField=$escaped";
        $filterForTaxonSearchAPI[$filterField] = $value;
      }
    }
    $filters = implode("\n", $filtersList);
    // Add a column to capture potential multiple matching taxa.
    $uniq = uniqid(TRUE);
    $sql = <<<SQL
ALTER TABLE import_temp.$config[tableName]
ADD COLUMN IF NOT EXISTS {$valueToMapColName}_id_choices integer[];

-- First prioritise accepted names.
SELECT trim(lower(i.{$valueToMapColName})) as taxon, ARRAY_AGG(DISTINCT cttl.id) AS choices
INTO TEMPORARY species_matches_$uniq
FROM import_temp.$config[tableName] i
JOIN cache_taxa_taxon_lists cttl
  ON trim(lower(i.{$valueToMapColName}))=lower(cttl.taxon) AND cttl.allow_data_entry=true
  $filters
LEFT JOIN cache_taxa_taxon_lists cttlaccepted
  ON trim(lower(i.{$valueToMapColName}))=lower(cttlaccepted.taxon) AND cttlaccepted.id<>cttl.id
  AND cttlaccepted.allow_data_entry=true AND cttlaccepted.preferred=true
  AND cttlaccepted.taxon_meaning_id=cttl.taxon_meaning_id
  AND cttlaccepted.taxon_list_id=cttl.taxon_list_id
WHERE cttlaccepted.id IS NULL
GROUP BY i.{$valueToMapColName};

UPDATE import_temp.$config[tableName] i
SET {$valueToMapColName}_id=CASE ARRAY_LENGTH(sm.choices, 1) WHEN 1 THEN ARRAY_TO_STRING(sm.choices, '') ELSE NULL END::integer,
{$valueToMapColName}_id_choices=sm.choices
FROM species_matches_$uniq sm
WHERE trim(lower(i.{$valueToMapColName}))=sm.taxon;

DROP TABLE species_matches_$uniq;
SQL;
    $db->query($sql);
    $sql = <<<SQL
SELECT DISTINCT {$valueToMapColName} as value, {$valueToMapColName}_id_choices as choices
FROM import_temp.$config[tableName]
WHERE {$valueToMapColName}_id IS NULL;
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
    $colsArray = ['id serial'];
    foreach (array_values($config['columns']) as $columnName) {
      $colsArray[] = "$columnName varchar";
    }
    $colsArray[] = 'errors jsonb';
    $colsList = implode(",\n", $colsArray);
    $qry = <<<SQL
CREATE TABLE import_temp.$tableName (
  $colsList
);
SQL;
    $db->query($qry);
    $errorCheck = pg_last_error();
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
    $file = $this->openSpreadsheet($fileName, $config);
    $count = 0;
    $rows = [];
    while (($count < BATCH_ROW_LIMIT) && ($data = $this->getNextRow($file, $count + $config['rowsLoaded'] + 1, $config))) {
      $data = array_map('pg_escape_literal', array_pad($data, count($config['columns']), ''));
      $rows[] = '(' . implode(', ', $data) . ')';
      $count++;
    }
    $config['rowsLoaded'] = $config['rowsLoaded'] + $count;
    if (count($rows)) {
      $columns = implode(', ', $config['columns']);
      $rowsList = implode("\n,", $rows);
      $query = <<<SQL
INSERT INTO import_temp.$config[tableName]($columns)
VALUES $rowsList;
SQL;
      $db = new Database();
      $db->query($query);
      $errorCheck = pg_last_error();
      if (!empty($errorCheck)) {
        throw new exception($errorCheck);
      }
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
      $config['parentEntity'] = 'sample';
      $model = ORM::Factory($config['parentEntity']);
      $parentSupportsImportGuid = in_array('import_guid', array_keys($model->as_array()));
      // Create a new config object.
      return [
        'fileName' => $fileName,
        'tableName' => '',
        'isExcel' => in_array($ext, ['xls', 'xlsx']),
        // @todo Entity should be dynamic.
        'entity' => $entity,
        'parentEntity' => $config['parentEntity'],
        'columns' => $this->loadColumnNamesFromFile($fileName),
        'state' => 'initial',
        'rowsLoaded' => 0,
        'progress' => 0,
        'totalRows' => $this->getTotalRows($fileName),
        'rowsInserted' => 0,
        'rowsUpdated' => 0,
        'rowsProcessed' => 0,
        'parentEntityRowsInserted' => 0,
        'errorsCount' => 0,
        'importGuid' => $this->createGuid(),
        'entitySupportsImportGuid' => $supportsImportGuid,
        'parentEntitySupportsImportGuid' => $parentSupportsImportGuid,
      ];
    }
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
    $db->query("DROP TABLE IF EXISTS import_temp.$config[tableName]");
  }

  /**
   * Opens a PHPSpreadsheet Reader for the selected file.
   *
   * @param string $fileName
   *   Name of the import file.
   *
   * @return PhpOffice\PhpSpreadsheet\Reader
   *   Reader object.
   */
  private function getReader($fileName) {
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    if ($ext === 'csv') {
      $reader = new Csv();
      $reader->setInputEncoding(Csv::GUESS_ENCODING);
    }
    elseif ($ext === 'xlsx') {
      $reader = new Xlsx();
    }
    elseif ($ext === 'xls') {
      $reader = new Xls();
    }
    else {
      error_logger::log_trace(debug_backtrace());
      throw new exception("Unsupported file type \"$ext\" for file \"$fileName\".");
    }
    // Don't read document formatting.
    $reader->setReadDataOnly(TRUE);
    return $reader;
  }

  /**
   * Return number of rows to import in the file.
   *
   * For CSV, the file size in bytes which can be compared with filepos to get
   * progress info. For PHPSpreadsheet files, the worksheet's size.
   *
   * @param string $fileName
   *   Name of the import file.
   *
   * @return int
   *   A representation of the file size.
   */
  private function getTotalRows($fileName) {
    $reader = $this->getReader($fileName);
    // Minimise data read from spreadsheet - first sheet only.
    $worksheetData = $reader->listWorksheetInfo(DOCROOT . "import/$fileName");
    if (count($worksheetData) === 0) {
      throw new exception('Spreadsheet contains no worksheets');
    }
    // Subtract 1 to exclude header.
    return $worksheetData[0]['totalRows'] - 1;
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
   *   values being the database field name for the temp table.
   */
  private function tidyUpColumnsList(array $columns) {
    $foundAProperColumn = FALSE;
    // Work backwords in case the spreadsheet contains empty columns on the
    // right.
    for ($i = count($columns) - 1; $i >= 0; $i--) {
      if (!empty($columns[$i])) {
        $foundAProperColumn = TRUE;
      }
      elseif ($foundAProperColumn) {
        if (empty(trim($columns[$i]))) {
          $columns[$i] = kohana::lang('misc.untitled') . ' - ' . ($i + 1);
        }
      }
    }
    $colsAndFieldNames = [];
    foreach ($columns as $column) {
      $proposedFieldName = preg_replace('/[^a-z0-9]/', '_', strtolower($column));
      if (in_array($proposedFieldName, $colsAndFieldNames) || in_array($proposedFieldName, SYSTEM_FIELD_NAMES)) {
        $proposedFieldName .= '_' . uniqid();
      }
      $colsAndFieldNames[$column] = $proposedFieldName;
    }
    return $colsAndFieldNames;
  }

  /**
   * Uses the first row of data to build a list of columns in the import file.
   *
   * @param string $fileName
   *   The data file to load.
   *
   * @return array
   *   List of columns in the file as array keys, with values being the
   *   suggested field names.
   */
  private function loadColumnNamesFromFile($fileName) {
    $reader = $this->getReader($fileName);
    // Minimise data read from spreadsheet - first sheet only.
    $worksheetData = $reader->listWorksheetInfo(DOCROOT . "import/$fileName");
    if (count($worksheetData) === 0) {
      throw new exception('Spreadsheet contains no worksheets');
    }
    $reader->setLoadSheetsOnly($worksheetData[0]['worksheetName']);
    // Only read first row.
    $reader->setReadFilter(new FirstRowReadFilter());
    $file = $reader->load(DOCROOT . "import/$fileName");
    $data = $file->getActiveSheet()->toArray();
    if (count($data) === 0) {
      throw new exception('The spreadsheet file is empty');
    }
    return $this->tidyUpColumnsList($data[0]);
  }

  /**
   * Opens a CSV or spreadsheet file and winds to current rowsLoaded offset.
   *
   * @param string $fileName
   *   The data file to open.
   * @param array $config
   *   Import config information.
   */
  private function openSpreadsheet($fileName, array &$config) {
    $reader = $this->getReader($fileName);
    // Minimise data read from spreadsheet - first sheet only.
    $worksheetData = $reader->listWorksheetInfo(DOCROOT . "import/$fileName");
    if (count($worksheetData) === 0) {
      throw new exception('Spreadsheet contains no worksheets');
    }
    $reader->setLoadSheetsOnly($worksheetData[0]['worksheetName']);
    // Add two to the range start, as it is indexed from one not zero unlike
    // the data array read out and we skip the header row.
    $reader->setReadFilter(new RangeReadFilter($config['rowsLoaded'] + 2, BATCH_ROW_LIMIT));
    $file = $reader->load(DOCROOT . "import/$fileName");
    return $file->getActiveSheet()->toArray();
  }

  /**
   * Reads the next row from the data file.
   *
   * @param resource $file
   *   Data array (PHPSpreadsheet).
   * @param int $row
   *   Row to fetch (PHPSpreadsheet).
   * @param array $config
   *   Config including file size info.
   *
   * @return array
   *   Data array.
   */
  private function getNextRow($file, $row, array $config) {
    return ($row <= $config['totalRows']) ? $file[$row] : FALSE;
  }

  /**
   * Converts a file validation code to a readable message.
   *
   * @param string $code
   *   File validation code.
   *
   * @return string
   *   Error message.
   */
  private function fileValidationcodeToMessage($code) {
    switch ($code) {
      case UPLOAD_ERR_INI_SIZE:
        $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
        break;

      case UPLOAD_ERR_FORM_SIZE:
        $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
        break;

      case UPLOAD_ERR_PARTIAL:
        $message = "The uploaded file was only partially uploaded";
        break;

      case UPLOAD_ERR_NO_FILE:
        $message = "No file was uploaded";
        break;

      case UPLOAD_ERR_NO_TMP_DIR:
        $message = "Missing a temporary folder";
        break;

      case UPLOAD_ERR_CANT_WRITE:
        $message = "Failed to write file to disk";
        break;

      case UPLOAD_ERR_EXTENSION:
        $message = "File upload stopped by extension";
        break;

      default:
        $message = "Unknown upload error";
        break;
    }
    return $message;
  }

}
