<?php
/**
 * @package Speedy\ActiveRecord
 */
namespace Speedy\ActiveRecord\Relationships;



use Speedy\ActiveRecord\Inflector;
use Speedy\ActiveRecord\Reflections;
use Speedy\ActiveRecord\Model;
use Speedy\ActiveRecord\Table;
use Speedy\ActiveRecord\SQLBuilder;
use Speedy\ActiveRecord\Exceptions\RelationshipException;

/**
 * Interface for a table relationship.
 *
 * @package Speedy\ActiveRecord
 */
interface InterfaceRelationship
{
	public function __construct($options=array());
	public function build_association(Model $model, $attributes=array());
	public function create_association(Model $model, $attributes=array());
}

/**
 * Abstract class that all relationships must extend from.
 *
 * @package Speedy\ActiveRecord
 * @see http://www.phpactiverecord.org/guides/associations
 */
abstract class Relationship implements InterfaceRelationship
{
	/**
	 * Name to be used that will trigger call to the relationship.
	 *
	 * @var string
	 */
	public $attribute_name;

	/**
	 * Class name of the associated model.
	 *
	 * @var string
	 */
	public $class_name;

	/**
	 * Name of the foreign key.
	 *
	 * @var string
	 */
	public $foreign_key = array();

	/**
	 * Options of the relationship.
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Is the relationship single or multi.
	 *
	 * @var boolean
	 */
	protected $poly_relationship = false;

	/**
	 * List of valid options for relationships.
	 *
	 * @var array
	 */
	static protected $valid_association_options = array('class_name', 'class', 'foreign_key', 'conditions', 'select', 'readonly', 'namespace');

	/**
	 * Constructs a relationship.
	 *
	 * @param array $options Options for the relationship (see {@link valid_association_options})
	 * @return mixed
	 */
	public function __construct($options=array())
	{
		$this->attribute_name = $options[0];
		$this->options = $this->merge_association_options($options);

		$relationship = strtolower(\Speedy\ActiveRecord\denamespace(get_called_class()));

		if ($relationship === 'hasmany' || $relationship === 'hasandbelongstomany')

		if (isset($this->options['conditions']) && !is_array($this->options['conditions']))
			$this->options['conditions'] = array($this->options['conditions']);

		if (isset($this->options['class']))
			$this->set_class_name($this->options['class']);
		elseif (isset($this->options['class_name']))
			$this->set_class_name($this->options['class_name']);

		$this->attribute_name = strtolower(Inflector::instance()->variablize($this->attribute_name));

		if (!$this->foreign_key && isset($this->options['foreign_key']))
			$this->foreign_key = is_array($this->options['foreign_key']) ? $this->options['foreign_key'] : array($this->options['foreign_key']);
	}

	protected function get_table()
	{
		return Table::load($this->class_name);
	}

	/**
	 * What is this relationship's cardinality?
	 *
	 * @return bool
	 */
	public function is_poly()
	{
		return $this->poly_relationship;
	}

	/**
	 * Eagerly loads relationships for $models.
	 *
	 * This method takes an array of models, collects PK or FK (whichever is needed for relationship), then queries
	 * the related table by PK/FK and attaches the array of returned relationships to the appropriately named relationship on
	 * $models.
	 *
	 * @param Table $table
	 * @param $models array of model objects
	 * @param $attributes array of attributes from $models
	 * @param $includes array of eager load directives
	 * @param $query_keys -> key(s) to be queried for on included/related table
	 * @param $model_values_keys -> key(s)/value(s) to be used in query from model which is including
	 * @return void
	 */
	protected function query_and_attach_related_models_eagerly(Table $table, $models, $attributes, $includes=array(), $query_keys=array(), $model_values_keys=array())
	{
		$values = array();
		$options = $this->options;
		$inflector = Inflector::instance();
		$query_key = $query_keys[0];
		$model_values_key = $model_values_keys[0];

		foreach ($attributes as $column => $value)
			$values[] = $value[$inflector->variablize($model_values_key)];

		$values = array($values);
		$conditions = SQLBuilder::create_conditions_from_underscored_string($table->conn,$query_key,$values);

		if (isset($options['conditions']) && strlen($options['conditions'][0]) > 1)
			Utils::add_condition($options['conditions'], $conditions);
		else
			$options['conditions'] = $conditions;

		if (!empty($includes))
			$options['include'] = $includes;

		if (!empty($options['through'])) {
			// save old keys as we will be reseting them below for inner join convenience
			$pk = $this->primary_key;
			$fk = $this->foreign_key;

			$this->set_keys($this->get_table()->class->getName(), true);

			if (!isset($options['class_name'])) {
				$class = classify($options['through'], true);
				if (isset($this->options['namespace']) && !class_exists($class))
					$class = $this->options['namespace'].'\\'.$class;

				$through_table = $class::table();
			} else {
				$class = $options['class_name'];
				$relation = $class::table()->get_relationship($options['through']);
				$through_table = $relation->get_table();
			}
			$options['joins'] = $this->construct_inner_join_sql($through_table, true);

			$query_key = $this->primary_key[0];

			// reset keys
			$this->primary_key = $pk;
			$this->foreign_key = $fk;
		}

		$options = $this->unset_non_finder_options($options);

		$class = $this->class_name;

		$related_models = $class::find('all', $options);
		$used_models = array();
		$model_values_key = $inflector->variablize($model_values_key);
		$query_key = $inflector->variablize($query_key);

		foreach ($models as $model)
		{
			$matches = 0;
			$key_to_match = $model->$model_values_key;

			foreach ($related_models as $related)
			{
				if ($related->$query_key == $key_to_match)
				{
					$hash = spl_object_hash($related);

					if (in_array($hash, $used_models))
						$model->set_relationship_from_eager_load(clone($related), $this->attribute_name);
					else
						$model->set_relationship_from_eager_load($related, $this->attribute_name);

					$used_models[] = $hash;
					$matches++;
				}
			}

			if (0 === $matches)
				$model->set_relationship_from_eager_load(null, $this->attribute_name);
		}
	}

	/**
	 * Creates a new instance of specified {@link Model} with the attributes pre-loaded.
	 *
	 * @param Model $model The model which holds this association
	 * @param array $attributes Hash containing attributes to initialize the model with
	 * @return Model
	 */
	public function build_association(Model $model, $attributes=array())
	{
		$class_name = $this->class_name;
		return new $class_name($attributes);
	}

	/**
	 * Creates a new instance of {@link Model} and invokes save.
	 *
	 * @param Model $model The model which holds this association
	 * @param array $attributes Hash containing attributes to initialize the model with
	 * @return Model
	 */
	public function create_association(Model $model, $attributes=array())
	{
		$class_name = $this->class_name;
		$new_record = $class_name::create($attributes);
		return $this->append_record_to_associate($model, $new_record);
	}

	protected function append_record_to_associate(Model $associate, Model $record)
	{
		$association =& $associate->{$this->attribute_name};

		if ($this->poly_relationship)
			$association[] = $record;
		else
			$association = $record;

		return $record;
	}

	protected function merge_association_options($options)
	{
		$available_options = array_merge(self::$valid_association_options,static::$valid_association_options);
		$valid_options = array_intersect_key(array_flip($available_options),$options);

		foreach ($valid_options as $option => $v)
			$valid_options[$option] = $options[$option];

		return $valid_options;
	}

	protected function unset_non_finder_options($options)
	{
		foreach (array_keys($options) as $option)
		{
			if (!in_array($option, Model::$VALID_OPTIONS))
				unset($options[$option]);
		}
		return $options;
	}

	/**
	 * Infers the $this->class_name based on $this->attribute_name.
	 *
	 * Will try to guess the appropriate class by singularizing and uppercasing $this->attribute_name.
	 *
	 * @return void
	 * @see attribute_name
	 */
	protected function set_inferred_class_name($model = null)
	{
		$ns 	= ($model && !isset($this->options['namespace'])) ? get_namespace($model) : null;
		$class	= $this->get_inferred_class_name();
		if ($ns) $class = '\\' . $ns . '\\' . $class;
		
		$this->set_class_name($class);
	}
	
	protected function get_inferred_class_name()
	{
		$singularize = ($this instanceOf HasMany ? true : false);
		return \Speedy\ActiveRecord\classify($this->attribute_name, $singularize);
	}

	protected function set_class_name($class_name)
	{
		try {
			$reflection = Reflections::instance()->add($class_name)->get($class_name);
		} catch (\ReflectionException $e) {
			if (isset($this->options['namespace'])) {
				$class_name = $this->options['namespace'].'\\'.$class_name;
				$reflection = Reflections::instance()->add($class_name)->get($class_name);
			} else {
				throw $e;
			}
		}

		if (!$reflection->isSubClassOf('Speedy\\ActiveRecord\\Model'))
			throw new RelationshipException("'$class_name' must extend from Speedy\ActiveRecord\\Model");

		$this->class_name = $class_name;
	}
	
	public function class_name() {
		if (!$this->class_name) {
			$this->class_name = $this->get_inferred_class_name();
			if (isset($this->options['namespace'])) 
				$this->class_name = $this->options['namespace'] . '\\' . $this->class_name;
		}
		
		return $this->class_name;
	}

	protected function create_conditions_from_keys(Model $model, $condition_keys=array(), $value_keys=array())
	{
		$condition_string = implode('_and_', $condition_keys);
		$condition_values = array_values($model->get_values_for($value_keys));

		// return null if all the foreign key values are null so that we don't try to do a query like "id is null"
		if (\Speedy\ActiveRecord\all(null,$condition_values))
			return null;

		$conditions = SQLBuilder::create_conditions_from_underscored_string(Table::load(get_class($model))->conn,$condition_string,$condition_values);

		# DO NOT CHANGE THE NEXT TWO LINES. add_condition operates on a reference and will screw options array up
		if (isset($this->options['conditions']))
			$options_conditions = $this->options['conditions'];
		else
			$options_conditions = array();

		return Utils::add_condition($options_conditions, $conditions);
	}

	/**
	 * Creates INNER JOIN SQL for associations.
	 *
	 * @param Table $from_table the table used for the FROM SQL statement
	 * @param bool $using_through is this a THROUGH relationship?
	 * @param string $alias a table alias for when a table is being joined twice
	 * @return string SQL INNER JOIN fragment
	 */
	public function construct_inner_join_sql(Table $from_table, $using_through=false, $alias=null)
	{
		if ($using_through)
		{
			$join_table = $from_table;
			$join_table_name = $from_table->get_fully_qualified_table_name();
			$from_table_name = Table::load($this->class_name)->get_fully_qualified_table_name();
 		}
		else
		{
			$join_table = Table::load($this->class_name);
			$join_table_name = $join_table->get_fully_qualified_table_name();
			$from_table_name = $from_table->get_fully_qualified_table_name();
		}

		// need to flip the logic when the key is on the other table
		if ($this instanceof HasMany || $this instanceof HasOne)
		{
			$this->set_keys($from_table->class->getName());

			if ($using_through)
			{
				$foreign_key = $this->primary_key[0];
				$join_primary_key = $this->foreign_key[0];
			}
			else
			{
				$join_primary_key = $this->foreign_key[0];
				$foreign_key = $this->primary_key[0];
			}
		}
		else
		{
			$foreign_key = $this->foreign_key[0];
			$join_primary_key = $this->primary_key[0];
		}

		if (!is_null($alias))
		{
			$aliased_join_table_name = $alias = $this->get_table()->conn->quote_name($alias);
			$alias .= ' ';
		}
		else
			$aliased_join_table_name = $join_table_name;

		return "INNER JOIN $join_table_name {$alias}ON ($from_table_name.$foreign_key = $aliased_join_table_name.$join_primary_key)";
	}

	/**
	 * This will load the related model data.
	 *
	 * @param Model $model The model this relationship belongs to
	 */
	abstract function load(Model $model);
};








?>
