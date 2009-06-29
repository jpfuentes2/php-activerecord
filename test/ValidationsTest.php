<?php
include 'helpers/config.php';

use ActiveRecord as AR;

class BookValidations extends ActiveRecord\Model {
	static $table_name = 'books';
	static $validates_presence_of = array(array('name'));
};

class ValidationsTest extends SnakeCase_PHPUnit_Framework_TestCase
{
	public function set_up()
	{
	}

	public function test_is_valid_invokes_validations()
	{
		$book = new Book;
		$this->assert_true(empty($book->errors));
		$book->is_valid();
		$this->assert_false(empty($book->errors));
	}

	public function test_is_valid_returns_true_if_no_validations_exist()
	{
		$book = new Book;
		$this->assert_true($book->is_valid());
	}

	public function test_is_valid_returns_false_if_failed_validations()
	{
		$book = new BookValidations;
		$this->assert_false($book->is_valid());
	}
};
?>