<?php
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

		@unlink(self::InvalidDb);
	}


	public static function tearDownAfterClass()
	{
		parent::tearDownAfterClass();

		@unlink(static::$db);
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

	public function test_gh183_sqliteadapter_autoincrement()
	{
		// defined in lowercase: id integer not null primary key
		$columns = $this->conn->columns('awesome_people');
		$this->assert_true($columns['id']->auto_increment);

		// defined in uppercase: `amenity_id` INTEGER NOT NULL PRIMARY KEY
		$columns = $this->conn->columns('amenities');
		$this->assert_true($columns['amenity_id']->auto_increment);

		// defined using int: `rm-id` INT NOT NULL
		$columns = $this->conn->columns('`rm-bldg`');
		$this->assert_false($columns['rm-id']->auto_increment);

		// defined using int: id INT NOT NULL PRIMARY KEY
		$columns = $this->conn->columns('hosts');
		$this->assert_true($columns['id']->auto_increment);
	}

	public function test_datetime_to_string()
	{
		$datetime = '2009-01-01 01:01:01';
		$this->assert_equals($datetime,$this->conn->datetime_to_string(date_create($datetime)));
	}

	public function test_date_to_string()
	{
		$datetime = '2009-01-01';
		$this->assert_equals($datetime,$this->conn->date_to_string(date_create($datetime)));
	}

	// not supported
	public function test_connect_with_port() {}
}
?>