<?php

function failInitialise($msg) {
  $root = dirname($_SERVER['SCRIPT_NAME']);
  $html = <<<HTML
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Indicia Team">
    <title>Indicia Warehouse installation</title>
    <!-- Bootstrap core CSS -->
    <link href="$root/vendor/bootstrap/css/bootstrap.css" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" rel="stylesheet">
    <style>
      .alert {
        font-size: 18px;
      }
      .fas {
        margin-right: 8px;
      }
      .fa-exclamation-triangle {
        font-size: 30px;
      }
      li {
        list-style: none;
      }
    </style>
</head>

<body>
    <div class="container">
      <div class="row">
          <div class="col-lg-12">
              <h1>Installation requirements not met</h1>
              $msg
          </div>
      </div>
    </div>
    <script src="$root/media/js/jquery.js"></script>
    <script src="$root/vendor/bootstrap/js/bootstrap.js"></script>
</body>
</html>
HTML;
  die($html);
}

/**
 * Pre-installation initialisation.
 *
 * Function called by the index.php script when it realises installation not
 * done. Checks folder and file pre-requisites.
 */
function initialise() {
  // No config file, so this is a first run for install. Before creating the
  // config file, check some directory access.
  $readonlyDirs = array();
  if (!is_writable(dirname(__file__) . "/application/config/")) {
    $readonlyDirs[] = "config";
  }
  if (!is_writable(dirname(__file__) . "/application/logs/")) {
    $readonlyDirs[] = "logs";
  }
  if (!is_writable(dirname(__file__) . "/application/cache/")) {
    $readonlyDirs[] = "cache";
  }
  if (count($readonlyDirs) > 0) {
    $readonlyDirs = array_map(
      function ($dir) {
        return "<li><span class=\"fas fa-folder-open\"></span>/application/$dir</li>";
      },
      $readonlyDirs
    );
    $list = implode("\n", $readonlyDirs);
    $message = <<<HTML
<div class="alert alert-warning">
  <p><span class="fas fa-exclamation-triangle"></span><strong>Installation cannot proceed.</strong></p>
  <p>The Warehouse installation needs write access priveleges to the following folders:</p>
  <ul>
    $list
  </ul>
Please correct the permissions then reload the page.
</div>
HTML;
    failInitialise($message);
  }
  $source = dirname(__file__) . "/application/config/config.php.example";
  $dest = dirname(__file__) . "/application/config/config.php";
  if (!file_exists($source) || FALSE === ($_source_content = file_get_contents($source))) {
    $msg = <<<HTML
<div class="alert alert-warning">
  <p><span class="fas fa-exclamation-triangle"></span><strong>Config file not found.</strong></p>
  <p>The <code>/application/config/config.php.example</code> file does not exist or could not be accessed. If it does
  not exist then please replace the file from your Indicia installation download. If it already exists please check
  permissions to ensure it can be read by the web server.</p>
</div>
HTML;
    failInitialise($msg);
  }
  include dirname(__file__) . '/modules/indicia_setup/libraries/Zend_Controller_Request_Http.php';
  $zend_http = new Zend_Controller_Request_Http();
  $site_domain = preg_replace("/index\.php.*$/", "", $zend_http->getHttpHost() . $zend_http->getBaseUrl());
  $_source_content = str_replace("*site_domain*", $site_domain, $_source_content);
  if (FALSE === file_put_contents($dest, $_source_content)) {
    $msg = <<<HTML
<div>
  <h1>Indicia Installation</h1>
  <h2>Config file cannot be written.</h2>
  <p>The <code>/application/config/config.php</code> file cannot be created. Please check write permissions to this
  folder.</p>
</div>
HTML;
    failInitialise($msg);
  }
}
