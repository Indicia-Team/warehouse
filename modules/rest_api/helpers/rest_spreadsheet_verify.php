<?php

/**
 * @file
 * Helper class for verifying records in an uploaded spreadsheet.
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

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

define('ROWS_PER_BATCH', 10);

/**
 * Shim function to allow client site code to work.
 */
function hostsite_get_config_value($context, $name, $default = FALSE) {
  if ($context === 'iform' && $name === 'master_checklist_id') {
    return kohana::config('indicia.master_list_id');
  }
  return $default;
}

/**
 * Shim function to allow client site code to work.
 */
function hostsite_get_user_field($field) {
  if ($field === 'indicia_user_id') {
    return 0;
  }
  elseif ($field === 'training') {
    return FALSE;
  }
  else {
    throw new Exception("Field $field not supported");
  }
}

/**
 * PHPSpreadsheet filter for reading the header row.
 */
class FirstRowReadFilter implements IReadFilter {

  /**
   * Only read cells in row 1.
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
   * Number of rows into the spreadsheet where reading starts.
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
   * Indexes of the columns to include.
   *
   * @var array
   */
  private $columnIndexes;

  /**
   * Constructor stores parameters.
   */
  public function __construct($offset, $limit, $columnIndexes) {
    $this->offset = $offset;
    $this->limit = $limit;
    $this->columnIndexes = $columnIndexes;
  }

  /**
   * Limit range of rows.
   */
  public function readCell($column, $row, $worksheetName = '') {
    $inRange = $row >= $this->offset && $row < $this->offset + $this->limit;
    $wantCol = in_array(ord($column) - 65, $this->columnIndexes);
    return $inRange && $wantCol;
  }

}

/**
 * Helper class for applying verification actions in a spreadsheet.
 */
class rest_spreadsheet_verify {

  /**
   * Processes the next task/batch of a verification spreadsheet.
   *
   * @return array
   *   Processing metadata to return to client.
   *
   * @todo Check the row is in the filter.
   */
  public static function verifySpreadsheet() {
    // Tolerate form or post data.
    if (empty($_POST)) {
      $postData = json_decode(file_get_contents('php://input'), TRUE);
    }
    else {
      $postData = $_POST;
    }
    if (isset($_FILES['decisions'])) {
      // Initial upload of the decision file.
      self::checkPrerequisites();
      $fileId = self::moveFileToImportsDir();
      $metadata = self::createTrackingFiles($fileId);
    }
    elseif (!empty($postData['fileId'])) {
      // Subsequent call - fileId used to find metadata file.
      $fileId = $postData['fileId'];
      $file = fopen(DOCROOT . "import/{$fileId}-metadata.json", "r");
      $metadata = json_decode(fread($file, filesize((DOCROOT . "import/{$fileId}-metadata.json"))), TRUE);
      fclose($file);
      if ($metadata['state'] === 'start') {
        // Start - load the first header row to find the important column
        // indexes.
        $reader = self::getReader($metadata);
        $reader->setReadFilter(new FirstRowReadFilter());
        $spreadsheet = $reader->load(DOCROOT . "import/$metadata[fileId].$metadata[type]");
        $header = $spreadsheet->getActiveSheet()->toArray();
        if (count($header) === 0) {
          RestObjects::$apiResponse->fail('Bad Request', 400, 'The uploaded spreadsheet file is empty.');
        }
        self::readHeaderRow($header[0], $metadata);
        $metadata['state'] = 'checking';
      }
      elseif ($metadata['state'] === 'checking') {
        // Check a batch of rows for problems.
        self::checkNextBatch($metadata);
      }
      elseif ($metadata['state'] === 'processing') {
        // Process a batch of decisions.
        self::processNextBatch($metadata);
      }
    }
    else {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Missing decisions file or fileId');
    }
    // Write metadata back to file.
    $file = fopen(DOCROOT . "import/{$fileId}-metadata.json", "w");
    fwrite($file, json_encode($metadata));
    fclose($file);
    return $metadata;
  }

  /**
   * Check a batch of rows for problems.
   *
   * @param array $metadata
   *   File metadata.
   */
  private static function checkNextBatch(array &$metadata) {
    // Load the files we are processing.
    $importFileName = DOCROOT . "import/$metadata[fileId].$metadata[type]";
    $errorsFile = fopen(DOCROOT . "import/$metadata[fileId]-errors.csv", "a");
    $verificationsFile = fopen(DOCROOT . "import/$metadata[fileId]-verifications.json", "a");
    $reader = self::getReader($metadata);
    // Limit the rows we are reading to this batch.
    // Range reader offset is indexed from 1, plus 1 more to skip header.
    $offset = $metadata['totalChecked'] + 2;
    $reader->setReadFilter(new RangeReadFilter($offset, ROWS_PER_BATCH, [
      $metadata['idColIndex'],
      $metadata['statusColIndex'],
      $metadata['commentColIndex'],
    ]));
    // Read the data from the spreadsheet.
    $spreadsheet = $reader->load($importFileName);
    $data = $spreadsheet->getActiveSheet()->toArray();
    $_ids = [];
    for ($i = $offset - 1; $i < $offset - 1 + ROWS_PER_BATCH; $i++) {
      // If failed to read more rows, then done.
      if (!isset($data[$i])) {
        // State switches to processing, or indicate that checks failed.
        $metadata['state'] = $metadata['errorsFound'] === 0 ? 'processing' : 'checks failed';
        break;
      }
      $row = $data[$i];
      $id = $row[$metadata['idColIndex']];
      $_ids[] = "$metadata[id_prefix]$id";
      $metadata['totalChecked']++;
      if (!preg_match('/\d+/', $id)) {
        // Log invalid ID value.
        $errorRow = [
          $id,
          $row[$metadata['statusColIndex']],
          $row[$metadata['commentColIndex']],
          'The value in the ID column in this row is incorrect. Expecting a whole number.',
        ];
        fputcsv($errorsFile, $errorRow);
        $metadata['errorsFound']++;
        continue;
      }

      if (empty($row[$metadata['statusColIndex']]) && empty($row[$metadata['commentColIndex']])) {
        // No status or comment update in this row so skip it.
        continue;
      }
      if (!empty($row[$metadata['statusColIndex']])) {
        $status = self::getRecordStatus($row, $metadata);
        if ($status === FALSE) {
          // Log invalid status in row.
          $errorRow = [
            $id,
            $row[$metadata['statusColIndex']],
            $row[$metadata['commentColIndex']],
            'The value in the *Decision status* column in this row is not recognised.',
          ];
          fputcsv($errorsFile, $errorRow);
          $metadata['errorsFound']++;
          continue;
        }
        // Save the status update to the list of changes we'll make during
        // processing.
        $verificationData = [
          $id,
          $status[0],
          $status[1],
          $status[2],
          $row[$metadata['commentColIndex']],
        ];
      }
      else {
        // Just a comment to save to the list of changes we'll make during
        // processing.
        $verificationData = [
          $id,
          NULL,
          NULL,
          NULL,
          $row[$metadata['commentColIndex']],
        ];
      }
      // Save the info about changes that need to be made.
      fputcsv($verificationsFile, $verificationData);
      $metadata['verificationsFound']++;
    }
    self::checkAllIdsInFilter($_ids, $metadata);
    fclose($errorsFile);
    fclose($verificationsFile);
  }

  /**
   * Checks all IDs in a batch fall inside the filter.
   *
   * For spreadsheet verification to work, it requires a filter_id that gives
   * verification rights to the records to the user. So we need to check that
   * all IDs in the spreadsheet are found within the filter, otherwise the
   * whole upload is aborted.
   *
   * @param array $_ids
   *   List of _id document identifiers to check.
   * @param array $metadata
   *   File upload metadata including filter_id and user_id.
   */
  private static function checkAllIdsInFilter(array $_ids, array $metadata) {
    require_once 'client_helpers/ElasticsearchProxyHelper.php';
    require_once 'client_helpers/helper_base.php';
    $readAuth = helper_base::get_read_auth(0 - $metadata['user_id'], kohana::config('indicia.private_key'));
    $boolFilter = [
      'must' => [
        [
          'terms' => [
            'metadata.website.id' => warehouse::getSharedWebsiteList([RestObjects::$clientWebsiteId], RestObjects::$db, RestObjects::$scope),
          ],
        ],
        [
          'terms' => [
            '_id' => $_ids,
          ],
        ],
      ],
      'should' => [],
      'must_not' => [],
      'filter' => [],
    ];
    ElasticsearchProxyHelper::applyUserFilters($readAuth, [
      'user_filters' => [$metadata['filter_id']],
      'refresh_user_filters' => FALSE,
    ], $boolFilter);
    $esApi = new RestApiElasticsearch($metadata['es_endpoint']);
    $doc = [
      'query' => ['bool' => $boolFilter],
      'size' => 0,
    ];
    $response = json_decode($esApi->elasticRequest(json_decode(json_encode($doc)), 'json', TRUE, '_search'));
    $hitsTotal = $response->hits->total;
    // ES versiion tolerance.
    $foundCount = isset($hitsTotal->value) ? $hitsTotal->value : $hitsTotal;
    // If the filtered set is smaller than the requested set, then the
    // spreadsheet contains records not in the filter.
    if ($foundCount < count($_ids)) {
      RestObjects::$apiResponse->fail('Bad Request', 400,
        'The spreadsheet cannot be accepted as it contains records that are not in your current verification context filter.');
    }
  }

  /**
   * Applies a set of verifications to the Elasticsearch index.
   *
   * @param array $metadata
   *   File uploade metadata.
   * @param array $esUpdates
   *   Array containing info about records to change status for. The array
   *   indexes are keys containing status info and the values are sub-arrays of
   *   record IDs that should be updated to that status.
   */
  private static function applyVerificationsToEs(array $metadata, array $esUpdates) {
    foreach ($esUpdates as $key => $ids) {
      list($status, $subStatus, $query) = str_split($key);
      $scripts = [];
      if (!empty(trim($status))) {
        $scripts[] = "ctx._source.identification.verification_status = '" . $status . "'";
      }
      if (!empty(trim($subStatus))) {
        $scripts[] = "ctx._source.identification.verification_substatus = '" . $subStatus . "'";
      }
      if (!empty(trim($query))) {
        $scripts[] = "ctx._source.identification.query = '" . $query . "'";
      }
      if (empty($scripts)) {
        continue;
      }
      $_ids = [];
      $sensitive_Ids = [];
      // Convert Indicia IDs to the document _ids for ES. Also make a 2nd
      // version for full precision copies of sensitive records.
      foreach ($ids as $id) {
        $_ids[] = "$metadata[id_prefix]$id";
        $sensitive_Ids[] = "$metadata[id_prefix]$id!";
      }
      $doc = [
        'script' => [
          'source' => implode("; ", $scripts),
          'lang' => 'painless',
        ],
        'query' => [
          'terms' => [
            '_id' => $sensitive_Ids,
          ],
        ],
      ];
      $esApi = new RestApiElasticsearch($metadata['es_endpoint']);
      // Update index immediately and overwrite update conflicts.
      // Sensitive records first.
      $_GET = [
        'refresh' => 'true',
        'conflicts' => 'proceed',
      ];
      $esApi->elasticRequest($doc, 'json', TRUE, '_update_by_query');
      // Now normal records/blurred records.
      $doc['query']['terms']['_id'] = $_ids;
      $esApi->elasticRequest($doc, 'json', TRUE, '_update_by_query');
    }
  }

  /**
   * Handles processing of a subsequent request to the upload spreadsheet API.
   *
   * @param array $metadata
   *   File upload tracking metadata.
   */
  private static function processNextBatch(&$metadata) {
    $verificationsFile = fopen(DOCROOT . "import/$metadata[fileId]-verifications.json", "r");
    // If not on first batch, skip to correct place in file.
    if (isset($metadata['filepos'])) {
      fseek($verificationsFile, $metadata['filepos']);
    }
    $done = 0;
    $db = new Database();
    $esUpdates = [];
    while ($done < ROWS_PER_BATCH) {
      $row = fgetcsv($verificationsFile);
      $metadata['filepos'] = ftell($verificationsFile);
      if ($row === FALSE) {
        $metadata['state'] = 'done';
        break;
      }
      $id = $row[0];
      $status = empty($row[1]) ? NULL : $row[1];
      $subStatus = empty($row[2]) ? NULL : $row[2];
      $query = $row[3];
      $comment = $row[4];
      if (!empty($status)) {
        $updates = [
          'record_status' => $status,
          'record_substatus' => $subStatus,
          'verified_by_id' => $metadata['user_id'],
          'verified_on' => date('Y-m-d H:i:s'),
          'updated_by_id' => $metadata['user_id'],
          'updated_on' => date('Y-m-d H:i:s'),
          // @todo Might spreadsheets be generated by machine?
          'record_decision_source' => 'H',
        ];
        $db->from('occurrences')
          ->set($updates)
          ->where('id', $id)
          ->update();
      }
      if (empty(trim($comment))) {
        $comment = self::getRecordStatusTerm($status . $subStatus . $query);
      }
      $db->insert('occurrence_comments', [
        'occurrence_id' => $id,
        'comment' => $comment,
        'created_by_id' => $metadata['user_id'],
        'created_on' => date('Y-m-d H:i:s'),
        'updated_by_id' => $metadata['user_id'],
        'updated_on' => date('Y-m-d H:i:s'),
        'record_status' => $status,
        'record_substatus' => $subStatus,
        'query' => $query === 'Q' ? 't' : 'f',
      ]);
      $done++;
      $metadata['totalProcessed']++;
      // If status or query updated, need to update ES. Group IDs by status
      // info so we can do a batch update later.
      if ($status || ($query === 'Q')) {
        $key = (empty($status) ? ' ' : $status) . (empty($subStatus) ? ' ' : $subStatus) . ($query === 'Q' ? 'Q' : ' ');
        if (!isset($esUpdates[$key])) {
          $esUpdates[$key] = [];
        }
        $esUpdates[$key][] = $id;
      }
    }
    self::applyVerificationsToEs($metadata, $esUpdates);
  }

  /**
   * Moves an uploaded file to the /import directory.
   *
   * @return string
   *   Filename prefix generated for saved file.
   */
  private static function moveFileToImportsDir() {
    $maxUploadSize = Kohana::config('indicia.maxUploadSize');
    $files = Validation::factory($_FILES)->add_rules(
      'upload::valid', 'upload::type[csv,xls,xlsx]', "upload::size[$maxUploadSize]"
    );
    if ($files->validate()) {
      $tokens = explode('.', $_FILES['decisions']['name']);
      $ext = strtolower(array_pop($tokens));
      $fileId = uniqid('verify-', TRUE);
      Upload::save('decisions', "$fileId.$ext", DOCROOT . 'import');
    }
    else {
      RestObjects::$apiResponse->fail('Bad Request', 400, implode('; ', $files->errors('form_error_messages')));
    }
    return $fileId;
  }

  /**
   * Create the files required for tracking the verification process.
   *
   * Files include a metadata file, errors file and verifications found file.
   *
   * @return array
   *   Processing metadata.
   */
  private static function createTrackingFiles($fileId) {
    $tokens = explode('.', strtolower($_FILES['decisions']['name']));
    $metadata = [
      'fileId' => $fileId,
      'user_id' => $_POST['user_id'],
      'filter_id' => $_POST['filter_id'],
      'es_endpoint' => $_POST['es_endpoint'],
      'type' => array_pop($tokens),
      'totalChecked' => 0,
      'totalProcessed' => 0,
      'verificationsFound' => 0,
      'errorsFound' => 0,
      'state' => 'start',
      'id_prefix' => $_POST['id_prefix'],
    ];
    $file = fopen(DOCROOT . "import/{$fileId}-metadata.json", "w");
    fwrite($file, json_encode($metadata));
    fclose($file);
    $file = fopen(DOCROOT . "import/{$fileId}-errors.csv", "w");
    fputcsv($file, [
      'ID',
      '*Decision status*',
      '*Decision comment*',
      'Error description',
    ]);
    fclose($file);
    $file = fopen(DOCROOT . "import/{$fileId}-verifications.json", "w");
    fclose($file);
    return $metadata;
  }

  /**
   * Ensures the API call has everything we need.
   */
  private static function checkPrerequisites() {
    if (empty($_POST['user_id'])) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Missing user authentication.');
    }
    if (empty($_POST['filter_id'])) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Missing filter_id parameter.');
    }
    if (empty($_FILES['decisions'])) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Missing filter_id parameter.');
    }
    if (empty($_POST['es_endpoint'])) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Missing es_endpoint parameter.');
    }
    // Check the user has access to this filter.
    $db = new Database();
    $fu = $db
      ->select('user_id')
      ->from('list_filters_users')
      ->where([
        'user_id' => $_POST['user_id'],
        'filter_id' => $_POST['filter_id'],
        'filter_defines_permissions' => 't',
        'filter_sharing' => 'V',
      ])
      ->get()->current();
    if (!$fu) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'Unauthorised filter requested');
    }
  }

  /**
   * Retrieves the appropriate class for reading the spreadsheet.
   */
  private static function getReader($metadata) {
    if ($metadata['type'] === 'csv') {
      $reader = new Csv();
    }
    elseif ($metadata['type'] === 'xlsx') {
      $reader = new Xlsx();
    }
    elseif ($metadata['type'] === 'xls') {
      $reader = new Xls();
    }
    $reader->setReadDataOnly(TRUE);
    if (substr($metadata['type'], 0, 3) === 'xls') {
      // Minimise data read from spreadsheet - first sheet only.
      $worksheetData = $reader->listWorksheetInfo(DOCROOT . "import/$metadata[fileId].$metadata[type]");
      if (count($worksheetData) === 0) {
        RestObjects::$apiResponse->fail('Bad Request', 400, 'The uploaded spreadsheet contains no worksheets.');
      }
      $reader->setLoadSheetsOnly($worksheetData[0]['worksheetName']);
    }
    return $reader;
  }

  /**
   * Determines the position of important columns from the header row.
   *
   * @param array $rowData
   *   Data read from first spreadsheet row.
   * @param array $metadata
   *   Metadata for file upload. Relevant column indexes found in header row
   *   will be appended.
   */
  private static function readHeaderRow(array $rowData, array &$metadata) {
    $metadata['idColIndex'] = array_search('ID', $rowData);
    $metadata['statusColIndex'] = array_search('*Decision status*', $rowData);
    $metadata['commentColIndex'] = array_search('*Decision comment*', $rowData);
    if ($metadata['idColIndex'] === FALSE || $metadata['statusColIndex'] === FALSE || $metadata['statusColIndex'] === FALSE) {
      RestObjects::$apiResponse->fail('Bad Request', 400, 'The uploaded spreadsheet does not have the required columns.');
    }
  }

  /**
   * Returns the status code according to a spreadsheet row's status term value.
   *
   * @param array $row
   *   Spreadsheet row values containing status, e.g. "Accepted as correct" or "Queried".
   *   Some tolerance of alternative forms included, e.g. "Rejected :: incorrect".
   * @param array $metadata
   *   File upload tracking metadata.
   *
   * @return string
   *   Status code, e.g. V1 or Q.
   */
  private static function getRecordStatus(array $row, array $metadata) {
    // Uppercase and resolve spacing issues.
    $origStatus = strtoupper(trim(preg_replace('/\s+/', ' ', $row[$metadata['statusColIndex']])));
    // Tolerate some alternative ways of writing the term.
    $origStatus = str_replace([' :: ', 'REJECTED'], [' AS ', 'NOT ACCEPTED'], $origStatus);
    $mappings = [
      'ACCEPTED' => 'V',
      'ACCEPTED AS CORRECT' => 'V1',
      'ACCEPTED AS CONSIDERED CORRECT' => 'V2',
      'PLAUSIBLE' => 'C3',
      'UNCONFIRMED AS PLAUSIBLE' => 'C3',
      'NOT ACCEPTED' => 'R',
      'NOT ACCEPTED AS UNABLE TO VERIFY' => 'R4',
      'NOT ACCEPTED AS INCORRECT' => 'R5',
      'QUERY' => 'Q',
      'QUERIED' => 'Q',
    ];
    if (isset($mappings[$origStatus])) {
      $status = $mappings[$origStatus];
    }
    elseif (in_array($origStatus, ['V', 'V1', 'V2', 'C3', 'R', 'R4', 'R5', 'Q'])) {
      $status = $origStatus;
    }
    else {
      // Unrecognisable status.
      return FALSE;
    }
    if ($status === 'Q') {
      return [NULL, NULL, 'Q'];
    }
    $statusValues = str_split($status);
    return array_pad($statusValues, 3, NULL);
  }

  /**
   * Returns the status term for a status code.
   *
   * @param string $statusCode
   */
  private static function getRecordStatusTerm($statusCode) {
    $recordStatusLookup = [
      'V' => 'Accepted',
      'V1' => 'Accepted as correct',
      'V2' => 'Accepted as considered correct',
      'C3' => 'Plausible',
      'R' => 'Not accepted',
      'R4' => 'Not accepted as unable to verify',
      'R5' => 'Not accepted as incorrect',
      'Q' => 'Queried',
    ];
    return $recordStatusLookup[$statusCode];
  }

}