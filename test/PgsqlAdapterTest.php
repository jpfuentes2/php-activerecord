<?php
use ActiveRecord\Column;

include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/adapters/PgsqlAdapter.php';

class PgsqlAdapterTest extends AdapterTest
{
	public function set_up($connection_name=null)
	{
		parent::set_up('pgsql');
	}

	public function test_insert_id()
	{
		$this->conn->query("INSERT INTO authors(author_id,name) VALUES(nextval('authors_author_id_seq'),'name')");
		$this->assert_true($this->conn->insert_id('authors_author_id_seq') > 0);
	}

	public function test_insert_id_with_params()
	{
		$x = array('name');
		$this->conn->query("INSERT INTO authors(author_id,name) VALUES(nextval('authors_author_id_seq'),?)",$x);
		$this->assert_true($this->conn->insert_id('authors_author_id_seq') > 0);
	}

	public function test_insert_id_should_return_explicitly_inserted_id()
	{
		$this->conn->query('INSERT INTO authors(author_id,name) VALUES(99,\'name\')');
		$this->assert_true($this->conn->insert_id('authors_author_id_seq') > 0);
	}
}
?>