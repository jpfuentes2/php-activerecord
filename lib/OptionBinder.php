<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;
/**
 * Used to create chain queries and scopes
 *
 * @package ActiveRecord
 */
class OptionBinder
{

	private $options = array();

	/**
	 * Determinates the valid aliases for the model
	 */
	private static $builder_scopes = array('where', 'order', 'group', 'limit', 'offset', 'select', 'having', 'include', 'joins', );

	public static function get_builder_scopes()
	{
		return self::$builder_scopes;
	}

	/**
	 * Sets the normal options
	 */
	public function __call($method, $arguments)
	{
		if(in_array($method, self::get_builder_scopes()))
			return $this->set_option($method, $arguments[0]);
		throw new ActiveRecordException("The scope \"$method\" does not exists");
	}

	public function set_option($option, $value)
	{
		$this->options[$option] = $value;
		return $this;
	}

	public function get_options()
	{
		return $this->options;
	}

	public function where()
	{
		$args = func_get_args();

		if(is_hash($args[0]))
		{
			foreach($args[0] as $key => $value)
			{
				if($value === null)
				{
					$this->append_where("$key IS NULL", $value);
				}
				else
				{
					$this->append_where("$key=?", $value);
				}
			}
		}
		else
		{
			$where = $args[0];
			$values = array_splice($args, 1);
			$this->append_where($where, $values);
		}
		return $this;
	}

	public function append_where($where, $value)
	{
		if(empty($this->options['conditions'][0]))
			$this->options['conditions'][0] = $where;
		else
			$this->options['conditions'][0] .= ' AND '.$where;

		if(is_array($value))
			foreach($value as $v)
			{
				if($v !== null)
					$this->options['conditions'][] = $v;
			}
		else
		{
			if($value !== null)
				$this->options['conditions'][] = $value;
		}
	}

	/**
	 * Merges the current query with the options in argument. In case of repeated options, the argument predominates
	 */
	public function merge($options)
	{
		if($options instanceof OptionBinder)
		{
			$options = $options->get_options();
		}
		foreach($options as $option => $value)
		{
			if($option == 'conditions' && !empty($value[0]))
			{
				if(is_string($value))
					$value = array($value);
				$values = isset($value[1]) ? array_splice($value, 1) : array();
				$this->append_where($value[0], $values);
				continue;
			}
			elseif($option == 'conditions' && count($value))
			{
				$this->where($value);
				continue;
			}
			$this->options[$option] = $value;
		}
		return $this;
	}

}
