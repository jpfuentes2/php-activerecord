<?php
include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/adapters/OciAdapter.php';

class OciAdapterTest extends AdapterTest
{
	public function setUp($connection_name=null)
	{
		if (!in_array('oci', PDO::getAvailableDrivers()))
			$this->markTestSkipped('Oracle drivers are not present');

		parent::setUp('oci');
	}

	public function testGetSequenceName()
	{
		$this->assertEquals('authors_seq',$this->conn->get_sequence_name('authors'));
	}

	public function testInsertIdWithParams() {}
	public function testInsertId() {}
	public function testInsertIdShouldReturnExplicitlyInsertedId() {}
}
?>