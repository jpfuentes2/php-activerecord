<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;
use Closure;

/**
 * @package ActiveRecord
 * @subpackage Internal
 */
class Config extends Singleton
{
	/**
	 * Default connection
	 * @var string
	 */
	private $default_connection = 'development';

	/**
	 * Array of available connection strings
	 * @var array
	 */
	private $connections = array();

	/**
	 * Directory for the auto_loading of model classes
	 * @see activerecord_autoload()
	 * @var string
	 */
	private $model_directory;

	/**
	 * Allows block-like config initialization,
	 *
	 * Example:
	 *
	 * ActiveRecord\Config::initialize(function($cfg)
	 *	{
     *		$cfg->set_model_directory('/path/to/your/model_directory');
     *		$cfg->set_connections(array('development' =>
     *  			'mysql://username:password@127.0.0.1/database_name'));
	 *	});
	 * @static
	 * @param Closure object
	 * @return void
	 */
	public static function initialize(Closure $initializer)
	{
		$initializer(parent::instance());
	}

	/**
	 * @see @var $connections
	 * @throws ActiveRecord\ConfigException
	 * @param array $connections Array of connections
	 * @param string $default_connection Optionally specify the default_connection
	 * @return void
	 */
	public function set_connections($connections, $default_connection=null)
	{
		if (!is_array($connections))
			throw new ConfigException("Connections must be an array");

		if ($default_connection)
			$this->set_default_connection($default_connection);

		$this->connections = $connections;
	}

	/**
	 * @return array
	 */
	public function get_connections()
	{
		return $this->connections;
	}

	/**
	 * Returns a connection string if found otherwise null
	 * @param string
	 * @return mixed
	 */
	public function get_connection($name)
	{
		if (array_key_exists($name, $this->connections))
			return $this->connections[$name];

		return null;
	}

	/**
	 * Returns the default connection string or null if there is none
	 * @return mixed
	 */
	public function get_default_connection_string()
	{
		return array_key_exists($this->default_connection,$this->connections) ? $this->connections[$this->default_connection] : null;
	}

	/**
	 * Returns the name of the default connection
	 * @return string
	 */
	public function get_default_connection()
	{
		return $this->default_connection;
	}

	/**
	 * Set the name of the default connection
	 * @param string $connection_name Name of a connection in the connections array
	 * @return void
	 */
	public function set_default_connection($name)
	{
		$this->default_connection = $name;
	}

	/**
	 * @throws ActiveRecord\ConfigException
	 * @param string
	 * @return void
	 */
	public function set_model_directory($dir)
	{
		if (!file_exists($dir))
			throw new ConfigException("Invalid or non-existent directory: $dir");

		$this->model_directory = $dir;
	}

	/**
	 * @return string
	 */
	public function get_model_directory()
	{
		return $this->model_directory;
	}
};
?>