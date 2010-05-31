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
	static $QUOTE_CHARACTER	= '"';
	static $DEFAULT_PORT	= 5432;

	public function supports_sequences() { return true; }

	public function get_sequence_name($table, $column_name)
	{
		return "{$table}_{$column_name}_seq";
	}

	public function next_sequence_value($sequence_name)
	{
		return "nextval('" . str_replace("'","\\'",$sequence_name) . "')";
	}

	public function limit($sql, $offset, $limit)
	{
		return $sql . ' LIMIT ' . intval($limit) . ' OFFSET ' . intval($offset);
	}

	public function query_column_info($table)
	{
		$sql = <<<SQL
SELECT a.attname AS field, a.attlen,
REPLACE(pg_catalog.format_type(a.atttypid, a.atttypmod),'character varying','varchar') AS type,
a.attnotnull AS not_nullable, 
i.indisprimary as pk,
REGEXP_REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(s.column_default,'::[a-z_ ]+',''),'\'$',''),'^\'','') AS default
FROM pg_catalog.pg_attribute a
LEFT JOIN pg_catalog.pg_class c ON(a.attrelid=c.oid)
LEFT JOIN pg_catalog.pg_index i ON(c.oid=i.indrelid AND a.attnum=any(i.indkey))
LEFT JOIN information_schema.columns s ON(s.table_name=? AND a.attname=s.column_name)
WHERE a.attrelid = (select c.oid from pg_catalog.pg_class c inner join pg_catalog.pg_namespace n on(n.oid=c.relnamespace) where c.relname=? and pg_catalog.pg_table_is_visible(c.oid))
AND a.attnum > 0
AND NOT a.attisdropped
ORDER BY a.attnum
SQL;
		$values = array($table,$table);
		return $this->query($sql,$values);
	}

	public function query_for_tables()
	{
		return $this->query("SELECT tablename FROM pg_tables WHERE schemaname NOT IN('information_schema','pg_catalog')");
	}

	public function create_column(&$column)
	{
		$c = new Column();
		$c->inflected_name	= Inflector::instance()->variablize($column['field']);
		$c->name			= $column['field'];
		$c->nullable		= ($column['not_nullable'] ? false : true);
		$c->pk				= ($column['pk'] ? true : false);
		$c->auto_increment	= false;

		if (substr($column['type'],0,9) == 'timestamp')
		{
			$c->raw_type = 'datetime';
			$c->length = 19;
		}
		elseif ($column['type'] == 'date')
		{
			$c->raw_type = 'date';
			$c->length = 10;
		}
		else
		{
			preg_match('/^([A-Za-z0-9_]+)(\(([0-9]+(,[0-9]+)?)\))?/',$column['type'],$matches);

			$c->raw_type = (count($matches) > 0 ? $matches[1] : $column['type']);
			$c->length = count($matches) >= 4 ? intval($matches[3]) : intval($column['attlen']);

			if ($c->length < 0)
				$c->length = null;
		}

		$c->map_raw_type();

		if ($column['default'])
		{
			preg_match("/^nextval\('(.*)'\)$/",$column['default'],$matches);

			if (count($matches) == 2)
				$c->sequence = $matches[1];
			else
				$c->default = $c->cast($column['default'],$this);
		}
		return $c;
	}

	public function set_encoding($charset)
	{
		$this->query("SET NAMES '$charset'");
	}
};
?>
