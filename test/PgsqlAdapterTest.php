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
}
?>