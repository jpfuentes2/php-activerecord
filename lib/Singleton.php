<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * This implementation of the singleton pattern does not conform to the strong definition
 * given by the "Gang of Four." The __construct() method has not be privatized so that
 * a singleton pattern is capable of being achieved; however, multiple instantiations are also
 * possible. This allows the user more freedom with this pattern.
 *
 * @package ActiveRecord
 */
abstract class Singleton
{
	/**
	 * Array of cached singleton objects.
	 *
	 * @var array
	 */
	private static $instances = array();
	
	/**
	 * You can subclass a Singleton and have it override the
	 * cache slots for one of its parents by returning the
	 * parent class name here instead.
	 */
	public static function cache_entry_name()
	{
		return get_called_class();
	}

	/**
	 * Static method for instantiating a singleton object.
	 *
	 * @return object
	 */
	final public static function instance()
	{
		$called_class_name = get_called_class();
		
		$cache_entry_name = forward_static_call(array($called_class_name, 'cache_entry_name'));
			
		if (!isset(self::$instances[$cache_entry_name]))
		{
			self::$instances[$cache_entry_name] = new $called_class_name;
		}

		return self::$instances[$cache_entry_name];
	}

	/**
	 * Singleton objects should not be cloned.
	 *
	 * @return void
	 */
	final private function __clone() {}

	/**
	 * Similar to a get_called_class() for a child class to invoke.
	 *
	 * @return string
	 */
	final protected function get_called_class()
	{
		$backtrace = debug_backtrace();
    	return get_class($backtrace[2]['object']);
	}
}
?>
