<?php

class StrongParametersModelTest extends DatabaseTest
{

	private static $require_strong_parameters;

	public static function setUpBeforeClass()
	{
		$config = ActiveRecord\Config::instance();
		self::$require_strong_parameters = $config->get_require_strong_parameters();
		$config->set_require_strong_parameters(true);
	}

	public static function tearDownAfterClass()
	{
		$config = ActiveRecord\Config::instance();
		$config->set_require_strong_parameters(self::$require_strong_parameters);
	}

	public function testConstructWithStrongParameters()
	{
		$params = new ActiveRecord\StrongParameters(array(
			'name' => 'Foo',
		));
		$book = new Book($params);
		$this->assertNull($book->name);

		$params->permit('name');
		$book = new Book($params);
		$this->assertEquals('Foo', $book->name);
	}

	/**
	 * @expectedException ActiveRecord\UnsafeParametersException
	 */
	public function testConstructWithoutStrongParametersThrowsException()
	{
		$config = ActiveRecord\Config::instance();
		$require_strong_parameters = $config->get_require_strong_parameters();
		$config->set_require_strong_parameters(true);

		$params = array('name' => 'Foo');
		$book = new Book($params);
	}

	public function testUpdateAttributesWithStrongParameters()
	{
		$params = new ActiveRecord\StrongParameters(array(
			'name' => 'Foo',
		));
		$book = new Book();
		$book->update_attributes($params);
		$this->assertNull($book->name);

		$params->permit('name');
		$book->update_attributes($params);
		$this->assertEquals('Foo', $book->name);
	}

	/**
	 * @expectedException ActiveRecord\UnsafeParametersException
	 */
	public function testUpdateAttributesWithoutStrongParameters()
	{
		$config = ActiveRecord\Config::instance();
		$require_strong_parameters = $config->get_require_strong_parameters();
		$config->set_require_strong_parameters(true);

		$params = array('name' => 'Foo');
		$book = new Book();
		$book->update_attributes($params);
	}


}
