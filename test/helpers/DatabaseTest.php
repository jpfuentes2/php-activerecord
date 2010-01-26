<?php
require_once 'DatabaseLoader.php';

class DatabaseTest extends SnakeCase_PHPUnit_Framework_TestCase
{
	protected $conn;
	public static $log = false;

	public function set_up($connection_name=null)
	{
		ActiveRecord\Table::clear_cache();

		$config = ActiveRecord\Config::instance();
		$this->original_default_connection = $config->get_default_connection();

		if ($connection_name)
			$config->set_default_connection($connection_name);

		if ($connection_name == 'sqlite' || $config->get_default_connection() == 'sqlite')
		{
			// need to create the db. the adapter specifically does not create it for us.
			$this->db = substr(ActiveRecord\Config::instance()->get_connection('sqlite'),9);
			new SQLite3($this->db);
		}

		$this->conn = ActiveRecord\ConnectionManager::get_connection($connection_name);

		$GLOBALS['ACTIVERECORD_LOG'] = false;

		$loader = new DatabaseLoader($this->conn);
		$loader->reset_table_data();

		if (self::$log)
			$GLOBALS['ACTIVERECORD_LOG'] = true;
	}

	public function tear_down()
	{
		if ($this->original_default_connection)
			ActiveRecord\Config::instance()->set_default_connection($this->original_default_connection);
	}

	public function assert_exception_message_contains($contains, $closure)
	{
		$message = "";

		try {
			$closure();
		} catch (ActiveRecord\UndefinedPropertyException $e) {
			$message = $e->getMessage();
		}

		$this->assert_true(strpos($message,$contains) !== false);
	}
}
?>