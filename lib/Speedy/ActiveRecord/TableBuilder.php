<?php 
namespace Speedy\ActiveRecord;

class TableBuilder
{
	
	private $sql;
	private $defaults;
	private $connection;
	
	
	
	public function __construct($connection) {
		if (!$connection)
		throw new Speedy\ActiveRecordException('A valid database connection is required.');
	
		$this->connection	= $connection;
	}
	
	public function column() {
		$args	= func_get_args();
		if (empty($args))
			return false;

		
		
	}
	
}
?>
