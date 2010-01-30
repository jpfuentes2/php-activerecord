<?php
class Book extends ActiveRecord\Model
{
	static $belongs_to = array(array('author'));

	public function upper_name()
	{
		return strtoupper($this->name);
	}

	public function name()
	{
		return strtolower($this->name);
	}
};
?>