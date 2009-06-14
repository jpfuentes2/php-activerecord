<?php
include 'helpers/config.php';
require_once dirname(__FILE__) . '/../lib/adapters/MysqlAdapter.php';

class MysqlAdapterTest extends AdapterTest
{
	public function set_up($connection_name=null)
	{
		parent::set_up('mysql');
	}
}
?>