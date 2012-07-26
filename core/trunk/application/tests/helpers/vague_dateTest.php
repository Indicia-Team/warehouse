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
    $vd = array('', '', 'U');
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals(kohana::lang('dates.unknown'), $s);
  }
  
  public function testConvertVagueDate_To_Day() {
    $date = new DateTime('2001-06-15');
    $this->checkConvertVagueDate('2001-06-15', '2001-06-15', 'D', $date->format(Kohana::lang('dates.format')));
    $date = new DateTime('2004-02-29');
    $this->checkConvertVagueDate('2004-02-29', '2004-02-29', 'D', $date->format(Kohana::lang('dates.format')));
    $date = new DateTime('1970-01-01');
    $this->checkConvertVagueDate('1970-01-01', '1970-01-01', 'D', $date->format(Kohana::lang('dates.format')));
    $date = new DateTime('1900-12-31');
    $this->checkConvertVagueDate('1900-12-31', '1900-12-31', 'D', $date->format(Kohana::lang('dates.format')));
    $date = new DateTime('1800-01-01');
    $this->checkConvertVagueDate('1800-01-01', '1800-01-01', 'D', $date->format(Kohana::lang('dates.format')));
  }
  
  public function testConvertDay_To_VagueDate() {
    $vd = vague_date::string_to_vague_date('28/03/2001');
    $this->assertEquals('2001-03-28', $vd[0]);
    $this->assertEquals('2001-03-28', $vd[1]);    
    $this->assertEquals('D', $vd[2]);
  }
  
  public function testBadDate29022001() {
    $vd = vague_date::string_to_vague_date('29/02/2001');
    $this->assertFalse($vd, "Date '29/02/2001' should not be accepted");
  }
  
  public function testBadDate34373() {
    $vd = vague_date::string_to_vague_date('34/3/73');
    $this->assertFalse($vd, "Date '34/3/73' should not be accepted");
    $vd = vague_date::string_to_vague_date('34 march 73');
    $this->assertFalse($vd, "Date '34 march 73' should not be accepted");
  }
  
  public function testConvertVagueDate_To_DayRange() {
    $this->checkConvertVagueDate('2001-03-28', '2001-03-28', 'DD', '28/03/2001 to 28/03/2001');
  }
  
  public function testConvertDayRange_To_VagueDate() {
    $vd = vague_date::string_to_vague_date('28/03/2001 - 28/03/2001');
    $this->assertEquals('2001-03-28', $vd[0]);
    $this->assertEquals('2001-03-28', $vd[1]);    
    $this->assertEquals('DD', $vd[2]);
    $vd = vague_date::string_to_vague_date('26/03/2008 - 26/03/2008');
    $this->assertEquals('2008-03-26', $vd[0]);
    $this->assertEquals('2008-03-26', $vd[1]);    
    $this->assertEquals('DD', $vd[2]);
    $vd = vague_date::string_to_vague_date('28/03/1999 - 14/11/2007');
    $this->assertEquals('1999-03-28', $vd[0]);
    $this->assertEquals('2007-11-14', $vd[1]);    
    $this->assertEquals('DD', $vd[2]);
  }
  
  public function testConvertYear_To_VagueDate() {
    $vd = vague_date::string_to_vague_date('2001');
    $this->assertEquals('2001-01-01', $vd[0]);
    $this->assertEquals('2001-12-31', $vd[1]);
    $this->assertEquals('Y', $vd[2]);
    // pre-1970 test
    $vd = vague_date::string_to_vague_date('1964');
    $this->assertEquals('1964-01-01', $vd[0]);
    $this->assertEquals('1964-12-31', $vd[1]);
    $this->assertEquals('Y', $vd[2]);
    kohana::log('debug', '***************');
    $vd = vague_date::string_to_vague_date('1900');
    $this->assertEquals('1900-01-01', $vd[0]);
    $this->assertEquals('1900-12-31', $vd[1]);
    $this->assertEquals('Y', $vd[2]);
    $vd = vague_date::string_to_vague_date('1700');
    $this->assertEquals('1700-01-01', $vd[0]);
    $this->assertEquals('1700-12-31', $vd[1]);
    $this->assertEquals('Y', $vd[2]);
  }
  
  public function testConvertVagueDate_To_Year() {
    $this->checkConvertVagueDate('2001-01-01', '2001-12-31', 'Y', '2001');
    $this->checkConvertVagueDate('1970-01-01', '1970-12-31', 'Y', '1970');
    $this->checkConvertVagueDate('1900-01-01', '1900-12-31', 'Y', '1900');
    $this->checkConvertVagueDate('1800-01-01', '1800-12-31', 'Y', '1800');
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
    // add this test because -1899 has been known to parse as 01-01-1970!
    $vd = vague_date::string_to_vague_date('-1899');
    $this->assertEquals(null, $vd[0]);
    $this->assertEquals('1899-12-31', $vd[1]);
    $this->assertEquals('-Y', $vd[2]);
  }
  
  public function testConvertVagueDate_To_YearTo() {
    $this->checkConvertVagueDate('', '2001-12-31', '-Y', 'To 2001');
    $this->checkConvertVagueDate('', '1970-12-31', '-Y', 'To 1970');
    $this->checkConvertVagueDate('', '1900-12-31', '-Y', 'To 1900');
    $this->checkConvertVagueDate('', '1800-12-31', '-Y', 'To 1800');
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
    $this->checkConvertVagueDate('2001-01-01', '', 'Y-', 'From 2001');
    $this->checkConvertVagueDate('1970-01-01', '', 'Y-', 'From 1970');
    $this->checkConvertVagueDate('1900-01-01', '', 'Y-', 'From 1900');
    $this->checkConvertVagueDate('1800-01-01', '', 'Y-', 'From 1800');
  }
  
  /**
   * Method to apply a parameterised test on a vague date conversion to a string
   */
  protected function checkConvertVagueDate($from, $to, $type, $expected) {
    $fromDate = $from ? new DateTime($from) : null;
    $toDate = $to ? new DateTime($to) : null;
    $vd = array($fromDate, $toDate, $type);
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals($expected, $s, 'Failed converting vague date (dates) to '.$expected);
    // test using strings rather than date objects
    $fromStr = $from ? $from : '';
    $toStr = $to ? $to : '';
    $vd = array($fromStr, $toStr, $type);
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals($expected, $s, 'Failed converting vague date (strings) to '.$expected);
  }

}