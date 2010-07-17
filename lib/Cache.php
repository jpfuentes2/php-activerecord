<?php
namespace ActiveRecord;
use Closure;

/**
 * Cache::get('the-cache-key', function() {
 *   # this gets executed when cache is stale
 *   return "your cacheable datas";
 * });
 */
class Cache
{
	static $adapter = null;
	static $options = array();

	/**
	 * Initializes the cache.
	 *
	 * @param string $url URL to your cache server
	 * @param array $options Specify additional options
	 */
	public static function initialize($url, $options=array())
	{
		if ($url)
		{
			$url = parse_url($url);
			$file = ucwords(Inflector::instance()->camelize($url['scheme']));
			$class = "ActiveRecord\\$file";
			require_once dirname(__FILE__) . "/cache/$file.php";
			static::$adapter = new $class($url);
		}
		else
			static::$adapter = null;

		static::$options = array_merge(array('expire' => 30),$options);
	}

	public static function flush()
	{
		if (static::$adapter)
			static::$adapter->flush();
	}

	public static function get($key, $closure)
	{
		if (!static::$adapter)
			return $closure();

		if (!($value = static::$adapter->read($key)))
			static::$adapter->write($key,($value = $closure()),static::$options['expire']);

		return $value;
	}
}
?>
