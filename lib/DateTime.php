<?php
namespace ActiveRecord;

class DateTime extends \DateTime
{
	private $model;
	private $attribute_name;

	public function attribute_of($model, $attribute_name)
	{
		$this->model = $model;
		$this->attribute_name = $attribute_name;
	}

	private function flag_dirty()
	{
		if ($this->model)
			$this->model->flag_dirty($this->attribute_name);
	}

	public function setDate($year, $month, $day)
	{
		$this->flag_dirty();
		call_user_func_array(array($this,'parent::setDate'),func_get_args());
	}

	public function setISODate($year, $week , $day=null)
	{
		$this->flag_dirty();
		call_user_func_array(array($this,'parent::setISODate'),func_get_args());
	}

	public function setTime($hour, $minute, $second=null)
	{
		$this->flag_dirty();
		call_user_func_array(array($this,'parent::setTime'),func_get_args());
	}

	public function setTimestamp($unixtimestamp)
	{
		$this->flag_dirty();
		call_user_func_array(array($this,'parent::setTimestamp'),func_get_args());
	}
}
?>