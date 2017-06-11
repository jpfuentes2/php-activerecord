<?php
use ActiveRecord\Cache;

class CacheTest extends TestCase
{
	public function setUp()
	{
		if (!extension_loaded('memcache'))
		{
			$this->markTestSkipped('The memcache extension is not available');
			return;
		}
		
		Cache::initialize('memcache://localhost');
	}

	public function tearDown()
	{
		Cache::flush();
	}

	private function cache_get()
	{
		return Cache::get("1337", function() { return "abcd"; });
	}

	public function test_initialize()
	{
		$this->assertNotNull(Cache::$adapter);
	}

	public function test_initialize_with_null()
	{
		Cache::initialize(null);
		$this->assertNull(Cache::$adapter);
	}

	public function test_get_returns_the_value()
	{
		$this->assertEquals("abcd", $this->cache_get());
	}

	public function test_get_writes_to_the_cache()
	{
		$this->cache_get();
		$this->assertEquals("abcd", Cache::$adapter->read("1337"));
	}

	public function test_get_does_not_execute_closure_on_cache_hit()
	{
		$this->cache_get();
		Cache::get("1337", function() { throw new Exception("I better not execute!"); });
	}

	public function test_cache_adapter_returns_false_on_cache_miss()
	{
		$this->assertSame(false, Cache::$adapter->read("some-key"));
	}

	public function test_get_works_without_caching_enabled()
	{
		Cache::$adapter = null;
		$this->assertEquals("abcd", $this->cache_get());
	}

	public function test_cache_expire()
	{
		Cache::$options['expire'] = 1;
		$this->cache_get();
		sleep(2);

		$this->assertSame(false, Cache::$adapter->read("1337"));
	}
	
	public function test_namespace_is_set_properly()
	{
		Cache::$options['namespace'] = 'myapp';
		$this->cache_get();
		$this->assertSame("abcd", Cache::$adapter->read("myapp::1337"));
	}

	/**
	 * @expectedException ActiveRecord\CacheException
	 * @expectedExceptionMessage Connection refused
	 */
	public function test_exception_when_connect_fails()
	{
		Cache::initialize('memcache://127.0.0.1:1234');
	}
}
