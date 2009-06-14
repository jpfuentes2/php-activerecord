<?php
include 'helpers/config.php';

use ActiveRecord\Config;
use ActiveRecord\ConnectionManager;

class ConnectionManagerTest extends DatabaseTest
{
	public function test_get_connection_with_null_connection()
	{
		$this->assert_not_null(ConnectionManager::get_connection(null));
		$this->assert_not_null(ConnectionManager::get_connection());
	}

	public function test_get_connection()
	{
		$this->assert_not_null(ConnectionManager::get_connection('mysql'));
	}

	public function test_get_connection_uses_existing_object()
	{
		$a = ConnectionManager::get_connection('mysql');
		$a->harro = 'harro there';

		$this->assert_same($a,ConnectionManager::get_connection('mysql'));
	}
}
?>