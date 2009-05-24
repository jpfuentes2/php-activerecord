<?php
include 'helpers/config.php';

use ActiveRecord\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->config = new Config();
		$this->connections = array('development' => 'mysql://blah/development', 'test' => 'mysql://blah/test');
		$this->config->set_connections($this->connections);
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function testSetConnectionsMustBeArray()
	{
		$this->config->set_connections(null);
	}

	public function testGetConnections()
	{
		$this->assertEquals($this->connections,$this->config->get_connections());
	}

	public function testGetConnection()
	{
		$this->assertEquals($this->connections['development'],$this->config->get_connection('development'));
	}

	public function testGetInvalidConnection()
	{
		$this->assertNull($this->config->get_connection('whiskey tango foxtrot'));
	}

	public function testGetDefaultConnectionAndConnection()
	{
		$this->config->set_default_connection('development');
		$this->assertEquals('development',$this->config->get_default_connection());
		$this->assertEquals($this->connections['development'],$this->config->get_default_connection_string());
	}

	public function testGetDefaultConnectionAndConnectionStringDefaultsToDevelopment()
	{
		$this->assertEquals('development',$this->config->get_default_connection());
		$this->assertEquals($this->connections['development'],$this->config->get_default_connection_string());
	}

	public function testGetDefaultConnectionStringWhenConnectionNameIsNotValid()
	{
		$this->config->set_default_connection('little mac');
		$this->assertNull($this->config->get_default_connection_string());
	}

	public function testDefaultConnectionIsSetWhenOnlyOneConnectionIsPresent()
	{
		$this->config->set_connections(array('development' => $this->connections['development']));
		$this->assertEquals('development',$this->config->get_default_connection());
	}

	public function testSetConnectionsWithDefault()
	{
		$this->config->set_connections($this->connections,'test');
		$this->assertEquals('test',$this->config->get_default_connection());
	}

	public function testInitializeClosure()
	{
		$test = $this;

		Config::initialize(function($cfg) use ($test)
		{
			$test->assertNotNull($cfg);
			$test->assertEquals('ActiveRecord\Config',get_class($cfg));
		});
	}
}
?>