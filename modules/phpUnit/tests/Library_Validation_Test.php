<?php

use PHPUnit\Framework\TestCase;

class Library_Validation_Test extends TestCase {

  public function testPreFiltersAreAppliedOncePerValidationPass() {
    $calls = 0;
    $filter = function ($value) use (&$calls) {
      $calls++;
      return $value;
    };
    $validation = new Validation(['value' => 'test']);
    $validation->pre_filter($filter);

    $validation->apply_pre_filters();
    $validation->validate();
    $this->assertEquals(1, $calls);

    $validation->validate();
    $this->assertEquals(2, $calls);
  }

  public function testCopiedValidationReappliesPreFilters() {
    $calls = 0;
    $filter = function ($value) use (&$calls) {
      $calls++;
      return strtoupper($value);
    };
    $validation = new Validation(['value' => 'first']);
    $validation->pre_filter($filter);
    $copy = $validation->copy(['value' => 'second']);

    $copy->validate();

    $this->assertEquals('SECOND', $copy['value']);
    $this->assertEquals(1, $calls);
  }

}
