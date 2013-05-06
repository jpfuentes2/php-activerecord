<?php

/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

class NullInflector extends StandardInflector
{
	public function camelize($s) { return $s; }

	public function uncamelize($s) { return $s; }


	public function tableize($s) { return $s; }
	public function variablize($s) { return str_replace(array('-',' '),array('_','_'),(trim($s))); }
}
