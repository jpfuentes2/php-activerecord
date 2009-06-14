<?php
class SnakeCase_PHPUnit_Framework_TestCase extends PHPUnit_Framework_TestCase
{
	public function __call($meth, $args)
	{
		$camel_cased_method = ActiveRecord\Inflector::instance()->camelize($meth);

		if (method_exists($this, $camel_cased_method))
			return call_user_func_array(array($this, $camel_cased_method), $args);

		$class_name = get_called_class();
		$trace = debug_backtrace();
		die("PHP Fatal Error:  Call to undefined method $class_name::$meth() in {$trace[1]['file']} on line {$trace[1]['line']}" . PHP_EOL);
	}

	public function setUp()
	{
		if (method_exists($this,'set_up'))
			call_user_func_array(array($this,'set_up'),func_get_args());
	}

	public function tearDown()
	{
		if (method_exists($this,'tear_down'))
			call_user_func_array(array($this,'tear_down'),func_get_args());
	}
}
?>