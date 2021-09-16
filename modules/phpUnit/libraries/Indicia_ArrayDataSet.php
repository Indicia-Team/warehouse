<?php

use PHPUnit\DbUnit\DataSet\AbstractDataSet as DbUDataSetAbstractDataSet;
use PHPUnit\DbUnit\DataSet\DefaultTable as DbUDataSetDefaultTable;
use PHPUnit\DbUnit\DataSet\DefaultTableIterator as DbUDataSetDefaultTableIterator;
use PHPUnit\DbUnit\DataSet\DefaultTableMetadata as DbUDataSetDefaultTableMetadata;
use PHPUnit\DbUnit\DataSet\ITable as DbUDataSetITable;
use PHPUnit\DbUnit\DataSet\ITableIterator as DbUDataSetITableIterator;

/**
 * Implements a dataset created from a PHP array.
 * https://phpunit.de/manual/6.5/en/database.html#database.available-implementations
 */
class Indicia_ArrayDataSet extends DbUDataSetAbstractDataSet {
  /**
   * @var array
   */
  protected $tables = [];

  /**
   * @param array $data
   */
  public function __construct(array $data) {
    foreach ($data as $tableName => $rows) {
      $columns = [];
      if (isset($rows[0])) {
        $columns = array_keys($rows[0]);
      }

      $metaData = new DbUDataSetDefaultTableMetadata($tableName, $columns);
      $table = new DbUDataSetDefaultTable($metaData);

      foreach ($rows as $row) {
        $table->addRow($row);
      }
      $this->tables[$tableName] = $table;
    }
  }

  protected function createIterator(bool $reverse = false): DbUDataSetITableIterator {
    return new DbUDataSetDefaultTableIterator($this->tables, $reverse);
  }

  public function getTable(string $tableName): DbUDataSetITable {
    if (!isset($this->tables[$tableName])) {
      throw new InvalidArgumentException("$tableName is not a table in the current database.");
    }

    return $this->tables[$tableName];
  }

}
