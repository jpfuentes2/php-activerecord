<?
include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/adapters/MysqliAdapter.php';

class MysqliAdapterTest extends AdapterTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp('mysqli');
	}

	// not supported
	public function testInsertIdShouldReturnExplicitlyInsertedId() {}
}
?>