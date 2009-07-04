<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * @package ActiveRecord
 */
class ActiveRecordException extends \Exception {};

/**
 * @package ActiveRecord
 */
class RecordNotFound extends ActiveRecordException {};

/**
 * @package ActiveRecord
 */
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

/**
 * @package ActiveRecord
 */
class ModelException extends ActiveRecordException {};

/**
 * @package ActiveRecord
 */
class ExpressionsException extends ActiveRecordException {};

/**
 * @package ActiveRecord
 */
class ConfigException extends ActiveRecordException {};

/**
 * @package ActiveRecord
 */
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

/**
 * @package ActiveRecord
 */
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

/**
 * @package ActiveRecord
 */
class ValidationsArgumentError extends ActiveRecordException {};

/**
 * @package ActiveRecord
 */
class RelationshipException extends ActiveRecordException {};

/**
 * @package ActiveRecord
 */
class HasManyThroughAssociationException extends RelationshipException {};
?>