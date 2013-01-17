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
 * @package	Taxon Designations
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

require_once(DOCROOT.'client_helpers/helper_base.php');

/**
 * Controller class for the data cleaner plugin module.
 */
class Verification_rule_Controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('verification_rule', 'verification_rule/index');
    $this->columns = array(
      'title'       => '',
      'description' => '',
      'test_type'   => ''
    );
    $this->pagetitle = "Verification Rules";
    $this->model = ORM::factory('verification_rule');
  }
  
  /**
   * Index controller action. Load the list of verification rule servers.
   */
  public function index() {
    parent::index();
    // Load the rule files from the server
    $list = $this->get_server_list();
    $this->view->serverList = $list;
  }
  
  /**
   * Returns an array of all values from this model ready to be loaded into a form. 
   * For this controller, we need to also setup text for the "other data" section.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $r['metaFields:metadata'] = $this->get_metadata_as_text();
    $r['metaFields:data'] = $this->get_data_as_text();
    return $r;
  }
  
  private function get_metadata_as_text() {
    if ($this->model->id) {
      $items = $this->db->select('key, value')
        ->from('verification_rule_metadata')
        ->where(array('verification_rule_id'=>$this->model->id, 'deleted'=>'f'))
        ->orderby(array('id'=>'ASC'))
        ->get()->result();
      $outputs=array();
      foreach ($items as $item) {
        $outputs[] = $item->key.'='.$item->value;
      }
      $r = implode("\n",$outputs);
      return $r;
    } else
      return '';
  }
  
  private function get_data_as_text() {
    $currentHeader = '';
    if ($this->model->id) {
      $items = $this->db->select('header_name, data_group, key, value')
        ->from('verification_rule_data')
        ->where(array('verification_rule_id'=>$this->model->id, 'deleted'=>'f'))
        ->orderby(array('data_group'=>'ASC', 'id'=>'ASC'))
        ->get()->result();
      $outputs=array();
      foreach ($items as $item) {
        if ($item->header_name!==$currentHeader) {
          $outputs[] = '['.$item->header_name.']';
          $currentHeader = $item->header_name;
        }
        $row = $item->key;
        if (!empty($item->value)&&$item->value!=='-')
          $row .= '='.$item->value;
        $outputs[] = $row;
      }
      $r = implode("\n",$outputs);
      return $r;
    } else
      return '';
  }
  
  /**
   * Returns the list of servers from the remote server configuration file.
   * @return type 
   */
  private function get_server_list() {
    $session = curl_init(kohana::config('data_cleaner.servers'));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $r = array();
    $response = curl_exec($session);
    if (curl_errno($session)) {
      $this->session->set_flash('flash_info', 'The list of verification rule servers could not be retrieved from the internet.');
      kohana::log('error', 'Error occurred when retrieving list of verification rule servers. '.curl_error($session));
      return array();
    }
    
    foreach(helper_base::explode_lines($response) as $line) {
      $tokens = explode('#', $line);
      $r[] = array(
        'file' => $tokens[0],
        'author' => $tokens[1],
        'date' => $tokens[2]
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
      if (strcasecmp($ext, 'zip')===0)
        $this->upload_rule_zip($_FILES['zipOrCsvFile']);
      elseif (strcasecmp($ext, 'csv')===0)
        $this->upload_rule_csv($_FILES['zipOrCsvFile']);
      else {
        $this->session->set_flash('flash_error', "Incompatible file selected. The rule file upload requires a zipped file ".
            "containing Record Cleaner compatible rule files or a CSV file containing rule definitions.");
      }
    } else {
      $this->display_progress_template_for_server_fetch();
    }
  }
  
  /**
   * Uploading from a zipped batch of rule files. Displays 
   * the upload template with progress bar and status message, which then initiates the actual import.
   */
  private function upload_rule_zip($zipfile) {
    $ruleFiles = array();
    $dir = $this->process_rule_zip_file($zipfile['tmp_name'], true);
    $dir_iterator = new RecursiveDirectoryIterator($dir);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $relativePath = substr($file->getRealPath(), strlen(realpath("$dir/extract")));
        $ruleFiles[] = array(
          'file'=>$file->__toString(),
          'source_url'=>$zipfile['name'],
          'display'=>basename($zipfile['name']).' '.$relativePath
        );
      }
    }
    // Save the rule file list to a cached list, so we can preserve it across http requests
    $uploadId = time() . md5($zipfile['tmp_name']);
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "w");
    fwrite($cacheHandle, json_encode(array('paths'=>array(array(
        'file'=>$dir,
        'source_url'=>$zipfile['name'],
        'title'=>basename($zipfile['name'])
      )), 'files'=>$ruleFiles)));
    fclose($cacheHandle);
    //  show a progress view.
    $view = new View('verification_rule/upload_rule_files');
    $view->uploadId = $uploadId;
    $view->requiresFetch='false';
    $this->template->content = $view;
    $this->template->title = 'Uploading rule files';
  }
   
  private function display_progress_template_for_server_fetch() {
    //  show a progress view.
    $view = new View('verification_rule/upload_rule_files');
    // generate a unique ID for the upload
    $uploadId = time() . md5($_POST['server']);
    $view->uploadId = $uploadId;
    $view->requiresFetch='true';
    $this->template->content = $view;    
    $this->template->title = 'Fetching rule files from server';
    $zipfiles = $this->fetch_server_file_list($_POST['server']);
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "w");
    fwrite($cacheHandle, json_encode(array('zipfiles'=>$zipfiles, 'done'=>0, 'paths'=>array(), 'files'=>array())));
    fclose($cacheHandle);
  }
  
  /**
   * Controller action for loading rule files from the verification rule server.
   */
  public function fetch_file_chunk() {
    $this->auto_render=false;
    // load our cached status
    $uploadId = $_GET['uploadId'];
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "r");
    $cacheData = fread($cacheHandle,10000000);
    fclose($cacheHandle);
    $cacheArr = json_decode($cacheData,true);
    $file = $cacheArr['zipfiles'][$cacheArr['done']];
    $path = array(
      'file'=>$this->process_rule_zip_file($file['file']),
      'source_url'=>$file['file'],
      'title'=>$file['title']
    );
    // unzip the file at the path and work out what files it contains
    $ruleFiles=array();
    $dir_iterator = new RecursiveDirectoryIterator($path['file'].'/extract');
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $relativePath = substr($file->getRealPath(), strlen(realpath($path['file'].'/extract')));
        $ruleFiles[] = array(
          'file'=>$file->__toString(),
          'source_url'=>$path['source_url'],
          'display'=>$path['title'].$relativePath
        );
      }
    }
    $cacheArr['paths'][]=$path;
    $cacheArr['files']=array_merge(
      $cacheArr['files'],
      $ruleFiles
    );
    $cacheArr['done']++;
    // Save the rule file list to a cached list, so we can preserve it across http requests
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "w");
    fwrite($cacheHandle, json_encode($cacheArr));
    fclose($cacheHandle);
    echo json_encode(array('progress'=>$cacheArr['done']*100/count($cacheArr['zipfiles'])));
  }
  
  /**
   * Finds a record cleaner server index page and returns the list of files it refers to.
   */
  private function fetch_server_file_list($server) {
    $session = curl_init($server);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $files = array();
    foreach(helper_base::explode_lines(curl_exec($session)) as $line) {
      $tokens = explode('#', $line);
      $files[] = array(
        'file' => $tokens[0],
        'title' => $tokens[1],
        'date' => $tokens[2]
      );
    }
    return $files;
  }
  
  /**
   * Loads a remote zip file, extracts the rule files and processes them.
   * @param string $sourcefile Path to a file.
   * @param bool $local Is the file local or remote?
   * @return bool Returns the unzipped directory location.
   */
  private function process_rule_zip_file($sourcefile, $local=false) {
    kohana::log('debug', 'processing '.$sourcefile);
    try {
      $dir = $this->create_zip_extract_dir().'rules-'.time().'-'.rand(0,1000);
    } catch (Exception $e) {
      throw new Exception('Could not create the extract directory on the warehouse');
    }
    mkdir($dir, 0777, TRUE);
    mkdir("$dir/extract", 0777, TRUE);
    if ($local) {
      // file is local so can just unzip it.
      $zipFile = $sourcefile;
    } else {
      $zipFile = "$dir/".basename($sourcefile);
      $fh = fopen($zipFile, "wb");
      // str_replace used here for spaces in file names, I would have thought urlencode would work but apparently not...
      $session = curl_init(str_replace(' ','%20',$sourcefile));
      curl_setopt($session, CURLOPT_FILE, $fh);
      curl_exec($session);
      if (curl_errno($session)) 
        throw new exception("Error downloading zip file $sourcefile: ".curl_error($session));
      curl_close($session);
    }
    $zip = new ZipArchive;
    $res = $zip->open($zipFile);
    $zip->extractTo("$dir/extract/");
    return $dir;
  }
  
  /**
   * AJAX handler to upload a single rule file from a folder.
   */
  public function upload_rule_file() {
    $this->auto_render=false;
    $start=time();
    $totaldone=$_GET['totaldone'];
    // find the cached list of files we are processing.
    $uploadId = $_GET['uploadId'];
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "r");
    $cacheData = fread($cacheHandle,10000000);
    fclose($cacheHandle);
    $cacheArr = json_decode($cacheData,true);
    $response=array('files'=>array(),'errors'=>array());
    // Do whatever we can get done in 10 seconds. 
    while (time()<$start+10 && $totaldone<count($cacheArr['files'])) {
      try {
        $response['files'][] = $this->internal_upload_rule_file($totaldone, $cacheArr);      
      } catch (Exception $e) {
        error::log_error('Verification rule import', $e);
        $response['errors'][]=$e->getMessage();
      }
      $totaldone++;
    }
    if ($totaldone>=count($cacheArr['files'])) {
      $response['complete']=true;
      $response['progress']=100;
      // clean up the cached list of files to process
      unlink(DOCROOT . "extract/$uploadId.txt");
      foreach($cacheArr['paths'] as $path) {
        $this->deleteDir($path['file']);
      }
    } else    
      $response['progress'] = ($totaldone * 100) / count($cacheArr['files']);
    $response['totaldone'] = $totaldone;
    echo json_encode($response);
  }
  
  private function internal_upload_rule_file($totaldone, $cacheArr) {
    $filepath = $cacheArr['files'][$totaldone]['file'];
    // try fopen as more likely to work for local files.
    $resource=fopen($filepath, 'r');
    if ($resource===false) {
      throw new exception("Could not open file $filepath");
    }
    $filecontent = fread($resource,1000000);
    $settings = data_cleaner::parse_test_file($filecontent);
    $this->read_rule_content($settings, basename($filepath), $cacheArr['files'][$totaldone]['source_url']);
    return $cacheArr['files'][$totaldone]['display'];
  }
  
  /**
   * Recursively deletes the contents of a directory
   */
  private function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
      throw new InvalidArgumentException('$dirPath must be a directory');
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/' && substr($dirPath, strlen($dirPath) - 1, 1) != '\\') {
      $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
      if (is_dir($file)) {
        $this->deleteDir($file);
      } else {
        unlink($file);
      }
    }
    rmdir($dirPath);
  }

  /**
   * Process the content of a verification rule file.
   */
  private function read_rule_content($rulesettings, $filename, $source_url) {
    if (!isset($rulesettings['metadata']))
      throw new exception("Missing Metadata section in $filename");
    if (!isset($rulesettings['metadata']['testtype']))
      throw new exception("Missing Metadata TestType value in $filename");
    require_once(MODPATH.'data_cleaner/plugins/data_cleaner.php');
    
    $currentRule = data_cleaner::get_rule($rulesettings['metadata']['testtype']);
    // Ensure that the required key/value pairs for this rule type are all present.
    foreach($currentRule['required'] as $category=>$keys) {
      $category = strtolower($category);
      foreach($keys as $key) {
        // every key must exist. A * key means that anything is accepted.
        if ($key='*') {
          if (!isset($rulesettings[$category]))
            throw new exception("Missing content for $category section in $filename. ".print_r($rulesettings, true));
        } elseif (!isset($rulesettings[$category][$key]))
          throw new exception("Missing $category $key value in $filename");
      }
    }
    $this->model->save_verification_rule($source_url, $filename, $rulesettings['metadata']);
    
    $this->model->save_verification_rule_metadata($currentRule, $rulesettings['metadata']);
    
    unset($rulesettings['metadata']);
    if (!empty($rulesettings))
      $this->model->save_verification_rule_data($currentRule, $rulesettings);
    
    $this->model->postProcess($currentRule);

  }
  
  public function upload_rule_csv($file) {
    try
    {
      // We will be using a POST array to send data, and presumably a FILES array for the
      // media.
      // Upload size
      $ups = Kohana::config('indicia.maxUploadSize');
      $_FILES = Validation::factory($_FILES)->add_rules(
        'zipOrCsvFile', 'upload::valid', 
        'upload::type[csv]', "upload::size[$ups]"
      );
      if (count($_FILES)===0) {
        echo "No file was uploaded.";
      }
      elseif ($_FILES->validate())
      {
        $finalName = time().strtolower($file['name']);
        $fTmp = upload::save('zipOrCsvFile', $finalName);
        url::redirect('verification_rule/csv_import_progress?uploaded_csv='.urlencode(basename($fTmp)));
      }
      else
      {
        kohana::log('error', 'Validation errors uploading file '. $_FILES['csv_upload']['name']);
        kohana::log('error', print_r($_FILES->errors('form_error_messages'), true));
        Throw new ArrayException('Validation error', $_FILES->errors('form_error_messages'));
      }
    }
    catch (Exception $e)
    {
      $this->handle_error($e);
    }
  }
  
    /**
   * Controller method for the import_progress path. Displays the upload template with 
   * progress bar and status message, which then initiates the actual import.
   */
  public function csv_import_progress() {
    if (file_exists(kohana::config('upload.directory').'/'.$_GET['uploaded_csv'])) {
      $this->template->content = new View('verification_rule/upload_csv');
      $this->template->title = 'Uploading verification rule files';
      $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
      $handle = fopen ($csvTempFile, "r");
      // first row, so load the column headings. Force lowercase so we can case insensitive search later. 
      $headings = array_map('strtolower',fgetcsv($handle, 1000, ","));
      $obj = array('headings'=>$headings);
      $filepos = ftell($handle);
      $obj['ruleIdColIdx'] = array_search('ruleid', $headings);
      if ($obj['ruleIdColIdx']===false) {
        $this->session->set_flash('flash_error', 'The CSV upload file must contain a RuleID column to provide unique '.
            'identifiers for each rule');
        $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
        unlink($csvTempFile);
        url::redirect('verification_rule/index');
      }
      $cache= Cache::instance();
      $cache->set(basename($_GET['uploaded_csv']).'metadata', $obj);          
    }
  }
  
  /**
   * AJAX callback to handle upload of a single chunk of designations spreadsheet.
   */
  public function csv_upload() {
    try {
      $this->auto_render=false;
      $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
      $cache= Cache::instance();
      if (file_exists($csvTempFile))
      {
        // Following helps for files from Macs
        ini_set('auto_detect_line_endings',1);
        // create the file pointer, plus one for errors
        $handle = fopen ($csvTempFile, "r");
        $count=0;
        $limit = (isset($_GET['limit']) ? $_GET['limit'] : false);
        $filepos = (isset($_GET['filepos']) ? $_GET['filepos'] : 0);
        $offset = (isset($_GET['offset']) ? $_GET['offset'] : 0);
        // skip rows to allow for the last file position
        fseek($handle, $filepos);
        if ($filepos==0) 
          // skip the headers
          fgetcsv($handle, 1000, ",");
        $obj = $cache->get(basename($_GET['uploaded_csv']).'metadata');
        $errors = array();
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && ($limit===false || $count<$limit)) {
          $line = implode('', $data);
          // skip blank lines
          if (empty($line)) 
            continue;
          $count++;
          $filepos = ftell($handle);
          $ruleSettings = $this->parse_csv_row($obj['headings'], $data);
          $uniqueId = $data[$obj['ruleIdColIdx']];
          if (!empty($uniqueId)) 
            try {
              $this->read_rule_content($ruleSettings, $uniqueId, 'csv');
            } catch (Exception $e) {
              $errors[]=$e->getMessage();
              error::log_error('Error during Data Cleaner module CSV upload.', $e);
            }
        }
      }
      $progress = $filepos * 100 / filesize($csvTempFile);
      $r = array('uploaded'=>$count, 'progress'=>$progress, 'filepos'=>$filepos,'errors'=>$errors);
      if (count($errors))
        kohana::log('debug', 'Upload CSV rule errors: '.print_r($errors, true));
      else
        kohana::log('debug', 'no errors');
      ob_clean();
      echo json_encode($r);
      fclose($handle);
    } catch (Exception $e) {
      error::log_error('Error during Data Cleaner module CSV upload.', $e);
      throw $e;
    }
  }
  
  /**
   * Converts a row of CSV data to the structured array representing the verification rule.
   * @param type $headings Array of CSV column headings
   * @param type $data Array of CSV column values.
   * @return array Structured array defining the rule
   */
  private function parse_csv_row($headings, $data) {
    $r = array();
    foreach ($data as $idx=>$value) {
      $tokens = explode(':', $headings[$idx]);
      // skip columns that are not correct format as assumed to be irrelevant
      if (count($tokens)===2) {
        // store a new section heading
        if (!isset($r[$tokens[0]]))
          $r[$tokens[0]] = array();
        $r[$tokens[0]][$tokens[1]] = $value;
      }
    }
    return $r;
  }
  
  /**
   * Controller method for the upload_complate path, called at the end of upload.
   * Displays a message about the number of designations uploaded, cleans the cache
   * and upload file, then navigaes to the taxon designation index page.
   */
  public function csv_upload_complete() {
    $this->session->set_flash('flash_info', $_GET['total']." rules were uploaded.");
    $cache= Cache::instance();
    $cache->delete(basename($_GET['uploaded_csv']).'metadata');
    $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
    unlink($csvTempFile);
    url::redirect('verification_rule/index'); 
  }
  
  /**
   * Enforce that a user must be at least a website admin to see the list of verification rules.
   */
  public function page_authorised()
  {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }
  
}

?>