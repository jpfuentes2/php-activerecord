<?php

/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Aws\Common\Aws;
use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Enum\AttributeAction;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Common\Enum\Region;
use Guzzle\Service\Resource\ResourceIteratorInterface;
/**
 * Adapter for DynamoDB.
 *
 * @package ActiveRecord
 */

class DynamoAdapter 
{

	/**
	 * The DB connection object.
	 * @var mixed
	 */
	public $connection;
	/**
	 * The last query run.
	 * @var string
	 */
	public $last_query;
	/**
	 * Switch for logging.
	 *
	 * @var bool
	 */
	public $logging = false;
	/**
	 * Contains a Logger object that must impelement a log() method.
	 *
	 * @var object
	 */
	public $logger;
	/**
	 * The name of the protocol that is used.
	 * @var string
	 */
	public $protocol;
	/**
	 * Database memcached
	 * @var string
	 */
	public $cache;
	/**
	 * Database's date format
	 * @var string
	 */
	static $date_format = 'Y-m-d';
	/**
	 * Database's datetime format
	 * @var string
	 */
	static $datetime_format = 'Y-m-d H:i:s T';

	public function __construct($info)
	{
		$aws = Aws::factory(__DIR__ . '/../../../config.inc.php');
		$this->connection = $aws->get('dynamodb');
		$this->tables();
	}
	
	public function quote_name($string)
	{
		//throw new \Exception('quote_name');
		//echo "DynamoAdapter->quote_name({$string})<br>";
		return $string;
	}

	public function query($sql, &$values=array())
	{
		print_r(array('query' => $sql, 'values' => $values));

		throw new \Exception('query');

		if ($this->logging)
		{
			$this->logger->log($sql);
			if ( $values ) $this->logger->log($values);
		}

		$this->last_query = $sql;

		try {
			if (!($sth = $this->connection->prepare($sql)))
				throw new DatabaseException($this);
		} catch (PDOException $e) {
			throw new DatabaseException($this);
		}

		$sth->setFetchMode(PDO::FETCH_ASSOC);

		try {
			if (!$sth->execute($values))
				throw new DatabaseException($this);
		} catch (PDOException $e) {
			throw new DatabaseException($e);
		}
		return $sth;
	}

	public function tables()
	{
		$list = $this->connection->listTables()['TableNames'];
		
		$tables = array();

		foreach($list as $table)
		{
			$tables[] = $table;
		}

		return $tables;
	}

	public function columns($table)
	{
		//throw new \Exception("columns");
		$reflect = new \ReflectionClass($table);
		$constants = $reflect->getConstants();
		$columns = array();
		$aliases = array();

		$reserved = array('HashKeyElement', 'HashKeyType', 'RangeKeyElement', 'RangeKeyType');

		foreach($constants as $alias => $name)
		{
			if(!in_array($alias, $reserved))
			{
				$col = null;
				if(array_key_exists($name, $columns))
				{
					$col = $columns[$name];
				}
				else
				{
					$col = new Column();
					$col->name = $name;
					$col->inflected_name = $name;
					$col->nullable = true;
				}
				$columns[$name] = $col;
			}
			if($alias !== 'HashKeyType' && $alias !== 'RangeKeyType')
			{
				$aliases[$alias] = $name;
			}
		}

		$table::$alias_attribute = $aliases;

		if(array_key_exists('HashKeyElement', $constants))
		{
			$name = $constants['HashKeyElement'];
			$col = $columns[$name];
			$col->type = $constants['HashKeyType'];
			$col->pk = 'HashKeyElement';
			$columns[$name] = $col;
		}

		if(array_key_exists('RangeKeyElement', $constants))
		{
			$name = $constants['RangeKeyElement'];
			$col = $columns[$name];
			$col->type = $constants['RangeKeyType'];
			$col->pk = 'RangeKeyElement';
			$columns[$name] = $col;
		}

		return $columns;
	}

	/**
	 * Tells you if this adapter supports sequences or not.
	 *
	 * @return boolean
	 */
	function supports_sequences()
	{
		return false;
	}

}
