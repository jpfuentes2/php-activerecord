<?php

/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

class CasePreservingInflector extends StandardInflector
{
	public function tableize($s) { return Utils::pluralize(($this->underscorify($s))); }
	public function variablize($s) { return str_replace(array('-',' '),array('_','_'),(trim($s))); }
}
