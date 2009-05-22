<?
class Venue extends ActiveRecord\Model
{
	static $has_many = array(array('events'));
	static $has_one;

	static $alias_attribute = array(
		'marquee' => 'name',
		'mycity' => 'city'
	);
};
?>