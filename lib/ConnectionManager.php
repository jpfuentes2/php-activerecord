<?php
/**
 * @package ActiveRecord
 * @subpackage ConnectionManager
 */
namespace ActiveRecord;

/**
 * @package ActiveRecord
 * @subpackage ConnectionManager
 */
class ConnectionManager extends Singleton
{
	/**
	 * Array of ActiveRecord\Connection objects
	 * @static
	 * @var array
	 */
	static private $connections = array();

	/**
	 * If @param $name is null then the default connection will be returned.
	 * @see ActiveRecord\Config @var $default_connection
	 * @param string $name Name of a connection
	 * @return ActiveRecord\Connection instance
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
