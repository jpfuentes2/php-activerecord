<?php

namespace ActiveRecord;

class StrongParametersTest extends \PHPUnit_Framework_TestCase
{

	public function setUp(){
		$this->params = new StrongParameters(array(
			'user' => array(
				'name' => 'Foo Bar',
				'email' => 'foo@bar.baz',
				'bio' => 'I am Foo Bar',
				'is_admin' => true,
			),
		));
	}

	public function testConstruct()
	{
		$params = new StrongParameters(array('name' => 'Foo Bar'));
		$this->assertInstanceOf('ActiveRecord\StrongParameters', $params);
	}

	public function testPermit()
	{
		$params = new StrongParameters(array(
			'name' => 'Foo Bar',
			'email' => 'foo@bar.baz',
			'bio' => 'I am Foo Bar',
			'is_admin' => true,
		));
		$params->permit();
		$expected = array();
		$actual = iterator_to_array($params);
		$this->assertEquals($expected, $actual);

		$params->permit(array('name', 'email'));
		$expected = array('name' => 'Foo Bar', 'email' => 'foo@bar.baz');
		$actual = iterator_to_array($params);
		$this->assertEquals($expected, $actual);

		$params->permit('name', 'bio');
		$expected = array('name' => 'Foo Bar', 'bio' => 'I am Foo Bar');
		$actual = iterator_to_array($params);
		$this->assertEquals($expected, $actual);
	}

	public function testPermitReturnsSelf()
	{
		$params = new StrongParameters(array(
			'name' => 'Foo Bar',
			'email' => 'foo@bar.baz',
			'bio' => 'I am Foo Bar',
			'is_admin' => true,
		));

		$expected = array('name' => 'Foo Bar');
		$actual = iterator_to_array($params->permit('name'));
		$this->assertEquals($expected, $actual);

		$expected = array('name' => 'Foo Bar', 'bio' => 'I am Foo Bar');
		$actual = iterator_to_array($params->permit('name', 'bio'));
		$this->assertEquals($expected, $actual);
	}

	public function testRequireParam()
	{
		$this->assertInstanceOf('ActiveRecord\StrongParameters', $this->params->requireParam('user'));
	}

	/**
	 * @expectedException ActiveRecord\ParameterMissingException
	 */
	public function testRequireParamThrowsOnMissingParam()
	{
		$this->params->requireParam('nonexisting');
	}

	public function testFetch()
	{
		$params = new StrongParameters(array('name' => 'Foo Bar'));
		$this->assertEquals('Foo Bar', $params->fetch('name'));

		$this->assertInstanceOf('ActiveRecord\StrongParameters', $this->params->fetch('user'));
		
		$this->assertEquals('Foo Bar', $this->params->fetch('user')->fetch('name'));
	}

	public function testGetIterator()
	{
		$this->assertInstanceOf('ArrayIterator', $this->params->getIterator());
	}

	public function testArrayAccessOffsetExists()
	{
		$params = new StrongParameters(array('name' => 'Foo Bar'));
		$this->assertTrue(isset($params['name']));
		$this->assertFalse(isset($params['undefined']));
	}

	public function testArrayAccessOffsetGet()
	{
		$params = new StrongParameters(array('name' => 'Foo Bar'));
		$this->assertEquals('Foo Bar', $params['name']);

		$this->assertEquals('Foo Bar', $this->params['user']->fetch('name'));
		$this->assertEquals('Foo Bar', $this->params['user']['name']);
	}

	public function testArrayAccessOffsetSet()
	{
		$params = new StrongParameters(array('name' => 'Foo Bar'));
		$params['test'] = 'foobar';
		$params->permit('test', 'name');
		$this->assertEquals(array('name' => 'Foo Bar', 'test' => 'foobar'), iterator_to_array($params));
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage offset cannot be null
	 */
	public function testArrayAccessOffsetSetThrowsOnNull()
	{
		$params = new StrongParameters(array('name' => 'Foo Bar'));
		$params[] = 'foobar';
	}

	public function testArrayAccessOffsetUnset()
	{
		$params = new StrongParameters(array('name' => 'Foo Bar'));
		$params['test'] = 'foobar';
		$params->permit('test', 'name');
		$this->assertEquals(array('name' => 'Foo Bar', 'test' => 'foobar'), iterator_to_array($params));
		unset($params['test']);
		$this->assertEquals(array('name' => 'Foo Bar'), iterator_to_array($params));
		unset($params['name']);
		$this->assertEquals(array(), iterator_to_array($params));
	}

	public function testArrayAccessOffsetUnsetNested()
	{
		$user = $this->params->fetch('user');
		$user->permit('name', 'email');

		$this->assertEquals(array(
			'name' => 'Foo Bar',
			'email' => 'foo@bar.baz',
		), iterator_to_array($user));

		unset($this->params['user']['name']);
		$this->assertEquals(array(
			'email' => 'foo@bar.baz',
		), iterator_to_array($user));
	}

}

