<?php
require_once __DIR__ . '/../lib/Inflector.php';

class InflectorTest extends TestCase
{
	public function setUp()
	{
		$this->inflector = ActiveRecord\Inflector::instance();
	}

	public function test_underscorify()
	{
		$this->assertEquals('rm__name__bob',$this->inflector->variablize('rm--name  bob'));
		$this->assertEquals('One_Two_Three',$this->inflector->underscorify('OneTwoThree'));
	}

	public function test_tableize()
	{
		$this->assertEquals('angry_people',$this->inflector->tableize('AngryPerson'));
		$this->assertEquals('my_sqls',$this->inflector->tableize('MySQL'));
	}

	public function test_keyify()
	{
		$this->assertEquals('building_type_id', $this->inflector->keyify('BuildingType'));
	}
}
