<?php
/**
 * UTF8 Helper Unit Tests
 *
 * @package  Core
 * @group    core
 * @group    core.helpers
 * @group    core.helpers.utf8
 */
class Helper_UTF8_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * DataProvider for the utf8::substr_replace() test
	 */
	public function substr_replace_provider()
	{
		return array(
				// ascii tests
				array('The rain in Spain falls mainly in the plain.', 'France', 12, 5, 'The rain in France falls mainly in the plain.'),

				// utf8 tests
				array('Here are some random utf8 chars: Å‘Å±.', 'test', 0, NULL, 'test'),
				array('Here are some random utf8 chars: Å‘Å±.', 'ăĂЙ', -5, 4, 'Here are some random utf8 chars: ăĂЙ.'),
				array('Please send your Résumé to: Box 1234....', 'CV', 17, 6, 'Please send your CV to: Box 1234....'),
			);
	}

	/**
	 * Tests the tutf8::substr_replace() function.
	 * @dataProvider substr_replace_provider
	 * @group core.helpers.utf8.substr_replace
	 * @test
	 */
	public function substr_replace($str, $replacement, $offset, $length, $expected_result)
	{
		$result = utf8::substr_replace($str, $replacement, $offset, $length);
		$this->assertEquals($expected_result, $result);
	}

	/**
	 * DataProvider for the utf8::ucfirst() test
	 */
	public function ucfirst_provider()
	{
		return array(
				// ascii tests
				array('hello world!', 'Hello world!'),
				array('', ''),
				array('gwen', 'Gwen'),

				// utf8 tests
				array('résumé', 'Résumé'),
				array('áaaa test', 'Áaaa test'),
				array('Ã foo bar', 'Ã foo bar'),
			);
	}

	/**
	 * Tests the tutf8::ucfirst() function.
	 * @dataProvider ucfirst_provider
	 * @group core.helpers.utf8.ucfirst
	 * @test
	 */
	public function ucfirst($str, $expected_result)
	{
		$result = utf8::ucfirst($str);
		$this->assertEquals($expected_result, $result);
	}

	/**
	 * DataProvider for the utf8::strcasecmp() test
	 */
	public function strcasecmp_provider()
	{
		return array(
				// Equal
				array('hello world!', 'Hello world!', 0),
				array('', '', 0),
				array('áaaa test', 'Áaaa test', 0),
				array('résumé', 'Résumé', 0),

				// Not Equal
				array('Your resume', 'Resume', 7),
				array('Your résumé', 'résumé', 1),
				array('áaaa test', 'Áaaa', 5),
				array('a', 'b', -1),
			);
	}

	/**
	 * Tests the tutf8::strcasecmp() function.
	 * @dataProvider strcasecmp_provider
	 * @group core.helpers.utf8.strcasecmp
	 * @test
	 */
	public function strcasecmp($str, $str2, $expected_result)
	{
		$result = utf8::strcasecmp($str, $str2);
		$this->assertEquals($expected_result, $result);
	}

	/**
	 * DataProvider for the utf8::str_pad() test
	 */
	public function str_pad_provider()
	{
		return array(
				array('Hello', 6, ' ', STR_PAD_RIGHT, 'Hello '),
				array('This is a test©©', 4, ' ', STR_PAD_RIGHT, 'This is a test©©'),
				array('Hellœ wœrld', 12, '!', STR_PAD_RIGHT, 'Hellœ wœrld!'),
				array('Hellœ wœrld', 12, 'œ', STR_PAD_RIGHT, 'Hellœ wœrldœ'),
				array('Hellœ wœrld', 12, 'œ', STR_PAD_LEFT, 'œHellœ wœrld'),
				array('Hellœ wœrld', 14, 'œ', STR_PAD_BOTH, 'œHellœ wœrldœ'),
				// This one should trigger an error
				array('Hellœ wœrld', 14, 'œ', -1, 'test'),
			);
	}

	/**
	 * Tests the utf8::str_pad() function.
	 * @dataProvider str_pad_provider
	 * @group core.helpers.utf8.str_pad
	 * @test
	 */
	public function str_pad($str, $final_str_length, $pad_str, $pad_type, $expected_result)
	{
		if ($pad_type === -1)
		{
			$this->setExpectedException('PHPUnit_Framework_Error');
			$result = utf8::str_pad($str, $final_str_length, $pad_str, $pad_type);
		}
		else
		{
			$result = utf8::str_pad($str, $final_str_length, $pad_str, $pad_type);
			$this->assertEquals($expected_result, $result);
		}
	}

	/**
	 * DataProvider for the utf8::str_split() test
	 */
	public function str_split_provider()
	{
		return array(
				array('This is A test', 1, array('T', 'h', 'i', 's', ' ', 'i', 's', ' ', 'A', ' ', 't', 'e', 's', 't')),
				array('©©©This is a test', 4, array('©©©T', 'his ', 'is a', ' tes', 't')),
				array('Hellœ wœrldœœœ', 3, array('Hel', 'lœ ', 'wœr', 'ldœ', 'œœ')),
				array('Hello worldœœœ', 50, array('Hello worldœœœ')),
				array('Hello worldœœœ', 0, FALSE),
			);
	}

	/**
	 * Tests the utf8::str_split() function.
	 * @dataProvider str_split_provider
	 * @group core.helpers.utf8.str_split
	 * @test
	 */
	public function str_split($str, $split_length, $expected_result)
	{
		$result = utf8::str_split($str, $split_length);
		$this->assertEquals($expected_result, $result);
	}

	/**
	 * DataProvider for the utf8::strrev() test
	 */
	public function strrev_provider()
	{
		return array(
				array('AAAThis is A test', 'tset A si sihTAAA'),
				array('©©©This is a test', 'tset a si sihT©©©'),
				array('Hellœ wœrldœœœ', 'œœœdlrœw œlleH'),
				array('Hello world', 'dlrow olleH'),
				array('     ', '     '),
			);
	}

	/**
	 * Tests the utf8::strrev() function.
	 * @dataProvider strrev_provider
	 * @group core.helpers.utf8.strrev
	 * @test
	 */
	public function strrev($str, $expected_result)
	{
		$result = utf8::strrev($str);
		$this->assertEquals($expected_result, $result);
	}

	/**
	 * DataProvider for the utf8::trim() test
	 */
	public function trim_provider()
	{
		return array(
				array('AAAThis is A testAAAA', 'A', 'This is A test'),
				array('©©©This is a test', '©', 'This is a test'),
				array('Hellœ wœrldœœœ', 'œ', 'Hellœ wœrld'),
				array('   Hello world  ', NULL, 'Hello world'),
				array('+++Hello world++++', '+', 'Hello world'),
				array('     ', NULL, ''),
			);
	}

	/**
	 * Tests the utf8::trim() function.
	 * @dataProvider trim_provider
	 * @group core.helpers.utf8.trim
	 * @test
	 */
	public function trim($str, $charlist, $expected_result)
	{
		$result = utf8::trim($str, $charlist);
		$this->assertEquals($expected_result, $result);
	}

	/**
	 * DataProvider for the utf8::ltrim() test
	 */
	public function ltrim_provider()
	{
		return array(
				array('AAAThis is A testAAAA', 'A', 'This is A testAAAA'),
				array('©©©This is a test', '©', 'This is a test'),
				array('Hellœ wœrldœœœ', 'œ', 'Hellœ wœrldœœœ'),
				array('   Hello world  ', NULL, 'Hello world  '),
				array('+++Hello world++++', '+', 'Hello world++++'),
				array('     ', NULL, ''),
			);
	}

	/**
	 * Tests the utf8::ltrim() function.
	 * @dataProvider ltrim_provider
	 * @group core.helpers.utf8.ltrim
	 * @test
	 */
	public function ltrim($str, $charlist, $expected_result)
	{
		$result = utf8::ltrim($str, $charlist);
		$this->assertEquals($expected_result, $result);
	}

	/**
	 * DataProvider for the utf8::rtrim() test
	 */
	public function rtrim_provider()
	{
		return array(
				array('AAAThis is A testAAAA', 'A', 'AAAThis is A test'),
				array('©©©This is a test', '©', '©©©This is a test'),
				array('Hellœ wœrldœœœ', 'œ', 'Hellœ wœrld'),
				array('   Hello world  ', NULL, '   Hello world'),
				array('+++Hello world++++', '+', '+++Hello world'),
				array('     ', NULL, ''),
			);
	}

	/**
	 * Tests the utf8::rtrim() function.
	 * @dataProvider rtrim_provider
	 * @group core.helpers.utf8.rtrim
	 * @test
	 */
	public function rtrim($str, $charlist, $expected_result)
	{
		$result = utf8::rtrim($str, $charlist);
		$this->assertEquals($expected_result, $result);
	}

	/**
	 * DataProvider for the utf8::ord() test
	 */
	public function ord_provider()
	{
		return array(
				array('A', 65),
				array('a', 97),
				array('©', 169),
				array('@', 64),
				array('℧', 8487),
				array('∲', 8754),
				array('', 0),
				array('œ', 339),
				array('Á', 193),
			);
	}

	/**
	 * Tests the tutf8::ord() function.
	 * @dataProvider ord_provider
	 * @group core.helpers.utf8.ord
	 * @test
	 */
	public function ord($str, $expected_result)
	{
		$result = utf8::ord($str);
		$this->assertEquals($expected_result, $result);
	}
}
