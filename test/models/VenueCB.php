<?php
class VenueCB extends ActiveRecord\Model
{
	static $table_name = 'venues';

	static $after_construct;
	static $before_save;
	static $after_save;
	static $before_create;
	static $after_create;
	static $before_update;
	static $after_update;
	static $before_validation;
	static $after_validation;
	static $before_validation_on_create;
	static $after_validation_on_create;
	static $before_validation_on_update;
	static $after_validation_on_update;
	static $before_destroy;
	static $after_destroy;

	public function test_after_construct()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_before_validation()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_before_validation_on_create()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_before_validation_on_update()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_before_validation_returns_false_cancels_call_backs()
	{
		CallBackTest::instance()->run_tests($this);
		return false;
	}

	public function test_after_validation()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_after_validation_on_create()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_after_validation_on_update()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_before_save()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_before_update()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_after_save()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_after_update()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_before_create()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_after_create()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_before_save_returns_false_cancels_call_backs()
	{
		CallBackTest::instance()->run_tests($this);
		return false;
	}

	public function test_before_destroy()
	{
		CallBackTest::instance()->run_tests($this);
	}

	public function test_after_destroy()
	{
		CallBackTest::instance()->run_tests($this);
	}
}
?>