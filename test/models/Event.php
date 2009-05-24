<?php
class Event extends ActiveRecord\Model
{
	static $belongs_to = array(array('host'));
};
?>