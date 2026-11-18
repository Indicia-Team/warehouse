<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for import_2 controller validation, requests and staging.
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

  public function testXlsStagingCountsOnlyNonBlankRows(): void {
    $fileName = uniqid('test_import_') . '.xls';
    $path = DOCROOT . 'import/' . $fileName;
    $spreadsheet = new Spreadsheet();
    $db = new Database();
    $config = [
      'entity' => 'occurrence',
      'parentEntity' => 'sample',
      'columns' => ['Comment' => ['tempDbField' => 'comment']],
      'state' => 'loadingRecords',
      'processingMode' => 'immediate',
      'rowsLoaded' => 0,
    ];
    try {
      $sheet = $spreadsheet->getActiveSheet();
      $sheet->setCellValue('A1', 'Comment');
      $sheet->setCellValue('A2', 'First record');
      $sheet->setCellValue('A3', " \t ");
      // Leave a full batch blank before the next record.
      $sheet->setCellValue('A1003', 0);
      $sheet->setCellValue('A1004', ' ');
      (new Xls($spreadsheet))->save($path);
      $tools = new ImportTools();
      $config['totalRows'] = $tools->getRowCountForFile($fileName);
      $config['files'][$fileName] = ['rowCount' => $config['totalRows'], 'rowsRead' => 0];

      $createTable = $this->controllerClass->getMethod('createTempTable');
      $createTable->setAccessible(TRUE);
      $createTable->invokeArgs($this->controller, [$fileName, &$config]);
      $loadBatch = $this->controllerClass->getMethod('loadNextRecordsBatch');
      $loadBatch->setAccessible(TRUE);
      for ($i = 0; $i < 3 && $config['state'] === 'loadingRecords'; $i++) {
        $loadBatch->invokeArgs($this->controller, [$fileName, &$config]);
      }

      $this->assertSame('loaded', $config['state']);
      $this->assertSame(2, $config['rowsLoaded']);
      $this->assertSame(2, $config['totalRows']);
      $this->assertEquals(100, $config['progress']);
      $rows = $db->query("SELECT comment FROM import_temp.$config[tableName] ORDER BY _row_id")->result_array();
      $this->assertSame(['First record', '0'], array_column($rows, 'comment'));
    }
    finally {
      $spreadsheet->disconnectWorksheets();
      if (is_file($path)) {
        unlink($path);
      }
      if (isset($config['tableName'])) {
        $db->query("DROP TABLE IF EXISTS import_temp.$config[tableName]");
      }
    }
  }

}
