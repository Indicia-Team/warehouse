<?php
class Helper_Vague_Date_Test extends PHPUnit_Framework_TestCase {

  public function testConvertUnknown_To_VagueDate() {
    $vd = vague_date::string_to_vague_date(kohana::lang('dates.unknown'));
    $this->assertEquals(null, $vd[0]);
    $this->assertEquals(null, $vd[1]);
    $this->assertEquals('U', $vd[2]);
  }
  
  public function testConvertVagueDate_To_Unknown() {
    $vd = array(null, null, 'U');
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals(kohana::lang('dates.unknown'), $s);
  }
  
  public function testConvertVagueDate_To_Day() {
    $today = new DateTime();
    $vd = array($today, $today, 'D');
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals($today->format(Kohana::lang('dates.format')), $s);
    $vd = array('2004-02-29', '2004-02-29', 'D');
    $s = vague_date::vague_date_to_string($vd);
    $leaptest = new DateTime('2004-02-29');
    $this->assertEquals($leaptest->format(Kohana::lang('dates.format')), $s);
  }
  
  public function testConvertYearTo_To_VagueDate() {
    $vd = vague_date::string_to_vague_date('To 2001');
    $this->assertEquals(null, $vd[0]);
    $this->assertEquals('2001-12-31', $vd[1]);
    $this->assertEquals('-Y', $vd[2]);
    $vd = vague_date::string_to_vague_date('-2001');
    $this->assertEquals(null, $vd[0]);
    $this->assertEquals('2001-12-31', $vd[1]);
    $this->assertEquals('-Y', $vd[2]);
  }
  
  public function testConvertVagueDate_To_YearTo() {
    $vd = array(null, new DateTime('2001-12-31'), '-Y');
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals('To 2001', $s);
    $vd = array(null, '2001-12-31', '-Y');
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals('To 2001', $s);
  }
  
  public function testConvertYearFrom_To_VagueDate() {
    $vd = vague_date::string_to_vague_date('after 2001');
    $this->assertEquals('2001-01-01', $vd[0]);
    $this->assertEquals(null, $vd[1]);
    $this->assertEquals('Y-', $vd[2]);
    
    $vd = vague_date::string_to_vague_date('2001-');
    $this->assertEquals('2001-01-01', $vd[0]);
    $this->assertEquals(null, $vd[1]);
    $this->assertEquals('Y-', $vd[2]);
  }
  
  public function testConvertVagueDate_To_YearFrom() {
    $vd = array(null, new DateTime('2001-12-31'), '-Y');
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals('To 2001', $s);
    $vd = array(null, '2001-12-31', '-Y');
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals('To 2001', $s);
  }

}