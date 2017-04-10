<?php
use ActiveRecord\DatabaseException;
use ActiveRecord\DateTime as DateTime;

class DateTimeTest extends SnakeCase_PHPUnit_Framework_TestCase
{
	public function set_up()
	{
		$this->date = new DateTime();
		$this->original_format = DateTime::$DEFAULT_FORMAT;
	}

	public function tear_down()
	{
		DateTime::$DEFAULT_FORMAT = $this->original_format;
	}

	private function get_model()
	{
		try {
			$model = new Author();
		} catch (DatabaseException $e) {
			$this->mark_test_skipped('failed to connect. '.$e->getMessage());
		}

		return $model;
	}

	private function assert_dirtifies($method /*, method params, ...*/)
	{
		$model = $this->get_model();
		$datetime = new DateTime();
		$datetime->attribute_of($model,'some_date');

		$args = func_get_args();
		array_shift($args);

		call_user_func_array(array($datetime,$method),$args);
		$this->assert_has_keys('some_date', $model->dirty_attributes());
	}

	public function test_should_flag_the_attribute_dirty()
	{
		$interval = new DateInterval('PT1S');
		$timezone = new DateTimeZone('America/New_York');
		$this->assert_dirtifies('setDate',2001,1,1);
		$this->assert_dirtifies('setISODate',2001,1);
		$this->assert_dirtifies('setTime',1,1);
		$this->assert_dirtifies('setTimestamp',1);
		$this->assert_dirtifies('setTimezone',$timezone);
		$this->assert_dirtifies('modify','+1 day');
		$this->assert_dirtifies('add',$interval);
		$this->assert_dirtifies('sub',$interval);
	}

	public function test_set_iso_date()
	{
		$a = new \DateTime();
		$a->setISODate(2001,1);

		$b = new DateTime();
		$b->setISODate(2001,1);

		$this->assert_datetime_equals($a,$b);
	}

	public function test_set_time()
	{
		$a = new \DateTime();
		$a->setTime(1,1);

		$b = new DateTime();
		$b->setTime(1,1);

		$this->assert_datetime_equals($a,$b);
	}

    public function test_set_time_microseconds()
    {
        $a = new \DateTime();
        $a->setTime(1, 1, 1);

        $b = new DateTime();
        $b->setTime(1, 1, 1, 0);

        $this->assert_datetime_equals($a,$b);
	}

	public function test_get_format_with_friendly()
	{
		$this->assert_equals('Y-m-d H:i:s', DateTime::get_format('db'));
	}

	public function test_get_format_with_format()
	{
		$this->assert_equals('Y-m-d', DateTime::get_format('Y-m-d'));
	}

	public function test_get_format_with_null()
	{
		$this->assert_equals(\DateTime::RFC2822, DateTime::get_format());
	}

	public function test_format()
	{
		$this->assert_true(is_string($this->date->format()));
		$this->assert_true(is_string($this->date->format('Y-m-d')));
	}

	public function test_format_by_friendly_name()
	{
		$d = date(DateTime::get_format('db'));
		$this->assert_equals($d, $this->date->format('db'));
	}

	public function test_format_by_custom_format()
	{
		$format = 'Y/m/d';
		$this->assert_equals(date($format), $this->date->format($format));
	}

	public function test_format_uses_default()
	{
		$d = date(DateTime::$FORMATS[DateTime::$DEFAULT_FORMAT]);
		$this->assert_equals($d, $this->date->format());
	}

	public function test_all_formats()
	{
		foreach (DateTime::$FORMATS as $name => $format)
			$this->assert_equals(date($format), $this->date->format($name));
	}

	public function test_change_default_format_to_format_string()
	{
		DateTime::$DEFAULT_FORMAT = 'H:i:s';
		$this->assert_equals(date(DateTime::$DEFAULT_FORMAT), $this->date->format());
	}

	public function test_change_default_format_to_friently()
	{
		DateTime::$DEFAULT_FORMAT = 'short';
		$this->assert_equals(date(DateTime::$FORMATS['short']), $this->date->format());
	}

	public function test_to_string()
	{
		$this->assert_equals(date(DateTime::get_format()), "" . $this->date);
	}

	public function test_create_from_format_error_handling()
	{
		$d = DateTime::createFromFormat('H:i:s Y-d-m', '!!!');
		$this->assert_false($d);
	}

	public function test_create_from_format_without_tz()
	{
		$d = DateTime::createFromFormat('H:i:s Y-d-m', '03:04:05 2000-02-01');
		$this->assert_equals(new DateTime('2000-01-02 03:04:05'), $d);
	}

	public function test_create_from_format_with_tz()
	{
		$d = DateTime::createFromFormat('Y-m-d H:i:s', '2000-02-01 03:04:05', new \DateTimeZone('Etc/GMT-10'));
		$d2 = new DateTime('2000-01-31 17:04:05');

		$this->assert_equals($d2->getTimestamp(), $d->getTimestamp());
	}

	public function test_native_date_time_attribute_copies_exact_tz()
	{
		$dt = new \DateTime(null, new \DateTimeZone('America/New_York'));
		$model = $this->get_model();

		// Test that the data transforms without modification
		$model->assign_attribute('updated_at', $dt);
		$dt2 = $model->read_attribute('updated_at');

		$this->assert_equals($dt->getTimestamp(), $dt2->getTimestamp());
		$this->assert_equals($dt->getTimeZone(), $dt2->getTimeZone());
		$this->assert_equals($dt->getTimeZone()->getName(), $dt2->getTimeZone()->getName());
	}

	public function test_ar_date_time_attribute_copies_exact_tz()
	{
		$dt = new DateTime(null, new \DateTimeZone('America/New_York'));
		$model = $this->get_model();

		// Test that the data transforms without modification
		$model->assign_attribute('updated_at', $dt);
		$dt2 = $model->read_attribute('updated_at');

		$this->assert_equals($dt->getTimestamp(), $dt2->getTimestamp());
		$this->assert_equals($dt->getTimeZone(), $dt2->getTimeZone());
		$this->assert_equals($dt->getTimeZone()->getName(), $dt2->getTimeZone()->getName());
	}

	public function test_clone()
	{
		$model = $this->get_model();
		$model_attribute = 'some_date';

		$datetime = new DateTime();
		$datetime->attribute_of($model, $model_attribute);

		$cloned_datetime = clone $datetime;

		// Assert initial state
		$this->assert_false($model->attribute_is_dirty($model_attribute));

		$cloned_datetime->add(new DateInterval('PT1S'));

		// Assert that modifying the cloned object didn't flag the model
		$this->assert_false($model->attribute_is_dirty($model_attribute));

		$datetime->add(new DateInterval('PT1S'));

		// Assert that modifying the model-attached object did flag the model
		$this->assert_true($model->attribute_is_dirty($model_attribute));

		// Assert that the dates are equal but not the same instance
		$this->assert_equals($datetime, $cloned_datetime);
		$this->assert_not_same($datetime, $cloned_datetime);
	}
}
?>
