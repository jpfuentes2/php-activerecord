<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;
class Scopes
{
	protected $model = null;
	protected $scopes = array();
	public function __construct($model,$initial_scope)
	{
		$this->model = $model;
		if($initial_scope)
			$this->add_scope($initial_scope);
		
	}
	public function add_scope($scope)
	{
		$this->scopes[] = new Scope($scope);
	}
	
	public function __call($method,$args)
	{
		$combined_options = array();
		if($this->model->check_for_named_scope($method))
		{
			$this->scopes[] = new Scope($this->model->check_for_named_scope($method));
		}
		else
		{
			foreach($this->scopes as $scope)
			{
				$scope_options = $scope->get_options();
				$options = Model::extract_and_validate_options($scope_options);
				var_dump($options);
				foreach($options as $key=>$value)
				{
					if(array_key_exists($key,$options))
					{
						$combined_options[$key] = $value;
					}
					else
					{
						$combined_options[$key] = $value;
					}
				}
			}
			$args = $combined_options;
			return $this->model->$method($args);
		}
		return $this;
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