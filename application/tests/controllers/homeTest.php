<?php

/**
 * The tests in this class will upgrade the database to the latest version
 * and confirm it matches with the code version. 
 */

class Home_Controller_Test extends PHPUnit_Framework_TestCase {
  
  public function testUpgrade() {
    
    // Trigger upgrade of Indicia database.
    $ctrl = new Home_Controller;
    $ctrl->upgrade();
    
    $db = new Database();
    $r = $db->select('version')
        ->from('system')
        ->where('name', 'Indicia')
        ->get();
    $version = $r[0]->version;
    
    $this->assertEquals(kohana::config('version.version'), $version);
  }

}