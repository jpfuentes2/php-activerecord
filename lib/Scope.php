<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;
class Scopes
{
	protected $model = null;
	protected $scopes = null;
	public function __construct($model,$initial_scope)
	{
		$this->model = $model;
		if($initial_scope)
			$this->add_scope($initial_scope);
		
	}
	public function add_scope($scope)
	{
		if($this->scopes)
		{
			$this->scopes->merge($scope);
		}
		else if($scope instanceof Query)//already Query Instance
		{
			$this->scopes = $scope;
		}
		else
		{
			$query = new Query($this->model);
			$this->scopes = $query->merge($scope);
		}
	}
	
	
	public function __call($method,$args)
	{
		$combined_options = array();
		
		if($options = $this->model->check_for_named_scope($method))
		{
			$this->add_scope($options);
			return $this;
		}
		elseif(in_array($method, Query::get_builder_scopes()))
		{
			$query = new Query($this->model);
			return call_user_func_array(array($query, $method), $args);
		}
		else
		{
			if(isset($args[0]))
				$this->add_scope($args[0]);
			if($this->scopes)
				return $this->model->$method($this->scopes->get_options());
			else
				return $this->model->$method();
		}
		return $this;
	}
}
?>