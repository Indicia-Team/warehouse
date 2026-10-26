<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests cache driver signatures and public API defaults without backend services.
 */
class Library_Cache_Signature_Test extends TestCase {

  public static function setUpBeforeClass(): void {
    if (!defined('SYSPATH')) {
      define('SYSPATH', __DIR__ . '/../../../system/');
    }
    $errorReporting = error_reporting(E_ALL);
    set_error_handler(static function ($severity, $message, $file, $line) {
      throw new ErrorException($message, 0, $severity, $file, $line);
    });
    try {
      require_once SYSPATH . 'libraries/drivers/Cache.php';
      require_once SYSPATH . 'libraries/Cache.php';
      foreach (['Apc', 'Eaccelerator', 'File', 'Memcache', 'Sqlite', 'Xcache'] as $driver) {
        require_once SYSPATH . 'libraries/drivers/Cache/' . $driver . '.php';
      }
    }
    finally {
      restore_error_handler();
      error_reporting($errorReporting);
    }
  }

  /**
   * @dataProvider driverClasses
   */
  public function testDriverSignature(string $class) {
    $method = new ReflectionMethod($class, 'set');
    $parameters = $method->getParameters();
    $this->assertSame(4, $method->getNumberOfParameters());
    $this->assertSame(4, $method->getNumberOfRequiredParameters());
    $this->assertSame('tags', $parameters[2]->getName());
    $this->assertSame('array', $parameters[2]->getType()->getName());
    $this->assertTrue($parameters[2]->allowsNull());
    $this->assertFalse($parameters[2]->isDefaultValueAvailable());
    $this->assertSame('lifetime', $parameters[3]->getName());
    $this->assertFalse($parameters[3]->isDefaultValueAvailable());
  }

  public static function driverClasses(): array {
    return [
      [Cache_Driver::class],
      [Cache_Apc_Driver::class],
      [Cache_Eaccelerator_Driver::class],
      [Cache_File_Driver::class],
      [Cache_Memcache_Driver::class],
      [Cache_Sqlite_Driver::class],
      [Cache_Xcache_Driver::class],
    ];
  }

  /**
   * @dataProvider publicSetArguments
   */
  public function testPublicApiDefaults(array $arguments, array $tags, int $lifetime) {
    $driver = $this->createMock(Cache_Driver::class);
    $driver->expects($this->once())->method('set')
      ->with('item', 'value', $tags, $lifetime)->willReturn(TRUE);

    $reflection = new ReflectionClass(Cache_Core::class);
    $cache = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('driver')->setValue($cache, $driver);
    $reflection->getProperty('config')->setValue($cache, ['lifetime' => 3600]);

    $this->assertTrue($cache->set(...$arguments));
  }

  public static function publicSetArguments(): array {
    return [
      'omitted tags and lifetime' => [['item', 'value'], [], 3600],
      'omitted lifetime' => [['item', 'value', ['tag']], ['tag'], 3600],
      'explicit nulls' => [['item', 'value', NULL, NULL], [], 3600],
      'string tag' => [['item', 'value', 'tag', 60], ['tag'], 60],
      'non-expiring' => [['item', 'value', NULL, 0], [], 0],
    ];
  }

  /**
   * @dataProvider fileSetArguments
   */
  public function testFileDriverNullableTagsAndLifetime(?array $tags, int $lifetime) {
    $directory = sys_get_temp_dir() . '/warehouse-cache-test-' . bin2hex(random_bytes(8));
    $this->assertTrue(mkdir($directory));
    try {
      $driver = new Cache_File_Driver($directory);
      $before = time();
      $this->assertTrue($driver->set('item', 'value', $tags, $lifetime));
      $this->assertSame('value', $driver->get('item'));

      $files = $driver->exists('item');
      $this->assertCount(1, $files);
      [$id, $storedTags, $expiration] = explode('~', basename($files[0]));
      $this->assertSame('item', $id);
      $this->assertSame(implode('+', $tags ?? []), $storedTags);
      if ($lifetime === 0) {
        $this->assertSame('0', $expiration);
      }
      else {
        $this->assertGreaterThanOrEqual($before + $lifetime, (int) $expiration);
        $this->assertLessThanOrEqual(time() + $lifetime, (int) $expiration);
      }
      if (!empty($tags)) {
        $this->assertSame(['item' => 'value'], $driver->find($tags[0]));
      }
    }
    finally {
      foreach (glob($directory . '/*') as $file) {
        unlink($file);
      }
      rmdir($directory);
    }
  }

  public static function fileSetArguments(): array {
    return [
      'null tags, no expiration' => [NULL, 0],
      'empty tags, no expiration' => [[], 0],
      'tagged, no expiration' => [['tag'], 0],
      'null tags, expiring' => [NULL, 60],
      'empty tags, expiring' => [[], 60],
      'tagged, expiring' => [['tag'], 60],
    ];
  }

}
