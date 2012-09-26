<?php
/**
 * Basic object class that supports mixins
 * @author Zachary Quintana
 * @package ActiveRecord
 */
namespace ActiveRecord;

class Object {
	
	public function respondsTo($method) {
		return method_exists($this, $method);
	}

}
