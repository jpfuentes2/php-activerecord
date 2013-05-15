<?php

/**
 * Any book found with the name "AwesomeBook" will automatically be created as an AwesomeBook
 * as opposed to an InstantiateBook
 */
class InstantiateBook extends ActiveRecord\Model
{
	public static $table_name = 'books';
	
	public static function instantiate($attributes)
	{
		if(isset($attributes['name']))
		{
			if($attributes['name'] == 'AwesomeBook')
				return 'AwesomeBook';
			elseif($attributes['name'] == 'NotAwesomeBook')
				return 'InvalidAwesomeBook';
		}
		
		return parent::instantiate($attributes);
	}
}

class AwesomeBook extends InstantiateBook
{
	public static $table_name = 'books';
}

class InvalidAwesomeBook extends ActiveRecord\Model
{
	public static $table_name = 'books';
}
?>