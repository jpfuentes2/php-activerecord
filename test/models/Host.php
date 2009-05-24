<?php
class Host extends ActiveRecord\Model
{
	static $has_many = array(array('events'));
}
?>