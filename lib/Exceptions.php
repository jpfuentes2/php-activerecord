<?php
namespace ActiveRecord;

class ActiveRecordException extends \Exception {};

class RecordNotFound extends ActiveRecordException {};

class DatabaseException extends ActiveRecordException
{
	public function __construct($adapter_or_string_or_mystery)
	{
		if ($adapter_or_string_or_mystery instanceof Connection)
		{
			parent::__construct(
				join(", ",$adapter_or_string_or_mystery->connection->errorInfo()),
				intval($adapter_or_string_or_mystery->connection->errorCode()));
		}
		elseif ($adapter_or_string_or_mystery instanceof \PDOStatement)
		{
			parent::__construct(
				join(", ",$adapter_or_string_or_mystery->errorInfo()),
				intval($adapter_or_string_or_mystery->errorCode()));
		}
		else
			parent::__construct($adapter_or_string_or_mystery);
	}
};

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
		parent::__construct();
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
		parent::__construct();
	}
};

class ValidationsArgumentError extends ActiveRecordException {};

class RelationshipException extends ActiveRecordException {};
class HasManyThroughAssociationException extends RelationshipException {};
?>