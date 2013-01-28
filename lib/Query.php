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
    // where is special because of append wheres and overrides. Example:
    // Model:where(...)->limit(...)->where(...)
    // Both wheres need to be considered, concatenating them with AND
    // For while, the while is override
    $this->options['conditions'] = func_get_args();
    return $this;
  }

  public function readonly($flag = true) 
  {
    $this->options['readonly'] = (boolean) $flag;
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