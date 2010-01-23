<?php
include 'helpers/config.php';

use ActiveRecord as AR;

class BookValidations extends ActiveRecord\Model {
	static $table_name = 'books';
	static $validates_presence_of = array(array('name'));
	static $validates_uniqueness_of = array(array('name'));
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

	public function test_is_invalid()
	{
		$book = new Book();
		$this->assert_false($book->is_invalid());
	}

	public function test_is_invalid_is_true()
	{
		$book = new BookValidations();
		$this->assert_true($book->is_invalid());
	}

	public function test_is_iterable()
	{
		$book = new BookValidations();
		$book->is_valid();

		foreach ($book->errors as $name => $message)
			$this->assert_equals("Name can't be blank",$message);
	}

	public function test_full_messages()
	{
		$book = new BookValidations();
		$book->is_valid();

		$this->assert_equals(array("Name can't be blank"),array_values($book->errors->full_messages(array('hash' => true))));
	}

	public function test_validates_uniqueness_of()
	{
		BookValidations::connection()->query("delete from books where name='bob'");
		BookValidations::create(array('name' => 'bob'));
		$book = BookValidations::create(array('name' => 'bob'));

		$this->assert_equals(array("Name must be unique"),$book->errors->full_messages());
		$this->assert_equals(1,BookValidations::count(array('conditions' => "name='bob'")));
	}

	public function test_validates_uniqueness_of_excludes_self()
	{
		$book = BookValidations::first();
		$this->assert_equals(true,$book->is_valid()); 
	}
};
?>