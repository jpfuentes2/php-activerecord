<?php
include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/adapters/OciAdapter.php';

class OciAdapterTest extends AdapterTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp('oci');
	}

	public function test_get_sequence_name()
	{
		$this->assert_equals('authors_seq',$this->conn->get_sequence_name('authors','author_id'));
	}

	public function test_columns_text()
	{
		$author_columns = $this->conn->columns('authors');
		$this->assert_equals('varchar2',$author_columns['some_text']->raw_type);
		$this->assert_equals(100,$author_columns['some_text']->length);
	}

	public function test_datetime_to_string()
	{
		$this->assert_equals('01-JAN-2009 01:01:01 AM',$this->conn->datetime_to_string(date_create('2009-01-01 01:01:01 EST')));
	}

	public function test_insert_id() {}
	public function test_insert_id_with_params() {}
	public function test_insert_id_should_return_explicitly_inserted_id() {}
	public function test_columns_time() {}
	public function test_columns_sequence() {}
}
?>