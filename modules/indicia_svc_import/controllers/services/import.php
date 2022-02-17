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

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * PHPSpreadsheet filter for reading the header row.
 */
class FirstRowReadFilter implements IReadFilter {

  public function readCell($column, $row, $worksheetName = '') {
    return $row == 1;
  }
}

/**
 * PHPSpreadsheet filter for reading a range of data rows.
 */
class RangeReadFilter implements IReadFilter {

  private $offset;

  private $limit;

  public function __construct($offset, $limit) {
    $this->offset = $offset;
    $this->limit = $limit;
  }

  /**
   * Limit range of rows.
   */
  public function readCell($column, $row, $worksheetName = '') {
    return $row >= $this->offset && $row < $this->offset + $this->limit;
  }
}
/**
 * Controller class for import web services.
 */
class Import_Controller extends Service_Base_Controller {
  private $submissionStruct;

  /**
   * Parent model field details from the previous row.
   *
   * Allows us to efficiently use the same sample for multiple occurrences etc.
   *
   * @var array
   */
  private $previousCsvSupermodel;

  /**
   * Import settings service end-point.
   *
   * Controller function that provides a web service
   * services/import/get_import_settings/model. Options for the model's
   * specific form can be passed in $_GET. Echos JSON parameters form details
   * for this model, or empty string if no parameters form required.
   *
   * @param string $model
   *   Singular name of the model entity to check.
   */
  public function get_import_settings($model) {
    $this->authenticate('read');
    $model = ORM::factory($model);
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
   * Controller function that returns the list of importable fields for a model.
   *
   * Accepts optional $_GET parameters for the website_id and survey_id, which
   * limit the available custom attribute fields as appropriate. Echoes JSON
   * listing the fields that can be imported.
   *
   * @param string $model
   *   Singular name of the model entity to check.
   */
  public function get_import_fields($model) {
    $this->authenticate('read');
    switch ($model) {
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
    $model = ORM::factory($model);
    // Identify the context of the import
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
    $use_associations = (empty($_GET['use_associations']) ? FALSE : ($_GET['use_associations'] == "true" ? TRUE : FALSE));
    echo json_encode($model->getSubmittableFields(TRUE, $identifiers, $attrTypeFilter, $use_associations));
  }

  /**
   * Controller function that returns the list of required fields for a model.
   *
   * Accepts optional $_GET parameters for the website_id and survey_id, which
   * limit the available custom attribute fields as appropriate. Echoes JSON
   * listing the fields that are required.
   *
   * @param string $model
   *   Singular name of the model entity to check.
   */
  public function get_required_fields($model) {
    $this->authenticate('read');
    $model = ORM::factory($model);
    // Identify the context of the import
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
    $use_associations = (empty($_GET['use_associations']) ? FALSE : ($_GET['use_associations'] == "true" ? TRUE : FALSE));
    $fields = $model->getRequiredFields(TRUE, $identifiers, $use_associations);
    foreach ($fields as &$field) {
      $field = preg_replace('/:date_type$/', ':date', $field);
    }
    echo json_encode($fields);
  }

  /**
   * List field combinations that can be used to locate existing records.
   *
   * Controller function that returns the list of combinations of fields that
   * can be used to determine if a record already exists. Echoes JSON listing
   * the fields that are required.
   *
   * @param string $modelName
   *   Singular name of the model entity to check.
   */
  public function get_existing_record_options($modelName) {
    $this->authenticate('read');
    $model = ORM::factory($modelName);
    $submissionStruct = $model->get_submission_structure();
    $combinations = array();
    if (isset($submissionStruct['superModels'])) {
      foreach ($submissionStruct['superModels'] as $superModelName => $details) {
        $superModel = ORM::factory($superModelName);
        if (isset($superModel->importDuplicateCheckCombinations)) {
          $combinations[$superModelName] = $superModel->importDuplicateCheckCombinations;
        }
      }
    }
    if (isset($model->importDuplicateCheckCombinations)) {
      $combinations[$modelName] = $model->importDuplicateCheckCombinations;
    }
    echo json_encode($combinations);
  }

  /**
   * Handle the upload of a CSV file.
   *
   * Deprecated - for legacy client support only. Delegates to upload_file().
   */
  public function upload_csv() {
    $this->upload_file();
  }

  /**
   * Handle the upload of an import file.
   *
   * Handle uploaded files in the $_FILES array by moving them to the import
   * folder. The current time is prefixed to the  name to make it unique. The
   * uploaded file should be in a field called media_upload.
   */
  public function upload_file() {
    try {
      // Ensure we have write permissions.
      $this->authenticate();
      // We will be using a POST array to send data, and presumably a FILES
      // array for the media.
      // Upload size.
      $ups = Kohana::config('indicia.maxUploadSize');
      $_FILES = Validation::factory($_FILES)->add_rules(
        'media_upload', 'upload::valid', 'upload::required',
        'upload::type[csv,xls,xlsx]', "upload::size[$ups]"
      );
      if (count($_FILES) === 0) {
        echo "No file was uploaded.";
      }
      elseif ($_FILES->validate()) {
        if (array_key_exists('name_is_guid', $_POST) && $_POST['name_is_guid'] == 'true') {
          $finalName = strtolower($_FILES['media_upload']['name']);
        }
        else {
          $finalName = time() . strtolower($_FILES['media_upload']['name']);
        }
        $fTmp = upload::save('media_upload', $finalName, DOCROOT.'import');
        $this->response = basename($fTmp);
        $this->send_response();
        kohana::log('debug', 'Successfully uploaded file to ' . basename($fTmp));
      }
      else {
        kohana::log('error', 'Validation errors uploading file ' . $_FILES['media_upload']['name']);
        kohana::log('error', print_r($_FILES->errors('form_error_messages'), TRUE));
        foreach ($_FILES as $file) {
          if (!empty($file['error'])) {
            kohana::log('error', 'PHP reports file upload error: ' . $this->codeToMessage($file['error']));
          }
        }
        throw new ValidationError('Validation error', 2004, $_FILES->errors('form_error_messages'));
      }
    }
    catch (Exception $e) {
      $this->handle_error($e);
    }
  }

  /**
   * Retrieves the extension from a file name.
   *
   * @param string $fileName
   *   Name of the file.
   *
   * @return string
   *   Extension in lowercase.
   */
  private function getFileExt($fileName) {
    $parts = explode('.', $fileName);
    $ext = array_pop($parts);
    return strtolower($ext);
  }

  /**
   * Ensure all columns have a title.
   *
   * After reading column titles from the import file, any with blank titles
   * are filled in with a default title. Otherwise untitled columns mess up
   * column alignment assumptions later during import.
   *
   * @param array $columns
   *   List of column titles read from the import file.
   *
   * @return array
   *   List of column titles with blanks filled in.
   */
  private function ensureAllColumnsTitled(array $columns) {
    $foundAProperColumn = FALSE;
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
    return $columns;
  }

  /**
   * Get the array of column names from a file.
   *
   * @param string $fileName
   *   Name of the file.
   *
   * @return array
   *   Array of column header titles.
   */
  private function getColumns($fileName) {
    $ext = $this->getFileExt($fileName);
    if ($ext === 'csv') {
      // For simple CSV, don't need overhead of PHPSpreadsheet.
      $handle = fopen($fileName, 'r');
      $columns = fgetcsv($handle);
      fclose($handle);
      return $this->ensureAllColumnsTitled($columns);
    }
    elseif ($ext === 'xlsx') {
      $reader = new Xlsx();
    }
    elseif ($ext === 'xls') {
      $reader = new Xls();
    }
    else {
      throw new exception('Unsupported file type');
    }
    $reader->setReadDataOnly(TRUE);
    // Minimise data read from spreadsheet - first sheet only.
    $worksheetData = $reader->listWorksheetInfo($fileName);
    if (count($worksheetData) === 0) {
      throw new exception('Spreadsheet contains no worksheets');
    }
    $reader->setLoadSheetsOnly($worksheetData[0]['worksheetName']);
    // Only read first row.
    $reader->setReadFilter(new FirstRowReadFilter());
    $file = $reader->load($fileName);
    $data = $file->getActiveSheet()->toArray();
    if (count($data) === 0) {
      throw new exception('The spreadsheet file is empty');
    }
    return $this->ensureAllColumnsTitled($data[0]);
  }

  /**
   * Controller action to read column names for the uploaded file.
   */
  public function get_column_names() {
    // Ensure we have read permissions.
    $this->authenticate('read');
    echo json_encode($this->getColumns(DOCROOT . "import/$_GET[file]"));
  }

  /**
   * Store the upload process metadata.
   *
   * Caches various metadata to do with the upload, including the upload
   * mappings and the error count. This action is called by the JavaScript
   * code responsible for a chunked upload, before the upload actually starts.
   */
  public function cache_upload_metadata() {
    $this->authenticate();
    $metadata = array_merge($_POST);
    if (isset($metadata['mappings'])) {
      $metadata['mappings'] = json_decode($metadata['mappings'], TRUE);
    }
    if (isset($metadata['settings'])) {
      $metadata['settings'] = json_decode($metadata['settings'], TRUE);
    }
    if (isset($metadata['existingDataLookups'])) {
      $metadata['existingDataLookups'] = json_decode($metadata['existingDataLookups'], TRUE);
    }
    if (isset($metadata['importMergeFields'])) {
      $metadata['importMergeFields'] = json_decode($metadata['importMergeFields'], TRUE);
    }
    if (isset($metadata['synonymProcessing'])) {
      $metadata['synonymProcessing'] = json_decode($metadata['synonymProcessing'], TRUE);
    }

    // The metadata can also hold auth tokens and user_id, though they do not
    // need decoding.
    $this->internalCacheUploadMetadata($metadata);
    echo "OK";
  }

  private function codeToMessage($code) {
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

  /**
   * Saves a set of metadata for an upload to a file.
   *
   * Allows the metadata to persist across requests.
   */
  private function internalCacheUploadMetadata($metadata) {
    $previous = $this->getMetadata($_GET['uploaded_csv']);
    if ($previous) {
      $metadata = array_merge($previous, $metadata);
    }
    $this->auto_render = FALSE;
    $mappingFile = str_ireplace(['.csv', '.xlsx', '.xls'], '-metadata.txt', $_GET['uploaded_csv']);
    $mappingHandle = fopen(DOCROOT . "import/$mappingFile", "w");
    fwrite($mappingHandle, json_encode($metadata));
    fclose($mappingHandle);
  }

  /**
   * Saves a set of meanings for an upload to a file, so it can persist across requests.
   * This is the mapping between the synonym identifier column and the indicia meaning id.
   */
  private function cacheStoredMeanings($meanings) {
    $previous = $this->retrieveCachedStoredMeanings();
    $meanings = array_merge($previous, $meanings);
    $meaningsFile = str_ireplace(['.csv', '.xlsx', '.xls'], '-meanings.txt', $_GET['uploaded_csv']);
    $meaningsHandle = fopen(DOCROOT . "import/$meaningsFile", "w");
    fwrite($meaningsHandle, json_encode($meanings));
    fclose($meaningsHandle);
  }

  /**
   * Internal function that retrieves the meanings for a CSV upload.
   */
  private function retrieveCachedStoredMeanings() {
    $meaningsFile = DOCROOT . "import/" . str_ireplace(['.csv', '.xlsx', '.xls'], '-meanings.txt', $_GET['uploaded_csv']);
    if (file_exists($meaningsFile)) {
      $meaningsHandle = fopen($meaningsFile, "r");
      $meanings = fgets($meaningsHandle);
      fclose($meaningsHandle);
      return json_decode($meanings, TRUE);
    }
    else {
      // No previous file, so create default new metadata.
      return array();
    }
  }

  /**
   * Determines if the provided module has been activated in the configuration.
   *
   * @param string $module
   *   Name of the module.
   *
   * @return bool
   *   TRUE if the module is active.
   */
  private function checkModuleActive($module) {
    $config = kohana::config_load('core');
    foreach ($config['modules'] as $path) {
      if (strlen($path) >= strlen($module) &&
        substr_compare($path, $module, strlen($path) - strlen($module), strlen($module), TRUE) === 0
      ) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Controller action that performs the import of data in an uploaded file.
   *
   * Allows $_GET parameters to specify the filepos, offset and limit when
   * uploading just a chunk at a time. This method is called to perform the
   * entire upload when JavaScript is not enabled, or can be called to perform
   * part of an AJAX csv upload where only a part of the data is imported on
   * each call.
   * Requires a $_GET parameter for uploaded_csv - the uploaded file name.
   */
  public function upload() {
    $allowCommitToDB = isset($_GET['allow_commit_to_db']) ? $_GET['allow_commit_to_db'] : TRUE;
    $importTempFile = DOCROOT . "import/" . $_GET['uploaded_csv'];
    $ext = $this->getFileExt($_GET['uploaded_csv']);
    $metadata = $this->getMetadata($_GET['uploaded_csv']);
    if (!empty($metadata['user_id'])) {
      global $remoteUserId;
      $remoteUserId = $metadata['user_id'];
    }
    // Check if details of the last supermodel (e.g. sample for an occurrence)
    // are in the cache from a previous iteration of this bulk operation.
    $cache = Cache::instance();
    $this->getPreviousRowSupermodel($cache);
    // Enable caching of things like language lookups.
    ORM::$cacheFkLookups = TRUE;
    // Make sure the file still exists.
    if (file_exists($importTempFile)) {
      $tm = microtime(TRUE);
      // Following helps for files from Macs.
      ini_set('auto_detect_line_endings', 1);
      $model = ORM::Factory($_GET['model']);
      $supportsImportGuid = in_array('import_guid', array_keys($model->as_array()));
      // Create an error file pointer.
      $existingNoOfProblemsColIdx = FALSE;
      $existingProblemColIdx = FALSE;
      $existingErrorRowNoColIdx = FALSE;
      $existingImportGuidColIdx = FALSE;
      $errorHandle = $this->getErrorFileHandle($importTempFile, $supportsImportGuid,
        $existingNoOfProblemsColIdx, $existingProblemColIdx, $existingErrorRowNoColIdx, $existingImportGuidColIdx);
      // Create the import file pointer.
      $limit = (isset($_GET['limit']) ? $_GET['limit'] : FALSE);
      $filepos = (isset($_GET['filepos']) ? $_GET['filepos'] : 0);
      $offset = (isset($_GET['offset']) ? $_GET['offset'] : 0);
      $file = $this->openSpreadsheet($importTempFile, $metadata, $filepos, $offset, $limit);
      $count = 0;
      $this->submissionStruct = $model->get_submission_structure();

      // Check if the conditions for special field processing are met - all the
      // columns are in the mapping.
      $specialFieldProcessing = array();
      if (isset($model->specialImportFieldProcessingDefn)) {
        foreach ($model->specialImportFieldProcessingDefn as $column => $defn) {
          $columns = array();
          $index = 0;
          foreach ($metadata['mappings'] as $col => $attr) {
            if ($col !== 'RememberAll' && substr($col, -9) !== '_Remember' && $col != 'AllowLookup') {
              if (in_array($attr, $defn['columns'])) {
                $columns[$attr] = TRUE;
              }
            }
            $index++;
          }
          // The genus, specific name and qualifier are all merge fields.
          // However the qualifier is not mandatory, so if a qualifier is not specified, we effectively tell the system it has been specified
          // so that the system doesn't ask for it. Ideally this code should be generalised going forward.
          if ((in_array('taxon:taxon:genus',$metadata['mappings']) || in_array('taxon:taxon:specific',$metadata['mappings'])) &&  !in_array('taxon:taxon:qualifier',$metadata['mappings'])) {
            $columns['taxon:taxon:qualifier'] = TRUE;
          }
          if (count($defn['columns']) === count(array_keys($columns))) {
            $specialFieldProcessing[$column] = TRUE;
          }
        }
      }
      $specialMergeProcessing = array();
      if (isset($metadata['importMergeFields']) && is_string($metadata['importMergeFields'])) {
        $metadata['importMergeFields'] = json_decode($metadata['importMergeFields'], TRUE);
      }
      if (isset($metadata['synonymProcessing']) && is_string($metadata['synonymProcessing'])) {
        $metadata['synonymProcessing'] = json_decode($metadata['synonymProcessing'], TRUE);
      }
      if (isset($metadata['importMergeFields'])) {
        // Only do the special merge processing if all the required fields are
        // there, and if there are no required then if one of the optional ones
        // are there.
        foreach ($metadata['importMergeFields'] as $modelSpec) {
          if (!isset($modelSpec['model']) || ($modelSpec['model'] = $_GET['model'])) {
            foreach ($modelSpec['fields'] as $fieldSpec) {
              $foundAllRequired = TRUE;
              $foundOne = FALSE;
              foreach ($fieldSpec['virtualFields'] as $subFieldSpec) {
                if (in_array($fieldSpec['fieldName'] . ':' . $subFieldSpec['fieldNameSuffix'], $metadata['mappings'])) {
                  $foundOne = TRUE;
                }
                elseif (isset($subFieldSpec['required']) && $subFieldSpec['required']) {
                  $foundAllRequired = FALSE;
                }
              }
              if ($foundOne && $foundAllRequired) {
                $specialMergeProcessing[] = $fieldSpec;
              }
            }
          }
        }
      }
      $storedMeanings = $this->retrieveCachedStoredMeanings();
      while (($limit === FALSE || $count < $limit) && ($data = $this->getNextRow($file, $count + $offset + 1, $metadata))) {
        $count++;
        if (!array_filter($data)) {
          // Skip empty rows.
          continue;
        }
        // Can't just clear the model, as clear does not do a full reset -
        // leaves related entries: important for location joinsTo websites.
        $model = ORM::Factory($_GET['model']);
        $index = 0;
        $saveArray = $model->getDefaults();
        // Note, the mappings will always be in the same order as the columns
        // of the CSV file.
        foreach ($metadata['mappings'] as $col => $attr) {
          // Skip cols to do with remembered mappings.
          if ($col !== 'RememberAll' && substr($col, -9) !== '_Remember' && $col != 'AllowLookup') {
            if (isset($data[$index])) {
              // '<Please select>' is a value fixed in
              // import_helper::model_field_options.
              if ($attr != '<Please select>' && $data[$index] !== '') {
                // Add the data to the record save array. Utf8 encode if file
                // does not have UTF8 BOM.
                $saveArray[$attr] = $metadata['isUtf8'] ? $data[$index] : utf8_encode($data[$index]);
              }
            }
            else {
              // This is one of our static fields at the end.
              $saveArray[$col] = $attr;
            }
            $index++;
          }
        }
        // The genus, specific name and qualifier are all merge fields.
        // However the qualifier is not mandatory, so if a qualifier is not specified, we effectively tell the system it has been specified
        // so that the system doesn't ask for it. Ideally this code should be generalised going forward.
        if ((array_key_exists('taxon:taxon:genus',$saveArray) || array_key_exists('taxon:taxon:specific',$saveArray)) &&  !array_key_exists('taxon:taxon:qualifier',$saveArray)) {
          $saveArray['taxon:taxon:qualifier'] = '';
        }
        foreach (array_keys($specialFieldProcessing) as $col) {
          if (!isset($saveArray[$col]) || $saveArray[$col] == '') {
            $saveArray[$col] = vsprintf(
              $model->specialImportFieldProcessingDefn[$col]['template'],
              array_map(function ($column) use ($saveArray) {
                return $saveArray[$column];
              },
              $model->specialImportFieldProcessingDefn[$col]['columns'])
            );
            foreach ($model->specialImportFieldProcessingDefn[$col]['columns'] as $column) {
              unset($saveArray[$column]);
            }
          }
        }
        foreach ($specialMergeProcessing as $fieldSpec) {
          $merge = array();
          foreach ($fieldSpec['virtualFields'] as $subFieldSpec) {
            $col = $fieldSpec['fieldName'] . ':' . $subFieldSpec['fieldNameSuffix'];
            if (isset($saveArray[$col])) {
              if ($saveArray[$col] !== '') {
                $merge[] = (isset($subFieldSpec['dataPrefix']) ? $subFieldSpec['dataPrefix'] : '') .
                  $saveArray[$col] .
                  (isset($subFieldSpec['dataSuffix']) ? $subFieldSpec['dataSuffix'] : '');
              }
              unset($saveArray[$col]);
            }
          }
          if (count($merge) > 0) {
            $saveArray[$fieldSpec['fieldName']] = implode(str_replace('<newline>', "\r\n", (isset($fieldSpec['joiningString']) ? $fieldSpec['joiningString'] : '')), $merge);
          }
        }
        // Copy across the fixed values, including the website id, into the
        // data to save.
        if ($metadata['settings']) {
          $saveArray = array_merge($metadata['settings'], $saveArray);
        }
        if (!empty($saveArray['website_id'])) {
          // Sutomatically join to the website if relevant.
          if (isset($this->submissionStruct['joinsTo']) && in_array('websites', $this->submissionStruct['joinsTo'])) {
            $saveArray['joinsTo:website:' . $saveArray['website_id']] = 1;
          }
        }
        if ($supportsImportGuid) {
          if ($existingImportGuidColIdx === FALSE) {
            // Save the import guid  in a field so the results of each
            // individual upload can be grouped together. Relies on the model
            // being imported into having a text field called import_guid
            // otherwise it's just ignored.
            $saveArray['import_guid'] = $metadata['guid'];
          }
          else {
            // This is a reimport of error records which want to link back to
            // the original import. So use the original GUID as supplied in the
            // data rather than the uploaded file name.
            $saveArray['import_guid'] = $data[$existingImportGuidColIdx];
          }
        }
        // Check if in an association situation.
        $associationExists = FALSE;
        $originalRecordPrefix = $this->submissionStruct['model'];
        $originalAttributePrefix = (isset($model->attrs_field_prefix) ? $model->attrs_field_prefix : '');
        $originalMediaPrefix = $originalRecordPrefix . '_media';
        if ($this->checkModuleActive($this->submissionStruct['model'] . '_associations')) {
          // Assume model has attributes.
          $associatedSuffix = '_2';
          $associatedRecordSubmissionStructure = $this->submissionStruct;
          $associatedRecordPrefix = $originalRecordPrefix . $associatedSuffix;
          $associatedAttributePrefix = $originalAttributePrefix . $associatedSuffix;
          $associatedMediaPrefix = $originalMediaPrefix . $associatedSuffix;
          $associationRecordPrefix = $originalRecordPrefix . '_association';
          // Find out if association or associated records exist - do this if a
          // species lookup value is filled in.
          // This restricts to occurrence/taxa associations.
          foreach ($saveArray as $assocField => $assocValue) {
            $associationExists = $associationExists || (!empty($assocValue) &&
                preg_match("/^$associatedRecordPrefix:fk_taxa_taxon_list/", $assocField));
          }
        }
        // Clear the model, so nothing else from the previous row carries over.
        $model->clear();
        // If main model ID is set in imported data, then we MUST be importing
        // into an existing record. Import file can't create arbitrary new IDs.
        $mustExist = FALSE;
        if (!empty($saveArray["$_GET[model]:id"])) {
          // Test that if an import contains an ID for the main model, that it
          // is used for a lookup of existing records.
          $ok = FALSE;
          if (!empty($metadata['mappings']['lookupSelect' . $_GET['model']])) {
            $lookupFields = json_decode($metadata['mappings']['lookupSelect' . $_GET['model']]);
            foreach ($lookupFields as $lookupField) {
              if ($lookupField->fieldName === "$_GET[model]:id") {
                $ok = TRUE;
              }
            }
          }
          if (!$ok) {
            $this->logError(
              $data, 1, 'ID specified in import row but not being used to lookup an existing record.',
              $existingNoOfProblemsColIdx, $existingProblemColIdx, $existingErrorRowNoColIdx,
              $errorHandle, $count + $offset + 1,
              $supportsImportGuid && $existingImportGuidColIdx === FALSE ? $metadata['guid'] : '',
              $metadata
            );
            // Get file position here otherwise the fgetcsv in the while loop
            // will move it one record too far.
            if ($ext === 'csv') {
              $filepos = ftell($file);
            }
            continue;
          }
          $mustExist = TRUE;
        }
        // If a possible previous record, attempt to find the relevant IDs.
        if (isset($metadata['mappings']['lookupSelect' . $_GET['model']]) && $metadata['mappings']['lookupSelect' . $_GET['model']] !== '') {
          try {
            $this->mergeExistingRecordIds($_GET['model'], $originalRecordPrefix, $originalAttributePrefix, '', $metadata,
              $mustExist, $model, $saveArray);
          }
          catch (Exception $e) {
            $this->logError(
              $data, 1, $e->getMessage(),
              $existingNoOfProblemsColIdx, $existingProblemColIdx, $existingErrorRowNoColIdx,
              $errorHandle, $count + $offset + 1,
              $supportsImportGuid && $existingImportGuidColIdx === FALSE ? $metadata['guid'] : '',
              $metadata
            );
            // Get file position here otherwise the fgetcsv in the while loop
            // will move it one record too far.
            if ($ext === 'csv') {
              $filepos = ftell($file);
            }
            continue;
          }
        }
        // Check if we can use a existing data relationship to workmout if this
        // is a new or old record. If posting a supermodel, are the details of
        // the supermodel the same as for the previous CSV row? If so, we can
        // link to that record rather than create a new supermodel record.
        // If not, then potentially lookup existing record in the database.
        $updatedPreviousCsvSupermodelDetails = $this->checkForSameSupermodel($saveArray, $model, $associationExists, $metadata);
        if ($associationExists && isset($metadata['mappings']['lookupSelect' . $associatedRecordPrefix]) && $metadata['mappings']['lookupSelect' . $associatedRecordPrefix] !== '') {
          $assocModel = ORM::Factory($_GET['model']);
          $this->mergeExistingRecordIds($_GET['model'], $associatedRecordPrefix, $associatedAttributePrefix, $associatedSuffix,
            $metadata, FALSE, $assocModel, $saveArray);
          if (isset($saveArray[$originalRecordPrefix . ':id']) && isset($saveArray[$associatedRecordPrefix . ':id'])) {
            $assocModel = ORM::Factory($associationRecordPrefix)
              ->where([
                'from_' . $_GET['model'] . '_id' => $saveArray[$originalRecordPrefix . ':id'],
                'to_' . $_GET['model'] . '_id' => $saveArray[$associatedRecordPrefix . ':id'],
                'association_type_id' => $saveArray[$associationRecordPrefix . ':association_type_id'],
                'deleted' => 'f',
              ])->find();
            if ($assocModel->loaded === TRUE) {
              $saveArray[$associationRecordPrefix . ':id'] = $assocModel->id;
            }
          }
        }

        // Special case for samples: the sref system is fixed for the file, but is not required in location_id provided
        $fkLocationSet = !empty($saveArray['sample:fk_location']);
        foreach ($saveArray as $field => $value) {
          $fkLocationSet |= (!empty($value) && strpos($field, 'sample:fk_location:') !== FALSE);
        }
        if ($fkLocationSet && empty($saveArray['sample:entered_sref'])) {
          unset($saveArray['sample:entered_sref']);
          unset($saveArray['sample:entered_sref_system']);
        }

        // Save the record.
        $model->set_submission_data($saveArray, TRUE);
        /*
        At this point, if model has associations (i.e. a module is active
        called <modelSingular>_associations) we flip the submission so the
        model becomes the subModel. This way we can bolt any second
        associated record in, into the submodel array.
         */
        // GvB TODO alter automatic mappings to set up secondary occurrences
        // correctly.
        if ($associationExists && isset($model->submission['superModels']) &&
          is_array($model->submission['superModels']) &&
          count($model->submission['superModels']) === 1
        ) {
          // We are assuming only one superModel, which must exist at this
          // point. Use key 'record1' into the subModel array so association
          // record knows which is which.
          // We are using the previously wrapped superModel.
          unset($associatedRecordSubmissionStructure['superModels']);
          // Flip then bolt in as second submodel to the supermodel using key
          // 'record2'.
          $submissionData = $model->submission;
          $superModelSubmission = $submissionData['superModels'][0]['model'];
          $superModelFK = $submissionData['superModels'][0]['fkId'];
          $superModel = ORM::Factory($superModelSubmission['id']);
          $superModel->clear();
          unset($submissionData['superModels']);
          // Try to wrap second record of original model.
          // As the submission builder needs a 1-1 match between field prefix
          // and model name, we need to generate an altered saveArray.
          $associatedArray = array();
          foreach ($saveArray as $fieldname => $value) {
            $parts = explode(':', $fieldname);
            // Filter out original model feilds, any of its attributes and
            // media records.
            if ($parts[0] != $originalRecordPrefix &&
              $parts[0] != $originalAttributePrefix &&
              $parts[0] != $originalMediaPrefix
            ) {
              if ($parts[0] == $associatedRecordPrefix) {
                $parts[0] = $originalRecordPrefix;
              }
              else {
                if ($parts[0] == $associatedAttributePrefix) {
                  $parts[0] = $originalAttributePrefix;
                }
                else {
                  if ($parts[0] == $associatedMediaPrefix) {
                    $parts[0] = $originalMediaPrefix;
                  }
                }
              }
              $associatedArray[implode(':', $parts)] = $value;
            }
          }
          $associatedSubmission = submission_builder::build_submission($associatedArray, $associatedRecordSubmissionStructure);
          // Map fk_* fields to the looked up id.
          $associatedSubmission = $model->getFkFields($associatedSubmission, $associatedArray);
          // Wrap the association and bolt in as a submodel of original model,
          // using '||record2||' pointer.
          $association = ORM::Factory($associationRecordPrefix);
          $association->set_submission_data($saveArray, TRUE);
          if (!isset($association->submission['fields']['to_' . $associatedRecordSubmissionStructure['model'] . '_id'])) {
            $association->submission['fields']['to_' . $associatedRecordSubmissionStructure['model'] . '_id'] = array('value' => '||record2||');
          }
          $submissionData['subModels'] = array(
            array(
              'fkId' => 'from_' . $associatedRecordSubmissionStructure['model'] . '_id',
              'model' => $association->submission,
            ),
          );
          $superModelSubmission['subModels'] =
            array(
              'record1' => array('fkId' => $superModelFK, 'model' => $submissionData),
              'record2' => array('fkId' => $superModelFK, 'model' => $associatedSubmission),
            );
          $superModel->submission = $superModelSubmission;
          $modelToSubmit = $superModel;
        }
        else {
          $associationExists = FALSE;
          $modelToSubmit = $model;
        }
        $mainOrSynonym = FALSE;
        $error = FALSE;
        if (isset($metadata['synonymProcessing']) && isset($metadata['synonymProcessing']['separateSynonyms']) &&
            $metadata['synonymProcessing']['separateSynonyms'] && isset($saveArray['synonym:tracker'])) {
          $mainOrSynonym = "maybe";
          $modelToSubmit->process_synonyms = FALSE;
          if (isset($metadata['synonymProcessing']['synonymValues'])) {
            foreach ($metadata['synonymProcessing']['synonymValues'] as $synonymValue) {
              if ($saveArray['synonym:tracker'] === $synonymValue) {
                $mainOrSynonym = "synonym";
              }
            }
          }
          if (isset($metadata['synonymProcessing']['mainValues'])) {
            foreach ($metadata['synonymProcessing']['mainValues'] as $mainValue) {
              if ($saveArray['synonym:tracker'] === $mainValue) {
                $mainOrSynonym = "main";
              }
            }
          }
          if (!isset($saveArray['synonym:identifier']) || $saveArray['synonym:identifier'] === '') {
            $error = "Could not identify field to group synonyms with.";
          }
          if ($mainOrSynonym === "maybe") {
            $error = "Could not identify whether record is main record or synonym : " . $saveArray['synonym:tracker'];
          }
        }
        if (!$error && $mainOrSynonym === "synonym") {
          $modelToSubmit->submission['fields']['preferred']['value'] = 'f';
          if (array_key_exists($saveArray['synonym:identifier'], $storedMeanings)) {
            // Meaning is held on supermodel.
            foreach ($modelToSubmit->submission['superModels'] as $idx => $superModel) {
              if ($superModel['model']['id'] == 'taxon_meaning' && !isset($superModel['model']['fields']['id'])) {
                $modelToSubmit->submission['superModels'][$idx]['model']['fields']['id'] = array(
                  'value' => $storedMeanings[$saveArray['synonym:identifier']],
                );
              }
            }
          }
          else {
            $error = "Synonym appears in file before equivalent main record : " . $saveArray['synonym:identifier'];
          }
        }
        if (!$error && $mainOrSynonym === "main") {
          if (array_key_exists($saveArray['synonym:identifier'], $storedMeanings)) {
            $error = "Main record appears more than once : " . $saveArray['synonym:identifier'];
          }
        }
        if (!$error) {
          if (($id = $modelToSubmit->submit()) == NULL) {
            // Record has errors - now embedded in model, so dump them into the
            // error file.
            $errors = [];
            foreach ($modelToSubmit->getAllErrors() as $field => $msg) {
              $fldTitle = array_search($field, $metadata['mappings']);
              $fldTitle = $fldTitle ? $fldTitle : $field;
              $errors[] = "$fldTitle: $msg";
            }
            $errors = array_unique($errors);
            $this->logError(
              $data, count($errors), implode("\n", $errors),
              $existingNoOfProblemsColIdx, $existingProblemColIdx, $existingErrorRowNoColIdx,
              $errorHandle, $count + $offset + 1,
              $supportsImportGuid && $existingImportGuidColIdx === FALSE ? $metadata['guid'] : '',
              $metadata
            );
          }
          else {
            // Now the record has successfully posted, we need to store the
            // details of any new supermodels and their Ids, in case they are
            // duplicated in the next csv row.
            $this->previousCsvSupermodel['details'] = array_merge($this->previousCsvSupermodel['details'], $updatedPreviousCsvSupermodelDetails);
            $this->captureSupermodelIds($modelToSubmit, $associationExists);
            if ($mainOrSynonym === "main") {
              // In case of a main record, store the meaning id.
              $storedMeanings[$saveArray['synonym:identifier']] = $this->previousCsvSupermodel['id']['taxon_meaning'];
            }
          }
        }
        else {
          $error = "Could not identify whether record is main record or synonym : " . $saveArray['synonym:tracker'];
          $this->logError(
            $data, 1, $error,
            $existingNoOfProblemsColIdx, $existingProblemColIdx, $existingErrorRowNoColIdx,
            $errorHandle, $count + $offset + 1,
            $supportsImportGuid && $existingImportGuidColIdx === FALSE ? $metadata['guid'] : '',
            $metadata
          );
        }
        // Get file position here otherwise the fgetcsv in the while loop will
        // move it one record too far.
        if ($ext === 'csv') {
          $filepos = ftell($file);
        }
      }
      // Get percentage progress, based on file size (CSV) or rows done
      // (PHPSpreadsheet).
      $progress = $ext === 'csv' ? ($filepos * 100 / $metadata['fileSize']) : (($count + $offset) * 100 / $metadata['fileSize']);
      $r = json_encode([
        'uploaded' => $count,
        'progress' => $progress,
        'filepos' => $filepos,
        'errorCount' => $metadata['errorCount'],
      ]);
      // Allow for a JSONP cross-site request.
      if (array_key_exists('callback', $_GET)) {
        $r = $_GET['callback'] . "(" . $r . ")";
        header('Content-Type: application/javascript');
      }
      else {
        header('Content-Type: application/json');
      }
      echo $r;
      $this->closeFile($file);
      fclose($errorHandle);
      $this->internalCacheUploadMetadata($metadata);
      $this->cacheStoredMeanings($storedMeanings);

      // An AJAX upload request will just receive the number of records
      // uploaded and progress.
      $this->auto_render = FALSE;
      if (!empty($allowCommitToDB) && $allowCommitToDB) {
        $cache->set(basename($importTempFile) . 'previousSupermodel', $this->previousCsvSupermodel);
      }
      if (class_exists('request_logging')) {
        request_logging::log('i', 'import', NULL, 'upload',
          empty($saveArray['website_id']) ? NULL : $saveArray['website_id'],
          security::getUserId(), $tm);
      }
    }
  }

  /**
   * Adds an error to the error log file.
   */
  private function logError(
      $data,
      $noOfProblems,
      $error,
      $existingNoOfProblemsColIdx,
      $existingProblemColIdx,
      $existingErrorRowNoColIdx,
      $errorHandle,
      $total,
      $importGuidToAppend,
      &$metadata) {
    if ($existingNoOfProblemsColIdx === FALSE) {
      $data[] = $noOfProblems;
    }
    else {
      $data[$existingNoOfProblemsColIdx] = $error;
    }
    if ($existingProblemColIdx === FALSE) {
      $data[] = $error;
    }
    else {
      $data[$existingProblemColIdx] = $error;
    }
    if ($existingErrorRowNoColIdx === FALSE) {
      // + 1 for header.
      $data[] = $total;
    }
    else {
      $data[$existingErrorRowNoColIdx] = $total;
    }
    if ($importGuidToAppend) {
      $data[] = $importGuidToAppend;
    }
    fputcsv($errorHandle, $data);
    $metadata['errorCount'] = $metadata['errorCount'] + 1;
  }

  /**
   * If there is an existing record to lookup, merge its IDs with the data row.
   */
  private function mergeExistingRecordIds(
      $modelName,
      $fieldPrefix,
      $attrPrefix,
      $assocSuffix,
      $metadata,
      $mustExist,
      &$model,
      &$saveArray,
      $setSupermodel = FALSE) {
    $join = "";
    $table = inflector::plural($modelName);
    $fields = json_decode($metadata['mappings']['lookupSelect' . $fieldPrefix]);
    $fields = array_map(
      function ($field) {
        return $field->fieldName;
      }, $fields);
    $join = self::buildJoin($fieldPrefix, $fields, $table, $saveArray);
    $wheres = $model->buildWhereFromSaveArray($saveArray, $fields, "(" . $table . ".deleted = 'f')", $in, $assocSuffix);
    if ($wheres !== FALSE) {
      $db = Database::instance();
      // Have to use a db as this may have a join.
      $existing = $db->query("SELECT DISTINCT $table.id FROM $table $join WHERE " . $wheres)->result_array(FALSE);
      if (count($existing) > 0) {
        // If an previous record exists, we have to check for existing
        // attributes.
        // Note this only works properly on single value attributes.
        $saveArray[$fieldPrefix . ':id'] = $existing[0]['id'];
        if (isset($model->attrs_field_prefix)) {
          if ($setSupermodel) {
            $this->previousCsvSupermodel['attributeIds'][$modelName] = [];
          }
          $attributes = ORM::Factory($modelName . '_attribute_value')
            ->where([$modelName . '_id' => $existing[0]['id'], 'deleted' => 'f'])->find_all();
          foreach ($attributes as $attribute) {
            if ($setSupermodel) {
              $this->previousCsvSupermodel['attributeIds'][$modelName][$attribute->__get($modelName . '_attribute_id')] = $attribute->id;
            }
            if (isset($saveArray[$attrPrefix . ':' . $attribute->__get($modelName . '_attribute_id')])) {
              $saveArray[$attrPrefix . ':' . $attribute->__get($modelName . '_attribute_id') . ':' . $attribute->id] =
                $saveArray[$attrPrefix . ':' . $attribute->__get($modelName . '_attribute_id')];
              unset($saveArray[$attrPrefix . ':' . $attribute->__get($modelName . '_attribute_id')]);
            }
            elseif (isset($saveArray[$attrPrefix . ':fk_' . $attribute->__get($modelName . '_attribute_id')])) {
              $saveArray[$attrPrefix . ':fk_' . $attribute->__get($modelName . '_attribute_id') . ':' . $attribute->id] =
                $saveArray[$attrPrefix . ':fk_' . $attribute->__get($modelName . '_attribute_id')];
              unset($saveArray[$attrPrefix . ':fk_' . $attribute->__get($modelName . '_attribute_id')]);
            }
          }
        }
      }
      elseif ($mustExist) {
        throw new Exception('Importing an existing ID but the row does not already exist.');
      }
    }
  }

  /*
   * Need to build a join so the system works correctly when importing taxa with update existing records selected.
   * e.g. a problematic scenario would happen if importing new taxa but the external key/search code is still selected
   * for existing record update, in this case without building a join, the system would keep overwriting the previous record
   * as each new one is imported (as it wasn't checking the search code/external key, the final result would be that only one row would import).
   * Note this function might need improving/generalising for other models, although I did check occurrence/sample import which
   * did not seem to show the same issue.
   */
  public static function buildJoin($fieldPrefix, $fields, $table, $saveArray) {
    $r = '';
    if (!empty($saveArray['taxon:external_key']) && $table === 'taxa_taxon_lists') {
      $r = "join taxa t on t.id = $table.taxon_id AND t.external_key='" . $saveArray['taxon:external_key'] . "' AND t.deleted=false";
    }
    elseif (!empty($saveArray['taxon:search_code']) && $table === 'taxa_taxon_lists') {
      $r = "join taxa t on t.id = $table.taxon_id AND t.search_code='" . $saveArray['taxon:search_code'] . "' AND t.deleted=false";
    }
    return $r;
  }

  /**
   * Display the end result of an upload.
   *
   * Either displayed at the end of a non-AJAX upload, or redirected to
   * directly by the AJAX code that is performing a chunked upload when the
   * upload completes. Requires a get parameter for the uploaded_csv filename.
   *
   * Echoes JSON containing the problems cound and error file name.
   */
  public function get_upload_result() {
    $this->authenticate('read');
    $metadataFile = str_ireplace(['.csv', '.xlsx', '.xls'], '-metadata.txt', $_GET['uploaded_csv']);
    $errorFile = str_ireplace(['.csv', '.xlsx', '.xls'], '-errors.csv', $_GET['uploaded_csv']);
    $metadata = $this->getMetadata($_GET['uploaded_csv']);
    echo json_encode([
      'problems' => $metadata['errorCount'],
      'file' => url::base() . 'import/' . basename($errorFile),
    ]);
    // Clean up the uploaded file and mapping file, but only remove the error
    // file if no errors, otherwise we make it downloadable.
    if (file_exists(DOCROOT . "import/" . $_GET['uploaded_csv'])) {
      unlink(DOCROOT . "import/" . $_GET['uploaded_csv']);
    }
    if (file_exists(DOCROOT . "import/" . $metadataFile)) {
      unlink(DOCROOT . "import/" . $metadataFile);
    }
    if ($metadata['errorCount'] == 0 && file_exists(DOCROOT . "import/" . $errorFile)) {
      unlink(DOCROOT . "import/" . $errorFile);
    }
    // Clean up cached lookups.
    $cache = Cache::instance();
    $cache->delete_tag('lookup');
  }

  /**
   * Checks for matching supermodels (e.g. samples) between rows.
   *
   * When looping through csv import data, if the import data includes a
   * supermodel (e.g. the sample for an occurrence) then this method checks to
   * see if the supermodel part of the submission is repeated. If so, then
   * rather than create a new record for the supermodel, we just link this new
   * record to the existing supermodel record. E.g. a spreadsheet containing
   * several occurrences in a single sample can repeat the sample details but
   * only one sample gets created. BUT, there are situations (like building an
   * association based submission) where we need to keep the structure, in
   * which case we just set the id, rather than remove all the supermodel
   * entries.
   */
  private function checkForSameSupermodel(&$saveArray, $model, $linkOnly = FALSE, $metadata = []) {
    $updatedPreviousCsvSupermodelDetails = [];
    if (isset($this->submissionStruct['superModels'])) {
      // Loop through the supermodels.
      foreach ($this->submissionStruct['superModels'] as $modelName => $modelDetails) {
        // Meaning models do not get shared across rows - we always generate a
        // new meaning ID.
        if ($modelName == 'taxon_meaning' || $modelName == 'meaning') {
          continue;
        }
        $sm = ORM::factory($modelName);
        $smAttrsPrefix = isset($sm->attrs_field_prefix) ? $sm->attrs_field_prefix : NULL;
        // We are going to build a hash which uniquely identifies everything we
        // know about the current row's supermodel, so we can detect if it
        // changes between rows.
        $hashArray = [];
        // If updating an existing record, then the comparison with supermodel
        // data must include the existing supermodel's key as the import data
        // is only partial.
        if (!empty($saveArray[$model->object_name . ':id'])) {
          $existing = ORM::factory($model->object_name, $saveArray[$model->object_name . ':id']);
          $hashArray[$modelDetails['fk']] = $existing->{$modelDetails['fk']};
        }
        // Look for new import values related to this supermodel to include in
        // our comparison. We must capture both normal and custom attributes.
        foreach ($saveArray as $field => $value) {
          if (substr($field, 0, strlen($modelName) + 1) === "$modelName:"
              || $smAttrsPrefix && substr($field, 0, strlen($smAttrsPrefix) + 1) === "$smAttrsPrefix:") {
            $hashArray[preg_replace("/^$modelName:/", '', $field)] = $value;
          }
        }
        $hash = '';
        // Convert the hash data into a key string we can store and compare.
        foreach ($hashArray as $field => $value) {
          $hash .= "$field|$value|";
        }
        // If we have previously stored a hash for this supermodel, check if
        // they are the same. If so we can get the ID.
        if (isset($this->previousCsvSupermodel['details'][$modelName]) && $this->previousCsvSupermodel['details'][$modelName] == $hash) {
          // The details for this supermodel point to an existing record, so we
          // need to re-use it.
          if ($linkOnly) {
            // Now link the existing supermodel record to the save array.
            $saveArray[$modelName . ':id'] = $this->previousCsvSupermodel['id'][$modelName];
            if (isset($sm->attrs_field_prefix)) {
              if (!isset($this->previousCsvSupermodel['attributeIds'][$modelName])) {
                // Only fetch supermodel attribute data now as this is first
                // time it is used.
                $this->previousCsvSupermodel['attributeIds'][$modelName] = array();
                $smattrs = ORM::factory($modelName . '_attribute_value')->where(array('deleted' => 'f', $modelName . '_id' => $this->previousCsvSupermodel['id'][$modelName]))->find_all();
                foreach ($smattrs as $smattr) {
                  $this->previousCsvSupermodel['attributeIds'][$modelName][$smattr->__get($modelName . '_attribute_id')] = $smattr->id;
                }
              }
              foreach ($this->previousCsvSupermodel['attributeIds'][$modelName] as $smattrId => $smattrValueId) {
                if (isset($saveArray[$sm->attrs_field_prefix . ':' . $smattrId])) {
                  $saveArray[$sm->attrs_field_prefix . ':' . $smattrId . ':' . $smattrValueId] = $saveArray[$sm->attrs_field_prefix . ':' . $smattrId];
                  unset($saveArray[$sm->attrs_field_prefix . ':' . $smattrId]);
                }
                elseif (isset($saveArray[$sm->attrs_field_prefix . ':fk_' . $smattrId])) {
                  $saveArray[$sm->attrs_field_prefix . ':fk_' . $smattrId . ':' . $smattrValueId] = $saveArray[$sm->attrs_field_prefix . ':fk_' . $smattrId];
                  unset($saveArray[$sm->attrs_field_prefix . ':fk_' . $smattrId]);
                }
              }
            }
          }
          else {
            // First, remove the data from the submission array so we don't
            // re-submit it. Although this leaves any attributes of the
            // supermodel in the saveArray, they are ignored without the
            // supermodel itself.
            foreach ($saveArray as $field => $value) {
              if (substr($field, 0, strlen($modelName) + 1) == "$modelName:") {
                unset($saveArray[$field]);
              }
            }
            // Now link the existing supermodel record to the save array.
            $saveArray[$model->object_name . ':' . $modelDetails['fk']] = $this->previousCsvSupermodel['id'][$modelName];
          }
        }
        else {
          // This is a new supermodel (e.g. a new sample for the occurrences).
          $updatedPreviousCsvSupermodelDetails[$modelName] = $hash;
          unset($this->previousCsvSupermodel['attributeIds'][$modelName]);
          // Check if there is lookup for existing data.
          if (isset($metadata['mappings']) && isset($metadata['mappings']['lookupSelect' . $modelName]) && $metadata['mappings']['lookupSelect' . $modelName] !== '') {
            $superModel = ORM::Factory($modelName);
            self::mergeExistingRecordIds($modelName, $modelName, $sm->attrs_field_prefix, '', $metadata, FALSE,
              $superModel, $saveArray, TRUE);
          }
          elseif ($modelName === 'term' && isset($metadata['mappings']) &&
              isset($metadata['mappings']['lookupSelect' . $model->object_name]) &&
              $metadata['mappings']['lookupSelect' . $model->object_name] !== '' &&
              isset($saveArray['term:term'])) {
            // Special case for termlist_terms, and their term supermodel: have
            // to look up using complex query to get the link between the
            // termlist and the term no attributes. No website join. The term
            // and termlist_id have to be provided at this point.
            $db = Database::instance();
            // Have to use a db as this may have a join.
            $existing = $db->query("SELECT tlt.term_id, tlt.meaning_id " .
                      "FROM indicia.termlists_terms tlt " .
                      "JOIN indicia.terms t ON t.id = tlt.term_id AND t.deleted = false " .
                      "JOIN indicia.termlists tl ON tl.id = tlt.termlist_id AND tl.deleted = false " .
                      "WHERE tlt.deleted = false " .
                        "AND t.term='" . $saveArray['term:term'] . "' " .
                        "AND t.language_id = " . $saveArray['term:language_id'] .
                        (isset($saveArray['termlists_term:fk_termlist']) ?
                          " AND tl.title = '" . $saveArray['termlists_term:fk_termlist'] . "'" :
                          " AND tlt.termlist_id = " . $saveArray['termlists_term:termlist_id']))->result_array(FALSE);
            if (count($existing) > 0) {
              // If an previous record exists, we have to check for existing
              // attributes.
              // Note this only works properly on single value attributes.
              $saveArray[$modelName . ':id'] = $existing[0]['term_id'];
              $saveArray['meaning:id'] = $existing[0]['meaning_id'];
              // No attributes for terms.
            }
          }
          elseif ($modelName === 'taxon' && isset($metadata['mappings']) &&
              isset($metadata['mappings']['lookupSelect' . $model->object_name]) &&
              $metadata['mappings']['lookupSelect' . $model->object_name] !== '' &&
              // Taxon info may not be provided if looking up existing record.
              // In which case, skip the lookup.
              !empty($saveArray['taxon:language_id']) &&
              (!empty($saveArray['taxon:taxon']) || !empty($saveArray['taxon:external_key'])  || !empty($saveArray['taxon:search_code']))) {
            // Same for taxa_taxon_lists, and their taxon supermodel: have to
            // look up using complex query to get the link between the
            // taxon_list and the taxon.
            // This has attributes. No website join. The taxon and
            // taxon_list_id have to be provided at this point.
            $fields = json_decode($metadata['mappings']['lookupSelect' . $model->object_name]);
            $fields = array_map(
              function ($field) {
                return $field->fieldName;
              },
              $fields
            );
            $db = Database::instance();
            // Have to use a db as this may have a join.
            $query = "SELECT ttl.taxon_id, ttl.taxon_meaning_id " .
                  "FROM indicia.taxa_taxon_lists ttl " .
                  "JOIN indicia.taxa t ON t.id = ttl.taxon_id AND t.deleted = false " .
                  "JOIN indicia.taxon_lists tl ON tl.id = ttl.taxon_list_id AND tl.deleted = false " .
                  "WHERE ttl.deleted = false " .
                  "AND t.language_id = " . $saveArray['taxon:language_id'] .
                  (isset($saveArray['taxa_taxon_list:fk_taxon_list']) ?
                      " AND tl.title = '" . $saveArray['taxa_taxon_list:fk_taxon_list'] . "'" :
                      " AND ttl.taxon_list_id = " . $saveArray['taxa_taxon_list:taxon_list_id']);
            if (in_array('taxon:taxon', $fields) && isset($saveArray['taxon:taxon'])) {
              $query .= "AND t.taxon='" . $saveArray['taxon:taxon'] . "' ";
              $existing = $db->query($query)->result_array(FALSE);
            }
            elseif (in_array('taxon:external_key', $fields) && isset($saveArray['taxon:external_key'])) {
              $query .= "AND t.external_key ='" . $saveArray['taxon:external_key'] . "' ";
              $existing = $db->query($query)->result_array(FALSE);
            }
            elseif (in_array('taxon:search_code', $fields) && isset($saveArray['taxon:search_code'])) {
              $query .= "AND t.search_code ='" . $saveArray['taxon:search_code'] . "' ";
              $existing = $db->query($query)->result_array(FALSE);
            }
            else {
              $existing = array();
            }
            if (count($existing) > 0) {
              // If an previous record exists, we have to check for existing
              // attributes.
              // Note this only works properly on single value attributes.
              $saveArray[$modelName . ':id'] = $existing[0]['taxon_id'];
              $saveArray['taxon_meaning:id'] = $existing[0]['taxon_meaning_id'];
              // TODO attributes.
            }
          }
        }
      }
    }
    return $updatedPreviousCsvSupermodelDetails;
  }

  /**
   * Find IDs associated with a supermodel (e.g. sample).
   *
   * When saving a model with supermodels, we don't want to duplicate the
   * supermodel record if all the details are the same across 2 spreadsheet
   * rows. So this method captures the ID of the supermodels that we have just
   * posted, in case their details are replicated in the next record. Handles
   * case where the submission has been flipped (associations), and supermodel
   * has been made the main model.
   */
  private function captureSupermodelIds($model, $flipped = FALSE) {
    if ($flipped) {
      // Supermodel is now main model - just look for the ID field...
      $array = $model->as_array();
      $subStruct = $model->get_submission_structure();
      $this->previousCsvSupermodel['id'][$subStruct['model']] = $model->id;
    }
    else {
      if (isset($this->submissionStruct['superModels'])) {
        $array = $model->as_array();
        // Loop through the supermodels.
        foreach ($this->submissionStruct['superModels'] as $modelName => $modelDetails) {
          $id = $modelName . '_id';
          // Expect that the fk field is called fkTable_id (e.g. if the super
          // model is called sample, then the field should be sample_id). If it
          // is not, then we revert to using ORM to find the ID, which incurs a
          // database hit. For this reason as well we don't get any attribute
          // values now, but rather the first time they need to be used.
          $this->previousCsvSupermodel['id'][$modelName] =
            isset($array[$id]) ? $array[$id] : $model->$modelName->id;
        }
      }
    }
  }

  private function createGuid() {
    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
       mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479),
       mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
    );
  }

  /**
   * Return a useful descriptor of file size.
   *
   * For CSV, the file size in bytes which can be compared with filepos to get
   * progress info. For PHPSpreadsheet files, the worksheet's size.
   */
  private function getFileSize($file) {
    $ext = $this->getFileExt($file);
    if ($ext === 'csv') {
      return filesize($file);
    }
    elseif ($ext === 'xlsx') {
      $reader = new Xlsx();
    }
    elseif ($ext === 'xls') {
      $reader = new Xls();
    }
    else {
      throw new exception('Unsupported file type');
    }
    $reader->setReadDataOnly(true);
    // Minimise data read from spreadsheet - first sheet only.
    $worksheetData = $reader->listWorksheetInfo($file);
    if (count($worksheetData) === 0) {
      throw new exception('Spreadsheet contains no worksheets');
    }
    return $worksheetData[0]['totalRows'] - 1;
  }

  /**
   * Internal function that retrieves the metadata for a CSV upload.
   *
   * For AJAX requests, this comes from a cached file. For normal requests, the
   * mappings should be in the $_POST data.
   */
  private function getMetadata($importTempFile) {
    $metadataFile = DOCROOT . 'import' . DIRECTORY_SEPARATOR  . str_ireplace(['.csv', '.xlsx', '.xls'], '-metadata.txt', $importTempFile);
    if (file_exists($metadataFile)) {
      $metadataHandle = fopen($metadataFile, "r");
      $fileContents = fgets($metadataHandle);
      fclose($metadataHandle);
      $metadata = json_decode($fileContents, TRUE);
    }
    else {
      // No previous file, so create default new metadata.
      $metadata = [
        'mappings' => [],
        'settings' => [],
        'errorCount' => 0,
        'guid' => $this->createGuid(),
      ];
    }
    if (empty($metadata['fileSize']) && file_exists(DOCROOT . 'import' . DIRECTORY_SEPARATOR  . $importTempFile)) {
      // If file uploaded, we can report it's size.
      $metadata['fileSize'] = $this->getFileSize(DOCROOT . 'import' . DIRECTORY_SEPARATOR . $importTempFile);
    }
    return $metadata;
  }

  /**
   * Retrieve the file handle of the error file.
   *
   * During a csv upload, this method is called to retrieve a resource handle
   * to a file that can contain errors during the upload. The file is created
   * if required, and the headers from the uploaded csv file (referred to by
   * handle) are copied into the first row of the new error file along with a
   * header for the problem description and row number.
   *
   * @param string $importTempFile
   *   File name of the imported CSV file.
   * @param resource $importFile
   *   Either CSV file handle or PHPSpreadsheet activeSheet instance.
   * @param bool $supportsImportGuid
   *   True if the model supports tracking imports by GUID, therefore the error
   *   file needs to link the error row to its original GUID.
   * @param int $existingNoOfProblemsColIdx
   *   Returns the column index that the current row's number of problems is in.
   * @param int $existingProblemColIdx
   *   Returns the column index that the current row's error message is in.
   * @param int $existingProblemRowNoColIdx
   *   Returns the column index that the current row's error source row number
   *   is in.
   * @param int $existingImportGuidColIdx
   *   Returns the column index that the current row's import GUID is in.
   *
   * @return resource
   *   The error file's handle.
   */
  private function getErrorFileHandle($importTempFile,
                                      $supportsImportGuid,
                                      &$existingNoOfProblemsColIdx,
                                      &$existingProblemColIdx,
                                      &$existingProblemRowNoColIdx,
                                      &$existingImportGuidColIdx) {
    $errorFile = str_ireplace(['.csv', '.xlsx', '.xls'], '-errors.csv', $importTempFile);
    $needHeaders = !file_exists($errorFile);
    $errorHandle = fopen($errorFile, 'a');
    $existingImportGuidColIdx = FALSE;
    if ($needHeaders) {
      $headers = $this->getColumns($importTempFile);
      $existingNoOfProblemsColIdx = array_search('Number of problems', $headers);
      if ($existingNoOfProblemsColIdx === FALSE) {
        $headers[] = 'Number of problems';
      }
      $existingProblemColIdx = array_search('Problem description', $headers);
      if ($existingProblemColIdx === FALSE) {
        $headers[] = 'Problem description';
      }
      $existingProblemRowNoColIdx = array_search('Row no.', $headers);
      if ($existingProblemRowNoColIdx === FALSE) {
        $headers[] = 'Row no.';
      }
      if ($supportsImportGuid) {
        $existingImportGuidColIdx = array_search('Import ID', $headers);
        if ($existingImportGuidColIdx === FALSE) {
          // If not re-importing errors, store the file ID as an import guid in
          // the errors, to link errors to their original import.
          $headers[] = 'Import ID';
        }
      }
      fputcsv($errorHandle, $headers);
    }
    return $errorHandle;
  }

  /**
   * Retrieves the supermodel (e.g. sample) associated with the last row.
   *
   * Runs at the start of each batch of rows. Checks if the previous imported
   * row defined a supermodel. If so, we'll load it from the Kohana cache. This
   * allows us to determine if the new row can link to the same supermodel or
   * not. An example would be linking several occurrences to the same sample.
   *
   * @param object $cache
   *   Cache object.
   */
  private function getPreviousRowSupermodel($cache) {
    $this->previousCsvSupermodel = $cache->get(basename($_GET['uploaded_csv']) . 'previousSupermodel');
    if (!$this->previousCsvSupermodel) {
      $this->previousCsvSupermodel = array(
        'id' => [],
        'details' => [],
        'attributeIds' => [],
      );
    }
  }

  /**
   * Checks if there is a byte order marker at the beginning of the file (BOM).
   *
   * If so, sets this information in the $metadata. Rewinds the file to the
   * beginning.
   *
   * @param array $metadata
   *   Import metadata information.
   * @param resource $handle
   *   File handle.
   */
  private function checkIfUtf8(array &$metadata, $handle) {
    if (!isset($metadata['isUtf8'])) {
      fseek($handle, 0);
      $bomCheck = fread($handle, 3);
      // Flag if this file has a UTF8 BOM at the start.
      $metadata['isUtf8'] = $bomCheck === chr(0xEF) . chr(0xBB) . chr(0xBF);
    }
  }

  private function openSpreadsheet($fileName, &$metadata, $filepos, $offset, $limit) {
    $ext = $this->getFileExt($fileName);
    if ($ext === 'csv') {
      $handle = fopen($fileName, "r");
      $this->checkIfUtf8($metadata, $handle);
      if ($filepos == 0) {
        // First row, so skip the header.
        fseek($handle, 0);
        fgetcsv($handle, 10000, ",");
        // Also clear the lookup cache.
        $cache = new Cache();
        $cache->delete_tag('lookup');
      }
      else {
        fseek($handle, $filepos);
      }
      return $handle;
    }
    elseif ($ext === 'xlsx') {
      $reader = new Xlsx();
    }
    elseif ($ext === 'xls') {
      $reader = new Xls();
    }
    else {
      throw new exception('Unsupported file type');
    }
    $metadata['isUtf8'] = TRUE;
    // @todo Check the following doesn't damage dates.
    $reader->setReadDataOnly(true);
    // Minimise data read from spreadsheet - first sheet only.
    $worksheetData = $reader->listWorksheetInfo($fileName);
    if (count($worksheetData) === 0) {
      throw new exception('Spreadsheet contains no worksheets');
    }
    $reader->setLoadSheetsOnly($worksheetData[0]['worksheetName']);
    // Limit rows read. First row to read is row 2.
    $reader->setReadFilter(new RangeReadFilter($offset + 2, $limit));
    $file = $reader->load($fileName);
    return $file->getActiveSheet()->toArray();
  }

  /**
   * Reads the next row from the data file.
   *
   * @param resource $file
   *   File handle (CSV) or data array (PHPSpreadsheet).
   * @param int $row
   *   Row to fetch (PHPSpreadsheet). For CSV just reads the next from the file
   *   pointer.
   * @param array $metadata
   *   Metadata including file size info.
   *
   * @return array
   *   Data array.
   */
  private function getNextRow($file, $row, $metadata) {
    if (is_array($file)) {
      return ($row <= $metadata['fileSize']) ? $file[$row] : FALSE;
    }
    else {
      return fgetcsv($file, 10000, ",");
    }
  }

  /**
   * Closes the file.
   *
   * If CSV, calls fclose on the handle. For PHPSpreadsheet files nothing to
   * do.
   *
   * @file
   *   File information (handle or data read via PHPSpreadsheet).
   */
  private function closeFile($file) {
    if (!is_array($file)) {
      fclose($file);
    }
  }
}

