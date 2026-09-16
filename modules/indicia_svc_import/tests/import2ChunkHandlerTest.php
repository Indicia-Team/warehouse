<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for import chunk helper utilities.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Import2ChunkHandlerTest extends TestCase {

  /**
   * @var string[]
   */
  private array $configFilesToDelete = [];

  protected function setUp(): void {
    require_once 'modules/indicia_svc_import/helpers/import2ChunkHandler.php';
  }

  protected function tearDown(): void {
    foreach ($this->configFilesToDelete as $file) {
      if (file_exists($file)) {
        unlink($file);
      }
    }
  }

  public function testTokeniseMultiValueCellCanonicalisesAndDeduplicatesTokens(): void {
    $tokens = import2ChunkHandler::tokeniseMultiValueCell(' Blue;"Red;Green";blue ; ;RED ');

    $this->assertSame(['blue', 'red;green', 'red'], $tokens);
  }

  public function testTokeniseMultiValueCellReturnsEmptyArrayForBlankInput(): void {
    $this->assertSame([], import2ChunkHandler::tokeniseMultiValueCell(" \t\n"));
    $this->assertSame([], import2ChunkHandler::tokeniseMultiValueCell(NULL));
  }

  public function testFindEntityColumnsIncludesEntityAttributeAndMediumColumns(): void {
    $config = [
      'columns' => [
        ['warehouseField' => 'occurrence:comment'],
        ['warehouseField' => 'occAttr:behaviour'],
        ['warehouseField' => 'occurrence_medium:path'],
        ['warehouseField' => 'sample:comment'],
      ],
      'systemAddedColumns' => [
        ['warehouseField' => 'occurrence:id'],
      ],
    ];

    $columns = import2ChunkHandler::findEntityColumns('occurrence', $config);

    $this->assertSame([
      ['warehouseField' => 'occurrence:comment'],
      ['warehouseField' => 'occAttr:behaviour'],
      ['warehouseField' => 'occurrence_medium:path'],
      ['warehouseField' => 'occurrence:id'],
    ], $columns);
  }

  public function testGetColumnInfoByPropertySupportsForeignKeyAliases(): void {
    $columns = [
      'Site' => [
        'tempDbField' => 'site',
        'isFkField' => TRUE,
      ],
    ];

    $result = import2ChunkHandler::getColumnInfoByProperty($columns, 'tempDbField', 'site_id');

    $this->assertSame('Site', $result['columnLabel']);
    $this->assertSame('site', $result['tempDbField']);
  }

  public function testGetColumnInfoByPropertyThrowsForMissingProperty(): void {
    $this->expectException(ColNotFoundException::class);

    import2ChunkHandler::getColumnInfoByProperty([], 'warehouseField', 'occurrence:id');
  }

  public function testConfigCanBeSavedAndLoadedWithoutChangingValues(): void {
    $configId = 'test-import-config-' . uniqid('', TRUE);
    $configFile = DOCROOT . "import/$configId.json";
    $this->configFilesToDelete[] = $configFile;
    $config = [
      'entity' => 'occurrence',
      'rowsProcessed' => 12,
      'errorsCount' => 2,
      'columns' => [
        ['warehouseField' => 'occurrence:comment'],
      ],
    ];

    import2ChunkHandler::saveConfig($configId, $config);

    $this->assertSame($config, import2ChunkHandler::getConfig($configId));
  }

}
