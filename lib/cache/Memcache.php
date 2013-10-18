<?php
namespace ActiveRecord;

class Memcache
{
	const DEFAULT_PORT = 11211;

	private $memcache;

	/**
	 * Creates a Memcache instance.
	 *
	 * Takes a $urls array, each w/ the following parameters:
	 *
	 * <ul>
	 * <li><b>host:</b> host for the memcache server </li>
	 * <li><b>port:</b> port for the memcache server </li>
	 * </ul>
	 * @param array $urls
	 */
	public function __construct($urls)
	{
		$this->memcache = new \Memcache();
       
        $servers_added = 0; 
        foreach($urls as $url){
		    if(!isset($url['port'])) $url['port'] = self::DEFAULT_PORT;
            if($this->memcache->addServer($url['host'],$url['port'])) 
                $servers_added++;
        }

        if($servers_added == 0)
            throw new CacheException("Could not add any servers to pool");

		//if (!$this->memcache->connect($options['host'],$options['port']))
		//	throw new CacheException("Could not connect to $options[host]:$options[port]");
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
