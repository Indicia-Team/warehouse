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
 * @package Media
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */


$url = $_GET['url'];

if (strpos($url, "?")!==false){
  $url=$url."&";
} else {
  $url=$url."?";
}

$found = false;
$proxyParams = array("url");
foreach($_GET AS $key => $value)
{
  // Do not copy the url param, only everything after it. Must include blanks in this so that reports know when they
  // get passed a blank param.
  if ($found) {
    $url=$url."$key=$value&";
  }
  if ($key == "url"){
    $found =true;
  }
}
$url = str_replace ('\"','"',$url);
$url = str_replace (' ','%20',$url);

$session = curl_init($url);
// Set the POST options.
$httpHeader = array();
$postData = file_get_contents( "php://input" );
if (empty($postData))
  $postData = $_POST;
if (!empty($postData)) {
  curl_setopt($session, CURLOPT_POST, 1);
  curl_setopt($session, CURLOPT_POSTFIELDS, $postData);
  // post contains a raw XML document?
  if (is_string($postData) && substr($postData, 0, 1)=='<') {
    $httpHeader[]='Content-Type: text/xml';
  }
}
if (count($httpHeader)>0) {
  curl_setopt($session, CURLOPT_HTTPHEADER, $httpHeader);
}

curl_setopt($session, CURLOPT_HEADER, true);
curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);


// Do the POST and then close the session
$response = curl_exec($session);
if (curl_errno($session) || strpos($response, 'HTTP/1.1 200 OK')===false) {
  echo 'cUrl POST request failed. Please check cUrl is installed on the server.';
  if (curl_errno($session))
  echo 'Error number: '.curl_errno($session).'';
  echo "Server response ";
  echo $response;
} else {
  $offset = strpos($response, "\r\n\r\n");
  $headers = curl_getinfo($session);

  if (strpos($headers['content_type'], '/')!==false) {
    $arr = explode('/',$headers['content_type']);
    $fileType = array_pop($arr);
    if (strpos($fileType, ';')!==false) {
      $arr = explode(';', $fileType);
      $fileType = $arr[0];
    }
    header('Content-Disposition', 'attachment; filename=download.'.$fileType);

    if ($fileType=='csv') {
      // output a byte order mark for proper CSV UTF-8
      echo chr(239) . chr(187) . chr(191);
    }
  }
  if (array_key_exists('charset', $headers)) {
    $headers['content_type'] .= '; '.$headers['charset'];
  }
  header('Content-type: '.$headers['content_type']);

  // last part of response is the actual data
  $arr = explode("\r\n\r\n", $response);
  echo array_pop($arr);
}
curl_close($session);
