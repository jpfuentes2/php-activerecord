<?php
class Venue extends ActiveRecord\Model
{
	static $has_many = array(
		array('events'),
		array('hosts', 'through' => 'events')
	);

	static $has_one;

	static $alias_attribute = array(
		'marquee' => 'name',
		'mycity' => 'city'
	);
};
?>