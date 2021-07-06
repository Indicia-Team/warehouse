<?php

use PHPUnit\DbUnit\TestCase as DbUTestCase;
use PHPUnit\DbUnit\Database\Connection as DbUDatabaseConnection;
use PHPUnit\DbUnit\Database\DefaultConnection as dbUDatabaseDefualtConnection;
use PHPUnit\DbUnit\Database\Metadata\AbstractMetadata as DbUDatabaseMetadataAbstractMetadata;
use PHPUnit\DbUnit\Database\Metadata\PgSQL as DbUDatabaseMetadataPgSQL;
use PHPUnit\DbUnit\DataSet\IDataSet as DbUDatasetIDataset;
use PHPUnit\DbUnit\Operation\Composite as DbUOperationComposite;
use PHPUnit\DbUnit\Operation\Exception as DbUOperationException;
use PHPUnit\DbUnit\Operation\Factory as DbUOperationFactory;
use PHPUnit\DbUnit\Operation\Operation as DbUOperationOperation;

/**
 * An abstract test case to efficiently make database connections.
 * https://phpunit.de/manual/current/en/database.html#database.tip-use-your-own-abstract-database-testcase
 */
abstract class Indicia_DatabaseTestCase extends DbUTestCase {
  // Only instantiate pdo once for test clean-up/fixture load.
  static private $pdo = NULL;

  // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
  private $conn = null;

  final public function getConnection() {
    if ($this->conn === null) {
      if (self::$pdo == null) {
        $dsn = 'pgsql:host=127.0.0.1;dbname=indicia';
        $user = 'indicia_user';
        $pass = 'indicia_user_pass';
        self::$pdo = new PDO($dsn, $user, $pass);
        }
      $this->conn = $this->createDefaultDBConnection(self::$pdo, 'indicia');
    }

    return $this->conn;
  }
  
  // Default implementation which does nothing
  public function getDataSet() {
    return new Indicia_ArrayDataSet(array());
  }

  // Override the operation used to set up the database.
  // The default is CLEAN_INSERT($cascadeTruncates = FALSE)
  // We require truncates to be cascaded to prevent foreign key
  // violations and sequences to be restarted.
  protected function getSetUpOperation() {
     return My_Operation_Factory::RESTART_INSERT(true);
  }
  
  // Override the function to create the database connection so that is uses 
  // My_DB_DefaultDatabaseConnection.
  protected function createDefaultDBConnection(PDO $connection, $schema = ''): dbUDatabaseDefualtConnection {
    return new My_DB_DefaultDatabaseConnection($connection, $schema);
  }  
}

/**
 * Extends PHPUnit_Extensions_Database_DB_MetaData_PgSQL in order to create
 * a database specific command to restart sequences.
 */
class My_DB_MetaData_PgSQL extends DbUDatabaseMetadataPgSQL {
  public function getRestartCommand($table) {
    // Assumes sequence naming convention has been followed.
    $seq = $table . '_id_seq';
    return "DO $$"
      . "BEGIN"
      . " IF EXISTS (SELECT * FROM  pg_class WHERE relkind = 'S' AND relname = '$seq')"
      . "  THEN PERFORM setval('$seq', 1, false);"
      . " END IF;"
      . "END"
      . "$$";
  }
}

/**
 * Extends PHPUnit_Extensions_Database_DB_MetaData in order to replace the 
 * default postgres driver with mine.
 */
abstract class My_DB_MetaData extends DbUDatabaseMetadataAbstractMetadata {
  public static function createMetaData(PDO $pdo, $schema = '') {
    self::$metaDataClassMap['pgsql'] = 'My_DB_MetaData_PgSQL';
    return parent::createMetaData($pdo, $schema);
  }    
}

/**
 * Extends PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection in order to
 * a. override the constructor so that it uses My_DB_MetaData.
 * b. give access to the command that will restart sequences.
 */
class My_DB_DefaultDatabaseConnection extends dbUDatabaseDefualtConnection {
  public function __construct(PDO $connection, $schema = '') {
    $this->connection = $connection;
    $this->metaData   = My_DB_MetaData::createMetaData($connection, $schema);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  public function getRestartCommand($table) {
    return $this->getMetaData()->getRestartCommand($table);
  }
}

/**
 * New class to add a Restart operation.
 */
Class My_Operation_Restart implements DbUOperationOperation {
  public function execute(
    DbUDatabaseConnection $connection,
    DbUDatasetIDataset $dataSet
    ): void {
    foreach ($dataSet->getReverseIterator() as $table) {
      $tableName = $table->getTableMetaData()->getTableName();
      $query = $connection->getRestartCommand($tableName);
      try {
          $connection->getConnection()->query($query);
      } catch (\Exception $e) {
        if ($e instanceof PDOException) {
          throw new DbUOperationException('RESTART', $query, [], $table, $e->getMessage());
        }
        throw $e;
      }
    }
  }
}

/**
 * Extends PHPUnit_Extensions_Database_Operation_Factory in order to add 
 * functions that call the Restart operation.
 */
Class My_Operation_Factory extends DbUOperationFactory {
  public static function RESTART_INSERT($cascadeTruncates = FALSE) {
    return new DbUOperationComposite([
      self::TRUNCATE($cascadeTruncates),
      self::RESTART(),
      self::INSERT()
    ]);
  }

  public static function RESTART() {
    return new My_Operation_Restart();
  }
}

