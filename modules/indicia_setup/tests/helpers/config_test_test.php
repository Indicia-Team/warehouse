<?php

/**
 * The tests in this class will check the server environment prior to 
 * installing the database. 
 */

class ConfigTestTest extends PHPUnit_Framework_TestCase {
  
  protected function setUp() {
    // Check config assumes there is a session in place
    $this->session = Session::instance();
  }
  
  /**
   * 
   */
  public function testCheckConfigBeforeInstall() {
    
    $checks = config_test::check_config(false, true);
    foreach($checks as $check) {
      switch ($check['title']) {
        // These tests must pass for environment to be functional.
        case 'PHP Version':
        case 'PostgreSQL PHP Extensions':
        case 'cUrl Library':
        case 'gd2 Library':
        case 'Correct Directory Access':
          $this->assertTrue($check['success'], $check['description']);
          break;
        
        case 'dBase Library':
        case 'Zip Library':
        // These tests must pass for environment to be fully functional.
//          $this->assertFalse($check['warning'], $check['description']);
          break;
        
        // These tests will fail as system not installed.
        case 'Email configuration':
        case 'Database configuration':
          $this->assertFalse($check['success']);
      }
    }
  }
  
}