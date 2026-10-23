<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Adapts Kohana session drivers to PHP's session handler interface.
 */
class Session_Handler implements SessionHandlerInterface {

  protected Session_Driver $driver;

  public function __construct(Session_Driver $driver) {
    $this->driver = $driver;
  }

  public function open(string $path, string $name): bool {
    return $this->driver->open($path, $name);
  }

  public function close(): bool {
    return $this->driver->close();
  }

  public function read(string $id): string|false {
    return $this->driver->read($id);
  }

  public function write(string $id, string $data): bool {
    return $this->driver->write($id, $data);
  }

  public function destroy(string $id): bool {
    return $this->driver->destroy($id);
  }

  public function gc(int $maxlifetime): int|false {
    $result = $this->driver->gc($maxlifetime);

    // Legacy drivers report success without a deletion count.
    return $result === TRUE ? 0 : $result;
  }

}
