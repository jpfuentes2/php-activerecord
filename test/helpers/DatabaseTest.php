<?php
require_once __DIR__ . '/DatabaseLoader.php';

class DatabaseTest extends TestCase
{
	protected $conn;
	public static $log = false;
	public static $db;

	public function setUp($connection_name=null)
	{
		ActiveRecord\Table::clear_cache();

		$config = ActiveRecord\Config::instance();
		$this->original_default_connection = $config->get_default_connection();

		$this->original_date_class = $config->get_date_class();

		if ($connection_name)
			$config->set_default_connection($connection_name);

		if ($connection_name == 'sqlite' || $config->get_default_connection() == 'sqlite')
		{
			// need to create the db. the adapter specifically does not create it for us.
			$info = ActiveRecord\Config::instance()->get_connection_info('sqlite');
			static::$db = $info->host;
			new SQLite3(static::$db);
		}

		$this->connection_name = $connection_name;
		try {
			$this->conn = ActiveRecord\ConnectionManager::get_connection($connection_name);
		} catch (ActiveRecord\DatabaseException $e) {
			$this->markTestSkipped($connection_name . ' failed to connect. '.$e->getMessage());
		}

		$GLOBALS['ACTIVERECORD_LOG'] = false;

		$loader = new DatabaseLoader($this->conn);
		$loader->reset_table_data();

		if (self::$log)
			$GLOBALS['ACTIVERECORD_LOG'] = true;
	}

	public function tearDown()
	{
		ActiveRecord\Config::instance()->set_date_class($this->original_date_class);
		if ($this->original_default_connection)
			ActiveRecord\Config::instance()->set_default_connection($this->original_default_connection);
	}

	public function assertExceptionMessageContains($contains, $closure)
	{
		$message = "";

		try {
			$closure();
		} catch (ActiveRecord\UndefinedPropertyException $e) {
			$message = $e->getMessage();
		}

		$this->assertContains($contains, $message);
	}

	/**
	 * Returns true if $regex matches $actual.
	 *
	 * Takes database specific quotes into account by removing them. So, this won't
	 * work if you have actual quotes in your strings.
	 */
	public function assertSqlHas($needle, $haystack)
	{
		$needle = str_replace(array('"','`'),'',$needle);
		$haystack = str_replace(array('"','`'),'',$haystack);
		return $this->assertContains($needle, $haystack);
	}

	public function assertSqlDoesntHas($needle, $haystack)
	{
		$needle = str_replace(array('"','`'),'',$needle);
		$haystack = str_replace(array('"','`'),'',$haystack);
		return $this->assertNotContains($needle, $haystack);
	}
}
