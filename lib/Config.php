<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;
use Closure;

/**
 * Manages configuration options for ActiveRecord.
 *
 * <code>
 * ActiveRecord::initialize(function($cfg) {
 *   $cfg->set_model_home('models');
 *   $cfg->set_connections(array(
 *     'development' => 'mysql://user:pass@development.com/awesome_development',
 *     'production' => 'mysql://user:pass@production.com/awesome_production'));
 * });
 * </code>
 *
 * @package ActiveRecord
 */
class Config extends Singleton
{
	/**
	 * Name of the connection to use by default.
	 *
	 * <code>
	 * ActiveRecord\Config::initialize(function($cfg) {
	 *   $cfg->set_model_directory('/your/app/models');
	 *   $cfg->set_connections(array(
	 *     'development' => 'mysql://user:pass@development.com/awesome_development',
	 *     'production' => 'mysql://user:pass@production.com/awesome_production'));
	 * });
	 * </code>
	 *
	 * This is a singleton class so you can retrieve the {@link Singleton} instance by doing:
	 *
	 * <code>
	 * $config = ActiveRecord\Config::instance();
	 * </code>
	 *
	 * @var string
	 */
	private $default_connection = 'development';

	/**
	 * Contains the list of database connection strings.
	 *
	 * @var array
	 */
	private $connections = array();

	/**
	 * Array of directories for the auto_loading of model classes.
	 *
	 * @see activerecord_autoload
	 * @var array
	 */
	private $model_directories = array();

	/**
	 * Switch for logging.
	 *
	 * @var bool
	 */
	private $logging = false;

	/**
	 * Contains a Logger object that must impelement a log() method.
	 *
	 * @var object
	 */
	private $logger;

	/**
	 * Contains the class name for the Date class to use. Must have a public format() method and a
	 * public static createFromFormat($format, $time) method
	 *
	 * @var string
	 */
	private $date_class = 'ActiveRecord\\DateTime';

	/**
	 * The format to serialize DateTime values into.
	 *
	 * @var string
	 */
	private $date_format = \DateTime::ISO8601;

	/**
	 * Switch for use of StrongParameters
	 *
	 * @var bool
	 */
	private $require_strong_parameters = false;

	/**
	 * Allows config initialization using a closure.
	 *
	 * This method is just syntatic sugar.
	 *
	 * <code>
	 * ActiveRecord\Config::initialize(function($cfg) {
	 *   $cfg->set_model_directory('/path/to/your/model_directory');
	 *   $cfg->set_connections(array(
	 *     'development' => 'mysql://username:password@127.0.0.1/database_name'));
	 * });
	 * </code>
	 *
	 * You can also initialize by grabbing the singleton object:
	 *
	 * <code>
	 * $cfg = ActiveRecord\Config::instance();
	 * $cfg->set_model_directory('/path/to/your/model_directory');
	 * $cfg->set_connections(array('development' =>
  	 *   'mysql://username:password@localhost/database_name'));
	 * </code>
	 *
	 * @param Closure $initializer A closure
	 * @return void
	 */
	public static function initialize(Closure $initializer)
	{
		$initializer(parent::instance());
	}

	/**
	 * Sets the list of database connections. Can be an array of connection strings or an array of arrays.
	 *
	 * <code>
	 * $config->set_connections(array(
	 *     'development' => 'mysql://username:password@127.0.0.1/database_name'));
	 * </code>
	 *
	 * <code>
	 * $config->set_connections(array(
	 *     'development' => array(
	 *         'adapter' => 'mysql',
	 *         'host' => '127.0.0.1',
	 *         'database' => 'database_name',
	 *         'username' => 'username',
	 *         'password' => 'password'
	 *     )
	 * ));
	 * </code>
	 *
	 * @param array $connections Array of connections
	 * @param string $default_connection Optionally specify the default_connection
	 * @return void
	 * @throws ActiveRecord\ConfigException
	 */
	public function set_connections($connections, $default_connection=null)
	{
		if (!is_array($connections))
			throw new ConfigException("Connections must be an array");

		if ($default_connection)
			$this->set_default_connection($default_connection);

		$this->connections = array_map(function($connection){
			if(is_string($connection))
			{
				return ConnectionInfo::from_connection_url($connection);
			}
			else
			{
				return new ConnectionInfo($connection);
			}
		}, $connections);
	}

	/**
	 * Returns the connection strings array.
	 *
	 * @return array
	 */
	public function get_connections()
	{
		return $this->connections;
	}

	/**
	 * Returns a connection string if found otherwise null.
	 *
	 * @param string $name Name of connection to retrieve
	 * @return string connection info for specified connection name
	 */
	public function get_connection_info($name)
	{
		if (array_key_exists($name, $this->connections))
			return $this->connections[$name];

		return null;
	}

	/**
	 * Returns the default connection string or null if there is none.
	 *
	 * @return string
	 */
	public function get_default_connection_info()
	{
		return array_key_exists($this->default_connection,$this->connections) ?
			$this->connections[$this->default_connection] : null;
	}

	/**
	 * Returns the name of the default connection.
	 *
	 * @return string
	 */
	public function get_default_connection()
	{
		return $this->default_connection;
	}

	/**
	 * Set the name of the default connection.
	 *
	 * @param string $name Name of a connection in the connections array
	 * @return void
	 */
	public function set_default_connection($name)
	{
		$this->default_connection = $name;
	}

	/**
	 * Sets the directory where models are located.
	 *
	 * @param string $directory Directory path containing your models
	 * @return void
	 */
	public function set_model_directory($directory)
	{
		$this->set_model_directories(array($directory));
	}
	
	/**
	 * Returns the first model directory.
	 *
	 * @return string
	 */
	public function get_model_directory()
	{
		$model_directories = $this->get_model_directories();
		return array_shift($model_directories);
	}
	
	/**
	 * Sets the directories where models are located.
	 *
	 * @param array $directories Array with directory paths containing your models
	 * @return void
	 * @throws ConfigException if one of the model directories was not found
	 */
	public function set_model_directories($directories)
	{
		if (!is_array($directories))
			throw new ConfigException("Directories must be an array");
		
		foreach($directories as $directory)
		{
			if(!file_exists($directory) || !is_dir($directory))
				throw new ConfigException('Invalid or non-existent directory: '. $directory);
		}
		$this->model_directories = $directories;
	}

	/**
	 * Returns the array of model directories.
	 *
	 * @return array
	 */
	public function get_model_directories()
	{
		return $this->model_directories;
	}

	/**
	 * Turn on/off logging
	 *
	 * @param boolean $bool
	 * @return void
	 */
	public function set_logging($bool)
	{
		$this->logging = (bool)$bool;
	}

	/**
	 * Sets the logger object for future SQL logging
	 *
	 * @param object $logger
	 * @return void
	 * @throws ConfigException if Logger objecct does not implement public log()
	 */
	public function set_logger($logger)
	{
		$klass = Reflections::instance()->add($logger)->get($logger);

		if (!$klass->getMethod('log') || !$klass->getMethod('log')->isPublic())
			throw new ConfigException("Logger object must implement a public log method");

		$this->logger = $logger;
	}

	/**
	 * Return whether or not logging is on
	 *
	 * @return boolean
	 */
	public function get_logging()
	{
		return $this->logging;
	}

	/**
	 * Returns the logger
	 *
	 * @return object
	 */
	public function get_logger()
	{
		return $this->logger;
	}

	public function set_date_class($date_class)
	{
		try {
			$klass = Reflections::instance()->add($date_class)->get($date_class);
		} catch (\ReflectionException $e) {
			throw new ConfigException("Cannot find date class");
		}

		if (!$klass->hasMethod('format') || !$klass->getMethod('format')->isPublic())
			throw new ConfigException('Given date class must have a "public format($format = null)" method');

		if (!$klass->hasMethod('createFromFormat') || !$klass->getMethod('createFromFormat')->isPublic())
			throw new ConfigException('Given date class must have a "public static createFromFormat($format, $time)" method');

		$this->date_class = $date_class;
	}

	public function get_date_class()
	{
		return $this->date_class;
	}

	/**
	 * @deprecated
	 */
	public function get_date_format()
	{
		trigger_error('Use ActiveRecord\Serialization::$DATETIME_FORMAT. Config::get_date_format() has been deprecated.', E_USER_DEPRECATED);
		return Serialization::$DATETIME_FORMAT;
	}

	/**
	 * @deprecated
	 */
	public function set_date_format($format)
	{
		trigger_error('Use ActiveRecord\Serialization::$DATETIME_FORMAT. Config::set_date_format() has been deprecated.', E_USER_DEPRECATED);
		Serialization::$DATETIME_FORMAT = $format;
	}

	/**
	 * Sets the url for the cache server to enable query caching.
	 *
	 * Only table schema queries are cached at the moment. A general query cache
	 * will follow.
	 *
	 * Example:
	 *
	 * <code>
	 * $config->set_cache("memcached://localhost");
	 * $config->set_cache("memcached://localhost",array("expire" => 60));
	 * </code>
	 *
	 * @param string $url Url to your cache server.
	 * @param array $options Array of options
	 */
	public function set_cache($url, $options=array())
	{
		Cache::initialize($url,$options);
	}

	/**
	 * Enable or disable use of StrongParameters
	 *
	 * @param bool $flag
	 * @return void
	 */
	public function set_require_strong_parameters($flag)
	{
		$this->require_strong_parameters = $flag;
	}

	/**
	 * Returns whether or not to require StrongParameters
	 *
	 * @return bool
	 */
	public function get_require_strong_parameters()
	{
		return $this->require_strong_parameters;
	}

}
