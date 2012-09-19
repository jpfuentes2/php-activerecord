<?php 
/**
 * @package Speedy\ActiveRecord
 */
namespace Speedy\ActiveRecord;


use \Speedy\ActiveRecord\Exceptions\Exception as ActiveRecordException;
use \Speedy\ActiveRecord\Exceptions\MigrationException;

abstract class Migration {
	
	const ID	= 'id';
	const UP	= 1;
	const DOWN	= 2;
	
	private $connection;
	private $sql;
	
	/**
	 * Sql representation of columns
	 * @var string
	 */
	private $columns;
	private $id = false;
	private $version;
	private $record;
	private $migrated = null;
	private $direction	= null;
	private $log	= array();
	
	
	
	public function __construct($connection) 
	{
		if (!$connection)
			throw new ActiveRecordException('A valid database connection is required.');
		
		$reflection	= new \ReflectionClass($this);
		$path	= pathinfo($reflection->getFileName());
		$filename	= $path['filename'];
		$filenameArr= explode('_', $filename);
		$version	= $filenameArr[0];
		$this->connection	= $connection;
		$this->set_version($version);
		
		if ($this->migrated()) 
			$this->direction = self::DOWN;
		else 
			$this->direction = self::UP;
	}
	
	abstract public function change();
	
	/**
	 * Getter for record
	 */
	public function record() {	
		return $this->record;
	}
	
	/**
	 * Record setter
	 * @param \ActiveRecord\Model $record
	 * @return \ActiveRecord\Migration
	 */
	private function set_record($record) {
		$this->record	= $record;
		return $this;
	}
	
	public function log() {
		return $this->log;
	}
	
	private function pushLog($msg) {
		$this->log[]	= $msg;
		return $this;
	}
	
	/**
	 * Test if current migration has been migrated already
	 * @throws MigrationException
	 * @return boolean
	 */
	public function migrated() {
		if ($this->migrated === null) {
			$this->set_record(SchemaMigration::find_by_version($this->version()));
		
			if ($this->record()) 
				$this->migrated	= true;
			else
				$this->migrated = false;
		}

		return $this->migrated;
	}
	
	/**
	 * Setter for version
	 * @param integer $version
	 */
	private function set_version($version) {
		$this->version	= $version;
		return $this;
	}
	
	/**
	 * Version getter
	 * @return integer
	 */
	public function version() {
		return $this->version;
	}
	
	public function query($sql) {
		return $this->connection->query($sql);
	}
	
	public function create_table($name, $closure)
	{
		if ($this->direction() === self::UP) {
			$this->query($this->build_create_table($name, $closure));
			$this->pushLog("Created table $name {$this->connection->get_execution_time()} ms");
			return;
		} elseif ($this->direction() === self::DOWN) {
			$this->query($this->build_drop_table($name));
			$this->pushLog("Dropped table $name {$this->connection->get_execution_time()} ms");
			return;
		}
		
		throw new MigrationException('Unknown error occured while creating table ' . $name);
	}
	
	public function add_column($table_name, $column, $type, $length = null, $null = true)
	{
		if ($this->direction() === self::UP) {
			$this->query($this->build_add_column($table_name, $column, $type, $length, $null));
			$this->pushLog("Added column $column to $table_name {$this->connection->get_execution_time()} ms");
			return;
		} elseif ($this->direction() === self::DOWN) {
			$this->query($this->build_drop_column($table_name, $column));
			$this->pushLog("Droppped column $column from $table_name {$this->connection->get_execution_time()}");
			return;
		}
		
		throw new MigrationException('Unable to determine direction in current migration');
	}
	
	public function set_direction($direction) {
		if ($direction != self::UP && $direction != self::DOWN) {
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
	
	public function runUp() {
		$this->set_direction(self::UP)->migrate();
		$this->up();
		
		$record	= new SchemaMigration(array('version' => $this->version()));
		$record->save();
		$this->set_record($record);
		
		return;
	}
	
	public function up() {}
	
	public function down() {}
	
	public function runDown()
	{
		$this->set_direction(self::DOWN)->migrate();
		$this->down();
		$this->record()->delete();
		
		return;
	}
	
	public function direction() 
	{
		if (!$this->direction) 
			throw new MigrationException('Unable to determine the direction');
		return $this->direction;
	}
	
	private function build_drop_column($table_name, $column)
	{
		return "ALTER TABLE $table_name DROP COLUMN $column";
	}
	
	private function build_add_column($table_name, $column, $type, $length = null, $null = true) {
		$this->column($column, $type, $length, $null);
		
		return "ALTER TABLE $table_name ADD COLUMN {$this->columns}";
	}
	
	private function build_create_table($name, $closure) 
	{
		$this->sql	= "CREATE TABLE $name (";
		$closure();
		if (!$this->id) {
			$this->sql	.=	'id ' . $this->connection->column('primary_key') . ', ';
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

?>
