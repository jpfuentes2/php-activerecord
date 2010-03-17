<?php
class Amenity extends ActiveRecord\Model
{
	static $table_name = 'amenities';
	static $primary_key = 'amenity_id';

	static $has_many = array(
		array('property_amenities')
	);
};
?>