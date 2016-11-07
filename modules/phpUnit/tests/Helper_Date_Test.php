<?php
/**
 * Date Helper Unit Tests
 *
 * @package Core
 * @author  Chris Bandy
 * @group core
 * @group core.helpers
 * @group core.helpers.date
 */
class Helper_Date_Test extends PHPUnit_Framework_TestCase
{
	public function offset_provider()
	{
		return array(
			array('Europe/Berlin', 'Europe/Paris', 0),
			array('America/New_York', 'America/Los_Angeles', 10800),
			array('America/Los_Angeles', 'America/New_York', -10800),
		);
	}

	/**
	 * @dataProvider offset_provider
	 * @test
	 */
	public function offset($local, $remote, $expected)
	{
		$result = date::offset($local, $remote);
		$this->assertEquals($expected, $result);
	}
}
