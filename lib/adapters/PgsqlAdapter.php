<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * Adapter for Postgres (not completed yet)
 * 
 * @package ActiveRecord
 */
class PgsqlAdapter extends Connection
{
	/**
	 * @param string $connection_string Should be in the format sqlite3://path-to-the-db
	 */
	protected function connect($connection_string)
	{
		$info = static::connection_info_from($connection_string);
		$this->connection = pg_connect(
			"host=$info->host " .
			"user=$info->user " .
			"password=$info->pass " .
			"dbname=$info->db " .
			($info->port ? "port=$info->port" : ""));

		if (!$this->connection)
			throw new DatabaseException("Could not connect to database $info->host");
	}

	public function close()
	{
		if ($this->connection)
		{
			$this->connection->pg_close($this->connection);
			$this->connection = null;
		}
	}

	public function columns($table)
	{
		$columns = array();
		$conn = $this;
/*
		$sql =<< SQL
			SELECT a.attname,
			  pg_catalog.format_type(a.atttypid, a.atttypmod),
			  (SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128)
			   FROM pg_catalog.pg_attrdef d
			   WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef),
			  a.attnotnull, a.attnum
			FROM pg_catalog.pg_attribute a
			WHERE a.attrelid = (select c.oid from pg_catalog.pg_class c inner join pg_catalog.pg_namespace n on(n.oid=c.relnamespace) where c.relname='venues' and pg_catalog.pg_table_is_visible(c.oid)) AND a.attnum > 0 AND NOT a.attisdropped
			ORDER BY a.attnum
		SQL;
*/
		$this->query_and_fetch($sql,function($row) use ($conn, &$columns)
		{
			$c = $conn->create_column($row);
			$columns[$c->name] = $c;
		});
		return $columns;
	}

	public function escape($string)
	{
		return pg_escape_string($this->connection,$string);
	}

	public function fetch($res)
	{
		if (!($row = pg_fetch_assoc($res)))
			$this->free_result_set($res);

		return $row;
	}

	public function free_result_set($res)
	{
		pg_free_result($res);
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

	public function prepare($sql)
	{
		return new PgsqlPreparedStatement($this,$sql);
	}

	public function query($sql)
	{
		if (getenv('LOG') == 'true')
			$GLOBALS['logger']->log($sql, PEAR_LOG_INFO);

		if (!($res = pg_query($this->connection,$sql)))
			throw new DatabaseException(pg_last_error($this->connection));

		return $res;
	}

	public function quote_name($string)
	{
		return "`$string`";
	}

	public function tables()
	{
		$tables = array();

		$this->query_and_fetch("SELECT tablename FROM pg_tables WHERE schemaname NOT IN('information_schema','pg_catalog')",function($row) use (&$tables)
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