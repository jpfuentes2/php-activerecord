<?php
namespace NamespaceTest\SubNamespaceTest;

class Page extends \ActiveRecord\Model
{
	static $belong_to = array(
		array('book', 'class_name' => '\NamespaceTest\Book'),
	);
}
?>
