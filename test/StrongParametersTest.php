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

}

