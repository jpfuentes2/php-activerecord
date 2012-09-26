<?php
namespace ActiveRecord\Exceptions;
 

/**
 * Thrown when there was an error performing a database operation.
 *
 * The error will be specific to whatever database you are running.
 *
 * @package ActiveRecord\Exceptions
 */
class DatabaseException extends Exception
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
?>