<?php
include 'helpers/config.php';
require_once __DIR__ . '/../lib/adapters/SqliteAdapter.php';

class SqliteAdapterTest extends AdapterTest
{
	public function set_up($connection_name=null)
	{
		parent::set_up('sqlite');
	}

	public function tearDown()
	{
		parent::tearDown();

		@unlink($this->db);
		@unlink(self::InvalidDb);
	}

	public function testConnectToInvalidDatabaseShouldNotCreateDbFile()
	{
		try
		{
			ActiveRecord\Connection::instance("sqlite://" . self::InvalidDb);
			$this->assertFalse(true);
		}
		catch (ActiveRecord\DatabaseException $e)
		{
			$this->assertFalse(file_exists(__DIR__ . "/" . self::InvalidDb));
		}
	}

	public function test_limit_with_null_offset_does_not_contain_offset()
	{
		$ret = array();
		$sql = 'SELECT * FROM authors ORDER BY name ASC';
		$this->conn->query_and_fetch($this->conn->limit($sql,null,1),function($row) use (&$ret) { $ret[] = $row; });

		$this->assert_true(strpos($this->conn->last_query, 'LIMIT 1') !== false);
	}

	// not supported
	public function testCompositeKey() {}
	public function testConnectWithPort() {}
}
?>
