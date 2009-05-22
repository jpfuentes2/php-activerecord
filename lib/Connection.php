<?php
namespace ActiveRecord;

require_once 'URL.php';
require_once 'Column.php';
require_once 'Expressions.php';

abstract class Connection
{
	public $connection;

	/**
	 * Retrieve a database connection.
	 *
	 * @param string $url A database connection string (ex. mysql://user:pass@host[:port]/dbname)
	 *   Everything after the protocol:// part is specific to the connection adapter.
	 *   OR
	 *   A connection name that is set in ActiveRecord\Config
	 *   If null it will use the default connection specified by ActiveRecord\Config->set_default_connection
	 * @return An ActiveRecord::Connection object
	 */
	public static function instance($connection_string_or_connection_name=null)
	{
		$config = Config::instance();

		if (strpos($connection_string_or_connection_name,'://') === false)
		{
			$connection_string = $connection_string_or_connection_name ?
				$config->get_connection($connection_string_or_connection_name) :
				$config->get_default_connection_string();
		}
		else
			$connection_string = $connection_string_or_connection_name;

		if (!$connection_string)
			throw new DatabaseException("Empty connection string");

		$url = new Net_URL($connection_string);
		$protocol = $url->protocol;
		$class = ucwords($protocol) . 'Adapter';
		$fqclass = '\ActiveRecord\\' . $class;
		$source = dirname(__FILE__) . "/adapters/$class.php";

		if (!file_exists($source))
			throw new DatabaseException("Adapter source not found. Expected to be in $source");

		require_once($source);

		if (!class_exists($fqclass))
			throw new DatabaseException("No connection adapter found for protocol: $url->protocol");

		$connection = new $fqclass($connection_string);
		$connection->protocol	= $protocol;
		$connection->class		= $class;
		$connection->fqclass	= $fqclass;

		return $connection;
	}

	protected function __construct($connection_string)
	{
		$this->connect($connection_string);
	}

	/**
	 * Use this for any adapters that can take connection info in the form below
	 * to set the adapters connection info.
	 *
	 * protocol://user:pass@host[:port]/dbname
	 *
	 * @params string $url A URL
	 * @return The parsed URL as an array.
	 */
	public static function connection_info_from($url)
	{
		$url = new Net_URL($url);

		if (!$url->host)
			throw new DatabaseException('Database host must be specified in the connection string.');

		if (!$url->path)
			throw new DatabaseException('Database name must be specified in the connection string.');

		$url->db = substr($url->path,1);

		return $url;
	}

	/**
	 * Fetches all data in the result set into an array.
	 */
	public function fetch_all($res)
	{
		$list = array();

		while (($row = $this->fetch($res)))
			$list[] = $row;

		return $list;
	}

	/**
	 * Retrieves column meta data for the specified table.
	 *
	 * @param string $table Name of a table
	 * @return An array of ActiveRecord::Column objects.
	 */
	abstract function columns($table);

	/**
	 * Connects to the database. Should throw an ActiveRecord\DatabaseException
	 * if connection failed.
	 */
	protected abstract function connect($connection_string);

	/**
	 * Closes the connection. Must set $this->connection to null.
	 */
	abstract function close();

	/**
	 * Escapes a string.
	 *
	 * @param string $string String to escape
	 * @return string
	 */
	abstract function escape($string);

	/**
	 * Fetches the current row data for the specified result set.
	 *
	 * @param object $res The raw connectoin result set.
	 * @return An associative array containing the record values.
	 */
	abstract function fetch($res);

	/**
	 * Frees a result set or statement handle.
	 *
	 * @param mixed $res The result set or statement handle to free
	 */
	abstract function free_result_set($res);

	/**
	 * Retrieve the insert id of the last model saved.
	 * @return int.
	 */
	abstract function insert_id();

	/**
	 * Adds a limit clause to the SQL query.
	 *
	 * @param string $sql The SQL statement.
	 * @param int $offset Row offste to start at.
	 * @param int $limit Maximum number of rows to return.
	 */
	abstract function limit($sql, $offset, $limit);

	/**
	 * Execute a raw SQL query on the database.
	 *
	 * @param string $sql Raw SQL string to execute.
	 * @param array $values Optional array of bind values
	 * @return A result set handle or void if you used $handler closure.
	 */
	abstract function query($sql, $values=array());

	/**
	 * Execute a raw SQL query and fetch the results.
	 *
	 * @param string $sql Raw SQL string to execute.
	 * @param Closure $handler Closure that will be passed the fetched results.
	 * @return array Array of table names.
	 */
	function query_and_fetch($sql, \Closure $handler)
	{
		$res = $this->query($sql);

		while (($row = $this->fetch($res)))
			$handler($row);
	}

	/**
	 * Quote a name like table names and field names.
	 *
	 * @param string $string String to quote.
	 * @return string
	 */
	abstract function quote_name($string);

	/**
	 * Returns a list of tables available to the current connection.
	 *
	 * @return array Array of table names.
	 */
	abstract function tables();
};
?>