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
   * Returns the list of servers from the remote server configuration file.
   * @return type 
   */
  private function get_server_list() {
    $session = curl_init(kohana::config('data_cleaner.servers'));
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $r = array();
    foreach($this->safe_explode_lines(curl_exec($session)) as $line) {
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
   * Controller action for loading rule files from the verification rule server.
   */
  public function load_from_server() {
    try {
      $serverList = $this->get_server_list();
      $uniqueUploadKey='';
      $files = array();
      foreach($_POST as $key=>$value) {
        $idx = substr($key, 4);
        $session = curl_init($serverList[$idx]['file']);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        foreach($this->safe_explode_lines(curl_exec($session)) as $line) {
          $tokens = explode('#', $line);
          $files[] = array(
            'file' => $tokens[0],
            'title' => $tokens[1],
            'date' => $tokens[2]
          );
        }
        // build a string we can use to create an upload identifier
        $uniqueUploadKey.=$serverList[$idx]['file'];
      }
      // extract all the rule files to get a set of temp paths, each of which contains
      // an extract folder with the files in it
      $paths = array();
      foreach($files as $file)
        $paths[] = array(
          'file'=>$this->process_rule_zip_file($file['title'], $file['file']),
          'source_url'=>$file['file'],
          'title'=>$file['title']
        );
      $ruleFiles = array();
      foreach ($paths as $path) {
        $dir_iterator = new RecursiveDirectoryIterator($path['file'].'/extract');
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
          if ($file->isFile()) {
            $relativePath = substr($file->getRealPath(), strlen(realpath($path['file'].'/extract')));
            $ruleFiles[] = array(
              'file'=>$file->__toString(),
              'source_url'=>$path['source_url'].$relativePath,
              'display'=>$path['title'].' '.$relativePath
            );
          }
        }
      }
      // Save the rule file list to a cached list, so we can preserve it across http requests
      $uploadId = time() . md5($uniqueUploadKey);
      $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "w");
      fwrite($cacheHandle, json_encode(array('paths'=>$paths, 'files'=>$ruleFiles)));
      fclose($cacheHandle);
      //  show a progress view.
      $view = new View('verification_rule/upload_rule_files');
      $view->uploadId = $uploadId;
      $this->template->content = $view;
      $this->template->title = 'Uploading rule files';
    } catch (Exception $e) {
      error::log_error('Error occurred during Record Cleaner rule file upload', $e);
      $view = new View('templates/error_message');
      $view->message=$e->getMessage();
      $this->template->content = $view;
      $this->template->title = 'Error occurred during upload';
    }
  }
  
  /**
   * Loads a remote zip file, extracts the rule files and processes them.
   * @param string $file Path to a remote file.
   * @return bool Returns true or an error string.
   */
  private function process_rule_zip_file($name, $sourcefile) {
    try {
      $dir = $this->create_zip_extract_dir().'rules-'.time().'-'.rand(0,1000);
    } catch (Exception $e) {
      throw new Exception('Could not create the extract directory on the warehouse');
    }
    mkdir($dir, 0777, TRUE);
    mkdir("$dir/extract", 0777, TRUE);
    $zipFile = "$dir/".basename($sourcefile);
    $fh = fopen($zipFile, "wb");
    // str_replace used here for spaces in file names, I would have thought urlencode would work but apparently not...
    $session = curl_init(str_replace(' ','%20',$sourcefile));
    curl_setopt($session, CURLOPT_FILE, $fh);
    curl_exec($session);
    if (curl_errno($session)) 
      throw new exception("Error downloading zip file $sourcefile: ".curl_error($session));
    curl_close($session);
    $zip = new ZipArchive;
    $res = $zip->open($zipFile);
    $zip->extractTo("$dir/extract/");
    return $dir;
  }
  
  /**
   * Controller method for the upload_rule_files path. Displays the upload template with 
   * progress bar and status message, which then initiates the actual import.
   */
  public function upload_rule_files() {
    if (!empty($_POST['path'])) {
      $view = new View('verification_rule/upload_rule_files');
      $view->paths = array($_POST['path']);
      $this->template->content = $view;
      $this->template->title = 'Uploading rule files';
    } else {
      $this->session->set_flash('flash_info', 'Please enter a path to upload rule files from.');
      url::redirect('verification_rule/index');
    }
    
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
//      unlink(DOCROOT . "extract/$uploadId.txt");
      foreach($cacheArr['paths'] as $path) {
//        $this->deleteDir($path['file']);
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
    $this->read_file_content($filecontent, basename($filepath), $cacheArr['files'][$_GET['totaldone']]['source_url']);
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
  private function read_file_content($filecontent, $filename, $source_url) {
    $fileSettings = $this->parse_test_file($filecontent, true);
    if (!isset($fileSettings['Metadata']))
      throw new exception("Missing Metadata section in $filename");
    if (!isset($fileSettings['Metadata']['TestType']))
      throw new exception("Missing Metadata TestType value in $filename");
    require_once(MODPATH.'data_cleaner/plugins/data_cleaner.php');
    $rules = data_cleaner_get_rules();
    // Ensure that the required key/value pairs for this rule type are all present.
    foreach ($rules as $rule) {
      if (strcasecmp($rule['testType'], $fileSettings['Metadata']['TestType'])===0) {
        $currentRule = $rule;
        // found a rule plugin which understands this rule test type. What does it require?
        if (isset($rule['required']))
          foreach($rule['required'] as $category=>$keys) {
            foreach($keys as $key) {
              // every key must exist. A * key means that anything is accepted.
              if ($key='*') {
                if (!isset($fileSettings[$category]))
                  throw new exception("Missing content for $category section in $filename");
              } elseif (!isset($fileSettings[$category][$key]))
                throw new exception("Missing $category $key value in $filename");
            }
          }
        $found = true;
        break;
      } 
    }
    if (!isset($currentRule))
      throw new exception ('Test type '.$fileSettings['Metadata']['TestType']. ' not found');
    if (!isset($currentRule['required']))
      $currentRule['required']=array();
    if (!isset($currentRule['optional']))
      $currentRule['optional']=array();
    // find existing or new verification rule record
    $vr = ORM::Factory('verification_rule')->where(array('source_url'=>$source_url, 'source_filename'=>$filename))->find();
    if (isset($fileSettings['Metadata']['ShortName']))
      $title = $fileSettings['Metadata']['ShortName'];
    else {
      // no short name in the rule, so build a valid title
      $titleArr=array($fileSettings['Metadata']['TestType']);
      if (isset($fileSettings['Metadata']['Organisation']))
        $titleArr[] = $fileSettings['Metadata']['Organisation'];
      $title = implode(' - ', $titleArr);
    }
    if (isset($fileSettings['Metadata']['ErrorMsg']))
      $errorMsg = $fileSettings['Metadata']['ErrorMsg'];
    else
      $errorMsg = 'Test failed';
    $submission = array(
      'verification_rule:title'=>$title,
      'verification_rule:test_type'=>$fileSettings['Metadata']['TestType'],
      'verification_rule:source_url'=>$source_url,
      'verification_rule:source_filename'=>$filename,
      'verification_rule:error_message'=>$errorMsg,
    );
    $newRule = $vr->id===0;
    if (!$newRule)
      $submission['verification_rule:id']=$vr->id;
    if (isset($fileSettings['Metadata']['Description']))
      $submission['verification_rule:description']=$fileSettings['Metadata']['Description'];
    $vr->set_submission_data($submission);
    $vr->submit();
    if (count($vr->getAllErrors())>0)
      throw new exception("Errors saving $filename to database - ".print_r($vr->getAllErrors(), true));
    // work out the other fields to submit
    $fields = array_merge_recursive($currentRule['required'], $currentRule['optional']);
    if (isset($fields['Metadata'])) {
      foreach ($fields['Metadata'] as $field) {
        if (isset($fileSettings['Metadata'][$field])) {
          $vrm = ORM::Factory('verification_rule_metadatum')->where(array(
              'verification_rule_id'=>$vr->id, 'key'=>$field
          ))->find();
          $submission=array(
            'verification_rule_metadatum:key'=>$field,
            'verification_rule_metadatum:value'=>$fileSettings['Metadata'][$field],
            'verification_rule_metadatum:verification_rule_id'=>$vr->id
          );
          if ($vrm->id!==0)
            $submission['verification_rule_metadatum:id']=$vrm->id;
          $vrm->set_submission_data($submission);
          $vrm->submit();
          if (count($vrm->getAllErrors())>0)
            throw new exception("Errors saving $filename to database - ".print_r($vrm->getAllErrors(), true));
        }
      }
    }
    // Metadata done now. 
    unset($fields['Metadata']);
    // counter to keep track of groups of related field values in a data section. Not implemented properly 
    // at the moment but we are likely to need this e.g. for periodInYear checks with multiple stages.
    $dataGroup=1;
    $rows = array();
    foreach($fields as $dataSection=>$dataContent) {
      if (isset($fileSettings[$dataSection])) {
        foreach ($dataContent as $key) {
          if ($key==='*') {
            // * means that any field value is allowed
            foreach ($fileSettings[$dataSection] as $anyField=>$anyValue)
              $rows[] = array('dataSection'=>$dataSection, 'dataGroup'=>$dataGroup, 'key'=>$anyField, 'value'=>$anyValue);
          }
          elseif (isset($fileSettings[$dataSection][$key])) 
            // doing specific named fields
            $rows[] = array('dataSection'=>$dataSection, 'dataGroup'=>$dataGroup, 'key'=>$key, 'value'=>$fileSettings[$dataSection][$field]);
        }
      }
    }
    $this->save_verification_rule_data($vr->id, $rows, $newRule);
    // Is there any post processing for the rule plugin, e.g. to construct a geom from grid squares?
    $ppMethod = $currentRule['plugin'].'_data_cleaner_postprocess';
    require_once(MODPATH.$currentRule['plugin'].'/plugins/'.$currentRule['plugin'].'.php');
    if (function_exists($ppMethod)) {
      call_user_func($ppMethod, $vr->id, $this->db);
    }
  }
  
  /**
   * Save a verification rule data record, either overwriting existing or creating a new one.
   * Avoids ORM for performance reasons as some files can be pretty big.
   * @param integer $vrId
   * @param array $rows
   * @param bool $newRule Is this a new or existing rule?
   */
  private function save_verification_rule_data($vrId, $rows, $newRule) { //$dataSection, $dataGroup, $field, $value) {
    // only worth trying an update if we are updating an existing rule.
    if (!$newRule) {
      foreach ($rows as $idx=>$row) {
        $updated = $this->db->update('verification_rule_data', 
          array('value'=>$row['value'], 'updated_on'=>date("Ymd H:i:s"), 'updated_by_id'=>$_SESSION['auth_user']->id), 
          array(
            'header_name'=>$row['dataSection'], 'data_group'=>$row['dataGroup'], 
            'verification_rule_id'=>$vrId, 'key'=>strval($row['key'])
          )
        );
        // remove the stuff we have done via update because it already existed.
        if (count($updated)) {
          unset($row[$idx]);
        }
      }
    }
    // build a multirow insert as it is faster than doing lots of single inserts
    $value = '';
    foreach ($rows as $idx=>$row) {
      if ($value!=='')
        $value .= ',';
      $value .= "('".$row['dataSection']."',".$row['dataGroup'].",$vrId,'".strval($row['key'])."','".
          $row['value']."','".date("Ymd H:i:s")."',".$_SESSION['auth_user']->id.",'".date("Ymd H:i:s")."',".$_SESSION['auth_user']->id.")";
    }
    if (count($rows))
      $this->db->query("insert into verification_rule_data(header_name, data_group, verification_rule_id, key, value, ".
          "updated_on, updated_by_id, created_on, created_by_id) values $value");
  }
  
  /**
   * Parses a data cleaner verification rule test file into an array of sections, 
   * each contining an array of key value pairs.
   * Very similar to PHP's parse_ini_string but a bit more tolerant, e.g of comments used.
   * @param type $content Content of the verification rule test file.
   * @return array File structure array.
   */
  private function parse_test_file($content) {
    // break into lines, tolerating different line ending forms;
    $lines = $this->safe_explode_lines($content);
    $currentSection='';
    $currentSectionData=array();
    $r=array();
    foreach($lines as $line) {
      $line = trim($line);
      // skip comments and blank lines plus the end of the metadata section
      if (substr($line, 1)===';' || empty($line) || $line==='[EndMetadata]')
        continue;
      if (preg_match('/^\[(?P<section>.+)\]$/', $line, $matches)) {
        if (!empty($currentSectionData))
          $r[$currentSection]=$currentSectionData;
        // reset for the next section
        $currentSection = $matches['section'];
        $currentSectionData=array();
      } elseif (preg_match('/^(?P<key>.+)=(?P<value>.+)$/', $line, $matches)) 
        $currentSectionData[$matches['key']]=$matches['value'];
      elseif (preg_match('/^(?P<key>.+)$/', $line, $matches)) 
        $currentSectionData[$matches['key']]='-';
    }
    // set the final section content
    if (!empty($currentSectionData))
      $r[$currentSection]=$currentSectionData;
    return $r;
  }
  
  /**
   * Explode text into lines, tolerating different line endings.
   * @param string $text Text to explode into lines
   * @return array Text split into an array of lines.
   */
  private function safe_explode_lines($text) {
    $content = str_replace("\r\n", "\n", $text);
    $content = str_replace("\r", "\n", $text);
    $lines = explode("\n", trim($text));
    return $lines;
  }
  
}

?>