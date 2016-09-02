<?php
/**
 * Request Helper Unit Tests
 *
 * @package     Core
 * @subpackage  Helpers
 * @author      Chris Bandy
 * @group   core
 * @group   core.helpers
 * @group   core.helpers.request
 */
class Helper_Request_Test extends PHPUnit_Framework_TestCase
{
	protected $server_vars;

	protected function setUp()
	{
		// Save $_SERVER
		$this->server_vars = $_SERVER;
	}

	protected function tearDown()
	{
		// Restore $_SERVER
		$_SERVER = $this->server_vars;

		Helper_Request_Test_Wrapper::reset();
	}

	public function accepts_provider()
	{
		return array(
			array(NULL, NULL, array('*' => array('*' => 1))),
			array(NULL, 'text/plain', TRUE),

			array('', NULL, array('*' => array('*' => 1))),

			array('text/plain', NULL, array('text' => array('plain' => 1))),
			array('text/plain', 'text/plain', TRUE),
			array('text/plain', 'text/html', FALSE),

			array('text/*, text/html', NULL, array('text' => array('*' => 1, 'html' => 1))),
			array('text/*, text/html', 'text/plain', TRUE),

			array('text/plain, text/html;q=0', NULL, array('text' => array('plain' => 1, 'html' => 0))),
			array('text/plain, text/html;q=0', 'text/html', FALSE),

			array('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', NULL,
				array(
					'text' => array('html' => 1),
					'application' => array('xhtml+xml' => 1, 'xml' => 0.9),
					'*' => array('*' => 0.8),
				)
			),
			array('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'application/xml', TRUE),
		);
	}

	/**
	 * @dataProvider accepts_provider
	 * @test
	 */
	public function accepts($accept_header, $arg, $expected)
	{
		$_SERVER['HTTP_ACCEPT'] = $accept_header;

		$this->assertEquals($expected, request::accepts($arg));
	}

	public function preferred_accept_provider()
	{
		return array(
			array(NULL, array(), FALSE, FALSE),
			array(NULL, array(), TRUE, FALSE),
			array(NULL, array('text/plain'), FALSE, 'text/plain'),
			array(NULL, array('text/plain'), TRUE, FALSE),

			array('text/plain', array(), FALSE, FALSE),
			array('text/plain', array(), TRUE, FALSE),
			array('text/plain', array('text/plain'), FALSE, 'text/plain'),
			array('text/plain', array('text/plain'), TRUE, 'text/plain'),
			array('text/plain', array('text/html'), FALSE, FALSE),
			array('text/plain', array('text/html'), TRUE, FALSE),

			array('text/*, text/html', array('text/plain'), FALSE, 'text/plain'),
			array('text/*, text/html', array('text/plain'), TRUE, FALSE),
			array('text/*, text/html', array('text/html'), FALSE, 'text/html'),
			array('text/*, text/html', array('text/html'), TRUE, 'text/html'),
			array('text/*, text/html', array('text/plain', 'text/html'), FALSE, 'text/plain'),
			array('text/*, text/html', array('text/plain', 'text/html'), TRUE, 'text/html'),
			array('text/*, text/html', array('text/html', 'text/plain'), FALSE, 'text/html'),
			array('text/*, text/html', array('text/html', 'text/plain'), TRUE, 'text/html'),

			array('text/plain, text/html;q=0', array('text/html'), FALSE, FALSE),
			array('text/plain, text/html;q=0', array('text/html'), TRUE, FALSE),

			array('text/html,application/xml;q=0.9,*/*;q=0.8', array('text/plain', 'text/html', 'application/xml'), FALSE, 'text/html'),
			array('text/html,application/xml;q=0.9,*/*;q=0.8', array('text/plain', 'text/html', 'application/xml'), TRUE, 'text/html'),
			array('text/html,application/xml;q=0.9,*/*;q=0.8', array('text/plain', 'application/xml'), FALSE, 'application/xml'),
			array('text/html,application/xml;q=0.9,*/*;q=0.8', array('text/plain', 'application/xml'), TRUE, 'application/xml'),
			array('text/html,application/xml;q=0.9,*/*;q=0.8', array('text/plain'), FALSE, 'text/plain'),
			array('text/html,application/xml;q=0.9,*/*;q=0.8', array('text/plain'), TRUE, FALSE),
		);
	}

	/**
	 * @dataProvider preferred_accept_provider
	 * @test
	 */
	public function preferred_accept($accept_header, $types, $explicit, $expected)
	{
		$_SERVER['HTTP_ACCEPT'] = $accept_header;

		$this->assertEquals($expected, request::preferred_accept($types, $explicit));
	}
}

class Helper_Request_Test_Wrapper extends request
{
	public static function reset()
	{
		request::$accept_types = NULL;
	}
}
