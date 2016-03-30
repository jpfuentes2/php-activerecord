<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * Interface for Date classes that can be linked to to a model attribute. This is used by
 * ActiveRecord\DateTime so it can flag the model as dirty via $model->flag_dirty() when one of its
 * setters is called.
 *
 * @package ActiveRecord
 * @see http://php.net/manual/en/class.datetime.php
 */
interface DateTimeLinkedModelInterface
{
	/**
	 * Indicates this object is an attribute of the specified model, with the given attribute name.
	 *
	 * @param Model $model The model this object is an attribute of
	 * @param string $attribute_name The attribute name 
	 * @return void
	 */
	public function attribute_of($model, $attribute_name);
}
