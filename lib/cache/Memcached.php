<?php
namespace ActiveRecord;

/**
 * @author Ernestas Stankevicius http://internetas.eu
 * @version 1.0 
 */
class Memcached
{
	const DEFAULT_PORT = 11211;
        const DEFAULT_HOST = 'localhost';

	private $memcached;

	/**
	 * @param array $options
	 */
	public function __construct($options)
	{
		$this->memcached = new \Memcached();
		$options['port'] = isset($options['port']) ? $options['port'] : self::DEFAULT_PORT;
                $options['host'] = isset($options['host']) ? $options['host'] : self::DEFAULT_HOST;

		if (!$this->memcached->addServer($options['host'], $options['port']))
			throw new CacheException("Could not connect to $options[host]:$options[port]");
	}

	public function flush()
	{
		$this->memcached->flush();
	}

	public function read($key)
	{
		return $this->memcached->get($key);
	}

	public function write($key, $value, $expire)
	{
		$this->memcached->set($key, $value, $expire);
	}
}
?>
