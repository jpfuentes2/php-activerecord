<?php
namespace ActiveRecord;

/**
 * @author Ernestas Stankevicius http://internetas.eu
 * @version 1.0 
 */
class Apc
{
	public function __construct($options)
	{
		if(!function_exists('apc_store'))
			throw new CacheException("APC not supported");
	}

	public function flush()
	{
		// No flush support in apc
	}

	public function read($key)
	{
		return apc_fetch($key);
	}
        
        public function delete($key)
        {
            apc_delete($key);
        }

	public function write($key, $value, $expire)
	{
            apc_store($key, $value, $expire);
	}
}
?>
