<?php

/**
 * The tests in this class will install the Indicia config files and 
 * database which many other tests depend on. 
 */

class SetupCheckControllerTest extends PHPUnit_Framework_TestCase {
  
  /**
   * Calls Setup_Check_Controller::config_db_save causing essential config
   * files to be created and the basic database schema to be created. 
   */
  public function testInitialise() {
    
    $_POST['host'] = 'localhost';
    $_POST['port'] = '5432';
    $_POST['database'] = 'indicia';
    $_POST['schema'] = 'indicia';
    $_POST['dbuser'] = 'indicia_user';
    $_POST['dbpassword'] = 'indicia_user_pass';
    $_POST['reportuser'] = 'indicia_report_user';
    $_POST['reportpassword'] = 'indicia_report_user_pass';
    
    // Trigger installation of Indicia database.
    $ctrl = new Setup_Check_Controller;
    $ctrl->config_db_save();
    
    // Database settings should have been saved to file.
    $this->assertFileExists('application/config/database.php');
    // Indicia settings should have been saved to file.
    $this->assertFileExists('application/config/indicia.php');
    // Client_helper config should have been saved to file.
    $this->assertFileExists('client_helpers/helper_config.php');

    // Use the phpunit/config/database.php connection becasue the list of 
    // config files is cached and the newly saved application/config/database.php
    // is not included yet.
    $db = new Database('phpunit');
    $r = $db->select('version')
        ->from('system')
        ->where('name', 'Indicia')
        ->get();
    $version = $r[0]->version;
    $this->assertEquals('0.1', $version);
  }
  
}
