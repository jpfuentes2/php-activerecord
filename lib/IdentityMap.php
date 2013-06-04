<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * The model identity map.
 *
 * By having an identity map we can store instances of a model
 * and return them when its identity is matched rather than
 * querying the database again and creating a new instance for
 * the same data.
 */
class IdentityMap extends Singleton
{
	private $_map = array();


	public function store(Model $model)
	{
		$id = $model->id;
		$table = $model->table_name();

		$this->_map[$table][$id] = $model;
	}


	public function get($table, $id)
	{
		return isset($this->_map[$table][$id]) ? $this->_map[$table][$id] : null;
	}
};
?>
