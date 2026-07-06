<?php

use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PHPUnit\Framework\TestCase;

/**
 * Basic compatibility and smoke tests for spreadsheet read filters.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SpreadsheetReadFilterSignatureTest extends TestCase {

  /**
   * @var string[]
   */
  private array $filesToDelete = [];

  protected function setUp(): void {
    require_once DOCROOT . 'modules/rest_api/helpers/rest_spreadsheet_verify.php';
  }

  protected function tearDown(): void {
    foreach ($this->filesToDelete as $file) {
      if (file_exists($file)) {
        unlink($file);
      }
    }
  }

  public function testReadCellSignaturesMatchPhpSpreadsheet57Contract(): void {
    $interfaceMethod = new ReflectionMethod(PhpOffice\PhpSpreadsheet\Reader\IReadFilter::class, 'readCell');
    $interfaceParams = $interfaceMethod->getParameters();
    $interfaceReturnType = $interfaceMethod->getReturnType();

    $firstMethod = new ReflectionMethod(IndiciaImportFirstRowReadFilter::class, 'readCell');
    $rangeMethod = new ReflectionMethod(IndiciaImportRangeReadFilter::class, 'readCell');

    foreach ([
      $firstMethod,
      $rangeMethod,
    ] as $method) {
      foreach ([0, 1, 2] as $paramIndex) {
        $expectedType = $interfaceParams[$paramIndex]->getType();
        $actualType = $method->getParameters()[$paramIndex]->getType();
        $this->assertSame(
          $expectedType ? (string) $expectedType : NULL,
          $actualType ? (string) $actualType : NULL
        );
      }
      $actualReturnType = $method->getReturnType();
      $this->assertSame(
        $interfaceReturnType ? (string) $interfaceReturnType : NULL,
        $actualReturnType ? (string) $actualReturnType : NULL
      );
    }
  }

  public function testCsvCanBeReadWithFirstRowAndRangeFilters(): void {
    $file = DOCROOT . 'import/test-filter-' . uniqid() . '.csv';
    $this->filesToDelete[] = $file;
    file_put_contents(
      $file,
      "ID,*Decision status*,*Decision comment*\n1,Accepted as correct,ok\n2,,\n"
    );

    $headerReader = new Csv();
    $headerReader->setReadDataOnly(true);
    $headerReader->setReadFilter(new IndiciaImportFirstRowReadFilter());
    $headerSheet = $headerReader->load($file)->getActiveSheet();
    $this->assertSame('ID', $headerSheet->getCell('A1')->getValue());
    $this->assertNull($headerSheet->getCell('A2')->getValue());

    $rangeReader = new Csv();
    $rangeReader->setReadDataOnly(true);
    $rangeReader->setReadFilter(new IndiciaImportRangeReadFilter(2, 1, [0, 1, 2]));
    $rangeSheet = $rangeReader->load($file)->getActiveSheet();
    $this->assertSame('1', (string) $rangeSheet->getCell('A2')->getValue());
    $this->assertSame('Accepted as correct', $rangeSheet->getCell('B2')->getValue());
  }

  public function testXlsxCanBeReadWithFirstRowAndRangeFilters(): void {
    $file = DOCROOT . 'import/test-filter-' . uniqid() . '.xlsx';
    $this->filesToDelete[] = $file;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
      ['ID', '*Decision status*', '*Decision comment*'],
      [1, 'Accepted as correct', 'ok'],
      [2, '', ''],
    ]);
    (new XlsxWriter($spreadsheet))->save($file);
    $spreadsheet->disconnectWorksheets();

    $headerReader = new Xlsx();
    $headerReader->setReadDataOnly(true);
    $headerReader->setReadFilter(new IndiciaImportFirstRowReadFilter());
    $headerSheet = $headerReader->load($file)->getActiveSheet();
    $this->assertSame('ID', $headerSheet->getCell('A1')->getValue());
    $this->assertNull($headerSheet->getCell('A2')->getValue());

    $rangeReader = new Xlsx();
    $rangeReader->setReadDataOnly(true);
    $rangeReader->setReadFilter(new IndiciaImportRangeReadFilter(2, 1, [0, 1, 2]));
    $rangeSheet = $rangeReader->load($file)->getActiveSheet();
    $this->assertSame(1, $rangeSheet->getCell('A2')->getValue());
    $this->assertSame('Accepted as correct', $rangeSheet->getCell('B2')->getValue());
  }

}
