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
class MysqlAdapter extends Connection
{
	static $DEFAULT_PORT = 3306;

	public function limit($sql, $offset, $limit)
	{
		$offset = is_null($offset) ? '' : intval($offset) . ',';
		$limit = intval($limit);
		return "$sql LIMIT {$offset}$limit";
	}

	public function query_column_info($table)
	{
		return $this->query("SHOW COLUMNS FROM $table");
	}

	public function query_for_tables()
	{
		return $this->query('SHOW TABLES');
	}

	public function create_column(&$column)
	{
		$fix_case = function($x) { return $x; };
		if(array_key_exists('field', $column))
		{	
			$fix_case = function($x) { return strtolower($x); };
		}

		$c = new Column();
		$c->inflected_name	= Inflector::instance()->variablize($column[$fix_case('Field')]);
		$c->name			= $column[$fix_case('Field')];
		$c->nullable		= ($column[$fix_case('Null')] === 'YES' ? true : false);
		$c->pk				= ($column[$fix_case('Key')] === 'PRI' ? true : false);
		$c->auto_increment	= ($column[$fix_case('Extra')] === 'auto_increment' ? true : false);

		if ($column[$fix_case('Type')] == 'timestamp' || $column[$fix_case('Type')] == 'datetime')
		{
			$c->raw_type = 'datetime';
			$c->length = 19;
		}
		elseif ($column[$fix_case('Type')] == 'date')
		{
			$c->raw_type = 'date';
			$c->length = 10;
		}
		elseif ($column[$fix_case('Type')] == 'time')
		{
			$c->raw_type = 'time';
			$c->length = 8;
		}
		else
		{
			preg_match('/^([A-Za-z0-9_]+)(\(([0-9]+(,[0-9]+)?)\))?/',$column[$fix_case('Type')],$matches);

			$c->raw_type = (count($matches) > 0 ? $matches[1] : $column[$fix_case('Type')]);

			if (count($matches) >= 4)
				$c->length = intval($matches[3]);
		}

		$c->map_raw_type();
		$c->default = $c->cast($column[$fix_case('Default')],$this);

		return $c;
	}

	public function set_encoding($charset)
	{
		$params = array($charset);
		$this->query('SET NAMES ?',$params);
	}

	public function accepts_limit_and_order_for_update_and_delete() { return true; }

	public function native_database_types()
	{
		return array(
			'primary_key' => 'int(11) UNSIGNED DEFAULT NULL auto_increment PRIMARY KEY',
			'string' => array('name' => 'varchar', 'length' => 255),
			'text' => array('name' => 'text'),
			'integer' => array('name' => 'int', 'length' => 11),
			'float' => array('name' => 'float'),
			'datetime' => array('name' => 'datetime'),
			'timestamp' => array('name' => 'datetime'),
			'time' => array('name' => 'time'),
			'date' => array('name' => 'date'),
			'binary' => array('name' => 'blob'),
			'boolean' => array('name' => 'tinyint', 'length' => 1)
		);
	}

}
?>
