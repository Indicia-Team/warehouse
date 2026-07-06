<?php

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class IndiciaImportFirstRowReadFilter implements IReadFilter {

  /**
   * Check if a cell should be read.
   *
   * @param string $columnAddress
   *   The column address (e.g. 'A', 'B', etc.) of the cell being checked.
   * @param int $row
   *   The row number of the cell being checked.
   * @param string $worksheetName
   *   The name of the worksheet being checked (not used in this filter).
   *
   * @return bool
   *   TRUE if the cell should be read, FALSE otherwise.
   */
  public function readCell($columnAddress, $row, $worksheetName = '') {
    return $row == 1;
  }

}

class IndiciaImportRangeReadFilter implements IReadFilter {

  /**
   * Start of range to read.
   *
   * @var int
   */
  private $offset;

  /**
   * Number of rows to read.
   *
   * @var int
   */
  private $limit;

  /**
   * If limited to columns, the 0-based indexes of columns to read.
   *
   * E.g. [0, 2] to read only columns A and C). If NULL, all columns are read.
   *
   * @var array
   */
  private $columnIndexes;

  /**
   * Read filter constructor sets the cell range to read.
   *
   * @param int $offset
   *   Start of range to read.
   * @param int $limit
   *   Number of rows to read.
   * @param array|null $columnIndexes
   *   If limited to columns, the 0-based indexes of columns to read. If NULL, all columns are read.
   */
  public function __construct(int $offset, int $limit, ?array $columnIndexes = NULL) {
    $this->offset = $offset;
    $this->limit = $limit;
    $this->columnIndexes = $columnIndexes;
  }

  /**
   * Check if a cell should be read.
   *
   * Based on the range and column indexes set in the constructor.
   *
   * @param mixed $columnAddress
   *   The column address (e.g. 'A', 'B', etc.) of the cell being checked.
   * @param mixed $row
   *   The row number of the cell being checked.
   * @param mixed $worksheetName
   *   The name of the worksheet being checked (not used in this filter).
   *
   * @return bool
   *   TRUE if the cell should be read, FALSE otherwise.
   */
  public function readCell($columnAddress, $row, $worksheetName = '') {
    $inRange = $row >= $this->offset && $row < $this->offset + $this->limit;
    $wantCol = $this->columnIndexes === NULL || in_array(ord($columnAddress) - 65, $this->columnIndexes);
    return $inRange && $wantCol;
  }

}
