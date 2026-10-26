<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Session driver interface
 *
 * $Id: Session.php 3769 2008-12-15 00:48:56Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
interface Session_Driver {

	/**
	 * Opens a session.
	 *
	 * @param   string   save path
	 * @param   string   session name
	 * @return  boolean
	 */
  public function open($path, $name): bool;

	/**
	 * Closes a session.
	 *
	 * @return  boolean
	 */
  public function close(): bool;

	/**
	 * Reads a session.
	 *
	 * @param   string  session id
   * @return  string|false
	 */
  public function read($id): string|false;

	/**
	 * Writes a session.
	 *
	 * @param   string   session id
	 * @param   string   session data
	 * @return  boolean
	 */
  public function write($id, $data): bool;

	/**
	 * Destroys a session.
	 *
	 * @param   string   session id
	 * @return  boolean
	 */
  public function destroy($id): bool;

	/**
	 * Regenerates the session id.
	 *
	 * @return  string
	 */
  public function regenerate(): string;

	/**
	 * Garbage collection.
	 *
	 * @param   integer  session expiration period
   * @return  integer|false  number of deleted sessions, or false on failure
	 */
  public function gc($maxlifetime): int|false;

} // End Session Driver Interface