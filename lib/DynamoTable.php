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
		 $this->reset_primary_key();
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

	public function reset_primary_key($first=false)
	{
		$pks = array();
		foreach($this->pk as $name)
		{
			$col = array_key_exists($name, $this->columns) ? $this->columns[$name] : $this->get_column_by_inflected_name($name);
			if($col->pk === 'HashKeyElement')
			{
				$pks[0] = $col->name;
			}
			if($col->pk === 'RangeKeyElement')
			{
				$pks[1] = $col->name;
			}
		}
		$this->pk = $pks;
	}

	private function encode_operator($expn)
	{
		$expn = strtoupper($expn);

		if(strpos($expn,'BEGINS WITH'))
		{
			return 'BEGINS_WITH';
		}
		if(strpos($expn,'NOT CONTAINS'))
		{
			return 'NOT_CONTAINS';
		}
		if(strpos($expn,'CONTAINS'))
		{
			return 'CONTAINS';
		}
		if(strpos($expn,'NOT NULL'))
		{
			return 'NOT_NULL';
		}
		if(strpos($expn,'NULL'))
		{
			return 'NULL';
		}
		if(strpos($expn,'BETWEEN'))
		{
			return 'BETWEEN';
		}
		if(strpos($expn,'>='))
		{
			return 'GE';
		}
		if(strpos($expn,'<='))
		{
			return 'LE';
		}
		if(strpos($expn,'IN'))
		{
			return 'IN';
		}
		if(strpos($expn,'!=') || strpos($expn,'<>'))
		{
			return 'NE';
		}
		if(strpos($expn,'<'))
		{
			return 'LT';
		}
		if(strpos($expn,'>'))
		{
			return 'GT';
		}
		if(strpos($expn,'='))
		{
			return 'EQ';
		}
		throw new \Exception('Operator not found in '. $expn);
		//	EQ, NE, IN, LE, LT, GE, GT, BETWEEN, NOT_NULL, NULL, CONTAINS, NOT_CONTAINS, BEGINS_WITH
	}

	private function encode_condition($expn,$value)
	{
		$expn = preg_replace('/\s+/', ' ',$expn);
		$parts = explode(' ', $expn);
		$col = $this->columns[$parts[0]];
		$parts[0] = ' ';
		$op = $this->encode_operator(implode(' ', $parts));
		return array($col->name => array(
				'AttributeValueList' => array(
					array($col->type => $value)
				),
				'ComparisonOperator' => $op));
	}

	public function find_by_pk($values, $options)
	{

		if(!is_array($values))
		{
			$values = array($values);
		}

		// exact key match?
		if(count($this->pk) === count($values) && empty($options))
		{
			$key = array($this->pk[0] => $values[0]);

			if(count($values) === 2)
			{
				$key[$this->pk[1]] = $values[1];
			}

			$keys = $this->make_key($key);
			$itemKey = $this->makeItemKey($keys);
			//echo '<hr>pk:' . json_encode($this->pk) . ' : ' . json_encode($keys) . ' : ' . json_encode($itemKey);
			$get_response = $this->db()->getItem($itemKey);

			if(!is_null($get_response['Item']))
			{
				$item = $this->item_to_array($get_response['Item']);
				$model = new $this->class->name($item,false,true,false);

				return $model;
			}
			else 
			{
				return null;
			}
		}
		else // prepare a range query
		{
			echo '<hr>find_by_pk: ' . print_r(array('values' => $values, 'options' => $options),true) .'<br>';
			
			$hash_col = $this->columns[$this->pk[0]];
			$query = array('TableName' => $this->table, 'HashKeyValue' => array($hash_col->type => $values[0]));

			if(array_key_exists('conditions', $options))
			{
				$conditions = $options['conditions'];
				$query['RangeKeyCondition'] = $this->encode_condition($conditions[0],$conditions[1]);
			}

			if(array_key_exists('order', $options)) 
			{
				$query['ScanIndexForward'] = (strpos(strtolower(' '.$options['order']), 'desc') === false);
			}

			if(array_key_exists('limit', $options))
			{
				$query['Limit'] = intval($options['limit']);
			}

			if(array_key_exists('consistent',$options))
			{
				$query['ConsistentRead'] = $options['consistent'];
			}

			echo '<hr>Query: ' . print_r($query,true) . '<br>';
			
			$results = $this->db()->query($query);

			print_r($results);

			$items = array();
			foreach($results['Items'] as $idx => $item)
			{
				$items[] = new $this->class->name($this->item_to_array($item),false,true,false);
			}

			return $items;

			print_r($results);
/*
			$query = array(
    		'TableName'     => 'errors',
    		'KeyConditions' => array(
        'id' => array(
            'AttributeValueList' => array(
                array('N' => '1201')
            ),
            'ComparisonOperator' => 'EQ'
        ),
        'time' => array(
            'AttributeValueList' => array(
                array('N' => strtotime("-15 minutes"))
            ),
            'ComparisonOperator' => 'GT'
        )
    )
*/
			throw new \Exception('Non-primary Key Search');
		}


	

		$class_name = $this->class;
		$options['conditions'] = $class_name::pk_conditions($values);
		$list = $this->find($options);
		$results = count($list);

		if ($results != ($expected = count($values)))
		{
			if ($expected == 1)
			{
				if (!is_array($values))
					$values = array($values);

				throw new RecordNotFound("Couldn't find $class with ID=" . join(',',$values));
			}

			$values = join(',',$values);
			throw new RecordNotFound("Couldn't find all $class_name with IDs ($values) (found $results, but was looking for $expected)");
		}
		return $expected == 1 ? $list[0] : $list;
	}

	public function get_hash_key_element()
	{
		foreach($this->pk as $name)
		{
			$col = array_key_exists($name, $this->columns) ? $this->columns[$name] : $this->get_column_by_inflected_name($name);
			if($col->pk === 'HashKeyElement')
			{
				return $name;
			}
		}
		throw new \Exception('No HashKeyElement defined for table: '. $this->table);
	}

	public function get_range_key_element()
	{
		if(count($this->pk) !== 2)
		{
			return null;
		}
		foreach($this->pk as $name)
		{
			$col = array_key_exists($name, $this->columns) ? $this->columns[$name] : $this->get_column_by_inflected_name($name);
			if($col->pk === 'RangeKeyElement')
			{
				return $name;
			}
		}
		return null;		
	}

	public function insert(&$data, $pk=null, $sequence_name=null)
	{
		$put_response = $this->db()->putItem(array(
        	'TableName' => $this->get_fully_qualified_table_name(),
           	'Item' => $this->db()->formatAttributes($data)
           	)
      	);
	}

	public function update(&$data, $where)
	{

		echo '<hr>Data: '.print_r($data,true);
		echo '<br>Where: '.print_r($where,true);
		echo '<hr>';
		throw new \Exception("Not Implemented");

		$data = $this->process_data($data);

        $updates = array();
        foreach($this->_attributes as $key => $value)
        {
            if(!array_key_exists($key,$this->_snapshot) && !is_null($value))
            {
                $updates[$key] = array('Action' => AttributeAction::PUT, 'Value' => DynamoItem::Database()->formatValue($value)); 
            }
            else if($value != $this->_snapshot[$key] && !is_null($value))
            {
                if(false && is_array($value) && is_array($this->_snapshot[$key]))
                {

                }
                else
                {
                    $updates[$key] = array('Action' => AttributeAction::PUT, 'Value' => DynamoItem::Database()->formatValue($value));
                }
            }
        }
        foreach($this->_snapshot as $key => $value)
        {
            if(!array_key_exists($key,$this->_attributes) || empty($this->_attributes[$key]))
            {
                $update[$key] = array('Action' => AttributeAction::DELETE);
            }
        }
        $expected = array();
        foreach($this->_snapshot as $key => $value)
        {
            if($key != $myclass::HashKeyElement && !defined(get_called_class().'::RangeKeyElement') || (defined(get_called_class().'::RangeKeyElement') && $key != $this->_statics['RangeKeyElement']))
            {
                $expected[$key] = array('Value' => DynamoItem::Database()->formatValue($value));
            }
        }
        $request = $this->itemKey();
        $request['AttributeUpdates'] = $updates;
        $request['Expected'] = $expected;
        $request['ReturnValues'] = 'ALL_NEW';
        if(!empty($updates))
        {
            $update_response = DynamoItem::Database()->updateItem($request);
        }

        return;


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
		if(array_key_exists('conditions', $options))
		{
			$keys = array_values($options['conditions']);
			return array($this->find_by_pk($keys,array()));
		}
		echo '<hr>Find Options: ' . print_r($options,true) . '<hr>';
		throw new \Exception('what?');

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
				$hash[$name] = $value->getTimestamp();
			}
			else if(is_array($value) && count($value) === 0)
			{
				unset($hash[$name]);
			}
			else
			{
				$hash[$name] = $value;
			}
		}
		return $hash;
	}

	public function make_key($keys)
	{
		$key = array();
		foreach($keys as $name => $value)
		{
			$col = array_key_exists($name, $this->columns) ? $this->columns[$name] : $this->get_column_by_inflected_name($name);

			if($col->pk)
			{
				$key[$col->name] = $this->db()->formatValue($value);
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
			$key = $this->make_key($options['conditions']);
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

	public function CreateTable($readCapacityUnits = 20, $writeCapacityUnits = 5)
    {
        $table = $this->get_fully_qualified_table_name();
        $keySchema = array(array('AttributeName' => $table::HashKeyElement, 'KeyType' => 'HASH'));
        $definitions = array(array('AttributeName' => $table::HashKeyElement, 'AttributeType' => $table::HashKeyType));

        if(defined($table.'::RangeKeyElement'))
        {
            $keySchema[] = array('AttributeName' => $table::RangeKeyElement, 'KeyType' => 'RANGE');
            $definitions[] = array('AttributeName' => $table::RangeKeyElement, 'AttributeType' => $table::RangeKeyType);
        }

        $dynamodb = self::Database();
        $response = $dynamodb->createTable(array(
            'TableName' => $table,
            'AttributeDefinitions' => $definitions,
            'KeySchema' => $keySchema,
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits'  => $readCapacityUnits,
                'WriteCapacityUnits' => $writeCapacityUnits
            )
        ));

    }

    public function DeleteTable()
    {
        $dynamodb = $this->db();
        $response = $dynamodb->deleteTable(array('TableName' => $this->get_fully_qualified_table_name()));
        //$cache = self::GetCache();
        //if($cache) { $cache->flush(); }
    }

    public function TableStatus()
    {
        $dynamodb = $this->db();
        $response = $dynamodb->describeTable(array('TableName' => $this->get_fully_qualified_table_name()));

        return (string) $response['Table']['TableStatus'];
    }

    public function TableExists()
    {
        return !is_null($this->TableStatus());
    }
}