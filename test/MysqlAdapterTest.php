<?
include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/adapters/MysqlAdapter.php';

class MysqlAdapterTest extends AdapterTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp('mysql');
	}
}
?>