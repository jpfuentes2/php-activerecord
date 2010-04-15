<?php
include 'helpers/config.php';

class DateTimeTest extends SnakeCase_PHPUnit_Framework_TestCase
{
	private function assert_dirtifies($method /*, method params, ...*/)
	{
		$model = new Author();
		$datetime = new ActiveRecord\DateTime();
		$datetime->attribute_of($model,'some_date');

		$args = func_get_args();
		array_shift($args);

		call_user_func_array(array($datetime,$method),$args);
		$this->assert_has_keys('some_date', $model->dirty_attributes());
	}

	public function test_should_flag_the_attribute_dirty()
	{
		$this->assert_dirtifies('setDate',2001,1,1);
		$this->assert_dirtifies('setISODate',2001,1);
		$this->assert_dirtifies('setTime',1,1);
		$this->assert_dirtifies('setTimestamp',1);
	}

	public function test_set_iso_date()
	{
		$a = new \DateTime();
		$a->setISODate(2001,1);

		$b = new ActiveRecord\DateTime();
		$b->setISODate(2001,1);

		$this->assert_datetime_equals($a,$b);
	}

	public function test_set_time()
	{
		$a = new \DateTime();
		$a->setTime(1,1);

		$b = new ActiveRecord\DateTime();
		$b->setTime(1,1);

		$this->assert_datetime_equals($a,$b);
	}
}
?>