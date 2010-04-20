<?php
include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/adapters/SqliteAdapter.php';

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
			$this->assertFalse(file_exists(dirname(__FILE__) . "/" . self::InvalidDb));
		}
	}

	// not supported
	public function testCompositeKey() {}
	public function testConnectWithPort() {}
}
?>