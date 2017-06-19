<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

use PDO;

/**
 * Adapter for MSSQL.
 * http://www.microsoft.com/download/en/details.aspx?displaylang=en&id=20098
 *
 * @package ActiveRecord
 */
class SqlsrvAdapter extends Connection
{
	static $QUOTE_CHARACTER = '';
	static $DEFAULT_PORT = 1433;

	protected function __construct($info)
	{
		try {
			$host = isset($info->port) ? "$info->host, $info->port" : $info->host;
			$this->connection = new PDO("sqlsrv:Server=$host;Database=$info->db", $info->user, $info->pass, static::$PDO_OPTIONS);
		} catch (PDOException $e) {
			throw new DatabaseException($e);
		}
	}

	public function quote_name($string)
	{
		return $string[0] === '[' || $string[strlen($string) - 1] === ']' ?
			$string : "[$string]";
	}

	// based on the implementation from the Zend Db adapter
	public function limit($sql, $offset, $limit)
	{
		$limit = intval($limit);
		$offset = intval($offset);

		if ($offset == 0)
			return preg_replace('/^SELECT\s/i', 'SELECT TOP ' . $limit . ' ', $sql);

		$orderby = stristr($sql, 'ORDER BY');
		$over = $orderby ? preg_replace('/\"[^,]*\".\"([^,]*)\"/i', '"inner_tbl"."$1"', $orderby) : 'ORDER BY (SELECT 0)';

		// Remove ORDER BY clause from $sql
		$sql = preg_replace('/\s+ORDER BY(.*)/', '', $sql);

		// Add ORDER BY clause as an argument for ROW_NUMBER()
		$sql = "SELECT ROW_NUMBER() OVER ($over) AS \"AR_ROWNUM\", * FROM ($sql) AS inner_tbl";

		$start = $offset + 1;
		$end = $offset + $limit;
		return "WITH outer_tbl AS ($sql) SELECT * FROM outer_tbl WHERE \"AR_ROWNUM\" BETWEEN $start AND $end";
	}

	public function query_column_info($table)
	{
		$sql =
			"SELECT c.COLUMN_NAME as field, c.DATA_TYPE as data_type, c.CHARACTER_MAXIMUM_LENGTH AS length, c.NUMERIC_PRECISION_RADIX AS radix, c.COLUMN_DEFAULT AS data_default, c.IS_NULLABLE AS nullable, " .
				"COLUMNPROPERTY(OBJECT_ID(TABLE_NAME), c.COLUMN_NAME, 'IsIdentity') AS extra, " .
				"(SELECT a.CONSTRAINT_TYPE " .
				"FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS a, INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE b " .
				"WHERE a.CONSTRAINT_TYPE='PRIMARY KEY' " .
				"AND a.CONSTRAINT_NAME = b.CONSTRAINT_NAME " .
				"AND a.TABLE_NAME = b.TABLE_NAME AND b.COLUMN_NAME = c.COLUMN_NAME) AS PK " .
			"FROM INFORMATION_SCHEMA.COLUMNS c " .
			"WHERE c.TABLE_NAME=?";

		$values = array($table);
		return $this->query($sql,$values);
	}

	public function query_for_tables()
	{
		return $this->query('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES');
	}

	public function create_column(&$column)
	{
		$c = new Column();
		$c->inflected_name = Inflector::instance()->variablize($column['field']);
		$c->name           = $column['field'];
		$c->nullable       = ($column['nullable'] === 'YES' ? true : false);
		$c->auto_increment = ($column['extra'] === '1' ? true : false);
		$c->pk             = ($column['pk'] === 'PRIMARY KEY' ? true : false);
		$c->raw_type       = $column['data_type'];
		$c->length         = ($column['length'] ? $column['length'] : $column['radix']);

		if ($c->raw_type == 'text')
			$c->length = null;

		if ($c->raw_type == 'datetime')
			$c->length = 19;

		$c->map_raw_type();
		$c->default = $c->cast(preg_replace("#\(+'?(.*?)'?\)+#", '$1', $column['data_default']),$this);

		return $c;
	}

	public function set_encoding($charset)
	{
		throw new ActiveRecordException("SqlsrvAdapter::set_charset not supported.");
	}

	public function accepts_limit_and_order_for_update_and_delete() { return false; }

	public function native_database_types()
	{
		return array(
			'primary_key' => 'int NOT NULL IDENTITY(1,1) PRIMARY KEY',
			'string'      => array('name' => 'varchar', 'length' => 255),
			'text'        => array('name' => 'text'),
			'integer'     => array('name' => 'int'),
			'float'       => array('name' => 'float'),
			'datetime'    => array('name' => 'datetime'),
			'timestamp'   => array('name' => 'datetime'),
			'time'        => array('name' => 'time'),
			'date'        => array('name' => 'date'),
			'binary'      => array('name' => 'blob'),
			'boolean'     => array('name' => 'bit')
		);
	}

}
?>
