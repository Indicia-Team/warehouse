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
 * @package	Setup
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Controller class for the setup_check page. Displays results
 * of a system configuration check.
 *
 * @package	Modules
 * @subpackage setup_check
 */
class Setup_Check_Controller extends Template_Controller {

  private $error=null;

  /**
   * Template name
   *
   * @var string $template
   */
  public $template = 'templates/template';
  
  public function get_breadcrumbs()
  {
    return '';
  }

  public function __construct()
  {
    parent::__construct();
    $this->session = Session::instance();
  }

  /**
   * Load the page which lists configuration checks.
   */
  public function index()
  {
    $this->disable_browser_caching();

    $this->template->title = 'Configuration Check';
    $this->template->content = new View('setup_check');
    $this->template->content->checks = config_test::check_config();
  }

  /**
   * Load the configuration of the demo pages view.
   */
  public function config_demo()
  {
    $this->template->title = Kohana::lang('setup.demo_configuration');
    $this->template->content = new View('fixers/config_demo');
    $this->template->content->error = $this->error;
    $this->error=null;
  }

  /**
   * Save the demo configuration settings.
   */
  public function config_demo_save() {
    $source = dirname(dirname(__file__ )) . '/config_files/_helper_config.php';
    $dest = dirname(dirname(dirname(dirname(__file__)))) . "/client_helpers/helper_config.php";
    try {
      unlink($dest);
    } catch (Exception $e) {
      // file doesn't exist?'
    }
    try {
      $_source_content = file_get_contents($source);
      // Now save the POST form values into the config file
      foreach ($_POST as $field => $value) {
        $_source_content = str_replace("*$field*", $value, $_source_content);
      }
      $base_url=kohana::config('config.site_domain');
      if (substr($base_url, 0, 4)!='http')
        $base_url = "http://$base_url";
      if (substr($base_url, -1, 1)!='/') 
        $base_url = $base_url.'/';
      $_source_content = str_replace("*base_url*", $base_url, $_source_content);
      file_put_contents($dest, $_source_content);
      // To get the demo working, we also need to copy over the data_entry_config.php file.
      $source = dirname(dirname(__file__ )) . '/config_files/_data_entry_config.php';
      $dest = dirname(dirname(dirname(__file__))) . "/demo/data_entry_config.php";
      copy($source, $dest);
      url::redirect('setup_check');
    } catch (Exception $e) {
      kohana::log('error', $e->getMessage());
      $this->error = $e->getMessage();
      $this->config_demo();
    }

  }


  /**
   * Load the configuration of emails view.
   */
  public function config_email()
  {
    $this->template->title = 'Email Configuration';
    $this->template->content = new View('fixers/config_email');
    $this->template->content->error = $this->error;
    $this->error=null;
  }

  /**
   * Save the results of a configuration of emails form, unless the skip button was clicked
   * in which case the user is redirected to a page allowing them to confirm this.
   */
  public function config_email_save()
  {
    if (isset($_POST['skip'])) {
      url::redirect('setup_check/skip_email');
    } else {
      $source = dirname(dirname(__file__ )) . '/config_files/_email.php';
      $dest = dirname(dirname(dirname(dirname(__file__)))) . "/application/config/email.php";
      try {
        unlink($dest);
      } catch (Exception $e) {
        // file doesn't exist?'
      }
      try {
        $_source_content = file_get_contents($source);
        // Now save the POST form values into the config file
        foreach ($_POST as $field => $value) {
          $_source_content = str_replace("*$field*", $value, $_source_content);
        }
        file_put_contents($dest, $_source_content);
        // Test the email config
        $swift = email::connect();
        $message = new Swift_Message('Setup test',
            Kohana::lang('setup.test_email_title'),            
            'text/html');
        $recipients = new Swift_RecipientList();
        $recipients->addTo($_POST['test_email']);
        if ($swift->send($message, $recipients, $_POST['address'])==1) {
           $_source_content = str_replace("*test_result*", 'pass', $_source_content);
           file_put_contents($dest, $_source_content);
           url::redirect('setup_check');
        } else {
          $this->error = Kohana::lang('setup.test_email_failed');
          $this->config_email();
        }
      } catch (Exception $e) {
        // Swift mailer messages tend to have the error message as the last part, with each part colon separated.
        $msg = explode(':', $e->getMessage());
        $this->error = $msg[count($msg)-1];
        kohana::log('error', $e->getMessage());
        $this->config_email();
      }
    }
  }

  /**
   * Action to display the acknowledge permissions page.
   */
  public function skip_email() {
    $this->template->title = Kohana::lang('setup.skip_email_config');
    $this->template->content = new View('fixers/skip_email');
  }

  /**
   * Action to handle click of the final skip email button, then
   * redirect back to the setup check page.
   */
  public function do_skip_email() {
    $_SESSION['skip_email']=true;
    url::redirect('setup_check');
  }

  /**
   * Action to display the acknowledge permissions page.
   */
  public function ack_permissions() {
    $this->template->title = Kohana::lang('ack_perm_problems');
    $this->template->content = new View('fixers/ack_permissions');
    $messages=array();
    config_test::check_dir_permissions($messages, true);
    $this->template->content->problems=$messages[0]['description'];
  }

  /**
   * Action to handle click of the final acknowledge permissions button, then
   * redirect back to the setup check page.
   */
  public function do_ack_permissions() {
    $_SESSION['ack_permissions']=true;
    url::redirect('setup_check');
  }

  /**
   * Add http headers to disable browser caching.
   */
  private function disable_browser_caching()
  {
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
      header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

      header("Cache-Control: no-store, no-cache, must-revalidate");
      header("Cache-Control: post-check=0, pre-check=0", false);
      header("Pragma: no-cache");
      header('P3P: CP="NOI NID ADMa OUR IND UNI COM NAV"');
  }

  /**
   * Display the database configuration form.
   */
  public function config_db() {
    // We need to know if the db is already configured. If so, just redirect back, otherwise it could be maliciously overwritten.
    $messages = array();
    config_test::check_db($messages, true);
    if (count($messages)==0) {
      url::redirect('setup_check');
    } else {
      // Db does indeed need to be configured.
      $this->template->title = 'Database Configuration';
      $this->template->content = new View('fixers/config_db');
      $this->template->content->error = $this->error;
  
      // init and default values of view vars
      $this->view_var = array();
  
      $this->template->content->description = str_replace('*code*',
          '<span class="code">CREATE DATABASE indicia TEMPLATE=template_postgis;</span>',
          Kohana::lang('setup.description'));
      // Assign default settings if the user has not yet updated the db config
      $db_config=kohana::config('database');
      if ($db_config['default']['connection']['type']=='mysql') {
        $this->template->content->host     = 'localhost';
        $this->template->content->port     = '5432';
        $this->template->content->user     = '';
        $this->template->content->password = '';
        $this->template->content->reportuser     = '';
        $this->template->content->reportpassword = '';
        $this->template->content->schema   = '';
        $this->template->content->database = '';
      } else {
        $this->template->content->host     = $db_config['default']['connection']['host'];
        $this->template->content->port     = $db_config['default']['connection']['port'];
        $this->template->content->user     = $db_config['default']['connection']['user'];
        $this->template->content->password = $db_config['default']['connection']['pass'];
        $this->template->content->reportuser     = $db_config['report']['connection']['user'];
        $this->template->content->reportpassword = $db_config['report']['connection']['pass'];
        $this->template->content->schema   = $db_config['default']['schema'];
        $this->template->content->database = $db_config['default']['connection']['database'];
      }
    }
  }

  /**
   * Save the database configuration file, then proceed and install the database objects.
   */
  public function config_db_save() {
    $this->get_paths();
    $this->get_form_vars();
    if (false === $this->write_database_config())
    {
      $this->db->rollback();
      $this->error = Kohana::lang('setup.error_db_database_config');
      Kohana::log("error", "Could not write database config file. Please check file write permission rights.");
      $this->config_db();
    }
    if (!$this->create_database()) {
      $this->config_db();
    } else {
      url::redirect('setup_check');
    }
  }

  /**
   * Pre assign form vars
   */
  private function get_form_vars()
  {
    $this->dbparam['host']     = trim($_POST['host']);
    $this->dbparam['port']     = trim(preg_replace("/[^0-9]/","", $_POST['port']));
    $this->dbparam['database'] = trim($_POST['database']);
    $this->dbparam['schema']   = trim($_POST['schema']);
    $this->dbparam['dbuser']     = trim($_POST['dbuser']);
    $this->dbparam['dbpassword'] = trim($_POST['dbpassword']);
  }

  /**
   * Create the database items
   *
   * @return bool
   */
  private function create_database()
  {
    // first try to connect to the database
    //
    if(true === $this->db_connect())
    {
      echo "and ";
        // check postgres version. at least 8.2 required
        //
        if(true !== ($version = $this->db->check_postgres_version()))
        {
            if(false !== $version)
            {
                $this->error = Kohana::lang('setup.error_db_wrong_postgres_version1') . $version . '. '.
                    Kohana::lang('setup.error_db_wrong_postgres_version2');
                Kohana::log("error", "Setup failed: wrong postgres version {$version}. At least 8.2 required");
                return false;
            }
            else
            {
                $this->error = Kohana::lang('setup.error_db_unknown_postgres_version') + '. ' .
                    Kohana::lang('setup.error_db_wrong_postgres_version2');
                Kohana::log("error", "Setup failed: unknown postgres version ");
                return false;
            }
        }

        // start transaction
        //
        $this->db->begin();

        // empty or public schema isnt allowed
        //
        if(($this->dbparam['schema'] == 'public') || empty($this->dbparam['schema']))
        {
            $this->view_var['error_general'][] = Kohana::lang('setup.error_db_wrong_schema');
            Kohana::log("error", "Setup failed: wrong schema {$this->dbparam['schema']}");
            $this->view_var['error_dbschema'] = true;
            return false;
        }
        // drop existing schema with this name and create a new schema
        //
        elseif(true !== ($result = $this->db->createSchema( $this->dbparam['schema'] )))
        {
            $this->view_var['error_general'][] = Kohana::lang('setup.error_db_schema');
            Kohana::log("error", "Setup failed: {$result}");
            $this->view_var['error_dbschema'] = true;
            return false;
        }

        // check postgis installation
        //
        if( true !== ($result = $this->db->checkPostgis()))
        {
            $this->view_var['error_general'][] = Kohana::lang('setup.error_db_postgis');
            Kohana::log("error", "Setup failed: {$result}");
            return false;
        }

        // postgis alterations
        if (!$this->run_script($this->db_file_postgis_alterations)) return false;

        // create sequences
        if (!$this->run_script($this->db_file_indicia_sequences)) return false;

        // create tables
        if (!$this->run_script($this->db_file_indicia_tables)) return false;

        // create views
        if (!$this->run_script($this->db_file_indicia_views)) return false;

        // insert default data
        if (!$this->run_script($this->db_file_indicia_data)) return false;

        // insert indicia version values into system table
        //
        if(true !== ($result = $this->db->insertSystemInfo()))
        {
            $this->view_var['error_general'][] = Kohana::lang('setup.error_db_setup') . '<br />' . $result;
            Kohana::log("error", "Setup failed: {$result}");
            return false;
        }

        // end transaction
        //
        $this->db->commit();

        if(false === $this->write_indicia_config())
        {
            $this->db->rollback();
            $this->view_var['error_general'][] = Kohana::lang('setup.error_db_indicia_config');
            Kohana::log("error", "Could not write indicia config file. Please check file write permission rights.");
            return false;
        }

        // If write termlist config fails, don't worry as the config test will help the user fix it.
        // TODO: $this->write_termlist_config();

        return true;
    }

    return false;
  }

    /**
     * run a database script
     *
     * param string $db_file_name name and path of the database script to run
     *
     * return boolean Success of the operation
     */
    private function run_script($db_file_name)
    {
        $_db_file = file_get_contents($db_file_name);

        Kohana::log("info", "Processing: ".$db_file_name);

        if(true !== ($result = $this->db->query($_db_file)))
        {
            $this->view_var['error_general'][] = Kohana::lang('setup.error_db_setup') . '<br />' . $result;
            Kohana::log("error", "Setup failed: {$result}");
            return false;
        }
        return true;
    }

    /**
     * connect to the database
     *
     * @return resource false on error
     */
    private function db_connect()
    {
        $this->db = new Setupdb_Model;
        try {
          if( false === $this->db->dbConnect($this->dbparam['host'],
              $this->dbparam['port'],
              $this->dbparam['database'],
              $this->dbparam['dbuser'],
              $this->dbparam['dbpassword'])) {
            $this->error = Kohana::lang('setup.error_db_connect');
            Kohana::log("error", "Setup failed: database connection error");
            return false;
          }
        } catch (Exception $e) {
          $this->error = Kohana::lang('setup.error_db_connect').'<br/>'.$e->getMessage();
          Kohana::log("error", "Setup failed: database connection error");
          Kohana::log("error", "Error: ".$e->getMessage());
          return false;
        }
        return true;
    }

  /**
   * Get all the paths we are going to use during db installation.
   */
  private  function get_paths()
  {
    $this->db_file_indicia_sequences = dirname(dirname(dirname(dirname(__file__ )))) . '/modules/indicia_setup/db/indicia_sequences.sql';
    $this->db_file_indicia_tables = dirname(dirname(dirname(dirname(__file__ )))) . '/modules/indicia_setup/db/indicia_tables.sql';
    $this->db_file_indicia_views = dirname(dirname(dirname(dirname(__file__ )))) . '/modules/indicia_setup/db/indicia_views.sql';
    $this->db_file_postgis_alterations = dirname(dirname(dirname(dirname(__file__ )))) . '/modules/indicia_setup/db/postgis_alterations.sql';
    $this->db_file_indicia_data = dirname(dirname(dirname(dirname(__file__ )))) . '/modules/indicia_setup/db/indicia_data.sql';
  }


  /**
     * Write database.php config file
     *
     * @return bool
     */
  private function write_database_config()
  {
    $tmp_config = file_get_contents(dirname(dirname(__file__ )) . '/config_files/_database.php');

    $_config = str_replace(
        array(
            "*host*",
            "'*port*'",
            "*database*",
            "*user*",
            "*password*",
            "*reportuser*",
            "*reportpassword*",
            "*schema*"
        ), array(
            trim($_POST['host']),
            trim($_POST['port']),
            trim($_POST['database']),
            trim($_POST['dbuser']),
            trim($_POST['dbpassword']),
            trim($_POST['reportuser']),
            trim($_POST['reportpassword']),
            trim($_POST['schema'])
        ), $tmp_config);

    $database_config = dirname(dirname(dirname(dirname(__file__)))) . "/application/config/database.php";

    if(!$fp = @fopen($database_config, 'w'))
    {
        $this->error = Kohana::lang('setup.error_db_setup');
        Kohana::log("error", "Cant open file to write: ". $database_config);
        return false;
    }

    if( !@fwrite($fp, $_config) )
    {
        $this->error = Kohana::lang('setup.error_db_setup');
        Kohana::log("error", "Cant write file: ". $database_config);
        return false;
    }

    @fclose($fp);

    return true;
  }

  /**
   * Write indicia.php config file
   *
   * @return bool
   */
  private function write_indicia_config()
  {
    $indicia_source_config = dirname(dirname(dirname(dirname(__file__)))) . "/application/config/indicia_dist.php";
    $indicia_dest_config = dirname(dirname(dirname(dirname(__file__)))) . "/application/config/indicia.php";

    return @copy($indicia_source_config, $indicia_dest_config);
  }

  /**
   * Override the load view behaviour to display better error information when a view
   * fails to load.
   */
  public function _kohana_load_view($kohana_view_filename, $kohana_input_data)
  {
    if ($kohana_view_filename == '')
      return;

    // Buffering on
    ob_start();

    // Import the view variables to local namespace
    extract($kohana_input_data, EXTR_SKIP);

    // Views are straight HTML pages with embedded PHP, so importing them
    // this way insures that $this can be accessed as if the user was in
    // the controller, which gives the easiest access to libraries in views

    // Put the include in a try catch block
    try
    {
      include $kohana_view_filename;
    }
    catch (Exception $e)
    {
      // Put the error out
      echo '<pre>'.print_r($e, TRUE).'</pre>';
      throw $e;
    }

    // Fetch the output and close the buffer
    return ob_get_clean();
  }


}

?>