<?php
use ActiveRecord\Column;

include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/adapters/MysqlAdapter.php';

class MysqlAdapterTest extends AdapterTest
{
	public function set_up($connection_name=null)
	{
		parent::set_up('mysql');
	}

	public function test_enum()
	{
		$author_columns = $this->conn->columns('authors');
		$this->assert_equals('enum',$author_columns['some_enum']->raw_type);
		$this->assert_equals(Column::STRING,$author_columns['some_enum']->type);
		$this->assert_same(null,$author_columns['some_enum']->length);
	}
}
?>