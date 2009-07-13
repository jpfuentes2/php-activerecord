<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * @package ActiveRecord
 * @subpackage Internal
 */
interface InterfaceRelationship
{
	public function __construct($options=array());
	public function build_association(Model $model, $attributes=array());
	public function create_association(Model $model, $attributes=array());
}

/**
 * @package ActiveRecord
 */
abstract class AbstractRelationship implements InterfaceRelationship
{
	public $attribute_name;
	public $class_name;
	public $foreign_key = array();
	static protected $valid_association_options = array('class_name', 'class', 'foreign_key', 'conditions', 'select', 'readonly');
	protected $options = array();
	protected $poly_relationship = false;

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
			$this->class_name = $this->options['class'];
		elseif (isset($this->options['class_name']))
			$this->class_name = $this->options['class_name'];

		$this->attribute_name = strtolower(Inflector::instance()->variablize($this->attribute_name));

		if (!$this->foreign_key && isset($this->options['foreign_key']))
			$this->foreign_key = is_array($this->options['foreign_key']) ? $this->options['foreign_key'] : array($this->options['foreign_key']);
	}

	/**
	 *	Creates a new instance of Model with the attributes pre-loaded
	 *
	 * @param ActiveRecord\Model the model which holds this association
	 * @param array $attributes
	 * @return ActiveRecord\Model
	 */
	public function build_association(Model $model, $attributes=array())
	{
		$class_name = $this->class_name;
		return new $class_name($attributes);
	}

	/**
	 * 	Creates a new instance of Model and invokes save
	 *
	 * @param ActiveRecord\Model the model which holds this association
	 * @param $attributes
	 * @return saved ActiveRecord\Model
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
		return strtolower(classify($class_name)). '_id';
	}

	/**
	 * Infers the $this->class_name based on $this->attribute_name
	 * Will try to guess the appropriate class by singularizing and uppercasing
	 * $this->attribute_name
	 *
	 * @return void
	 * @see $this->attribute_name
	 */
	protected function set_inferred_class_name()
	{
		$class_name = classify($this->attribute_name, true);
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
	 * @param $model The model this relationship belongs to
	 */
	abstract function load(Model $model);
};

/**
 * Has Many!
 * 
 * @package ActiveRecord
 */
class HasMany extends AbstractRelationship
{
	private $has_one = false;
	private $through;
	private $primary_key;
	static protected $valid_association_options = array('primary_key', 'order', 'group', 'having', 'limit', 'offset', 'through', 'source');

	public function __construct($options=array())
	{
		parent::__construct($options);

		if (isset($this->options['through']))
		{
			$this->through = $this->options['through'];
			if (isset($this->options['source']))
				$this->class_name = $this->options['source'];
		}

		if (!$this->class_name)
			$this->set_inferred_class_name();
	}

	private function get_table()
	{
		return Table::load($this->class_name);
	}

	private function set_keys(Model $model)
	{
		//infer from class_name
		if (!$this->foreign_key)
			$this->foreign_key = array($this->keyify(get_class($model)));

		if (!$this->primary_key)
			$this->primary_key = $model->get_primary_key();
	}

	public function load(Model $model)
	{
		$inflector = Inflector::instance();
		$class_name = $this->class_name;
		$table = $this->get_table();
		$this->set_keys($model);

		if ($this->through)
		{
			$table_name = $table->get_fully_qualified_table_name();

			//verify through is a belongs_to or has_many for access of keys
			if (!($through_relationship = $table->get_relationship($this->through)))
				throw new HasManyThroughAssociationException("Could not find the association $this->through in model ".get_class($model));

			if (!($through_relationship instanceof HasMany) && !($through_relationship instanceof BelongsTo))
				throw new HasManyThroughAssociationException('has_many through can only use a belongs_to or has_many association');

			$through_table = Table::load(classify($this->through, true));
			$through_table_name = $through_table->get_fully_qualified_table_name();
			$through_pk = $this->keyify($class_name);

			$this->options['joins'] = "INNER JOIN $through_table_name ON ($table_name.{$this->primary_key[0]} = $through_table_name.$through_pk)";

			if (!isset($this->options['select']))
				$this->options['select'] = "$table_name.*";

			foreach ($this->foreign_key as $index => &$key)
				$key = "$through_table_name.$key";
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
 * @package ActiveRecord
 */
class HasOne extends HasMany
{

};

/**
 * @package ActiveRecord
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
 * @package ActiveRecord
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
