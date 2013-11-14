<?php

/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

use PDO;

class NullInflector extends StandardInflector
{
	public function camelize($s) { return $s; }

	public function uncamelize($s) { return $s; }


	public function tableize($s) { return $s; }
	public function variablize($s) { return str_replace(array('-',' '),array('_','_'),(trim($s))); }

	public function pdo_options()
	{
		return array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
		PDO::ATTR_STRINGIFY_FETCHES => false);
	}

	public function fix_case($x) { return ($x); }

}
