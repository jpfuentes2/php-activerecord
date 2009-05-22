<?php
namespace ActiveRecord;

class Sqlite3Adapter extends Connection
{
	/**
	 * @param string $connection_string Should be in the format sqlite3://path-to-the-db
	 */
	protected function connect($connection_string)
	{
		// 10 = sqlite3://
		$db = substr($connection_string,10);

		if (!file_exists($db))
			throw new DatabaseException("Could not find sqlite db: $db");

		$this->connection = new \SQLite3($db);

		if (!$this->connection)
			throw new DatabaseException("Could not connect to database $db");
	}

	public function close()
	{
		if ($this->connection)
		{
			$this->connection->close();
			$this->connection = null;
		}
	}

	public function columns($table)
	{
		$columns = array();
		$conn = $this;

		$this->query_and_fetch("pragma table_info($table)",function($row) use ($conn, &$columns)
		{
			$c = $conn->create_column($row);
			$columns[$c->name] = $c;
		});
		return $columns;
	}

	public function escape($string)
	{
		return $this->connection->escapeString($string);
	}

	public function fetch($res)
	{
		return $res->fetchArray(SQLITE3_ASSOC);
	}

	public function free_result_set($res)
	{
		// no free method
	}

	public function insert_id()
	{
		return $this->connection->lastInsertRowID();
	}

	public function limit($sql, $offset, $limit)
	{
		$offset = intval($offset);
		$limit = intval($limit);
		return "$sql LIMIT $offset,$limit";
	}

	public function query($sql, $values=array())
	{
		if (getenv('LOG') == 'true')
			$GLOBALS['logger']->log($sql, PEAR_LOG_INFO);

		$sql = new Expressions($sql);
		$sql->set_connection($this);
		$sql->bind_values($values);

		$sql = trim($sql->to_s());
		$values = $values ? array_flatten($values) : array();
		$select = true;

		// don't return a resultset if we're inserting/updating
		$left7 = strtolower(substr($sql,0,7));

		if ($left7 === 'insert ' || $left7 === 'update ' || $left7 === 'delete ')
			$select = false;

		if (!($sth = @$this->connection->prepare($sql)))
			throw new DatabaseException($this->connection->lastErrorMsg(),$this->connection->lastErrorCode());

		for ($i=0,$n=count($values); $i<$n; ++$i)
		{
			if (is_string($values[$i]))		$type = SQLITE3_TEXT;
			elseif (is_float($values[$i]))	$type = SQLITE3_FLOAT;
			elseif (is_numeric($values[$i]))$type = SQLITE3_INTEGER;
			elseif (is_null($values[$i]))	$type = SQLITE3_NULL;
			else $type = SQLITE3_TEXT;

			$sth->bindParam($i+1,$values[$i],$type);
		}

		$res = $sth->execute();
		return $select ? $res : true;
	}

	public function quote_name($string)
	{
		return "`$string`";
	}

	public function tables()
	{
		$tables = array();

		$this->query_and_fetch("SELECT name FROM sqlite_master",function($row) use (&$tables)
		{
			$name = array_values($row);
			$tables[] = $name[0];
		});
		return $tables;
	}

	public function create_column($column)
	{
		$c = new Column();
		$c->inflected_name	= Inflector::instance()->variablize($column['name']);
		$c->name			= $column['name'];
		$c->nullable		= $column['notnull'] ? false : true;
		$c->pk				= $column['pk'] ? true : false;
		$c->auto_increment	= $column['type'] == 'INTEGER' && $c->pk;

		$column['type'] = preg_replace('/ +/',' ',$column['type']);
		$column['type'] = str_replace(array('(',')'),' ',$column['type']);
		$column['type'] = Utils::squeeze(' ',$column['type']);
		$matches = explode(' ',$column['type']);

		if (count($matches) > 0)
		{
			$c->raw_type = strtolower($matches[0]);

			if (count($matches) > 1)
				$c->length = intval($matches[1]);
		}

		$c->map_raw_type();

		if ($c->type == Column::DATETIME)
			$c->length = 19;
		elseif ($c->type == Column::DATE)
			$c->length = 10;

		// From SQLite3 docs: The value is a signed integer, stored in 1, 2, 3, 4, 6,
		// or 8 bytes depending on the magnitude of the value.
		// so is it ok to assume it's possible an int can always go up to 8 bytes?
		if ($c->type == Column::INTEGER && !$c->length)
			$c->length = 8;

		$c->default = $c->cast($column['dflt_value']);

		return $c;
	}
};
?>