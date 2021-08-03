<?php

use PHPUnit\Framework\TestCase;

define("CORNER_1847_SW_X", 13.5);
define("CORNER_1847_SW_Y", 54.1);
define("QYX_SQUARE_WIDTH_X", Q_SQUARE_WIDTH_X / 5);
define("QYX_SQUARE_WIDTH_Y", Q_SQUARE_WIDTH_Y / 3);

class Helpers_mtbqyx_Test extends TestCase {

  public function testIsValid() {
    $sref='1847-1';
    $this->assertFalse(mtbqyx::is_valid($sref));
    $sref='1847/1';
    $this->assertTrue(mtbqyx::is_valid($sref));
    $sref='1847/135';
    $this->assertTrue(mtbqyx::is_valid($sref));    
  }
  
  public function testSrefToWktQ() {
    $sref='1847/1';
    $wkt = mtbqyx::sref_to_wkt($sref);
    // test for top left quadrant going clockwise
    $left = CORNER_1847_SW_X;
    $right = CORNER_1847_SW_X + MTB_SQUARE_WIDTH_X / 2;
    $bottom = CORNER_1847_SW_Y + MTB_SQUARE_WIDTH_Y / 2;
    $top = CORNER_1847_SW_Y + MTB_SQUARE_WIDTH_Y;
    $this->assertEquals(
      "POLYGON(($left $bottom,$left $top,$right $top,$right $bottom,$left $bottom))",
      $wkt,
      'Sref to WKT returned an unexpected value'
    );
  }
  
  public function testSrefToWktQYX() {
    $square = 1847;
    $quadrant = 1;
    $y = 2;
    $x = 3;
    $sref="$square/$quadrant$y$x";
    $wkt = mtbqyx::sref_to_wkt($sref);
    // test for top left quadrant going clockwise
    $right = CORNER_1847_SW_X + QYX_SQUARE_WIDTH_X * $x;
    $left = $right - QYX_SQUARE_WIDTH_X;
    $bottom = CORNER_1847_SW_Y + Q_SQUARE_WIDTH_Y + QYX_SQUARE_WIDTH_Y * (3 - $y);
    $top = $bottom + QYX_SQUARE_WIDTH_Y;
    $this->assertEquals(
      "POLYGON(($left $bottom,$left $top,$right $top,$right $bottom,$left $bottom))",
      $wkt,
      'Sref to WKT returned an unexpected value'
    );
  }
  
  public function testWktToSrefQYX() {
    $square = 1847;
    $quadrant = 1;
    $y = 2;
    $x = 3;
    // Find a point just inside the square we are testing against
    $right = CORNER_1847_SW_X + QYX_SQUARE_WIDTH_X * $x;
    $left = $right - QYX_SQUARE_WIDTH_X;
    $bottom = CORNER_1847_SW_Y + Q_SQUARE_WIDTH_Y + QYX_SQUARE_WIDTH_Y * (3 - $y);
    $top = $bottom + QYX_SQUARE_WIDTH_Y;
    $midX = ($left + $right) / 2;
    $midY = ($top + $bottom) / 2;
    $wkt = "POINT($midX $midY)";
    $sref = mtbqyx::wkt_to_sref($wkt, null, null, 2000);
    $this->assertEquals(
      "$square/$quadrant$y$x",
      $sref,
      'WKT to SREF returned an unexpected value'
    );
  }

  public function testSrefToWktInvalidQ() {
    $this->expectException(InvalidArgumentException::class);
    $sref = '1847/a';
    $wkt = mtbqyx::sref_to_wkt($sref);
  }

  public function testSrefToWktInvalidQY() {
    $this->expectException(InvalidArgumentException::class);
    $sref = '1847/14';
    $wkt = mtbqyx::sref_to_wkt($sref);
  }

  public function testSrefToWktInvalidQYX() {
    // Limits of yx grid are 3,5.
    $this->expectException(InvalidArgumentException::class);
    $sref = '1847/146';
    $wkt = mtbqyx::sref_to_wkt($sref);
  }

}