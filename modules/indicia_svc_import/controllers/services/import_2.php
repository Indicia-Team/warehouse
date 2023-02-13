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

define('BATCH_ROW_LIMIT', 100);
define('SYSTEM_FIELD_NAMES', [
  '_row_id',
  'errors',
]);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
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
    if (!empty($_GET['required']) && $_GET['required'] === 'true') {
      $requiredFields = $model->getRequiredFields(TRUE, $identifiers, $useAssociations);
      // Use the calculated date field for vague date, rather than individual
      // fields.
      foreach ($requiredFields as &$field) {
        $field = preg_replace('/:date_type$/', ':date', $field);
      }
      $fields = array_intersect_key($fields, array_combine($requiredFields, $requiredFields));
    }
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
      $sourceColName = $matchesInfo['source-field'];
      foreach ($matchesInfo['values'] as $value => $termlist_term_id) {
        // Safety check.
        if (!preg_match('/\d+/', $termlist_term_id)) {
          throw new exception('Mapped termlist term ID is not an integer.');
        }
        $literal = pg_escape_literal($db->getLink(), $value);
        $sql = <<<SQL
UPDATE import_temp.$config[tableName]
SET {$sourceColName}_id=$termlist_term_id
WHERE trim(lower({$sourceColName}))=lower($literal);
SQL;
        $db->query($sql);
      }
      // Need to check all done.
      $sql = <<<SQL
SELECT DISTINCT {$sourceColName} AS value FROM import_temp.$config[tableName]
WHERE {$sourceColName}<>'' AND {$sourceColName}_id IS NULL;
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
    if ($stepIndex >= count($steps)) {
      echo json_encode([
        'status' => 'done',
      ]);
    }
    else {
      $r = array_merge([
        'step' => $steps[$stepIndex][0],
        'description' => $steps[$stepIndex][1],
      ], call_user_func([$this, $steps[$stepIndex][0]], $fileName, $config));
      if ($stepIndex < count($steps) - 1) {
        // Another step to do...
        $r['nextStep'] = $steps[$stepIndex + 1][0];
        $r['nextDescription'] = $steps[$stepIndex + 1][1];
      }
      echo json_encode($r);
    }
  }

  /**
   * Preprocessing to check existing record updates limited to user's records.
   */
  private function checkRecordIdsOwnedByUser($fileName, array $config) {
    $db = new Database();
    $columnInfo = $this->getColumnInfoByProperty($config['columns'], 'warehouseField', 'occurrence:id');
    $websiteId = $config['global-values']['website_id'];
    $errorsJson = pg_escape_literal($db->getLink(), json_encode([
      $columnInfo['columnLabel'] => 'You do not have permission to update this record or the record referred to does not exist.',
    ]));
    $table = inflector::plural($config['entity']);
    // @todo For tables other than occurrences, method of accessing website ID differs.
    $sql = <<<SQL
UPDATE import_temp.$config[tableName] u
SET errors = COALESCE(u.errors, '{}'::jsonb) || $errorsJson::jsonb
FROM import_temp.$config[tableName] t
LEFT JOIN $table exist
  ON exist.id=t.$columnInfo[tempDbField]::integer
  AND exist.created_by_id=$this->auth_user_id
  AND exist.website_id=$websiteId
WHERE exist.id IS NULL
AND t.$columnInfo[tempDbField] ~ '^\d+$'
AND t._row_id=u._row_id;
SQL;
    $updated = $db->query($sql)->count();
    if ($updated > 0) {
      return ['error' => 'The import cannot proceed as you do not have permission to update some of the records or the records referred to do not exist.'];
    }
    $sql = <<<SQL
SELECT count(DISTINCT $columnInfo[tempDbField]) as distinct_count, count(*) as total_count
FROM import_temp.$config[tableName]
WHERE $columnInfo[tempDbField]<>''
SQL;
    $counts = $db->query($sql)->current();
    if ($counts->distinct_count !== $counts->total_count) {
      return ['error' => 'The import file appears to refer to the same existing record more than once.'];
    }
    return [
      'message' => [
        "{1} existing {2} found",
        $counts->distinct_count,
        $table,
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
    $websiteId = $config['global-values']['website_id'];
    $table = inflector::plural($config['entity']);
    // @todo For tables other than occurrences, method of accessing website ID differs.
    $sql = <<<SQL
ALTER TABLE import_temp.$config[tableName]
ADD COLUMN IF NOT EXISTS _$config[entity]_id integer;

UPDATE import_temp.$config[tableName] u
SET _$config[entity]_id=exist.id
FROM $table exist
WHERE exist.external_key::text=u.$columnInfo[tempDbField]
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
SELECT count(DISTINCT _$config[entity]_id) as distinct_count, count(*) as total_count
FROM import_temp.$config[tableName]
WHERE _$config[entity]_id IS NOT NULL
SQL;
    $counts = $db->query($sql)->current();
    if ($counts->distinct_count !== $counts->total_count) {
      return ['error' => 'The import file appears to refer to the same existing record more than once.'];
    }
    return [
      'message' => [
        "{1} existing {2} found",
        $counts->distinct_count,
        $table,
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
    $table = inflector::plural($config['entity']);
    $sql = <<<SQL
ALTER TABLE import_temp.$config[tableName]
ADD COLUMN IF NOT EXISTS _$config[parentEntity]_id integer;

UPDATE import_temp.$config[tableName] u
SET _$config[parentEntity]_id=exist.$config[parentEntity]_id
FROM $table exist
WHERE u.$config[pkFieldInTempTable] ~ '^\d+$'
AND exist.id=u.$config[pkFieldInTempTable]::integer
AND exist.deleted=false;
SQL;
    $db->query($sql);
    $updated = $db->query("SELECT count(DISTINCT _$config[parentEntity]_id) FROM import_temp.$config[tableName] WHERE _$config[parentEntity]_id IS NOT NULL")->current()->count;
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
    $table = inflector::plural($config['entity']);
    $parentTable = inflector::plural($config['parentEntity']);
    $sql = <<<SQL
UPDATE import_temp.$config[tableName] u
SET _$config[parentEntity]_id=null
FROM $parentTable parent
JOIN import_temp.$config[tableName] t ON t._$config[parentEntity]_id=parent.id
JOIN $table allchildren ON allchildren.$config[parentEntity]_id=parent.id AND allchildren.deleted=false AND allchildren.deleted=false
LEFT JOIN import_temp.$config[tableName] exist ON exist.$config[pkFieldInTempTable] ~ '^\d+$' AND COALESCE(exist.$config[pkFieldInTempTable], '0')::integer=allchildren.id
WHERE exist._row_id IS NULL
AND parent.id=u._$config[parentEntity]_id
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
      $parentColNames[] = $info['tempDbField'];
    }
    $parentColsList = implode(" || '||' || ", $parentColNames);
    $sql = <<<SQL
    SELECT t._$config[parentEntity]_id, COUNT(DISTINCT $parentColsList)
    INTO TEMPORARY to_clear
    FROM import_temp.$config[tableName] t
    GROUP BY t._$config[parentEntity]_id
    HAVING COUNT(DISTINCT $parentColsList)>1;

    UPDATE import_temp.$config[tableName] u
    SET _$config[parentEntity]_id=null
    FROM to_clear c
    WHERE c._$config[parentEntity]_id=u._$config[parentEntity]_id;
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
      foreach ($parentEntityDataRows as $parentEntityDataRow) {
        // @todo Updating existing data.
        // @todo tracking of records that are done in the import table so can restart.
        $parent = ORM::factory($config['parentEntity']);
        $submission = [];
        $this->copyFieldsFromRowToSubmission($parentEntityDataRow, $parentEntityColumns, $config, $submission);
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
          $errors = $parent->precheck($identifiers);
          // A fake ID to allow check on children.
          $parent->id = 1;
        }
        else {
          $parent->submit();
          $errors = $parent->getAllErrors();
        }
        $childEntityDataRows = $this->fetchChildEntityData($db, $parentEntityColumns, $config, $parentEntityDataRow);
        if (count($errors) > 0) {
          $config['errorsCount'] += count($childEntityDataRows);
          if (!$isPrecheck) {
            // As we won't individually process the occurrences due to error in
            // the sample, add them to the count.
            $config['rowsProcessed'] += count($childEntityDataRows);
          }
          $keyFields = $this->getDestFieldsForColumns($parentEntityColumns);
          $this->saveErrorsToRows($db, $parentEntityDataRow, $keyFields, $parent->getAllErrors(), $config);
        }
        // If sample saved OK, or we are just prechecking, process the matching
        // occurrences.
        if (count($errors) === 0 || $isPrecheck) {
          foreach ($childEntityDataRows as $childEntityDataRow) {
            $child = ORM::factory($config['entity']);
            $submission = [
              'sample_id' => $parent->id,
            ];
            $this->applyGlobalValues($config, $config['entity'], $child->attrs_field_prefix ?? NULL, $submission);
            $this->copyFieldsFromRowToSubmission($childEntityDataRow, $childEntityColumns, $config, $submission);
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
              $config['errorsCount']++;
              $this->saveErrorsToRows($db, $childEntityDataRow, ['_row_id'], $errors, $config);
            }
            elseif (!$isPrecheck) {
              $config['rowsInserted']++;
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

    $fields = array_merge(array_values($this->getColumnTempDbFieldMappings($config['columns'])), [
      '_row_id',
      'errors',
    ]);
    $fieldSql = implode(', ', $fields);
    $query = <<<SQL
SELECT $fieldSql
FROM import_temp.$config[tableName]
WHERE errors IS NOT NULL
ORDER BY _row_id;
SQL;
    $db = new Database();
    $results = $db->query($query)->result(FALSE);
    $out = fopen('php://output', 'w');
    fputcsv($out, array_merge(array_keys($config['columns']), [
      '[Row no.]',
      '[Errors]',
    ]));
    foreach ($results as $row) {
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
   * @param array $columns
   *   Columns config.
   *
   * @return array
   *   Associative array.
   */
  private function getColumnTempDbFieldMappings(array $columns) {
    $r = [];
    foreach ($columns as $columnLabel => $info) {
      $r[$columnLabel] = $info['tempDbField'];
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
    $import->website_id = $config['global-values']['website_id'];
    $import->inserted = $config['rowsInserted'];
    $import->updated = $config['rowsUpdated'];
    $import->errors = $config['errorsCount'];
    $import->mappings = json_encode($config['columns']);
    $import->global_values = json_encode($config['global-values']);
    if ($importInfo && !empty($importInfo->description)) {
      // This will only get specified on initial save.
      $import->description = $importInfo->description;
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
      $whereList[] = "$field='{$rowData->$field}'";
    }
    $wheres = implode(' AND ', $whereList);
    $errorsList = [];
    foreach ($errors as $field => $error) {
      // A date error might be reported against a vague date component
      // field, but can map back to the calculated date field if separate
      // date fields not being used.
      $field = preg_replace('/date_(start|end|type)$/', 'date', $field);
      try {
        $columnInfo = $this->getColumnInfoByProperty($config['columns'], 'warehouseField', $field);
        $errorsList[$columnInfo['columnLabel']] = $error;
      }
      catch (NotFoundException $e) {
        // Shouldn't happen, but means we need better logic from mapping from
        // the errored field to the mapped field name.
        $errorsList['unknown column'] = $error;
      }
    }
    $errorsJson = pg_escape_literal($db->getLink(), json_encode($errorsList));
    $sql = <<<SQL
UPDATE import_temp.$config[tableName]
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
   * @param array $columns
   *   List of column definitions to look for uniqueness in the values of.
   * @param array $config
   *   Import metadata configuration object.
   */
  private function fetchParentEntityData($db, array $columns, array $config) {
    $fields = $this->getDestFieldsForColumns($columns);
    $fieldsAsCsv = implode(', ', $fields);
    // Batch row limit div by arbitrary 10 to allow for multiple children per
    // parent.
    $batchRowLimit = BATCH_ROW_LIMIT / 10;
    $sql = <<<SQL
SELECT DISTINCT $fieldsAsCsv
FROM import_temp.$config[tableName]
ORDER BY $fieldsAsCsv
LIMIT $batchRowLimit
OFFSET $config[parentEntityRowsProcessed];
SQL;
    return $db->query($sql)->result();
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
  private function copyFieldsFromRowToSubmission($dataRow, array $columns, array $config, array &$submission) {
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
      if ($config['isExcel'] && preg_match('/date$/', $destFieldName) && preg_match('/^\d+$/', $dataRow->$srcFieldName)) {
        // Date fields are integers when read from Excel.
        $date = ImportDate::excelToDateTimeObject($dataRow->$srcFieldName);
        $submission[$destFieldName] = $date->format('d/m/Y');
      }
      else {
        $submission[$destFieldName] = $dataRow->$srcFieldName;
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
    $fields = $this->getDestFieldsForColumns($columns);
    // Build a filter to extract rows for this parent entity.
    $wheresList = [];
    foreach ($fields as $field) {
      $wheresList[] = "COALESCE($field::text, '')='" . $parentEntityDataRow->$field . "'";
    }
    $wheres = implode("\nAND ", $wheresList);
    // Now retrieve the sub-entity rows.
    $sql = <<<SQL
SELECT *
FROM import_temp.$config[tableName]
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
          $sql = <<<SQL
ALTER TABLE import_temp.$config[tableName]
ADD COLUMN IF NOT EXISTS $info[tempDbField]_id integer;
SQL;
          $db->query($sql);
          // Query to fill in ID for all obvious matches.
          if (substr($destFieldParts[0], -4) === 'Attr' and strlen($destFieldParts[0]) === 7) {
            $unmatchedInfo = $this->autofillLookupAttrIds($db, $config, $destFieldParts, $info['tempDbField']);
          }
          elseif ($info['warehouseField'] === 'occurrence:fk_taxa_taxon_list') {
            $unmatchedInfo = $this->autofillTaxonIds($db, $config, $info['tempDbField']);
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
   *
   * @return array
   *   Array containing information about the result.
   */
  private function autofillTaxonIds($db, array $config, $valueToMapColName) {
    $filtersList = [];
    $filterForTaxonSearchAPI = [];
    foreach ($config['global-values'] as $fieldDef => $value) {
      if (substr($fieldDef, 0, 36) === 'occurrence:fkFilter:taxa_taxon_list:') {
        $filterField = str_replace('occurrence:fkFilter:taxa_taxon_list:', '', $fieldDef);
        $escaped = pg_escape_literal($db->getLink(), $value);
        $filtersList[] = "AND cttl.$filterField=$escaped";
        $filterForTaxonSearchAPI[$filterField] = $value;
      }
    }
    $filters = implode("\n", $filtersList);
    // Add a column to capture potential multiple matching taxa.
    $uniq = uniqid(TRUE);
    $sql = <<<SQL
ALTER TABLE import_temp.$config[tableName]
ADD COLUMN IF NOT EXISTS {$valueToMapColName}_id_choices json;

-- Find possible taxon name matches. If a name is not accepted, but has an
-- accepted alternative, it gets skipped.
SELECT trim(lower(i.{$valueToMapColName})) as taxon,
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
  ON trim(lower(i.{$valueToMapColName}))=lower(cttl.taxon) AND cttl.allow_data_entry=true
  $filters
-- Drop if accepted name exists which also matches.
LEFT JOIN cache_taxa_taxon_lists cttlaccepted
  ON trim(lower(i.{$valueToMapColName}))=lower(cttlaccepted.taxon) AND cttlaccepted.id<>cttl.id
  AND cttlaccepted.allow_data_entry=true AND cttlaccepted.preferred=true
  AND cttlaccepted.taxon_meaning_id=cttl.taxon_meaning_id
  AND cttlaccepted.taxon_list_id=cttl.taxon_list_id
WHERE cttlaccepted.id IS NULL
GROUP BY i.{$valueToMapColName};

UPDATE import_temp.$config[tableName] i
SET {$valueToMapColName}_id=CASE ARRAY_LENGTH(sm.choices, 1) WHEN 1 THEN ARRAY_TO_STRING(sm.choices, '') ELSE NULL END::integer,
{$valueToMapColName}_id_choices=sm.choice_info
FROM species_matches_$uniq sm
WHERE trim(lower(i.{$valueToMapColName}))=sm.taxon;

DROP TABLE species_matches_$uniq;
SQL;
    $db->query($sql);
    $sql = <<<SQL
SELECT DISTINCT {$valueToMapColName} as value, {$valueToMapColName}_id_choices::text as choices
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
   * Auto-fills the foreign keys for lookup taxon text values
   *
   * Excluding taxon lookups which are handled separately.
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
    $destFieldParts = explode(':', $info['warehouseField']);
    $entity = ORM::factory($destFieldParts[0], -1);
    $fieldName = preg_replace('/^fk_/', '', $destFieldParts[1]);
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
    $lookupAgainst = inflector::plural(isset($fkModel->lookup_against) ? $fkModel->lookup_against : $fkEntity);
    $sql = <<<SQL
UPDATE import_temp.$config[tableName] i
SET {$info['tempDbField']}_id=l.id
FROM $lookupAgainst l
WHERE trim(lower(i.$info[tempDbField]))=lower(l.$fkModel->search_field);
SQL;
    $db->query($sql);
    $sql = <<<SQL
SELECT DISTINCT trim(lower($info[tempDbField])) as value
FROM import_temp.$config[tableName]
WHERE {$info['tempDbField']}_id IS NULL
AND $info[tempDbField] <> ''
ORDER BY trim(lower($info[tempDbField]));
SQL;
    $values = [];
    $rows = $db->query($sql)->result();
    foreach ($rows as $row) {
      $values[] = $row->value;
    }
    // Find the available possible options from the fk lookup list.
    $sql = <<<SQL
SELECT l.id, l.$fkModel->search_field
FROM $lookupAgainst l
ORDER BY l.$fkModel->search_field
SQL;
    $matchOptions = [];
    $rows = $db->query($sql)->result();
    foreach ($rows as $row) {
      $matchOptions[$row->id] = $row->{$fkModel->search_field};
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
    $dbFieldNames = $this->getColumnTempDbFieldMappings($config['columns']);
    foreach ($dbFieldNames as $fieldName) {
      // Enclose column names in "" in case reserved word.
      $colsArray[] = "\"$fieldName\" varchar";
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
    $file = $this->openSpreadsheet($fileName, $config);
    $rowPos = 0;
    $count = 0;
    $rows = [];
    $db = new Database();
    while (($rowPos < BATCH_ROW_LIMIT) && ($data = $this->getNextRow($file, $rowPos + $config['rowsRead'] + 1, $config))) {
      // Nulls need to be empty strings for trim() to work.
      $data = array_map(function ($value) {
        return $value === NULL ? '' : $value;
      }, $data);
      // Skip empty rows.
      if (!empty(implode('', $data))) {
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
      $rowPos++;
    }
    $config['rowsLoaded'] = $config['rowsLoaded'] + $count;
    $config['rowsRead'] = $config['rowsRead'] + $rowPos;
    if (count($rows)) {
      $fieldNames = $this->getColumnTempDbFieldMappings($config['columns']);
      // Enclose field names in "" in case reserved word.
      $fields = '"' . implode('", "', $fieldNames) . '"';
      $rowsList = implode("\n,", $rows);
      $query = <<<SQL
INSERT INTO import_temp.$config[tableName]($fields)
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
      // Create a new config object.
      return [
        'fileName' => $fileName,
        'tableName' => '',
        'isExcel' => in_array($ext, ['xls', 'xlsx']),
        'entity' => $entity,
        'parentEntity' => $parentEntity,
        'columns' => $this->loadColumnNamesFromFile($fileName),
        'systemAddedColumns' => [],
        'state' => 'initial',
        // Rows loaded into the temp table (excludes blanks).
        'rowsLoaded' => 0,
        // Rows read from the import file (includes blanks).
        'rowsRead' => 0,
        'progress' => 0,
        'totalRows' => $this->getTotalRows($fileName),
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
   *   values being the an info array containing the database field name for
   *   the temp table.
   */
  private function tidyUpColumnsList(array $columns) {
    $foundAProperColumn = FALSE;
    $columns = array_map('trim', $columns);
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
   * Opens a CSV or spreadsheet file and winds to current rowsRead offset.
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
    $reader->setReadFilter(new RangeReadFilter($config['rowsRead'] + 2, BATCH_ROW_LIMIT));
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
