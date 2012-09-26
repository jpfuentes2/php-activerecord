<?php 
namespace ActiveRecord\Relationships;


use ActiveRecord\Model;
/**
 * @todo implement me
 * @package ActiveRecord\Relationships
 * @see http://www.phpactiverecord.org/guides/associations
 */


class HasAndBelongsToMany extends Relationship
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
		parent::__construct($options);

		if (!$this->class_name)
			$this->set_inferred_class_name();
	}

	public function load(Model $model)
	{

	}
};
?>