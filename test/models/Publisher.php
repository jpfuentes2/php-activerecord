<?php
class Publisher extends ActiveRecord\Model
{
	static $pk = 'publisher_id';
	static $cache = true;
	static $cache_expire = 2592000; // 1 month. 60 * 60 * 24 * 30
}
