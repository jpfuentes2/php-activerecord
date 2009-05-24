<?php
include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/Inflector.php';

class InflectorTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->inflector = ActiveRecord\Inflector::instance();
	}

	public function testUnderscorify()
	{
		$this->assertEquals('rm_name_bob',$this->inflector->variablize('rm--name  bob'));
	}
};
?>