<?php 
namespace Speedy\ActiveRecord\Exceptions;


/**
 * Thrown when attempting to access an invalid property on a {@link Model}.
 *
 * @package Speedy\ActiveRecord
 */
class UndefinedPropertyException extends ModelException
{
	/**
	 * Sets the exception message to show the undefined property's name.
	 *
	 * @param str $property_name name of undefined property
	 * @return void
	 */
	public function __construct($class_name, $property_name)
	{
		if (is_array($property_name))
		{
			$this->message = implode("\r\n", $property_name);
			return;
		}

		$this->message = "Undefined property: {$class_name}->{$property_name} in {$this->file} on line {$this->line}";
		parent::__construct();
	}
};
?>