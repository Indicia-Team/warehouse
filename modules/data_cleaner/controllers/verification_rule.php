<?php

/**
 * @file
 * Controller class for verification rules.
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

require_once DOCROOT . 'client_helpers/helper_base.php';

/**
 * Controller class for the data cleaner plugin module.
 */
class Verification_rule_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('verification_rule', 'verification_rule/index');
    $this->columns = array(
      'title'       => '',
      'description' => '',
      'test_type'   => '',
    );
    $this->pagetitle = "Verification Rules";
    $this->model = ORM::factory('verification_rule');
  }

  /**
   * Index controller action. Load the list of verification rule servers.
   */
  public function index() {
    parent::index();
    // Load the rule files from the server.
    $list = $this->get_server_list();
    $this->view->serverList = $list;
  }

  /**
   * Gets model data to load on the edit form.
   *
   * Returns an array of all values from this model ready to be loaded into a
   * form. For this controller, we need to also setup text for the "other data"
   * section.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $r['metaFields:metadata'] = $this->getMetadataAsText();
    $r['metaFields:data'] = $this->getDataAsText();
    return $r;
  }

  private function getMetadataAsText() {
    if ($this->model->id) {
      $items = $this->db->select('key, value')
        ->from('verification_rule_metadata')
        ->where(array('verification_rule_id' => $this->model->id, 'deleted' => 'f'))
        ->orderby(array('id' => 'ASC'))
        ->get()->result();
      $outputs = [];
      foreach ($items as $item) {
        $outputs[] = "$item->key=$item->value";
      }
      $r = implode("\n", $outputs);
      return $r;
    }
    else {
      return '';
    }
  }

  private function getDataAsText() {
    $currentHeader = '';
    $lastGroup = NULL;
    if ($this->model->id) {
      $items = $this->db->select('header_name, data_group, key, value')
        ->from('verification_rule_data')
        ->where(['verification_rule_id' => $this->model->id, 'deleted' => 'f'])
        ->orderby(['data_group' => 'ASC', 'id' => 'ASC'])
        ->get()->result();
      $outputs = [];
      foreach ($items as $item) {
        // Space separate groups of metadata.
        $lastGroup = $lastGroup === NULL ? $item->data_group : $lastGroup;
        if ($item->data_group !== $lastGroup) {
          $outputs[] = '';
          $lastGroup = $item->data_group;
        }
        if ($item->header_name !== $currentHeader) {
          $outputs[] = "[$item->header_name]";
          $currentHeader = $item->header_name;
        }
        $row = $item->key;
        if (!empty($item->value)&&$item->value !== '-') {
          $row .= "=$item->value";
        }
        $outputs[] = $row;
      }
      $r = implode("\n", $outputs);
      return $r;
    }
    else {
      return '';
    }
  }

  /**
   * Returns the list of servers from the remote server configuration file.
   *
   * @return array
   *   List of available servers.
   */
  private function get_server_list() {
    $session = curl_init(kohana::config('data_cleaner.servers'));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
    $r = [];
    $response = curl_exec($session);
    if (curl_errno($session)) {
      $this->session->set_flash('flash_info', 'The list of verification rule servers could not be retrieved from the internet. ' .
          'More information is available in the server logs.');
      kohana::log('error', 'Error occurred when retrieving list of verification rule servers. ' . curl_error($session));
      return [];
    }
    foreach (helper_base::explode_lines($response) as $line) {
      $tokens = explode('#', $line);
      $r[] = array(
        'file' => $tokens[0],
        'author' => $tokens[1],
        'date' => $tokens[2],
      );
    }
    return $r;
  }

  /**
   * Controller function that responds to any request for an upload.
   */
  public function upload() {
    if (!empty($_FILES['zipOrCsvFile']['name'])) {
      $tokens = explode('.', $_FILES['zipOrCsvFile']['name']);
      $ext = array_pop($tokens);
      if (strcasecmp($ext, 'zip') === 0) {
        $this->uploadRuleZip($_FILES['zipOrCsvFile']);
      }
      elseif (strcasecmp($ext, 'csv') === 0) {
        $this->uploadRuleCsv($_FILES['zipOrCsvFile']);
      }
      else {
        $this->session->set_flash('flash_error', "Incompatible file selected. The rule file upload requires a zipped file ".
          "containing Record Cleaner compatible rule files or a CSV file containing rule definitions.");
      }
    }
    else {
      $this->displayProgressTemplateForServerFetch();
    }
  }

  /**
   * Uploading from a zipped batch of rule files.
   *
   * Displays the upload template with progress bar and status message, which
   * then initiates the actual import.
   */
  private function uploadRuleZip($zipfile) {
    $ruleFiles = [];
    $dir = $this->processRuleZipFile($zipfile['tmp_name'], TRUE);
    $dir_iterator = new RecursiveDirectoryIterator($dir);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $relativePath = substr($file->getRealPath(), strlen(realpath("$dir/extract")));
        $ruleFiles[] = array(
          'file' => $file->__toString(),
          'source_url' => $zipfile['name'],
          'display' => basename($zipfile['name']) . ' ' . $relativePath,
          'path' => preg_replace('/^[\/]/', '', str_replace('\\', '/', $relativePath)),
        );
      }
    }
    // Save the rule file list to a cached list, so we can preserve it across
    // http requests.
    $uploadId = time() . md5($zipfile['tmp_name']);
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "w");
    fwrite($cacheHandle, json_encode(array(
      'paths' => array(
        array(
          'file' => $dir,
          'source_url' => $zipfile['name'],
          'title' => basename($zipfile['name']),
        )
      ),
      'files' => $ruleFiles,
    )));
    fclose($cacheHandle);
    // Show a progress view.
    $view = new View('verification_rule/upload_rule_files');
    $view->uploadId = $uploadId;
    $view->requiresFetch = 'false';
    $this->template->content = $view;
    $this->template->title = 'Uploading rule files';
  }

  private function displayProgressTemplateForServerFetch() {
    // Show a progress view.
    $view = new View('verification_rule/upload_rule_files');
    // Generate a unique ID for the upload.
    $uploadId = time() . md5($_POST['server']);
    $view->uploadId = $uploadId;
    $view->requiresFetch = 'true';
    $this->template->content = $view;
    $this->template->title = 'Importing verification rule files';
    $zipfiles = $this->fetchServerFileList($_POST['server']);
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "w");
    fwrite($cacheHandle, json_encode(array(
      'zipfiles' => $zipfiles,
      'done' => 0,
      'paths' => [],
      'files' => [],
    )));
    fclose($cacheHandle);
  }

  /**
   * Controller action for loading rule files from the verification rule server.
   */
  public function fetch_file_chunk() {
    $this->auto_render = FALSE;
    // Load our cached status.
    $uploadId = $_GET['uploadId'];
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "r");
    $cacheData = fread($cacheHandle, 10000000);
    fclose($cacheHandle);
    $cacheArr = json_decode($cacheData, TRUE);
    $file = $cacheArr['zipfiles'][$cacheArr['done']];
    $path = array(
      'file' => $this->processRuleZipFile($file['file']),
      'source_url' => $file['file'],
      'title' => $file['title'],
    );
    // Unzip the file at the path and work out what files it contains.
    $ruleFiles = [];
    $dir_iterator = new RecursiveDirectoryIterator("$path[file]/extract");
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $relativePath = substr($file->getRealPath(), strlen(realpath("$path[file]/extract")));
        $ruleFiles[] = array(
          'file' => $file->__toString(),
          'source_url' => $path['source_url'],
          'display' => $path['title'] . $relativePath,
          'path' => preg_replace('/^[\/]/', '', str_replace('\\', '/', $relativePath)),
        );
      }
    }
    $cacheArr['paths'][] = $path;
    $cacheArr['files'] = array_merge(
      $cacheArr['files'],
      $ruleFiles
    );
    $cacheArr['done']++;
    // Save the rule file list to a cached list, so we can preserve it across
    // http requests.
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "w");
    fwrite($cacheHandle, json_encode($cacheArr));
    fclose($cacheHandle);
    echo json_encode(array(
      'progress' => $cacheArr['done'] * 100 / count($cacheArr['zipfiles']),
    ));
  }

  /**
   * Fetches a file list from the server hosting rule files.
   *
   * Finds a record cleaner server index page and returns the list of files it
   * refers to.
   *
   * @param string $server
   *   URL of the server.
   */
  private function fetchServerFileList($server) {
    // As redirections cause a recursion, let's set a limit.
    static $curl_loops = 0;
    static $curl_max_loops = 20;
    if ($curl_loops++ >= $curl_max_loops) {
      $curl_loops = 0;
      throw new exception("cUrl request to $server resulted in too many redirections");
    }

    $session = curl_init($server);
    curl_setopt($session, CURLOPT_HEADER, TRUE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
    $files = [];
    $response = curl_exec($session);
    if (curl_errno($session)) {
      kohana::log('error', 'cUrl error : ' . curl_errno($session));
      kohana::log('error', 'cUrl message : ' . curl_error($session));
      throw new exception("cUrl request to $server failed");
    }
    $http_code = curl_getinfo($session, CURLINFO_HTTP_CODE);
    // Did we get a redirect response?
    if ($http_code == 301 || $http_code == 302) {
      // Find the redirect location in the response.
      preg_match('/Location:(.*?)\n/', $response, $matches);
      $url = @parse_url(trim(array_pop($matches)));
      if (!$url) {
        throw new exception("Redirect from $server failed");
      }
      $last_url = parse_url(curl_getinfo($session, CURLINFO_EFFECTIVE_URL));
      if (!$url['scheme']) {
        $url['scheme'] = $last_url['scheme'];
      }
      if (!$url['host']) {
        $url['host'] = $last_url['host'];
      }
      if (!$url['path']) {
        $url['path'] = $last_url['path'];
      }
      $newUrl = $url['scheme'] . '://' . $url['host'] . $url['path'] . (isset($url['query']) ? "?$url[query]" : '');
      return self::fetchServerFileList($newUrl);
    }
    list($header, $data) = explode("\r\n\r\n", $response, 2);
    foreach (helper_base::explode_lines($data) as $line) {
      $tokens = explode('#', $line);
      $files[] = array(
        'file' => $tokens[0],
        'title' => $tokens[1],
        'date' => $tokens[2],
      );
    }
    return $files;
  }

  /**
   * Loads a remote zip file, extracts the rule files and processes them.
   *
   * @param string $sourcefile
   *   Path to a file.
   * @param bool $local
   *   Is the file local or remote?
   *
   * @return bool
   *   Returns the unzipped directory location.
   */
  private function processRuleZipFile($sourcefile, $local = FALSE) {
    kohana::log('debug', "Processing $sourcefile");
    try {
      $dir = $this->create_zip_extract_dir() . 'rules-' . time() . '-' . rand(0, 1000);
    }
    catch (Exception $e) {
      throw new Exception('Could not create the extract directory on the warehouse');
    }
    mkdir($dir, 0777, TRUE);
    mkdir("$dir/extract", 0777, TRUE);
    if ($local) {
      // File is local so can just unzip it.
      $zipFile = $sourcefile;
    }
    else {
      $zipFile = "$dir/" . basename($sourcefile);
      $fh = fopen($zipFile, "wb");
      // str_replace used here for spaces in file names, I would have thought urlencode would work but apparently not...
      $session = curl_init(str_replace(' ', '%20', $sourcefile));
      curl_setopt($session, CURLOPT_FILE, $fh);
      curl_setopt($session, CURLOPT_HEADER, FALSE);
      curl_setopt($session, CURLOPT_FOLLOWLOCATION, TRUE);
      curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_exec($session);
      if (curl_errno($session)) {
        throw new exception("Error downloading zip file $sourcefile: " . curl_error($session));
      }
      curl_close($session);
      fclose($fh);
    }
    $zip = new ZipArchive();
    $res = $zip->open($zipFile);
    $zip->extractTo("$dir/extract/");
    return $dir;
  }

  /**
   * AJAX handler to upload a single rule file from a folder.
   */
  public function upload_rule_file() {
    $this->auto_render = FALSE;
    $start = time();
    $totaldone = $_GET['totaldone'];
    // Find the cached list of files we are processing.
    $uploadId = $_GET['uploadId'];
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "r");
    $cacheData = fread($cacheHandle, 10000000);
    fclose($cacheHandle);
    $cacheArr = json_decode($cacheData, TRUE);
    $response = array('files' => [], 'errors' => []);
    // Do whatever we can get done in 10 seconds.
    while (time() < $start + 10 && $totaldone < count($cacheArr['files'])) {
      try {
        $response['files'][] = $this->internalUploadRuleFile($totaldone, $cacheArr);
      }
      catch (Exception $e) {
        error_logger::log_error('Verification rule import', $e);
        $response['errors'][] = $e->getMessage();
      }
      $totaldone++;
    }
    if ($totaldone >= count($cacheArr['files'])) {
      $response['complete'] = TRUE;
      $response['progress'] = 100;
      // Clean up the cached list of files to process.
      unlink(DOCROOT . "extract/$uploadId.txt");
      foreach ($cacheArr['paths'] as $path) {
        $this->deleteDir($path['file']);
      }
    }
    else {
      $response['progress'] = ($totaldone * 100) / count($cacheArr['files']);
    }
    $response['totaldone'] = $totaldone;
    echo json_encode($response);
  }

  private function internalUploadRuleFile($totaldone, $cacheArr) {
    $filepath = $cacheArr['files'][$totaldone]['file'];
    // Try fopen as more likely to work for local files.
    $resource = fopen($filepath, 'r');
    if ($resource === FALSE) {
      throw new exception("Could not open file $filepath");
    }
    $filecontent = fread($resource, 1000000);
    // If no BOM, not unicode, so convert for safety. See Warehouse issue 22.
    if (strpos($filecontent, "\xEF\xBB\xBF") !== 0) {
      $filecontent = mb_convert_encoding($filecontent, 'UTF-8', 'ISO-8859-1');
    }
    $settings = data_cleaner::parseTestFile($filecontent);
    $this->readRuleContent($settings, $cacheArr['files'][$totaldone]['path'], $cacheArr['files'][$totaldone]['source_url']);
    return $cacheArr['files'][$totaldone]['display'];
  }

  /**
   * Recursively deletes the contents of a directory.
   *
   * @param string $dirPath
   *   Directory.
   */
  private function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
      throw new InvalidArgumentException('$dirPath must be a directory');
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/' && substr($dirPath, strlen($dirPath) - 1, 1) != '\\') {
      $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    if (!empty($files)) {
      foreach ($files as $file) {
        if (is_dir($file)) {
          $this->deleteDir($file);
        }
        else {
          unlink($file);
        }
      }
    }
    rmdir($dirPath);
  }

  /**
   * Process the content of a verification rule file.
   */
  private function readRuleContent($rulesettings, $filename, $source_url) {
    if (!isset($rulesettings['metadata'])) {
      throw new exception("Missing Metadata section in $filename");
    }
    if (!isset($rulesettings['metadata']['testtype'])) {
      throw new exception("Missing Metadata TestType value in $filename");
    }
    require_once MODPATH . 'data_cleaner/plugins/data_cleaner.php';
    $currentRule = data_cleaner::getRule($rulesettings['metadata']['testtype']);
    // Ensure that the required key/value pairs for this rule type are all
    // present.
    foreach ($currentRule['required'] as $category => $keys) {
      $category = strtolower($category);
      foreach ($keys as $key) {
        // Every key must exist. A * key means that anything is accepted.
        if ($key = '*') {
          if (!isset($rulesettings[$category])) {
            throw new exception("Missing content for $category section in $filename. " . print_r($rulesettings, TRUE));
          }
        }
        elseif (!isset($rulesettings[$category][$key])) {
          throw new exception("Missing $category $key value in $filename");
        }
      }
    }
    $this->model->save_verification_rule($source_url, $filename, $rulesettings['metadata']);
    $this->model->save_verification_rule_metadata($currentRule, $rulesettings['metadata']);
    unset($rulesettings['metadata']);
    if (!empty($rulesettings)) {
      $this->model->save_verification_rule_data($currentRule, $rulesettings);
    }
    $this->model->postProcessRule($currentRule);
    $this->model->updateCache();
  }

  public function uploadRuleCsv($file) {
    try {
      // We will be using a POST array to send data, and presumably a FILES array for the
      // media.
      // Upload size.
      $ups = Kohana::config('indicia.maxUploadSize');
      $_FILES = Validation::factory($_FILES)->add_rules(
        'zipOrCsvFile', 'upload::valid',
        'upload::type[csv]', "upload::size[$ups]"
      );
      if (count($_FILES) === 0) {
        echo "No file was uploaded.";
      }
      elseif ($_FILES->validate()) {
        $finalName = time() . strtolower($file['name']);
        $fTmp = upload::save('zipOrCsvFile', $finalName);
        url::redirect('verification_rule/csv_import_progress?uploaded_csv=' . urlencode(basename($fTmp)));
      }
      else {
        kohana::log('error', 'Validation errors uploading file ' . $_FILES['csv_upload']['name']);
        kohana::log('error', print_r($_FILES->errors('form_error_messages'), TRUE));
        throw new ValidationError('Validation error', 2004, $_FILES->errors('form_error_messages'));
      }
    }
    catch (Exception $e) {
      $this->handle_error($e);
    }
  }

  /**
   * Controller method for the csv_import_progress path.
   *
   * Displays the upload template with progress bar and status message, which
   * then initiates the actual import.
   */
  public function csv_import_progress() {
    if (file_exists(kohana::config('upload.directory') . "/$_GET[uploaded_csv]")) {
      $this->template->content = new View('verification_rule/upload_csv');
      $this->template->title = 'Uploading verification rule files';
      $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
      $handle = fopen($csvTempFile, "r");
      // First row, so load the column headings. Force lowercase so we can
      // case insensitive search later.
      $headings = array_map('strtolower', fgetcsv($handle, 1000, ","));
      $obj = array('headings' => $headings);
      $filepos = ftell($handle);
      $obj['ruleIdColIdx'] = array_search('ruleid', $headings);
      if ($obj['ruleIdColIdx'] === FALSE) {
        $this->session->set_flash('flash_error', 'The CSV upload file must contain a RuleID column to provide unique '.
            'identifiers for each rule');
        $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
        unlink($csvTempFile);
        url::redirect('verification_rule/index');
      }
      $cache = Cache::instance();
      $cache->set(basename($_GET['uploaded_csv']) . 'metadata', $obj);
    }
  }

  /**
   * CSV upload callback.
   *
   * AJAX callback to handle upload of a single chunk of designations
   * spreadsheet.
   */
  public function csv_upload() {
    try {
      $this->auto_render = FALSE;
      $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
      $cache = Cache::instance();
      if (file_exists($csvTempFile)) {
        // Create the file pointer, plus one for errors.
        $handle = fopen($csvTempFile, "r");
        $count = 0;
        $limit = (isset($_GET['limit']) ? $_GET['limit'] : FALSE);
        $filepos = (isset($_GET['filepos']) ? $_GET['filepos'] : 0);
        $offset = (isset($_GET['offset']) ? $_GET['offset'] : 0);
        // Skip rows to allow for the last file position.
        fseek($handle, $filepos);
        if ($filepos == 0) {
          // Skip the headers.
          fgetcsv($handle, 1000, ",");
        }
        $obj = $cache->get(basename($_GET['uploaded_csv']) . 'metadata');
        $errors = [];
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && ($limit === FALSE || $count < $limit)) {
          $line = implode('', $data);
          // Skip blank lines.
          if (empty($line)) {
            continue;
          }
          $count++;
          $filepos = ftell($handle);
          $ruleSettings = $this->parseCsvRow($obj['headings'], $data);
          $uniqueId = $data[$obj['ruleIdColIdx']];
          if (!empty($uniqueId)) {
            try {
              $this->readRuleContent($ruleSettings, $uniqueId, 'csv');
            }
            catch (Exception $e) {
              $errors[] = $e->getMessage();
              error_logger::log_error('Error during Data Cleaner module CSV upload.', $e);
            }
          }
        }
      }
      $progress = $filepos * 100 / filesize($csvTempFile);
      $r = array(
        'uploaded' => $count,
        'progress' => $progress,
        'filepos' => $filepos,
        'errors' => $errors
      );
      if (count($errors)) {
        kohana::log('debug', 'Upload CSV rule errors: ' . print_r($errors, TRUE));
      }
      else {
        kohana::log('debug', 'no errors');
      }
      ob_clean();
      echo json_encode($r);
      fclose($handle);
    }
    catch (Exception $e) {
      error_logger::log_error('Error during Data Cleaner module CSV upload.', $e);
      throw $e;
    }
  }

  /**
   * Parse a row of CSV.
   *
   * Converts a row of CSV data to the structured array representing the
   * verification rule.
   *
   * @param array $headings
   *   Array of CSV column headings.
   * @param array $data
   *   Array of CSV column values.
   *
   * @return array
   *   Structured array defining the rule
   */
  private function parseCsvRow(array $headings, array $data) {
    $r = [];
    foreach ($data as $idx => $value) {
      $tokens = explode(':', $headings[$idx]);
      // Skip columns that are not correct format as assumed to be irrelevant.
      if (count($tokens) === 2) {
        // Store a new section heading.
        if (!isset($r[$tokens[0]])) {
          $r[$tokens[0]] = [];
        }
        $r[$tokens[0]][$tokens[1]] = $value;
      }
    }
    return $r;
  }

  /**
   * Controller method for the upload_complate path.
   *
   * Called at the end of upload. Displays a message about the number of
   * designations uploaded, cleans the cache and upload file, then navigaes to
   * the taxon designation index page.
   */
  public function csv_upload_complete() {
    $this->session->set_flash('flash_info', "$_GET[total] rules were uploaded.");
    $cache = Cache::instance();
    $cache->delete(basename($_GET['uploaded_csv']) . 'metadata');
    $csvTempFile = DOCROOT . "upload/$_GET[uploaded_csv]";
    unlink($csvTempFile);
    url::redirect('verification_rule/index');
  }

  /**
   * Page access authorisation.
   *
   * Enforce that a user must be at least a website admin to see the list of
   * verification rules.
   */
  public function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }

}
