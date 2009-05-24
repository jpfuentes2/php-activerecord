<?php
use ActiveRecord\Column;

class AdapterTest extends DatabaseTest
{
	const InvalidDb = '__1337__invalid_db__';

	public function testShouldSetAdapterVariables()
	{
		$this->assertNotNull($this->conn->protocol);
		$this->assertNotNull($this->conn->class);
		$this->assertNotNull($this->conn->fqclass);
	}

	public function testNullConnectionStringUsesDefaultConnection()
	{
		$this->assertNotNull(ActiveRecord\Connection::instance(null));
		$this->assertNotNull(ActiveRecord\Connection::instance(''));
		$this->assertNotNull(ActiveRecord\Connection::instance());
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testInvalidConnectionProtocol()
	{
		ActiveRecord\Connection::instance('terribledb://user:pass@host/db');
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testNoHostConnection()
	{
		ActiveRecord\Connection::instance("{$this->conn->protocol}://user:pass");
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testConnectionFailedInvalidHost()
	{
		ActiveRecord\Connection::instance("{$this->conn->protocol}://user:pass/lskjdflkjsdlkjflksjf/db");
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testConnectionFailed()
	{
		ActiveRecord\Connection::instance("{$this->conn->protocol}://baduser:badpass@127.0.0.1/db");
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testConnectFailed()
	{
		ActiveRecord\Connection::instance("{$this->conn->protocol}://zzz:zzz@127.0.0.1/test");
	}

	public function testConnectWithPort()
	{
		// TODO refactor so we use whats in the config but just add port to it
		ActiveRecord\Connection::instance("{$this->conn->protocol}://test:test@127.0.0.1:3306/test");
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testConnectToInvalidDatabase()
	{
		ActiveRecord\Connection::instance("{$this->conn->protocol}://test:test@127.0.0.1/" . self::InvalidDb);
	}

	public function testDateTimeType()
	{
		$columns = $this->conn->columns('authors');
		$this->assertEquals('datetime',$columns['created_at']->raw_type);
		$this->assertEquals(Column::DATETIME,$columns['created_at']->type);
		$this->assertTrue($columns['created_at']->length > 0);
	}

	public function testDate()
	{
		$columns = $this->conn->columns('authors');
		$this->assertEquals('date',$columns['some_date']->raw_type);
		$this->assertEquals(Column::DATE,$columns['some_date']->type);
		$this->assertEquals(10,$columns['some_date']->length);
	}

	public function testColumnsNoInflectionOnHashKey()
	{
		$author_columns = $this->conn->columns('authors');
		$this->assertTrue(array_key_exists('author_id',$author_columns));
	}

	public function testColumnsNullable()
	{
		$author_columns = $this->conn->columns('authors');
		$this->assertFalse($author_columns['author_id']->nullable);
		$this->assertTrue($author_columns['parent_author_id']->nullable);
	}

	public function testColumnsPK()
	{
		$author_columns = $this->conn->columns('authors');
		$this->assertTrue($author_columns['author_id']->pk);
		$this->assertFalse($author_columns['parent_author_id']->pk);
	}

	public function testColumnsDefault()
	{
		$author_columns = $this->conn->columns('authors');
		$this->assertEquals('default_name',$author_columns['name']->default);
	}

	public function testColumnsType()
	{
		$author_columns = $this->conn->columns('authors');
		$this->assertEquals('varchar',$author_columns['name']->raw_type);
		$this->assertEquals(Column::STRING,$author_columns['name']->type);
		$this->assertEquals(25,$author_columns['name']->length);
	}

	public function testQuery()
	{
		$res = $this->conn->query('SELECT * FROM authors');

		while (($row = $this->conn->fetch($res)))
			$this->assertNotNull($row);

		$res = $this->conn->query('SELECT * FROM authors WHERE author_id=1');
		$row = $this->conn->fetch($res);
		$this->assertEquals('Tito',$row['name']);
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testInvalidQuery()
	{
		$this->conn->query('alsdkjfsdf');
	}

	public function testFetch()
	{
		$res = $this->conn->query('SELECT * FROM authors WHERE author_id IN(1,2,3)');
		$i = 0;
		$ids = array();

		while (($row = $this->conn->fetch($res)))
		{
			++$i;
			$ids[] = $row['author_id'];
		}

		$this->assertEquals(3,$i);
		$this->assertEquals(array(1,2,3),$ids);
	}

	public function testQueryWithParams()
	{
		$res = $this->conn->query('SELECT * FROM authors WHERE name IN(?) ORDER BY name DESC',array(array('Bill Clinton','Tito')));
		$row = $this->conn->fetch($res);
		$this->assertEquals('Tito',$row['name']);

		$row = $this->conn->fetch($res);
		$this->assertEquals('Bill Clinton',$row['name']);

		$row = $this->conn->fetch($res);
		$this->assertEquals(null,$row);
	}

	public function testInsertIdShouldReturnExplicitlyInsertedId()
	{
		$this->conn->query('INSERT INTO authors(author_id,name) VALUES(99,\'name\')');
		$this->assertTrue($this->conn->insert_id() > 0);
	}

	public function testInsertId()
	{
		$this->conn->query('INSERT INTO authors(name) VALUES(\'name\')');
		$this->assertTrue($this->conn->insert_id() > 0);
	}

	public function testInsertIdWithParams()
	{
		$this->conn->query('INSERT INTO authors(name) VALUES(?)',array('name'));
		$this->assertTrue($this->conn->insert_id() > 0);
	}

	public function testInflection()
	{
		$columns = $this->conn->columns('authors');
		$this->assertEquals('parent_author_id',$columns['parent_author_id']->inflected_name);
	}

	public function testEscape()
	{
		$s = "Bob's";
		$this->assertNotEquals($s,$this->conn->escape($s));
	}

	public function testColumns()
	{
		$columns = $this->conn->columns('authors');
		$this->assertEquals(array('author_id','parent_author_id','name','updated_at','created_at','some_date'),array_keys($columns));

		$this->assertEquals(true,$columns['author_id']->pk);
		$this->assertEquals('int',$columns['author_id']->raw_type);
		$this->assertEquals(Column::INTEGER,$columns['author_id']->type);
		$this->assertTrue($columns['author_id']->length > 1);
		$this->assertFalse($columns['author_id']->nullable);

		$this->assertEquals(false,$columns['parent_author_id']->pk);
		$this->assertTrue($columns['parent_author_id']->nullable);

		$this->assertEquals('varchar',$columns['name']->raw_type);
		$this->assertEquals(Column::STRING,$columns['name']->type);
		$this->assertEquals(25,$columns['name']->length);
		$this->assertEquals('default_name',$columns['name']->default);
	}

	public function testColumnsDecimal()
	{
		$columns = $this->conn->columns('books');
		$this->assertEquals(Column::DECIMAL,$columns['special']->type);
		$this->assertEquals(10,$columns['special']->length);
	}

	public function testLimit()
	{
		$sql = 'SELECT * FROM authors';
		$this->assertEquals("$sql LIMIT 1,5",$this->conn->limit($sql,1,5));
		$this->assertEquals("$sql LIMIT 0,1",$this->conn->limit($sql,0,1));
		$this->assertEquals("$sql LIMIT 0,0",$this->conn->limit($sql,null,null));
		$this->assertEquals("$sql LIMIT 0,1",$this->conn->limit($sql,null,1));
	}

	public function testFetchNoResults()
	{
		$res = $this->conn->query('SELECT * FROM authors WHERE author_id=65534');
		$this->assertEquals(null,$this->conn->fetch($res));
	}

	public function testFetchAll()
	{
		$res = $this->conn->query('SELECT * FROM authors');
		$this->assertTrue(count($this->conn->fetch_all($res)) > 0);
	}

	public function testFree()
	{
		$res = $this->conn->query('SELECT * FROM authors');
		$this->conn->free_result_set($res);
		$this->assertFalse(is_resource($res));
	}

	public function testTables()
	{
		$this->assertTrue(count($this->conn->tables()) > 0);
	}
}
?>