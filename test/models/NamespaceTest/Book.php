<?php
namespace NamespaceTest;

class Book extends \ActiveRecord\Model
{
	static $belongs_to = array(
		array('parent_book', 'class_name' => '\NamespaceTest\Book'),
		array('parent_book_2', 'class_name' => 'Book'),
		array('parent_book_3', 'class_name' => '\Book'),
	);

	static $has_many = array(
		array('pages', 'class_name' => '\NamespaceTest\SubNamespaceTest\Page'),
		array('pages_2', 'class_name' => 'SubNamespaceTest\Page'),
	);
}
?>
