<?php
require_once 'DatabaseLoader.php';

class SnakeCasePHPUnitMethodNames extends PHPUnit_Framework_TestCase
{
	public function __call($meth, $args)
	{
		$camel_cased_method = ActiveRecord\Inflector::instance()->camelize($meth);

		if (method_exists($this, $camel_cased_method))
			return call_user_func_array(array($this, $camel_cased_method), $args);

		$class_name = get_called_class();
		$trace = debug_backtrace();
		die("PHP Fatal Error:  Call to undefined method $class_name::$meth() in {$trace[1]['file']} on line {$trace[1]['line']}" . PHP_EOL);
	}
}

class DatabaseTest extends SnakeCasePHPUnitMethodNames
{
	protected $conn;
	public static $log = false;

	public function setUp($connection_name=null)
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

	public function tearDown()
	{
		if ($this->original_default_connection)
			ActiveRecord\Config::instance()->set_default_connection($this->original_default_connection);
	}
}
?>