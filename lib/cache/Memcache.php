<?php
namespace ActiveRecord;

class Memcache
{
	private $memcache;

	public function __construct($options)
	{
		$this->memcache = new \Memcache();

		if (!$this->memcache->connect($options['host']))
			throw new CacheException("Could not connect to $options[host]:$options[port]");
	}

	public function flush()
	{
		$this->memcache->flush();
	}

	public function read($key)
	{
		return $this->memcache->get($key);
	}

	public function write($key, $value, $expire)
	{
		$this->memcache->set($key,$value,null,$expire);
	}
}
?>
