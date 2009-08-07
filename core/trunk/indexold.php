<?php
/**
 * This file acts as the "front controller" to your application. You can
 * configure your application, modules, and system directories here.
 * PHP error_reporting level may also be changed.
 *
 * @see http://kohanaphp.com
 */

/**
 * Define the website environment status. When this flag is set to TRUE, some
 * module demonstration controllers will result in 404 errors. For more information
 * about this option, read the documentation about deploying Kohana.
 *
 * @see http://docs.kohanaphp.com/installation/deployment
 */
define('IN_PRODUCTION', FALSE);

/**
 * Website application directory. This directory should contain your application
 * configuration, controllers, models, views, and other resources.
 *
 * This path can be absolute or relative to this file.
 */
$kohana_application = 'application';

/**
 * Kohana modules directory. This directory should contain all the modules used
 * by your application. Modules are enabled and disabled by the application
 * configuration file.
 *
 * This path can be absolute or relative to this file.
 */
$kohana_modules = 'modules';

/**
 * Kohana system directory. This directory should contain the core/ directory,
 * and the resources you included in your download of Kohana.
 *
 * This path can be absolute or relative to this file.
 */
$kohana_system = 'system';

/**
 * Test to make sure that Kohana is running on PHP 5.2 or newer. Once you are
 * sure that your environment is compatible with Kohana, you can comment this
 * line out. When running an application on a new server, uncomment this line
 * to check the PHP version quickly.
 */
version_compare(PHP_VERSION, '5.2', '<') and exit('Kohana requires PHP 5.2 or newer.');

/**
 * Set the error reporting level. Unless you have a special need, E_ALL is a
 * good level for error reporting.
 */
error_reporting(E_ALL & ~E_STRICT);

/**
 * Turning off display_errors will effectively disable Kohana error display
 * and logging. You can turn off Kohana errors in application/config/config.php
 */
ini_set('display_errors', TRUE);

/**
 * If you rename all of your .php files to a different extension, set the new
 * extension here. This option can left to .php, even if this file has a
 * different extension.
 */
define('EXT', '.php');

//
// DO NOT EDIT BELOW THIS LINE, UNLESS YOU FULLY UNDERSTAND THE IMPLICATIONS.
// ----------------------------------------------------------------------------
// $Id: index.php 3168 2008-07-21 01:34:36Z Shadowhand $
//

// Define the front controller name and docroot
define('DOCROOT', getcwd().DIRECTORY_SEPARATOR);
define('KOHANA',  basename(__FILE__));

// If the front controller is a symlink, change to the real docroot
is_link(KOHANA) and chdir(dirname(realpath(__FILE__)));

// Define application and system paths
define('APPPATH', str_replace('\\', '/', realpath($kohana_application)).'/');
define('MODPATH', str_replace('\\', '/', realpath($kohana_modules)).'/');
define('SYSPATH', str_replace('\\', '/', realpath($kohana_system)).'/');

// Clean up
unset($kohana_application, $kohana_modules, $kohana_system);

if ( ! IN_PRODUCTION)
{
  // Check APPPATH
  if ( ! (is_dir(APPPATH)))
  {
    die
    (
      '<div style="width:80%;margin:50px auto;text-align:center;">'.
        '<h3>Application Directory Not Found</h3>'.
        '<p>The <code>$kohana_application</code> directory does not exist.</p>'.
        '<p>Set <code>$kohana_application</code> in <tt>'.KOHANA.'</tt> to a valid directory and refresh the page.</p>'.
      '</div>'
    );
  }
  // If the config file does not exist, we will overwrite it with the example file, and update the site domain.
  if ( !  (is_file(APPPATH.'config/config'.EXT)))
  {
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
          '<h3>Config file cannot be writted.</h3>'.
          '<p>The <code>'.APPPATH.'config/config'.EXT.'</code> file cannot be created.</p>'.
        '</div>'
      );
    }
  }

  // Check SYSPATH
  if ( ! (is_dir(SYSPATH) AND is_file(SYSPATH.'core/Bootstrap'.EXT)))
  {
    die
    (
      '<div style="width:80%;margin:50px auto;text-align:center;">'.
        '<h3>System Directory Not Found</h3>'.
        '<p>The <code>$kohana_system</code> directory does not exist.</p>'.
        '<p>Set <code>$kohana_system</code> in <tt>'.KOHANA.'</tt> to a valid directory and refresh the page.</p>'.
      '</div>'
    );
  }
}

// Initialize.
require SYSPATH.'core/Bootstrap'.EXT;
