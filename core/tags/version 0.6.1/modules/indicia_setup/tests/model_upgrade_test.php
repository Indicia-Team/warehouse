<?php
class Model_Upgrade_Test extends PHPUnit_Framework_TestCase {
  
  private $model;
  private $db;
  
  protected function setUp() {
    $this->model = new Upgrade_Model();
    $this->db = new Database();    
  }
  
  protected function tearDown() {
    // cleanup the db
    $result = $this->db->query('delete from system where id>1');
    // cleanup the file system
    if (file_exists(MODPATH.'indicia_setup/db/test_scripts/____200912271517_test1____'))
      unlink(MODPATH.'indicia_setup/db/test_scripts/____200912271517_test1____');
    if (file_exists(MODPATH.'indicia_setup/db/test_scripts/____200912271528_test2____'))
      unlink(MODPATH.'indicia_setup/db/test_scripts/____200912271528_test2____');
    if (file_exists(MODPATH.'indicia_setup/db/test_scripts/200912271528_test2.sql'))
      unlink(MODPATH.'indicia_setup/db/test_scripts/200912271528_test2.sql');
  }
  
  /**
   * Test an initial upgrade against a folder with no ___ file in it.
   */
  public function testFirstUpgrade() {
    $this->model->execute_sql_scripts('test_scripts');
    // Check the file tracking
    $this->assertFileExists(MODPATH.'indicia_setup/db/test_scripts/____200912271517_test1____');
    // and check the SQL actually ran
    $result = $this->db->query('select * from system where "version"=\'test\'')->result_array(false);
    $this->assertTrue(count($result)==1);
    $this->assertTrue($result[0]['name']=='test');
  }
  
  /**
   * Test an subsequent upgrade against a folder which already has a ____ file in it to denote the last run script.
   * @depends testFirstUpgrade   
   */
  public function testSubsequentUpgrade() {
    // Setup a second file to upgrade
    $file = MODPATH.'indicia_setup/db/test_scripts/200912271528_test2.sql';
    $handle = fopen($file, 'w');
    $query = "INSERT INTO system (\"version\", \"name\", repository, release_date) \n".
      "VALUES ('test2', 'test2', 'http://indicia.googlecode.com/svn/tag/version_0_1', '2009-12-27')";
    fwrite($handle, $query);
    fclose($handle); 
    
    // run the upgrade
    $this->model->execute_sql_scripts('test_scripts');
    // Check the file tracking    
    $this->assertFalse(file_exists(MODPATH.'indicia_setup/db/test_scripts/____200912271517_test1____'));
    $this->assertFileExists(MODPATH.'indicia_setup/db/test_scripts/____200912271528_test2____');
    // and check the SQL actually ran 
    $result = $this->db->query('select * from system where "version"=\'test2\'')->result_array(false);
    $this->assertTrue(count($result)==1);
    $this->assertTrue($result[0]['name']=='test2');   
  }
  
  
}


?>