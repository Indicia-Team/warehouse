<?php

use PHPUnit\Framework\TestCase;

define("CORNER_1846_SW_X", 13 + 1/3);
define("CORNER_1846_SW_Y", 54.1);
define("MTB_SQUARE_WIDTH_X", 1/6);
define("MTB_SQUARE_WIDTH_Y", 0.1);
define("Q_SQUARE_WIDTH_X", MTB_SQUARE_WIDTH_X / 2);
define("Q_SQUARE_WIDTH_Y", MTB_SQUARE_WIDTH_Y / 2);
define("QQ_SQUARE_WIDTH_X", Q_SQUARE_WIDTH_X / 2);
define("QQ_SQUARE_WIDTH_Y", Q_SQUARE_WIDTH_Y / 2);

class Helpers_mtbqqq_Test extends TestCase {

  public function testIsValid() {
    $sref='1846-1';
    $this->assertFalse(mtbqqq::is_valid($sref));
    $sref='1846/1';
    $this->assertTrue(mtbqqq::is_valid($sref));
    $sref='1846/11';
    $this->assertTrue(mtbqqq::is_valid($sref));  
    $sref='1846/111';
    $this->assertTrue(mtbqqq::is_valid($sref));  
    $sref='1846.1';
    $this->assertTrue(mtbqqq::is_valid($sref));
    $sref='1846.11';
    $this->assertTrue(mtbqqq::is_valid($sref));
    $sref='1846.111';
    $this->assertTrue(mtbqqq::is_valid($sref));
  }
  
  public function testSrefToWktQ() {
    $sref='1846/1';
    $wkt = mtbqqq::sref_to_wkt($sref);
    // test for top left quadrant going clockwise
    $left = CORNER_1846_SW_X;
    $right = CORNER_1846_SW_X + Q_SQUARE_WIDTH_X;
    $bottom = CORNER_1846_SW_Y + Q_SQUARE_WIDTH_Y;
    $top = CORNER_1846_SW_Y + Q_SQUARE_WIDTH_Y * 2;
    $this->assertEquals(
      "POLYGON(($left $bottom,$left $top,$right $top,$right $bottom,$left $bottom))",
      $wkt,
      'Sref to WKT returned an unexpected value'
    );
  }
  
  private function getTestSquare1846_12() {
    // top left quadrant of main square, top right quadrant of inner square
    $left = CORNER_1846_SW_X + QQ_SQUARE_WIDTH_X;
    $right = $left + QQ_SQUARE_WIDTH_X;
    $bottom = CORNER_1846_SW_Y + Q_SQUARE_WIDTH_Y + QQ_SQUARE_WIDTH_Y;
    $top = $bottom + QQ_SQUARE_WIDTH_Y;
    return "POLYGON(($left $bottom,$left $top,$right $top,$right $bottom,$left $bottom))";
  }
  
  public function testSrefToWktQQ() {
    $sref = '1846/12';
    $wkt = mtbqqq::sref_to_wkt($sref);
    // top left quadrant of main square, top right quadrant of inner square
    $this->assertEquals(
      $this->getTestSquare1846_12(),
      $wkt,
      'Sref to WKT returned an unexpected value'
    );
  }
  
  public function testWktToSrefQQ() {
    // Find a point inside the square we are testing against
    $left = CORNER_1846_SW_X + QQ_SQUARE_WIDTH_X + QQ_SQUARE_WIDTH_X / 2;
    $bottom = CORNER_1846_SW_Y + Q_SQUARE_WIDTH_Y + QQ_SQUARE_WIDTH_Y + QQ_SQUARE_WIDTH_Y / 2;
    $wkt = "POINT($left $bottom)";
    $sref = mtbqqq::wkt_to_sref($wkt, null, null, 5000);
    $this->assertEquals(
      '1846/12',
      $sref,
      'WKT to SREF returned an unexpected value'
    );
  }

  public function testSrefToWktInvalidQ() {
    $this->expectException(InvalidArgumentException::class);
    $sref = '1846/5';
    $wkt = mtbqqq::sref_to_wkt($sref);
  }

  public function testSrefToWktInvalidQQ() {
    $this->expectException(InvalidArgumentException::class);
    $sref =' 1846/15';
    $wkt = mtbqqq::sref_to_wkt($sref);
  }

  public function testSrefToWktInvalidQQQ() {
    $this->expectException(InvalidArgumentException::class);
    $sref = '1846/115';
    $wkt = mtbqqq::sref_to_wkt($sref);
  }

}