<?php
include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/adapters/Sqlite3Adapter.php';

class Sqlite3AdapterTest extends AdapterTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp('sqlite3');
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
			ActiveRecord\Connection::instance("sqlite3://" . self::InvalidDb);
			$this->assertFalse(true);
		}
		catch (ActiveRecord\DatabaseException $e)
		{
			$this->assertFalse(file_exists(dirname(__FILE__) . "/" . self::InvalidDb));
		}
	}

	// not supported
	public function testCompositeKey() {}
	public function testConnectWithPort() {}
}
?>