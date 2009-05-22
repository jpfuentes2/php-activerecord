<?php
namespace ActiveRecord;

abstract class AbstractMysqlAdapter extends Connection
{
	public function columns($table)
	{
		$columns = array();
		$conn = $this;

		$this->query_and_fetch("SHOW COLUMNS FROM $table",function($row) use ($conn, &$columns)
		{
			$c = $conn->create_column($row);
			$columns[$c->name] = $c;
		});
		return $columns;
	}

	public function limit($sql, $offset, $limit)
	{
		$offset = intval($offset);
		$limit = intval($limit);
		return "$sql LIMIT $offset,$limit";
	}

	public function quote_name($string)
	{
		return "`$string`";
	}

	public function tables()
	{
		$tables = array();

		$this->query_and_fetch("SHOW TABLES",function($row) use (&$tables)
		{
			$name = array_values($row);
			$tables[] = $name[0];
		});
		return $tables;
	}

	public function create_column(&$column)
	{
		$c = new Column();
		$c->inflected_name	= Inflector::instance()->variablize($column['Field']);
		$c->name			= $column['Field'];
		$c->nullable		= ($column['Null'] === 'YES' ? true : false);
		$c->pk				= ($column['Key'] === 'PRI' ? true : false);
		$c->auto_increment	= ($column['Extra'] === 'auto_increment' ? true : false);

		if ($column['Type'] == 'timestamp' || $column['Type'] == 'datetime')
		{
			$c->raw_type = 'datetime';
			$c->length = 19;
		}
		elseif ($column['Type'] == 'date')
		{
			$c->raw_type = 'date';
			$c->length = 10;
		}
		else
		{
			preg_match('/^(.*?)\(([0-9]+(,[0-9]+)?)\)/',$column['Type'],$matches);

			if (sizeof($matches) > 0)
			{
				$c->raw_type = $matches[1];
				$c->length = intval($matches[2]);
			}
		}

		$c->map_raw_type();
		$c->default = $c->cast($column['Default']);

		return $c;
	}
}
?>
