<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;
class Scopes
{
	protected $model = null;
	protected $scopes = null;
	public function __construct($model, $initial_scope)
	{
		$this->model = $model;
		if($initial_scope)
			$this->add_scope($initial_scope);

	}

	public function add_scope($scope)
	{
		$this->scopes[] = new Scope($scope);
	}

	public function __call($method, $args)
	{
		$combined_options = array();

		if($options = $this->model->check_for_named_scope($method))
		{
			$this->scopes[] = new Scope($this->model->check_for_named_scope($method));
		}
		else
		{
			return $this->call_model_method($method, $args);
		}
		return $this;
	}

	protected function call_model_method($method, $args = array())
	{
		return call_user_func_array(array($this->model, $method), $args);
	}
	
	public function find($type,$options=array())
	{
		if($this->scopes)
		{
			if($options)
			{
				$this->add_scope($options);
			}
			$args = array($type,$this->scopes->get_options());
		}
		return call_user_func_array(array($this->model, 'find'), $args);
	}
	
	public function all($options = array())
	{
		return $this->find('all',$options);
	}
	public function first($options = array())
	{
		return $this->find('first',$options);
	}
	public function last()
	{
		return $this->find('last',$options);
	}

}
class Scope
{
	protected $options = null;
	public function __construct($options=null)
	{
		$this->options = $options;
	}
	
	public function get_options()
	{
		return array('all',$this->options);
	}
}

?>