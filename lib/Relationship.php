<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * Interface for a table relationship.
 *
 * @package ActiveRecord
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
 * @package ActiveRecord
 * @see http://www.phpactiverecord.org/guides/associations
 */
abstract class AbstractRelationship implements InterfaceRelationship
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
	static protected $valid_association_options = array('class_name', 'class', 'foreign_key', 'conditions', 'select', 'readonly');

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

		$relationship = strtolower(denamespace(get_called_class()));

		if ($relationship === 'hasmany' || $relationship === 'hasandbelongstomany')
			$this->poly_relationship = true;

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

	protected function keyify($class_name)
	{
		return strtolower(classify(denamespace($class_name))). '_id';
	}

	/**
	 * Infers the $this->class_name based on $this->attribute_name.
	 *
	 * Will try to guess the appropriate class by singularizing and uppercasing $this->attribute_name.
	 *
	 * @return void
	 * @see attribute_name
	 */
	protected function set_inferred_class_name()
	{
		$this->set_class_name(classify($this->attribute_name, true));
	}

	protected function set_class_name($class_name)
	{
		$reflection = Reflections::instance()->add($class_name)->get($class_name);

		if (!$reflection->isSubClassOf('ActiveRecord\\Model'))
			throw new RelationshipException("'$class_name' must extend from ActiveRecord\\Model");

		$this->class_name = $class_name;
	}

	protected function create_conditions_from_keys(Model $model, $condition_keys=array(), $value_keys=array())
	{
		$condition_string = implode('_and_', $condition_keys);
		$condition_values = array_values($model->get_values_for($value_keys));

		// return null if all the foreign key values are null so that we don't try to do a query like "id is null"
		if (all(null,$condition_values))
			return null;

		$conditions = SQLBuilder::create_conditions_from_underscored_string($condition_string,$condition_values);

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
	 * @return string SQL INNER JOIN fragment
	 */
	public function construct_inner_join_sql(Table $from_table, $using_through=false)
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
				$join_primary_key = $this->keyify($this->class_name);
			else
				$join_primary_key = $this->foreign_key[0];

			$foreign_key = $this->primary_key[0];
		}
		else
		{
			$foreign_key = $this->foreign_key[0];
			$join_primary_key = $this->primary_key[0];
		}

		return "INNER JOIN $join_table_name ON($from_table_name.$foreign_key = $join_table_name.$join_primary_key)";
	}

	/**
	 * This will load the related model data.
	 *
	 * @param Model $model The model this relationship belongs to
	 */
	abstract function load(Model $model);
};

/**
 * One-to-many relationship.
 *
 * <code>
 * # Table: people
 * # Primary key: id
 * # Foreign key: school_id
 * class Person extends ActiveRecord\Model {}
 *
 * # Table: schools
 * # Primary key: id
 * class School extends ActiveRecord\Model {
 *   static $has_many = array(
 *     array('people')
 *   );
 * });
 * </code>
 *
 * Example using options:
 *
 * <code>
 * class Payment extends ActiveRecord\Model {
 *   static $belongs_to = array(
 *     array('person'),
 *     array('order')
 *   );
 * }
 *
 * class Order extends ActiveRecord\Model {
 *   static $has_many = array(
 *     array('people',
 *           'through'    => 'payments',
 *           'select'     => 'people.*, payments.amount',
 *           'conditions' => 'payments.amount < 200')
 *     );
 * }
 * </code>
 *
 * @package ActiveRecord
 * @see http://www.phpactiverecord.org/guides/associations
 * @see valid_association_options
 */
class HasMany extends AbstractRelationship
{
	/**
	 * Valid options to use for a {@link HasMany} relationship.
	 *
	 * <ul>
	 * <li><b>limit/offset:</b> limit the number of records</li>
     * <li><b>primary_key:</b> name of the primary_key of the association (defaults to "id")</li>
     * <li><b>group:</b> GROUP BY clause</li>
     * <li><b>order:</b> ORDER BY clause</li>
     * <li><b>through:</b> name of a model</li>
     * </ul>
	 *
	 * @var array
	 */
	static protected $valid_association_options = array('primary_key', 'order', 'group', 'having', 'limit', 'offset', 'through', 'source');

	protected $primary_key;

	private $has_one = false;
	private $through;

	/**
	 * Constructs a {@link HasMany} relationship.
	 *
	 * @param array $options Options for the association
	 * @return HasMany
	 */
	public function __construct($options=array())
	{
		parent::__construct($options);

		if (isset($this->options['through']))
		{
			$this->through = $this->options['through'];

			if (isset($this->options['source']))
				$this->set_class_name($this->options['source']);
		}

		if (!$this->class_name)
			$this->set_inferred_class_name();
	}

	private function get_table()
	{
		return Table::load($this->class_name);
	}

	protected function set_keys($model_class_name)
	{
		//infer from class_name
		if (!$this->foreign_key)
			$this->foreign_key = array($this->keyify($model_class_name));

		if (!$this->primary_key)
			$this->primary_key = Table::load($model_class_name)->pk;
	}

	public function load(Model $model)
	{
		$class_name = $this->class_name;
		$this->set_keys(get_class($model));

		// since through relationships depend on other relationships we can't do
		// this initiailization in the constructor since the other relationship
		// may not have been created yet and we only want this to run once
		if (!isset($this->initialized))
		{
			if ($this->through)
			{
				// verify through is a belongs_to or has_many for access of keys
				if (!($through_relationship = $this->get_table()->get_relationship($this->through)))
					throw new HasManyThroughAssociationException("Could not find the association $this->through in model " . get_class($model));

				if (!($through_relationship instanceof HasMany) && !($through_relationship instanceof BelongsTo))
					throw new HasManyThroughAssociationException('has_many through can only use a belongs_to or has_many association');

				$through_table = Table::load(classify($this->through, true));
				$through_table_name = $through_table->get_fully_qualified_table_name();

				$this->options['joins'] = $this->construct_inner_join_sql($through_table, true);

				foreach ($this->foreign_key as $index => &$key)
					$key = "$through_table_name.$key";
			}

			$this->initialized = true;
		}

		if (!($conditions = $this->create_conditions_from_keys($model, $this->foreign_key, $this->primary_key)))
			return null;

		$options = $this->unset_non_finder_options($this->options);
		$options['conditions'] = $conditions;
		return $class_name::find($this->poly_relationship ? 'all' : 'first',$options);
	}

	private function inject_foreign_key_for_new_association(Model $model, &$attributes)
	{
		$this->set_keys($model);
		$primary_key = Inflector::instance()->variablize($this->foreign_key[0]);

		if (!isset($attributes[$primary_key]))
			$attributes[$primary_key] = $model->id;

		return $attributes;
	}

	public function build_association(Model $model, $attributes=array())
	{
		$attributes = $this->inject_foreign_key_for_new_association($model, $attributes);
		return parent::build_association($model, $attributes);
	}

	public function create_association(Model $model, $attributes=array())
	{
		$attributes = $this->inject_foreign_key_for_new_association($model, $attributes);
		return parent::create_association($model, $attributes);
	}
};

/**
 * One-to-one relationship.
 *
 * <code>
 * # Table name: states
 * # Primary key: id
 * class State extends ActiveRecord\Model {}
 *
 * # Table name: people
 * # Foreign key: state_id
 * class Person extends ActiveRecord\Model {
 *   static $has_one = array(array('state'));
 * }
 * </code>
 *
 * @package ActiveRecord
 * @see http://www.phpactiverecord.org/guides/associations
 */
class HasOne extends HasMany
{
};

/**
 * @todo implement me
 * @package ActiveRecord
 * @see http://www.phpactiverecord.org/guides/associations
 */
class HasAndBelongsToMany extends AbstractRelationship
{
	public function __construct($options=array())
	{
		/* options =>
		 *   join_table - name of the join table if not in lexical order
		 *   foreign_key -
		 *   association_foreign_key - default is {assoc_class}_id
		 *   uniq - if true duplicate assoc objects will be ignored
		 *   validate
		 */
	}

	public function load(Model $model)
	{

	}
};

/**
 * Belongs to relationship.
 *
 * <code>
 * class School extends ActiveRecord\Model {}
 *
 * class Person extends ActiveRecord\Model {
 *   static $belongs_to = array(
 *     array('school')
 *   );
 * }
 * </code>
 *
 * Example using options:
 *
 * <code>
 * class School extends ActiveRecord\Model {}
 *
 * class Person extends ActiveRecord\Model {
 *   static $belongs_to = array(
 *     array('school', 'primary_key' => 'school_id')
 *   );
 * }
 * </code>
 *
 * @package ActiveRecord
 * @see valid_association_options
 * @see http://www.phpactiverecord.org/guides/associations
 */
class BelongsTo extends AbstractRelationship
{
	/**
	 * Valid options to use:
	 *
	 * <ul>
	 * <li><b>primary_key:</b> name of the primary_key of the association (defaults to "id")</li>
	 * </ul>
	 *
	 * @var array
	 */
	static protected $valid_association_options = array('primary_key');

	public function __construct($options=array())
	{
		parent::__construct($options);

		if (!$this->class_name)
			$this->set_inferred_class_name();

		//infer from class_name
		if (!$this->foreign_key)
			$this->foreign_key = array($this->keyify($this->class_name));

		$this->primary_key = array(Table::load($this->class_name)->pk[0]);
	}

	public function load(Model $model)
	{
		$keys = array();
		$inflector = Inflector::instance();

		foreach ($this->foreign_key as $key)
			$keys[] = $inflector->variablize($key);

		if (!($conditions = $this->create_conditions_from_keys($model, $this->primary_key, $keys)))
			return null;

		$options = $this->unset_non_finder_options($this->options);
		$options['conditions'] = $conditions;
		$class = $this->class_name;
		return $class::first($options);
	}
};
?>
