<?php
class TestCase extends PHPUnit_Framework_TestCase
{
	private function setupAssertKeys($args)
	{
		$last = count($args)-1;
		$keys = array_slice($args,0,$last);
		$array = $args[$last];
		return array($keys,$array);
	}

	public function assertHasKeys(/* $keys..., $array */)
	{
		list($keys,$array) = $this->setupAssertKeys(func_get_args());

		$this->assertNotNull($array,'Array was null');

		foreach ($keys as $name)
			$this->assertArrayHasKey($name,$array);
	}

	public function assertDoesntHasKeys(/* $keys..., $array */)
	{
		list($keys,$array) = $this->setupAssertKeys(func_get_args());

		foreach ($keys as $name)
			$this->assertArrayNotHasKey($name,$array);
	}

	public function assertIsA($expected_class, $object)
	{
		$this->assertEquals($expected_class,get_class($object));
	}

	public function assertDateTimeEquals($expected, $actual)
	{
		$this->assertEquals($expected->format(DateTime::ISO8601),$actual->format(DateTime::ISO8601));
	}
}
