<?php 
namespace Speedy\ActiveRecord\Relationships;
/**
 * Belongs to relationship.
 *
 * <code>
 * class School extends Speedy\ActiveRecord\Model {}
 *
 * class Person extends Speedy\ActiveRecord\Model {
 *   static $belongs_to = array(
 *     array('school')
 *   );
 * }
 * </code>
 *
 * Example using options:
 *
 * <code>
 * class School extends Speedy\ActiveRecord\Model {}
 *
 * class Person extends Speedy\ActiveRecord\Model {
 *   static $belongs_to = array(
 *     array('school', 'primary_key' => 'school_id')
 *   );
 * }
 * </code>
 *
 * @package Speedy\ActiveRecord
 * @see valid_association_options
 * @see http://www.phpactiverecord.org/guides/associations
 */


use Speedy\ActiveRecord\Inflector;

class BelongsTo extends Relationship
{

	public function __construct($options = [])
	{
		parent::__construct($options);

		if (!$this->class_name)
			$this->set_inferred_class_name();

		//infer from class_name
		if (!$this->foreign_key)
			$this->foreign_key = array(Inflector::instance()->keyify($this->class_name));

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

	public function load_eagerly($models=array(), $attributes, $includes, Table $table)
	{
		$this->query_and_attach_related_models_eagerly($table,$models,$attributes,$includes, $this->primary_key,$this->foreign_key);
	}
};
?>