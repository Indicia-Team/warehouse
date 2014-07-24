<?php
/**
 * upload.php
 *
 * Copyright 2013, Moxiecode Systems AB
* Released under GPL License.
*
* License: http://www.plupload.com/license
* Contributing: http://www.plupload.com/contributing
*/

  // Only output real errors. We don't want warnings to break the JSON
  error_reporting(E_ERROR);

  // HTTP headers for no cache etc
  header('Content-type: text/html;');
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
  
  // 5 minutes execution time
  @set_time_limit(5 * 60);

  require('helper_config.php');
  require('data_entry_helper.php');

  // Settings. 
  // Note the interim image folder may not be in helper_config in which case use a default
  $interim_image_folder = isset(helper_config::$interim_image_folder) ? helper_config::$interim_image_folder : 'upload/';
  $targetDir = dirname(__FILE__) . '/' . $interim_image_folder;
  // Clenaup old .part upload files
  $cleanupTargetDir = true; 
  // Max .part file age in seconds
  $maxFileAge = 5 * 3600; 

  // Create target dir
  if (!file_exists($targetDir))
    @mkdir($targetDir);
  
  // Get a file name
  if (isset($_REQUEST["name"])) {
    $fileName = $_REQUEST["name"];
  } elseif (!empty($_FILES)) {
    $fileName = $_FILES["file"]["name"];
  } else {
    $fileName = uniqid("file_");
  }
  // Clean the fileName for security reasons
  $fileName = preg_replace('/[^\w\._]+/', '', $fileName);

  // Chunking might be enabled
  $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
  $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

  $filePath  = $targetDir . DIRECTORY_SEPARATOR . $fileName;

  if (!file_exists($targetDir)) {
    echo '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to create upload directory."}, "id" : "id"}';
    return;
  }
  
// Remove old temp files
if ($cleanupTargetDir) {
  if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
    die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
  }

  while (($file = readdir($dir)) !== false) {
    $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

    // If .part file is current file proceed to the next
    if ($tmpfilePath == "{$filePath}.part") {
      continue;
    }

    // Remove .part file if it is older than the max age 
    if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
      @unlink($tmpfilePath);
    }
  }
  closedir($dir);
}	

  // Open .part file for output
  if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
    die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
  }

  if (!empty($_FILES)) {
    if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
      die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
    }

    // Read binary input stream and append it to .psrt file
    if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
      die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
    }
  } else {	
    if (!$in = @fopen("php://input", "rb")) {
      die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
    }
  }

  while ($buff = fread($in, 4096)) {
    fwrite($out, $buff);
  }

  @fclose($out);
  @fclose($in);

  // Test file size after each chunk in case hacker has 
  // circumvented client-side check to send something huge.
  clearstatcache();
  $file['size'] = filesize("{$filePath}.part");
  $file['error'] = '';
  if (!data_entry_helper::check_upload_size($file)) {
    unlink("{$filePath}.part");
    die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "Uploaded file too big."}, "id" : "id"}'); 
  }
  
// Check if file has been uploaded
  if (!$chunks || $chunk == $chunks - 1) {
    // Strip the temp .part suffix off
    rename("{$filePath}.part", $filePath);
  }

  // Return JSON-RPC success response
  echo '{"jsonrpc" : "2.0", "result" : null, "id" : "id"}';
?>