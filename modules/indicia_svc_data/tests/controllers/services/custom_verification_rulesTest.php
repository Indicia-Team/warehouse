<?php

use PHPUnit\Framework\TestCase;

class Controllers_Services_Custom_Verification_Rules_Test extends TestCase {

  /**
   * @var object
   */
  private $controller;

  protected function setUp(): void {
    require_once 'modules/indicia_svc_data/controllers/services/custom_verification_rules.php';
    $reflection = new ReflectionClass('Custom_verification_rules_Controller');
    $this->controller = $reflection->newInstanceWithoutConstructor();
  }

  public function testGetValueAsArrayTrimsValues(): void {
    $result = $this->invoke('getValueAsArray', [
      [' first ; second '],
      ['values' => 0],
      'values',
    ]);

    $this->assertSame(['first', 'second'], $result);
  }

  public function testGetValueAsArrayReturnsNullForMissingValue(): void {
    $result = $this->invoke('getValueAsArray', [
      [],
      [],
      'values',
    ]);

    $this->assertNull($result);
  }

  public function testValidateRequiredReportsMissingValues(): void {
    $errors = [];
    $this->invoke('validateRequired', [
      &$errors,
      ['value'],
      4,
      ['present' => 0],
      ['present', 'missing'],
    ]);

    $this->assertCount(1, $errors);
    $this->assertStringContainsString('missing', $errors[0]);
    $this->assertStringContainsString('row 4', $errors[0]);
  }

  public function testValidateAnyRequiredAcceptsOneProvidedValue(): void {
    $errors = [];
    $this->invoke('validateAnyRequired', [
      &$errors,
      ['value'],
      4,
      ['second' => 0],
      ['first', 'second'],
    ]);

    $this->assertSame([], $errors);
  }

  public function testValidatePhenologyRejectsDayWithoutItsMonth(): void {
    $errors = [];
    $this->invoke('validatePhenology', [
      &$errors,
      [
        '10',
        '6',
      ],
      7,
      [
        'min day' => 0,
        'max month' => 1,
      ],
    ]);

    $this->assertCount(1, $errors);
    $this->assertStringContainsString('min day', $errors[0]);
    $this->assertStringContainsString('min month', $errors[0]);
  }

  public function testValidatePhenologyRejectsInvalidDayForMonth(): void {
    $errors = [];
    $this->invoke('validatePhenology', [
      &$errors,
      [
        '2',
        '30',
      ],
      8,
      [
        'min month' => 0,
        'min day' => 1,
      ],
    ]);

    $this->assertCount(1, $errors);
    $this->assertStringContainsString('Invalid day number', $errors[0]);
  }

  public function testValidatePhenologyRejectsReversedDateRange(): void {
    $errors = [];
    $this->invoke('validatePhenology', [
      &$errors,
      [
        '9',
        '1',
        '3',
        '31',
      ],
      9,
      [
        'min month' => 0,
        'min day' => 1,
        'max month' => 2,
        'max day' => 3,
      ],
    ]);

    $this->assertCount(1, $errors);
    $this->assertStringContainsString('wrong way round', $errors[0]);
  }

  public function testValidateGridRefFieldsRequiresAndValidatesSystem(): void {
    $errors = [];
    $this->invoke('validateGridRefFields', [
      &$errors,
      ['SU1234'],
      10,
      ['grid refs' => 0],
      'grid refs',
      'grid ref system',
    ]);

    $this->assertCount(1, $errors);
    $this->assertStringContainsString('grid ref system', $errors[0]);
  }

  public function testValidateLocationIdFieldsRejectsInvalidList(): void {
    $errors = [];
    $this->invoke('validateLocationIdFields', [
      &$errors,
      ['1;two'],
      11,
      ['location ids' => 0],
      'location ids',
    ]);

    $this->assertCount(1, $errors);
    $this->assertStringContainsString('Invalid format', $errors[0]);
  }

  public function testDefinitionBuildersReturnExpectedValues(): void {
    $row = [
      'max individual count' => '5',
      'grid refs' => 'SU1234;SU1235',
      'grid ref system' => 'osgb',
      'min longitude' => '-2.5',
      'min month' => '3',
      'max month' => '9',
      'min day' => '1',
      'max day' => '30',
      'min year' => '2000',
      'max year' => '2020',
    ];
    $indexes = array_flip(array_keys($row));
    $row = array_values($row);

    $this->assertSame(
      ['max_individual_count' => '5'],
      $this->invoke('getAbundanceDefinition', [$row, $indexes])
    );
    $this->assertSame(
      [
        'grid_refs' => ['SU1234', 'SU1235'],
        'grid_ref_system' => 'osgb',
        'min_lng' => '-2.5',
      ],
      $this->invoke('getGeographyDefinition', [$row, $indexes])
    );
    $this->assertSame(
      [
        'min_month' => '3',
        'max_month' => '9',
        'min_day' => '1',
        'max_day' => '30',
      ],
      $this->invoke('getPhenologyDefinition', [$row, $indexes])
    );
    $this->assertSame(
      [
        'min_year' => '2000',
        'max_year' => '2020',
      ],
      $this->invoke('getPeriodDefinition', [$row, $indexes])
    );
  }

  /**
   * Invoke a private controller method.
   *
   * @param string $method
   *   Method name.
   * @param array $arguments
   *   Method arguments.
   *
   * @return mixed
   *   Method result.
   */
  private function invoke($method, array $arguments = []) {
    $reflection = new ReflectionMethod($this->controller, $method);
    $reflection->setAccessible(TRUE);
    return $reflection->invokeArgs($this->controller, $arguments);
  }

}
