<?php
class Book extends ActiveRecord\Model
{
	static $belongs_to = array(array('author'));
	static $has_one = array();
	static $getters = array('upper_name');

	public function upper_name()
	{
		return strtoupper($this->name);
	}

	public function name()
	{
		return strtolower($this->name);
	}

	public function get_name()
	{
		return strtoupper($this->read_attribute('name'));
	}

	public function get_upper_name()
	{
		return strtoupper($this->name);
	}
};
?>