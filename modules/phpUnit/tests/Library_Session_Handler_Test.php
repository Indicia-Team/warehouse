<?php

use PHPUnit\Framework\TestCase;

if (!defined('SYSPATH')) {
  define('SYSPATH', __DIR__ . '/../../../system/');
}

require_once __DIR__ . '/../../../system/libraries/drivers/Session.php';
require_once __DIR__ . '/../../../system/libraries/Session_Handler.php';

/**
 * Tests compatibility between Kohana drivers and PHP session handling.
 */
class Library_Session_Handler_Test extends TestCase {

  public function testDelegatesSessionCallbacks() {
    $driver = $this->createMock(Session_Driver::class);
    $driver->expects($this->once())->method('open')
      ->with('/sessions', 'warehouse')->willReturn(TRUE);
    $driver->expects($this->once())->method('close')->willReturn(TRUE);
    $driver->expects($this->once())->method('read')
      ->with('session-id')->willReturn('key|s:5:"value";');
    $driver->expects($this->once())->method('write')
      ->with('session-id', 'key|s:5:"value";')->willReturn(TRUE);
    $driver->expects($this->once())->method('destroy')
      ->with('session-id')->willReturn(TRUE);

    $handler = new Session_Handler($driver);
    $this->assertInstanceOf(SessionHandlerInterface::class, $handler);
    $this->assertTrue($handler->open('/sessions', 'warehouse'));
    $this->assertSame('key|s:5:"value";', $handler->read('session-id'));
    $this->assertTrue($handler->write('session-id', 'key|s:5:"value";'));
    $this->assertTrue($handler->destroy('session-id'));
    $this->assertTrue($handler->close());
  }

  public function testPreservesFailuresAndEmptySessions() {
    $driver = $this->createMock(Session_Driver::class);
    foreach (['open', 'close', 'write', 'destroy'] as $method) {
      $driver->method($method)->willReturn(FALSE);
    }
    $driver->method('read')->willReturnOnConsecutiveCalls('', FALSE);

    $handler = new Session_Handler($driver);
    $this->assertFalse($handler->open('/sessions', 'warehouse'));
    $this->assertSame('', $handler->read('missing'));
    $this->assertFalse($handler->read('failed'));
    $this->assertFalse($handler->write('session-id', 'data'));
    $this->assertFalse($handler->destroy('session-id'));
    $this->assertFalse($handler->close());
  }

  /**
   * @dataProvider garbageCollectionResults
   */
  public function testGarbageCollectionResults($driverResult, $expectedResult) {
    $driver = $this->createMock(Session_Driver::class);
    $driver->expects($this->once())->method('gc')
      ->with(3600)->willReturn($driverResult);

    $handler = new Session_Handler($driver);
    $this->assertSame($expectedResult, $handler->gc(3600));
  }

  public static function garbageCollectionResults(): array {
    return [
      'legacy success' => [TRUE, 0],
      'failure' => [FALSE, FALSE],
      'no deletions' => [0, 0],
      'deletion count' => [3, 3],
    ];
  }

  public function testDriverExceptionsPropagate() {
    $driver = $this->createMock(Session_Driver::class);
    $driver->method('read')->willThrowException(new RuntimeException('Storage unavailable'));

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Storage unavailable');
    (new Session_Handler($driver))->read('session-id');
  }

  /**
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testPhpSessionLifecycle() {
    ini_set('session.use_cookies', '0');
    ini_set('session.cache_limiter', '');
    ini_set('session.gc_probability', '0');
    $stored = [];
    $driver = $this->createMock(Session_Driver::class);
    $driver->method('open')->willReturn(TRUE);
    $driver->method('close')->willReturn(TRUE);
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
      ->with((int) ini_get('session.gc_maxlifetime'))->willReturn(TRUE);

    $this->assertTrue(session_set_save_handler(new Session_Handler($driver), FALSE));
    session_id('warehouse-handler-test');
    try {
      $this->assertTrue(session_start());
      $_SESSION['key'] = 'value';
      $this->assertTrue(session_write_close());
      $this->assertArrayHasKey('warehouse-handler-test', $stored);

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
