<?php
include 'helpers/config.php';

// Only use this to test static methods in Connection that are not specific
// to any database adapter.

class ConnectionTest extends SnakeCase_PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function test_connection_info_from_should_throw_exception_when_no_host()
	{
		ActiveRecord\Connection::parse_connection_url('mysql://user:pass@');
	}

	public function test_connection_info()
	{
		$info = ActiveRecord\Connection::parse_connection_url('mysql://user:pass@127.0.0.1:3306/dbname');
		$this->assert_equals('mysql',$info->protocol);
		$this->assert_equals('user',$info->user);
		$this->assert_equals('pass',$info->pass);
		$this->assert_equals('127.0.0.1',$info->host);
		$this->assert_equals(3306,$info->port);
		$this->assert_equals('dbname',$info->db);
	}
}
?>