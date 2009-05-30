<?php
class DatabaseTest extends PHPUnit_Framework_TestCase
{
	protected $conn;
	public static $log = false;
	public static $imported = 0;

	public function setUp($connection_name=null)
	{
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

		$this->clear_tables();
		$this->run_sql_script_for($config->get_default_connection());

		if (self::$log)
			$GLOBALS['ACTIVERECORD_LOG'] = true;
	}

	public function tearDown()
	{
		if ($this->original_default_connection)
			ActiveRecord\Config::instance()->set_default_connection($this->original_default_connection);
	}

	private function clear_tables()
	{
		$tables = $this->conn->tables();

		foreach ($tables as $table)
		{
			try {
				$this->conn->query("DELETE FROM " . $this->conn->quote_name($table));
			} catch (Exception $e) {
				// ignore
			}
		}

		// this is kinda retarded but works for now so that we create the schema
		// if there are no tables currently in it
		if (count($tables) <= 0)
			static::$imported = 0;
	}

	private function run_sql_script_for($connection_name)
	{
		if (++static::$imported <= 1 && ($script = $this->get_sql_file($connection_name)))
			$this->exec_batch_sql($script);

		if (($script = $this->get_sql_file("$connection_name-data","data")))
			$this->exec_batch_sql($script);
	}

	private function exec_batch_sql($script)
	{
		// this isn't full proof (exploding on ';') and don't need it to be
		foreach (explode(';',$script) as $sql)
		{
			if (trim($sql) != '')
				$this->conn->query($sql);
		}
		return true;
	}

	private function get_sql_file($file_name, $default=null)
	{
		$file_name = str_replace("mysqli","mysql",$file_name);
		$file = dirname(__FILE__) . "/../fixtures/$file_name.sql";

		if (file_exists($file))
			return file_get_contents($file);

		if ($default)
			return $this->get_sql_file($default);

		return null;
	}
}
?>