<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * Adapter for MySQL.
 *
 * @package ActiveRecord
 */

class DynamoAdapter 
{
	
	public function quote_name($string)
	{
		//throw new \Exception('quote_name');
		//echo "DynamoAdapter->quote_name({$string})<br>";
		return $string;
	}

	public function query($sql, &$values=array())
	{
		print_r(array('query' => $sql, 'values' => $values));

		throw new \Exception('query');

		if ($this->logging)
		{
			$this->logger->log($sql);
			if ( $values ) $this->logger->log($values);
		}

		$this->last_query = $sql;

		try {
			if (!($sth = $this->connection->prepare($sql)))
				throw new DatabaseException($this);
		} catch (PDOException $e) {
			throw new DatabaseException($this);
		}

		$sth->setFetchMode(PDO::FETCH_ASSOC);

		try {
			if (!$sth->execute($values))
				throw new DatabaseException($this);
		} catch (PDOException $e) {
			throw new DatabaseException($e);
		}
		return $sth;
	}

	public function columns($table)
	{
		$reflect = new \ReflectionClass($table);
		$constants = $reflect->getConstants();
		$columns = array();
		$aliases = array();

		$reserved = array('HashKeyElement', 'HashKeyType', 'RangeKeyElement', 'RangeKeyType');

		foreach($constants as $alias => $name)
		{
			if(!in_array($alias, $reserved))
			{
				$col = null;
				if(array_key_exists($name, $columns))
				{
					$col = $columns[$name];
				}
				else
				{
					$col = new Column();
					$col->name = $name;
					$col->inflected_name = $name;
					$col->nullable = true;
				}
				$columns[$name] = $col;
			}
			$aliases[$name] = $alias;
		}

		$table::$alias_attribute = $aliases;

		if(array_key_exists('HashKeyElement', $constants))
		{
			$name = $constants['HashKeyElement'];
			$col = $columns[$name];
			$col->type = $constants['HashKeyType'];
			$col->pk = 'HashKeyElement';
			$columns[$name] = $col;
		}

		if(array_key_exists('RangeKeyElement', $constants))
		{
			$name = $constants['RangeKeyElement'];
			$col = $columns[$name];
			$col->type = $constants['RangeKeyType'];
			$col->pk = 'RangeKeyElement';
			$columns[$name] = $col;
		}

		return array_values($columns);
	}

	/**
	 * Tells you if this adapter supports sequences or not.
	 *
	 * @return boolean
	 */
	function supports_sequences()
	{
		return false;
	}

}
