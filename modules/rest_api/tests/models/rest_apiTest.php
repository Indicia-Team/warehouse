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
    $this->db->query('delete from system where name in (\'test\', \'test2\')');
    // and cleanup the upgrade file we created.
    if (file_exists(MODPATH.'indicia_setup/db/test_scripts/200912271528_test2.sql'))
      unlink(MODPATH.'indicia_setup/db/test_scripts/200912271528_test2.sql');
  }
  
  /**
   * Test an initial upgrade against a folder with no ___ file in it.
   */
  public function testFirstUpgrade() {
    $last_run_script = '';
    $this->model->execute_sql_scripts(MODPATH.'indicia_setup/','test_scripts','test',$last_run_script);
    $this->assertTrue($last_run_script==='200912271517_test1.sql');
    // Check the SQL actually ran
    $result = $this->db->query('select * from system where "version"=\'test\'')->result_array(false);
    $this->assertTrue(count($result)==1);
    $this->assertTrue($result[0]['name']=='test');
    $this->assertTrue($result[0]['last_run_script']=='200912271517_test1.sql');
  }
  
  /**
   * Test an subsequent upgrade against a folder which already has been upgraded.
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
    $last_run_script='200912271517_test1.sql';
    $this->model->execute_sql_scripts(MODPATH.'indicia_setup/','test_scripts','test2',$last_run_script);
    // Check the tracking    
    $this->assertTrue($last_run_script==='200912271528_test2.sql');
    // Check the SQL actually ran
    $result = $this->db->query('select * from system where "version"=\'test2\'')->result_array(false);
    $this->assertTrue(count($result)==1);
    $this->assertTrue($result[0]['name']=='test2');
    print $result[0]['last_run_script'];
    $this->assertTrue($result[0]['last_run_script']=='200912271528_test2.sql');
  }
  
  
}


?>