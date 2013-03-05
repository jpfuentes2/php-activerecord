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
class Query
{

  private $model_name;
  private $options = array();

  /**
   * Determinates the valid aliases for the model
   */
  private static $builder_scopes = array(
    'where',
    'order',
    'group',
    'limit',
    'offset',
    'select',
    'having',
    'include',
    'joins',
  );

  public function __construct($model_name) 
  { 
    $this->model_name = $model_name;
  }

  public static function get_builder_scopes()
  {
    return self::$builder_scopes;
  }  

  /**
   * Sets the normal options
   */
  public function __call($method, $arguments) 
  {
    if (in_array($method, self::get_builder_scopes())) 
      return $this->set_option($method, $arguments[0]);
    elseif (is_callable(array($this->model_name, $method))) 
    {
      $query = call_user_func_array(array($this->model_name, $method), $arguments);
      if (!$query instanceof Query)
        throw new ActiveRecordException("Scopes must return a Query object");

      return $this->merge($query->get_options());
    }
    else
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

    if (is_hash($args[0])) 
    {
      foreach($args[0] as $key => $value)
	  {
		  if($value === null)
		  {
		  	$this->append_where("$key IS NULL",$value);
		  }
		  else
		  {
	        $this->append_where("$key=?",$value);
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

  private function append_where($where, $value) 
  {
    if (empty($this->options['conditions'][0]))
      $this->options['conditions'][0] = $where;
    else
      $this->options['conditions'][0] .= ' AND ' . $where;

    if (is_array($value))
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

  public function readonly($flag = true) 
  {
    $this->options['readonly'] = (boolean) $flag;
    return $this;
  }

  /**
   * Merges the current query with the options in argument. In case of repeated options, the argument predominates
   */
  public function merge($options) 
  {
    foreach($options as $option => $value)
    {
      if ($option == 'conditions' && !empty($value[0])) 
      {
        $values = isset($value[1]) ? array_splice($value, 1) : array();
        $this->append_where($value[0], $values);
        continue;
      }elseif($option == 'conditions' && count($value))
      {
      	$this->where($value);
      	continue;
      }

      $this->options[$option] = $value;
    }
    return $this;
  }

  private function find($type = 'all') 
  {
    $arguments[] = $type;
    $options = $this->get_options();    
    if (!empty($options))
      $arguments[] = $options;

    return call_user_func_array(array($this->model_name, 'find'), $arguments);
  }

  /**
   * Executes the query, returning all the occurences
   */
  public function all() 
  {
    return $this->find('all');
  }

  /**
   * Executes the query, returning the first occurence
   */
  public function first() 
  {
    return $this->find('first');
  }

  /**
   * Executes the query, returning the last occurence
   */
  public function last() 
  {
    return $this->find('last');
  }

}