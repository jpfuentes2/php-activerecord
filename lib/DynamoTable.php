<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * Manages reading and writing to a database table.
 *
 * This class manages a database table and is used by the Model class for
 * reading and writing to its database table. There is one instance of Table
 * for every table you have a model for.
 *
 * @package ActiveRecord
 */

use Aws\Common\Aws;
use Aws\DynamoDb\Enum\Type;

class DynamoTable extends Table
{

	public function __construct($class_name)
	{
		if(strpos($class_name, '\\', 0) === 0)
		{
			throw new \Exception('Wacky class name');
		}
		 parent::__construct($class_name);
	}

	public function get_fully_qualified_table_name($quote_name=true)
	{
		/*
		$table = $quote_name ? $this->conn->quote_name($this->table) : $this->table;

		if ($this->db_name)
			$table = $this->conn->quote_name($this->db_name) . ".$table";

		*/
		return $this->table;
	}

	public function insert(&$data, $pk=null, $sequence_name=null)
	{
		$data = $this->process_data($data);

		$sql = new SQLBuilder($this->conn,$this->get_fully_qualified_table_name());
		$sql->insert($data,$pk,$sequence_name);

		$values = array_values($data);
		return $this->conn->query(($this->last_sql = $sql->to_s()),$values);
	}

	public function update(&$data, $where)
	{
		$data = $this->process_data($data);

		$sql = new SQLBuilder($this->conn,$this->get_fully_qualified_table_name());
		$sql->update($data)->where($where);

		$values = $sql->bind_values();
		return $this->conn->query(($this->last_sql = $sql->to_s()),$values);
	}

	public function delete($data)
	{
		$key = $this->build_match_expression($data);
		$itemKey = $this->makeItemKey($key);
		$cache = $this->conn->cache;
		$this->conn->connection->deleteItem($itemKey);

        //$cache = self::GetCache();
        //if($cache) { $cache->delete(json_encode($key)); }
        //$get_response = $this->db()->deleteItem($key);
        
/*
        if(!is_null($get_response['Item']))
        {
            return new $myclass($get_response['Item']);
        }
        return null;

    }

		$sql = new SQLBuilder($this->conn,$this->get_fully_qualified_table_name());
		$sql->delete($data);

		$values = $sql->bind_values();
		return $this->conn->query(($this->last_sql = $sql->to_s()),$values);
		*/
	}


	public function find($options)
	{
		return $this->options_to_dynamo($options);
	}

	private function db()
	{
		return $this->conn->connection;
	}

	public function makeItemKey($key)
	{
		return array('TableName' => $this->table, 'Key' => $key);
	}

	private function item_to_array($array)
	{
		$toNumber = function($s) { return 0 + $s; };
		$attrs = array();
		foreach ($array as $key => $aval) 
		{
			//$aval = get_object_vars($value);
			if(array_key_exists(Type::STRING, $aval) && !is_null($aval[Type::STRING]))
			{
				$attrs[$key] = $aval[Type::STRING];
			}
			else if(array_key_exists(Type::NUMBER, $aval) && !is_null($aval[Type::NUMBER]))
			{
				$attrs[$key] = $toNumber($aval[Type::NUMBER]);
			}
			else if (array_key_exists(Type::STRING_SET, $aval))
			{
				$attrs[$key] = is_array($aval[Type::STRING_SET]) ? $aval[Type::STRING_SET] : array($aval[Type::STRING_SET]);
			}
			else if (array_key_exists(Type::NUMBER_SET, $aval))
			{
				$attrs[$key] = is_array($aval[Type::NUMBER_SET]) ? map($toNumber,$aval[Type::NUMBER_SET]) : map($toNumber,array($val[Type::NUMBER_SET]));
			}
			else if(array_key_exists(Type::BINARY,$aval))
			{
				// binary
				$attrs[$key] = $value;
			}
			else if(array_key_exists(Type::BINARY_SET,$aval))
			{
				// binary
				$attrs[$key] = is_array($aval[Type::BINARY_SET]) ? $aval[Type::BINARY_SET] : array($val[Type::BINARY_SET]);
			}
			else if(!is_null($value))
			{
				$attrs[$key] = $value;
			}
		}
		return $attrs;
	}

	private function process_data($hash)
	{
		if (!$hash || $hash)
			return $hash;

		foreach ($hash as $name => &$value)
		{
			if ($value instanceof \DateTime)
			{
				if (isset($this->columns[$name]) && $this->columns[$name]->type == Column::DATE)
					$hash[$name] = $this->conn->date_to_string($value);
				else
					$hash[$name] = $this->conn->datetime_to_string($value);
			}
			else
				$hash[$name] = $value;
		}
		return $hash;
	}

	public function build_match_expression($keys)
	{
		$key = array();
		foreach($keys as $name => $value)
		{
			$col = array_key_exists($name, $this->columns) ? $this->columns[$name] : $this->get_column_by_inflected_name($name);

			if($col->pk)
			{
				$key[$col->pk] = $this->db()->formatValue($value);
			}
			else
			{
				throw new \Exception('Scan not supported yet');
				$query_or_scan = 'scan';
			}
		}
		return $key;
	}

	public function options_to_dynamo($options)
	{

		$table = array_key_exists('from', $options) ? $options['from'] : $this->get_fully_qualified_table_name();

		$query_or_scan = 'query';

		$key = array();

		if(array_key_exists('conditions', $options))
		{
			$key = $this->build_match_expression($options['conditions']);
		}

		$get_response = $this->db()->getItem($this->makeItemKey($key));

		if(!is_null($get_response['Item']))
		{
			$item = $this->item_to_array($get_response['Item']);
			$model = new $this->class->name($item,false,true,false);

			return array($model);
		}
		else
		{
			return array();
		}

		return null;

		$request = array();		
		return $request;


		$sql = new SQLBuilder($this->conn, $table);

		if (array_key_exists('joins',$options))
		{
			$sql->joins($this->create_joins($options['joins']));

			// by default, an inner join will not fetch the fields from the joined table
			if (!array_key_exists('select', $options))
				$options['select'] = $this->get_fully_qualified_table_name() . '.*';
		}

		if (array_key_exists('select',$options))
			$sql->select($options['select']);

		if (array_key_exists('conditions',$options))
		{
			if (!is_hash($options['conditions']))
			{
				if (is_string($options['conditions']))
					$options['conditions'] = array($options['conditions']);

				call_user_func_array(array($sql,'where'),$options['conditions']);
			}
			else
			{
				if (!empty($options['mapped_names']))
					$options['conditions'] = $this->map_names($options['conditions'],$options['mapped_names']);

				$sql->where($options['conditions']);
			}
		}

		if (array_key_exists('order',$options))
			$sql->order($options['order']);

		if (array_key_exists('limit',$options))
			$sql->limit($options['limit']);

		if (array_key_exists('offset',$options))
			$sql->offset($options['offset']);

		if (array_key_exists('group',$options))
			$sql->group($options['group']);

		if (array_key_exists('having',$options))
			$sql->having($options['having']);

		return $sql;
	}

	function create_conditions_from_keys(Model $model, $condition_keys=array(), $value_keys=array())
	{
		$condition_values = array_values($model->get_values_for($value_keys));

		// return null if all the foreign key values are null so that we don't try to do a query like "id is null"
		if (all(null,$condition_values))
			return null;

		$conditions = array();

		foreach($condition_keys as $idx => $value)
		{
			$conditions[$value] = $condition_values[$idx];
		}

		return $conditions;
	}
}