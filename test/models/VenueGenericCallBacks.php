<?php
class VenueGenericCallBacks extends ActiveRecord\Model
{
	static $table_name = 'venues';

	//static $after_find;
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

	//public function after_find() { CallBackTest::instance()->run_tests($this); }

	public function after_construct() { CallBackTest::instance()->run_tests($this); }

	public function before_save() { CallBackTest::instance()->run_tests($this); }

	public function after_save() { CallBackTest::instance()->run_tests($this); }

	public function before_create() { CallBackTest::instance()->run_tests($this); }

	public function after_create() { CallBackTest::instance()->run_tests($this); }

	public function before_update() { CallBackTest::instance()->run_tests($this); }

	public function after_update() { CallBackTest::instance()->run_tests($this); }

	public function before_validation() { CallBackTest::instance()->run_tests($this); }

	public function after_validation() { CallBackTest::instance()->run_tests($this); }

	public function before_validation_on_create() { CallBackTest::instance()->run_tests($this); }

	public function after_validation_on_create() { CallBackTest::instance()->run_tests($this); }

	public function before_validation_on_update() { CallBackTest::instance()->run_tests($this); }

	public function after_validation_on_update() { CallBackTest::instance()->run_tests($this); }

	public function before_destroy() { CallBackTest::instance()->run_tests($this); }

	public function after_destroy() { CallBackTest::instance()->run_tests($this); }
}
?>