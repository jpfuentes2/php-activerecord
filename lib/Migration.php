<?php 
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

abstract class Migration {
	
	const ID	= 'id';
	const UP	= 1;
	const DOWN	= 2;
	
	private $connection;
	private $sql;
	private $columns;
	private $id = false;
	private $version;
	private $migrated = null;
	private $direction	= null;
	private $log	= array();
	
	
	
	public function __construct($connection) 
	{
		if (!$connection)
			throw new ActiveRecordException('A valid database connection is required.');
		
		$path	= pathinfo(__FILE__);
		$filename	= $path['filename'];
		$filenameArr= explode('_', $filename);
		$version	= $filenameArr[0];
		$this->connection	= $connection;
		$this->version	= $version;
		
		$migrated	= SchemaMigrations::find_by_version($this->version);
		if ($migrated) 
			$this->direction = self::DOWN;
		else 
			$this->direction = self::UP;
	}
	
	abstract public function change();
	
	public function log() {
		return $this->log;
	}
	
	private function pushLog($msg) {
		$this->log[]	= $msg;
		return $this;
	}
	
	public function migrated() {
		if ($this->migrated === null) {
			if ($this->direction === self::UP) {
				$this->migrated = true;
			} elseif ($this->direction === self::DOWN) {
				$this->migrated = false;
			} else {
				throw new MigrationException('Unexcepted value for direction while determining if has been migrated');
			}
		}
		
		return $this->migrated;
	}
	
	public function create_table($name, $closure)
	{
		if ($this->direction() === self::UP) {
			$this->connection->query($this->build_create_table($name, $closure));
			$this->pushLog("Created table $name {$this->connection->get_execution_time()} ms");
			return;
		} elseif ($this->direction() === self::DOWN) {
			$this->connection->query($this->build_drop_table($name, $closure));
			$this->pushLog("Dropped table $name {$this->connection->get_execution_time()} ms");
			return;
		}
		
		throw new MigrationException('Unknown error occured while creating table ' . $name);
	}
	
	public function set_direction($direction) {
		if ($direction != self::UP || $direction != self::DOWN) {
			throw new MigrationException('Unknown value for direction');
		}
		
		return $this;
	}
	
	public function migrate() {
		// try {
		$this->change();
		// } catch (\Exception $e) {
		// 	echo get_class($e) . "\n";
		// }
		
		return;
	}
	
	public function up() 
	{
		return $this->set_direction(self::UP)->migrate();
	}
	
	public function down() 
	{
		return $this->set_direction(self::DOWN)->migrate();
	}
	
	public function direction() 
	{
		if (!$this->direction) 
			throw new MigrationException('Unable to determine the direction');
		return $this->direction;
	}
	
	private function build_create_table($name, $closure) 
	{
		$this->sql	= "CREATE TABLE $name (";
		$closure();
		if (!$this->id) {
			$this->sql	.=	 $this->connection->column('primary_key') . ', ';
		}
		
		if (empty($this->columns))
			throw new MigrationException('No column definition in migration');
		
		$this->sql	.= $this->columns . ')';
		return $this->sql;
	}
	
	private function build_drop_table($name) 
	{
		$this->sql	= "DROP TABLE " . $name;
		return $this->sql;
	}
	
	private function column() {
		if (empty($this->columns)) {
			$this->columns	= '';
		} else {
			$this->columns	.= ', ';
		}
	
		$args	= func_get_args();
		if (strtolower($args[0]) == self::ID) {
			$this->id	= true;
		}
		$this->columns .= call_user_func_array(array($this->connection, 'column'), $args);
	}
	
	public function string($name, $length = null, $null = true) {
		$this->column($name, 'string', $length, $null);
	}
	
	public function text($name, $length = null, $null = true) {
		$this->column($name, 'text', $length, $null);
	}
	
	public function integer($name, $length = null, $null = true) {
		$this->column($name, 'integer', $length, $null);
	}
	
	public function float($name, $length = null, $null = true) {
		$this->column($name, 'float', $length, $null);
	}
	
	public function datetime($name, $length = null, $null = true) {
		$this->column($name, 'datetime', $length, $null);
	}
	
	public function timestamp($name, $length = null, $null = true) {
		$this->column($name, 'timestamp', $length, $null);
	}
	
	public function time($name, $length = null, $null = true) {
		$this->column($name, 'time', $length, $null);
	}
	
	public function date($name, $length = null, $null = true) {
		$this->column($name, 'date', $length, $null);
	}
	
	public function binary($name, $length = null, $null = true) {
		$this->column($name, 'binary', $length, $null);
	}
	
	public function boolean($name, $length = null, $null = true) {
		$this->column($name, 'boolean', $length, $null);
	}
	
	public function timestamps() {
		$this->timestamp('created_at');
		$this->timestamp('updated_at');
	}
	
}

class SchemaMigrations extends Model {}
?>