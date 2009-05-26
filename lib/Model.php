<?php
/**
 * @package ActiveRecord
 * @subpackage Model
 */
namespace ActiveRecord;
use DateTime;

/**
 * @package ActiveRecord
 * @subpackage Model
 */
class Model
{
	/**
	 * Instance of ActiveRecord\Errors and will be instantiated once a
	 * write method is called
	 * @var object
	 */
	public $errors;

	/**
	 * Contains model values as column_name => value
	 * @var array
	 */
	private $attributes = array();

	/**
	 * Flag whether or not this model's attributes have been modified since
	 * it will either be null or an array of column_names that have been modified
	 * @var null/array
	 */
	private $__dirty = null;

	/**
	 * Flag that determines of this model can have a writer method invoked such
	 * as: save/update/insert/delete
	 * @var boolean
	 */
	private $__readonly = false;

	/**
	 * Array of relationship objects as model_attribute_name => relationship
	 * @var array
	 */
	private $__relationships = array();

	/**
	 * Flag that determines if a call to save() should issue an insert or an update
	 * sql statement
	 * @var boolean
	 */
	private $__new_record;

	/**
	 * Container of aliases which allows you to access an attribute via a
	 * different name.
	 * @static
	 * @var array
	 */
	static $alias_attribute = array();

	/**
	 * Whitelist of attributes that can be mass-assigned via an instantiation or
	 * a mass-assignment method such as Model#update_attributes()
	 * @static
	 * @var array
	 */
	static $attr_accessible = array();

	/**
	 * Blacklist of attributes that cannot be mass-assigned
	 * @see @var $attr_accessible for more info
	 * @static
	 * @var array
	 */
	static $attr_protected = array();

	/**
	 * When a user instantiates a new object (e.g.: it was not ActiveRecord that instantiated via a find)
	 * then @var $attributes will be mapped according to the schema's defaults. Otherwise, the given @param
	 * $attributes will be mapped via set_attributes_via_mass_assignment.
	 * @param array
	 * @param boolean
	 * @param boolean
	 * @return void
	 */
	public function __construct($attributes=array(), $guard_attributes=true, $instantiating_via_find=false)
	{
		// initialize attributes applying defaults
		if (!$instantiating_via_find)
		{
			foreach (static::table()->columns as $name => $meta)
				$this->attributes[$meta->inflected_name] = $meta->default;
		}

		Reflections::instance()->add($this);

		$this->set_attributes_via_mass_assignment($attributes, $guard_attributes);
		$this->invoke_callback('after_construct',false);
	}

	/**
	 * Retrieves an attribute's value or a relationship object based on the name passed. If the attribute
	 * accessed is 'id' then it will return the model's primary key no matter what the actual attribute name is
	 * for the primary key.
	 * @throws ActiveRecord\Exception (lacking composite PK support)
	 * @throws ActiveRecord\UndefinedPropertyException (if an attr/relationshp is not found by @param $name)
	 * @param $name
	 * @return mixed
	 */
	public function &__get($name)
	{
		// check for aliased attribute
		if (array_key_exists($name, static::$alias_attribute))
			$name = static::$alias_attribute[$name];

		// check for attribute
		if (array_key_exists($name,$this->attributes))
			return $this->attributes[$name];

		// check relationships if no attribute
		if (array_key_exists($name,$this->__relationships))
			return $this->__relationships[$name];

		$table = static::table();

		// this may be first access to the relationship so check Table
		if (($relationship = $table->get_relationship($name)))
		{
			$this->__relationships[$name] = $relationship->load($this);
			return $this->__relationships[$name];
		}

		if ($name == 'id')
		{
			if (count(($this->get_primary_key(true))) > 1)
				throw new Exception("TODO composite key support");

			if (isset($this->attributes[$table->pk[0]]))
				return $this->attributes[$table->pk[0]];
		}

		throw new UndefinedPropertyException($name);
	}

	/**
	 * Determines if an attribute name exists
	 * @param string
	 * @return boolean
	 */
	public function __isset($name)
	{
		return array_key_exists($name,$this->attributes);
	}

	/**
	 * Magic allows un-defined attributes to set via @var $attributes
	 * @throws ActiveRecord\UndefinedPropertyException if @param $name does not exist
	 * @param string
	 * @param mixed
	 * @return mixed value of attribute name
	 */
	public function __set($name, $value)
	{
		if (array_key_exists($name, static::$alias_attribute))
			$name = static::$alias_attribute[$name];

		if (array_key_exists($name,$this->attributes))
		{
			$table = static::table();

			if (!$this->__dirty)
				$this->__dirty = array();

			if (array_key_exists($name,$table->columns) && !is_object($value))
				$value = $table->columns[$name]->cast($value);

			$this->attributes[$name] = $value;
			$this->__dirty[$name] = true;
			return $value;
		}

		throw new UndefinedPropertyException($name);
	}

	/**
	 * Returns hash of attributes that have been modified since loading the model.
	 * @return null or array
	 */
	public function dirty_attributes()
	{
		if (!$this->__dirty)
			return null;

		$dirty = array_intersect_key($this->attributes,$this->__dirty);
		return count($dirty) > 0 ? $dirty : null;
	}

	/**
	 * Getter for @var $attributes
	 * @return array
	 */
	public function attributes()
	{
		return $this->attributes;
	}

	/**
	 * Retrieve the primary key name.
	 * @param boolean
	 * @return string
	 */
	public function get_primary_key($inflect=true)
	{
		return Table::load(get_class($this))->pk;
	}

	/**
	 * Returns an associative array containg values for all the properties in $properties
	 * @param array of property names
	 * @return array containing $property => $value
	 */
	public function get_values_for($properties)
	{
		$ret = array();

		foreach ($properties as $property)
		{
			if (array_key_exists($property,$this->attributes))
				$ret[$property] = $this->attributes[$property];
		}
		return $ret;
	}

	/**
	 * True if this model is read only.
	 * @return boolean
	 */
	public function is_readonly()
	{
		return $this->__readonly;
	}

	/**
	 * True if this is a new record.
	 * @return boolean
	 */
	public function is_new_record()
	{
		return isset($this->__new_record) ? $this->__new_record : false;
	}

	/**
	 * Throws an exception if this model is set to readonly.
	 * @throws ActiveRecord\ReadOnlyException
	 * @param string name of method that was invoked on model for exception message
	 * @return void
	 */
	private function verify_not_readonly($method_name)
	{
		if ($this->is_readonly())
			throw new ReadOnlyException(get_class($this), $method_name);
	}

	/**
	 * Flag model as readonly
	 * @param boolean
	 * @return void
	 */
	public function readonly($readonly=true)
	{
		$this->__readonly = $readonly;
	}

	/**
	 * Retrieve the connection for this model.
	 * @static
	 * @return object instance of ActiveRecord\Connection
	 */
	public static function connection()
	{
		return static::table()->conn;
	}

	/**
	 * Returns the ActiveRecord\Table object for this model. Be sure to call in static scoping.
	 * Example: static::table()
	 * @static
	 * @return object instance of ActiveRecord\Table
	 */
	public static function table()
	{
		return Table::load(get_called_class());
	}

	/**
	 * Creates a model and invokes insert.
	 * @static
	 * @param array Array of the models attributes
	 * @param boolean True if the validators should be run
	 * @return object ActiveRecord\Model
	 */
	public static function create($attributes, $validate=true)
	{
		$class_name = get_called_class();
		$model = new $class_name($attributes);
		$model->save($validate);
		return $model;
	}

	/**
	 * Writer method that will determine whether or not the model is a new record or not. If it
	 * is then it will issue an insert statement, otherwise it will be an upate. You may pass a boolean if the
	 * writer method should invoke validations or not. Callbacks on this model will be called regardless of
	 * the flag passed. If a validation or a callback for this model returns false, then the resultant sql
	 * query will not be issued and false will be returned.
	 * @param boolean true if this should validate
	 * @return boolean
	 */
	public function save($validate=true)
	{
		$this->verify_not_readonly('save');

		$this->__new_record = false;

		foreach ($this->get_primary_key(true) as $pk)
		{
			if (!isset($this->attributes[$pk]))
			{
				$this->__new_record = true;
				break;
			}
		}

		if (!$this->is_new_record())
			return $this->update($validate);
		else
			return $this->insert($validate);
	}

	/**
	 * Issue an INSERT sql statement for this model's attribute.
	 * @see @method save()
	 * @param boolean
	 * @return boolean
	 */
	public function insert($validate = true)
	{
		$this->verify_not_readonly('insert');

		if ($validate && !$this->_validate())
			return false;

		$this->invoke_callback('before_create',false);
		if (($dirty = $this->dirty_attributes()))
			static::table()->insert($dirty);
		else
			static::table()->insert($this->attributes);
		$this->invoke_callback('after_create',false);

		$pk = $this->get_primary_key(false);
		$table = static::table();

		// if we've got an autoincrementing pk set it
		if (count($pk) == 1 && $table->columns[$pk[0]]->auto_increment)
		{
			$inflector = Inflector::instance();
			$this->attributes[$inflector->variablize($pk[0])] = $table->conn->insert_id();
		}
		return true;
	}

	/**
	 * Issue an UPDATE sql statement for this model's dirty attributes.
	 * @see @method save()
	 * @see @var $__dirty
	 * @param boolean
	 * @return boolean
	 */
	public function update($validate = true)
	{
		$this->verify_not_readonly('update');

		if ($validate && !$this->_validate())
			return false;

		if (($dirty = $this->dirty_attributes()))
		{
			$this->invoke_callback('before_update',false);
			static::table()->update($dirty,$this->values_for_pk());
			$this->invoke_callback('after_update',false);
		}

		return true;
	}

	/**
	 * Issue a DELETE statement based on this model's primary key
	 * @return boolean
	 */
	public function delete()
	{
		$this->verify_not_readonly('delete');

		$this->invoke_callback('before_destroy',false);
		static::table()->delete($this->values_for_pk());
		$this->invoke_callback('after_destroy',false);

		return true;
	}

	/**
	 * Helper that creates an array of values for the primary key(s) of this model in the form of
	 * key_name => value
	 * @return array
	 */
	public function values_for_pk()
	{
		return $this->values_for(static::table()->pk);
	}

	/**
	 * Return a hash of name => value for the specified attributes.
	 * @return array
	 */
	public function values_for($attribute_names)
	{
		$filter = array();

		foreach ($attribute_names as $name)
			$filter[$name] = $this->$name;

		return $filter;
	}

	/**
	 *
	 * @return boolean
	 */
	private function _validate()
	{
		$validator = new Validations($this);

		$validation_on = 'validation_on_' . ($this->is_new_record() ? 'create' : 'update');

		foreach (array('before_validation', "before_$validation_on") as $callback)
		{
			if (!$this->invoke_callback($callback,false))
				return false;
		}

		$this->errors = $validator->validate();

		foreach (array('after_validation', "after_$validation_on") as $callback)
			$this->invoke_callback($callback,false);

		if (!$this->errors->is_empty())
			return false;

		return true;
	}

	/**
	 * Update model's timestamps based on is_new_record()?
	 * @return void
	 */
	public function set_timestamps()
	{
		$now = date('Y-m-d H:i:s');

		if (isset($this->updated_at))
			$this->updated_at = $now;

		if (isset($this->created_at) && $this->is_new_record())
			$this->created_at = $now;
	}

	/**
	 * Updates all the attributes from the passed-in array and saves the record. If the object is invalid,
	 * the saving will fail and false will be returned.
	 * @param $attributes array
	 * @return boolean
	 */
	public function update_attributes($attributes)
	{
		$this->set_attributes($attributes);
		return $this->save();
	}

	/**
	 * Updates a single attribute and saves the record without going through the normal validation procedure.
	 * @param string
	 * @param mixed
	 * @return boolean
	 */
	public function update_attribute($name, $value)
	{
		$this->__set($name, $value);
		return $this->update(false);
	}

	/**
	 * Allows you to set all the attributes at once by passing in an array with
	 * keys matching the attribute names (which again matches the column names).
	 * @param array
	 * @return void
	 */
	public function set_attributes($attributes)
	{
		$this->set_attributes_via_mass_assignment($attributes, true);
	}

	/**
	 * Passing strict as true will throw an exception if an attribute does not exist.
	 * @throws ActiveRecord\UndefinedPropertyException
	 * @param array
	 * @param boolean flag of whether or not attributes should be guarded
	 * @return unknown_type
	 */
	private function set_attributes_via_mass_assignment(&$attributes, $guard_attributes)
	{
		if (!is_array($attributes) || empty($attributes))
			return false;

		//access uninflected columns since that is what we would have in result set
		$table = static::table();
		$columns = array_merge($table->inflected,$table->columns);
		$exceptions = array();
		$use_attr_accessible = is_array(static::$attr_accessible) && count(static::$attr_accessible) > 0;
		$use_attr_protected = is_array(static::$attr_protected) && count(static::$attr_protected) > 0;

		foreach ($attributes as $name => $value)
		{
			if (array_key_exists($name,$columns))
			{
				$name = $columns[$name]->inflected_name;

				if (!$guard_attributes)
					$value = $columns[$name]->cast($value);
			}

			if ($guard_attributes)
			{
				if ($use_attr_accessible && !in_array($name,static::$attr_accessible))
					continue;

				if ($use_attr_protected && in_array($name,static::$attr_protected))
					continue;

				try {
					$this->$name = $value;
				} catch (UndefinedPropertyException $e) {
					$exceptions[] = $e->getMessage();
				}
			}
			else
				$this->attributes[$name] = $value;
		}

		if (!empty($exceptions))
			throw new UndefinedPropertyException($exceptions);
	}

	/**
	 * Reloads the attributes of this object from the database and the relationships.
	 * Returns $this to support:
	 *
	 * $model->reload()->relationship_name->attribute
	 * $model->reload()->attribute
	 *
	 * @return $this
	 */
	public function reload()
	{
		$this->__relationships = array();
		$pk = array_values($this->get_values_for($this->get_primary_key()));
		$this->set_attributes($this->find($pk)->attributes);
		$this->reset_dirty();

		return $this;
	}

	/**
	 * Resets @var $__dirty to null
	 * @return void
	 */
	public function reset_dirty()
	{
		$this->__dirty = null;
	}

	/**
	 * A list of valid finder options
	 * @static
	 * @var array
	 */
	static $VALID_OPTIONS = array('conditions', 'limit', 'offset', 'order', 'select', 'joins', 'include', 'readonly', 'group');

	/**
	 * Enables the use of dynamic finders.
	 * Example: SomeModel::find_by_attribute('value');
	 * @static
	 * @throws ActiveRecord\ActiveRecordException if invalid query
	 * @param string
	 * @param mixed
	 * @return instance of ActiveRecord\Model
	 */
	public static function __callStatic($method, $args)
	{
		$options = static::extract_and_validate_options($args);

		if (substr($method,0,7) === 'find_by')
		{
			$options['conditions'] = SQLBuilder::create_conditions_from_underscored_string(substr($method,8),$args);
			return static::find('first',$options);
		}
		elseif (substr($method,0,11) === 'find_all_by')
		{
			$options['conditions'] = SQLBuilder::create_conditions_from_underscored_string(substr($method,12),$args);
			return static::find('all',$options);
		}

		throw new ActiveRecordException("Call to undefined method: $method");
	}

	/**
	 * Enables the use of build|create for associations.
	 * @param string
	 * @param mixed
	 * @return instance of a given ActiveRecord\Relationship
	 */
	public function __call($method, $args)
	{
		//check for build|create_association methods
		if (preg_match('/(build|create)_/', $method))
		{
			if (!empty($args))
				$args = $args[0];

			$association_name = str_replace(array('build_', 'create_'), '', $method);
			if (($association = $this->table()->get_relationship($association_name)))
			{
				//access association to ensure that the relationship has been loaded
				//so that we do not double-up on records if we append a newly created
				$this->$association_name;
				$method = str_replace($association_name,'association', $method);
				return $association->$method($this, $args);
			}
		}
	}

	/**
	 * Alias for self::find('all')
	 * @static
	 * @return array
	 */
	public static function all(/* ... */)
	{
		return call_user_func_array('static::find',array_merge(array('all'),func_get_args()));
	}

	/**
	 * Get a count of qualifying records
	 * @static
	 * @return integer
	 */
	public static function count(/* ... */)
	{
		$args = func_get_args();
		$options = static::extract_and_validate_options($args);
		$options['select'] = 'COUNT(*) AS n';

		$row = call_user_func_array('static::find',array_merge(array('first'),$args,array($options)));
		return $row->attributes['n'];
	}

	/**
	 * Uses count to determine whether a qualifying record exists or not.
	 * @static
	 * @return boolean
	 */
	public static function exists(/* ... */)
	{
		return call_user_func_array('static::count',func_get_args()) > 0 ? true : false;
	}

	/**
	 * Alias for self::find('first')
	 * @static
	 * @return object instanceof ActiveRecord\Model
	 */
	public static function first(/* ... */)
	{
		return call_user_func_array('static::find',array_merge(array('first'),func_get_args()));
	}

	/**
	 * Alias for self::find('last')
	 * @return object instanceof ActiveRecord\Model
	 */
	public static function last(/* ... */)
	{
		return call_user_func_array('static::find',array_merge(array('last'),func_get_args()));
	}

	/**
	 * Base method for retrieving records from the database.
	 * @static
	 * @throws ActiveRecord\RecordNotFound if no options are passed
	 * @return mixed instance(s) of ActiveRecord\Model
	 */
	public static function find(/* $type, $options */)
	{
		$class = get_called_class();

		if (func_num_args() <= 0)
			throw new RecordNotFound("Couldn't find $class without an ID");

		$args = func_get_args();
		$options = static::extract_and_validate_options($args);
		$num_args = count($args);
		$single = true;

		if ($num_args > 0 && ($args[0] == 'all' || $args[0] == 'first' || $args[0] == 'last'))
		{
			switch ($args[0])
			{
				case 'all':
					$single = false;
					unset($options['limit']);
					unset($options['offset']);
					break;

			 	case 'last':
					if (!array_key_exists('order',$options))
						$options['order'] = join(' DESC, ',static::table()->pk) . ' DESC';
					else
						$options['order'] = SQLBuilder::reverse_order($options['order']);

					// fall thru

			 	case 'first':
			 		$options['limit'] = 1;
			 		$options['offset'] = 0;
			 		break;
			}

			$args = array_slice($args,1);
			$num_args--;
		}
		//find by pk
		elseif (1 === count($args) && 1 == $num_args)
			$args = $args[0];

		// anything left in $args is a find by pk
		if ($num_args > 0)
			return static::find_by_pk($args, $options);

		$list = static::table()->find($options);

		return $single ? (count($list) > 0 ? $list[0] : null) : $list;
	}

	/**
	 * Finder method which will find by a single or array of primary keys for this model.
	 * @static
	 * @throws ActiveRecord\RecordNotFound if a record could not be found with the @param $values passed
	 * @param mixed
	 * @param mixed
	 * @return unknown_type
	 */
	public static function find_by_pk($values, $options)
	{
		if (($expected = count($values)) <= 1)
		{
			$options['limit'] = 1;
			$options['offset'] = 0;
		}

		$options['conditions'] = static::pk_conditions($values);

		$list = static::table()->find($options);
		$results = count($list);

		if ($results != $expected)
		{
			$class = get_called_class();

			if ($expected == 1)
			{
				if (!is_array($values))
					$values = array($values);

				throw new RecordNotFound("Couldn't find $class with ID=" . join(',',$values));
			}

			$values = join(',',$values);
			throw new RecordNotFound("Couldn't find all $class with IDs ($values) (found $results, but was looking for $expected)");
		}
		return $expected == 1 ? $list[0] : $list;
	}

	/**
	 * Allows you to pass raw sql to be executed. Use only if feeling evil; although, this method
	 * will still escape the raw sql to prevent SQL injection.
	 * @static
	 * @param string
	 * @return array
	 */
	public static function find_by_sql($sql)
	{
		return static::table()->find_by_sql($sql, null, true);
	}

	/**
	 * Returns true if @var $array is a valid options hash. Will throw exceptions
	 * if it is a hash and contains invalid option keys.
	 * @throws ActiveRecord\ActiveRecordException
	 * @return boolean
	 */
	public static function is_options_hash($array)
	{
		if (is_hash($array))
		{
			$keys = array_keys($array);

			if (count(($diff = array_diff($keys,self::$VALID_OPTIONS))) > 0)
				throw new ActiveRecordException("Unknown key(s): " . join(', ',$diff));

			if (count(array_intersect($keys,self::$VALID_OPTIONS)) > 0)
				return true;
		}
		return false;
	}

	/**
	 *
	 * @param $args
	 * @return unknown_type
	 */
	public static function pk_conditions($args)
	{
		$table = static::table();
		$ret = array($table->pk[0] => $args);
		return $ret;
	}

	/**
	 * Pulls out the options hash from $array if any.	 *
	 * DO NOT remove the reference on $array.
	 * @param array
	 * @return array
	 */
	public static function extract_and_validate_options(&$array)
	{
		$options = array();

		if ($array && is_array($array))
		{
			$last = &$array[count($array)-1];

			if (self::is_options_hash($last))
			{
				array_pop($array);
				$options = $last;
			}
		}
		return $options;
	}

	/**
	 * Returns a JSON representation of this model.
	 * @param array
	 * @return string
	 */
	public function to_json($options=array())
	{
		return $this->serialize('Json', $options);
	}

	/**
	 * Returns an XML representation of this model.
	 * @param array
	 * @return string
	 */
	public function to_xml($options=array())
	{
		return $this->serialize('Xml', $options);
	}

	/**
	 * Creates a serializer based on pre-defined to_serializer()
	 * Use options['only'] and options['except'] to include/exclude desired attributes.
	 * @param string
	 * @param array
	 * @return string
	 */
	private function serialize($type, $options)
	{
		$class = "ActiveRecord\\{$type}Serializer";
		$serializer = new $class($this, $options);
		return $serializer->to_s();
	}

	/**
	 * Invokes the specified callback on this model.
	 * @param string $method_name Name of the call back to run.
	 * @param boolean $must_exist Set to true to raise an exception if the callback does not exist.
	 * @return boolean or null
	 */
	private function invoke_callback($method_name, $must_exist=true)
	{
		return $this->table()->callback->invoke($this,$method_name,$must_exist);
	}
};
?>