<?php
use ActiveRecord\Column;

include 'helpers/config.php';
require_once __DIR__ . '/../lib/adapters/SqlsrvAdapter.php';

class SqlsrvAdapterTest extends AdapterTest
{
	public function set_up($connection_name=null)
	{
		parent::set_up('sqlsrv');
	}

	public function test_insert_id_should_return_explicitly_inserted_id()
	{
		$this->conn->query('SET IDENTITY_INSERT authors ON; INSERT INTO authors(author_id,name) VALUES(99,\'name\')');
		$this->assert_true($this->conn->insert_id() > 0);
	}

	public function test_date() {}
	public function test_columns_time() {}

	public function test_transaction_commit()
	{
		$original = $this->conn->query_and_fetch_one("SELECT COUNT(*) FROM authors");

		$this->conn->transaction();
		$this->conn->query("SET IDENTITY_INSERT authors ON; INSERT INTO authors(author_id,name) VALUES(9999,'blahhhhhhhh')");
		$this->conn->commit();

		$this->assert_equals($original+1,$this->conn->query_and_fetch_one("SELECT COUNT(*) FROM authors"));
	}

	public function test_transaction_rollback()
	{
		$original = $this->conn->query_and_fetch_one("SELECT COUNT(*) FROM authors");

		$this->conn->transaction();
		$this->conn->query("SET IDENTITY_INSERT authors ON; INSERT INTO authors(author_id,name) VALUES(9999,'blahhhhhhhh')");
		$this->conn->rollback();

		$this->assert_equals($original,$this->conn->query_and_fetch_one("SELECT COUNT(*) FROM authors"));
	}

	public function test_quote_name_does_not_over_quote()
	{
		$c = $this->conn;
		$qn = function($s) use ($c) { return $c->quote_name($s); };

		$this->assert_equals("[string", $qn("[string"));
		$this->assert_equals("string]", $qn("string]"));
		$this->assert_equals("[string]", $qn("[string]"));
	}
}
?>
