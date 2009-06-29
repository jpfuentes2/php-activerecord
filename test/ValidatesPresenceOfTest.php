<?php
include 'helpers/config.php';

class BookPresence extends ActiveRecord\Model
{
	static $table_name = 'books';

	static $validates_presence_of = array(
		array('name')
	);
}

class ValidatesPresenceOfTest extends DatabaseTest
{
	public function test_presence()
	{
		$book = new BookPresence;
		$book->name = 'blah';
		$book->save();
		$this->assert_false($book->errors->is_invalid('name'));
	}

	public function test_invalid_null()
	{
		$book = new BookPresence;
		$book->name = null;
		$book->save();
		$this->assert_true($book->errors->is_invalid('name'));
	}

	public function test_invalid_blank()
	{
		$book = new BookPresence;
		$book->name = '';
		$book->save();
		$this->assert_true($book->errors->is_invalid('name'));
	}

	public function test_valid_white_space()
	{
		$book = new BookPresence;
		$book->name = ' ';
		$book->save();
		$this->assert_false($book->errors->is_invalid('name'));
	}

	public function test_custom_message()
	{
		BookPresence::$validates_presence_of[0]['message'] = 'is using a custom message.';

		$book = new BookPresence;
		$book->name = null;
		$book->save();
		$this->assert_equals('is using a custom message.', $book->errors->on('name'));
	}
};
?>