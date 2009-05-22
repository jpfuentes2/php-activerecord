<?php
namespace ActiveRecord;
use DateTime;

require_once 'Relationship.php';

class Table
{
	private static $cache = array();

	public $class;
	public $conn;
	public $pk;
	public $last_sql;

	// Name/value pairs of columns in this table
	public $columns = array();

	/**
	 * Same as columns but carries inflected_name
	 * @var array
	 */
	public $inflected_columns = array();

	/**
	 * Name of the table.
	 */
	public $table;

	/**
	 * Name of the database (optional)
	 */
	public $db_name;

	/**
	 * List of relationships for this table.
	 */
	private $relationships = array();

	public static function load($model_class_name)
	{
		if (!isset(self::$cache[$model_class_name]))
			return (self::$cache[$model_class_name] = new Table($model_class_name));

		return self::$cache[$model_class_name];
	}

	public static function clear_cache()
	{
		self::$cache = array();
	}

	public function __construct($class_name)
	{
		$this->class = Reflections::instance()->add($class_name)->get($class_name);

		// if connection name property is null the connection manager will use the default connection
		$connection = $this->class->getStaticPropertyValue('connection',null);

		$this->conn = ConnectionManager::get_connection($connection);
		$this->set_table_name();
		$this->get_meta_data();
		$this->set_primary_key();
		$this->set_associations();
	}

	public function construct_inner_join_sql($self, $other_class_name)
	{
		$other = self::load($other_class_name);
		$other_class_name = strtolower($other_class_name);

		return "INNER JOIN $other->table ON($this->table." . Utils::singularize($other->table) . "_id={$other->table}.{$other->pk[0]})";
	}

	public function create_joins($joins)
	{
		if (!is_array($joins))
			return $joins;

		$self = $this->table;
		$ret = $space = '';

		foreach ($joins as $value)
		{
			$ret .= $space;

			if (stripos($value,'JOIN ') === false)
				$ret .= $this->construct_inner_join_sql($this,$value);
			else
				$ret .= $value;

			$space = ' ';
		}
		return $ret;
	}

	public function find($options)
	{
		$sql = new SQLBuilder($this->conn,$this->get_fully_qualified_table_name());

		if (array_key_exists('select',$options))
			$sql->select($options['select']);

		if (array_key_exists('joins',$options))
			$sql->joins($this->create_joins($options['joins']));

		if (array_key_exists('conditions',$options))
		{
			// accept a string as a condition
			if (is_string($options['conditions']))
				$options['conditions'] = array($options['conditions']);

			if (is_hash($options['conditions']))
				$sql->where($options['conditions']);
			else
				call_user_func_array(array($sql,'where'),$options['conditions']);
		}

		if (array_key_exists('order',$options))
			$sql->order($options['order']);

		if (array_key_exists('limit',$options))
			$sql->limit($options['limit']);

		if (array_key_exists('offset',$options))
			$sql->offset($options['offset']);

		if (array_key_exists('group',$options))
			$sql->group($options['group']);

		if (array_key_exists('readonly',$options) && $options['readonly'])
			$readonly = true;
		else
			$readonly = false;

		return $this->find_by_sql($sql->to_s(),$sql->get_where_values(), $readonly);
	}

	public function find_by_sql($sql, $values=null, $readonly=false)
	{
		$this->last_sql = $sql;

		$list = array();
		$res = $this->conn->query($sql,$values);

		while (($row = $this->conn->fetch($res)))
		{
			$model = new $this->class->name($row,false,true);

			if ($readonly)
				$model->readonly();

			$list[] = $model;
		}
		return $list;
	}

	public function get_relationship($name)
	{
		if (isset($this->relationships[$name]))
			return $this->relationships[$name];
	}

	private function &process_data($hash)
	{
		foreach ($hash as $name => &$value)
		{
			// TODO this will probably need to be changed for oracle
			if ($value instanceof DateTime)
				$hash[$name] = $value->format('Y-m-d H:i:s T');
			else
				$hash[$name] = $value;
		}
		return $hash;
	}

	public function insert(&$data)
	{
		$data = $this->process_data($data);

		$sql = new SQLBuilder($this->conn,$this->get_fully_qualified_table_name());
		$sql->insert($data);

		return $this->conn->query(($this->last_sql = $sql->to_s()),array_values($data));
	}

	public function update(&$data, $where)
	{
		$data = $this->process_data($data);

		$sql = new SQLBuilder($this->conn,$this->get_fully_qualified_table_name());
		$sql->update($data)->where($where);

		return $this->conn->query(($this->last_sql = $sql->to_s()),$sql->bind_values());
	}

	public function delete($data)
	{
		$data = $this->process_data($data);

		$sql = new SQLBuilder($this->conn,$this->get_fully_qualified_table_name());
		$sql->delete($data);

		return $this->conn->query(($this->last_sql = $sql->to_s()),$sql->bind_values());
	}

	/**
	 * Add a relationship.
	 *
	 * @param Relationship $relationship a Relationship object
	 */
	private function add_relationship($relationship)
	{
		$this->relationships[$relationship->attribute_name] = $relationship;
	}

	private function get_meta_data()
	{
		$this->columns = $this->conn->columns($this->get_fully_qualified_table_name());

		$this->inflected = array();

		foreach ($this->columns as $name => &$column)
			$this->inflected[$column->inflected_name] = $column;
	}

	private function set_primary_key()
	{
		if (($pk = $this->class->getStaticPropertyValue('pk',null)) || ($pk = $this->class->getStaticPropertyValue('primary_key',null)))
			$this->pk = is_array($pk) ? $pk : array($pk);
		else
		{
			$this->pk = array();

			foreach ($this->columns as $c)
			{
				if ($c->pk)
					$this->pk[] = $c->name;
			}
		}
	}

	public function get_fully_qualified_table_name()
	{
		$table = $this->conn->quote_name($this->table);

		if ($this->db_name)
			$table = $this->conn->quote_name($this->db_name) . ".$table";

		return $table;
	}

	private function set_table_name()
	{
		if (($table = $this->class->getStaticPropertyValue('table',null)) || ($table = $this->class->getStaticPropertyValue('table_name',null)))
			$this->table = $table;
		else
		{
			// infer table name from the class name
			$this->table = Utils::pluralize(strtolower($this->class->getName()));
		}

		if(($db = $this->class->getStaticPropertyValue('db',null)) || ($db = $this->class->getStaticPropertyValue('db_name',null)))
			$this->db_name = $db;
	}

	private function set_associations()
	{
		foreach ($this->class->getStaticProperties() as $name => $definitions)
		{
			if (!$definitions || !is_array($definitions))
				continue;

			foreach ($definitions as $definition)
			{
				$relationship = null;

				switch ($name)
				{
					case 'has_many':
						$relationship = new Relationship\HasMany($definition);
						break;

					case 'has_one':
						$relationship = new Relationship\HasOne($definition);
						break;

					case 'belongs_to':
						$relationship = new Relationship\BelongsTo($definition);
						break;

					case 'has_and_belongs_to_many':
						$relationship = new Relationship\HasAndBelongsToMany($definition);
						break;
				}

				if ($relationship)
					$this->add_relationship($relationship);
			}
		}
	}
};
?>