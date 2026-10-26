<?php

use PHPUnit\Framework\TestCase;

if (!defined('SYSPATH')) {
  define('SYSPATH', __DIR__ . '/../../../system/');
}

require_once SYSPATH . 'libraries/drivers/Session.php';
require_once SYSPATH . 'libraries/drivers/Session/Database.php';
require_once SYSPATH . 'libraries/drivers/Session/Cache.php';
require_once SYSPATH . 'libraries/drivers/Session/Cookie.php';

/**
 * Tests direct registration of Kohana drivers with PHP's session handler API.
 */
class Library_Session_Driver_Test extends TestCase {

  /**
   * @dataProvider driverClasses
   */
  public function testImplementsSessionInterfaces(string $class) {
    $reflection = new ReflectionClass($class);
    $this->assertTrue($reflection->implementsInterface(Session_Driver::class));
    $this->assertTrue($reflection->implementsInterface(SessionHandlerInterface::class));
    $interface = new ReflectionClass(Session_Driver::class);
    foreach ($interface->getMethods() as $method) {
      $this->assertTrue($method->hasReturnType());
      $this->assertSame(
        (string) $method->getReturnType(),
        (string) $reflection->getMethod($method->getName())->getReturnType()
      );
    }
  }

  public static function driverClasses(): array {
    return [
      'database' => [Session_Database_Driver::class],
      'cache' => [Session_Cache_Driver::class],
      'cookie' => [Session_Cookie_Driver::class],
    ];
  }

  public function testBackendManagedGarbageCollectionReturnsZero() {
    foreach ([Session_Cache_Driver::class, Session_Cookie_Driver::class] as $class) {
      $driver = (new ReflectionClass($class))->newInstanceWithoutConstructor();
      $this->assertSame(0, $driver->gc(3600));
      $this->assertTrue($driver->close());
    }
  }

  /**
   * @dataProvider deletionCounts
   */
  public function testDatabaseGarbageCollectionReturnsDeletionCount(int $count) {
    require_once SYSPATH . 'core/Kohana.php';
    $configuration = new ReflectionProperty(Kohana::class, 'configuration');
    $originalConfiguration = $configuration->getValue();
    $configuration->setValue(NULL, ['core' => ['log_threshold' => 0]]);

    try {
      $query = $this->getMockBuilder(stdClass::class)->addMethods(['count'])->getMock();
      $query->method('count')->willReturn($count);
      $database = $this->getMockBuilder(stdClass::class)->addMethods(['delete'])->getMock();
      $database->expects($this->once())->method('delete')
        ->with('sessions', $this->callback(static function ($conditions) {
          $cutoff = $conditions['last_activity <'] ?? NULL;
          return is_int($cutoff) && abs($cutoff - (time() - 3600)) <= 1;
        }))->willReturn($query);

      $reflection = new ReflectionClass(Session_Database_Driver::class);
      $driver = $reflection->newInstanceWithoutConstructor();
      $reflection->getProperty('db')->setValue($driver, $database);
      $this->assertSame($count, $driver->gc(3600));
    }
    finally {
      $configuration->setValue(NULL, $originalConfiguration);
    }
  }

  public static function deletionCounts(): array {
    return [[0], [3]];
  }

  /**
   * @dataProvider driverClasses
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testPhpSessionLifecycle(string $class) {
    ini_set('session.use_cookies', '0');
    ini_set('session.cache_limiter', '');
    ini_set('session.gc_probability', '0');
    $stored = [];
    $driver = $this->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->onlyMethods(['open', 'read', 'write', 'destroy', 'gc'])
      ->getMock();
    $driver->method('open')->willReturn(TRUE);
    $driver->method('read')->willReturnCallback(function ($id) use (&$stored) {
      return $stored[$id] ?? '';
    });
    $driver->method('write')->willReturnCallback(function ($id, $data) use (&$stored) {
      $stored[$id] = $data;
      return TRUE;
    });
    $driver->method('destroy')->willReturnCallback(function ($id) use (&$stored) {
      unset($stored[$id]);
      return TRUE;
    });
    $driver->expects($this->once())->method('gc')
      ->with((int) ini_get('session.gc_maxlifetime'))->willReturn(0);

    $this->assertTrue(session_set_save_handler($driver, FALSE));
    session_id('warehouse-driver-test');
    try {
      $this->assertTrue(session_start());
      $_SESSION['key'] = 'value';
      $this->assertTrue(session_write_close());
      $this->assertArrayHasKey('warehouse-driver-test', $stored);

      $this->assertTrue(session_start());
      $this->assertSame('value', $_SESSION['key']);
      $this->assertSame(0, session_gc());
      $this->assertTrue(session_destroy());
      $this->assertSame([], $stored);
    }
    finally {
      if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
      }
    }
  }

}
