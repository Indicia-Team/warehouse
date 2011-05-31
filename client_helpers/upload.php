<?php
/**
 * upload.php
 *
 * Copyright 2009, Moxiecode Systems AB
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
  
  require('helper_config.php');
  require('data_entry_helper.php');

  // Settings. Note the interim image folder may not be in helper_config in which case use a default
  $interim_image_folder = isset(helper_config::$interim_image_folder) ? helper_config::$interim_image_folder : 'upload/';
  $targetDir = dirname(__FILE__) . '/' . $interim_image_folder;
  $cleanupTargetDir = false; // Remove old files
  $maxFileAge = 60 * 60; // Temp file age in seconds
  // 5 minutes execution time
  @set_time_limit(5 * 60);
  // usleep(5000);

  // Get parameters
  $chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
  $chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
  $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';  

  // Clean the fileName for security reasons
  $fileName = preg_replace('/[^\w\._]+/', '', $fileName);
  // Create target dir
  if (!file_exists($targetDir))
    @mkdir($targetDir);
  $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

  if (!file_exists($targetDir)) {
    echo '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to create upload directory."}, "id" : "id"}';
    return;
  }
  
  // Remove old temp files
  if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
    while (($file = readdir($dir)) !== false) {
      $filePath = $targetDir . DIRECTORY_SEPARATOR . $file;

      // Remove temp files if they are older than the max age
      if (preg_match('/\\.tmp$/', $file) && (filemtime($filePath) < time() - $maxFileAge))
        @unlink($filePath);
    }

    closedir($dir);
  } else {
    echo '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}';
    return;
  }
  
  // Look for the content type header
  if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
    $contentType = $_SERVER["HTTP_CONTENT_TYPE"];

  if (isset($_SERVER["CONTENT_TYPE"]))
    $contentType = $_SERVER["CONTENT_TYPE"];

  if (strpos($contentType, "multipart") !== false) {
    if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
      // Open temp file
      $out = fopen($targetPath, $chunk == 0 ? "wb" : "ab");
      if ($out) {
        // Read binary input stream and append it to temp file
        $in = fopen($_FILES['file']['tmp_name'], "rb");
        if ($in) {
          while ($buff = fread($in, 4096))
            fwrite($out, $buff);
        } else {
          echo '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}';
          return;
        }
        fclose($out);
        unlink($_FILES['file']['tmp_name']);
      } else {
        echo '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to save the uploaded file."}, "id" : "id"}';
        return;
      }
    } else {
      echo '{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file. '.$_FILES['file']['tmp_name'].'"}, "id" : "id"}';
      return;
    }
  } else {
    // Open temp file
    $out = fopen($targetPath, $chunk == 0 ? "wb" : "ab");
    if ($out) {
      // Read binary input stream and append it to temp file
      $in = fopen("php://input", "rb");

      if ($in) {
        while ($buff = fread($in, 4096))
          fwrite($out, $buff);
      } else {
        echo '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}';
        return;
      }

      fclose($out);
    } else {
      echo '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to save the uploaded file."}, "id" : "id"}'; 
      return;
    }
  }

  //test uploaded file size
  clearstatcache();
  $file['size'] = filesize($targetPath);
  $file['error'] = '';
  if (!data_entry_helper::check_upload_size($file)) {
    unlink($targetPath);
      echo '{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "Uploaded file too big."}, "id" : "id"}'; 
      return;
  }
  
  // Return JSON-RPC success response
  echo '{"jsonrpc" : "2.0", "result" : null, "id" : "id"}';
?>