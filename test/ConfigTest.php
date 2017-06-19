<?php

use ActiveRecord\Config;
use ActiveRecord\ConfigException;

class TestLogger
{
	private function log() {}
}

class TestDateTimeWithoutCreateFromFormat
{
   public function format($format=null) {}
}

class TestDateTime
{
   public function format($format=null) {}
   public static function createFromFormat($format, $time) {}
}

class ConfigTest extends SnakeCase_PHPUnit_Framework_TestCase
{
	public function set_up()
	{
		$this->config = new Config();
		$this->connections = array(
			'development' => 'mysql://blah/development',
			'test' => 'mysql://blah/test',
			'production' => array(
				'adapter' => 'mysql',
				'host' => '127.0.0.1',
				'database' => 'production'
			));
		$this->config->set_connections($this->connections);
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function test_set_connections_must_be_array()
	{
		$this->config->set_connections(null);
	}

	public function test_get_connections()
	{
		$connections = $this->config->get_connections();
		$this->assert_instance_of('ActiveRecord\ConnectionInfo', $connections['development']);
		$this->assert_equals('development', $connections['development']->database);
	}

	public function test_get_connection_info()
	{
		$connection = $this->config->get_connection_info('development');
		$this->assert_instance_of('ActiveRecord\ConnectionInfo', $connection);
		$this->assert_equals('development', $connection->database);
	}

	public function test_get_invalid_connection()
	{
		$this->assert_null($this->config->get_connection_info('whiskey tango foxtrot'));
	}

	public function test_get_default_connection_and_connection()
	{
		$this->config->set_default_connection('test');
		$this->assert_equals('test', $this->config->get_default_connection());
		$this->assert_equals('test', $this->config->get_default_connection_info()->database);
	}

	public function test_get_default_connection_and_connection_info_defaults_to_development()
	{
		$this->assert_equals('development', $this->config->get_default_connection());
		$this->assert_equals('development', $this->config->get_default_connection_info()->database);
	}

	public function test_get_default_connection_info_when_connection_name_is_not_valid()
	{
		$this->config->set_default_connection('little mac');
		$this->assert_null($this->config->get_default_connection_info());
	}

	public function test_default_connection_is_set_when_only_one_connection_is_present()
	{
		$this->config->set_connections(array('development' => $this->connections['development']));
		$this->assert_equals('development',$this->config->get_default_connection());
	}

	public function test_set_connections_with_default()
	{
		$this->config->set_connections($this->connections,'test');
		$this->assert_equals('test',$this->config->get_default_connection());
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function test_set_model_directories_must_be_array()
	{
		$this->config->set_model_directories(null);
	}

	public function test_get_date_class_with_default()
	{
		$this->assert_equals('ActiveRecord\\DateTime', $this->config->get_date_class());
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function test_set_date_class_when_class_doesnt_exist()
	{
		$this->config->set_date_class('doesntexist');
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function test_set_model_directories_directories_must_exist(){
		$home = ActiveRecord\Config::instance()->get_model_directory();

		$this->config->set_model_directories(array(
			realpath(__DIR__ . '/models'),
			'/some-non-existing-directory'
		));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function test_set_model_directory_stores_as_array(){
		$home = ActiveRecord\Config::instance()->get_model_directory();

		$this->config->set_model_directory(realpath(__DIR__ . '/models'));
		$this->assertInternalType('array', $this->config->get_model_directories());

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function test_get_model_directory_returns_first_model_directory(){
		$home = ActiveRecord\Config::instance()->get_model_directory();

		$this->config->set_model_directories(array(
			realpath(__DIR__ . '/models'),
			realpath(__DIR__ . '/backup-models'),
		));
		$this->assert_equals(realpath(__DIR__ . '/models'), $this->config->get_model_directory());

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function test_set_date_class_when_class_doesnt_have_format_or_createfromformat()
	{
		$this->config->set_date_class('TestLogger');
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function test_set_date_class_when_class_doesnt_have_createfromformat()
	{
		$this->config->set_date_class('TestDateTimeWithoutCreateFromFormat');
	}

	public function test_set_date_class_with_valid_class()
	{
		$this->config->set_date_class('TestDateTime');
		$this->assert_equals('TestDateTime', $this->config->get_date_class());
	}

	public function test_initialize_closure()
	{
		$test = $this;

		Config::initialize(function($cfg) use ($test)
		{
			$test->assert_not_null($cfg);
			$test->assert_equals('ActiveRecord\Config',get_class($cfg));
		});
	}

	public function test_logger_object_must_implement_log_method()
	{
		try {
			$this->config->set_logger(new TestLogger);
			$this->fail();
		} catch (ConfigException $e) {
			$this->assert_equals($e->getMessage(), "Logger object must implement a public log method");
		}
	}
}
