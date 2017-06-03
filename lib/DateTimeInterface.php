<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * Interface for the ActiveRecord\DateTime class so that ActiveRecord\Model->assign_attribute() will
 * know to call attribute_of() on passed values. This is so the DateTime object can flag the model
 * as dirty via $model->flag_dirty() when one of its setters is called.
 *
 * @package ActiveRecord
 * @see http://php.net/manual/en/class.datetime.php
 */
interface DateTimeInterface
{
	/**
	 * Indicates this object is an attribute of the specified model, with the given attribute name.
	 *
	 * @param Model $model The model this object is an attribute of
	 * @param string $attribute_name The attribute name 
	 * @return void
	 */
	public function attribute_of($model, $attribute_name);

	/**
	 * Formats the DateTime to the specified format.
	 */
	public function format($format=null);

	/**
	 * See http://php.net/manual/en/datetime.createfromformat.php
	 */
	public static function createFromFormat($format, $time, $tz = null);
}
