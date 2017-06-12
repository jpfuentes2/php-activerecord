<?php
use ActiveRecord\Column;

require_once __DIR__ . '/../lib/adapters/PgsqlAdapter.php';

class PgsqlAdapterTest extends AdapterTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp('pgsql');
	}

	public function test_insert_id()
	{
		$this->conn->query("INSERT INTO authors(author_id,name) VALUES(nextval('authors_author_id_seq'),'name')");
		$this->assertTrue($this->conn->insert_id('authors_author_id_seq') > 0);
	}

	public function test_insert_id_with_params()
	{
		$x = array('name');
		$this->conn->query("INSERT INTO authors(author_id,name) VALUES(nextval('authors_author_id_seq'),?)",$x);
		$this->assertTrue($this->conn->insert_id('authors_author_id_seq') > 0);
	}

	public function test_insert_id_should_return_explicitly_inserted_id()
	{
		$this->conn->query('INSERT INTO authors(author_id,name) VALUES(99,\'name\')');
		$this->assertTrue($this->conn->insert_id('authors_author_id_seq') > 0);
	}

	public function test_set_charset()
	{
		$connection_info = ActiveRecord\Config::instance()->get_connection_info($this->connection_name);
		$connection_info->charset = 'utf8';
		$conn = ActiveRecord\Connection::instance($connection_info);
		$this->assertEquals("SET NAMES 'utf8'",$conn->last_query);
	}

	public function test_gh96_columns_not_duplicated_by_index()
	{
		$this->assertEquals(3,$this->conn->query_column_info("user_newsletters")->rowCount());
	}

	public function test_boolean_to_string()
	{
		// false values
		$this->assertEquals("0", $this->conn->boolean_to_string(false));
		$this->assertEquals("0", $this->conn->boolean_to_string('0'));
		$this->assertEquals("0", $this->conn->boolean_to_string('f'));
		$this->assertEquals("0", $this->conn->boolean_to_string('false'));
		$this->assertEquals("0", $this->conn->boolean_to_string('n'));
		$this->assertEquals("0", $this->conn->boolean_to_string('no'));
		$this->assertEquals("0", $this->conn->boolean_to_string('off'));
		// true values
		$this->assertEquals("1", $this->conn->boolean_to_string(true));
		$this->assertEquals("1", $this->conn->boolean_to_string('1'));
		$this->assertEquals("1", $this->conn->boolean_to_string('t'));
		$this->assertEquals("1", $this->conn->boolean_to_string('true'));
		$this->assertEquals("1", $this->conn->boolean_to_string('y'));
		$this->assertEquals("1", $this->conn->boolean_to_string('yes'));
		$this->assertEquals("1", $this->conn->boolean_to_string('on'));
	}
}
