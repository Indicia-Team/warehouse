<?php

use PHPUnit\Framework\TestCase;

class Helper_MY_Valid_Test extends TestCase {

  /**
   * Test valid boolean representations, including values used by imports.
   */
  public function testBoolAcceptsBooleanRepresentations(): void {
    foreach ([TRUE, FALSE, 't', 'f', 'true', 'false', '1', '0', 'yes', 'no', 'on', 'off'] as $value) {
      $this->assertTrue(Valid::bool($value), 'Expected a valid boolean representation.');
    }
  }

  /**
   * Test blank and unsupported values are not accepted as booleans.
   */
  public function testBoolRejectsBlankAndInvalidValues(): void {
    foreach (['', NULL, 'not a boolean'] as $value) {
      $this->assertFalse(Valid::bool($value), 'Expected an invalid boolean representation.');
    }
  }

}
