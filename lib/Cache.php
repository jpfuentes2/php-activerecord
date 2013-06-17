<?php
namespace ActiveRecord;
use Closure;

/**
 * Cache::get('the-cache-key', function() {
 *	 # this gets executed when cache is stale
 *	 return "your cacheable datas";
 * });
 */
class Cache
{
	static $adapter = null;
	static $options = array();

	/**
	 * Initializes the cache.
	 *
	 * With the $options array it's possible to define:
	 * - expiration of the key, (time in seconds)
	 * - a namespace for the key
	 *
	 * this last one is useful in the case two applications use
	 * a shared key/store (for instance a shared Memcached db)
	 *
	 * Ex:
	 * $cfg_ar = ActiveRecord\Config::instance();
	 * $cfg_ar->set_cache('memcache://localhost:11211',array('namespace' => 'my_cool_app',
	 *																											 'expire'		 => 120
	 *																											 ));
	 *
	 * In the example above all the keys expire after 120 seconds, and the
	 * all get a postfix 'my_cool_app'.
	 *
	 * (Note: expiring needs to be implemented in your cache store.)
	 *
	 * @param string $url URL to your cache server
	 * @param array $options Specify additional options
	 */
	public static function initialize($urls, $options=array())
	{
		if (is_array($urls) && !empty($urls))
		{
			$parsed_urls = array();
			foreach($urls as $url){
				$parsed_urls[] = parse_url($url);
			}

			$file = ucwords(Inflector::instance()->camelize($parsed_urls[0]['scheme']));
			$class = "ActiveRecord\\$file";
			require_once __DIR__ . "/cache/$file.php";

			try{
				static::$adapter = new $class($parsed_urls);
			} catch (CacheException $e) {
				static::$adapter = null;
			}
		}
		else
			static::$adapter = null;

		static::$options = array_merge(array('expire' => 30, 'namespace' => ''),$options);
	}

	public static function flush()
	{
		if (static::$adapter)
			static::$adapter->flush();
	}

	public static function get($key, $closure, $expire=null)
	{
		$key = static::get_namespace() . $key;
		
		if (!static::$adapter)
			return $closure();
        
		if (!($value = static::$adapter->read($key))){
			$expire = is_null($expire) ? static::$options['expire'] : $expire;
			static::$adapter->write($key,($value = $closure()),$expire);
		}

		return $value;
	}

	private static function get_namespace()
	{
		return (isset(static::$options['namespace']) && strlen(static::$options['namespace']) > 0) ? (static::$options['namespace'] . "::") : "";
	}

	public static function format_options($options){
		$rval = null;

		if($options === true){
			// set default cache options
			$rval = array('expire' => static::$options['expire']); 

		} elseif (is_array($options)) {
			$rval = array();
			if(isset($options['key'])) $rval['key'] = $options['key'];
			$rval['expire'] = isset($options['expire']) ? intval($options['expire']) : static::$options['expire'];

		}

		return $rval;
	}
}
?>
