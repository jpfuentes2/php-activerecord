<?php
class Author extends ActiveRecord\Model
{
	static $pk = 'author_id';
	static $has_one = array(array('awesome_person', 'foreign_key' => 'author_id', 'primary_key' => 'author_id'));
};
?>