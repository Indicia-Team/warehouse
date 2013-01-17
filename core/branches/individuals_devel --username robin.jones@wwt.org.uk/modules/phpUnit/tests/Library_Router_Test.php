<?php
/**
 * Router Library Unit Tests
 *
 * @package     Core
 * @subpackage  Libraries
 * @author      Chris Bandy
 * @group   core
 * @group   core.libraries
 * @group   core.libraries.router
 */
class Library_Router_Test extends PHPUnit_Framework_TestCase
{
	protected $backupGlobals = TRUE;

	protected $kohana_config = array();
	protected $kohana_server_api;
	protected $router_vars;

	protected function setUp()
	{
		// Save config
		$this->kohana_config['core.url_suffix'] = Kohana_Config::instance()->get('core.url_suffix');

		// Save Server API
		$this->kohana_server_api = Kohana::$server_api;

		// Save Router members
		$this->router_vars = array(
			'complete_uri'  => Router::$complete_uri,
			'controller'    => Router::$controller,
			'current_uri'   => Router::$current_uri,
			'query_string'  => Router::$query_string,
			'rsegments'     => Router::$rsegments,
			'routed_uri'    => Router::$routed_uri,
			'segments'      => Router::$segments,
			'url_suffix'    => Router::$url_suffix,
		);

		// Reset Router members
		Router::$complete_uri = '';
		Router::$controller   = NULL;
		Router::$current_uri  = '';
		Router::$query_string = '';
		Router::$rsegments    = NULL;
		Router::$routed_uri   = '';
		Router::$segments     = NULL;
		Router::$url_suffix   = '';
	}

	protected function tearDown()
	{
		// Restore config
		foreach ($this->kohana_config as $key => $value)
		{
			Kohana_Config::instance()->set($key, $value);
		}

		// Restore Server API
		Kohana::$server_api = $this->kohana_server_api;

		// Restore Router members
		foreach ($this->router_vars as $key => $value)
		{
			Router::$$key = $value;
		}
	}

	public function find_uri_cli_provider()
	{
		return array(
			array(array(KOHANA), ''),

			array(array(KOHANA, ''), ''),
			array(array(KOHANA, 'default'), 'default'),
			array(array(KOHANA, 'default/index'), 'default/index'),
			array(array(KOHANA, '//default////index///'), 'default/index'),

			array(array(KOHANA, 'default?'), 'default'),
			array(array(KOHANA, 'default?a=first&b=2nd'), 'default', array('a' => 'first', 'b' => '2nd')),

			// URL may contain KOHANA, see #1810
			array(array(KOHANA, '/default/index/'.KOHANA), 'default/index/'.KOHANA),
		);
	}

	/**
	 * @dataProvider find_uri_cli_provider
	 * @test
	 */
	public function find_uri_cli($argv, $current_uri, $get = array())
	{
		Kohana::$server_api = 'cli';

		$_SERVER['argc'] = count($argv);
		$_SERVER['argv'] = $argv;

		Router::find_uri();

		$this->assertEquals($current_uri, Router::$current_uri);
		$this->assertEquals($get, $_GET);
	}

	public function find_uri_apache_provider()
	{
		return array(

			// Apache 2.2

			array('/', '', array(
				'PHP_SELF' => '/'.KOHANA,
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('/'.KOHANA, '', array(
				'PHP_SELF' => '/'.KOHANA,
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('/default/index', 'default/index', array(
				'PATH_INFO' => '/default/index',
				'PHP_SELF' => '/'.KOHANA.'/default/index',
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('//default////index///', 'default/index', array(
				'PATH_INFO' => '/default/index/',
				'PHP_SELF' => '/'.KOHANA.'/default/index/',
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('/default/index', 'default/index', array(
				'PHP_SELF' => '/'.KOHANA,
				'QUERY_STRING' => 'kohana_uri=default/index',
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			// URI should be decoded, see #1712
			array('/default/index/%22%20onclick=%22alert()%22', 'default/index/" onclick="alert()"', array(
				'PATH_INFO' => '/default/index/" onclick="alert()"',
				'PHP_SELF' => '/'.KOHANA.'/default/index/" onclick="alert()"',
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			// URL may contain KOHANA, see #1810
			array('/default/index/'.KOHANA, 'default/index/'.KOHANA, array(
				'PATH_INFO' => '/default/index/'.KOHANA,
				'PHP_SELF' => '/'.KOHANA.'/default/index/'.KOHANA,
				'SCRIPT_NAME' => '/'.KOHANA,
			)),
		);
	}

	public function find_uri_lighty_provider()
	{
		return array(

			// lighttpd 1.4.20

			array('/', '', array(
				'PATH_INFO' => '',
				'PHP_SELF' => '/'.KOHANA,
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('/'.KOHANA, '', array(
				'PATH_INFO' => '',
				'PHP_SELF' => '/'.KOHANA,
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('/default/index', 'default/index', array(
				'PATH_INFO' => '/default/index',
				'PHP_SELF' => '/'.KOHANA.'/default/index',
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('//default////index///', 'default/index', array(
				'PATH_INFO' => '/default/index/',
				'PHP_SELF' => '/'.KOHANA.'/default/index/',
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('/default/index', 'default/index', array(
				'PHP_SELF' => '/'.KOHANA,
				'QUERY_STRING' => 'kohana_uri=default/index&',
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			// URI should be decoded, see #1712
			array('/default/index/%22%20onclick=%22alert()%22', 'default/index/" onclick="alert()"', array(
				'PATH_INFO' => '/default/index/" onclick="alert()"',
				'PHP_SELF' => '/'.KOHANA.'/default/index/" onclick="alert()"',
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			// URL may contain KOHANA, see #1810
			array('/default/index/'.KOHANA, 'default/index/'.KOHANA, array(
				'PATH_INFO' => '/default/index/'.KOHANA,
				'PHP_SELF' => '/'.KOHANA.'/default/index/'.KOHANA,
				'SCRIPT_NAME' => '/'.KOHANA,
			)),
		);
	}

	public function find_uri_iis_provider()
	{
		return array(

			// IIS 6.0

			array('/', '', array(
				'ORIG_PATH_INFO' => DOCROOT.'/'.KOHANA,
				'PHP_SELF' => DOCROOT.'/'.KOHANA,
				'SCRIPT_NAME' => DOCROOT.'/'.KOHANA,
			)),

			array('/'.KOHANA, '', array(
				'ORIG_PATH_INFO' => DOCROOT.'/'.KOHANA,
				'PHP_SELF' => DOCROOT.'/'.KOHANA,
				'SCRIPT_NAME' => DOCROOT.'/'.KOHANA,
			)),

			array('/default/index', 'default/index', array(
				'ORIG_PATH_INFO' => DOCROOT.'/'.KOHANA.'/default/index',
				'PATH_INFO' => '/default/index',
				'PHP_SELF' => DOCROOT.'/'.KOHANA.'/default/index',
				'SCRIPT_NAME' => DOCROOT.'/'.KOHANA,
			)),

			// URL may contain KOHANA, see #1810
			array('/default/index/'.KOHANA, 'default/index/'.KOHANA, array(
				'ORIG_PATH_INFO' => DOCROOT.'/'.KOHANA.'/default/index/'.KOHANA,
				'PATH_INFO' => '/default/index/'.KOHANA,
				'PHP_SELF' => DOCROOT.'/'.KOHANA.'/default/index/'.KOHANA,
				'SCRIPT_NAME' => DOCROOT.'/'.KOHANA,
			)),


			// IIS 6.0 with Ionics Isapi Rewrite Filter, see #1730

			array('/', '', array(
				'ORIG_PATH_INFO' => '/'.KOHANA,
				'PATH_INFO' => '',
				'PHP_SELF' => '/'.KOHANA,
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('/'.KOHANA, '', array(
				'ORIG_PATH_INFO' => '/'.KOHANA,
				'PATH_INFO' => '',
				'PHP_SELF' => '/'.KOHANA,
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('/default/index', 'default/index', array(
				'ORIG_PATH_INFO' => '/'.KOHANA.'/default/index',
				'PATH_INFO' => '/default/index',
				'PHP_SELF' => '/'.KOHANA,
				'SCRIPT_NAME' => '/'.KOHANA,
			)),

			array('/default/index/'.KOHANA, 'default/index/'.KOHANA, array(
				'ORIG_PATH_INFO' => '/'.KOHANA.'/default/index/'.KOHANA,
				'PATH_INFO' => '/default/index/'.KOHANA,
				'PHP_SELF' => '/'.KOHANA,
				'SCRIPT_NAME' => '/'.KOHANA,
			)),
		);
	}

	/**
	 * @dataProvider find_uri_apache_provider
	 * @dataProvider find_uri_lighty_provider
	 * @dataProvider find_uri_iis_provider
	 * @test
	 */
	public function find_uri_cgi($request_uri, $current_uri, $server_vars)
	{
		Kohana::$server_api = 'cgi-fcgi';

		parse_str(arr::get($server_vars, 'QUERY_STRING'), $_GET);

		$_SERVER = array_merge(
			$_SERVER,
			array(
				'ORIG_PATH_INFO' => NULL,
				'PATH_INFO' => NULL,
				'PHP_SELF' => NULL,
				'REQUEST_URI' => $request_uri,
				'QUERY_STRING' => NULL,
				'SCRIPT_NAME' => NULL,
			),
			$server_vars
		);

		Router::find_uri();

		$this->assertEquals($current_uri, Router::$current_uri);
	}

	/**
	 * @test
	 */
	public function find_uri_suffix()
	{
		Kohana::$server_api = 'cgi-fcgi';

		Kohana_Config::instance()->set('core.url_suffix', '.html');

		$_SERVER = array_merge(
			$_SERVER,
			array(
				'ORIG_PATH_INFO' => NULL,
				'PATH_INFO' => '/default/index.html',
				'PHP_SELF' => '/'.KOHANA.'/default/index.html',
				'REQUEST_URI' => '/default/index.html',
				'QUERY_STRING' => NULL,
				'SCRIPT_NAME' => '/'.KOHANA,
			)
		);

		Router::find_uri();

		$this->assertEquals('default/index', Router::$current_uri);
		$this->assertEquals('.html', Router::$url_suffix);
	}

	public function setup_test_provider()
	{
		return array(
			array(
				array('', ''),
				array('', '', array())
			),
			array(
				array('.', ''),
				array('.', '', array('.'))      // FIXME is this correct?
			),
			array(
				array('..', ''),
				array('..', '', array('..'))    // FIXME is this correct?
			),
			array(
				array('./..', ''),
				array('..', '', array('..'))    // FIXME is this correct?
			),
			array(
				array('../.', ''),
				array('.', '', array('.'))      // FIXME is this correct?
			),
			array(
				array('../..', ''),
				array('..', '', array('..'))    // FIXME is this correct?
			),
			array(
				array('./.././.././../..', ''),
				array('..', '', array('..'))    // FIXME is this correct?
			),
			array(
				array('./.. /. /. . .. ././../..', ''),
				array('..', '', array('..'))    // FIXME is this correct?
			),
			array(
				array('привет', ''),
				array('привет', '', array('привет'))
			),
			array(
				array('./../привет', ''),
				array('привет', '', array('привет'))
			),
			array(
				array('', 'key=value&'),
				array('', '?key=value', array())
			),

			array(
				array('привет/index/" onclick="alert()"', ''),
				array('привет/index/" onclick="alert()"', '', array('привет', 'index', '" onclick="alert()"'))
			),

			// see #1887
			// Apache 2.2, lighttpd 1.4.20
			array(
				array('', 'sample=%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82'),
				array('', '?sample=привет', array())
			),

			// see #2404
			array(
				array('', 'sample=%26%2f'),
				array('', '?sample=&/', array())
			),

		);
	}

	/**
	 * @dataProvider setup_test_provider
	 * @test
	 */
	public function setup_test($input, $expected)
	{
		list(Router::$current_uri, $_SERVER['QUERY_STRING']) = $input;

		if ($expected[0] === '')
		{
			// The default route should exist. No 404 will be thrown.
			Router::setup();
		}
		else
		{
			// Other tested URIs should not exist. A 404 must be thrown.
			try
			{
				Router::setup();

				$this->setExpectedException('Kohana_404_Exception');
			}
			catch (Kohana_404_Exception $e)
			{
				// Correct, do nothing
			}
			catch (Exception $e)
			{
				// Unexpected exception
				$this->setExpectedException('Kohana_404_Exception');
			}
		}

		$this->assertEquals($expected[0], Router::$current_uri);
		$this->assertEquals($expected[1], Router::$query_string);
		$this->assertEquals($expected[2], Router::$segments);

	}
}
