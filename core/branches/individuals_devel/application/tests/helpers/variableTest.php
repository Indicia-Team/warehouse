<?php
class Helper_Variable_Test extends PHPUnit_Framework_TestCase {

  public function testSet() {
    variable::set('unittestset', 'testvalue');
    // check in db
    $db = new Database();
    $r = $db->select('value')
        ->from('variables')
        ->where('name', 'unittestset')
        ->get()->result_array(false);
    $value = false;
    if (count($r)===1) {
      $value = json_decode($r[0]['value']);
      $value = $value[0];
    }
    $this->assertEquals('testvalue', $value);
    // check in cache
    $value = false;
    $cache = Cache::instance();
    // get returns null if no value
    $value = $cache->get("variable-unittestset");
    $this->assertEquals('testvalue', $value);
    variable::delete('unittestset');
  }
  
  public function testGet() {
    variable::set('unittestget', 'testvalueget');
    $value = variable::get('unittestget');
    $this->assertEquals('testvalueget', $value);
    $value = variable::get('doesntexist', 'default');
    $this->assertEquals('default', $value);
    variable::delete('unittestget');
  }

}