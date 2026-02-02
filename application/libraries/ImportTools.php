<?php

/**
 * @file
 * A library of tools for handling import files.
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
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

/**
 * PHPSpreadsheet filter for reading the header row.
 */
class FirstRowReadFilter implements IReadFilter {

  /**
   * Enable reading of row 1 only.
   *
   * @param int $columnAddress
   *   Column letter - ignored.
   * @param int $row
   *   Row number.
   * @param string $worksheetName
   *   Worksheet name - ignored.
   */
  public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool {
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
   * @param int $columnAddress
   *   Column letter - ignored.
   * @param int $row
   *   Row number.
   * @param string $worksheetName
   *   Worksheet name - ignored.
   */
  public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool {
    return $row >= $this->offset && $row < $this->offset + $this->limit;
  }

}

/**
 * A library of tools for handling import files.
 */
class ImportTools {

  /**
   * List of spreadsheets opened that need to be disconnected when done.
   *
   * @var array
   */
  private $filesToDisconnect = [];

  /**
   * Destructor cleans up memory.
   */
  public function __destruct() {
    $this->disconnect();
  }

  /**
   * Ensure we clean up from any spreadsheets we connected to.
   */
  public function disconnect() {
    foreach ($this->filesToDisconnect as $spreadsheet) {
      $spreadsheet->disconnectWorksheets();
    }
    $this->filesToDisconnect = [];
  }

  /**
   * Uploads a file in the $_FILES array so it's ready to import.
   *
   * @return string
   *   Uploaded file path.
   */
  public function uploadFile() {
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
      throw new exception('No file was uploaded.');
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
      return $fTmp;
    }
    else {
      kohana::log('error', 'Validation errors uploading file ' . $validatedFiles['media_upload']['name']);
      kohana::log('error', print_r($validatedFiles->errors('form_error_messages'), TRUE));
      foreach ($validatedFiles as $file) {
        if (!empty($file['error'])) {
          kohana::log('error', 'PHP reports file upload error: ' . $this->fileValidationCodeToMessage($file['error']));
        }
      }
      throw new exception(implode('; ', $validatedFiles->errors('form_error_messages')));
    }
  }

  /**
   * Extract an already uploaded zip file containing a spreadsheet/CSV file.
   *
   * @param string $uploadedFile
   *   Base name of the existing uploaded file.
   *
   * @return string
   *   Extracted filename.
   */
  public function extractFile($uploadedFile) {
    if (!file_exists(DOCROOT . 'import/' . $uploadedFile)) {
      throw new exception('Parameter uploaded-file refers to a missing file');
    }
    $zip = new ZipArchive();
    $res = $zip->open(DOCROOT . 'import/' . $uploadedFile);
    if ($res !== TRUE) {
      throw new exception('The Zip archive could not be opened.');
    }
    // Strip any __MACOSX hidden folders that may be present.
    $needToReopen = FALSE;
    for ($i = $zip->count() - 1; $i >= 0; $i--) {
      if (substr($zip->getNameIndex($i), 0, 9) === '__MACOSX/') {
        $zip->deleteIndex($i);
        $needToReopen = TRUE;
      }
    }
    if ($needToReopen) {
      $zip->close();
      $res = $zip->open(DOCROOT . 'import/' . $uploadedFile);
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
    // No need to keep file any more.
    @unlink(DOCROOT . 'import/' . $uploadedFile);
    return $fileName;
  }

  /**
   * Uses the first row of data to build a list of columns in the import file.
   *
   * @param string $fileName
   *   The data file to load.
   * @param bool $lowercase
   *   Set to true if the result should be in lowercase.
   *
   * @return array
   *   List of columns titles read from the file.
   */
  public function loadColumnTitlesFromFile($fileName, $lowercase) {
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
    $columnTitles = $data[0];
    // Remove any null columns from the end.
    for ($i = count($columnTitles) - 1; $i >= 0 && $columnTitles[$i] === NULL; $i--) {
      unset($columnTitles[$i]);
    }
    // Tidy.
    if ($lowercase) {
      $columnTitles = array_map(fn ($value) => trim(strtolower($value ?? '')), $columnTitles);
    }
    return $columnTitles;
  }

  /**
   * Opens a CSV or spreadsheet file and winds to current rowsRead offset.
   *
   * @param string $fileName
   *   The data file to open.
   * @param array $config
   *   Import config information.
   * @param int $limit
   *   Maximum number of rows to load.
   *
   * @return PhpOffice\PhpSpreadsheet\Worksheet\RowIterator
   *   Row data.
   */
  public function openSpreadsheet($fileName, array &$config, $limit = 100000) {
    $reader = $this->getReader($fileName);
    // Minimise data read from spreadsheet - first sheet only.
    $worksheetData = $reader->listWorksheetInfo(DOCROOT . "import/$fileName");
    if (count($worksheetData) === 0) {
      throw new exception('Spreadsheet contains no worksheets');
    }
    $reader->setLoadSheetsOnly($worksheetData[0]['worksheetName']);
    // Get number of rows read. Stored in config under files array only for
    // import types that support multi-file import.
    $rowsRead = $config['rowsRead'] ?? $config['files'][$fileName]['rowsRead'];
    // Add two to the range start, as it is indexed from one not zero unlike
    // the data array read out and we skip the header row.
    $reader->setReadFilter(new RangeReadFilter($rowsRead + 2, $limit));
    $file = $reader->load(DOCROOT . "import/$fileName");
    $this->filesToDisconnect[] = $file;
    return $file->getActiveSheet()->getRowIterator($rowsRead + 2);
  }

  /**
   * Return number of rows to import in a single file.
   *
   * @param string $fileName
   *   Name of the import file.
   *
   * @return int
   *   A representation of the file size.
   */
  public function getRowCountForFile($fileName) {
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
   * Return number of rows to import in the file(s).
   *
   * For CSV, the file size in bytes which can be compared with filepos to get
   * progress info. For PHPSpreadsheet files, the cumulative worksheet's size.
   *
   * @param array $files
   *   List of the import file names.
   *
   * @return int
   *   A representation of the file size.
   */
  public function getTotalRows(array $files) {
    $total = 0;
    foreach ($files as $fileName) {
      $total += $this->getRowCountForFile($fileName);
    }
    return $total;
  }

  /**
   * Convert a worksheet row instance to a simple array.
   *
   * The array will be associative if $columnTitles is provided.
   *
   * @param PhpOffice\PhpSpreadsheet\Worksheet\Row $row
   *   Worksheet row instance from a RowIterator.
   * @param ?array $columnTitles
   *   Optional list of column titles to use as array keys.
   *
   * @return array
   *   Row data as a simple or associative array.
   */
  public function rowToArray(Row $row, ?array $columnTitles = NULL) {
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    $rowData = [];
    foreach ($cellIterator as $index => $cell) {
      if ($columnTitles) {
        $rowData[$columnTitles[$index]] = $cell->getValue();
      }
      else {
        $rowData[] = $cell->getValue();
      }
    }
    return $rowData;
  }

  /**
   * Opens a PHPSpreadsheet Reader for the selected file.
   *
   * @param string $fileName
   *   Name of the import file.
   *
   * @return PhpOffice\PhpSpreadsheet\Reader\BaseReader
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
