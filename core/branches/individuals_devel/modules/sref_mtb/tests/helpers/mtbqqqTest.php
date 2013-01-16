<?php

class Helpers_mtbqqq_Test extends PHPUnit_Framework_TestCase {

  public function testIsValid() {
    $sref='1846-1';
    $this->assertFalse(mtbqqq::is_valid($sref));
    $sref='1846/1';
    $this->assertTrue(mtbqqq::is_valid($sref));    
  }
  
  public function testSrefToWkt() {
    $sref='1846/1';
    $wkt = mtbqqq::sref_to_wkt($sref);
    $this->assertEquals(
      'POLYGON((13.333333333333 54.15,13.333333333333 54.2,13.416666666667 54.2,13.416666666667 54.15,13.333333333333 54.15))',
      $wkt,
      'Sref to WKT returned an unexpected value'
    );
  }
  
  /**
   * @expectedException InvalidArgumentException
   */
  public function testSrefToWktInvalid() {
    $sref='1846/5';
    $wkt = mtbqqq::sref_to_wkt($sref);
  }

}