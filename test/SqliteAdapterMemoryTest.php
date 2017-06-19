<?php
require_once __DIR__ . '/../lib/adapters/SqliteAdapter.php';
require_once __DIR__ . '/SqliteAdapterTest.php';

class SqliteAdapterMemoryTest extends SqliteAdapterTest
{
	public function set_up($connection_name=null)
	{
		$connections = ActiveRecord\Config::instance()->get_connections();
    $connections['sqlite'] = 'sqlite://:memory:';
		ActiveRecord\Config::instance()->set_connections($connections);

		parent::set_up();
	}

	public function testConnectToMemoryDatabaseShouldNotCreateDbFile()
	{
		try
		{
			ActiveRecord\Connection::instance("sqlite://:memory:");
			$this->assertFalse(file_exists(__DIR__ . "/" . ":memory:"));
		}
		catch (ActiveRecord\DatabaseException $e)
		{
			$this->fail("could not open connection to :memory: database");
		}
	}

}
?>
