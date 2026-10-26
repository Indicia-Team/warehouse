<?php

use PHPUnit\Framework\TestCase;

/**
 * Test batch verification permissions without the warehouse HTTP service.
 *
 * PostgreSQL cases use temporary tables only. To run them, set
 * INDICIA_TEST_PG_DSN to a PDO PostgreSQL DSN for a test connection.
 */
class Controllers_Services_Verification_Access_Test extends TestCase {

  /**
   * Load the real permission check without initialising controllers or models.
   */
  public static function setUpBeforeClass(): void {
    $root = dirname(__DIR__, 5);
    if (!defined('SYSPATH')) {
      define('SYSPATH', $root . '/system/');
    }
    if (!class_exists('Controller')) {
      require_once SYSPATH . 'libraries/Controller.php';
      class_alias('Controller_Core', 'Controller');
    }
    require_once $root . '/application/controllers/service_base.php';
    require_once $root . '/application/helpers/warehouse.php';
    require_once $root . '/modules/indicia_svc_data/controllers/data_service_base.php';
    require_once $root . '/modules/indicia_svc_data/controllers/services/data_utils.php';
  }

  /**
   * Enforce a constant query count for single records and large batches.
   *
   * @param int $size
   *   Number of records to authorise.
   *
   * @dataProvider batchSizes
   */
  public function testOnePermissionQueryPerBatch(int $size) {
    $db = $this->database(NULL, $size);
    $this->checkAccess($db, 'occurrence', range(1, $size));
    $this->assertCount(1, $db->queries);
    $this->assertStringContainsString('to_website_id=1 AND provide_for_verification=true', $db->queries[0]);
    $this->assertStringContainsString('r.deleted=false', $db->queries[0]);
  }

  /**
   * @return array
   *   Single-record, ordinary batch, and large batch sizes.
   */
  public static function batchSizes(): array {
    return [[1], [100], [10000]];
  }

  /**
   * Empty selections must neither query permissions nor grant any record IDs.
   */
  public function testEmptyBatchDoesNotQuery() {
    $db = $this->database();
    $this->checkAccess($db, 'sample', []);
    $this->assertSame([], $db->queries);
  }

  /**
   * Reject malformed IDs before building or executing a permission query.
   */
  public function testInvalidIdsDoNotQuery() {
    $db = $this->database();
    try {
      $this->checkAccess($db, 'occurrence', ['1); SELECT 1']);
      $this->fail('Invalid record IDs were accepted.');
    }
    catch (Exception $e) {
      $this->assertSame('Parameter format incorrect', $e->getMessage());
      $this->assertSame([], $db->queries);
    }
  }

  /**
   * Reject the entire selection when even one requested ID is not authorised.
   */
  public function testPartialAccessRejectsBatch() {
    $db = $this->database(NULL, 1);
    $this->expectException(AuthorisationError::class);
    $this->checkAccess($db, 'occurrence', [1, 2]);
  }

  /**
   * Verify actual SQL semantics, including agreement direction and live rows.
   *
   * @param string $entity
   *   Occurrence or sample.
   * @param array $ids
   *   Requested records from the temporary fixtures.
   * @param array $context
   *   Authentication properties overriding website 1.
   * @param bool $allowed
   *   Whether the whole batch should be authorised.
   *
   * @dataProvider permissionCases
   */
  public function testPostgresPermissions(string $entity, array $ids, array $context, bool $allowed) {
    $dsn = getenv('INDICIA_TEST_PG_DSN');
    if (!$dsn || !extension_loaded('pdo_pgsql')) {
      $this->markTestSkipped('Set INDICIA_TEST_PG_DSN with pdo_pgsql enabled to run temporary-table SQL tests.');
    }
    $pdo = new PDO($dsn, NULL, NULL, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec(<<<SQL
      CREATE TEMP TABLE occurrences (id integer PRIMARY KEY, website_id integer, deleted boolean DEFAULT false);
      CREATE TEMP TABLE surveys (id integer PRIMARY KEY, website_id integer, deleted boolean DEFAULT false);
      CREATE TEMP TABLE samples (id integer PRIMARY KEY, survey_id integer, deleted boolean DEFAULT false);
      CREATE TEMP TABLE index_websites_website_agreements (
        from_website_id integer, to_website_id integer,
        provide_for_verification boolean, receive_for_verification boolean
      );
      CREATE TEMP TABLE users_websites (user_id integer, website_id integer, site_role_id integer, banned boolean);
      INSERT INTO occurrences VALUES (1,1,false),(2,2,false),(3,3,false),(4,4,false),(5,1,true);
      INSERT INTO surveys VALUES (1,1,false),(2,2,false),(3,3,false),(4,4,false),(5,1,true);
      INSERT INTO samples VALUES (1,1,false),(2,2,false),(3,3,false),(4,4,false),(5,5,false),(6,NULL,false),(7,1,true);
      INSERT INTO index_websites_website_agreements VALUES (2,1,true,false),(1,3,true,false),(4,1,false,true);
      INSERT INTO users_websites VALUES (2,1,1,false),(2,3,1,true),(3,4,NULL,false);
    SQL);
    $db = $this->database($pdo);
    if (!$allowed) {
      $this->expectException(AuthorisationError::class);
    }
    $this->checkAccess($db, $entity, $ids, $context);
    $this->assertCount(1, $db->queries);
  }

  /**
   * @return array
   *   Own-site, shared, forbidden, missing/deleted, and warehouse-user cases.
   */
  public static function permissionCases(): array {
    $warehouse = ['website_id' => 0, 'in_warehouse' => TRUE, 'user_id' => 2];
    $admin = ['website_id' => 0, 'in_warehouse' => TRUE, 'user_id' => 1, 'user_is_core_admin' => TRUE];
    return [
      'own site without self agreement' => ['occurrence', [1], [], TRUE],
      'shared occurrence' => ['occurrence', [2], [], TRUE],
      'own and shared batch' => ['occurrence', [1, 2], [], TRUE],
      'duplicate IDs' => ['occurrence', [1, '01', '1'], [], TRUE],
      'reverse agreement' => ['occurrence', [3], [], FALSE],
      'receive flag is not provide permission' => ['occurrence', [4], [], FALSE],
      'mixed permitted and forbidden batch' => ['occurrence', [1, 3], [], FALSE],
      'deleted occurrence' => ['occurrence', [5], [], FALSE],
      'missing occurrence' => ['occurrence', [999], [], FALSE],
      'own sample' => ['sample', [1], [], TRUE],
      'shared sample' => ['sample', [2], [], TRUE],
      'reverse agreement sample' => ['sample', [3], [], FALSE],
      'deleted survey' => ['sample', [5], [], FALSE],
      'missing survey' => ['sample', [6], [], FALSE],
      'deleted sample' => ['sample', [7], [], FALSE],
      'website token cannot use core-user privileges' => ['occurrence', [3], ['auth_user_id' => 1], FALSE],
      'warehouse user website' => ['occurrence', [1], $warehouse, TRUE],
      'warehouse user banned website' => ['occurrence', [3], $warehouse, FALSE],
      'warehouse core admin' => ['occurrence', [4], $admin, TRUE],
      'core admin cannot modify deleted records' => ['occurrence', [5], $admin, FALSE],
      'missing authenticated website' => ['occurrence', [1], ['website_id' => NULL], FALSE],
    ];
  }

  /**
   * Invoke the real check with an explicitly configured authentication context.
   *
   * @param object $db
   *   Query-counting database adapter.
   * @param string $entity
   *   Record entity.
   * @param array $ids
   *   Batch IDs.
   * @param array $context
   *   Authentication properties.
   */
  private function checkAccess($db, string $entity, array $ids, array $context = []): void {
    $reflection = new ReflectionClass(Data_utils_Controller::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    foreach (array_merge(['website_id' => 1], $context) as $name => $value) {
      $reflection->getProperty($name)->setValue($controller, $value);
    }
    $method = $reflection->getMethod('checkVerificationAccess');
    $method->setAccessible(TRUE);
    $method->invoke($controller, $db, $entity, $ids);
  }

  /**
   * Build a query-counting adapter with either real SQL or a fixed row count.
   *
   * @param PDO|null $pdo
   *   Optional PostgreSQL connection using temporary tables.
   * @param int $count
   *   Simulated count for database-independent query-count tests.
   *
   * @return object
   *   Adapter implementing the query/current methods used by the check.
   */
  private function database(?PDO $pdo = NULL, int $count = 0) {
    return new class($pdo, $count) {
      public $queries = [];
      private $pdo;
      private $count;

      public function __construct(?PDO $pdo, int $count) {
        $this->pdo = $pdo;
        $this->count = $count;
      }

      public function query(string $sql) {
        $this->queries[] = $sql;
        $row = $this->pdo ? $this->pdo->query($sql)->fetchObject() : (object) ['count' => $this->count];
        return new class($row) {
          private $row;

          public function __construct(object $row) {
            $this->row = $row;
          }

          public function current(): object {
            return $this->row;
          }
        };
      }
    };
  }

}
