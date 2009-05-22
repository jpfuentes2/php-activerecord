<?php
namespace ActiveRecord;

require_once 'AbstractMysqlAdapter.php';

class MysqliAdapter extends AbstractMysqlAdapter
{
	protected function connect($connection_string)
	{
		$info = static::connection_info_from($connection_string);
		$this->connection = @mysqli_connect($info->host,$info->user,$info->pass,null,$info->port);

		if (!$this->connection)
			throw new DatabaseException(mysqli_connect_error(),mysqli_connect_errno());

		if (!$this->connection->select_db($info->db))
			throw new DatabaseException(mysqli_error($this->connection),mysqli_errno($this->connection));
	}

	public function close()
	{
		if ($this->connection)
		{
			mysqli_close($this->connection);
			$this->connection = null;
		}
	}

	public function escape($string)
	{
		return mysqli_real_escape_string($this->connection,$string);
	}

	public function fetch($res)
	{
		if (!($row = $res->fetch()))
			$res->free();

		return $row;
	}

	public function free_result_set($res)
	{
		$res->free();
	}

	public function insert_id()
	{
		if (($res = $this->query('SELECT LAST_INSERT_ID() AS id')))
		{
			$row = $this->fetch($res);
			$this->free_result_set($res);
			return $row['id'];
		}
		return null;
	}

	public function query($sql, $values=array())
	{
		$sql = new Expressions($sql);
		$sql->set_connection($this);
		$sql->bind_values($values);

		$sql = trim($sql->to_s());
		$values = $values ? array_flatten($values) : array();
		$select = true;

		// don't return a resultset if we're inserting/updating
		$left7 = strtolower(substr($sql,0,7));

		// TODO find better way to do this
		if ($left7 === 'insert ' || $left7 === 'update ' || $left7 === 'delete ')
			$select = false;

		if (!($sth = mysqli_prepare($this->connection,$sql)))
			throw new DatabaseException(mysqli_error($this->connection),mysqli_errno($this->connection));

		if (count($values) > 0)
		{
			$params = array($sth,'');

			foreach ($values as &$value)
			{
				$params[1] .= 's';
				$params[] = &$value;
			}

			if ($params[1])
				call_user_func_array('mysqli_stmt_bind_param',$params);
		}

		mysqli_stmt_execute($sth);
		return $select ? new MysqliResultSet($sth) : true;
	}
};

class MysqliResultSet
{
	private $data;
	private $sth;

	public function __construct(&$sth)
	{
		$this->sth = $sth;
		$this->bind_assoc();
	}

	public function fetch()
	{
		if (!mysqli_stmt_fetch($this->sth))
			$this->data = null;

		return $this->data;
	}

	public function free()
	{
		if ($this->sth)
		{
			mysqli_stmt_close($this->sth);
			$this->sth = null;
		}
	}

	private function bind_assoc()
	{
		if (!($meta = mysqli_stmt_result_metadata($this->sth)))
			return;

		$fields = array();
		$this->data = array();

		$fields[0] = $this->sth;
		$count = 1;

		while (($field = mysqli_fetch_field($meta)))
		{
			$fields[$count] = &$this->data[$field->name];
			$count++;
		}
		call_user_func_array('mysqli_stmt_bind_result',$fields);
	}
};
?>