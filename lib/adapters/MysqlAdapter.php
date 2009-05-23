<?php
namespace ActiveRecord;

require_once 'AbstractMysqlAdapter.php';

class MysqlAdapter extends AbstractMysqlAdapter
{
	protected function connect($connection_string)
	{
		$info = static::connection_info_from($connection_string);
		$this->connection = @mysql_connect($info->host . ($info->port ? ":$info->port" : ""),$info->user,$info->pass);

		if (!$this->connection)
			throw new DatabaseException("Could not connect to database $info->host");

		if (!mysql_select_db($info->db))
			throw new DatabaseException(mysql_error($this->connection),mysql_errno($this->connection));
	}

	public function close()
	{
		if ($this->connection)
		{
			mysql_close($this->connection);
			$this->connection = null;
		}
	}

	public function escape($string)
	{
		return mysql_real_escape_string($string, $this->connection);
	}

	public function fetch($res)
	{
		if (!($row = mysql_fetch_assoc($res)))
			$this->free_result_set($res);

		return $row;
	}

	public function free_result_set($res)
	{
		@mysql_free_result($res);
	}

	public function insert_id()
	{
		return mysql_insert_id($this->connection);
	}

	public function query($sql, $values=array())
	{
		if (isset($GLOBALS['ACTIVERECORD_LOG']) && $GLOBALS['ACTIVERECORD_LOG'])
			$GLOBALS['ACTIVERECORD_LOGGER']->log($sql, PEAR_LOG_INFO);

		if (is_array($values) && count($values) > 0)
		{
			$sql = new Expressions($sql);
			$sql->set_connection($this);
			$sql->bind_values($values);
			$sql = $sql->to_s(true);
		}

		if (!($res = mysql_query($sql,$this->connection)))
			throw new DatabaseException(mysql_error($this->connection),mysql_errno($this->connection));

		return $res;
	}
};
?>