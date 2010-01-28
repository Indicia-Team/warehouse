<?php
  require_once("data_entry_helper.php");

  $api_key                 = helper_config::$flickr_api_key;
  $api_secret              = helper_config::$flickr_api_secret;
  $default_redirect        = "/";
  $permissions             = "read";
  $path_to_phpFlickr_class = "./phpFlickr/";

  ob_start();
  require_once($path_to_phpFlickr_class . "phpFlickr.php");

  unset($_SESSION['phpFlickr_auth_token']);

  if (!empty($_GET['extra'])) {
    $redirect = $_GET['extra'];
  }

  $f = new phpFlickr($api_key, $api_secret);

  if (empty($_GET['frob'])) {
    $f->auth($permissions, false);
  } else {
    $f->auth_getToken($_GET['frob']);
  }
echo $redirect;
  if (empty($redirect)) {
    header("Location: " . $default_redirect);
  } else {
    header("Location: " . $redirect);
  }

?>