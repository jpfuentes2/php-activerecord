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
                $this->memcached->addServer($options['host'], $options['port']);
	}

	public function flush()
	{
                if(!$this->memcached->flush())
                        throw new CacheException("Memcached: ".$this->memcached->getResultCode()
                            ." - ".$this->memcached->getResultMessage());
	}

	public function read($key)
	{
                $data = $this->memcached->get($key);
                if(!$data)
                        throw new CacheException("Memcached: ".$this->memcached->getResultCode()
                            ." - ".$this->memcached->getResultMessage());
                return $data;
	}
        
        public function delete($key)
        {
                if(!$this->memcached->delete($key))
                        throw new CacheException("Memcached: ".$this->memcached->getResultCode()
                            ." - ".$this->memcached->getResultMessage());
        }

	public function write($key, $value, $expire)
	{
            	if(!$this->memcached->set($key, $value, $expire))
                        throw new CacheException("Memcached: ".$this->memcached->getResultCode()
                            ." - ".$this->memcached->getResultMessage());
	}
        
}
?>
