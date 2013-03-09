<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * Used to stack Query objects and provide a mechanism for default scopes, named
 * scopes, and parameterized scopes.
 *
 * 
 * @package ActiveRecord
 */
class Scopes
{
	protected $model = null;
	protected $scopes = null;
	protected $applied_scopes = null; //Hash of applied named and default_scopes
	
	/**
	* While enabled - The default scope will be used in all find methods for the Model
	*
	* @var boolean
	*/
	protected $default_scope_enabled = true;
	
	/**
	* Flag that is set if conditions are added after scopes have been applied
	* It is used to determine whether or not a find_by_pk should be called even
	* if conditions have been set after a find by the default scope
	*
	* @var boolean
	*/
	public $added_unscoped_conditions = false;
	
	
	public function __construct($model, $initial_scope=null)
	{
		$this->model = $model;
		if($initial_scope)
			$this->add_scope($initial_scope);
	}
	
	/**
	*  Called to disable a model from using the default scope on a find.
	* Usage Model::scoped()->disable_default_scope();
	*/
	public function disable_default_scope()
	{
		$this->default_scope_enabled = false;
		return $this;
	}
	
	public function default_scope_is_enabled()
	{
		return $this->default_scope_enabled;
	}
	
	/**
	* @return parent model
	*/
	public function get_model()
	{
		return $this->model;
	}
	
	/**
	* Returns the list of stacked scope options with a reference to itself
	* in the options array so that the state of the Model object it was called upon
	* can be persisted
	*
	* @return array An options array with a reference to the scope in the ['scope'] key
	*/
	public function get_options($with_scope_attached = true)
	{
		$options = array();
		if($this->scopes)
		{
			$options = $this->scopes->get_options();
		}
		if($with_scope_attached)
			$options['scope'] = $this;
		return $options;
	}
	
	/**
	* Pushes a scope onto the stack of applied scopes.
	* A scope can be in the form of an options array or a Query Object
	*/
	public function add_scope($scope)
	{
		if(!$this->scopes)
			$this->scopes = new Query($this->model);
		if(is_array($scope) && isset($scope['scope']))
		{
			$this->add_scope($scope['scope']);
			unset($scope['scope']);
		}
		
		if($scope instanceof Query)//already Query Instance
		{
			$this->scopes->merge($scope);
		}
		else if($scope instanceof Scopes)
		{
			if($scope == $this)
			{
				return $this;
			}
			else
			{
				$options = $scope->get_options(false);
				if($options)
					$this->scopes->merge($options);
			}
		}else if(is_array($scope) && $scope)
		{
			$this->scopes->merge($scope);
		}
		return $this;
	}
	
	public function default_scope()
	{
		$model = $this->model;
		
		return $model->get_default_scope();
	}

	/**
	* Firstly checks for named scopes and then adds them to the scope stack
	* Will secondly check to see if it's a function call that can be appended to 
	* the list of scopes
	* Lastly it will delegate to the model that the function was called on, and if
	* the model's function returns a Scope or Query object, it will append it to the 
	* list of scopes.
	*/
	public function __call($method, $args)
	{
		$combined_options = array();
		$model = $this->model;
		
		if($named_scope = $model::check_for_named_scope($method))
		{
			$this->add_scope($named_scope);
			return $this;
		}
		elseif(in_array($method, Query::get_builder_scopes()))
		{
			$query = new Query($this->model);
			$result = call_user_func_array(array($query, $method), $args);
			return $this->add_scope($result);
		}
		elseif(is_callable(array($this->model,$method)))
		{
			$result = $this->call_model_method($method, $args);
			if($result instanceof Scopes || $result instanceof Query)
				return $this->add_scope($result);
			else
				return $this;
		}
		else
		{
			throw new ActiveRecordException("Call to undefined method: $method");
		}
	}

	protected function call_model_method($method, $args = array())
	{
		return call_user_func_array(array($this->model, $method), $args);
	}
	
	/**
	*  Delegates to the model's find method while merging the passed options with the 
	* currently applied scopes. Options passed as a parameter override the scopes that were
	* added, but the default scope will override any other options or scopes
	*
	* @return Model::find($type,$options);
	*/
	public function find($type,$options=array())
	{
		$args = array($type,$options);
		if($this->scopes)
		{
			if($options)
			{
				$this->add_scope($options);
			}
			$args = array($type,$this->get_options());
		}
		else
		{
			$options['scope'] = $this;
			$args = array($type,$options);
		}
		return call_user_func_array(array($this->model, 'find'), $args);
	}
	
	public $remove_scope_from_hash_after_adding_default_scope = false;
	public function count($options = array())
	{
		if($this->scopes)
		{
			if($options)
			{
				$this->add_scope($options);
			}
			$args = array($this->get_options());
		}
		else
		{
			$options['scope'] = $this;
			$args = array($options);
		}
		$this->remove_scope_from_hash_after_adding_default_scope = true;
		return call_user_func_array(array($this->model, 'count'), $args);
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