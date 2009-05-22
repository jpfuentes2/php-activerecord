<?php
namespace ActiveRecord;

class ActiveRecordException extends \Exception {};

class RecordNotFound extends ActiveRecordException {};

class DatabaseException extends ActiveRecordException {};

class ModelException extends ActiveRecordException {};

class ExpressionsException extends ActiveRecordException {};

class ConfigException extends ActiveRecordException {};

class UndefinedPropertyException extends ModelException
{
	/**
	 * Sets the exception message to show the undefined property's name
	 *
	 * @param str $property_name name of undefined property
	 * @return void
	 */
	public function __construct($property_name)
	{
		if (is_array($property_name))
		{
			$this->message = implode("\r\n", $property_name);
			return;
		}

		$this->message = "Undefined property: $property_name in {$this->file} on line {$this->line}";
	}
};

class ReadOnlyException extends ModelException
{
	/**
	 * Sets the exception message to show the undefined property's name
	 *
	 * @param str $class_name name of the model that is read only
	 * @param str $method_name name of method which attempted to modify the model
	 * @return void
	 */
	public function __construct($class_name, $method_name)
	{
		$this->message = "Model ".get_class($this)." cannot be $method_name because it is set to read only";
	}
};

class ValidationsArgumentError extends ActiveRecordException {};



namespace ActiveRecord\Relationship;
use ActiveRecord;

class RelationshipException extends ActiveRecord\ActiveRecordException {};

class HasManyThroughAssociationException extends RelationshipException {};
?>