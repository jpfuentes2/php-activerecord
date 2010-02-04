<?php
include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/Inflector.php';

class InflectorTest extends SnakeCase_PHPUnit_Framework_TestCase
{
	public function set_up()
	{
		$this->inflector = ActiveRecord\Inflector::instance();
	}

	public function test_underscorify()
	{
		$this->assert_equals('rm__name__bob',$this->inflector->variablize('rm--name  bob'));
		$this->assert_equals('One_Two_Three',$this->inflector->underscorify('OneTwoThree'));
	}

	public function test_tableize()
	{
		$this->assert_equals('angry_people',$this->inflector->tableize('AngryPerson'));
		$this->assert_equals('my_sqls',$this->inflector->tableize('MySQL'));
	}
};
?>