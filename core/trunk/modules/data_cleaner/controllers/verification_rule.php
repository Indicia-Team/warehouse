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
            'date' => $tokens[2],
            'source_url'=>$serverList[$idx]['file']
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
          'source_url'=>$file['source_url']
        );
      $ruleFiles = array();
      foreach ($paths as $path) {
        $dir = opendir($path['file'].'/extract');
        while (false !== $ruleFile = readdir($dir))
          if (substr($ruleFile, 0, 1)!=='.')
            $ruleFiles[] = array(
              'file'=>$path['file']."/extract/$ruleFile",
              'source_url'=>$path['source_url']
            );
      }
      // Save the rule file list to a cached list, so we can preserve it across http requests
      $uploadId = time() . md5($uniqueUploadKey);
      $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "w");
      fwrite($cacheHandle, json_encode($ruleFiles));
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
    $session = curl_init($sourcefile);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($session);
    try {
      $dir = $this->create_zip_extract_dir().'rules-'.time().'-'.rand(0,1000);
    } catch (Exception $e) {
      throw new Exception('Could not create the extract directory on the warehouse');
    }
    mkdir($dir, 0777, TRUE);
    mkdir("$dir/extract", 0777, TRUE);
    $zipFile = "$dir/".basename($sourcefile);
    file_put_contents($zipFile, $content);
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
    // find the cached list of files we are processing.
    $uploadId = $_GET['uploadId'];
    $cacheHandle = fopen(DOCROOT . "extract/$uploadId.txt", "r");
    $content = fread($cacheHandle,100000);
    fclose($cacheHandle);
    $files = json_decode($content,true);
    $filepath = $files[$_GET['totaldone']]['file'];
    // try fopen as more likely to work for local files.
    $resource=fopen($filepath, 'r');
    if ($resource!==false) {
      $filecontent = fread($resource,100000);
    } else {
      // try curl as more likely to work for remote files
      $session = curl_init();
      curl_setopt ($session, CURLOPT_URL, $fullpath);
      curl_setopt($session, CURLOPT_HEADER, false);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $filecontent = curl_exec($session);
      if (curl_errno($session)) {
        echo json_encode(array(
          'error' => curl_error($session),
          'filepath' => $fullpath
        ));
        return;
      }
    }
    $response = array('lastfile'=>basename($filepath));
    
    try {
      $this->read_file_content($filecontent, basename($filepath), $files[$_GET['totaldone']]['source_url']);
    } catch (Exception $e) {
      kohana::log('debug', $e->getMessage());
      $response['error']=$e->getMessage();
    }
    if ($_GET['totaldone']>=count($files)-1) {
      $response['complete']=true;
      // clean up the cached list of files to process
      unlink(DOCROOT . "extract/$uploadId.txt");
      // @todo clean up the extract directory
    }
    $reponse['progress'] = (($_GET['totaldone']+1) * 100) / count($files);
    echo json_encode($response);
  
  }
  
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
    
    if ($vr->id!==0) 
      $submission['verification_rule:id']=$vr->id;
    if (isset($fileSettings['Metadata']['Description']))
      $submission['verification_rule:description']=$fileSettings['Metadata']['Description'];
    $vr->set_submission_data($submission);
    $vr->submit();
    kohana::log('debug', 'vr saved');
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
    // counter to keep track of groups of related field values in a data section
    $dataGroup=1;
    foreach($fields as $dataSection=>$dataContent) {
      foreach ($dataContent as $field) {
        if ($field==='*') {
          // * means that any field value is allowed
          foreach ($fileSettings[$dataSection] as $anyField=>$anyValue)
            $this->save_verification_rule_data($vr->id, $dataSection, $dataGroup, $anyField, $anyValue);
        }
        elseif (isset($fileSettings[$dataSection][$field])) 
          // doing specific named fields
          $this->save_verification_rule_data($vr->id, $dataSection, $dataGroup, $field, $fileSettings[$dataSection][$field]);
      }
    }
  }
  
  /**
   * Save a verification rule data record, either overwriting existing or creating a new one.
   * Avoids ORM for performance reasons as some files can be pretty big.
   * @param type $vrId
   * @param type $dataSection
   * @param type $dataGroup
   * @param type $field
   * @param type $value 
   */
  private function save_verification_rule_data($vrId, $dataSection, $dataGroup, $field, $value) {
    $updated = $this->db->update('verification_rule_data', 
      array('value'=>$value, 'updated_on'=>date("Ymd H:i:s"), 'updated_by_id'=>$_SESSION['auth_user']->id), 
      array(
        'header_name'=>$dataSection, 'data_group'=>$dataGroup, 
        'verification_rule_id'=>$vrId, 'key'=>strval($field)
      )
    );
    if (!count($updated)) {
      $this->db->insert('verification_rule_data', array('header_name'=>$dataSection, 'data_group'=>$dataGroup, 
        'verification_rule_id'=>$vrId, 'key'=>strval($field), 'value'=>$value, 
        'updated_on'=>date("Ymd H:i:s"), 'updated_by_id'=>$_SESSION['auth_user']->id,
        'created_on'=>date("Ymd H:i:s"), 'created_by_id'=>$_SESSION['auth_user']->id));
      
    }
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
      } elseif (preg_match('/^(?P<key>.+)=(?P<value>.+)$/', $line, $matches)) {
        $currentSectionData[$matches['key']]=$matches['value'];
        
      }
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