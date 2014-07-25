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

  // Check fileinfo extension is installed
  if (!extension_loaded('fileinfo')) {
    die('{"jsonrpc" : "2.0", "error" : {"code": 111, "message": "The fileinfo extension must be enabled by the website administrator in php.ini."}, "id" : "id"}');
  }
  
// Create target dir
  if (!file_exists($targetDir)) {
    @mkdir($targetDir);
  }
  if (!file_exists($targetDir)) {
    die('{"jsonrpc" : "2.0", "error" : {"code": 105, "message": "Failed to create upload directory."}, "id" : "id"}');
  }
  
  // Get a file name
  if (isset($_REQUEST["name"])) {
    $fileName = $_REQUEST["name"];
  } elseif (!empty($_FILES)) {
    $fileName = $_FILES["file"]["name"];
  } else {
    die('{"jsonrpc" : "2.0", "error" : {"code": 106, "message": "File has no name."}, "id" : "id"}');
  }
  // Clean the fileName for security reasons
  $fileName = preg_replace('/[^\w\._]+/', '', $fileName);
  
  // Test file extension is one of the allowed types
  $fileNameParts = explode('.', $fileName);
  if (count($fileNameParts) < 2) {
    die('{"jsonrpc" : "2.0", "error" : {"code": 107, "message": "File name has no extension."}, "id" : "id"}');
  }
  $extension = strtolower(array_pop($fileNameParts));
  $extensionFound = false;
  foreach(data_entry_helper::$upload_file_types as $mediaTypeFiles) {
    if (in_array($extension, $mediaTypeFiles)) {
      $extensionFound = true;
      break;
    }
  }
  if (!$extensionFound) {
    die('{"jsonrpc" : "2.0", "error" : {"code": 108, "message": "File type not allowed."}, "id" : "id"}');
  }
  
 
  // Chunking might be enabled
  $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
  $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

  $filePath  = $targetDir . DIRECTORY_SEPARATOR . $fileName;

  
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
    // Check MIME type of file    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, "{$filePath}.part");  
    finfo_close($finfo);
    if (!$mimeType) {
      unlink("{$filePath}.part");
      die('{"jsonrpc" : "2.0", "error" : {"code": 110, "message": "File type not known."}, "id" : "id"}'); 
    }
    list($mediaType, $mimeSubType) = split('/', $mimeType);
    if (!in_array($mimeSubType, data_entry_helper::$upload_mime_types[$mediaType], true)) {
      unlink("{$filePath}.part");
      die('{"jsonrpc" : "2.0", "error" : {"code": 109, "message": "File type not allowed."}, "id" : "id"}'); 
    }

    // File appears to be valid.
    // Strip the temp .part suffix off
    rename("{$filePath}.part", $filePath);
  }

  // Return JSON-RPC success response
  echo '{"jsonrpc" : "2.0", "result" : null, "id" : "id"}';
?>