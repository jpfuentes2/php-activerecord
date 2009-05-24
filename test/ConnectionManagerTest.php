<?php
include 'helpers/config.php';

use ActiveRecord\Config;
use ActiveRecord\ConnectionManager;

class ConnectionManagerTest extends DatabaseTest
{
	public function testGetConnectionWithNullConnection()
	{
		$this->assertNotNull(ConnectionManager::get_connection(null));
		$this->assertNotNull(ConnectionManager::get_connection());
	}

	public function testGetConnection()
	{
		$this->assertNotNull(ConnectionManager::get_connection('mysql'));
	}

	public function testGetConnectionUsesExistingObject()
	{
		$a = ConnectionManager::get_connection('mysql');
		$a->harro = 'harro there';

		$this->assertSame($a,ConnectionManager::get_connection('mysql'));
	}
}
?>