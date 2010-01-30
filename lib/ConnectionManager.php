<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * Singleton to manage any and all database connections.
 *
 * @package ActiveRecord
 */
class ConnectionManager extends Singleton
{
	/**
	 * Array of {@link Connection} objects.
	 * @var array
	 */
	static private $connections = array();

	/**
	 * If $name is null then the default connection will be returned.
	 *
	 * @see Config
	 * @param string $name Optional name of a connection
	 * @return Connection
	 */
	public static function get_connection($name=null)
	{
		if (!isset(self::$connections[$name]) || !self::$connections[$name]->connection)
		{
			$config = Config::instance();
			$connection_string = $name ? $config->get_connection($name) : $config->get_default_connection();
			self::$connections[$name] = Connection::instance($connection_string);
		}
		return self::$connections[$name];
	}
};
?>
