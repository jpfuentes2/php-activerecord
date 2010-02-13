<?php
class Event extends ActiveRecord\Model
{
	static $belongs_to = array(
		array('host'),
		array('venue')
	);

	static $delegate = array(
		array('state', 'address', 'to' => 'venue'),
		array('name', 'to' => 'host', 'prefix' => 'woot')
	);
};
?>