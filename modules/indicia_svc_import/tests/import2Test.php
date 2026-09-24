<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for import_2 controller validation and request helpers.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Import2Test extends TestCase {

  /**
   * @var ReflectionClass
   */
  private ReflectionClass $controllerClass;

  /**
   * @var object
   */
  private object $controller;

  protected function setUp(): void {
    require_once 'modules/indicia_svc_import/controllers/services/import_2.php';
    $this->controllerClass = new ReflectionClass('Import_2_Controller');
    $this->controller = $this->controllerClass->newInstanceWithoutConstructor();
  }

  public function testGetConfigIdUsesConfigIdAndRemovesKnownExtension(): void {
    $method = $this->controllerClass->getMethod('getConfigId');
    $method->setAccessible(TRUE);

    $this->assertSame('import-123', $method->invoke($this->controller, 'import-123.csv'));
    $this->assertSame('import-456', $method->invoke($this->controller, 'import-456.json'));
  }

  public function testGetConfigIdReadsConfigIdFromPostWhenArgumentIsOmitted(): void {
    $_POST['config-id'] = 'import-789.xlsx';
    $method = $this->controllerClass->getMethod('getConfigId');
    $method->setAccessible(TRUE);

    $this->assertSame('import-789', $method->invoke($this->controller));
  }

  public function testValidateImportColumnsRejectsEmptyMappings(): void {
    $method = $this->controllerClass->getMethod('validateImportColumns');
    $method->setAccessible(TRUE);
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('at least one column heading');

    $method->invoke($this->controller, []);
  }

  public function testValidateImportColumnsAcceptsMappedColumns(): void {
    $method = $this->controllerClass->getMethod('validateImportColumns');
    $method->setAccessible(TRUE);

    $this->assertNull($method->invoke($this->controller, [
      ['warehouseField' => 'occurrence:comment'],
    ]));
  }

}
