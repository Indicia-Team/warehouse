<?php
/**
 * Database Result Library Unit Tests
 *
 * @package     Core
 * @subpackage  Libraries
 * @author      Chris Bandy
 * @group   core
 * @group   core.libraries
 * @group   core.libraries.database
 * @group   core.libraries.database.result
 */
class Library_Database_Result_Test extends PHPUnit_Framework_TestCase
{
	protected $db;

	protected function setUp()
	{
		$this->db = Database::instance('phpunit');
		$this->db->connect();
	}

	protected function tearDown()
	{
		$this->db = NULL;
	}

	public function test_count()
	{
		$result = $this->db->query('SELECT 1 UNION SELECT 2 UNION SELECT 3');

		$this->assertSame(3, $result->count());
	}

	/**
	 * @group core.libraries.database.result.array_access
	 */
	public function test_offset_exists()
	{
		$result = $this->db->query('SELECT 1 UNION SELECT 2 UNION SELECT 3');

		$this->assertTrue($result->offsetExists(0));
		$this->assertTrue($result->offsetExists(2));

		$this->assertFalse($result->offsetExists(-1));
		$this->assertFalse($result->offsetExists(3));
	}

	/**
	 * @group core.libraries.database.result.array_access
	 */
	public function test_offset_get()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2 UNION SELECT 3')->as_array();

		$this->assertEquals(array('value' => 1), $result->offsetGet(0));
		$this->assertEquals(array('value' => 3), $result->offsetGet(2));

		$this->assertNull($result->offsetGet(-1));
		$this->assertNull($result->offsetGet(3));
	}

	/**
	 * @group core.libraries.database.result.array_access
	 *
	 * @expectedException Kohana_Exception
	 */
	public function test_offset_set()
	{
		$result = $this->db->query('SELECT 1');

		$result->offsetSet(0, TRUE);
	}

	/**
	 * @group core.libraries.database.result.array_access
	 *
	 * @expectedException Kohana_Exception
	 */
	public function test_offset_unset()
	{
		$result = $this->db->query('SELECT 1');

		$result->offsetUnset(0);
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_current()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2')->as_array();

		$this->assertEquals(array('value' => 1), $result->current());

		// Repeated calls should not advance, see #1817
		$this->assertEquals(array('value' => 1), $result->current());
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_next()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2')->as_array();

		$result->next();

		$this->assertSame(1, $result->key());
		$this->assertEquals(array('value' => 2), $result->current());

		$result->next();

		$this->assertFalse($result->valid());
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_prev()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2')->as_array();

		$result->seek(1);

		$result->prev();

		$this->assertEquals(array('value' => 1), $result->current());
		$this->assertSame(0, $result->key());

		$result->prev();

		$this->assertFalse($result->valid());
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_rewind()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2')->as_array();

		$result->next();

		$result->rewind();

		$this->assertEquals(array('value' => 1), $result->current());
		$this->assertSame(0, $result->key());
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_seek()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2 UNION SELECT 3')->as_array();

		$result->seek(2);

		$this->assertEquals(array('value' => 3), $result->current());
		$this->assertSame(2, $result->key());

		$result->seek(0);

		$this->assertEquals(array('value' => 1), $result->current());
		$this->assertSame(0, $result->key());

		$this->assertFalse($result->seek(-1));
		$this->assertFalse($result->seek(3));
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_iteration()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2 UNION SELECT 3')->as_array();

		$result->rewind();

		$this->assertTrue($result->valid());
		$this->assertEquals(array('value' => 1), $result->current());
		$this->assertSame(0, $result->key());

		$result->next();

		$this->assertTrue($result->valid());
		$this->assertEquals(array('value' => 2), $result->current());
		$this->assertSame(1, $result->key());

		$result->next();

		$this->assertTrue($result->valid());
		$this->assertEquals(array('value' => 3), $result->current());
		$this->assertSame(2, $result->key());

		$result->next();

		$this->assertFalse($result->valid());
	}

	public function test_get()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2');

		$this->assertEquals(1, $result->get('value'));
		$this->assertEquals(1, $result->get('value'));
	}

	public function test_array()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2')->as_array(TRUE);

		$this->assertEquals(array(array('value' => 1), array('value' => 2)), $result);
	}

	public function test_object()
	{
		$result = $this->db->query('SELECT 1 AS value')->as_object();

		$row = $result->current();

		$this->assertObjectHasAttribute('value', $row);
		$this->assertEquals(1, $row->value);
	}

	public function test_object_array()
	{
		$result = $this->db->query('SELECT 1 AS value')->as_object(NULL, TRUE);

		$this->assertEquals(array( (object) array('value' => 1)), $result);
	}

	public function test_class()
	{
		$result = $this->db->query('SELECT 1 AS value')->as_object('Library_Database_Result_Test_Class');

		$row = $result->current();

		$this->assertTrue($row instanceof Library_Database_Result_Test_Class);
		$this->assertObjectHasAttribute('value', $row);
		$this->assertEquals(1, $row->value);
	}

	public function test_class_array()
	{
		$result = $this->db->query('SELECT 1 AS value')->as_object('Library_Database_Result_Test_Class', TRUE);

		$obj = new Library_Database_Result_Test_Class;
		$obj->value = 1;

		$this->assertEquals(array($obj), $result);
	}
}


/**
 * Used to test object fetching
 */
final class Library_Database_Result_Test_Class {}
