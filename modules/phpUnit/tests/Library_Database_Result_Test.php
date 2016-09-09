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
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2 UNION SELECT 3');
    
		$this->assertEquals((object) array('value' => 1), $result->offsetGet(0));
		$this->assertEquals((object) array('value' => 3), $result->offsetGet(2));

		$this->assertFalse($result->offsetGet(-1));
		$this->assertFalse($result->offsetGet(3));
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
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2');

		$this->assertEquals((object) array('value' => 1), $result->current());

		// Repeated calls should not advance, see #1817
		$this->assertEquals((object) array('value' => 1), $result->current());
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_next()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2');

		$result->next();

		$this->assertSame(1, $result->key());
		$this->assertEquals((object) array('value' => 2), $result->current());

		$result->next();

		$this->assertFalse($result->valid());
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_prev()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2');

		$result->seek(1);

		$result->prev();

		$this->assertEquals((object) array('value' => 1), $result->current());
		$this->assertSame(0, $result->key());

		$result->prev();

		$this->assertFalse($result->valid());
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_rewind()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2');

		$result->next();

		$result->rewind();

		$this->assertEquals((object) array('value' => 1), $result->current());
		$this->assertSame(0, $result->key());
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_seek()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2 UNION SELECT 3');

		$result->seek(2);

		$this->assertEquals((object) array('value' => 3), $result->current());
		$this->assertSame(2, $result->key());

		$result->seek(0);

		$this->assertEquals((object) array('value' => 1), $result->current());
		$this->assertSame(0, $result->key());

		$this->assertFalse($result->seek(-1));
		$this->assertFalse($result->seek(3));
	}

	/**
	 * @group core.libraries.database.result.iterator
	 */
	public function test_iteration()
	{
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2 UNION SELECT 3');

		$result->rewind();

		$this->assertTrue($result->valid());
		$this->assertEquals((object) array('value' => 1), $result->current());
		$this->assertSame(0, $result->key());

		$result->next();

		$this->assertTrue($result->valid());
		$this->assertEquals((object) array('value' => 2), $result->current());
		$this->assertSame(1, $result->key());

		$result->next();

		$this->assertTrue($result->valid());
		$this->assertEquals((object) array('value' => 3), $result->current());
		$this->assertSame(2, $result->key());

		$result->next();

		$this->assertFalse($result->valid());
	}

	public function test_array()
	{
//		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2')->as_array(TRUE);
		$result = $this->db->query('SELECT 1 AS value UNION SELECT 2')->result_array(FALSE);

		$this->assertEquals(array(array('value' => 1), array('value' => 2)), $result);
	}

	public function test_object()
	{
		$result = $this->db->query('SELECT 1 AS value');

		$row = $result->current();

		$this->assertObjectHasAttribute('value', $row);
		$this->assertEquals(1, $row->value);
	}

}
