<?php
include 'helpers/config.php';

use ActiveRecord\Cache;

class ActiveRecordCacheTest extends DatabaseTest
{
	public function set_up($connection_name=null)
	{
		parent::set_up($connection_name);
		ActiveRecord\Config::instance()->set_cache('memcache://localhost');
	}

	public function tear_down()
	{
		Cache::flush();
		Cache::initialize(null);
	}

	public function test_caches_column_meta_data()
	{
		Author::first();

		$table_name = Author::table()->get_fully_qualified_table_name(!($this->conn instanceof ActiveRecord\PgsqlAdapter));
		$value = Cache::$adapter->read("get_meta_data-$table_name");
		$this->assert_true(is_array($value));
	}
}
?>
