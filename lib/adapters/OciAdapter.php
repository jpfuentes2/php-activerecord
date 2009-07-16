<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

use PDO;

/**
 * Adapter for OCI (not completed yet).
 * 
 * @package ActiveRecord
 */
class OciAdapter extends Connection
{
	protected function __construct($info)
	{
		$this->connection = new PDO("oci:dbname=//$info->host/$info->db",$info->user,$info->pass,static::$PDO_OPTIONS);
	}

	public function default_port()
	{
		return 1521;
	}
	
	public function get_sequence_name($table)
	{
		return $table . '_seq';
	}

	public function limit($sql, $offset, $limit)
	{
		$offset = intval($offset) + 1;
		$stop = $offset + intval($limit) - 1;
		return 
			"SELECT * FROM (SELECT a.*, rownum rnum FROM (" .
				$sql .
			") a WHERE rownum <= $stop) WHERE rnum >= $offset";
	}

	public function query_column_info($table)
	{
		$sql = 
			"SELECT c.column_name, c.data_type, c.data_length, c.data_scale, c.data_default, c.nullable, " .
				"(SELECT a.constraint_type " .
				"FROM all_constraints a, all_cons_columns b " .
				"WHERE a.constraint_type='P' " .
				"AND a.constraint_name=b.constraint_name " .
				"AND a.table_name = t.table_name AND b.column_name=c.column_name) AS pk " .
			"FROM user_tables t " .
			"INNER JOIN user_tab_columns c on(t.table_name=c.table_name) " .
			"WHERE t.table_name=?";

		$values = array(strtoupper($table));
		return $this->query($sql,$values);
	}

	public function query_for_tables()
	{
		return $this->query("SELECT table_name FROM user_tables");
	}

	public function quote_name($string)
	{
		return "\"$string\"";
	}

	public function create_column($column)
	{
		$column['column_name'] = strtolower($column['column_name']);
		$column['data_type'] = strtolower(preg_replace('/\(.*?\)/','',$column['data_type']));

		if ($column['data_default'] !== null)
			$column['data_default'] = trim($column['data_default'],"' ");

		if ($column['data_type'] == 'number')
		{
			if ($column['data_scale'] > 0)
				$column['data_type'] = 'decimal';
			elseif ($column['data_scale'] == 0)
				$column['data_type'] = 'int';
		}

		$c = new Column();
		$c->inflected_name	= Inflector::instance()->variablize($column['column_name']);
		$c->name			= $column['column_name'];
		$c->nullable		= $column['nullable'] == 'Y' ? true : false;
		$c->pk				= $column['pk'] == 'P' ? true : false;
		$c->length			= $column['data_length'];
	
		if ($column['data_type'] == 'timestamp')
			$c->raw_type = 'datetime';
		else
			$c->raw_type = $column['data_type'];

		$c->map_raw_type();
		$c->default	= $c->cast($column['data_default']);

		return $c;
	}
};
?>