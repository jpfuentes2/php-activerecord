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
	 * The Table where the relationship originates from; used to look up
	 * any 'through' relationship.
	 */
	protected $from_table;

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
		
		$this->from_table = $options['from'];
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
			$options['joins'] = $relation->construct_join_sql(FALSE /* dunno? */);

			$query_key = $this->primary_key[0];
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
	protected function set_inferred_class_name()
	{
		$singularize = ($this instanceOf HasMany ? true : false);
		$this->set_class_name(classify($this->attribute_name, $singularize));
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
	 * @param bool $reverse_rel TRUE if we have already joined the 
	 *   table at the end of this relationship, and we want to join the
	 *   far side to restrict the results; FALSE if we have not yet joined
	 *   the table and we are just following a relationship.
	 * @param string $alias a table alias for when a table is being joined twice
	 * @return string SQL INNER JOIN fragment
	 */
	public function construct_join_sql($reverse_rel, $alias=null,
		$join_type="INNER")
	{
		if (!empty($this->options['through']))
		{
			// We're joining between the intermediate and far table.
			// The far table is already joined because it's the main
			// table (it's the table that the results columns come from).
			// That makes it the "from" table. The "dest" table (the one
			// we're joining TO) is the intermediate table.
			$through_relationship = $this->from_table->get_relationship(
				$this->options['through'], TRUE);

			if ($reverse_rel)
			{
				$from_table = Table::load($this->class_name);
				$dest_table = $through_relationship->get_table();
			}
			else
			{
				$from_table = $through_relationship->get_table();
				$dest_table = Table::load($this->class_name);
			}
		}
		else
		{
			$from_table = $this->from_table;
			$dest_table = Table::load($this->class_name);
		}

		/*
		print "name = ".$this->attribute_name."\n";
		print "through = ".(isset($this->options['through'])?$this->options['through']:"NULL").
			", reverse=".($reverse_rel?"TRUE":"FALSE")."\n";
		print "before set_keys(".$this->class_name."): ".
			"primary key = ".$this->primary_key[0].", ".
			"foreign key = ".$this->foreign_key[0]."\n";
		*/
		
		$this->set_keys($this->class_name);

		$dest_table_name = $dest_table->get_fully_qualified_table_name();
		$from_table_name = $from_table->get_fully_qualified_table_name();

		/*
		print "after set_keys(".$this->class_name."): ".
			"primary key = ".$this->primary_key[0].", ".
			"foreign key = ".$this->foreign_key[0]."\n";
		print "from_table_name = $from_table_name\n";
		print "dest_table_name = $dest_table_name\n";
		*/
		
		// need to flip the logic when the key is on the other table
		// In the case of a reverse relationship, we're coming FROM
		// the end of this relationship, so the FROM table side is the
		// primary key, and the DEST side is the intermediate table, which
		// has the foreign key; so we want to take this path too.
		if (($this instanceof HasMany or $this instanceof HasOne)
			and (empty($this->options['through']) || $reverse_rel))
		{
			$from_table_key = $this->primary_key[0];
			$dest_table_key = $this->foreign_key[0];
		}
		else
		{
			$from_table_key = $this->foreign_key[0];
			$dest_table_key = $this->primary_key[0];
		}
		
		if (!is_null($alias))
		{
			$aliased_dest_table_name = $alias = $this->get_table()->conn->quote_name($alias);
			$alias .= ' ';
		}
		else
			$aliased_dest_table_name = $dest_table_name;

		return "$join_type JOIN $dest_table_name {$alias} ON ".
			"($from_table_name.$from_table_key = $aliased_dest_table_name.$dest_table_key)";
	}

	/**
	 * This will load the related model data.
	 *
	 * @param Model $model The model this relationship belongs to
	 */
	abstract function load(Model $model);

	public function get_through_relationship_name()
	{
		return NULL;
	}

	protected function set_keys($model_class_name)
	{
		//infer from class_name
		if (!$this->foreign_key)
			$this->foreign_key = array(Inflector::instance()->keyify($model_class_name));

		if (!$this->primary_key)
			$this->primary_key = Table::load($model_class_name)->pk;
	}
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

		if (!$this->primary_key && isset($this->options['primary_key']))
			$this->primary_key = is_array($this->options['primary_key']) ? $this->options['primary_key'] : array($this->options['primary_key']);

		if (!$this->class_name)
			$this->set_inferred_class_name();
	}
	
	public function get_through_relationship_name()
	{
		return $this->through;
	}

	public function load(Model $model)
	{
		$this->set_keys(get_class($model));
		$class_name = $this->class_name;

		$options = $this->unset_non_finder_options($this->options);

		if ($this->through)
		{
			$through_relation = $model::table()->get_relationship($this->through, TRUE);
			
			if (!isset($this->initialized))
			{
				// verify through is a belongs_to or has_many for access of keys
				
				if (!($through_relation instanceof HasMany) &&
					!($through_relation instanceof BelongsTo))
					throw new HasManyThroughAssociationException('has_many through can only use a belongs_to or has_many association');

				$options['joins'] = $this->construct_join_sql(TRUE
					/* we have already joined the model at the end of this
					relationship, so we are using it in reverse */);
				// print "joins = ".$options['joins']."\n";
			}
			
			$through_relation->set_keys(get_class($model));	
			$conditions = $through_relation->create_conditions_from_keys($model,
				$through_relation->foreign_key, $through_relation->primary_key);
		}
		else
		{
			$conditions = $this->create_conditions_from_keys($model,
				$this->foreign_key, $this->primary_key);
		}
		
		if (!$conditions)
			return null;

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

	public function load_eagerly($models=array(), $attributes=array(), $includes, Table $table)
	{
		$this->set_keys($table->class->name);
		$this->query_and_attach_related_models_eagerly($table,$models,$attributes,$includes,$this->foreign_key, $table->pk);
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
	public function __construct($options=array())
	{
		parent::__construct($options);

		if (!$this->class_name)
			$this->set_inferred_class_name();

		//infer from class_name
		if (!$this->foreign_key)
			$this->foreign_key = array(Inflector::instance()->keyify($this->class_name));
	}

	public function __get($name)
	{
		if($name === 'primary_key' && !isset($this->primary_key)) {
			$this->primary_key = array(Table::load($this->class_name)->pk[0]);
		}

		return $this->$name;
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

	public function load_eagerly($models=array(), $attributes, $includes, Table $table)
	{
		$this->query_and_attach_related_models_eagerly($table,$models,$attributes,$includes, $this->primary_key,$this->foreign_key);
	}
};
?>
