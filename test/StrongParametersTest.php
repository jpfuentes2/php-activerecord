<?php

class StrongParametersTest extends SnakeCase_PHPUnit_Framework_TestCase
{

	public function setUp(){
		$this->obj = new ActiveRecord\StrongParameters(array(
			'name' => 'Foo Bar',
			'email' => 'foo@bar.baz',
			'bio' => 'I am Foo Bar',
			'is_admin' => true
		));
	}

	public function testConstruct()
	{
		$obj = new ActiveRecord\StrongParameters(array('name' => 'Foo Bar'));
		$this->assertInstanceOf('ActiveRecord\StrongParameters', $obj);
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testConstructWithObjectTriggersError()
	{
		$obj = new ActiveRecord\StrongParameters((object)array('name' => 'Foo Bar'));
	}

	public function testPermit()
	{
		$this->obj->permit();
		$expected = array();
		$actual = iterator_to_array($this->obj);
		$this->assertEquals($expected, $actual);

		$this->obj->permit(array('name', 'email'));
		$expected = array('name' => 'Foo Bar', 'email' => 'foo@bar.baz');
		$actual = iterator_to_array($this->obj);
		$this->assertEquals($expected, $actual);

		$this->obj->permit('name', 'bio');
		$expected = array('name' => 'Foo Bar', 'bio' => 'I am Foo Bar');
		$actual = iterator_to_array($this->obj);
		$this->assertEquals($expected, $actual);
	}

	public function testGetIterator()
	{
		$this->assertInstanceOf('ArrayIterator', $this->obj->getIterator());
	}

}

