<?php
class VenueCB extends ActiveRecord\Model
{
	static $table_name = 'venues';

	static $after_destroy = array('after_destroy_one', 'after_destroy_two');
	static $before_destroy = 'before_destroy_using_string';
	static $after_create;

	// DO NOT add a static $after_construct for this. we are testing
	// auto registration of callback with this
	public function after_construct() {}

	public function non_generic_after_construct() {}

	public function after_destroy_one() {}
	public function after_destroy_two() {}

	public function before_destroy_using_string() {}
}
?>