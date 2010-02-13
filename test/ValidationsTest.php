<?php
include 'helpers/config.php';

use ActiveRecord as AR;

class BookValidations extends ActiveRecord\Model
{
	static $table_name = 'books';
	static $validates_presence_of = array(array('name'));
	static $validates_uniqueness_of = array();
};

class ValidationsTest extends DatabaseTest
{
	public function set_up($connection_name=null)
	{
		parent::set_up($connection_name);

		BookValidations::$validates_uniqueness_of[0] = array('name');
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

	public function test_validates_uniqueness_of_with_multiple_fields()
	{
		BookValidations::$validates_uniqueness_of[0] = array(array('name','special'));
		$book1 = BookValidations::first();
		$book2 = new BookValidations(array('name' => $book1->name, 'special' => $book1->special+1));
		$this->assert_true($book2->is_valid());
	}

	public function test_validates_uniqueness_of_with_multiple_fields_is_not_unique()
	{
		BookValidations::$validates_uniqueness_of[0] = array(array('name','special'));
		$book1 = BookValidations::first();
		$book2 = new BookValidations(array('name' => $book1->name, 'special' => $book1->special));
		$this->assert_false($book2->is_valid());
		$this->assert_equals(array('Name and special must be unique'),$book2->errors->full_messages());
	}
};
?>