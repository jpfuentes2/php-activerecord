<?php
use ActiveRecord\Column;

require_once __DIR__ . '/../lib/adapters/MysqlAdapter.php';

class MysqlAdapterTest extends AdapterTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp('mysql');
	}

	public function test_enum()
	{
		$author_columns = $this->conn->columns('authors');
		$this->assertEquals('enum',$author_columns['some_enum']->raw_type);
		$this->assertEquals(Column::STRING,$author_columns['some_enum']->type);
		$this->assertSame(null,$author_columns['some_enum']->length);
	}

	public function test_set_charset()
	{
		$connection_info = ActiveRecord\Config::instance()->get_connection_info($this->connection_name);
		$connection_info->charset = 'utf8';
		$conn = ActiveRecord\Connection::instance($connection_info);
		$this->assertEquals('SET NAMES ?', $conn->last_query);
	}

	public function test_limit_with_null_offset_does_not_contain_offset()
	{
		$ret = array();
		$sql = 'SELECT * FROM authors ORDER BY name ASC';
		$this->conn->query_and_fetch($this->conn->limit($sql,null,1),function($row) use (&$ret) { $ret[] = $row; });

		$this->assertTrue(strpos($this->conn->last_query, 'LIMIT 1') !== false);
	}
}
