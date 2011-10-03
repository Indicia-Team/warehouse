<?php

function initialise() {
    // No config file, so this is a first run for install. Before creating the config file, check some
  // directory access
  $readonlyDirs = array();
  if (!is_writable(dirname(__file__) . "/application/config/"))
    $readonlyDirs[] = "/application/config/";
  if (!is_writable(dirname(__file__) . "/application/logs/"))
    $readonlyDirs[] = "/application/logs/";
  if (!is_writable(dirname(__file__) . "/application/cache/"))
    $readonlyDirs[] = "/application/cache/";
  if (count($readonlyDirs)>0)
    die
    (
      '<div style="width:80%;margin:50px auto;text-align:center;">'.
        '<h3>Installation cannot proceed.</h3>'.
        '<p>The Warehouse installation needs write access priveleges to the following folders:</p>'.
        '<ul><li>'.
        implode('</li><li>', $readonlyDirs).
        '</li></ul>'.
      '</div>'
    );
  $source = dirname(__file__) . "/application/config/config.php.example";
  $dest = dirname(__file__) . "/application/config/config.php";
  if(false === ($_source_content = file_get_contents($source))) {
    die
    (
      '<div style="width:80%;margin:50px auto;text-align:center;">'.
        '<h3>Config file not found.</h3>'.
        '<p>The <code>'.APPPATH.'config/config'.EXT.'.example</code> file does not exist or could not be accessed.</p>'.
      '</div>'
    );
  }
  include(dirname(__file__) . '/modules/indicia_setup/libraries/Zend_Controller_Request_Http.php');
  $zend_http = new Zend_Controller_Request_Http;
  $site_domain = preg_replace("/index\.php.*$/","", $zend_http->getHttpHost() . $zend_http->getBaseUrl());
  $_source_content = str_replace("*site_domain*", $site_domain, $_source_content);
  if(false === file_put_contents($dest, $_source_content)) {
    die
    (
      '<div style="width:80%;margin:50px auto;text-align:center;">'.
        '<h1>Indicia Installation</h1>'.
        '<h2>Config file cannot be written.</h2>'.
        '<p>The <code>'.APPPATH.'config/config'.EXT.'</code> file cannot be created..</p>'.
      '</div>'
    );
  }
}

?>
