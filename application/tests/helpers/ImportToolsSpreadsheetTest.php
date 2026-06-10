<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PHPUnit\Framework\TestCase;

/**
 * Basic import spreadsheet coverage for CSV and XLSX.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ImportToolsSpreadsheetTest extends TestCase {

  /**
   * @var string[]
   */
  private array $filesToDelete = [];

  protected function setUp(): void {
    require_once DOCROOT . 'application/libraries/ImportTools.php';
  }

  protected function tearDown(): void {
    foreach ($this->filesToDelete as $file) {
      if (file_exists($file)) {
        unlink($file);
      }
    }
  }

  public function testCsvImportBasics(): void {
    $fileName = 'test-import-' . uniqid() . '.csv';
    $fullPath = DOCROOT . 'import/' . $fileName;
    $this->filesToDelete[] = $fullPath;

    file_put_contents($fullPath, "ColA,ColB\nAlpha,Beta\nGamma,Delta\n");

    $tools = new ImportTools();
    $columns = $tools->loadColumnTitlesFromFile($fileName, true);
    $this->assertSame(['cola', 'colb'], array_values($columns));

    $config = ['rowsRead' => 0];
    $rows = $tools->openSpreadsheet($fileName, $config, 10);
    $rows->rewind();
    $firstRow = $tools->rowToArray($rows->current(), array_values($columns));
    $this->assertSame('Alpha', $firstRow['cola']);
    $this->assertSame('Beta', $firstRow['colb']);

    $this->assertSame(2, $tools->getRowCountForFile($fileName));
    $tools->disconnect();
  }

  public function testXlsxImportBasics(): void {
    $fileName = 'test-import-' . uniqid() . '.xlsx';
    $fullPath = DOCROOT . 'import/' . $fileName;
    $this->filesToDelete[] = $fullPath;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
      ['ColA', 'ColB'],
      ['Alpha', 'Beta'],
      ['Gamma', 'Delta'],
    ]);
    (new XlsxWriter($spreadsheet))->save($fullPath);
    $spreadsheet->disconnectWorksheets();

    $tools = new ImportTools();
    $columns = $tools->loadColumnTitlesFromFile($fileName, false);
    $this->assertSame(['ColA', 'ColB'], array_values($columns));

    $config = ['rowsRead' => 0];
    $rows = $tools->openSpreadsheet($fileName, $config, 10);
    $rows->rewind();
    $firstRow = $tools->rowToArray($rows->current(), array_values($columns));
    $this->assertSame('Alpha', $firstRow['ColA']);
    $this->assertSame('Beta', $firstRow['ColB']);

    $this->assertSame(2, $tools->getRowCountForFile($fileName));
    $tools->disconnect();
  }

}
