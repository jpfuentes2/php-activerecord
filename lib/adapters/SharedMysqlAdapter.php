<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

require_once('MysqlAdapter.php');

/**
 * Adapter for MySQL that uses a static connection shared by every instance of this class. For use
 * by tests so that each test can be wrapped in a transaction for performance reasons. Transactions
 * are connection-specific, so we have to force everything to use a single connection.
 *
 * @package ActiveRecord
 */
class SharedMysqlAdapter extends MysqlAdapter
{
	private $db;
	static $currentDefaultDb;
	static $sharedConnection;

	protected function __construct($info)
	{
		$this->db = $info->db;
		if (!isset(self::$sharedConnection))
		{
			$info->protocol = 'mysql';
			parent::__construct($info);
			self::$sharedConnection = $this->connection;
		}
		else
		{
			$this->connection = self::$sharedConnection;
		}
	}

	public function query($sql, &$values = array())
	{
		if (!isset(self::$currentDefaultDb) || self::$currentDefaultDb != $this->db)
		{
			// switch current default database to what this connection would use if it wasn't shared
			$this->connection->exec('USE ' . $this->db);
			self::$currentDefaultDb = $this->db;
		}
		return parent::query($sql, $values);
	}
}
