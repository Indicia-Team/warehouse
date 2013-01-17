<?php
/**
 * Database Library Unit Tests
 *
 * @package     Core
 * @subpackage  Libraries
 * @author      Chris Bandy
 * @group   core
 * @group   core.libraries
 * @group   core.libraries.database
 */
class Library_Database_Test extends PHPUnit_Framework_TestCase
{

	public function parse_dsn_provider()
	{
		return array(
			array('type:///database', array(
				'type' => 'type',
				'user' => FALSE,
				'pass' => FALSE,
				'host' => FALSE,
				'port' => FALSE,
				'socket' => FALSE,
				'database' => 'database',
			)),

			array('type://hostname', array(
				'type' => 'type',
				'user' => FALSE,
				'pass' => FALSE,
				'host' => 'hostname',
				'port' => FALSE,
				'socket' => FALSE,
				'database' => FALSE,
			)),

			array('type://hostname:12345', array(
				'type' => 'type',
				'user' => FALSE,
				'pass' => FALSE,
				'host' => 'hostname',
				'port' => 12345,
				'socket' => FALSE,
				'database' => FALSE,
			)),

			array('type://hostname/database', array(
				'type' => 'type',
				'user' => FALSE,
				'pass' => FALSE,
				'host' => 'hostname',
				'port' => FALSE,
				'socket' => FALSE,
				'database' => 'database',
			)),

			array('type://hostname:12345/database', array(
				'type' => 'type',
				'user' => FALSE,
				'pass' => FALSE,
				'host' => 'hostname',
				'port' => 12345,
				'socket' => FALSE,
				'database' => 'database',
			)),

			array('type://username@hostname', array(
				'type' => 'type',
				'user' => 'username',
				'pass' => FALSE,
				'host' => 'hostname',
				'port' => FALSE,
				'socket' => FALSE,
				'database' => FALSE,
			)),

			array('type://username@hostname:12345', array(
				'type' => 'type',
				'user' => 'username',
				'pass' => FALSE,
				'host' => 'hostname',
				'port' => 12345,
				'socket' => FALSE,
				'database' => FALSE,
			)),

			array('type://username:password@hostname', array(
				'type' => 'type',
				'user' => 'username',
				'pass' => 'password',
				'host' => 'hostname',
				'port' => FALSE,
				'socket' => FALSE,
				'database' => FALSE,
			)),

			array('type://username:password@hostname:12345', array(
				'type' => 'type',
				'user' => 'username',
				'pass' => 'password',
				'host' => 'hostname',
				'port' => 12345,
				'socket' => FALSE,
				'database' => FALSE,
			)),

			array('type://username:password@hostname/database', array(
				'type' => 'type',
				'user' => 'username',
				'pass' => 'password',
				'host' => 'hostname',
				'port' => FALSE,
				'socket' => FALSE,
				'database' => 'database',
			)),

			array('type://username:password@hostname:12345/database', array(
				'type' => 'type',
				'user' => 'username',
				'pass' => 'password',
				'host' => 'hostname',
				'port' => 12345,
				'socket' => FALSE,
				'database' => 'database',
			)),

			array('type://unix(/path/to/socket)', array(
				'type' => 'type',
				'user' => FALSE,
				'pass' => FALSE,
				'host' => FALSE,
				'port' => FALSE,
				'socket' => '/path/to/socket',
				'database' => FALSE,
			)),

			array('type://unix(/path/to/socket)/database', array(
				'type' => 'type',
				'user' => FALSE,
				'pass' => FALSE,
				'host' => FALSE,
				'port' => FALSE,
				'socket' => '/path/to/socket',
				'database' => 'database',
			)),

			array('type://username@unix(/path/to/socket)', array(
				'type' => 'type',
				'user' => 'username',
				'pass' => FALSE,
				'host' => FALSE,
				'port' => FALSE,
				'socket' => '/path/to/socket',
				'database' => FALSE,
			)),

			array('type://username:password@unix(/path/to/socket)', array(
				'type' => 'type',
				'user' => 'username',
				'pass' => 'password',
				'host' => FALSE,
				'port' => FALSE,
				'socket' => '/path/to/socket',
				'database' => FALSE,
			)),

			array('type://username:password@unix(/path/to/socket)/database', array(
				'type' => 'type',
				'user' => 'username',
				'pass' => 'password',
				'host' => FALSE,
				'port' => FALSE,
				'socket' => '/path/to/socket',
				'database' => 'database',
			)),
		);
	}

	/**
	 * @dataProvider parse_dsn_provider
	 * @test
	 */
	public function parse_dsn($dsn, $expected)
	{
		$result = Database::parse_dsn($dsn);
		$this->assertEquals($expected, $result);
	}
}
