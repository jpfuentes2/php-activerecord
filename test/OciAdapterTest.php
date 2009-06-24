<?php
include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/adapters/OciAdapter.php';

class OciAdapterTest extends AdapterTest
{
	public function set_up($connection_name=null)
	{
		if (!in_array('oci', PDO::getAvailableDrivers()))
			$this->mark_test_skipped('Oracle drivers are not present');

		parent::set_up('oci');
	}

	public function test_get_sequence_name()
	{
		$this->assert_equals('authors_seq',$this->conn->get_sequence_name('authors'));
	}

	public function test_insert_id_with_params() {}
	public function test_insert_id() {}
	public function test_insert_id_should_return_explicitly_inserted_id() {}
}
?>